=== Debugger & Troubleshooter ===
Contributors: jhimross
Tags: debug, troubleshoot, site health, php info, theme
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Donate link: https://paypal.me/jhimross28

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

== Installation ==

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

== Usage ==

Once the plugin is installed and activated, navigate to **Tools > Debug & Troubleshooter** in your WordPress dashboard.

### 1. Site Information

The top section provides a quick overview of your WordPress environment:

* **PHP Information:** Displays your PHP version and memory limit.
* **WordPress Information:** Shows your WordPress version, active theme, and a list of currently active plugins.
* **WordPress Constants:** Lists important WordPress configuration constants and their values.
* **Server Information:** Provides basic details about your web server.

This section is for informational purposes only and does not allow for direct modifications.

### 2. Troubleshooting Mode

This powerful feature allows you to simulate theme switches and plugin deactivations for your current browser session without affecting your live website for other visitors.

**Entering Troubleshooting Mode:**

1.  Locate the "Troubleshooting Mode" section.
2.  Click the **"Enter Troubleshooting Mode"** button.
3.  The page will reload, and a yellow admin notice will appear at the top of your dashboard, indicating that Troubleshooting Mode is active. This confirms that your session is now isolated.

**Simulating Theme Switch:**

1.  While in Troubleshooting Mode, use the **"Simulate Theme Switch"** dropdown.
2.  Select any installed theme from the list. This theme will be active for your session only.

**Simulating Plugin Deactivation:**

1.  In the **"Simulate Plugin Deactivation"** section, you'll see a list of all your installed plugins.
2.  By default, plugins currently active on your live site will be checked.
3.  To simulate deactivating a plugin for your session, simply **uncheck** its box. Plugins that remain checked will be active in your troubleshooting session.

**Applying Troubleshooting Changes:**

1.  After making your desired selections for both theme and plugins, click the **"Apply Troubleshooting Changes"** button.
2.  The page will reload, applying your simulated theme and plugin states to your current browser session.
3.  You can now navigate to the front-end of your website to test for conflicts or issues with the new configuration.

**Exiting Troubleshooting Mode:**

1.  To end your troubleshooting session and revert your browser to seeing the live site's actual configuration, return to the **Tools > Debug & Troubleshooter** page.
2.  Click the **"Exit Troubleshooting Mode"** button.
3.  The page will reload, and the admin notice will disappear, confirming you have exited the mode.


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

1.  The main Debug & Troubleshooter dashboard showing Site Information.
![screenshot-1](https://github.com/user-attachments/assets/fb8beb25-06f9-4c5d-b19c-094520c95670)
  
2. The Troubleshooting Mode section with theme and plugin selection.
![screenshot-2](https://github.com/user-attachments/assets/f14e61ee-837b-46ff-ae1f-ffadbba2344b)

3.  Screenshot 3: An example of the admin notice when Troubleshooting Mode is active.
![screenshot-3](https://github.com/user-attachments/assets/c64e2e99-8b3b-4d5c-8ee0-3881b74361a1)


== Changelog ==

= 1.0.0 – 2025-06-25 =
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
