<?php
/**
 * WHMCS MX Changer Addon Module
 *
 * @package    WHMCS
 * @author     WebJIVE
 * @copyright  Copyright (c) WebJIVE
 * @link       https://webjive.com
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Module configuration
 */
function mxchanger_config()
{
    return [
        'name' => 'MX Changer',
        'description' => 'Manage and change MX records for domains',
        'version' => '1.0.0',
        'author' => 'WebJIVE',
        'fields' => [
            'api_key' => [
                'FriendlyName' => 'API Key',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Enter your DNS provider API key',
            ],
        ],
    ];
}

/**
 * Activate the module
 */
function mxchanger_activate()
{
    return [
        'status' => 'success',
        'description' => 'MX Changer module has been activated.',
    ];
}

/**
 * Deactivate the module
 */
function mxchanger_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'MX Changer module has been deactivated.',
    ];
}

/**
 * Admin area output
 */
function mxchanger_output($vars)
{
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    
    echo '<h2>MX Changer v' . $version . '</h2>';
    echo '<p>Welcome to the MX Changer module.</p>';
    // Add your admin interface code here
}
