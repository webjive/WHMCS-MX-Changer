<?php
/**
 * WHMCS MX Changer - Hooks
 *
 * Place this file in: /your-whmcs/includes/hooks/mxchanger_hooks.php
 *
 * @package    WHMCS
 * @author     WebJIVE
 */

use WHMCS\Database\Capsule;

/**
 * Add MX Manager button CSS
 */
add_hook('AdminAreaHeadOutput', 1, function($vars) {
    return '
<!-- MX Changer CSS -->
<style>
.mxchanger-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}
.mxchanger-modal-overlay.active { display: flex; }
.mxchanger-modal {
    background: #fff;
    border-radius: 8px;
    max-width: 900px;
    width: 95%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}
.mxchanger-modal-header {
    background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
    color: #fff;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.mxchanger-modal-header.restore {
    background: linear-gradient(135deg, #5bc0de 0%, #337ab7 100%);
}
.mxchanger-modal-header h3 { margin: 0; font-size: 1.4em; }
.mxchanger-modal-header h3 i { margin-right: 10px; }
.mxchanger-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    font-size: 24px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
}
.mxchanger-modal-close:hover { background: rgba(255, 255, 255, 0.3); }
.mxchanger-modal-body { padding: 25px; overflow-y: auto; max-height: calc(90vh - 180px); }
.mxchanger-modal-footer {
    padding: 15px 25px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.mxchanger-loading { text-align: center; padding: 60px 20px; }
.mxchanger-loading .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e9ecef;
    border-top-color: #4285f4;
    border-radius: 50%;
    animation: mxspin 1s linear infinite;
    margin: 0 auto 20px;
}
@keyframes mxspin { to { transform: rotate(360deg); } }
.mxchanger-loading p { color: #6c757d; font-size: 1.1em; }
.mxchanger-comparison { display: grid; grid-template-columns: 1fr 60px 1fr; gap: 20px; margin-bottom: 25px; }
.mxchanger-dns-panel { background: #f8f9fa; border-radius: 8px; padding: 20px; border: 1px solid #e9ecef; }
.mxchanger-dns-panel.current { border-left: 4px solid #6c757d; }
.mxchanger-dns-panel.proposed { border-left: 4px solid #34a853; }
.mxchanger-dns-panel.proposed.local { border-left: 4px solid #337ab7; }
.mxchanger-dns-panel h4 { margin: 0 0 15px 0; color: #495057; font-size: 1.1em; font-weight: 600; }
.mxchanger-dns-panel h4 i { margin-right: 8px; }
.mxchanger-dns-panel.current h4 { color: #6c757d; }
.mxchanger-dns-panel.proposed h4 { color: #34a853; }
.mxchanger-dns-panel.proposed.local h4 { color: #337ab7; }
.mxchanger-arrow { display: flex; align-items: center; justify-content: center; font-size: 28px; color: #4285f4; }
.mxchanger-record {
    background: #fff;
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 8px;
    border: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.mxchanger-record:last-child { margin-bottom: 0; }
.mxchanger-record .priority {
    background: #e9ecef;
    padding: 4px 10px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.9em;
    min-width: 40px;
    text-align: center;
}
.mxchanger-record .host { font-family: monospace; font-size: 0.95em; word-break: break-all; }
.mxchanger-record.remove { background: #fff5f5; border-color: #f5c6cb; }
.mxchanger-record.remove .host { text-decoration: line-through; color: #dc3545; }
.mxchanger-record.add { background: #f0fff4; border-color: #c3e6cb; }
.mxchanger-record.add .host { color: #28a745; }
.mxchanger-record.add.local { background: #e8f4fc; border-color: #bee5eb; }
.mxchanger-record.add.local .host { color: #337ab7; }
.mxchanger-domain-info {
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.mxchanger-domain-info .icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 24px;
}
.mxchanger-domain-info .details h4 { margin: 0 0 5px 0; font-size: 1.2em; }
.mxchanger-domain-info .details p { margin: 0; color: #6c757d; font-size: 0.9em; }
.mxchanger-domain-info .mx-badge { margin-left: auto; padding: 6px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 500; }
.mxchanger-domain-info .mx-badge.google { background: #e8f5e9; color: #2e7d32; }
.mxchanger-domain-info .mx-badge.local { background: #e3f2fd; color: #1565c0; }
.mxchanger-domain-info .mx-badge.other { background: #fff3e0; color: #e65100; }
.mxchanger-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-left: 4px solid #ffc107;
    border-radius: 6px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.mxchanger-warning i { color: #856404; font-size: 20px; margin-top: 2px; }
.mxchanger-warning .content h5 { margin: 0 0 5px 0; color: #856404; font-weight: 600; }
.mxchanger-warning .content p { margin: 0; color: #856404; font-size: 0.9em; }
.mxchanger-success { text-align: center; padding: 40px 20px; }
.mxchanger-success .icon {
    width: 80px;
    height: 80px;
    background: #d4edda;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #28a745;
    font-size: 40px;
}
.mxchanger-success h4 { color: #28a745; margin: 0 0 10px 0; font-size: 1.4em; }
.mxchanger-success p { color: #6c757d; margin: 0; }
.mxchanger-error { text-align: center; padding: 40px 20px; }
.mxchanger-error .icon {
    width: 80px;
    height: 80px;
    background: #f8d7da;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #dc3545;
    font-size: 40px;
}
.mxchanger-error h4 { color: #dc3545; margin: 0 0 10px 0; font-size: 1.4em; }
.mxchanger-error p { color: #6c757d; margin: 0; }
.mxchanger-btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.95em;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.mxchanger-btn-primary { background: linear-gradient(135deg, #4285f4 0%, #34a853 100%); color: #fff; }
.mxchanger-btn-primary:hover { box-shadow: 0 4px 12px rgba(66, 133, 244, 0.4); color: #fff; }
.mxchanger-btn-info { background: linear-gradient(135deg, #5bc0de 0%, #337ab7 100%); color: #fff; }
.mxchanger-btn-info:hover { box-shadow: 0 4px 12px rgba(51, 122, 183, 0.4); color: #fff; }
.mxchanger-btn-secondary { background: #6c757d; color: #fff; }
.mxchanger-btn-secondary:hover { background: #5a6268; color: #fff; }
.mxchanger-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.mxchanger-action-choice { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
.mxchanger-action-card {
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 25px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.mxchanger-action-card:hover { transform: translateY(-3px); box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); }
.mxchanger-action-card.google:hover { border-color: #28a745; background: #f0fff4; }
.mxchanger-action-card.local:hover { border-color: #2196f3; background: #e3f2fd; }
.mxchanger-action-card .icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 28px;
}
.mxchanger-action-card.google .icon { background: linear-gradient(135deg, #4285f4 0%, #34a853 100%); color: #fff; }
.mxchanger-action-card.local .icon { background: linear-gradient(135deg, #5bc0de 0%, #337ab7 100%); color: #fff; }
.mxchanger-action-card h4 { margin: 0 0 8px 0; }
.mxchanger-action-card p { margin: 0; color: #6c757d; font-size: 0.9em; }
.mxchanger-module-btn {
    background: linear-gradient(135deg, #4285f4 0%, #34a853 100%) !important;
    color: #fff !important;
    border: none !important;
    margin-left: 5px;
}
.mxchanger-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 6px;
    color: #fff;
    font-weight: 500;
    z-index: 10001;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
.mxchanger-toast.success { background: #28a745; }
.mxchanger-toast.error { background: #dc3545; }
</style>';
});

/**
 * Add MX Manager button and JavaScript
 */
add_hook('AdminAreaFooterOutput', 1, function($vars) {
    $csrfToken = generate_token("plain");

    return '
<!-- MX Changer Modal -->
<div class="mxchanger-modal-overlay" id="mxchanger-modal">
    <div class="mxchanger-modal">
        <div class="mxchanger-modal-header" id="mxchanger-modal-header">
            <h3><i class="fas fa-envelope"></i> <span id="mxchanger-modal-title">MX Record Manager</span></h3>
            <button class="mxchanger-modal-close" onclick="MXChanger.closeModal()">&times;</button>
        </div>
        <div class="mxchanger-modal-body" id="mxchanger-modal-body"></div>
        <div class="mxchanger-modal-footer" id="mxchanger-modal-footer"></div>
    </div>
</div>

<script>
var MXChanger = {
    serviceId: null,
    domain: null,
    currentRecords: [],
    currentMxType: null,
    csrfToken: "' . $csrfToken . '",

    init: function() {
        console.log("MXChanger: Initializing...");
        this.injectButtons();
    },

    injectButtons: function() {
        var self = this;

        // Find Module Commands row - look for the text "Module Commands"
        var moduleCommandsContainer = null;
        var allElements = document.querySelectorAll("td");

        allElements.forEach(function(el) {
            if (el.textContent.trim() === "Module Commands") {
                // Found the label, now find the adjacent cell with buttons
                var nextCell = el.nextElementSibling;
                if (nextCell) {
                    moduleCommandsContainer = nextCell;
                    console.log("MXChanger: Found Module Commands container", nextCell);
                }
            }
        });

        if (!moduleCommandsContainer) {
            console.log("MXChanger: Module Commands not found on this page");
            return;
        }

        // Check if button already exists
        if (moduleCommandsContainer.querySelector(".mxchanger-module-btn")) {
            console.log("MXChanger: Button already exists");
            return;
        }

        // Get service ID from URL
        var serviceId = null;
        var urlParams = new URLSearchParams(window.location.search);
        serviceId = urlParams.get("id");
        console.log("MXChanger: Service ID from URL: " + serviceId);

        // Get domain from page
        var domain = null;
        allElements.forEach(function(el) {
            if (el.textContent.trim() === "Domain") {
                var nextCell = el.nextElementSibling;
                if (nextCell) {
                    var input = nextCell.querySelector("input");
                    var select = nextCell.querySelector("select");
                    if (input && input.value) {
                        domain = input.value.trim();
                    } else if (select && select.value) {
                        domain = select.value.trim();
                    } else {
                        domain = nextCell.textContent.trim().split("\\n")[0].trim();
                    }
                }
            }
        });
        console.log("MXChanger: Domain found: " + domain);

        if (serviceId && domain) {
            var mxBtn = document.createElement("button");
            mxBtn.type = "button";
            mxBtn.className = "btn btn-default mxchanger-module-btn";
            mxBtn.innerHTML = \'<i class="fas fa-envelope"></i> MX Manager\';
            mxBtn.onclick = function(e) {
                e.preventDefault();
                MXChanger.openModal(serviceId, domain);
                return false;
            };
            moduleCommandsContainer.appendChild(mxBtn);
            console.log("MXChanger: Button added!");
        } else {
            console.log("MXChanger: Missing serviceId or domain");
        }
    },

    openModal: function(serviceId, domain) {
        this.serviceId = serviceId;
        this.domain = domain;
        document.getElementById("mxchanger-modal").classList.add("active");
        document.getElementById("mxchanger-modal-header").className = "mxchanger-modal-header";
        document.getElementById("mxchanger-modal-title").textContent = "MX Record Manager";
        this.showLoading("Fetching current DNS records...");
        this.fetchCurrentRecords();
    },

    closeModal: function() {
        document.getElementById("mxchanger-modal").classList.remove("active");
    },

    showLoading: function(message) {
        document.getElementById("mxchanger-modal-body").innerHTML =
            \'<div class="mxchanger-loading"><div class="spinner"></div><p>\' + (message || "Loading...") + \'</p></div>\';
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()">Cancel</button>\';
    },

    fetchCurrentRecords: function() {
        var self = this;
        fetch("addonmodules.php?module=mxchanger&action=get_dns&service_id=" + this.serviceId + "&token=" + this.csrfToken)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    self.currentRecords = data.records || [];
                    self.currentMxType = data.mx_type || "other";
                    self.showActionChoice(data);
                } else {
                    self.showError(data.message || "Failed to fetch DNS records");
                }
            })
            .catch(function(error) {
                self.showError("Network error: " + error.message);
            });
    },

    showActionChoice: function(data) {
        var mxTypeBadge = "";
        if (this.currentMxType === "google") {
            mxTypeBadge = \'<span class="mx-badge google"><i class="fab fa-google"></i> Google MX</span>\';
        } else if (this.currentMxType === "local") {
            mxTypeBadge = \'<span class="mx-badge local"><i class="fas fa-server"></i> Local Mail</span>\';
        } else {
            mxTypeBadge = \'<span class="mx-badge other"><i class="fas fa-question-circle"></i> Custom MX</span>\';
        }

        var html = \'<div class="mxchanger-domain-info">\' +
            \'<div class="icon"><i class="fas fa-globe"></i></div>\' +
            \'<div class="details">\' +
            \'<h4>\' + this.domain + \'</h4>\' +
            \'<p>Service ID: \' + this.serviceId + \'</p></div>\' + mxTypeBadge + \'</div>\';

        html += \'<h4 style="margin-bottom: 15px;">Select an action:</h4>\';
        html += \'<div class="mxchanger-action-choice">\';
        html += \'<div class="mxchanger-action-card google" onclick="MXChanger.selectAction(\\\'google\\\')">\' +
            \'<div class="icon"><i class="fab fa-google"></i></div>\' +
            \'<h4>Set Google Workspace MX</h4>\' +
            \'<p>Configure MX records for Google Workspace email</p></div>\';
        html += \'<div class="mxchanger-action-card local" onclick="MXChanger.selectAction(\\\'local\\\')">\' +
            \'<div class="icon"><i class="fas fa-server"></i></div>\' +
            \'<h4>Restore Local Mail</h4>\' +
            \'<p>Reset to default cPanel mail server</p></div>\';
        html += \'</div>\';

        if (this.currentRecords.length > 0) {
            html += \'<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">\';
            html += \'<h5 style="margin: 0 0 10px 0; color: #6c757d;">Current MX Records</h5>\';
            html += \'<div style="font-family: monospace; font-size: 0.9em;">\';
            var self = this;
            this.currentRecords.forEach(function(record) {
                html += \'<div style="padding: 3px 0;">\' + record.priority + \' \' + record.host + \'</div>\';
            });
            html += \'</div></div>\';
        }

        document.getElementById("mxchanger-modal-body").innerHTML = html;
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()">Cancel</button>\';
    },

    selectAction: function(action) {
        if (action === "google") {
            document.getElementById("mxchanger-modal-header").className = "mxchanger-modal-header";
            document.getElementById("mxchanger-modal-title").textContent = "Set Google Workspace MX";
            this.showGoogleComparison();
        } else {
            document.getElementById("mxchanger-modal-header").className = "mxchanger-modal-header restore";
            document.getElementById("mxchanger-modal-title").textContent = "Restore Local Mail";
            this.showLocalComparison();
        }
    },

    showGoogleComparison: function() {
        var self = this;
        var html = \'<div class="mxchanger-domain-info">\' +
            \'<div class="icon"><i class="fab fa-google"></i></div>\' +
            \'<div class="details"><h4>\' + this.domain + \'</h4>\' +
            \'<p>Configure Google Workspace MX Records</p></div></div>\';

        html += \'<div class="mxchanger-warning"><i class="fas fa-exclamation-triangle"></i>\' +
            \'<div class="content"><h5>Warning: DNS Changes</h5>\' +
            \'<p>This will remove existing MX records and replace them with Google Workspace MX records.</p></div></div>\';

        html += \'<div class="mxchanger-comparison">\';
        html += \'<div class="mxchanger-dns-panel current"><h4><i class="fas fa-inbox"></i> Current MX Records</h4>\';
        if (this.currentRecords.length === 0) {
            html += \'<p style="color: #999;">No MX records found</p>\';
        } else {
            this.currentRecords.forEach(function(record) {
                html += \'<div class="mxchanger-record remove"><span class="priority">\' + record.priority + \'</span>\' +
                    \'<span class="host">\' + record.host + \'</span></div>\';
            });
        }
        html += \'</div><div class="mxchanger-arrow"><i class="fas fa-arrow-right"></i></div>\';
        html += \'<div class="mxchanger-dns-panel proposed"><h4><i class="fab fa-google"></i> Google Workspace MX</h4>\';
        [{priority: 1, host: "ASPMX.L.GOOGLE.COM"}, {priority: 5, host: "ALT1.ASPMX.L.GOOGLE.COM"},
         {priority: 5, host: "ALT2.ASPMX.L.GOOGLE.COM"}, {priority: 10, host: "ALT3.ASPMX.L.GOOGLE.COM"},
         {priority: 10, host: "ALT4.ASPMX.L.GOOGLE.COM"}].forEach(function(record) {
            html += \'<div class="mxchanger-record add"><span class="priority">\' + record.priority + \'</span>\' +
                \'<span class="host">\' + record.host + \'</span></div>\';
        });
        html += \'</div></div>\';

        document.getElementById("mxchanger-modal-body").innerHTML = html;
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.fetchCurrentRecords()"><i class="fas fa-arrow-left"></i> Back</button>\' +
            \'<button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.applyGoogleMx()"><i class="fas fa-check"></i> Apply Google MX</button>\';
    },

    showLocalComparison: function() {
        var self = this;
        var html = \'<div class="mxchanger-domain-info">\' +
            \'<div class="icon" style="background: linear-gradient(135deg, #5bc0de 0%, #337ab7 100%);"><i class="fas fa-server"></i></div>\' +
            \'<div class="details"><h4>\' + this.domain + \'</h4>\' +
            \'<p>Restore Default cPanel Mail Configuration</p></div></div>\';

        html += \'<div class="mxchanger-warning"><i class="fas fa-exclamation-triangle"></i>\' +
            \'<div class="content"><h5>Warning: DNS Changes</h5>\' +
            \'<p>This will remove existing MX records and point mail to the local cPanel server.</p></div></div>\';

        html += \'<div class="mxchanger-comparison">\';
        html += \'<div class="mxchanger-dns-panel current"><h4><i class="fas fa-inbox"></i> Current MX Records</h4>\';
        if (this.currentRecords.length === 0) {
            html += \'<p style="color: #999;">No MX records found</p>\';
        } else {
            this.currentRecords.forEach(function(record) {
                html += \'<div class="mxchanger-record remove"><span class="priority">\' + record.priority + \'</span>\' +
                    \'<span class="host">\' + record.host + \'</span></div>\';
            });
        }
        html += \'</div><div class="mxchanger-arrow"><i class="fas fa-arrow-right"></i></div>\';
        html += \'<div class="mxchanger-dns-panel proposed local"><h4><i class="fas fa-server"></i> Local Mail Server</h4>\';
        html += \'<div class="mxchanger-record add local"><span class="priority">0</span>\' +
            \'<span class="host">\' + this.domain + \'</span></div>\';
        html += \'</div></div>\';

        document.getElementById("mxchanger-modal-body").innerHTML = html;
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.fetchCurrentRecords()"><i class="fas fa-arrow-left"></i> Back</button>\' +
            \'<button class="mxchanger-btn mxchanger-btn-info" onclick="MXChanger.applyLocalMx()"><i class="fas fa-check"></i> Restore Local Mail</button>\';
    },

    applyGoogleMx: function() {
        var self = this;
        this.showLoading("Applying Google MX configuration...");
        document.getElementById("mxchanger-modal-footer").innerHTML = "";

        fetch("addonmodules.php?module=mxchanger&action=update_dns&service_id=" + this.serviceId + "&token=" + this.csrfToken, {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({service_id: this.serviceId})
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showSuccess("Google Workspace MX records applied successfully!");
            } else {
                self.showError(data.message || "Failed to update DNS records");
            }
        })
        .catch(function(error) { self.showError("Network error: " + error.message); });
    },

    applyLocalMx: function() {
        var self = this;
        this.showLoading("Restoring local mail configuration...");
        document.getElementById("mxchanger-modal-footer").innerHTML = "";

        fetch("addonmodules.php?module=mxchanger&action=restore_local&service_id=" + this.serviceId + "&token=" + this.csrfToken, {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({service_id: this.serviceId})
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showSuccess("Local mail server configuration restored!");
            } else {
                self.showError(data.message || "Failed to restore local mail");
            }
        })
        .catch(function(error) { self.showError("Network error: " + error.message); });
    },

    showSuccess: function(message) {
        document.getElementById("mxchanger-modal-body").innerHTML =
            \'<div class="mxchanger-success"><div class="icon"><i class="fas fa-check"></i></div>\' +
            \'<h4>Success!</h4><p>\' + message + \'<br><small style="color: #999;">DNS changes may take up to 48 hours to propagate.</small></p></div>\';
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.closeModal()">Done</button>\';
    },

    showError: function(message) {
        document.getElementById("mxchanger-modal-body").innerHTML =
            \'<div class="mxchanger-error"><div class="icon"><i class="fas fa-times"></i></div>\' +
            \'<h4>Error</h4><p>\' + message + \'</p></div>\';
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()">Close</button>\' +
            \'<button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.fetchCurrentRecords()">Retry</button>\';
    }
};

// Initialize when page loads
document.addEventListener("DOMContentLoaded", function() { MXChanger.init(); });
setTimeout(function() { MXChanger.init(); }, 500);
setTimeout(function() { MXChanger.init(); }, 1500);
</script>';
});
