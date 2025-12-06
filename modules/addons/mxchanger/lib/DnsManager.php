<?php
/**
 * WHMCS MX Changer - DNS Manager Class
 *
 * Handles MX record retrieval and updates via cPanel Email API
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

        // Use Email API to list MX records
        $response = $this->callEmailApi('listmxs', [
            'domain' => $domain,
        ]);

        // Handle various response structures
        $data = [];
        if (isset($response['result']['data'])) {
            $data = $response['result']['data'];
        } elseif (isset($response['cpanelresult']['data'])) {
            $data = $response['cpanelresult']['data'];
        } elseif (isset($response['data'])) {
            $data = $response['data'];
        }

        // Ensure $data is an array
        if (!is_array($data)) {
            $data = [];
        }

        $records = [];
        foreach ($data as $record) {
            if (!is_array($record)) {
                continue;
            }

            // Get priority
            $priority = $record['priority'] ?? $record['preference'] ?? $record['pref'] ?? 0;

            // Get mail server (exchange or mx)
            $host = $record['exchange'] ?? $record['mx'] ?? $record['exchanger'] ?? '';
            $host = rtrim($host, '.');

            if (empty($host)) {
                continue;
            }

            $records[] = [
                'priority' => (int)$priority,
                'host' => $host,
            ];
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

        // Step 2: Remove existing MX records using Email API
        foreach ($currentRecords as $record) {
            try {
                // Try without trailing dot first
                $this->callEmailApi('delmx', [
                    'domain' => $domain,
                    'exchange' => $record['host'],
                    'preference' => $record['priority'],
                ]);
            } catch (\Exception $e) {
                // Try with trailing dot as fallback
                try {
                    $this->callEmailApi('delmx', [
                        'domain' => $domain,
                        'exchange' => $record['host'] . '.',
                        'preference' => $record['priority'],
                    ]);
                } catch (\Exception $e2) {
                    $errors[] = 'Failed to remove: ' . $record['host'] . ' - ' . $e->getMessage();
                }
            }
        }

        // Step 3: Add Google MX records using Email API
        foreach (self::GOOGLE_MX_RECORDS as $mxRecord) {
            try {
                $this->callEmailApi('addmx', [
                    'domain' => $domain,
                    'exchange' => $mxRecord['exchange'],
                    'preference' => $mxRecord['priority'],
                ]);
            } catch (\Exception $e) {
                $errors[] = 'Failed to add: ' . $mxRecord['exchange'] . ' - ' . $e->getMessage();
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

        // Step 2: Remove existing MX records using Email API
        foreach ($currentRecords as $record) {
            try {
                // Try without trailing dot first
                $this->callEmailApi('delmx', [
                    'domain' => $domain,
                    'exchange' => $record['host'],
                    'preference' => $record['priority'],
                ]);
            } catch (\Exception $e) {
                // Try with trailing dot as fallback
                try {
                    $this->callEmailApi('delmx', [
                        'domain' => $domain,
                        'exchange' => $record['host'] . '.',
                        'preference' => $record['priority'],
                    ]);
                } catch (\Exception $e2) {
                    $errors[] = 'Failed to remove: ' . $record['host'] . ' - ' . $e->getMessage();
                }
            }
        }

        // Step 3: Add local MX record pointing to the domain itself
        $localMxRecord = [
            'priority' => 0,
            'exchange' => $domain . '.',
        ];

        try {
            $this->callEmailApi('addmx', [
                'domain' => $domain,
                'exchange' => $localMxRecord['exchange'],
                'preference' => $localMxRecord['priority'],
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
     * Call cPanel API via WHM
     *
     * @param string $function API function name
     * @param array $params API parameters
     * @param string $module API module (default: ZoneEdit)
     * @return array API response
     */
    protected function callCpanelApi($function, $params = [], $module = 'ZoneEdit')
    {
        $server = $this->serverData;
        $service = $this->serviceData;

        $hostname = $server->hostname ?: $server->ipaddress;
        $username = $server->username ?: 'root';
        $accessHash = trim(preg_replace('/\s+/', '', $server->accesshash ?? ''));
        $secure = ($server->secure === 'on');
        $protocol = $secure ? 'https' : 'http';
        $cpanelUser = $service->username;

        // Build query params
        $queryParams = array_merge([
            'cpanel_jsonapi_user' => $cpanelUser,
            'cpanel_jsonapi_apiversion' => '2',
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
        ], $params);

        $url = "{$protocol}://{$hostname}:2087/json-api/cpanel?" . http_build_query($queryParams);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        // Auth via access hash or password
        if (!empty($accessHash)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: whm {$username}:{$accessHash}"]);
        } else {
            $password = $this->decryptPassword($server->password);
            curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("Connection failed: {$curlError}");
        }

        if ($httpCode === 401) {
            throw new \Exception("Auth failed (401) - Verify API token for {$username}@{$hostname}");
        }

        if ($httpCode !== 200) {
            throw new \Exception("HTTP {$httpCode} from {$hostname}");
        }

        $data = json_decode($response, true);
        if (!$data) {
            throw new \Exception("Invalid JSON: " . substr($response, 0, 100));
        }

        // Check for API2 error responses
        if (isset($data['cpanelresult']['error']) && $data['cpanelresult']['error']) {
            throw new \Exception($data['cpanelresult']['error']);
        }

        // Check for event result errors
        if (isset($data['cpanelresult']['event']['result']) && $data['cpanelresult']['event']['result'] == 0) {
            $reason = $data['cpanelresult']['event']['reason'] ?? 'Unknown error';
            throw new \Exception($reason);
        }

        // Check data-level errors (some API2 calls return errors in data)
        if (isset($data['cpanelresult']['data'][0]['result']['status']) && $data['cpanelresult']['data'][0]['result']['status'] == 0) {
            $reason = $data['cpanelresult']['data'][0]['result']['statusmsg'] ?? 'Operation failed';
            throw new \Exception($reason);
        }

        if (isset($data['cpanelresult']['data'])) {
            return ['result' => ['data' => $data['cpanelresult']['data']]];
        }

        return $data;
    }

    /**
     * Call cPanel Email API for MX operations
     *
     * @param string $function API function name
     * @param array $params API parameters
     * @return array API response
     */
    protected function callEmailApi($function, $params = [])
    {
        return $this->callCpanelApi($function, $params, 'Email');
    }

    /**
     * Decrypt WHMCS encrypted password
     *
     * @param string $encryptedPassword
     * @return string
     */
    protected function decryptPassword($encryptedPassword)
    {
        // Try multiple decryption methods for compatibility

        // Method 1: Use WHMCS decrypt function directly (most reliable)
        if (function_exists('decrypt')) {
            $decrypted = decrypt($encryptedPassword);
            if (!empty($decrypted)) {
                return $decrypted;
            }
        }

        // Method 2: localAPI (works in proper admin context)
        $result = localAPI('DecryptPassword', ['password2' => $encryptedPassword]);
        if (!empty($result['password'])) {
            return $result['password'];
        }

        // Method 3: Try the internal WHMCS function
        if (class_exists('\\WHMCS\\Security\\Encryption')) {
            try {
                $decrypted = \WHMCS\Security\Encryption::decode($encryptedPassword);
                if (!empty($decrypted)) {
                    return $decrypted;
                }
            } catch (\Exception $e) {
                // Continue to next method
            }
        }

        throw new \Exception('Password decryption failed - unable to retrieve cPanel credentials');
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
