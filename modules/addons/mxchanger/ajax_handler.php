<?php
/**
 * MX Changer - AJAX Handler
 *
 * @package    WHMCS
 * @author     WebJIVE
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
    require_once __DIR__ . '/../../../includes/adminfunctions.php';
    require_once __DIR__ . '/../../../includes/clientfunctions.php';

    // Check admin authentication
    if (!isset($_SESSION['adminid']) || !$_SESSION['adminid']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

    if (!$serviceId) {
        echo json_encode(['success' => false, 'message' => 'Service ID required']);
        exit;
    }

    require_once __DIR__ . '/lib/DnsManager.php';

    $dnsManager = new \MXChanger\DnsManager($serviceId);

    switch ($action) {
        case 'get_dns':
            $records = $dnsManager->getCurrentMxRecords();
            $mxType = $dnsManager->detectMxType();
            $o365Record = $dnsManager->getOffice365MxRecord();
            echo json_encode([
                'success' => true,
                'domain' => $dnsManager->getDomain(),
                'server' => $dnsManager->getServerName(),
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
