<?php
/**
 * WHMCS MX Changer - AJAX Handler
 *
 * Processes AJAX requests for DNS operations
 *
 * @package    WHMCS
 * @author     WebJIVE
 * @copyright  Copyright (c) WebJIVE
 * @link       https://webjive.com
 */

// This file is included by the main module when handling AJAX actions

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/DnsManager.php';

/**
 * Handle AJAX actions for the MX Changer module
 *
 * @param array $vars Module variables
 * @return void
 */
function mxchanger_handle_ajax($vars)
{
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Set JSON response header
    header('Content-Type: application/json');

    try {
        switch ($action) {
            case 'get_dns':
                mxchanger_ajax_get_dns();
                break;

            case 'update_dns':
                mxchanger_ajax_update_dns();
                break;

            case 'restore_local':
                mxchanger_ajax_restore_local();
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid action',
                ]);
        }
    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }

    exit;
}

/**
 * Get current DNS records for a service
 */
function mxchanger_ajax_get_dns()
{
    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

    if (!$serviceId) {
        throw new \Exception('Service ID is required');
    }

    // Verify admin permission
    if (!isset($_SESSION['adminid']) || !$_SESSION['adminid']) {
        throw new \Exception('Unauthorized access');
    }

    $dnsManager = new \MXChanger\DnsManager($serviceId);

    $records = $dnsManager->getCurrentMxRecords();
    $mxType = $dnsManager->detectMxType();

    echo json_encode([
        'success' => true,
        'domain' => $dnsManager->getDomain(),
        'server' => $dnsManager->getServerName(),
        'records' => $records,
        'mx_type' => $mxType,
    ]);
}

/**
 * Update DNS records to Google MX configuration
 */
function mxchanger_ajax_update_dns()
{
    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

    if (!$serviceId) {
        // Check POST body for service_id
        $input = json_decode(file_get_contents('php://input'), true);
        $serviceId = isset($input['service_id']) ? (int)$input['service_id'] : 0;
    }

    if (!$serviceId) {
        throw new \Exception('Service ID is required');
    }

    // Verify admin permission
    if (!isset($_SESSION['adminid']) || !$_SESSION['adminid']) {
        throw new \Exception('Unauthorized access');
    }

    $dnsManager = new \MXChanger\DnsManager($serviceId);
    $result = $dnsManager->updateToGoogleMx();

    echo json_encode($result);
}

/**
 * Restore DNS records to local/cPanel mail configuration
 */
function mxchanger_ajax_restore_local()
{
    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

    if (!$serviceId) {
        // Check POST body for service_id
        $input = json_decode(file_get_contents('php://input'), true);
        $serviceId = isset($input['service_id']) ? (int)$input['service_id'] : 0;
    }

    if (!$serviceId) {
        throw new \Exception('Service ID is required');
    }

    // Verify admin permission
    if (!isset($_SESSION['adminid']) || !$_SESSION['adminid']) {
        throw new \Exception('Unauthorized access');
    }

    $dnsManager = new \MXChanger\DnsManager($serviceId);
    $result = $dnsManager->restoreToLocalMx();

    echo json_encode($result);
}
