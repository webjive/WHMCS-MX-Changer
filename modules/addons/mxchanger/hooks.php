<?php
/**
 * WHMCS MX Changer Addon Module - Hooks
 *
 * Adds MX Record Manager button to the Module Commands area
 * on the Products/Services tab of customer summary page.
 *
 * @package    WHMCS
 * @author     WebJIVE
 * @copyright  Copyright (c) WebJIVE
 * @link       https://webjive.com
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Add custom CSS to admin pages
 */
add_hook('AdminAreaHeadOutput', 1, function($vars) {
    // Load on client summary page AND client services page
    $isClientPage = strpos($_SERVER['SCRIPT_NAME'], 'clientssummary.php') !== false ||
                    strpos($_SERVER['SCRIPT_NAME'], 'clientsservices.php') !== false;
    if (!$isClientPage) {
        return '';
    }

    $output = '
<style>
/* MX Changer Modal Styles */
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

.mxchanger-modal-overlay.active {
    display: flex;
}

.mxchanger-modal {
    background: #fff;
    border-radius: 8px;
    max-width: 900px;
    width: 95%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: mxchanger-slide-in 0.3s ease;
}

@keyframes mxchanger-slide-in {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
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

.mxchanger-modal-header h3 {
    margin: 0;
    font-size: 1.4em;
    font-weight: 500;
}

.mxchanger-modal-header h3 i {
    margin-right: 10px;
}

.mxchanger-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: #fff;
    font-size: 24px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mxchanger-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.mxchanger-modal-body {
    padding: 25px;
    overflow-y: auto;
    max-height: calc(90vh - 180px);
}

.mxchanger-modal-footer {
    padding: 15px 25px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.mxchanger-loading {
    text-align: center;
    padding: 60px 20px;
}

.mxchanger-loading .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e9ecef;
    border-top-color: #4285f4;
    border-radius: 50%;
    animation: mxchanger-spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes mxchanger-spin {
    to { transform: rotate(360deg); }
}

.mxchanger-loading p {
    color: #6c757d;
    font-size: 1.1em;
}

.mxchanger-comparison {
    display: grid;
    grid-template-columns: 1fr 60px 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

@media (max-width: 768px) {
    .mxchanger-comparison {
        grid-template-columns: 1fr;
    }
    .mxchanger-arrow {
        transform: rotate(90deg);
    }
}

.mxchanger-dns-panel {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e9ecef;
}

.mxchanger-dns-panel.current {
    border-left: 4px solid #6c757d;
}

.mxchanger-dns-panel.proposed {
    border-left: 4px solid #34a853;
}

.mxchanger-dns-panel.proposed.local {
    border-left: 4px solid #337ab7;
}

.mxchanger-dns-panel h4 {
    margin: 0 0 15px 0;
    color: #495057;
    font-size: 1.1em;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.mxchanger-dns-panel h4 i {
    margin-right: 8px;
}

.mxchanger-dns-panel.current h4 { color: #6c757d; }
.mxchanger-dns-panel.proposed h4 { color: #34a853; }
.mxchanger-dns-panel.proposed.local h4 { color: #337ab7; }

.mxchanger-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #4285f4;
}

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
    color: #495057;
    min-width: 40px;
    text-align: center;
}

.mxchanger-record .host {
    font-family: monospace;
    font-size: 0.95em;
    color: #212529;
    word-break: break-all;
}

.mxchanger-record.remove {
    background: #fff5f5;
    border-color: #f5c6cb;
}

.mxchanger-record.remove .host {
    text-decoration: line-through;
    color: #dc3545;
}

.mxchanger-record.add {
    background: #f0fff4;
    border-color: #c3e6cb;
}

.mxchanger-record.add .host { color: #28a745; }

.mxchanger-record.add.local {
    background: #e8f4fc;
    border-color: #bee5eb;
}

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
    flex-shrink: 0;
}

.mxchanger-domain-info .details h4 {
    margin: 0 0 5px 0;
    color: #212529;
    font-size: 1.2em;
}

.mxchanger-domain-info .details p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9em;
}

.mxchanger-domain-info .mx-badge {
    margin-left: auto;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
}

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

.mxchanger-warning i {
    color: #856404;
    font-size: 20px;
    margin-top: 2px;
}

.mxchanger-warning .content h5 {
    margin: 0 0 5px 0;
    color: #856404;
    font-weight: 600;
}

.mxchanger-warning .content p {
    margin: 0;
    color: #856404;
    font-size: 0.9em;
}

.mxchanger-success {
    text-align: center;
    padding: 40px 20px;
}

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

.mxchanger-success h4 {
    color: #28a745;
    margin: 0 0 10px 0;
    font-size: 1.4em;
}

.mxchanger-success p { color: #6c757d; margin: 0; }

.mxchanger-error {
    text-align: center;
    padding: 40px 20px;
}

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

.mxchanger-error h4 {
    color: #dc3545;
    margin: 0 0 10px 0;
    font-size: 1.4em;
}

.mxchanger-error p { color: #6c757d; margin: 0; }

.mxchanger-btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.95em;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.mxchanger-btn-primary {
    background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
    color: #fff;
}

.mxchanger-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(66, 133, 244, 0.4);
    color: #fff;
}

.mxchanger-btn-info {
    background: linear-gradient(135deg, #5bc0de 0%, #337ab7 100%);
    color: #fff;
}

.mxchanger-btn-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(51, 122, 183, 0.4);
    color: #fff;
}

.mxchanger-btn-secondary {
    background: #6c757d;
    color: #fff;
}

.mxchanger-btn-secondary:hover {
    background: #5a6268;
    color: #fff;
}

.mxchanger-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
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
    animation: mxchanger-toast-in 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 400px;
}

.mxchanger-toast.success { background: #28a745; }
.mxchanger-toast.error { background: #dc3545; }

@keyframes mxchanger-toast-in {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes mxchanger-toast-out {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

.mxchanger-action-choice {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

@media (max-width: 600px) {
    .mxchanger-action-choice { grid-template-columns: 1fr; }
}

.mxchanger-action-card {
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 25px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.mxchanger-action-card:hover {
    border-color: #4285f4;
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.mxchanger-action-card.google { border-color: #34a853; }
.mxchanger-action-card.google:hover { border-color: #28a745; background: #f0fff4; }
.mxchanger-action-card.local { border-color: #337ab7; }
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

.mxchanger-action-card.google .icon {
    background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
    color: #fff;
}

.mxchanger-action-card.local .icon {
    background: linear-gradient(135deg, #5bc0de 0%, #337ab7 100%);
    color: #fff;
}

.mxchanger-action-card h4 { margin: 0 0 8px 0; color: #212529; }
.mxchanger-action-card p { margin: 0; color: #6c757d; font-size: 0.9em; }
</style>
';

    return $output;
});

/**
 * Add MX Manager button to Module Commands on Products/Services tab
 */
add_hook('AdminAreaFooterOutput', 1, function($vars) {
    // Load on client summary page AND client services page
    $isClientPage = strpos($_SERVER['SCRIPT_NAME'], 'clientssummary.php') !== false ||
                    strpos($_SERVER['SCRIPT_NAME'], 'clientsservices.php') !== false;
    if (!$isClientPage) {
        return '';
    }

    $csrfToken = generate_token("plain");

    $output = '
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
        this.injectButtons();
    },

    injectButtons: function() {
        var self = this;

        console.log("MXChanger: injectButtons called");

        // Method 1: Find Module Commands row in service detail view
        // Look for the row with "Module Commands" label and buttons like Create, Suspend, etc.
        var moduleCommandsRow = null;
        var moduleCommandsContainer = null;

        // Search for "Module Commands" text in the page
        document.querySelectorAll("td, th, label, div, span").forEach(function(el) {
            var text = el.textContent.trim();
            if (text === "Module Commands" || text.indexOf("Module Commands") === 0) {
                console.log("MXChanger: Found Module Commands element", el);
                moduleCommandsRow = el.closest("tr") || el.parentElement;
                // Find the container with the buttons (next sibling td or adjacent element)
                if (moduleCommandsRow) {
                    moduleCommandsContainer = moduleCommandsRow.querySelector("td:last-child") ||
                                              moduleCommandsRow.querySelector(".controls") ||
                                              el.nextElementSibling;
                    console.log("MXChanger: Container found", moduleCommandsContainer);
                }
            }
        });

        // Also try finding by looking for the Create/Suspend buttons directly
        if (!moduleCommandsContainer) {
            var createBtn = document.querySelector(\'button[value="Create"], input[value="Create"], a:contains("Create")\');
            if (!createBtn) {
                // Try finding buttons with these texts
                document.querySelectorAll("button, input[type=\'button\'], input[type=\'submit\']").forEach(function(btn) {
                    if (btn.value === "Create" || btn.textContent.trim() === "Create") {
                        moduleCommandsContainer = btn.parentElement;
                        console.log("MXChanger: Found via Create button", moduleCommandsContainer);
                    }
                });
            }
        }

        if (moduleCommandsContainer && !moduleCommandsContainer.querySelector(".mxchanger-module-btn")) {
            // Get service ID from URL or from existing buttons
            var serviceId = null;
            var urlMatch = window.location.search.match(/id=(\d+)/);
            if (urlMatch) {
                serviceId = urlMatch[1];
            } else {
                // Try to find from existing module command buttons
                var existingBtn = moduleCommandsContainer.querySelector("a[href*='id='], button[onclick*='id=']");
                if (existingBtn) {
                    var href = existingBtn.getAttribute("href") || existingBtn.getAttribute("onclick") || "";
                    var match = href.match(/id=(\d+)/);
                    if (match) serviceId = match[1];
                }
            }

            // Get domain from the page
            var domain = null;
            // Look for Domain field in the form
            document.querySelectorAll("td, th, label").forEach(function(el) {
                if (el.textContent.trim() === "Domain") {
                    var row = el.closest("tr") || el.parentElement;
                    if (row) {
                        var valueCell = row.querySelector("td:last-child") ||
                                        row.querySelector(".controls") ||
                                        row.querySelector("input") ||
                                        el.nextElementSibling;
                        if (valueCell) {
                            // Check for input field
                            var input = valueCell.querySelector("input[type='text'], input:not([type='hidden'])");
                            if (input && input.value) {
                                domain = input.value.trim();
                            } else if (valueCell.textContent) {
                                domain = valueCell.textContent.trim().split("\n")[0].trim();
                            }
                        }
                    }
                }
            });

            // Also try to find domain from select dropdown
            if (!domain) {
                var domainSelect = document.querySelector("select[name='domain'], input[name='domain']");
                if (domainSelect) {
                    domain = domainSelect.value;
                }
            }

            console.log("MXChanger: serviceId=" + serviceId + ", domain=" + domain);

            if (serviceId && domain) {
                // Create the MX Manager button matching WHMCS button style
                var mxBtn = document.createElement("button");
                mxBtn.type = "button";
                mxBtn.className = "btn btn-default mxchanger-module-btn";
                mxBtn.innerHTML = \'<i class="fas fa-envelope"></i> MX Manager\';
                mxBtn.style.cssText = "background: linear-gradient(135deg, #4285f4 0%, #34a853 100%); color: #fff; border: none; margin-left: 5px;";
                mxBtn.onclick = function(e) {
                    e.preventDefault();
                    MXChanger.openModal(serviceId, domain);
                    return false;
                };
                moduleCommandsContainer.appendChild(mxBtn);
                console.log("MXChanger: Button added successfully!");
            } else {
                console.log("MXChanger: Missing serviceId or domain, button not added");
            }
        } else {
            console.log("MXChanger: moduleCommandsContainer not found or button already exists");
        }

        // Method 2: Also check dropdown menus for Products/Services list view
        document.querySelectorAll(".dropdown-menu").forEach(function(dropdown) {
            var items = dropdown.querySelectorAll("a");
            var hasModuleCommands = false;
            var serviceId = null;
            var domain = null;

            items.forEach(function(item) {
                var href = item.getAttribute("href") || "";
                if (href.indexOf("modop=") !== -1 || href.indexOf("modulecmd") !== -1 ||
                    href.indexOf("a=module") !== -1 || item.textContent.match(/Create|Suspend|Unsuspend|Terminate/i)) {
                    hasModuleCommands = true;
                    var match = href.match(/id=(\d+)/);
                    if (match) serviceId = match[1];
                }
            });

            if (hasModuleCommands && serviceId) {
                if (dropdown.querySelector(".mxchanger-menu-item")) return;

                var row = dropdown.closest("tr");
                if (row) {
                    var cells = row.querySelectorAll("td");
                    cells.forEach(function(cell) {
                        var text = cell.textContent.trim();
                        if (text.match(/^[a-zA-Z0-9][a-zA-Z0-9\-\.]*\.[a-zA-Z]{2,}$/)) {
                            domain = text;
                        }
                    });
                }

                if (domain) {
                    var divider = document.createElement("li");
                    divider.className = "divider";
                    divider.setAttribute("role", "separator");
                    dropdown.appendChild(divider);

                    var menuItem = document.createElement("li");
                    menuItem.className = "mxchanger-menu-item";
                    var link = document.createElement("a");
                    link.href = "#";
                    link.innerHTML = \'<i class="fas fa-envelope"></i> MX Record Manager\';
                    link.onclick = function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        MXChanger.openModal(serviceId, domain);
                        var dropdownToggle = dropdown.closest(".btn-group");
                        if (dropdownToggle) dropdownToggle.classList.remove("open");
                        return false;
                    };
                    menuItem.appendChild(link);
                    dropdown.appendChild(menuItem);
                }
            }
        });
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
        this.serviceId = null;
        this.domain = null;
        this.currentRecords = [];
        this.currentMxType = null;
    },

    showLoading: function(message) {
        document.getElementById("mxchanger-modal-body").innerHTML =
            \'<div class="mxchanger-loading"><div class="spinner"></div><p>\' + this.escapeHtml(message || "Loading...") + \'</p></div>\';
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()"><i class="fas fa-times"></i> Cancel</button>\';
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
            \'<h4>\' + this.escapeHtml(this.domain) + \'</h4>\' +
            \'<p>Service ID: \' + this.serviceId + \' &bull; Server: \' + this.escapeHtml(data.server || "N/A") + \'</p>\' +
            \'</div>\' + mxTypeBadge + \'</div>\';

        html += \'<h4 style="margin-bottom: 15px; color: #495057;">Select an action:</h4>\';
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
            html += \'<h5 style="margin: 0 0 10px 0; color: #6c757d;"><i class="fas fa-info-circle"></i> Current MX Records</h5>\';
            html += \'<div style="font-family: monospace; font-size: 0.9em; color: #495057;">\';
            this.currentRecords.forEach(function(record) {
                html += \'<div style="padding: 3px 0;">\' + record.priority + \' \' + record.host + \'</div>\';
            });
            html += \'</div></div>\';
        }

        document.getElementById("mxchanger-modal-body").innerHTML = html;
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()"><i class="fas fa-times"></i> Cancel</button>\';
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
            \'<div class="details"><h4>\' + this.escapeHtml(this.domain) + \'</h4>\' +
            \'<p>Configure Google Workspace MX Records</p></div></div>\';

        html += \'<div class="mxchanger-warning"><i class="fas fa-exclamation-triangle"></i>\' +
            \'<div class="content"><h5>Warning: DNS Changes</h5>\' +
            \'<p>This will remove existing MX records and replace them with Google Workspace MX records.</p></div></div>\';

        html += \'<div class="mxchanger-comparison">\';
        html += \'<div class="mxchanger-dns-panel current"><h4><i class="fas fa-inbox"></i> Current MX Records</h4>\';
        if (this.currentRecords.length === 0) {
            html += \'<p style="margin: 0; color: #999;">No MX records found</p>\';
        } else {
            this.currentRecords.forEach(function(record) {
                html += \'<div class="mxchanger-record remove"><span class="priority">\' + record.priority + \'</span>\' +
                    \'<span class="host">\' + self.escapeHtml(record.host) + \'</span></div>\';
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
            \'<div class="details"><h4>\' + this.escapeHtml(this.domain) + \'</h4>\' +
            \'<p>Restore Default cPanel Mail Configuration</p></div></div>\';

        html += \'<div class="mxchanger-warning"><i class="fas fa-exclamation-triangle"></i>\' +
            \'<div class="content"><h5>Warning: DNS Changes</h5>\' +
            \'<p>This will remove existing MX records and point mail to the local cPanel server.</p></div></div>\';

        html += \'<div class="mxchanger-comparison">\';
        html += \'<div class="mxchanger-dns-panel current"><h4><i class="fas fa-inbox"></i> Current MX Records</h4>\';
        if (this.currentRecords.length === 0) {
            html += \'<p style="margin: 0; color: #999;">No MX records found</p>\';
        } else {
            this.currentRecords.forEach(function(record) {
                html += \'<div class="mxchanger-record remove"><span class="priority">\' + record.priority + \'</span>\' +
                    \'<span class="host">\' + self.escapeHtml(record.host) + \'</span></div>\';
            });
        }
        html += \'</div><div class="mxchanger-arrow"><i class="fas fa-arrow-right"></i></div>\';
        html += \'<div class="mxchanger-dns-panel proposed local"><h4><i class="fas fa-server"></i> Local Mail Server</h4>\';
        html += \'<div class="mxchanger-record add local"><span class="priority">0</span>\' +
            \'<span class="host">\' + this.escapeHtml(this.domain) + \'</span></div>\';
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
            body: JSON.stringify({service_id: this.serviceId, domain: this.domain})
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showSuccess("Google Workspace MX records applied successfully!");
                self.showToast("success", "Google MX applied to " + self.domain);
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
            body: JSON.stringify({service_id: this.serviceId, domain: this.domain})
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                self.showSuccess("Local mail server configuration restored!");
                self.showToast("success", "Local mail restored for " + self.domain);
            } else {
                self.showError(data.message || "Failed to restore local mail");
            }
        })
        .catch(function(error) { self.showError("Network error: " + error.message); });
    },

    showSuccess: function(message) {
        document.getElementById("mxchanger-modal-body").innerHTML =
            \'<div class="mxchanger-success"><div class="icon"><i class="fas fa-check"></i></div>\' +
            \'<h4>Success!</h4><p>\' + this.escapeHtml(message) + \'<br><small style="color: #999;">DNS propagation may take up to 48 hours.</small></p></div>\';
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.closeModal()"><i class="fas fa-check"></i> Done</button>\';
    },

    showError: function(message) {
        document.getElementById("mxchanger-modal-body").innerHTML =
            \'<div class="mxchanger-error"><div class="icon"><i class="fas fa-exclamation-circle"></i></div>\' +
            \'<h4>Error</h4><p>\' + this.escapeHtml(message) + \'</p></div>\';
        document.getElementById("mxchanger-modal-footer").innerHTML =
            \'<button class="mxchanger-btn mxchanger-btn-secondary" onclick="MXChanger.closeModal()"><i class="fas fa-times"></i> Close</button>\' +
            \'<button class="mxchanger-btn mxchanger-btn-primary" onclick="MXChanger.fetchCurrentRecords()"><i class="fas fa-redo"></i> Retry</button>\';
    },

    showToast: function(type, message) {
        var toast = document.createElement("div");
        toast.className = "mxchanger-toast " + type;
        toast.innerHTML = \'<i class="fas fa-\' + (type === "success" ? "check-circle" : "exclamation-circle") + \'"></i> \' + this.escapeHtml(message);
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.style.animation = "mxchanger-toast-out 0.3s ease forwards";
            setTimeout(function() { toast.remove(); }, 300);
        }, 4000);
    },

    escapeHtml: function(text) {
        if (!text) return "";
        var div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize on page load and after delays for dynamic content
document.addEventListener("DOMContentLoaded", function() { MXChanger.init(); });
setTimeout(function() { MXChanger.init(); }, 1000);
setTimeout(function() { MXChanger.init(); }, 2500);

// Also reinitialize when tab content loads (for AJAX-loaded tabs)
var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length > 0) {
            setTimeout(function() { MXChanger.init(); }, 100);
        }
    });
});
observer.observe(document.body, { childList: true, subtree: true });
</script>
';

    return $output;
});
