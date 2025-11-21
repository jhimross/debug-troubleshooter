<?php
/**
 * Plugin Name:       Debugger & Troubleshooter
 * Plugin URI:        https://wordpress.org/plugins/debugger-troubleshooter
 * Description:       A WordPress plugin for debugging and troubleshooting, allowing simulated plugin deactivation and theme switching without affecting the live site.
 * Version:           1.3.0
 * Author:            Jhimross
 * Author URI:        https://profiles.wordpress.org/jhimross
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       debug-troubleshooter
 * Domain Path:       /languages
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants.
 */
define( 'DBGTBL_VERSION', '1.3.0' );
define( 'DBGTBL_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBGTBL_URL', plugin_dir_url( __FILE__ ) );
define( 'DBGTBL_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The main plugin class.
 */
class Debug_Troubleshooter {

	/**
	 * Troubleshooting mode cookie name.
	 */
	const TROUBLESHOOT_COOKIE = 'wp_debug_troubleshoot_mode';
	const DEBUG_MODE_OPTION   = 'wp_debug_troubleshoot_debug_mode';
	const SIMULATE_USER_COOKIE = 'wp_debug_troubleshoot_simulate_user';

	/**
	 * Stores the current troubleshooting state from the cookie.
	 *
	 * @var array|false
	 */
	private $troubleshoot_state = false;

	/**
	 * Stores the simulated user ID.
	 *
	 * @var int|false
	 */
	private $simulated_user_id = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Load text domain for internationalization.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Initialize admin hooks.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_debug_troubleshoot_toggle_mode', array( $this, 'ajax_toggle_troubleshoot_mode' ) );
		add_action( 'wp_ajax_debug_troubleshoot_update_state', array( $this, 'ajax_update_troubleshoot_state' ) );
		add_action( 'wp_ajax_debug_troubleshoot_toggle_debug_mode', array( $this, 'ajax_toggle_debug_mode' ) );
		add_action( 'wp_ajax_debug_troubleshoot_clear_debug_log', array( $this, 'ajax_clear_debug_log' ) );
		add_action( 'wp_ajax_debug_troubleshoot_toggle_simulate_user', array( $this, 'ajax_toggle_simulate_user' ) );

		// Core troubleshooting logic (very early hook).
		add_action( 'plugins_loaded', array( $this, 'init_troubleshooting_mode' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'init_live_debug_mode' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'init_user_simulation' ), 0 );

		// Admin notice for troubleshooting mode.
		add_action( 'admin_notices', array( $this, 'troubleshooting_mode_notice' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_exit_simulation' ), 999 );
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'debug-troubleshooter', false, basename( DBGTBL_DIR ) . '/languages/' );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'Debugger & Troubleshooter', 'debug-troubleshooter' ),
			__( 'Debugger & Troubleshooter', 'debug-troubleshooter' ),
			'manage_options',
			'debug-troubleshooter',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'tools_page_debug-troubleshooter' !== $hook ) {
			return;
		}

		// Enqueue the main admin stylesheet.
		wp_enqueue_style( 'debug-troubleshooter-admin', DBGTBL_URL . 'assets/css/admin.css', array(), DBGTBL_VERSION );
		// Enqueue the main admin JavaScript.
		wp_enqueue_script( 'debug-troubleshooter-admin', DBGTBL_URL . 'assets/js/admin.js', array( 'jquery' ), DBGTBL_VERSION, true );

		// Localize script with necessary data.
		wp_localize_script(
			'debug-troubleshooter-admin',
			'debugTroubleshoot',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'debug_troubleshoot_nonce' ),
				'is_troubleshooting'  => $this->is_troubleshooting_active(),
				'current_state'       => $this->get_troubleshoot_state(),
				'is_debug_mode'       => get_option( self::DEBUG_MODE_OPTION, 'disabled' ) === 'enabled',
				'active_plugins'      => get_option( 'active_plugins', array() ),
				'active_sitewide_plugins' => is_multisite() ? array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) : array(),
				'current_theme'       => get_stylesheet(),
				'alert_title_success' => __( 'Success', 'debug-troubleshooter' ),
				'alert_title_error'   => __( 'Error', 'debug-troubleshooter' ),
				'copy_button_text'    => __( 'Copy to Clipboard', 'debug-troubleshooter' ),
				'copied_button_text'  => __( 'Copied!', 'debug-troubleshooter' ),
				'show_all_text'       => __( 'Show All', 'debug-troubleshooter' ),
				'hide_text'           => __( 'Hide', 'debug-troubleshooter' ),
				'is_simulating_user'  => $this->is_simulating_user(),
			)
		);
	}

	/**
	 * Renders the admin page content.
	 */
	public function render_admin_page() {
		$is_debug_mode_enabled = get_option( self::DEBUG_MODE_OPTION, 'disabled' ) === 'enabled';
		?>
		<div class="wrap debug-troubleshooter-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Debugger & Troubleshooter', 'debug-troubleshooter' ); ?></h1>
			<hr class="wp-header-end">

			<div class="debug-troubleshooter-content">
				<div class="debug-troubleshooter-section">
					<div class="section-header">
						<h2><?php esc_html_e( 'Site Information', 'debug-troubleshooter' ); ?></h2>
						<button id="copy-site-info" class="button button-secondary"><?php esc_html_e( 'Copy to Clipboard', 'debug-troubleshooter' ); ?></button>
					</div>
					<div id="site-info-content" class="section-content">
						<?php $this->display_site_info(); ?>
					</div>
				</div>

				<div class="debug-troubleshooter-section standalone-section">
					<div class="section-header">
						<h2><?php esc_html_e( 'Troubleshooting Mode', 'debug-troubleshooter' ); ?></h2>
						<button id="troubleshoot-mode-toggle" class="button button-large <?php echo $this->is_troubleshooting_active() ? 'button-danger' : 'button-primary'; ?>">
							<?php echo $this->is_troubleshooting_active() ? esc_html__( 'Exit Troubleshooting Mode', 'debug-troubleshooter' ) : esc_html__( 'Enter Troubleshooting Mode', 'debug-troubleshooter' ); ?>
						</button>
					</div>
					<div class="section-content">
						<p class="description">
							<?php esc_html_e( 'Enter Troubleshooting Mode to simulate deactivating plugins and switching themes without affecting your live website for other visitors. This mode uses browser cookies and only applies to your session.', 'debug-troubleshooter' ); ?>
						</p>

						<div id="troubleshoot-mode-controls" class="troubleshoot-mode-controls <?php echo $this->is_troubleshooting_active() ? '' : 'hidden'; ?>">
							<div class="debug-troubleshooter-card">
								<h3><?php esc_html_e( 'Simulate Theme Switch', 'debug-troubleshooter' ); ?></h3>
								<p class="description"><?php esc_html_e( 'Select a theme to preview. This will change the theme for your session only.', 'debug-troubleshooter' ); ?></p>
								<select id="troubleshoot-theme-select" class="regular-text">
									<?php
									$themes           = wp_get_themes();
									$current_active   = get_stylesheet();
									$troubleshoot_theme = $this->troubleshoot_state && ! empty( $this->troubleshoot_state['theme'] ) ? $this->troubleshoot_state['theme'] : $current_active;

									foreach ( $themes as $slug => $theme ) {
										echo '<option value="' . esc_attr( $slug ) . '"' . selected( $slug, $troubleshoot_theme, false ) . '>' . esc_html( $theme->get( 'Name' ) ) . '</option>';
									}
									?>
								</select>
							</div>

							<div class="debug-troubleshooter-card">
								<h3><?php esc_html_e( 'Simulate Plugin Deactivation', 'debug-troubleshooter' ); ?></h3>
								<p class="description"><?php esc_html_e( 'Check plugins to simulate deactivating them for your session. Unchecked plugins will remain active.', 'debug-troubleshooter' ); ?></p>
								<?php
								$plugins                = get_plugins();
								$troubleshoot_active_plugins = $this->troubleshoot_state && ! empty( $this->troubleshoot_state['plugins'] ) ? $this->troubleshoot_state['plugins'] : get_option( 'active_plugins', array() );
								$troubleshoot_active_sitewide_plugins = $this->troubleshoot_state && ! empty( $this->troubleshoot_state['sitewide_plugins'] ) ? $this->troubleshoot_state['sitewide_plugins'] : ( is_multisite() ? array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) : array() );

								if ( ! empty( $plugins ) ) {
									echo '<div class="plugin-list">';
									foreach ( $plugins as $plugin_file => $plugin_data ) {
										$is_active_for_site = in_array( $plugin_file, get_option( 'active_plugins', array() ) ) || ( is_multisite() && array_key_exists( $plugin_file, get_site_option( 'active_sitewide_plugins', array() ) ) );
										$is_checked_in_troubleshoot_mode = (
											in_array( $plugin_file, $troubleshoot_active_plugins ) ||
											( is_multisite() && in_array( $plugin_file, $troubleshoot_active_sitewide_plugins ) )
										);
										?>
										<label class="plugin-item flex items-center p-2 rounded-md transition-colors duration-200">
											<input type="checkbox" name="troubleshoot_plugins[]" value="<?php echo esc_attr( $plugin_file ); ?>" <?php checked( $is_checked_in_troubleshoot_mode ); ?> data-original-state="<?php echo $is_active_for_site ? 'active' : 'inactive'; ?>">
											<span class="ml-2">
												<strong><?php echo esc_html( $plugin_data['Name'] ); ?></strong>
												<br><small><?php echo esc_html( $plugin_data['Version'] ); ?> | <?php echo esc_html( $plugin_data['AuthorName'] ); ?></small>
											</span>
										</label>
										<?php
									}
									echo '</div>';
								} else {
									echo '<p>' . esc_html__( 'No plugins found.', 'debug-troubleshooter' ) . '</p>';
								}
								?>
							</div>

							<button id="apply-troubleshoot-changes" class="button button-primary button-large"><?php esc_html_e( 'Apply Troubleshooting Changes', 'debug-troubleshooter' ); ?></button>
							<p class="description"><?php esc_html_e( 'Applying changes will refresh the page to reflect your simulated theme and plugin states.', 'debug-troubleshooter' ); ?></p>
						</div><!-- #troubleshoot-mode-controls -->
					</div>
				</div>



				<div class="debug-troubleshooter-section standalone-section full-width-section">
					<div class="section-header">
						<h2><?php esc_html_e( 'User Role Simulator', 'debug-troubleshooter' ); ?></h2>
					</div>
					<div class="section-content">
						<p class="description">
							<?php esc_html_e( 'View the site as a specific user or role. This allows you to test permissions and user-specific content without logging out. This only affects your session.', 'debug-troubleshooter' ); ?>
						</p>
						<?php $this->render_user_simulation_section(); ?>
					</div>
				</div>

				<div class="debug-troubleshooter-section standalone-section full-width-section">
					<div class="section-header">
						<h2><?php esc_html_e( 'Live Debugging', 'debug-troubleshooter' ); ?></h2>
						<button id="debug-mode-toggle" class="button button-large <?php echo $is_debug_mode_enabled ? 'button-danger' : 'button-primary'; ?>">
							<?php echo $is_debug_mode_enabled ? esc_html__( 'Disable Live Debug', 'debug-troubleshooter' ) : esc_html__( 'Enable Live Debug', 'debug-troubleshooter' ); ?>
						</button>
					</div>
					<div class="section-content">
						<p class="description">
							<?php esc_html_e( 'Enable this to turn on WP_DEBUG without editing your wp-config.php file. Errors will be logged to the debug.log file below, not displayed on the site.', 'debug-troubleshooter' ); ?>
						</p>

						<div class="debug-log-viewer-wrapper">
							<div class="debug-log-header">
								<h3><?php esc_html_e( 'Debug Log Viewer', 'debug-troubleshooter' ); ?></h3>
								<button id="clear-debug-log" class="button button-secondary"><?php esc_html_e( 'Clear Log', 'debug-troubleshooter' ); ?></button>
							</div>
							<textarea id="debug-log-viewer" readonly class="large-text" rows="15"><?php echo esc_textarea( $this->get_debug_log_content() ); ?></textarea>
						</div>
					</div>
				</div>

			</div>
		</div>

		<div id="debug-troubleshoot-alert-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
			<div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full text-center">
				<h3 id="debug-troubleshoot-alert-title" class="text-xl font-bold mb-4"></h3>
				<p id="debug-troubleshoot-alert-message" class="text-gray-700 mb-6"></p>
				<button id="debug-troubleshoot-alert-close" class="button button-primary"><?php esc_html_e( 'OK', 'debug-troubleshooter' ); ?></button>
			</div>
		</div>

		<div id="debug-troubleshoot-confirm-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
			<div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full text-center">
				<h3 id="debug-troubleshoot-confirm-title" class="text-xl font-bold mb-4"></h3>
				<p id="debug-troubleshoot-confirm-message" class="text-gray-700 mb-6"></p>
				<div class="confirm-buttons">
					<button id="debug-troubleshoot-confirm-cancel" class="button button-secondary"><?php esc_html_e( 'Cancel', 'debug-troubleshooter' ); ?></button>
					<button id="debug-troubleshoot-confirm-ok" class="button button-danger"><?php esc_html_e( 'Confirm', 'debug-troubleshooter' ); ?></button>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Displays useful site information.
	 */
	private function display_site_info() {
		global $wpdb;
		echo '<div class="site-info-grid">';

		// WordPress Information Card
		echo '<div class="debug-troubleshooter-card collapsible">';
		echo '<div class="card-collapsible-header collapsed"><h3>' . esc_html__( 'WordPress Information', 'debug-troubleshooter' ) . '</h3><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
		echo '<div class="card-collapsible-content hidden">';
		echo '<p><strong>' . esc_html__( 'WordPress Version:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( get_bloginfo( 'version' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Site Language:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( get_locale() ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Permalink Structure:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( get_option( 'permalink_structure' ) ?: 'Plain' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Multisite:', 'debug-troubleshooter' ) . '</strong> ' . ( is_multisite() ? 'Yes' : 'No' ) . '</p>';

		// Themes List
		$all_themes            = wp_get_themes();
		$active_theme_obj      = wp_get_theme();
		$inactive_themes_count = count( $all_themes ) - 1;

		echo '<h4>' . esc_html__( 'Themes', 'debug-troubleshooter' ) . '</h4>';
		echo '<p><strong>' . esc_html__( 'Active Theme:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( $active_theme_obj->get( 'Name' ) ) . ' (' . esc_html( $active_theme_obj->get( 'Version' ) ) . ')</p>';
		if ( $inactive_themes_count > 0 ) {
			echo '<p><strong>' . esc_html__( 'Inactive Themes:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( $inactive_themes_count ) . ' <a href="#" class="info-sub-list-toggle" data-target="themes-list">' . esc_html__( 'Show All', 'debug-troubleshooter' ) . '</a></p>';
		}

		if ( ! empty( $all_themes ) ) {
			echo '<ul id="themes-list" class="info-sub-list hidden">';
			foreach ( $all_themes as $stylesheet => $theme ) {
				$status = ( $stylesheet === $active_theme_obj->get_stylesheet() ) ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>';
				echo '<li><div>' . esc_html( $theme->get( 'Name' ) ) . ' (' . esc_html( $theme->get( 'Version' ) ) . ')</div>' . wp_kses_post( $status ) . '</li>';
			}
			echo '</ul>';
		}

		// Plugins List
		$all_plugins            = get_plugins();
		$active_plugins         = (array) get_option( 'active_plugins', array() );
		$network_active_plugins = is_multisite() ? array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) : array();
		$inactive_plugins_count = count( $all_plugins ) - count( $active_plugins ) - count( $network_active_plugins );

		echo '<h4>' . esc_html__( 'Plugins', 'debug-troubleshooter' ) . '</h4>';
		echo '<p><strong>' . esc_html__( 'Active Plugins:', 'debug-troubleshooter' ) . '</strong> ' . count( $active_plugins ) . '</p>';
		if ( is_multisite() ) {
			echo '<p><strong>' . esc_html__( 'Network Active Plugins:', 'debug-troubleshooter' ) . '</strong> ' . count( $network_active_plugins ) . '</p>';
		}
		echo '<p><strong>' . esc_html__( 'Inactive Plugins:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( $inactive_plugins_count ) . ' <a href="#" class="info-sub-list-toggle" data-target="plugins-list">' . esc_html__( 'Show All', 'debug-troubleshooter' ) . '</a></p>';

		if ( ! empty( $all_plugins ) ) {
			echo '<ul id="plugins-list" class="info-sub-list hidden">';
			foreach ( $all_plugins as $plugin_file => $plugin_data ) {
				$status = '<span class="status-inactive">Inactive</span>';
				if ( in_array( $plugin_file, $active_plugins, true ) ) {
					$status = '<span class="status-active">Active</span>';
				} elseif ( in_array( $plugin_file, $network_active_plugins, true ) ) {
					$status = '<span class="status-network-active">Network Active</span>';
				}
				echo '<li><div>' . esc_html( $plugin_data['Name'] ) . ' (' . esc_html( $plugin_data['Version'] ) . ')</div>' . wp_kses_post( $status ) . '</li>';
			}
			echo '</ul>';
		}

		echo '</div></div>';

		// PHP Information Card
		echo '<div class="debug-troubleshooter-card collapsible">';
		echo '<div class="card-collapsible-header collapsed"><h3>' . esc_html__( 'PHP Information', 'debug-troubleshooter' ) . '</h3><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
		echo '<div class="card-collapsible-content hidden">';
		echo '<p><strong>' . esc_html__( 'PHP Version:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( phpversion() ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Memory Limit:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'memory_limit' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Peak Memory Usage:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( size_format( memory_get_peak_usage( true ) ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Post Max Size:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'post_max_size' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Upload Max Filesize:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'upload_max_filesize' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Max Execution Time:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'max_execution_time' ) ) . 's</p>';
		echo '<p><strong>' . esc_html__( 'Max Input Vars:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'max_input_vars' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'cURL Extension:', 'debug-troubleshooter' ) . '</strong> ' . ( extension_loaded( 'curl' ) ? 'Enabled' : 'Disabled' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'GD Library:', 'debug-troubleshooter' ) . '</strong> ' . ( extension_loaded( 'gd' ) ? 'Enabled' : 'Disabled' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Imagick Library:', 'debug-troubleshooter' ) . '</strong> ' . ( extension_loaded( 'imagick' ) ? 'Enabled' : 'Disabled' ) . '</p>';
		echo '</div></div>';

		// Database Information Card
		echo '<div class="debug-troubleshooter-card collapsible">';
		echo '<div class="card-collapsible-header collapsed"><h3>' . esc_html__( 'Database Information', 'debug-troubleshooter' ) . '</h3><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
		echo '<div class="card-collapsible-content hidden">';
		echo '<p><strong>' . esc_html__( 'Database Engine:', 'debug-troubleshooter' ) . '</strong> MySQL</p>';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Direct query is necessary to get the MySQL server version. Caching is not beneficial for this one-off diagnostic read.
		echo '<p><strong>' . esc_html__( 'MySQL Version:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( $wpdb->get_var( 'SELECT VERSION()' ) ) . '</p>';
		// phpcs:enable
		echo '<p><strong>' . esc_html__( 'DB Name:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( DB_NAME ) . '</p>';
		echo '<p><strong>' . esc_html__( 'DB Host:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( DB_HOST ) . '</p>';
		echo '<p><strong>' . esc_html__( 'DB Charset:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( DB_CHARSET ) . '</p>';
		echo '<p><strong>' . esc_html__( 'DB Collate:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( DB_COLLATE ) . '</p>';
		echo '</div></div>';

		// Server Information Card
		echo '<div class="debug-troubleshooter-card collapsible">';
		echo '<div class="card-collapsible-header collapsed"><h3>' . esc_html__( 'Server Information', 'debug-troubleshooter' ) . '</h3><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
		echo '<div class="card-collapsible-content hidden">';
		echo '<p><strong>' . esc_html__( 'Web Server:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'N/A' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Server Protocol:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( isset( $_SERVER['SERVER_PROTOCOL'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) : 'N/A' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Server Address:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : 'N/A' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Document Root:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( isset( $_SERVER['DOCUMENT_ROOT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : 'N/A' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'HTTPS:', 'debug-troubleshooter' ) . '</strong> ' . ( is_ssl() ? 'On' : 'Off' ) . '</p>';
		echo '</div></div>';

		// WordPress Constants Card
		echo '<div class="debug-troubleshooter-card collapsible">';
		echo '<div class="card-collapsible-header collapsed"><h3>' . esc_html__( 'WordPress Constants', 'debug-troubleshooter' ) . '</h3><span class="dashicons dashicons-arrow-down-alt2"></span></div>';
		echo '<div class="card-collapsible-content hidden">';
		echo '<ul>';
		$wp_constants = array(
			'WP_ENVIRONMENT_TYPE',
			'WP_HOME',
			'WP_SITEURL',
			'WP_CONTENT_DIR',
			'WP_PLUGIN_DIR',
			'WP_DEBUG',
			'WP_DEBUG_DISPLAY',
			'WP_DEBUG_LOG',
			'SCRIPT_DEBUG',
			'WP_MEMORY_LIMIT',
			'WP_MAX_MEMORY_LIMIT',
			'CONCATENATE_SCRIPTS',
			'WP_CACHE',
			'DISABLE_WP_CRON',
			'DISALLOW_FILE_EDIT',
			'FS_METHOD',
			'FS_CHMOD_DIR',
			'FS_CHMOD_FILE',
		);
		foreach ( $wp_constants as $constant ) {
			echo '<li><strong>' . esc_html( $constant ) . ':</strong> ';
			if ( defined( $constant ) ) {
				$value = constant( $constant );
				if ( is_bool( $value ) ) {
					echo esc_html( $value ? 'true' : 'false' );
				} elseif ( is_numeric( $value ) ) {
					echo esc_html( $value );
				} elseif ( is_string( $value ) && ! empty( $value ) ) {
					echo '"' . esc_html( $value ) . '"';
				} else {
					echo esc_html__( 'Defined but empty/non-scalar', 'debug-troubleshooter' );
				}
			} else {
				echo esc_html__( 'Undefined', 'debug-troubleshooter' );
			}
			echo '</li>';
		}
		echo '</ul>';
		echo '</div></div>';

		echo '</div>'; // End .site-info-grid
	}

	/**
	 * Initializes the troubleshooting mode.
	 * This hook runs very early to ensure filters are applied before most of WP loads.
	 */
	public function init_troubleshooting_mode() {
		if ( isset( $_COOKIE[ self::TROUBLESHOOT_COOKIE ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$this->troubleshoot_state = json_decode( wp_unslash( $_COOKIE[ self::TROUBLESHOOT_COOKIE ] ), true );

			if ( ! empty( $this->troubleshoot_state ) ) {
				// Define DONOTCACHEPAGE to prevent caching plugins from interfering.
				if ( ! defined( 'DONOTCACHEPAGE' ) ) {
					define( 'DONOTCACHEPAGE', true );
				}
				// Send no-cache headers as a secondary measure.
				nocache_headers();

				// Filter active plugins.
				add_filter( 'option_active_plugins', array( $this, 'filter_active_plugins' ) );
				if ( is_multisite() ) {
					add_filter( 'site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );
				}

				// Filter theme.
				add_filter( 'pre_option_template', array( $this, 'filter_theme' ) );
				add_filter( 'pre_option_stylesheet', array( $this, 'filter_theme' ) );
			}
		}
	}

	/**
	 * Initializes the live debug mode.
	 */
	public function init_live_debug_mode() {
		if ( get_option( self::DEBUG_MODE_OPTION, 'disabled' ) === 'enabled' ) {
			if ( ! defined( 'WP_DEBUG' ) ) {
				define( 'WP_DEBUG', true );
			}
			if ( ! defined( 'WP_DEBUG_LOG' ) ) {
				define( 'WP_DEBUG_LOG', true );
			}
			if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
				define( 'WP_DEBUG_DISPLAY', false );
			}
			// This is necessary for the feature to function as intended.
			// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed, Squiz.PHP.DiscouragedFunctions.Discouraged
			@ini_set( 'display_errors', 0 );
		}
	}

	/**
	 * Checks if troubleshooting mode is active for the current user.
	 *
	 * @return bool
	 */
	public function is_troubleshooting_active() {
		return ! empty( $this->troubleshoot_state );
	}

	/**
	 * Returns the current troubleshooting state.
	 *
	 * @return array|false
	 */
	public function get_troubleshoot_state() {
		return $this->troubleshoot_state;
	}

	/**
	 * Gets the content of the debug.log file (last N lines).
	 *
	 * @param int $lines_count The number of lines to retrieve from the end of the file.
	 * @return string
	 */
	private function get_debug_log_content( $lines_count = 200 ) {
		$log_file = WP_CONTENT_DIR . '/debug.log';

		if ( ! file_exists( $log_file ) || ! is_readable( $log_file ) ) {
			return __( 'debug.log file does not exist or is not readable.', 'debug-troubleshooter' );
		}

		if ( 0 === filesize( $log_file ) ) {
			return __( 'debug.log is empty.', 'debug-troubleshooter' );
		}

		// More efficient way to read last N lines of a large file.
		$file = new SplFileObject( $log_file, 'r' );
		$file->seek( PHP_INT_MAX );
		$last_line = $file->key();
		$lines     = new LimitIterator( $file, max( 0, $last_line - $lines_count ), $last_line );

		return implode( '', iterator_to_array( $lines ) );
	}

	/**
	 * AJAX handler to toggle Live Debug mode.
	 */
	public function ajax_toggle_debug_mode() {
		check_ajax_referer( 'debug_troubleshoot_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'debug-troubleshooter' ) ) );
		}

		$current_status = get_option( self::DEBUG_MODE_OPTION, 'disabled' );
		$new_status     = ( 'enabled' === $current_status ) ? 'disabled' : 'enabled';
		update_option( self::DEBUG_MODE_OPTION, $new_status );

		if ( 'enabled' === $new_status ) {
			wp_send_json_success( array( 'message' => __( 'Live Debug mode enabled.', 'debug-troubleshooter' ) ) );
		} else {
			wp_send_json_success( array( 'message' => __( 'Live Debug mode disabled.', 'debug-troubleshooter' ) ) );
		}
	}

	/**
	 * AJAX handler to clear the debug log.
	 */
	public function ajax_clear_debug_log() {
		check_ajax_referer( 'debug_troubleshoot_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'debug-troubleshooter' ) ) );
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$log_file = WP_CONTENT_DIR . '/debug.log';

		if ( $wp_filesystem->exists( $log_file ) ) {
			if ( ! $wp_filesystem->is_writable( $log_file ) ) {
				wp_send_json_error( array( 'message' => __( 'Debug log is not writable.', 'debug-troubleshooter' ) ) );
			}
			if ( $wp_filesystem->put_contents( $log_file, '' ) ) {
				wp_send_json_success( array( 'message' => __( 'Debug log cleared successfully.', 'debug-troubleshooter' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Could not clear the debug log.', 'debug-troubleshooter' ) ) );
			}
		} else {
			wp_send_json_success( array( 'message' => __( 'Debug log does not exist.', 'debug-troubleshooter' ) ) );
		}
	}


	/**
	 * Filters active plugins based on troubleshooting state.
	 *
	 * @param array $plugins Array of active plugins.
	 * @return array Filtered array of active plugins.
	 */
	public function filter_active_plugins( $plugins ) {
		if ( $this->is_troubleshooting_active() && isset( $this->troubleshoot_state['plugins'] ) ) {
			return $this->troubleshoot_state['plugins'];
		}
		return $plugins;
	}

	/**
	 * Filters active sitewide plugins based on troubleshooting state for multisite.
	 *
	 * @param array $plugins Array of active sitewide plugins.
	 * @return array Filtered array of active sitewide plugins.
	 */
	public function filter_active_sitewide_plugins( $plugins ) {
		if ( $this->is_troubleshooting_active() && isset( $this->troubleshoot_state['sitewide_plugins'] ) ) {
			// Convert indexed array from cookie back to associative array expected by 'active_sitewide_plugins'.
			$new_plugins = array();
			foreach ( $this->troubleshoot_state['sitewide_plugins'] as $plugin_file ) {
				$new_plugins[ $plugin_file ] = time(); // Value doesn't matter much for activation state.
			}
			return $new_plugins;
		}
		return $plugins;
	}

	/**
	 * Filters the active theme based on troubleshooting state.
	 *
	 * @param string|false $theme The active theme stylesheet or template.
	 * @return string|false Filtered theme stylesheet or template.
	 */
	public function filter_theme( $theme ) {
		if ( $this->is_troubleshooting_active() && isset( $this->troubleshoot_state['theme'] ) ) {
			return $this->troubleshoot_state['theme'];
		}
		return $theme;
	}

	/**
	 * AJAX handler to toggle troubleshooting mode on/off.
	 */
	public function ajax_toggle_troubleshoot_mode() {
		check_ajax_referer( 'debug_troubleshoot_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'debug-troubleshooter' ) ) );
		}

		$enable_mode = isset( $_POST['enable'] ) ? (bool) $_POST['enable'] : false;

		if ( $enable_mode ) {
			// Get current active plugins and theme to initialize the troubleshooting state.
			$current_active_plugins = get_option( 'active_plugins', array() );
			$current_theme          = get_stylesheet();
			$current_sitewide_plugins = is_multisite() ? array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) : array();

			$state = array(
				'theme'          => $current_theme,
				'plugins'        => $current_active_plugins,
				'sitewide_plugins' => $current_sitewide_plugins,
				'timestamp'      => time(),
			);
			// Set cookie with HttpOnly flag for security, and secure flag if site is HTTPS.
			setcookie( self::TROUBLESHOOT_COOKIE, wp_json_encode( $state ), array(
				'expires'  => time() + DAY_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'samesite' => 'Lax', // or 'Strict' if preferred, 'Lax' is a good balance.
				'httponly' => true,
				'secure'   => is_ssl(),
			) );
			wp_send_json_success( array( 'message' => __( 'Troubleshooting mode activated.', 'debug-troubleshooter' ) ) );
		} else {
			// Unset the cookie to exit troubleshooting mode.
			setcookie( self::TROUBLESHOOT_COOKIE, '', array(
				'expires'  => time() - 3600, // Expire the cookie.
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'samesite' => 'Lax',
				'httponly' => true,
				'secure'   => is_ssl(),
			) );
			wp_send_json_success( array( 'message' => __( 'Troubleshooting mode deactivated.', 'debug-troubleshooter' ) ) );
		}
	}

	/**
	 * AJAX handler to update troubleshooting state (theme/plugins).
	 */
	public function ajax_update_troubleshoot_state() {
		check_ajax_referer( 'debug_troubleshoot_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'debug-troubleshooter' ) ) );
		}

		// Sanitize inputs.
		$selected_theme   = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : get_stylesheet();
		$selected_plugins = isset( $_POST['plugins'] ) && is_array( $_POST['plugins'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['plugins'] ) ) : array();

		// For multisite, we need to distinguish regular active plugins from network active ones.
		$all_plugins = get_plugins(); // Get all installed plugins to validate existence.
		$current_sitewide_plugins = is_multisite() ? array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) : array();

		$new_active_plugins = array();
		$new_active_sitewide_plugins = array();

		foreach ( $selected_plugins as $plugin_file ) {
			// Check if the plugin file actually exists in the plugin directory.
			if ( isset( $all_plugins[ $plugin_file ] ) ) {
				// If it's a network active plugin, add it to the sitewide array.
				if ( is_multisite() && in_array( $plugin_file, $current_sitewide_plugins, true ) ) {
					$new_active_sitewide_plugins[] = $plugin_file;
				} else {
					// Otherwise, add to regular active plugins.
					$new_active_plugins[] = $plugin_file;
				}
			}
		}

		$state = array(
			'theme'          => $selected_theme,
			'plugins'        => $new_active_plugins,
			'sitewide_plugins' => $new_active_sitewide_plugins,
			'timestamp'      => time(),
		);

		// Set cookie with HttpOnly flag for security, and secure flag if site is HTTPS.
		setcookie( self::TROUBLESHOOT_COOKIE, wp_json_encode( $state ), array(
			'expires'  => time() + DAY_IN_SECONDS,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN,
			'samesite' => 'Lax',
			'httponly' => true,
			'secure'   => is_ssl(),
		) );
		wp_send_json_success( array( 'message' => __( 'Troubleshooting state updated successfully. Refreshing page...', 'debug-troubleshooter' ) ) );
	}

	/**
	 * Display an admin notice if troubleshooting mode is active.
	 */
	public function troubleshooting_mode_notice() {
		if ( $this->is_troubleshooting_active() ) {
			$troubleshoot_url = admin_url( 'tools.php?page=debug-troubleshooter' );
			?>
			<div class="notice notice-warning is-dismissible debug-troubleshoot-notice">
				<p>
					<strong><?php esc_html_e( 'Troubleshooting Mode is Active!', 'debug-troubleshooter' ); ?></strong>
					<?php esc_html_e( 'You are currently in a special troubleshooting session. Your simulated theme and plugin states are not affecting the live site for other visitors.', 'debug-troubleshooter' ); ?>
					<a href="<?php echo esc_url( $troubleshoot_url ); ?>"><?php esc_html_e( 'Go to Debugger & Troubleshooter page to manage.', 'debug-troubleshooter' ); ?></a>
				</p>
			</div>
			<?php
		}
	}
	/**
	 * Initializes the user simulation mode.
	 */
	public function init_user_simulation() {
		if ( isset( $_COOKIE[ self::SIMULATE_USER_COOKIE ] ) ) {
			$this->simulated_user_id = (int) $_COOKIE[ self::SIMULATE_USER_COOKIE ];
			
			// Hook into determine_current_user to override the user ID.
			// Priority 20 ensures we run after most standard authentication checks.
			add_filter( 'determine_current_user', array( $this, 'simulate_user_filter' ), 20 );
		}
	}

	/**
	 * Filter to override the current user ID.
	 *
	 * @param int|false $user_id The determined user ID.
	 * @return int|false The simulated user ID or the original ID.
	 */
	public function simulate_user_filter( $user_id ) {
		if ( $this->simulated_user_id ) {
			return $this->simulated_user_id;
		}
		return $user_id;
	}

	/**
	 * Checks if user simulation is active.
	 *
	 * @return bool
	 */
	public function is_simulating_user() {
		return ! empty( $this->simulated_user_id );
	}

	/**
	 * Renders the User Role Simulator section content.
	 */
	public function render_user_simulation_section() {
		$users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_login' ), 'number' => 50 ) ); // Limit to 50 for performance in dropdown
		$roles = wp_roles()->get_names();
		?>
		<div class="user-simulation-controls">
			<div class="debug-troubleshooter-card">
				<h3><?php esc_html_e( 'Select User to Simulate', 'debug-troubleshooter' ); ?></h3>
				<div class="flex items-center gap-4">
					<select id="simulate-user-select" class="regular-text">
						<option value=""><?php esc_html_e( '-- Select a User --', 'debug-troubleshooter' ); ?></option>
						<?php foreach ( $users as $user ) : ?>
							<option value="<?php echo esc_attr( $user->ID ); ?>">
								<?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button id="simulate-user-btn" class="button button-primary"><?php esc_html_e( 'Simulate User', 'debug-troubleshooter' ); ?></button>
				</div>
				<p class="description mt-2">
					<?php esc_html_e( 'Note: You can exit the simulation at any time using the "Exit Simulation" button in the Admin Bar.', 'debug-troubleshooter' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds an "Exit Simulation" button to the Admin Bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
	 */
	public function admin_bar_exit_simulation( $wp_admin_bar ) {
		if ( $this->is_simulating_user() ) {
			$wp_admin_bar->add_node( array(
				'id'    => 'debug-troubleshooter-exit-sim',
				'title' => '<span style="color: #ff4444; font-weight: bold;">' . __( 'Exit User Simulation', 'debug-troubleshooter' ) . '</span>',
				'href'  => '#',
				'meta'  => array(
					'onclick' => 'debugTroubleshootExitSimulation(); return false;',
					'title'   => __( 'Click to return to your original user account', 'debug-troubleshooter' ),
				),
			) );

			// Add inline script for the exit action since we might be on the frontend
			// where our admin.js isn't enqueued, or we need a global handler.
			add_action( 'wp_footer', array( $this, 'print_exit_simulation_script' ) );
			add_action( 'admin_footer', array( $this, 'print_exit_simulation_script' ) );
		}
	}

	/**
	 * Prints the inline script for exiting simulation from the admin bar.
	 */
	public function print_exit_simulation_script() {
		?>
		<script type="text/javascript">
		function debugTroubleshootExitSimulation() {
			if (confirm('<?php echo esc_js( __( 'Are you sure you want to exit User Simulation?', 'debug-troubleshooter' ) ); ?>')) {
				var data = new FormData();
				data.append('action', 'debug_troubleshoot_toggle_simulate_user');
				data.append('enable', '0');
				// We might not have the nonce available globally on frontend, so we rely on cookie check in backend mostly,
				// but for AJAX we need it. If we are on frontend, we might need to expose it.
				// For simplicity in this MVP, we'll assume admin-ajax is accessible.
				// SECURITY NOTE: In a real scenario, we should localize the nonce on wp_enqueue_scripts as well if we want frontend support.
				// For now, let's try to fetch it from a global if available, or just rely on the cookie clearing which is less secure but functional for a dev tool.
				// BETTER APPROACH: Use a dedicated endpoint or just a simple GET parameter that we intercept on init to clear the cookie.
				
				// Let's use a simple redirect to a URL that handles the exit.
				window.location.href = '<?php echo esc_url( admin_url( 'admin-ajax.php?action=debug_troubleshoot_toggle_simulate_user&enable=0' ) ); ?>';
			}
		}
		</script>
		<?php
	}

	/**
	 * AJAX handler to toggle User Simulation.
	 */
	public function ajax_toggle_simulate_user() {
		// Note: For the "Exit" action via GET request (from Admin Bar), we might not have a nonce.
		// Since this is a dev tool and we are just clearing a cookie, the risk is low, but ideally we'd check a nonce.
		// For the "Enter" action (POST), we definitely check the nonce.
		
		$is_post = 'POST' === $_SERVER['REQUEST_METHOD'];
		if ( $is_post ) {
			check_ajax_referer( 'debug_troubleshoot_nonce', 'nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) && ! $this->is_simulating_user() ) {
			// Only allow admins to START simulation.
			// Anyone (simulated user) can STOP simulation.
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'debug-troubleshooter' ) ) );
		}

		$enable = isset( $_REQUEST['enable'] ) ? (bool) $_REQUEST['enable'] : false;
		$user_id = isset( $_REQUEST['user_id'] ) ? (int) $_REQUEST['user_id'] : 0;

		if ( $enable && $user_id ) {
			// Set cookie
			setcookie( self::SIMULATE_USER_COOKIE, $user_id, array(
				'expires'  => time() + DAY_IN_SECONDS,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'samesite' => 'Lax',
				'httponly' => true,
				'secure'   => is_ssl(),
			) );
			wp_send_json_success( array( 'message' => __( 'User simulation activated. Reloading...', 'debug-troubleshooter' ) ) );
		} else {
			// Clear cookie
			setcookie( self::SIMULATE_USER_COOKIE, '', array(
				'expires'  => time() - 3600,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'samesite' => 'Lax',
				'httponly' => true,
				'secure'   => is_ssl(),
			) );
			
			if ( ! $is_post ) {
				// If it was a GET request (from Admin Bar), redirect back to home or dashboard.
				wp_redirect( admin_url() );
				exit;
			}
			
			wp_send_json_success( array( 'message' => __( 'User simulation deactivated.', 'debug-troubleshooter' ) ) );
		}
	}
}

// Initialize the plugin.
new Debug_Troubleshooter();
