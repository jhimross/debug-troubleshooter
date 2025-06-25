** Debug & Troubleshooter **

Contributors: jhimross
Tags: debug, troubleshoot, site health, php info, theme
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

A WordPress plugin for debugging & troubleshooting. Safely simulate plugin deactivation and theme switching for your session only.

== Description ==

The "Debug & Troubleshooter" plugin provides essential tools for WordPress site administrators to diagnose and resolve issues efficiently. It offers a dedicated section in the WordPress dashboard that displays comprehensive site health information, similar to the built-in Site Health feature, including PHP version, memory limit, WordPress constants, and active theme/plugin details.

**Key Features:**

* **Comprehensive Site Information:** Get a quick overview of your WordPress environment, including PHP settings, WordPress version, active theme, active plugins, and important WordPress constants.
* **Troubleshooting Mode:** Activate a unique "Troubleshooting Mode" for your current browser session. This mode allows you to:
    * **Simulate Plugin Deactivation:** Selectively "deactivate" plugins. The plugin's assets and code will be disabled for your session, mimicking actual deactivation, but the live site remains untouched for other visitors.
    * **Simulate Theme Switching:** Preview any installed theme. Your browser will render the site using the chosen theme, while the public-facing site continues to use the active theme.
* **Safe Debugging:** All troubleshooting actions are session-based using cookies, ensuring that any changes you make in Troubleshooting Mode do not impact your live website or its visitors. This makes it a safe environment for diagnosing conflicts and issues.
* **User-Friendly Interface:** An intuitive dashboard interface makes it easy to access site information and manage troubleshooting options.
* **Admin Notices:** Clear notices alert you when Troubleshooting Mode is active, ensuring you are always aware of your current debugging state.

This plugin is an invaluable tool for developers, site administrators, and anyone who needs to debug WordPress issues without risking site downtime or affecting user experience.

Installation

1.  **Download** the plugin ZIP file.
2.  **Upload** the plugin to your WordPress site:
    * Navigate to **Plugins > Add New** in your WordPress dashboard.
    * Click the "Upload Plugin" button.
    * Choose the downloaded ZIP file and click "Install Now".
3.  **Activate** the plugin through the 'Plugins' menu in WordPress.
4.  Once activated, go to **Tools > Debug & Troubleshooter** to access the plugin's features.

**Manual Installation (if needed):**

1.  **Extract** the `debug-troubleshooter.zip` file.
2.  **Upload** the `debug-troubleshooter` folder (containing `debug-troubleshooter.php`, `assets/css/admin.css`, and `assets/js/admin.js`) to the `/wp-content/plugins/` directory via FTP or your hosting's file manager.
3.  **Activate** the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

**Q: How does Troubleshooting Mode work without affecting my live site?**
A: Troubleshooting Mode uses a browser cookie specific to your session. When enabled, the plugin filters WordPress functions that determine active plugins and themes, redirecting them to your simulated settings. This happens only for your browser, while other visitors see the live, unchanged site.

**Q: Can I use this plugin on a multisite network?**
A: Yes, the plugin is designed to work with multisite. It will display network-active plugins and allow you to simulate their deactivation for your session as well.

**Q: What happens if I clear my browser cookies while in Troubleshooting Mode?**
A: Clearing your browser cookies will effectively exit Troubleshooting Mode, as the plugin relies on the `wp_debug_troubleshoot_mode` cookie to maintain your session's state. You will revert to seeing the live site's actual configuration.

**Q: Is there any performance impact when Troubleshooting Mode is active?**
A: The performance impact is minimal and only applies to the session where Troubleshooting Mode is active. The filtering mechanism is lightweight and designed not to significantly burden your server.

**Q: What information does the "Site Information" section show?**
A: It shows crucial details like your PHP version, memory limits, WordPress version, currently active theme and plugins, and various WordPress constants, which are vital for debugging and understanding your site's configuration.

== Screenshots ==

(No screenshots yet. You would typically add screenshots here demonstrating the plugin's interface.)

1.  Screenshot 1: The main Debug & Troubleshooter dashboard showing Site Information.
2.  Screenshot 2: The Troubleshooting Mode section with theme and plugin selection.
3.  Screenshot 3: An example of the admin notice when Troubleshooting Mode is active.

== Changelog ==

= 1.0.0 - 2025-06-25 =
* Initial release.
* Added comprehensive Site Information display (PHP, WP, Constants, Server).
* Implemented session-based Troubleshooting Mode for simulated theme switching.
* Implemented session-based Troubleshooting Mode for simulated plugin deactivation.
* Added AJAX handlers for mode toggling and state updates.
* Included admin notices for active troubleshooting sessions.
* Ensured compliance with WordPress.org plugin review guidelines for asset enqueuing and security.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade notice needed.
