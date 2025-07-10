# Debugger & Troubleshooter 

A WordPress plugin for debugging & troubleshooting. Safely simulate plugin deactivation, theme switching, and WP_DEBUG.

## Description

The "Debugger & Troubleshooter" plugin provides essential tools for WordPress site administrators to diagnose and resolve issues efficiently. It offers a dedicated section in the WordPress dashboard that displays comprehensive site health information and powerful debugging toggles.

## Key Features:

* **Troubleshooting Mode:** Activate a unique, **session-based** "Troubleshooting Mode" for your current browser session. This means any changes you make are temporary and only visible to you. This mode allows you to:
    * **Simulate Plugin Deactivation:** Selectively "deactivate" plugins. The plugin's assets and code will be disabled for your session only.
    * **Simulate Theme Switching:** Preview any installed theme, while the public-facing site continues to use the active theme.
* **Live Debugging:** Safely enable `WP_DEBUG` with a single click from the admin dashboard. Errors are logged to `debug.log` without being displayed on the site, and you can view the log file directly in the plugin's interface.
* **Comprehensive Site Information:** Get a quick, organized overview of your WordPress environment in collapsible cards. This includes detailed PHP, Database, and Server information, a full list of all themes and plugins with their status, and important WordPress constants.
* **Copy to Clipboard:** A one-click button allows you to copy all the site information, making it incredibly easy to share with support forums or developers.
* **Safe Debugging & Cache Bypassing:** All troubleshooting actions are session-based. The plugin automatically attempts to bypass caching when Troubleshooting Mode is active, ensuring your changes are reflected instantly.
* **User-Friendly Interface:** An intuitive dashboard interface makes it easy to access all features.
* **Admin Notices:** Clear notices alert you when Troubleshooting Mode is active.

This plugin is an invaluable tool for developers, site administrators, and anyone who needs to debug WordPress issues without risking site downtime or affecting user experience.

## Installation

1.  **Download** the plugin ZIP file.
2.  **Upload** the plugin to your WordPress site:
    * Navigate to **Plugins > Add New** in your WordPress dashboard.
    * Click the "Upload Plugin" button.
    * Choose the downloaded ZIP file and click "Install Now".
3.  **Activate** the plugin through the 'Plugins' menu in WordPress.
4.  Once activated, go to **Tools > Debugger & Troubleshooter** to access the plugin's features.

**Manual Installation (if needed):**

1.  **Extract** the `debug-troubleshooter.zip` file.
2.  **Upload** the `debug-troubleshooter` folder to the `/wp-content/plugins/` directory via FTP or your hosting's file manager.
3.  **Activate** the plugin through the 'Plugins' menu in WordPress.

## Usage

Once the plugin is installed and activated, navigate to **Tools > Debugger & Troubleshooter** in your WordPress dashboard.

### 1. Site Information

The top section provides a comprehensive overview of your WordPress environment, organized into collapsible cards that are closed by default. Click on any card title to expand it and view the details.

### 2. Troubleshooting Mode

This session-based feature allows you to simulate theme switches and plugin deactivations without affecting your live website for other visitors.

### 3. Live Debugging

This section allows you to safely manage WordPress's debugging features.

* **Enable Live Debug:** Click this button to programmatically enable `WP_DEBUG` and `WP_DEBUG_LOG`, while keeping `WP_DEBUG_DISPLAY` off. This logs errors to `wp-content/debug.log` without showing them to visitors.
* **Debug Log Viewer:** A text area displays the contents of your `debug.log` file, allowing you to see errors as they are generated.
* **Clear Log:** Safely clear the `debug.log` file with a click.

## Frequently Asked Questions

**Q: How does Troubleshooting Mode work without affecting my live site?**
A: Troubleshooting Mode uses a browser cookie specific to your session. When enabled, the plugin filters WordPress functions that determine active plugins and themes, redirecting them to your simulated settings. This happens only for your browser.

**Q: Will this work if I have a caching plugin active?**
A: Yes. When Troubleshooting Mode is active, the plugin defines the `DONOTCACHEPAGE` constant, which instructs most caching plugins and hosting environments to bypass the cache for your session.

**Q: How does Live Debugging work without editing wp-config.php?**
A: The plugin uses the `plugins_loaded` hook to define the `WP_DEBUG` constants programmatically. This happens very early in the WordPress loading sequence, effectively enabling debug mode for all requests while the feature is turned on.

## Screenshots

1.  The main Debug & Troubleshooter dashboard showing Site Information.
![screenshot-1](https://github.com/user-attachments/assets/fb8beb25-06f9-4c5d-b19c-094520c95670)
  
2. The Troubleshooting Mode section with theme and plugin selection.
![screenshot-2](https://github.com/user-attachments/assets/f14e61ee-837b-46ff-ae1f-ffadbba2344b)

3.  An example of the admin notice when Troubleshooting Mode is active.
![screenshot-3](https://github.com/user-attachments/assets/c64e2e99-8b3b-4d5c-8ee0-3881b74361a1)

4.  The Live Debugging section with the log viewer.
<img width="1918" height="975" alt="screenshot-4" src="https://github.com/user-attachments/assets/18d6ddbc-d487-4c81-a6db-3ba34db3a0ed" />


