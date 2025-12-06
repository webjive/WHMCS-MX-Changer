<?php
/**
 * MX Changer - Direct AJAX Handler
 * Place in: modules/addons/mxchanger/ajax_handler.php
 * Access via: /modules/addons/mxchanger/ajax_handler.php?action=get_dns&service_id=X
 */

// Initialize WHMCS
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/adminfunctions.php';

use WHMCS\Database\Capsule;

// Check admin authentication
if (!isset($_SESSION['adminid']) || !$_SESSION['adminid']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin login required']);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

if (!$serviceId) {
    echo json_encode(['success' => false, 'message' => 'Service ID required']);
    exit;
}

require_once __DIR__ . '/lib/DnsManager.php';

try {
    $dnsManager = new \MXChanger\DnsManager($serviceId);

    switch ($action) {
        case 'get_dns':
            $records = $dnsManager->getCurrentMxRecords();
            $mxType = $dnsManager->detectMxType();
            echo json_encode([
                'success' => true,
                'domain' => $dnsManager->getDomain(),
                'server' => $dnsManager->getServerName(),
                'records' => $records,
                'mx_type' => $mxType,
            ]);
            break;

        case 'update_dns':
            $result = $dnsManager->updateToGoogleMx();
            echo json_encode($result);
            break;

        case 'restore_local':
            $result = $dnsManager->restoreToLocalMx();
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
