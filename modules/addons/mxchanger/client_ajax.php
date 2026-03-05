<?php
/**
 * MX Changer - Client AJAX Handler
 *
 * @package    WHMCS
 * @author     WebJIVE (https://www.web-jive.com)
 * @copyright  Copyright (c) WebJIVE
 */

// Set JSON header
header('Content-Type: application/json');

// Error handler to return JSON
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Initialize WHMCS
    require_once __DIR__ . '/../../../init.php';
    require_once __DIR__ . '/../../../includes/clientfunctions.php';

    // Check client authentication
    $clientId = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;

    if (!$clientId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
        exit;
    }

    // Check if client access is enabled
    $addonSettings = \WHMCS\Database\Capsule::table('tbladdonmodules')
        ->where('module', 'mxchanger')
        ->where('setting', 'enable_client_access')
        ->first();

    if ($addonSettings && !in_array($addonSettings->value, ['on', 'yes'])) {
        echo json_encode(['success' => false, 'message' => 'Client access is disabled']);
        exit;
    }

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Return all domains (main + addon + parked) across all of this client's hosting services
    if ($action === 'get_client_domains') {
        $rows = \WHMCS\Database\Capsule::table('tblhosting')
            ->where('userid', $clientId)
            ->where('domainstatus', 'Active')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->orderBy('domain')
            ->get(['id', 'domain']);
        require_once __DIR__ . '/lib/DnsManager.php';
        $domains = [];
        foreach ($rows as $row) {
            try {
                $mgr = new \MXChanger\DnsManager($row->id);
                foreach ($mgr->getAllDomains() as $d) {
                    $domains[] = ['service_id' => $row->id, 'domain' => $d];
                }
            } catch (\Exception $e) {
                $domains[] = ['service_id' => $row->id, 'domain' => $row->domain];
            }
        }
        usort($domains, function($a, $b) { return strcmp($a['domain'], $b['domain']); });
        echo json_encode(['success' => true, 'domains' => $domains]);
        exit;
    }

    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

    if (!$serviceId) {
        echo json_encode(['success' => false, 'message' => 'Service ID required']);
        exit;
    }

    // Verify client owns this service
    $service = \WHMCS\Database\Capsule::table('tblhosting')
        ->where('id', $serviceId)
        ->where('userid', $clientId)
        ->first();

    if (!$service) {
        echo json_encode(['success' => false, 'message' => 'Service not found or access denied']);
        exit;
    }

    require_once __DIR__ . '/lib/DnsManager.php';

    $dnsManager = new \MXChanger\DnsManager($serviceId);

    // Support addon/parked domain override
    $domainParam = isset($_GET['domain']) ? trim($_GET['domain']) : '';
    if ($domainParam) {
        $dnsManager->setDomain($domainParam);
    }

    switch ($action) {
        case 'get_dns':
            $records = $dnsManager->getCurrentMxRecords();
            $mxType = $dnsManager->detectMxType();
            $o365Record = $dnsManager->getOffice365MxRecord();
            echo json_encode([
                'success' => true,
                'domain' => $dnsManager->getDomain(),
                'records' => $records,
                'mx_type' => $mxType,
                'o365_record' => $o365Record,
            ]);
            break;

        case 'update_dns':
            $result = $dnsManager->updateToGoogleMx();
            echo json_encode($result);
            break;

        case 'update_office365':
            $result = $dnsManager->updateToOffice365Mx();
            echo json_encode($result);
            break;

        case 'restore_local':
            $result = $dnsManager->restoreToLocalMx();
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
