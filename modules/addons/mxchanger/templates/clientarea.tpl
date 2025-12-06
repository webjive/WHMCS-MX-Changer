<style>
.mxchanger-client-container {
    max-width: 900px;
    margin: 0 auto;
}
.mxchanger-client-header {
    background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
    color: #fff;
    padding: 25px 30px;
    border-radius: 8px 8px 0 0;
    margin-bottom: 0;
}
.mxchanger-client-header h2 {
    margin: 0 0 5px 0;
    font-size: 1.5em;
}
.mxchanger-client-header p {
    margin: 0;
    opacity: 0.9;
}
.mxchanger-client-body {
    background: #fff;
    border: 1px solid #e9ecef;
    border-top: none;
    border-radius: 0 0 8px 8px;
    padding: 30px;
}
.mxchanger-status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9em;
    margin-bottom: 20px;
}
.mxchanger-status-badge.google { background: #e8f5e9; color: #2e7d32; }
.mxchanger-status-badge.office365 { background: #fff4e5; color: #d83b01; }
.mxchanger-status-badge.local { background: #e3f2fd; color: #1565c0; }
.mxchanger-status-badge.other { background: #fff3e0; color: #e65100; }
.mxchanger-status-badge.loading { background: #f5f5f5; color: #666; }

.mxchanger-current-records {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}
.mxchanger-current-records h4 {
    margin: 0 0 15px 0;
    color: #495057;
}
.mxchanger-record-item {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px 15px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.mxchanger-record-item:last-child { margin-bottom: 0; }
.mxchanger-record-item .priority {
    background: #e9ecef;
    padding: 4px 10px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.85em;
    min-width: 35px;
    text-align: center;
}
.mxchanger-record-item .host {
    font-family: monospace;
    font-size: 0.9em;
    word-break: break-all;
}

.mxchanger-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 25px;
}
@media (max-width: 768px) {
    .mxchanger-options { grid-template-columns: 1fr; }
}
.mxchanger-option-card {
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 25px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.mxchanger-option-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
.mxchanger-option-card.google:hover { border-color: #28a745; background: #f0fff4; }
.mxchanger-option-card.office365:hover { border-color: #d83b01; background: #fff4e5; }
.mxchanger-option-card.local:hover { border-color: #2196f3; background: #e3f2fd; }
.mxchanger-option-card.active { opacity: 0.6; cursor: default; transform: none; box-shadow: none; }
.mxchanger-option-card .icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
}
.mxchanger-option-card.google .icon { background: linear-gradient(135deg, #4285f4 0%, #34a853 100%); color: #fff; }
.mxchanger-option-card.office365 .icon { background: linear-gradient(135deg, #d83b01 0%, #ff8c00 100%); color: #fff; }
.mxchanger-option-card.local .icon { background: linear-gradient(135deg, #5bc0de 0%, #337ab7 100%); color: #fff; }
.mxchanger-option-card h4 { margin: 0 0 8px 0; }
.mxchanger-option-card p { margin: 0; color: #6c757d; font-size: 0.9em; }
.mxchanger-option-card .current-label {
    display: inline-block;
    background: #28a745;
    color: #fff;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 0.75em;
    margin-top: 10px;
}

.mxchanger-info-box {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    border-left: 4px solid #007bff;
    border-radius: 6px;
    padding: 15px;
    margin-top: 25px;
    font-size: 0.9em;
    color: #004085;
}
.mxchanger-info-box i { margin-right: 8px; color: #007bff; }
.mxchanger-info-box code { background: #d1e7ff; padding: 2px 6px; border-radius: 3px; font-size: 0.85em; }

.mxchanger-loading {
    text-align: center;
    padding: 40px;
}
.mxchanger-loading .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e9ecef;
    border-top-color: #4285f4;
    border-radius: 50%;
    animation: mxspin 1s linear infinite;
    margin: 0 auto 15px;
}
@keyframes mxspin { to { transform: rotate(360deg); } }

.mxchanger-result {
    text-align: center;
    padding: 30px;
}
.mxchanger-result.success .icon { color: #28a745; font-size: 48px; margin-bottom: 15px; }
.mxchanger-result.error .icon { color: #dc3545; font-size: 48px; margin-bottom: 15px; }
.mxchanger-result h4 { margin: 0 0 10px 0; }
.mxchanger-result p { margin: 0; color: #6c757d; }
</style>

<div class="mxchanger-client-container">
    {if $error}
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> {$error}
        </div>
    {else}
        <div class="mxchanger-client-header">
            <h2><i class="fas fa-envelope"></i> Email MX Changer</h2>
            <p>Manage email routing for <strong>{$domain}</strong></p>
        </div>
        <div class="mxchanger-client-body" id="mxchanger-client-content">
            <div class="mxchanger-loading">
                <div class="spinner"></div>
                <p>Loading DNS records...</p>
            </div>
        </div>
    {/if}
</div>

{if $serviceId}
<script>
var MXChangerClient = {
    serviceId: {$serviceId},
    domain: '{$domain|escape:'javascript'}',
    currentType: null,

    init: function() {
        this.fetchRecords();
    },

    fetchRecords: function() {
        var self = this;
        fetch('/modules/addons/mxchanger/client_ajax.php?action=get_dns&service_id=' + this.serviceId, {
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                self.currentType = data.mx_type;
                self.renderContent(data);
            } else {
                self.showError(data.message || 'Failed to load DNS records');
            }
        })
        .catch(function(e) {
            self.showError('Error: ' + e.message);
        });
    },

    renderContent: function(data) {
        var statusLabel = 'Unknown';
        var statusClass = 'other';
        switch (data.mx_type) {
            case 'google': statusLabel = 'Google Workspace'; statusClass = 'google'; break;
            case 'office365': statusLabel = 'Microsoft 365'; statusClass = 'office365'; break;
            case 'local': statusLabel = 'cPanel Mail'; statusClass = 'local'; break;
        }

        var html = '<div class="mxchanger-status-badge ' + statusClass + '"><i class="fas fa-check-circle"></i> Current: ' + statusLabel + '</div>';

        html += '<div class="mxchanger-current-records"><h4><i class="fas fa-list"></i> Current MX Records</h4>';
        if (data.records && data.records.length > 0) {
            data.records.forEach(function(r) {
                html += '<div class="mxchanger-record-item"><span class="priority">' + r.priority + '</span><span class="host">' + r.host + '</span></div>';
            });
        } else {
            html += '<p class="text-muted">No MX records found</p>';
        }
        html += '</div>';

        html += '<h4><i class="fas fa-exchange-alt"></i> Switch Email Provider</h4>';
        html += '<div class="mxchanger-options">';

        // Google option
        html += '<div class="mxchanger-option-card google' + (data.mx_type === 'google' ? ' active' : '') + '" onclick="MXChangerClient.switchTo(\'google\')">';
        html += '<div class="icon"><i class="fab fa-google"></i></div>';
        html += '<h4>Google Workspace</h4>';
        html += '<p>Gmail, Calendar, Drive</p>';
        if (data.mx_type === 'google') html += '<span class="current-label">Current</span>';
        html += '</div>';

        // Office 365 option
        html += '<div class="mxchanger-option-card office365' + (data.mx_type === 'office365' ? ' active' : '') + '" onclick="MXChangerClient.switchTo(\'office365\')">';
        html += '<div class="icon"><i class="fab fa-microsoft"></i></div>';
        html += '<h4>Microsoft 365</h4>';
        html += '<p>Outlook, Teams, OneDrive</p>';
        if (data.mx_type === 'office365') html += '<span class="current-label">Current</span>';
        html += '</div>';

        // Local option
        html += '<div class="mxchanger-option-card local' + (data.mx_type === 'local' ? ' active' : '') + '" onclick="MXChangerClient.switchTo(\'local\')">';
        html += '<div class="icon"><i class="fas fa-server"></i></div>';
        html += '<h4>cPanel Mail</h4>';
        html += '<p>Server-based email</p>';
        if (data.mx_type === 'local') html += '<span class="current-label">Current</span>';
        html += '</div>';

        html += '</div>';

        html += '<div class="mxchanger-info-box"><i class="fas fa-info-circle"></i> <strong>Note:</strong> Changing your MX records will redirect all incoming email to the selected provider. DNS changes may take up to 24-48 hours to fully propagate.</div>';

        document.getElementById('mxchanger-client-content').innerHTML = html;
    },

    switchTo: function(provider) {
        if (provider === this.currentType) return;

        var providerName = provider === 'google' ? 'Google Workspace' : (provider === 'office365' ? 'Microsoft 365' : 'cPanel Mail');

        if (!confirm('Are you sure you want to switch your email to ' + providerName + '?\n\nThis will update your MX records and related DNS settings.')) {
            return;
        }

        var self = this;
        var action = provider === 'google' ? 'update_dns' : (provider === 'office365' ? 'update_office365' : 'restore_local');

        document.getElementById('mxchanger-client-content').innerHTML = '<div class="mxchanger-loading"><div class="spinner"></div><p>Updating DNS records...</p></div>';

        fetch('/modules/addons/mxchanger/client_ajax.php?action=' + action + '&service_id=' + this.serviceId, {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                self.showSuccess('Email routing updated to ' + providerName + '!');
                setTimeout(function() { self.fetchRecords(); }, 2000);
            } else {
                self.showError(data.message || 'Failed to update DNS records');
            }
        })
        .catch(function(e) {
            self.showError('Error: ' + e.message);
        });
    },

    showSuccess: function(msg) {
        var html = '<div class="mxchanger-result success">';
        html += '<div class="icon"><i class="fas fa-check-circle"></i></div>';
        html += '<h4>Success!</h4>';
        html += '<p>' + msg + '</p>';
        html += '</div>';
        document.getElementById('mxchanger-client-content').innerHTML = html;
    },

    showError: function(msg) {
        var html = '<div class="mxchanger-result error">';
        html += '<div class="icon"><i class="fas fa-times-circle"></i></div>';
        html += '<h4>Error</h4>';
        html += '<p>' + msg + '</p>';
        html += '<p style="margin-top:15px"><button class="btn btn-primary" onclick="MXChangerClient.fetchRecords()">Try Again</button></p>';
        html += '</div>';
        document.getElementById('mxchanger-client-content').innerHTML = html;
    }
};

document.addEventListener('DOMContentLoaded', function() {
    MXChangerClient.init();
});
</script>
{/if}
