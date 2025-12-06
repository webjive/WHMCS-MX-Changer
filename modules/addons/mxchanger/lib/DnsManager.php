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
    const MX_TYPE_OFFICE365 = 'office365';
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
        // The listmxs API returns: data[0].entries[] for the actual MX records
        $entries = [];

        // Check for entries inside data[0] (cPanel Email API structure)
        if (isset($response['result']['data'][0]['entries'])) {
            $entries = $response['result']['data'][0]['entries'];
        } elseif (isset($response['cpanelresult']['data'][0]['entries'])) {
            $entries = $response['cpanelresult']['data'][0]['entries'];
        } elseif (isset($response['data'][0]['entries'])) {
            $entries = $response['data'][0]['entries'];
        }
        // Fallback to old structure if entries not found
        elseif (isset($response['result']['data'])) {
            $entries = $response['result']['data'];
        } elseif (isset($response['cpanelresult']['data'])) {
            $entries = $response['cpanelresult']['data'];
        } elseif (isset($response['data'])) {
            $entries = $response['data'];
        }

        // Ensure $entries is an array
        if (!is_array($entries)) {
            $entries = [];
        }

        $records = [];
        foreach ($entries as $record) {
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

        // Step 4: Update SPF record for Google (replaces any existing)
        $spfResult = $this->updateSpfRecord('google');
        if (!$spfResult['success']) {
            $errors = array_merge($errors, $spfResult['errors'] ?? [$spfResult['message']]);
        }

        // Step 5: Remove autodiscover CNAME if exists (Google doesn't use it)
        $this->removeAutodiscoverRecord();

        // Step 6: Log the change
        $success = empty($errors);
        $this->logChange($oldRecordsJson, json_encode(self::GOOGLE_MX_RECORDS), $success, implode('; ', $errors), 'google');

        return [
            'success' => $success,
            'message' => $success ? 'MX and SPF records updated for Google Workspace' : implode('; ', $errors),
            'errors' => $errors,
            'spf' => $spfResult['spf_value'] ?? null,
        ];
    }

    /**
     * Get Office 365 MX record for a domain
     * Format: domain-tld.mail.protection.outlook.com (dots replaced with dashes)
     *
     * @return array
     */
    public function getOffice365MxRecord()
    {
        $domain = $this->getDomain();
        // Replace dots with dashes for O365 format: example.com -> example-com
        $o365Domain = str_replace('.', '-', $domain);
        return [
            'priority' => 0,
            'exchange' => $o365Domain . '.mail.protection.outlook.com.',
        ];
    }

    /**
     * Update MX records to Office 365 configuration
     *
     * @return array Result with success status and message
     */
    public function updateToOffice365Mx()
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

        // Step 3: Add Office 365 MX record
        $o365Record = $this->getOffice365MxRecord();
        try {
            $this->callEmailApi('addmx', [
                'domain' => $domain,
                'exchange' => $o365Record['exchange'],
                'preference' => $o365Record['priority'],
            ]);
        } catch (\Exception $e) {
            $errors[] = 'Failed to add: ' . $o365Record['exchange'] . ' - ' . $e->getMessage();
        }

        // Step 4: Update SPF record for Office 365
        $spfResult = $this->updateSpfRecord('office365');
        if (!$spfResult['success']) {
            $errors = array_merge($errors, $spfResult['errors'] ?? [$spfResult['message']]);
        }

        // Step 5: Add autodiscover CNAME for Outlook
        $autodiscoverResult = $this->updateAutodiscoverCname();
        if (!$autodiscoverResult['success']) {
            $errors = array_merge($errors, $autodiscoverResult['errors'] ?? [$autodiscoverResult['message']]);
        }

        // Step 6: Log the change
        $success = empty($errors);
        $this->logChange($oldRecordsJson, json_encode([$o365Record]), $success, implode('; ', $errors), 'office365');

        return [
            'success' => $success,
            'message' => $success ? 'MX, SPF, and Autodiscover records updated for Office 365' : implode('; ', $errors),
            'errors' => $errors,
            'new_records' => [$o365Record],
            'spf' => $spfResult['spf_value'] ?? null,
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

        // Step 4: Update SPF record for local mail
        $spfResult = $this->updateSpfRecord('local');
        if (!$spfResult['success']) {
            $errors = array_merge($errors, $spfResult['errors'] ?? [$spfResult['message']]);
        }

        // Step 5: Restore autodiscover A record (cPanel default)
        $autodiscoverResult = $this->restoreAutodiscoverARecord();
        if (!$autodiscoverResult['success']) {
            // Non-critical, just log but don't fail
            error_log('MX Changer: ' . ($autodiscoverResult['message'] ?? 'Autodiscover restore failed'));
        }

        // Step 6: Log the change
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
     * Get current SPF record for the domain
     *
     * @return string|null The SPF record value or null if not found
     */
    public function getCurrentSpfRecord()
    {
        $domain = $this->getDomain();

        try {
            $response = $this->callCpanelApi('fetchzone_records', [
                'domain' => $domain,
                'type' => 'TXT',
            ], 'ZoneEdit');

            $records = $response['cpanelresult']['data'] ?? $response['result']['data'] ?? [];

            foreach ($records as $record) {
                $txtdata = $record['txtdata'] ?? $record['record'] ?? '';
                if (strpos($txtdata, 'v=spf1') === 0) {
                    return [
                        'line' => $record['line'] ?? null,
                        'value' => $txtdata,
                    ];
                }
            }
        } catch (\Exception $e) {
            // SPF lookup failed, return null
        }

        return null;
    }

    /**
     * Update or create SPF record
     * Completely replaces existing SPF with provider-specific SPF
     *
     * @param string $provider 'google', 'office365', or 'local'
     * @return array Result with success status
     */
    public function updateSpfRecord($provider)
    {
        $domain = $this->getDomain();
        $errors = [];
        $serverIp = $this->serverData->ipaddress ?? null;

        // Get current SPF record
        $currentSpf = $this->getCurrentSpfRecord();

        // Build new SPF based on provider - complete replacement
        switch ($provider) {
            case 'local':
                // Local cPanel mail - server IP + a + mx
                if ($serverIp) {
                    $newSpfValue = 'v=spf1 +a +mx +ip4:' . $serverIp . ' ~all';
                } else {
                    $newSpfValue = 'v=spf1 +a +mx ~all';
                }
                break;

            case 'google':
                // Google Workspace - include Google SPF + server IP for webforms
                if ($serverIp) {
                    $newSpfValue = 'v=spf1 +ip4:' . $serverIp . ' include:_spf.google.com ~all';
                } else {
                    $newSpfValue = 'v=spf1 include:_spf.google.com ~all';
                }
                break;

            case 'office365':
                // Office 365 - include Microsoft SPF + server IP for webforms
                if ($serverIp) {
                    $newSpfValue = 'v=spf1 +ip4:' . $serverIp . ' include:spf.protection.outlook.com ~all';
                } else {
                    $newSpfValue = 'v=spf1 include:spf.protection.outlook.com ~all';
                }
                break;

            default:
                return ['success' => false, 'message' => 'Unknown provider'];
        }

        // Clean up any double spaces
        $newSpfValue = preg_replace('/\s+/', ' ', trim($newSpfValue));

        try {
            if ($currentSpf && isset($currentSpf['line'])) {
                // Edit existing record
                $this->callCpanelApi('edit_zone_record', [
                    'domain' => $domain,
                    'line' => $currentSpf['line'],
                    'type' => 'TXT',
                    'txtdata' => $newSpfValue,
                    'name' => $domain . '.',
                    'ttl' => 3600,
                ], 'ZoneEdit');
            } else {
                // Add new record
                $this->callCpanelApi('add_zone_record', [
                    'domain' => $domain,
                    'type' => 'TXT',
                    'txtdata' => $newSpfValue,
                    'name' => $domain . '.',
                    'ttl' => 3600,
                ], 'ZoneEdit');
            }
        } catch (\Exception $e) {
            $errors[] = 'SPF update failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'SPF record updated' : implode('; ', $errors),
            'spf_value' => $newSpfValue,
            'errors' => $errors,
        ];
    }

    /**
     * Get current autodiscover record (any type - A, CNAME, etc)
     *
     * @param string $type Record type to search for (A, CNAME, or null for any)
     * @return array|null
     */
    public function getAutodiscoverRecord($type = null)
    {
        $domain = $this->getDomain();

        $typesToCheck = $type ? [$type] : ['CNAME', 'A'];

        foreach ($typesToCheck as $recordType) {
            try {
                $response = $this->callCpanelApi('fetchzone_records', [
                    'domain' => $domain,
                    'type' => $recordType,
                ], 'ZoneEdit');

                $records = $response['cpanelresult']['data'] ?? $response['result']['data'] ?? [];

                foreach ($records as $record) {
                    $name = $record['name'] ?? '';
                    // Match autodiscover but not _autodiscover (SRV records)
                    if (preg_match('/^autodiscover\./i', $name)) {
                        return [
                            'line' => $record['line'] ?? null,
                            'name' => $name,
                            'type' => $recordType,
                            'value' => $record['cname'] ?? $record['address'] ?? $record['record'] ?? '',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Lookup failed, continue
            }
        }

        return null;
    }

    /**
     * Update or create autodiscover CNAME for Office 365
     *
     * @return array Result with success status
     */
    public function updateAutodiscoverCname()
    {
        $domain = $this->getDomain();
        $errors = [];

        $autodiscoverTarget = 'autodiscover.outlook.com.';
        $autodiscoverName = 'autodiscover.' . $domain . '.';

        try {
            // Check for existing autodiscover record (any type)
            $current = $this->getAutodiscoverRecord();

            if ($current && isset($current['line'])) {
                // If it's already a CNAME pointing to outlook, we're done
                if ($current['type'] === 'CNAME' && strpos(strtolower($current['value']), 'outlook.com') !== false) {
                    return ['success' => true, 'message' => 'Autodiscover already configured'];
                }

                // Delete existing record (whether A or CNAME)
                $this->callCpanelApi('remove_zone_record', [
                    'domain' => $domain,
                    'line' => $current['line'],
                ], 'ZoneEdit');
            }

            // Add new CNAME record
            $this->callCpanelApi('add_zone_record', [
                'domain' => $domain,
                'type' => 'CNAME',
                'name' => $autodiscoverName,
                'cname' => $autodiscoverTarget,
                'ttl' => 3600,
            ], 'ZoneEdit');

        } catch (\Exception $e) {
            $errors[] = 'Autodiscover CNAME failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Autodiscover CNAME configured' : implode('; ', $errors),
            'errors' => $errors,
        ];
    }

    /**
     * Remove any autodiscover record (A or CNAME)
     *
     * @return array Result with success status
     */
    public function removeAutodiscoverRecord()
    {
        $domain = $this->getDomain();

        try {
            $current = $this->getAutodiscoverRecord();

            if ($current && isset($current['line'])) {
                $this->callCpanelApi('remove_zone_record', [
                    'domain' => $domain,
                    'line' => $current['line'],
                ], 'ZoneEdit');
                return ['success' => true, 'message' => 'Autodiscover record removed'];
            }

            return ['success' => true, 'message' => 'No autodiscover record to remove'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restore autodiscover A record for local cPanel mail
     * Points autodiscover to server IP (cPanel default)
     *
     * @return array Result with success status
     */
    public function restoreAutodiscoverARecord()
    {
        $domain = $this->getDomain();
        $serverIp = $this->serverData->ipaddress ?? null;
        $errors = [];

        if (!$serverIp) {
            return ['success' => false, 'message' => 'Server IP not available'];
        }

        $autodiscoverName = 'autodiscover.' . $domain . '.';

        try {
            // First remove any existing autodiscover record (A or CNAME)
            $current = $this->getAutodiscoverRecord();
            if ($current && isset($current['line'])) {
                // If already an A record pointing to correct IP, we're done
                if ($current['type'] === 'A' && $current['value'] === $serverIp) {
                    return ['success' => true, 'message' => 'Autodiscover A record already configured'];
                }

                // Remove existing record
                $this->callCpanelApi('remove_zone_record', [
                    'domain' => $domain,
                    'line' => $current['line'],
                ], 'ZoneEdit');
            }

            // Add A record pointing to server IP
            $this->callCpanelApi('add_zone_record', [
                'domain' => $domain,
                'type' => 'A',
                'name' => $autodiscoverName,
                'address' => $serverIp,
                'ttl' => 3600,
            ], 'ZoneEdit');

        } catch (\Exception $e) {
            $errors[] = 'Autodiscover A record failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Autodiscover A record restored' : implode('; ', $errors),
            'errors' => $errors,
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

        // Check for Office 365 MX records
        foreach ($records as $record) {
            $host = strtolower($record['host']);
            if (strpos($host, '.mail.protection.outlook.com') !== false) {
                return self::MX_TYPE_OFFICE365;
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

        // Check data-level errors (Email API returns status directly in data[0])
        if (isset($data['cpanelresult']['data'][0]['status']) && $data['cpanelresult']['data'][0]['status'] == 0) {
            $reason = $data['cpanelresult']['data'][0]['statusmsg'] ?? 'Operation failed';
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
