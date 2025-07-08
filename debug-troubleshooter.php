<?php
/**
 * Plugin Name:       Debugger & Troubleshooter
 * Plugin URI:        https://wordpress.org/plugins/debugger-troubleshooter
 * Description:       A WordPress plugin for debugging and troubleshooting, allowing simulated plugin deactivation and theme switching without affecting the live site.
 * Version:           1.0.0
 * Author:            Jhimross
 * Author URI:        https://jhimross.com
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
define( 'DBGTBL_VERSION', '1.0.0' );
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

	/**
	 * Stores the current troubleshooting state from the cookie.
	 *
	 * @var array|false
	 */
	private $troubleshoot_state = false;

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

		// Core troubleshooting logic (very early hook).
		add_action( 'plugins_loaded', array( $this, 'init_troubleshooting_mode' ), 0 );

		// Admin notice for troubleshooting mode.
		add_action( 'admin_notices', array( $this, 'troubleshooting_mode_notice' ) );
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
				'active_plugins'      => get_option( 'active_plugins', array() ),
				'active_sitewide_plugins' => is_multisite() ? array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) : array(),
				'current_theme'       => get_stylesheet(),
				'alert_title_success' => __( 'Success', 'debug-troubleshooter' ),
				'alert_title_error'   => __( 'Error', 'debug-troubleshooter' ),
			)
		);
	}

	/**
	 * Renders the admin page content.
	 */
	public function render_admin_page() {
		?>
		<div class="wrap debug-troubleshooter-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Debugger & Troubleshooter', 'debug-troubleshooter' ); ?></h1>
			<hr class="wp-header-end">

			<div class="debug-troubleshooter-content">
				<div class="debug-troubleshooter-section">
					<h2><?php esc_html_e( 'Site Information', 'debug-troubleshooter' ); ?></h2>
					<?php $this->display_site_info(); ?>
				</div>

				<div class="debug-troubleshooter-section">
					<h2 class="flex justify-between items-center">
						<span><?php esc_html_e( 'Troubleshooting Mode', 'debug-troubleshooter' ); ?></span>
						<button id="troubleshoot-mode-toggle" class="button button-large <?php echo $this->is_troubleshooting_active() ? 'button-danger' : 'button-primary'; ?>">
							<?php echo $this->is_troubleshooting_active() ? esc_html__( 'Exit Troubleshooting Mode', 'debug-troubleshooter' ) : esc_html__( 'Enter Troubleshooting Mode', 'debug-troubleshooter' ); ?>
						</button>
					</h2>
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
		</div>

		<div id="debug-troubleshoot-alert-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
			<div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full text-center">
				<h3 id="debug-troubleshoot-alert-title" class="text-xl font-bold mb-4"></h3>
				<p id="debug-troubleshoot-alert-message" class="text-gray-700 mb-6"></p>
				<button id="debug-troubleshoot-alert-close" class="button button-primary"><?php esc_html_e( 'OK', 'debug-troubleshooter' ); ?></button>
			</div>
		</div>

		<?php
	}

	/**
	 * Displays useful site information.
	 */
	private function display_site_info() {
		echo '<div class="debug-troubleshooter-card">';
		echo '<h3>' . esc_html__( 'PHP Information', 'debug-troubleshooter' ) . '</h3>';
		echo '<p><strong>' . esc_html__( 'PHP Version:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( phpversion() ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Memory Limit:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'memory_limit' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Post Max Size:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'post_max_size' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Upload Max Filesize:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'upload_max_filesize' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Max Execution Time:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'max_execution_time' ) ) . 's</p>';
		echo '<p><strong>' . esc_html__( 'Max Input Time:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'max_input_time' ) ) . 's</p>';
		echo '<p><strong>' . esc_html__( 'Default Charset:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'default_charset' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Display Errors:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'display_errors' ) ? 'On' : 'Off' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Log Errors:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'log_errors' ) ? 'On' : 'Off' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Error Log File:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'error_log' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Open BaseDir:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( ini_get( 'open_basedir' ) ? ini_get( 'open_basedir' ) : 'Not Set' ) . '</p>';
		echo '</div>';

		echo '<div class="debug-troubleshooter-card">';
		echo '<h3>' . esc_html__( 'WordPress Information', 'debug-troubleshooter' ) . '</h3>';
		echo '<p><strong>' . esc_html__( 'WordPress Version:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( get_bloginfo( 'version' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Active Theme:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( wp_get_theme()->get( 'Name' ) ) . ' (' . esc_html( wp_get_theme()->get_stylesheet() ) . ')</p>';

		$active_plugins = get_option( 'active_plugins' );
		$active_plugin_names = array();
		if ( ! empty( $active_plugins ) ) {
			foreach ( $active_plugins as $plugin ) {
				// Use `get_plugin_data` to get plugin details safely.
				if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
					$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
					$active_plugin_names[] = $plugin_data['Name'];
				}
			}
		}
		echo '<p><strong>' . esc_html__( 'Active Plugins:', 'debug-troubleshooter' ) . '</strong> ' . ( ! empty( $active_plugin_names ) ? esc_html( implode( ', ', $active_plugin_names ) ) : esc_html__( 'None', 'debug-troubleshooter' ) ) . '</p>';

		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins' );
			$sitewide_plugin_names = array();
			if ( ! empty( $active_sitewide_plugins ) ) {
				foreach ( $active_sitewide_plugins as $plugin_file => $data ) {
					// Use `get_plugin_data` to get plugin details safely.
					if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
						$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
						$sitewide_plugin_names[] = $plugin_data['Name'];
					}
				}
			}
			echo '<p><strong>' . esc_html__( 'Active Network Plugins:', 'debug-troubleshooter' ) . '</strong> ' . ( ! empty( $sitewide_plugin_names ) ? esc_html( implode( ', ', $sitewide_plugin_names ) ) : esc_html__( 'None', 'debug-troubleshooter' ) ) . '</p>';
		}

		echo '</div>';

		echo '<div class="debug-troubleshooter-card">';
		echo '<h3>' . esc_html__( 'WordPress Constants', 'debug-troubleshooter' ) . '</h3>';
		echo '<ul>';
		$wp_constants = array(
			'WP_DEBUG',
			'WP_DEBUG_DISPLAY',
			'WP_DEBUG_LOG',
			'SCRIPT_DEBUG',
			'WP_MEMORY_LIMIT',
			'WP_MAX_MEMORY_LIMIT',
			'CONCATENATE_SCRIPTS',
			'COMPRESS_SCRIPTS',
			'COMPRESS_CSS',
			'UPLOADS',
			'WP_POST_REVISIONS',
			'EMPTY_TRASH_DAYS',
			'AUTOSAVE_INTERVAL',
			'WP_CACHE',
			'DISABLE_WP_CRON',
			'ALTERNATE_WP_CRON',
			'DISALLOW_FILE_EDIT',
			'DISALLOW_FILE_MODS',
			'FS_METHOD',
		);
		foreach ( $wp_constants as $constant ) {
			echo '<li><strong>' . esc_html( $constant ) . ':</strong> ';
			if ( defined( $constant ) ) {
				$value = constant( $constant );
				if ( is_bool( $value ) ) {
					echo esc_html( $value ? 'true' : 'false' );
				} elseif ( is_numeric( $value ) ) {
					echo esc_html( $value );
				} elseif ( is_string( $value ) ) {
					echo '"' . esc_html( $value ) . '"';
				} else {
					// For arrays/objects or other complex types, just note it as 'Defined'.
					echo esc_html__( 'Defined', 'debug-troubleshooter' );
				}
			} else {
				echo esc_html__( 'Undefined', 'debug-troubleshooter' );
			}
			echo '</li>';
		}
		echo '</ul>';
		echo '</div>';

    	echo '<div class="debug-troubleshooter-card">';
		echo '<h3>' . esc_html__( 'Server Information', 'debug-troubleshooter' ) . '</h3>';
		// Applying sanitize_text_field for stricter adherence to input sanitization warnings
		echo '<p><strong>' . esc_html__( 'Web Server:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : __( 'N/A', 'debug-troubleshooter' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Server Address:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : __( 'N/A', 'debug-troubleshooter' ) ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Port:', 'debug-troubleshooter' ) . '</strong> ' . esc_html( isset( $_SERVER['SERVER_PORT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) ) : __( 'N/A', 'debug-troubleshooter' ) ) . '</p>';
		echo '</div>';
	}

	/**
	 * Initializes the troubleshooting mode.
	 * This hook runs very early to ensure filters are applied before most of WP loads.
	 */
	public function init_troubleshooting_mode() {
		if ( isset( $_COOKIE[ self::TROUBLESHOOT_COOKIE ] ) ) {
			$this->troubleshoot_state = json_decode( sanitize_text_field( wp_unslash( $_COOKIE[ self::TROUBLESHOOT_COOKIE ] ) ), true );

			if ( ! empty( $this->troubleshoot_state ) ) {
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
	 * @param string $theme The active theme stylesheet or template.
	 * @return string Filtered theme stylesheet or template.
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
			setcookie( self::TROUBLESHOOT_COOKIE, json_encode( $state ), array(
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
		$selected_plugins = isset( $_POST['plugins'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['plugins'] ) ) : array();

		// For multisite, we need to distinguish regular active plugins from network active ones.
		// For simplicity, we'll store network active plugins as an indexed array in the cookie as well.
		$all_plugins = get_plugins(); // Get all installed plugins to validate existence.
		$current_sitewide_plugins = is_multisite() ? array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) : array();

		$new_active_plugins = array();
		$new_active_sitewide_plugins = array();

		foreach ( $selected_plugins as $plugin_file ) {
			// Check if the plugin file actually exists in the plugin directory.
			if ( isset( $all_plugins[ $plugin_file ] ) ) {
				// If it's a network active plugin, add it to the sitewide array.
				if ( is_multisite() && in_array( $plugin_file, $current_sitewide_plugins ) ) {
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
		setcookie( self::TROUBLESHOOT_COOKIE, json_encode( $state ), array(
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
					<a href="<?php echo esc_url( $troubleshoot_url ); ?>"><?php esc_html_e( 'Go to Debug & Troubleshooter page to manage.', 'debug-troubleshooter' ); ?></a>
				</p>
			</div>
			<?php
		}
	}
}

// Initialize the plugin.
new Debug_Troubleshooter();
