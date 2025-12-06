<?php
/**
 * WHMCS MX Changer Addon Module
 *
 * Enables automated DNS record updates for Google Workspace, Microsoft 365,
 * and local cPanel mail through a web interface integrated into the WHMCS admin panel.
 *
 * @package    WHMCS
 * @author     WebJIVE
 * @copyright  Copyright (c) WebJIVE
 * @link       https://webjive.com
 * @version    1.4.0
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
        'name' => 'MX Changer',
        'description' => 'Automated DNS record updates for Google Workspace, Microsoft 365, and local cPanel mail via cPanel API',
        'version' => '1.4.0',
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
            'enable_client_access' => [
                'FriendlyName' => 'Enable Client Access',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Allow clients to manage MX records from their product details page',
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
            'description' => 'MX Changer module has been activated successfully.',
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
            'description' => 'MX Changer module has been deactivated.',
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
    echo '<li class="' . ($action === 'google' ? 'active' : '') . '">';
    echo '<a href="' . $modulelink . '&action=google">Google Workspace</a>';
    echo '</li>';
    echo '<li class="' . ($action === 'microsoft' ? 'active' : '') . '">';
    echo '<a href="' . $modulelink . '&action=microsoft">Microsoft 365</a>';
    echo '</li>';
    echo '<li class="' . ($action === 'logs' ? 'active' : '') . '">';
    echo '<a href="' . $modulelink . '&action=logs">Activity Logs</a>';
    echo '</li>';
    echo '</ul>';

    echo '<div class="tab-content admin-tabs-content">';

    switch ($action) {
        case 'google':
            mxchanger_output_google($vars);
            break;
        case 'microsoft':
            mxchanger_output_microsoft($vars);
            break;
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
    echo '<h3 class="panel-title"><i class="fas fa-envelope"></i> MX Changer v' . htmlspecialchars($version) . '</h3>';
    echo '</div>';
    echo '<div class="panel-body">';

    echo '<div class="alert alert-info">';
    echo '<i class="fas fa-info-circle"></i> ';
    echo 'This module adds an <strong>"MX Manager"</strong> button to the Module Commands area on hosting service pages. ';
    echo 'It allows you to quickly switch between Google Workspace, Microsoft 365, and local cPanel mail configurations.';
    echo '</div>';

    echo '<h4>Supported Configurations:</h4>';
    echo '<div class="row">';

    // Google Card
    echo '<div class="col-md-4">';
    echo '<div class="panel panel-success">';
    echo '<div class="panel-heading"><h4 class="panel-title"><i class="fab fa-google"></i> Google Workspace</h4></div>';
    echo '<div class="panel-body">';
    echo '<ul><li>5 MX records</li><li>SPF with Google include</li><li>Removes autodiscover</li></ul>';
    echo '</div></div></div>';

    // Microsoft Card
    echo '<div class="col-md-4">';
    echo '<div class="panel panel-warning">';
    echo '<div class="panel-heading"><h4 class="panel-title"><i class="fab fa-microsoft"></i> Microsoft 365</h4></div>';
    echo '<div class="panel-body">';
    echo '<ul><li>1 MX record</li><li>SPF with Microsoft include</li><li>Autodiscover CNAME</li></ul>';
    echo '</div></div></div>';

    // Local Card
    echo '<div class="col-md-4">';
    echo '<div class="panel panel-info">';
    echo '<div class="panel-heading"><h4 class="panel-title"><i class="fas fa-server"></i> Local cPanel</h4></div>';
    echo '<div class="panel-body">';
    echo '<ul><li>1 MX record (domain)</li><li>SPF with server IP</li><li>Autodiscover A record</li></ul>';
    echo '</div></div></div>';

    echo '</div>';

    echo '<h4>How to Use:</h4>';
    echo '<ol>';
    echo '<li>Navigate to a customer\'s hosting service in the WHMCS admin area</li>';
    echo '<li>Find the <strong>"MX Manager"</strong> button in the Module Commands section</li>';
    echo '<li>Select your desired mail configuration (Google, Microsoft, or Local)</li>';
    echo '<li>Review the current records and proposed changes</li>';
    echo '<li>Confirm to apply the new configuration</li>';
    echo '</ol>';

    echo '</div>';
    echo '</div>';
}

/**
 * Google Workspace tab content
 */
function mxchanger_output_google($vars)
{
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">';
    echo '<h3 class="panel-title"><i class="fab fa-google"></i> Google Workspace DNS Configuration</h3>';
    echo '</div>';
    echo '<div class="panel-body">';

    echo '<h4>MX Records:</h4>';
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

    echo '<h4>SPF Record:</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Value</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>TXT</td><td>@</td><td><code>v=spf1 +ip4:[SERVER_IP] include:_spf.google.com ~all</code></td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle"></i> <strong>Automatically Configured:</strong> All records listed above (MX, SPF) plus the custom URL CNAMEs below are automatically configured when you select Google Workspace.';
    echo '</div>';

    echo '<h4>Custom URL CNAME Records <span class="label label-success">Auto-configured</span>:</h4>';
    echo '<p>These CNAMEs allow access via <code>mail.yourdomain.com</code>, <code>calendar.yourdomain.com</code>, etc.</p>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Points To</th><th>Access URL</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>CNAME</td><td>mail</td><td><code>ghs.googlehosted.com</code></td><td>Gmail webmail</td></tr>';
    echo '<tr><td>CNAME</td><td>calendar</td><td><code>ghs.googlehosted.com</code></td><td>Google Calendar</td></tr>';
    echo '<tr><td>CNAME</td><td>drive</td><td><code>ghs.googlehosted.com</code></td><td>Google Drive</td></tr>';
    echo '<tr><td>CNAME</td><td>docs</td><td><code>ghs.googlehosted.com</code></td><td>Google Docs</td></tr>';
    echo '<tr><td>CNAME</td><td>sites</td><td><code>ghs.googlehosted.com</code></td><td>Google Sites</td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<div class="alert alert-info">';
    echo '<i class="fas fa-info-circle"></i> <strong>Note:</strong> Google Workspace does not require an autodiscover record. ';
    echo 'Any existing autodiscover CNAME or Office 365 records will be removed when switching to Google.';
    echo '</div>';

    echo '<h4>Manual Setup (Not Auto-configured):</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Value</th><th>Purpose</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>TXT</td><td>@</td><td><code>google-site-verification=XXXXX</code></td><td>Domain verification (unique per account)</td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '</div>';
    echo '</div>';
}

/**
 * Microsoft 365 tab content
 */
function mxchanger_output_microsoft($vars)
{
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading">';
    echo '<h3 class="panel-title"><i class="fab fa-microsoft"></i> Microsoft 365 DNS Configuration</h3>';
    echo '</div>';
    echo '<div class="panel-body">';

    echo '<h4>MX Record:</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Priority</th><th>Mail Server</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>0</td><td><code>[DOMAIN-WITH-DASHES].mail.protection.outlook.com</code></td></tr>';
    echo '</tbody>';
    echo '</table>';
    echo '<p class="text-muted">Example: <code>example-com.mail.protection.outlook.com</code> for domain <code>example.com</code></p>';

    echo '<h4>SPF Record:</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Value</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>TXT</td><td>@</td><td><code>v=spf1 +ip4:[SERVER_IP] include:spf.protection.outlook.com ~all</code></td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<h4>Autodiscover CNAME:</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Points To</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>CNAME</td><td>autodiscover</td><td><code>autodiscover.outlook.com</code></td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<div class="alert alert-success">';
    echo '<i class="fas fa-check-circle"></i> <strong>Automatically Configured:</strong> All records listed above (MX, SPF, Autodiscover) plus the Teams/Skype and MDM records below are automatically configured when you select Office 365.';
    echo '</div>';

    echo '<h4>Teams/Skype for Business Records <span class="label label-success">Auto-configured</span>:</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Value</th><th>Purpose</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>CNAME</td><td>sip</td><td><code>sipdir.online.lync.com</code></td><td>Teams/Skype</td></tr>';
    echo '<tr><td>CNAME</td><td>lyncdiscover</td><td><code>webdir.online.lync.com</code></td><td>Teams/Skype</td></tr>';
    echo '<tr><td>SRV</td><td>_sip._tls</td><td><code>100 1 443 sipdir.online.lync.com</code></td><td>Teams/Skype</td></tr>';
    echo '<tr><td>SRV</td><td>_sipfederationtls._tcp</td><td><code>100 1 5061 sipfed.online.lync.com</code></td><td>Federation</td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<h4>Mobile Device Management Records <span class="label label-success">Auto-configured</span>:</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Value</th><th>Purpose</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>CNAME</td><td>enterpriseregistration</td><td><code>enterpriseregistration.windows.net</code></td><td>Azure AD Join</td></tr>';
    echo '<tr><td>CNAME</td><td>enterpriseenrollment</td><td><code>enterpriseenrollment.manage.microsoft.com</code></td><td>Intune MDM</td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<h4>Manual Setup (Not Auto-configured):</h4>';
    echo '<table class="table table-striped table-bordered">';
    echo '<thead><tr><th>Type</th><th>Host</th><th>Value</th><th>Purpose</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td>TXT</td><td>@</td><td><code>MS=msXXXXXXXX</code></td><td>Domain verification (unique per tenant)</td></tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<p><a href="https://learn.microsoft.com/en-us/microsoft-365/admin/get-help-with-domains/create-dns-records-at-any-dns-hosting-provider" target="_blank" class="btn btn-default"><i class="fas fa-external-link-alt"></i> Microsoft 365 DNS Documentation</a></p>';

    echo '</div>';
    echo '</div>';
}

/**
 * Activity logs tab content
 */
function mxchanger_output_logs($vars)
{
    $modulelink = $vars['modulelink'];

    // Handle clear logs action
    if (isset($_GET['clear_logs']) && $_GET['clear_logs'] === 'confirm') {
        try {
            Capsule::table('mod_mxchanger_log')->truncate();
            echo '<div class="alert alert-success"><i class="fas fa-check"></i> All activity logs have been cleared.</div>';
        } catch (\Exception $e) {
            echo '<div class="alert alert-danger">Error clearing logs: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

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
        echo '<div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;">';
        echo '<h3 class="panel-title" style="margin:0;"><i class="fas fa-history"></i> MX Change Activity Logs</h3>';
        if ($totalLogs > 0) {
            echo '<button type="button" class="btn btn-danger btn-sm" onclick="if(confirm(\'Are you sure you want to clear all activity logs? This cannot be undone.\')) { window.location.href=\'' . $modulelink . '&action=logs&clear_logs=confirm\'; }"><i class="fas fa-trash"></i> Clear Logs</button>';
        }
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
                switch ($actionType) {
                    case 'local':
                        $actionLabel = 'Restore Local';
                        $actionClass = 'info';
                        break;
                    case 'office365':
                        $actionLabel = 'Set Office 365';
                        $actionClass = 'warning';
                        break;
                    default:
                        $actionLabel = 'Set Google MX';
                        $actionClass = 'primary';
                }

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

/**
 * Client Area Output
 * Accessible via index.php?m=mxchanger&service_id=X
 */
function mxchanger_clientarea($vars)
{
    // Check if client access is enabled
    $enableClientAccess = $vars['enable_client_access'] ?? 'yes';
    if ($enableClientAccess !== 'yes') {
        return [
            'pagetitle' => 'MX Changer',
            'breadcrumb' => ['index.php?m=mxchanger' => 'MX Changer'],
            'templatefile' => '',
            'vars' => [
                'error' => 'Client access to MX Changer is disabled.',
            ],
        ];
    }

    // Get service ID from request
    $serviceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

    // Verify client owns this service
    $clientId = isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;

    if (!$clientId) {
        return [
            'pagetitle' => 'MX Changer',
            'breadcrumb' => ['index.php?m=mxchanger' => 'MX Changer'],
            'templatefile' => 'clientarea',
            'vars' => [
                'error' => 'You must be logged in to access this feature.',
                'serviceId' => 0,
            ],
        ];
    }

    if (!$serviceId) {
        return [
            'pagetitle' => 'MX Changer',
            'breadcrumb' => ['index.php?m=mxchanger' => 'MX Changer'],
            'templatefile' => 'clientarea',
            'vars' => [
                'error' => 'No service selected. Please access this page from your hosting service details.',
                'serviceId' => 0,
            ],
        ];
    }

    // Verify ownership
    $service = Capsule::table('tblhosting')
        ->where('id', $serviceId)
        ->where('userid', $clientId)
        ->first();

    if (!$service) {
        return [
            'pagetitle' => 'MX Changer',
            'breadcrumb' => ['index.php?m=mxchanger' => 'MX Changer'],
            'templatefile' => 'clientarea',
            'vars' => [
                'error' => 'Service not found or you do not have permission to manage it.',
                'serviceId' => 0,
            ],
        ];
    }

    return [
        'pagetitle' => 'Email MX Changer - ' . $service->domain,
        'breadcrumb' => [
            'clientarea.php?action=productdetails&id=' . $serviceId => $service->domain,
            'index.php?m=mxchanger&service_id=' . $serviceId => 'MX Changer',
        ],
        'templatefile' => 'clientarea',
        'vars' => [
            'serviceId' => $serviceId,
            'domain' => $service->domain,
            'error' => '',
        ],
    ];
}
