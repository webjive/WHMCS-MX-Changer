<?php
/**
 * WHMCS MX Changer Addon Module
 *
 * Enables automated DNS record updates for Google MX configuration
 * through a web interface integrated into the WHMCS admin panel.
 *
 * @package    WHMCS
 * @author     WebJIVE
 * @copyright  Copyright (c) WebJIVE
 * @link       https://webjive.com
 * @version    1.0.0
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Google Workspace MX Records Configuration
 */
define('GOOGLE_MX_RECORDS', [
    ['priority' => 1, 'host' => 'ASPMX.L.GOOGLE.COM'],
    ['priority' => 5, 'host' => 'ALT1.ASPMX.L.GOOGLE.COM'],
    ['priority' => 5, 'host' => 'ALT2.ASPMX.L.GOOGLE.COM'],
    ['priority' => 10, 'host' => 'ALT3.ASPMX.L.GOOGLE.COM'],
    ['priority' => 10, 'host' => 'ALT4.ASPMX.L.GOOGLE.COM'],
]);

/**
 * Module configuration
 */
function mxchanger_config()
{
    return [
        'name' => 'Google MX Changer',
        'description' => 'Automated DNS record updates for Google MX configuration via cPanel Extended API',
        'version' => '1.0.0',
        'author' => 'WebJIVE',
        'fields' => [
            'enable_logging' => [
                'FriendlyName' => 'Enable Logging',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Log all MX change operations for auditing',
            ],
            'require_confirmation' => [
                'FriendlyName' => 'Require Confirmation',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Show confirmation screen before applying changes',
            ],
        ],
    ];
}

/**
 * Activate the module
 */
function mxchanger_activate()
{
    try {
        // Create logging table
        if (!Capsule::schema()->hasTable('mod_mxchanger_log')) {
            Capsule::schema()->create('mod_mxchanger_log', function ($table) {
                $table->increments('id');
                $table->integer('admin_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->integer('service_id')->unsigned();
                $table->string('domain', 255);
                $table->text('old_records')->nullable();
                $table->text('new_records')->nullable();
                $table->enum('status', ['success', 'failed', 'pending']);
                $table->string('action_type', 20)->default('google');
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Add action_type column if table exists but column doesn't
        if (Capsule::schema()->hasTable('mod_mxchanger_log') &&
            !Capsule::schema()->hasColumn('mod_mxchanger_log', 'action_type')) {
            Capsule::schema()->table('mod_mxchanger_log', function ($table) {
                $table->string('action_type', 20)->default('google')->after('status');
            });
        }

        return [
            'status' => 'success',
            'description' => 'Google MX Changer module has been activated successfully.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Could not activate module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate the module
 */
function mxchanger_deactivate()
{
    try {
        // Optionally drop the logging table
        // Capsule::schema()->dropIfExists('mod_mxchanger_log');

        return [
            'status' => 'success',
            'description' => 'Google MX Changer module has been deactivated.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Could not deactivate module: ' . $e->getMessage(),
        ];
    }
}

/**
 * Admin area output - Module configuration and logs view
 */
function mxchanger_output($vars)
{
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $action = isset($_GET['action']) ? $_GET['action'] : 'overview';

    // Handle AJAX requests
    if (in_array($action, ['get_dns', 'update_dns', 'restore_local'])) {
        require_once __DIR__ . '/ajax.php';
        mxchanger_handle_ajax($vars);
        return;
    }

    echo '<div class="mxchanger-admin-wrapper">';

    // Navigation tabs
    echo '<ul class="nav nav-tabs admin-tabs" role="tablist">';
    echo '<li class="' . ($action === 'overview' ? 'active' : '') . '">';
    echo '<a href="' . $modulelink . '&action=overview">Overview</a>';
    echo '</li>';
    echo '<li class="' . ($action === 'logs' ? 'active' : '') . '">';
    echo '<a href="' . $modulelink . '&action=logs">Activity Logs</a>';
    echo '</li>';
    echo '</ul>';

    echo '<div class="tab-content admin-tabs-content">';

    switch ($action) {
        case 'logs':
            mxchanger_output_logs($vars);
            break;
        default:
            mxchanger_output_overview($vars);
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Overview tab content
 */
function mxchanger_output_overview($vars)
{
    $version = $vars['version'];

    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">';
    echo '<h3 class="panel-title"><i class="fas fa-envelope"></i> Google MX Changer v' . htmlspecialchars($version) . '</h3>';
    echo '</div>';
    echo '<div class="panel-body">';

    echo '<div class="alert alert-info">';
    echo '<i class="fas fa-info-circle"></i> ';
    echo 'This module adds a <strong>"Google MX Update"</strong> button to the Products tab on customer profile pages. ';
    echo 'When clicked, it retrieves current DNS records via cPanel Extended API and allows you to update them to Google Workspace MX records.';
    echo '</div>';

    echo '<h4>Google Workspace MX Records:</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Priority</th><th>Mail Server</th></tr></thead>';
    echo '<tbody>';
    foreach (GOOGLE_MX_RECORDS as $record) {
        echo '<tr>';
        echo '<td>' . $record['priority'] . '</td>';
        echo '<td>' . htmlspecialchars($record['host']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';

    echo '<h4>How to Use:</h4>';
    echo '<ol>';
    echo '<li>Navigate to a customer profile in the WHMCS admin area</li>';
    echo '<li>Click on the <strong>Products/Services</strong> tab</li>';
    echo '<li>Find a cPanel hosting product and click <strong>"Google MX Update"</strong></li>';
    echo '<li>Review the current MX records and proposed changes</li>';
    echo '<li>Confirm to apply the Google Workspace MX configuration</li>';
    echo '</ol>';

    echo '</div>';
    echo '</div>';
}

/**
 * Activity logs tab content
 */
function mxchanger_output_logs($vars)
{
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 25;
    $offset = ($page - 1) * $perPage;

    try {
        $totalLogs = Capsule::table('mod_mxchanger_log')->count();
        $logs = Capsule::table('mod_mxchanger_log')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        echo '<div class="panel panel-default">';
        echo '<div class="panel-heading">';
        echo '<h3 class="panel-title"><i class="fas fa-history"></i> MX Change Activity Logs</h3>';
        echo '</div>';
        echo '<div class="panel-body">';

        if ($logs->isEmpty()) {
            echo '<div class="alert alert-warning">No activity logs found.</div>';
        } else {
            echo '<table class="table table-striped table-bordered datatable">';
            echo '<thead><tr>';
            echo '<th>Date/Time</th>';
            echo '<th>Admin</th>';
            echo '<th>Client</th>';
            echo '<th>Domain</th>';
            echo '<th>Action</th>';
            echo '<th>Status</th>';
            echo '<th>Details</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($logs as $log) {
                $statusClass = $log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning');
                $actionType = isset($log->action_type) ? $log->action_type : 'google';
                $actionLabel = $actionType === 'local' ? 'Restore Local' : 'Set Google MX';
                $actionClass = $actionType === 'local' ? 'info' : 'primary';

                echo '<tr>';
                echo '<td>' . date('Y-m-d H:i:s', strtotime($log->created_at)) . '</td>';
                echo '<td>' . htmlspecialchars($log->admin_id) . '</td>';
                echo '<td><a href="clientssummary.php?userid=' . $log->client_id . '">#' . $log->client_id . '</a></td>';
                echo '<td>' . htmlspecialchars($log->domain) . '</td>';
                echo '<td><span class="label label-' . $actionClass . '">' . $actionLabel . '</span></td>';
                echo '<td><span class="label label-' . $statusClass . '">' . ucfirst($log->status) . '</span></td>';
                echo '<td>';
                if ($log->error_message) {
                    echo '<span class="text-danger">' . htmlspecialchars($log->error_message) . '</span>';
                } else {
                    echo '<span class="text-muted">-</span>';
                }
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';

            // Pagination
            $totalPages = ceil($totalLogs / $perPage);
            if ($totalPages > 1) {
                echo '<nav><ul class="pagination">';
                for ($i = 1; $i <= $totalPages; $i++) {
                    $active = $i === $page ? 'active' : '';
                    echo '<li class="' . $active . '"><a href="' . $vars['modulelink'] . '&action=logs&page=' . $i . '">' . $i . '</a></li>';
                }
                echo '</ul></nav>';
            }
        }

        echo '</div>';
        echo '</div>';
    } catch (\Exception $e) {
        echo '<div class="alert alert-danger">Error loading logs: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
