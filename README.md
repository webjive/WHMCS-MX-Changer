# WHMCS Google MX Changer

A WHMCS 8.5+ addon module for automated DNS record updates to Google Workspace MX configuration through a professional web interface.

## Features

- **MX Manager Button**: Adds an "MX Manager" button to the Products/Services tab on customer profile pages
- **Two-Way Configuration**: Switch between Google Workspace MX and local cPanel mail server
- **DNS Retrieval**: Fetches current MX records using cPanel Extended API (UAPI)
- **Visual Comparison**: Displays a clear before/after comparison of DNS changes
- **MX Type Detection**: Automatically detects current configuration (Google, Local, or Custom)
- **One-Click Update**: Removes existing MX records and applies selected configuration
- **Restore Local Mail**: Reset to default cPanel mail server with a single click
- **Activity Logging**: Tracks all MX changes with admin, client, domain, action type, and status
- **Toast Notifications**: Real-time success/error notifications using WHMCS styling
- **Professional UI**: Consistent styling with WHMCS admin panel, including warning colors and success indicators

## Requirements

- WHMCS 8.5 or higher
- PHP 7.4 or higher
- cPanel/WHM hosting accounts with DNS zone access
- Admin account with module access permissions

## Installation

1. Upload the `modules/addons/mxchanger` directory to your WHMCS installation:
   ```
   /path/to/whmcs/modules/addons/mxchanger/
   ```

2. Navigate to **Setup > Addon Modules** in WHMCS admin

3. Find "Google MX Changer" and click **Activate**

4. Configure module settings:
   - **Enable Logging**: Enable/disable activity logging
   - **Require Confirmation**: Show confirmation screen before changes

5. Set access permissions for admin roles as needed

## File Structure

```
modules/addons/mxchanger/
├── mxchanger.php      # Main module file (config, activate, deactivate, output)
├── hooks.php          # WHMCS hooks for UI integration
├── ajax.php           # AJAX request handler
└── lib/
    └── DnsManager.php # DNS operations via cPanel API
```

## Usage

### Setting Google Workspace MX Records

1. Navigate to a customer profile in the WHMCS admin area
2. Click on the **Products/Services** tab
3. Find a cPanel hosting product and click the **MX Manager** button
4. Select **Set Google Workspace MX**
5. Review the current MX records and proposed Google Workspace configuration
6. Click **Apply Google MX Records** to confirm

### Restoring Local cPanel Mail

1. Navigate to a customer profile in the WHMCS admin area
2. Click on the **Products/Services** tab
3. Find a cPanel hosting product and click the **MX Manager** button
4. Select **Restore Local Mail**
5. Review the current MX records and proposed local mail configuration
6. Click **Restore Local Mail** to confirm

## MX Configurations

### Google Workspace MX Records

| Priority | Mail Server |
|----------|-------------|
| 1 | ASPMX.L.GOOGLE.COM |
| 5 | ALT1.ASPMX.L.GOOGLE.COM |
| 5 | ALT2.ASPMX.L.GOOGLE.COM |
| 10 | ALT3.ASPMX.L.GOOGLE.COM |
| 10 | ALT4.ASPMX.L.GOOGLE.COM |

### Local cPanel Mail

| Priority | Mail Server |
|----------|-------------|
| 0 | domain.com (points to local server) |

## Activity Logs

View all MX change operations under **Addons > Google MX Changer > Activity Logs**

Logs include:
- Date/Time
- Admin ID
- Client ID (linked to profile)
- Domain
- Action Type (Set Google MX / Restore Local)
- Status (Success/Failed)
- Error details if applicable

## User Interface

The module provides a professional, modern interface that includes:

- **Action Selection Screen**: Choose between Google MX or Local Mail configuration
- **MX Type Badge**: Visual indicator showing current configuration type
- **Side-by-Side Comparison**: View current records vs. proposed changes
- **Warning Messages**: Clear warnings about DNS propagation and service impact
- **Toast Notifications**: Non-intrusive success/error messages
- **Cancel/Back Navigation**: Easy return to previous screens without making changes

## Troubleshooting

### Button not appearing
- Ensure the module is activated
- Check admin role has access to the addon
- Verify the product has a valid domain configured
- The button appears on rows with a domain that matches standard patterns

### DNS update fails
- Verify cPanel credentials are correct in the hosting account
- Check the server is accessible and cPanel API is enabled
- Ensure the domain has a DNS zone on the cPanel server
- Check the WHMCS activity log for detailed error messages

### API errors
- Check server connectivity and SSL certificates
- Verify the hosting account has permission to modify DNS zones
- Ensure the cPanel account username/password are correctly stored in WHMCS

### MX type detection incorrect
- The detection checks for Google MX server patterns
- Local detection checks if MX points to domain itself or mail.domain
- Custom/Other is shown for any unrecognized configuration

## Database

The module creates a `mod_mxchanger_log` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| admin_id | INT | Admin who made the change |
| client_id | INT | Client ID |
| service_id | INT | Hosting service ID |
| domain | VARCHAR(255) | Domain name |
| old_records | TEXT | JSON of previous MX records |
| new_records | TEXT | JSON of new MX records |
| status | ENUM | success, failed, pending |
| action_type | VARCHAR(20) | google, local |
| error_message | TEXT | Error details if failed |
| created_at | TIMESTAMP | When the change was made |

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Author

WebJIVE - [https://www.web-jive.com](https://www.web-jive.com)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
