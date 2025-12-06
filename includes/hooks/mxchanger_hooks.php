<?php
/**
 * WHMCS MX Changer - Hooks
 * Place this file in: /your-whmcs/includes/hooks/mxchanger_hooks.php
 */

add_hook('AdminAreaHeadOutput', 1, function($vars) {
    return <<<'CSS'
<!-- MX Changer CSS -->
<style>
.mxchanger-modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:10000;justify-content:center;align-items:center}
.mxchanger-modal-overlay.active{display:flex}
.mxchanger-modal{background:#fff;border-radius:8px;max-width:900px;width:95%;max-height:90vh;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.3)}
.mxchanger-modal-header{background:linear-gradient(135deg,#4285f4 0%,#34a853 100%);color:#fff;padding:20px 25px;display:flex;justify-content:space-between;align-items:center}
.mxchanger-modal-header.restore{background:linear-gradient(135deg,#5bc0de 0%,#337ab7 100%)}
.mxchanger-modal-header h3{margin:0;font-size:1.4em;color:#fff !important}
.mxchanger-modal-header h3 i{margin-right:10px;color:#fff !important}
.mxchanger-modal-close{background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:24px;width:36px;height:36px;border-radius:50%;cursor:pointer}
.mxchanger-modal-close:hover{background:rgba(255,255,255,0.3)}
.mxchanger-modal-body{padding:25px;overflow-y:auto;max-height:calc(90vh - 180px)}
.mxchanger-modal-footer{padding:15px 25px;background:#f8f9fa;border-top:1px solid #e9ecef;display:flex;justify-content:flex-end;gap:10px}
.mxchanger-loading{text-align:center;padding:60px 20px}
.mxchanger-loading .spinner{width:50px;height:50px;border:4px solid #e9ecef;border-top-color:#4285f4;border-radius:50%;animation:mxspin 1s linear infinite;margin:0 auto 20px}
@keyframes mxspin{to{transform:rotate(360deg)}}
.mxchanger-loading p{color:#6c757d;font-size:1.1em}
.mxchanger-comparison{display:grid;grid-template-columns:1fr 60px 1fr;gap:20px;margin-bottom:25px}
.mxchanger-dns-panel{background:#f8f9fa;border-radius:8px;padding:20px;border:1px solid #e9ecef}
.mxchanger-dns-panel.current{border-left:4px solid #6c757d}
.mxchanger-dns-panel.proposed{border-left:4px solid #34a853}
.mxchanger-dns-panel h4{margin:0 0 15px 0;color:#495057;font-size:1.1em;font-weight:600}
.mxchanger-dns-panel h4 i{margin-right:8px}
.mxchanger-dns-panel.current h4{color:#6c757d}
.mxchanger-dns-panel.proposed h4{color:#34a853}
.mxchanger-arrow{display:flex;align-items:center;justify-content:center;font-size:28px;color:#4285f4}
.mxchanger-record{background:#fff;padding:12px 15px;border-radius:6px;margin-bottom:8px;border:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center}
.mxchanger-record:last-child{margin-bottom:0}
.mxchanger-record .priority{background:#e9ecef;padding:4px 10px;border-radius:4px;font-weight:600;font-size:0.9em;min-width:40px;text-align:center}
.mxchanger-record .host{font-family:monospace;font-size:0.95em;word-break:break-all}
.mxchanger-record.remove{background:#fff5f5;border-color:#f5c6cb}
.mxchanger-record.remove .host{text-decoration:line-through;color:#dc3545}
.mxchanger-record.add{background:#f0fff4;border-color:#c3e6cb}
.mxchanger-record.add .host{color:#28a745}
.mxchanger-domain-info{background:linear-gradient(135deg,#f8f9fa 0%,#fff 100%);border:1px solid #e9ecef;border-radius:8px;padding:15px 20px;margin-bottom:20px;display:flex;align-items:center;gap:15px}
.mxchanger-domain-info .icon{width:50px;height:50px;background:linear-gradient(135deg,#4285f4 0%,#34a853 100%);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px}
.mxchanger-domain-info .details h4{margin:0 0 5px 0;font-size:1.2em}
.mxchanger-domain-info .details p{margin:0;color:#6c757d;font-size:0.9em}
.mxchanger-domain-info .mx-badge{margin-left:auto;padding:6px 12px;border-radius:20px;font-size:0.85em;font-weight:500}
.mxchanger-domain-info .mx-badge.google{background:#e8f5e9;color:#2e7d32}
.mxchanger-domain-info .mx-badge.local{background:#e3f2fd;color:#1565c0}
.mxchanger-domain-info .mx-badge.other{background:#fff3e0;color:#e65100}
.mxchanger-warning{background:#fff3cd;border:1px solid #ffc107;border-left:4px solid #ffc107;border-radius:6px;padding:12px 15px;margin-bottom:15px;display:flex;align-items:center;gap:10px}
.mxchanger-warning i{color:#856404;font-size:20px;margin-top:2px}
.mxchanger-warning .content{min-height:auto !important}
.mxchanger-warning .content h5{margin:0 0 5px 0;color:#856404;font-weight:600}
.mxchanger-warning .content p{margin:0;color:#856404;font-size:0.9em}
.mxchanger-success{text-align:center;padding:40px 20px}
.mxchanger-success .icon{width:80px;height:80px;background:#d4edda;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:#28a745;font-size:40px}
.mxchanger-success h4{color:#28a745;margin:0 0 10px 0;font-size:1.4em}
.mxchanger-success p{color:#6c757d;margin:0}
.mxchanger-error{text-align:center;padding:40px 20px}
.mxchanger-error .icon{width:80px;height:80px;background:#f8d7da;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:#dc3545;font-size:40px}
.mxchanger-error h4{color:#dc3545;margin:0 0 10px 0;font-size:1.4em}
.mxchanger-error p{color:#6c757d;margin:0}
.mxchanger-btn{padding:10px 20px;border-radius:6px;font-weight:500;font-size:0.95em;cursor:pointer;border:none;display:inline-flex;align-items:center;gap:8px}
.mxchanger-btn-primary{background:linear-gradient(135deg,#4285f4 0%,#34a853 100%);color:#fff}
.mxchanger-btn-primary:hover{box-shadow:0 4px 12px rgba(66,133,244,0.4);color:#fff}
.mxchanger-btn-info{background:linear-gradient(135deg,#5bc0de 0%,#337ab7 100%);color:#fff}
.mxchanger-btn-secondary{background:#6c757d;color:#fff}
.mxchanger-btn-secondary:hover{background:#5a6268;color:#fff}
.mxchanger-action-choice{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0}
.mxchanger-action-card{background:#fff;border:2px solid #e9ecef;border-radius:10px;padding:25px;text-align:center;cursor:pointer;transition:all 0.2s}
.mxchanger-action-card:hover{transform:translateY(-3px);box-shadow:0 5px 20px rgba(0,0,0,0.1)}
.mxchanger-action-card.google:hover{border-color:#28a745;background:#f0fff4}
.mxchanger-action-card.local:hover{border-color:#2196f3;background:#e3f2fd}
.mxchanger-action-card .icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:28px}
.mxchanger-action-card.google .icon{background:linear-gradient(135deg,#4285f4 0%,#34a853 100%);color:#fff}
.mxchanger-action-card.local .icon{background:linear-gradient(135deg,#5bc0de 0%,#337ab7 100%);color:#fff}
.mxchanger-action-card h4{margin:0 0 8px 0}
.mxchanger-action-card p{margin:0;color:#6c757d;font-size:0.9em}
.mxchanger-module-btn{background:linear-gradient(135deg,#4285f4 0%,#34a853 100%)!important;color:#fff!important;border:none!important;margin-left:5px}
</style>
CSS;
});

add_hook('AdminAreaFooterOutput', 1, function($vars) {
    $csrfToken = generate_token("plain");

    // Get admin path from WHMCS config
    $adminPath = defined('WHMCS\Application\Support\Facades\App')
        ? \WHMCS\Application\Support\Facades\App::getAdminPath()
        : (defined('ADMINAREA') ? '/' . basename(dirname($_SERVER['SCRIPT_NAME'])) . '/' : '/admin/');

    // Alternative: Get from config
    if (function_exists('getAdminFolder')) {
        $adminPath = '/' . getAdminFolder() . '/';
    } elseif (isset($GLOBALS['customadminpath'])) {
        $adminPath = '/' . $GLOBALS['customadminpath'] . '/';
    }

    // Clean up the path
    $adminPath = '/' . trim($adminPath, '/') . '/';

    $js = <<<JSEND
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
    csrfToken: (function() {
        // Try to get token from page
        var tokenInput = document.querySelector('input[name="token"]');
        if (tokenInput) return tokenInput.value;
        // Try from meta tag
        var metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) return metaToken.getAttribute('content');
        // Fallback to PHP generated
        return "$csrfToken";
    })(),
    adminPath: "$adminPath",

    init: function() {
        console.log("MXChanger: Initializing... Admin path: " + this.adminPath + ", token: " + this.csrfToken.substring(0,10) + "...");
        this.injectButtons();
    },

    injectButtons: function() {
        var self = this;
        var moduleCommandsContainer = null;
        var allTds = document.querySelectorAll("td");

        allTds.forEach(function(el) {
            if (el.textContent.trim() === "Module Commands") {
                var nextCell = el.nextElementSibling;
                if (nextCell) {
                    moduleCommandsContainer = nextCell;
                    console.log("MXChanger: Found Module Commands container");
                }
            }
        });

        if (!moduleCommandsContainer) {
            console.log("MXChanger: Module Commands not found");
            return;
        }

        if (moduleCommandsContainer.querySelector(".mxchanger-module-btn")) {
            console.log("MXChanger: Button already exists");
            return;
        }

        // Get service ID
        var serviceId = null;
        var urlParams = new URLSearchParams(window.location.search);
        serviceId = urlParams.get("id");

        if (!serviceId) {
            var idInput = document.querySelector("input[name='id']");
            if (idInput) serviceId = idInput.value;
        }

        if (!serviceId) {
            document.querySelectorAll("input[type='hidden']").forEach(function(input) {
                console.log("MXChanger DEBUG: " + input.name + " = " + input.value);
                if (input.name === "id" || input.name === "serviceid") {
                    serviceId = input.value;
                }
            });
        }

        // Get domain
        var domain = null;
        allTds.forEach(function(el) {
            if (el.textContent.trim() === "Domain") {
                var nextCell = el.nextElementSibling;
                if (nextCell) {
                    var input = nextCell.querySelector("input");
                    var select = nextCell.querySelector("select");
                    if (input) domain = input.value;
                    else if (select) domain = select.value;
                    else domain = nextCell.textContent.trim().split("\\n")[0];
                }
            }
        });

        console.log("MXChanger: serviceId=" + serviceId + ", domain=" + domain);

        if (serviceId && domain) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "btn btn-default mxchanger-module-btn";
            btn.innerHTML = '<i class="fas fa-envelope"></i> MX Manager';
            btn.onclick = function(e) {
                e.preventDefault();
                MXChanger.openModal(serviceId, domain);
            };

            // Find the modcmdbtns div (where buttons actually live)
            var btnContainer = document.getElementById("modcmdbtns");
            if (!btnContainer) {
                btnContainer = moduleCommandsContainer;
            }

            // Find Change Password button by ID
            var changePasswordBtn = document.getElementById("btnChange_Password");

            if (changePasswordBtn) {
                // Insert after Change Password
                if (changePasswordBtn.nextSibling) {
                    btnContainer.insertBefore(btn, changePasswordBtn.nextSibling);
                } else {
                    btnContainer.appendChild(btn);
                }
                console.log("MXChanger: Button added after Change Password");
            } else {
                // Fallback: append at end of button container
                btnContainer.appendChild(btn);
                console.log("MXChanger: Button appended (Change Password not found)");
            }
        } else {
            console.log("MXChanger: Missing serviceId or domain");
        }
    },

    openModal: function(serviceId, domain) {
        this.serviceId = serviceId;
        this.domain = domain;
        document.getElementById("mxchanger-modal").classList.add("active");
        this.showLoading("Fetching DNS records...");
        this.fetchRecords();
    },

    closeModal: function() {
        document.getElementById("mxchanger-modal").classList.remove("active");
    },

    showLoading: function(msg) {
        document.getElementById("mxchanger-modal-body").innerHTML = '<div class="mxchanger-loading"><div class="spinner"></div><p>' + msg + '</p></div>';
        document.getElementById("mxchanger-modal-footer").innerHTML = '<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()">Cancel</button>';
    },

    fetchRecords: function() {
        var self = this;
        // Use direct AJAX handler in the module directory
        var url = "/modules/addons/mxchanger/ajax_handler.php?action=get_dns&service_id=" + this.serviceId;
        console.log("MXChanger: Fetching " + url);
        fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(function(r) {
            console.log("MXChanger: Got response, status: " + r.status);
            return r.text();
        })
        .then(function(text) {
            console.log("MXChanger: Response text: " + text.substring(0, 800));
            var data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                console.log("MXChanger: Not valid JSON");
                self.showError("Server returned invalid response");
                return;
            }
            if (data.success) {
                self.currentRecords = data.records || [];
                self.showActions(data);
            } else {
                self.showError(data.message || "Failed to fetch DNS");
            }
        })
        .catch(function(e) {
            console.log("MXChanger: Fetch error: " + e.message);
            self.showError("Network error: " + e.message);
        });
    },

    showActions: function(data) {
        this.mxType = data.mx_type;

        // Auto-navigate to the appropriate view based on current MX type
        if (data.mx_type === "google") {
            // Already on Google, show option to restore local
            this.showLocal();
        } else {
            // On local or other, show option to set Google
            this.showGoogle();
        }
    },

    showMenu: function() {
        var badge = '<span class="mx-badge other">Custom</span>';
        if (this.mxType === "google") badge = '<span class="mx-badge google">Google MX</span>';
        else if (this.mxType === "local") badge = '<span class="mx-badge local">Local Mail</span>';

        document.getElementById("mxchanger-modal-title").textContent = "MX Record Manager";
        document.getElementById("mxchanger-modal-header").classList.remove("restore");

        var html = '<div class="mxchanger-domain-info"><div class="icon"><i class="fas fa-globe"></i></div>';
        html += '<div class="details"><h4>' + this.domain + '</h4><p>Service ID: ' + this.serviceId + '</p></div>' + badge + '</div>';
        html += '<h4>Select an action:</h4>';
        html += '<div class="mxchanger-action-choice">';
        html += '<div class="mxchanger-action-card google" onclick="MXChanger.showGoogle()"><div class="icon"><i class="fab fa-google"></i></div><h4>Google Workspace MX</h4><p>Configure Google email</p></div>';
        html += '<div class="mxchanger-action-card local" onclick="MXChanger.showLocal()"><div class="icon"><i class="fas fa-server"></i></div><h4>Restore Local Mail</h4><p>Reset to cPanel mail</p></div>';
        html += '</div>';

        if (this.currentRecords.length > 0) {
            html += '<div style="margin-top:20px;padding:15px;background:#f8f9fa;border-radius:6px;"><h5>Current MX Records:</h5>';
            this.currentRecords.forEach(function(r) { html += '<div>' + r.priority + ' ' + r.host + '</div>'; });
            html += '</div>';
        }

        document.getElementById("mxchanger-modal-body").innerHTML = html;
        document.getElementById("mxchanger-modal-footer").innerHTML = '<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()">Cancel</button>';
    },

    showGoogle: function() {
        document.getElementById("mxchanger-modal-title").textContent = "Set Google MX";
        var html = '<div class="mxchanger-warning"><i class="fas fa-exclamation-triangle"></i><div class="content"><h5>Warning</h5><p>This will replace all MX records with Google Workspace MX.</p></div></div>';
        html += '<div class="mxchanger-comparison"><div class="mxchanger-dns-panel current"><h4>Current</h4>';
        if (this.currentRecords.length === 0) html += '<p>No records</p>';
        else this.currentRecords.forEach(function(r) { html += '<div class="mxchanger-record remove"><span class="priority">' + r.priority + '</span><span class="host">' + r.host + '</span></div>'; });
        html += '</div><div class="mxchanger-arrow"><i class="fas fa-arrow-right"></i></div><div class="mxchanger-dns-panel proposed"><h4>Google MX</h4>';
        [{p:1,h:"ASPMX.L.GOOGLE.COM"},{p:5,h:"ALT1.ASPMX.L.GOOGLE.COM"},{p:5,h:"ALT2.ASPMX.L.GOOGLE.COM"},{p:10,h:"ALT3.ASPMX.L.GOOGLE.COM"},{p:10,h:"ALT4.ASPMX.L.GOOGLE.COM"}].forEach(function(r) {
            html += '<div class="mxchanger-record add"><span class="priority">' + r.p + '</span><span class="host">' + r.h + '</span></div>';
        });
        html += '</div></div>';
        document.getElementById("mxchanger-modal-body").innerHTML = html;
        document.getElementById("mxchanger-modal-footer").innerHTML = '<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.showMenu()">Back</button><button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.applyGoogle()">Apply Google MX</button>';
    },

    showLocal: function() {
        document.getElementById("mxchanger-modal-title").textContent = "Restore Local Mail";
        document.getElementById("mxchanger-modal-header").classList.add("restore");
        var html = '<div class="mxchanger-warning"><i class="fas fa-exclamation-triangle"></i><div class="content"><h5>Warning</h5><p>This will reset MX to local cPanel mail server.</p></div></div>';
        html += '<div class="mxchanger-comparison"><div class="mxchanger-dns-panel current"><h4>Current</h4>';
        if (this.currentRecords.length === 0) html += '<p>No records</p>';
        else this.currentRecords.forEach(function(r) { html += '<div class="mxchanger-record remove"><span class="priority">' + r.priority + '</span><span class="host">' + r.host + '</span></div>'; });
        html += '</div><div class="mxchanger-arrow"><i class="fas fa-arrow-right"></i></div><div class="mxchanger-dns-panel proposed"><h4>Local Mail</h4>';
        html += '<div class="mxchanger-record add"><span class="priority">0</span><span class="host">' + this.domain + '</span></div>';
        html += '</div></div>';
        document.getElementById("mxchanger-modal-body").innerHTML = html;
        document.getElementById("mxchanger-modal-footer").innerHTML = '<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.showMenu()">Back</button><button class="mxchanger-btn mxchanger-btn-info" onclick="MXChanger.applyLocal()">Restore Local</button>';
    },

    applyGoogle: function() {
        var self = this;
        this.showLoading("Applying Google MX...");
        document.getElementById("mxchanger-modal-footer").innerHTML = "";
        fetch("/modules/addons/mxchanger/ajax_handler.php?action=update_dns&service_id=" + this.serviceId, {method:"POST", credentials:"same-origin"})
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) self.showSuccess("Google MX applied!");
                else self.showError(data.message || "Failed");
            })
            .catch(function(e) { self.showError("Error: " + e.message); });
    },

    applyLocal: function() {
        var self = this;
        this.showLoading("Restoring local mail...");
        document.getElementById("mxchanger-modal-footer").innerHTML = "";
        fetch("/modules/addons/mxchanger/ajax_handler.php?action=restore_local&service_id=" + this.serviceId, {method:"POST", credentials:"same-origin"})
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) self.showSuccess("Local mail restored!");
                else self.showError(data.message || "Failed");
            })
            .catch(function(e) { self.showError("Error: " + e.message); });
    },

    showSuccess: function(msg) {
        document.getElementById("mxchanger-modal-body").innerHTML = '<div class="mxchanger-success"><div class="icon"><i class="fas fa-check"></i></div><h4>Success!</h4><p>' + msg + '</p></div>';
        document.getElementById("mxchanger-modal-footer").innerHTML = '<button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.closeModal()">Done</button>';
    },

    showError: function(msg) {
        document.getElementById("mxchanger-modal-body").innerHTML = '<div class="mxchanger-error"><div class="icon"><i class="fas fa-times"></i></div><h4>Error</h4><p>' + msg + '</p></div>';
        document.getElementById("mxchanger-modal-footer").innerHTML = '<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()">Close</button><button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.fetchRecords()">Retry</button>';
    }
};

document.addEventListener("DOMContentLoaded", function() { MXChanger.init(); });
setTimeout(function() { MXChanger.init(); }, 500);
setTimeout(function() { MXChanger.init(); }, 1500);
</script>
JSEND;

    return $js;
});
