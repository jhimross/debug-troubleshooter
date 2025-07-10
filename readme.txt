=== Debugger & Troubleshooter ===
Contributors: jhimross
Tags: debug, troubleshoot, php info, developer
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.2.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Donate link: https://paypal.me/jhimross28

A WordPress plugin for debugging & troubleshooting. Safely simulate plugin deactivation, theme switching, and WP_DEBUG.

== Description ==

The "Debugger & Troubleshooter" plugin provides essential tools for WordPress site administrators to diagnose and resolve issues efficiently. It offers a dedicated section in the WordPress dashboard that displays comprehensive site health information and powerful debugging toggles.

**Key Features:**

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

== Installation ==

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

== Usage ==

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

== Frequently Asked Questions ==

**Q: How does Troubleshooting Mode work without affecting my live site?**
A: Troubleshooting Mode uses a browser cookie specific to your session. When enabled, the plugin filters WordPress functions that determine active plugins and themes, redirecting them to your simulated settings. This happens only for your browser.

**Q: Will this work if I have a caching plugin active?**
A: Yes. When Troubleshooting Mode is active, the plugin defines the `DONOTCACHEPAGE` constant, which instructs most caching plugins and hosting environments to bypass the cache for your session.

**Q: How does Live Debugging work without editing wp-config.php?**
A: The plugin uses the `plugins_loaded` hook to define the `WP_DEBUG` constants programmatically. This happens very early in the WordPress loading sequence, effectively enabling debug mode for all requests while the feature is turned on.

== Screenshots ==

1.  The main Debugger & Troubleshooter dashboard showing all feature sections.
2.  An expanded view of the Site Information card, showing detailed lists of themes and plugins.
3.  An example of the admin notice when Troubleshooting Mode is active.
4.  The Live Debugging section with the log viewer.

== Changelog ==

= 1.2.1 - 2025-07-11 =
* **Fix:** Addressed all security and code standard issues reported by the Plugin Check plugin, including escaping all output and using the `WP_Filesystem` API for file operations.
* **Fix:** Replaced the native browser `confirm()` dialog with a custom modal for a better user experience and to prevent potential browser compatibility issues.

= 1.2.0 - 2025-07-11 =
* **Feature:** Added "Live Debugging" section to safely enable/disable `WP_DEBUG` and `WP_DEBUG_LOG` from the UI without editing `wp-config.php`.
* **Feature:** Added a `debug.log` file viewer and a "Clear Log" button to the Live Debugging section.

= 1.1.1 - 2025-07-10 =
* **Fix:** Implemented cache-bypassing measures for Troubleshooting Mode. The plugin now defines the `DONOTCACHEPAGE` constant and sends no-cache headers to ensure compatibility with most caching plugins and server-side caches.

= 1.1.0 - 2025-07-09 =
* **Feature:** Site Information cards (WordPress, PHP, Database, Server, Constants) are now collapsible and closed by default for a cleaner interface.
* **Feature:** Added a "Copy to Clipboard" button to easily copy all site information for support requests or documentation.
* **Enhancement:** The "WordPress Information" card now displays a detailed list of all installed themes and plugins, along with their respective active, inactive, or network-active status.
* **Enhancement:** The theme and plugin lists within the "WordPress Information" card are now compact, showing counts by default with a "Show All" toggle to view the complete list.
* **Enhancement:** Expanded the displayed information for PHP, Server, and WordPress constants.
* **Fix:** Resolved a bug that prevented the collapsible sections from functioning correctly.

= 1.0.0 – 2025-06-25 =
* Initial release.

== Upgrade Notice ==

= 1.2.1 =
This is a recommended security and maintenance update. It addresses all issues reported by the Plugin Check plugin, including proper data escaping and use of the `WP_Filesystem` API.

= 1.2.0 =
This version adds a major new feature: "Live Debugging." You can now enable WP_DEBUG and view the debug.log file directly from the plugin's admin page without editing any files.
