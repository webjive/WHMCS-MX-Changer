<?php
/**
 * WHMCS MX Changer - DNS Manager Class
 *
 * Handles DNS record retrieval and updates via cPanel Extended API
 *
 * @package    WHMCS
 * @author     WebJIVE
 * @copyright  Copyright (c) WebJIVE
 * @link       https://webjive.com
 */

namespace MXChanger;

use WHMCS\Database\Capsule;

class DnsManager
{
    /**
     * Google Workspace MX Records
     */
    const GOOGLE_MX_RECORDS = [
        ['priority' => 1, 'exchange' => 'ASPMX.L.GOOGLE.COM.'],
        ['priority' => 5, 'exchange' => 'ALT1.ASPMX.L.GOOGLE.COM.'],
        ['priority' => 5, 'exchange' => 'ALT2.ASPMX.L.GOOGLE.COM.'],
        ['priority' => 10, 'exchange' => 'ALT3.ASPMX.L.GOOGLE.COM.'],
        ['priority' => 10, 'exchange' => 'ALT4.ASPMX.L.GOOGLE.COM.'],
    ];

    /**
     * MX configuration types
     */
    const MX_TYPE_GOOGLE = 'google';
    const MX_TYPE_LOCAL = 'local';

    /**
     * @var int
     */
    protected $serviceId;

    /**
     * @var array
     */
    protected $serviceData;

    /**
     * @var array
     */
    protected $serverData;

    /**
     * Constructor
     *
     * @param int $serviceId WHMCS service/hosting ID
     */
    public function __construct($serviceId)
    {
        $this->serviceId = (int)$serviceId;
        $this->loadServiceData();
    }

    /**
     * Load service and server data from WHMCS database
     */
    protected function loadServiceData()
    {
        $this->serviceData = Capsule::table('tblhosting')
            ->where('id', $this->serviceId)
            ->first();

        if (!$this->serviceData) {
            throw new \Exception('Service not found');
        }

        $this->serverData = Capsule::table('tblservers')
            ->where('id', $this->serviceData->server)
            ->first();

        if (!$this->serverData) {
            throw new \Exception('Server not found for this service');
        }
    }

    /**
     * Get the domain for this service
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->serviceData->domain;
    }

    /**
     * Get the client ID for this service
     *
     * @return int
     */
    public function getClientId()
    {
        return (int)$this->serviceData->userid;
    }

    /**
     * Get server hostname
     *
     * @return string
     */
    public function getServerName()
    {
        return $this->serverData->hostname ?? $this->serverData->name;
    }

    /**
     * Get current MX records from cPanel
     *
     * @return array
     */
    public function getCurrentMxRecords()
    {
        $domain = $this->getDomain();
        $response = $this->callCpanelApi('fetchzone_records', [
            'domain' => $domain,
            'type' => 'MX',
        ]);

        if (!isset($response['result']['data'])) {
            return [];
        }

        $records = [];
        foreach ($response['result']['data'] as $record) {
            if ($record['type'] === 'MX') {
                $records[] = [
                    'line' => $record['line'] ?? null,
                    'priority' => (int)$record['preference'],
                    'host' => rtrim($record['exchange'], '.'),
                    'name' => $record['name'] ?? $domain,
                ];
            }
        }

        // Sort by priority
        usort($records, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $records;
    }

    /**
     * Update MX records to Google Workspace configuration
     *
     * @return array Result with success status and message
     */
    public function updateToGoogleMx()
    {
        $domain = $this->getDomain();
        $errors = [];

        // Step 1: Get current MX records
        $currentRecords = $this->getCurrentMxRecords();
        $oldRecordsJson = json_encode($currentRecords);

        // Step 2: Remove existing MX records
        foreach ($currentRecords as $record) {
            if (isset($record['line'])) {
                try {
                    $this->callCpanelApi('remove_zone_record', [
                        'domain' => $domain,
                        'line' => $record['line'],
                    ]);
                } catch (\Exception $e) {
                    $errors[] = 'Failed to remove record: ' . $record['host'] . ' - ' . $e->getMessage();
                }
            }
        }

        // Step 3: Add Google MX records
        foreach (self::GOOGLE_MX_RECORDS as $mxRecord) {
            try {
                $this->callCpanelApi('add_zone_record', [
                    'domain' => $domain,
                    'name' => $domain . '.',
                    'type' => 'MX',
                    'preference' => $mxRecord['priority'],
                    'exchange' => $mxRecord['exchange'],
                    'ttl' => 14400,
                ]);
            } catch (\Exception $e) {
                $errors[] = 'Failed to add record: ' . $mxRecord['exchange'] . ' - ' . $e->getMessage();
            }
        }

        // Step 4: Log the change
        $success = empty($errors);
        $this->logChange($oldRecordsJson, json_encode(self::GOOGLE_MX_RECORDS), $success, implode('; ', $errors), 'google');

        return [
            'success' => $success,
            'message' => $success ? 'MX records updated successfully' : implode('; ', $errors),
            'errors' => $errors,
        ];
    }

    /**
     * Restore MX records to default cPanel/local mail configuration
     * Points mail to the hosting server itself
     *
     * @return array Result with success status and message
     */
    public function restoreToLocalMx()
    {
        $domain = $this->getDomain();
        $errors = [];

        // Step 1: Get current MX records
        $currentRecords = $this->getCurrentMxRecords();
        $oldRecordsJson = json_encode($currentRecords);

        // Step 2: Remove existing MX records
        foreach ($currentRecords as $record) {
            if (isset($record['line'])) {
                try {
                    $this->callCpanelApi('remove_zone_record', [
                        'domain' => $domain,
                        'line' => $record['line'],
                    ]);
                } catch (\Exception $e) {
                    $errors[] = 'Failed to remove record: ' . $record['host'] . ' - ' . $e->getMessage();
                }
            }
        }

        // Step 3: Add local MX record pointing to the server/domain
        // Default cPanel MX record points to mail.domain.com or the domain itself
        $serverHostname = $this->getServerName();
        $localMxRecord = [
            'priority' => 0,
            'exchange' => $domain . '.',
        ];

        try {
            $this->callCpanelApi('add_zone_record', [
                'domain' => $domain,
                'name' => $domain . '.',
                'type' => 'MX',
                'preference' => $localMxRecord['priority'],
                'exchange' => $localMxRecord['exchange'],
                'ttl' => 14400,
            ]);
        } catch (\Exception $e) {
            $errors[] = 'Failed to add local MX record: ' . $e->getMessage();
        }

        // Step 4: Log the change
        $success = empty($errors);
        $newRecords = [$localMxRecord];
        $this->logChange($oldRecordsJson, json_encode($newRecords), $success, implode('; ', $errors), 'local');

        return [
            'success' => $success,
            'message' => $success ? 'MX records restored to local mail server' : implode('; ', $errors),
            'errors' => $errors,
            'new_records' => $newRecords,
        ];
    }

    /**
     * Detect current MX configuration type
     *
     * @return string 'google', 'local', or 'other'
     */
    public function detectMxType()
    {
        $records = $this->getCurrentMxRecords();

        if (empty($records)) {
            return 'none';
        }

        // Check for Google MX records
        $googleHosts = ['aspmx.l.google.com', 'alt1.aspmx.l.google.com', 'alt2.aspmx.l.google.com',
                        'alt3.aspmx.l.google.com', 'alt4.aspmx.l.google.com'];

        foreach ($records as $record) {
            $host = strtolower($record['host']);
            if (in_array($host, $googleHosts) || strpos($host, 'google.com') !== false) {
                return self::MX_TYPE_GOOGLE;
            }
        }

        // Check for local MX (points to domain itself or mail.domain)
        $domain = strtolower($this->getDomain());
        foreach ($records as $record) {
            $host = strtolower($record['host']);
            if ($host === $domain || $host === 'mail.' . $domain) {
                return self::MX_TYPE_LOCAL;
            }
        }

        return 'other';
    }

    /**
     * Call cPanel Extended API
     *
     * @param string $function API function name
     * @param array $params API parameters
     * @return array API response
     */
    protected function callCpanelApi($function, $params = [])
    {
        $server = $this->serverData;
        $service = $this->serviceData;

        // Build cPanel API URL
        $protocol = $server->secure === 'on' ? 'https' : 'http';
        $port = $server->secure === 'on' ? 2083 : 2082;
        $hostname = $server->hostname ?: $server->ipaddress;

        // Decrypt password
        $password = $this->decryptPassword($service->password);

        // API endpoint for UAPI
        $url = "{$protocol}://{$hostname}:{$port}/execute/DNS/{$function}";

        // Build query string
        $queryString = http_build_query($params);

        // Initialize cURL
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '?' . $queryString,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $service->username . ':' . $password,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        // For POST requests (add/remove records)
        if (in_array($function, ['add_zone_record', 'remove_zone_record'])) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \Exception('HTTP error: ' . $httpCode);
        }

        $data = json_decode($response, true);

        if (!$data) {
            throw new \Exception('Invalid JSON response from cPanel');
        }

        if (isset($data['errors']) && !empty($data['errors'])) {
            throw new \Exception(implode(', ', $data['errors']));
        }

        return $data;
    }

    /**
     * Decrypt WHMCS encrypted password
     *
     * @param string $encryptedPassword
     * @return string
     */
    protected function decryptPassword($encryptedPassword)
    {
        return localAPI('DecryptPassword', ['password2' => $encryptedPassword])['password'] ?? '';
    }

    /**
     * Log MX change to database
     *
     * @param string $oldRecords JSON of old records
     * @param string $newRecords JSON of new records
     * @param bool $success Whether the change was successful
     * @param string $errorMessage Error message if failed
     * @param string $actionType Type of action (google, local)
     */
    protected function logChange($oldRecords, $newRecords, $success, $errorMessage = '', $actionType = 'google')
    {
        try {
            Capsule::table('mod_mxchanger_log')->insert([
                'admin_id' => $_SESSION['adminid'] ?? 0,
                'client_id' => $this->getClientId(),
                'service_id' => $this->serviceId,
                'domain' => $this->getDomain(),
                'old_records' => $oldRecords,
                'new_records' => $newRecords,
                'status' => $success ? 'success' : 'failed',
                'error_message' => $errorMessage ?: null,
                'action_type' => $actionType,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Silently fail logging - don't break the main operation
            error_log('MX Changer log error: ' . $e->getMessage());
        }
    }
}
