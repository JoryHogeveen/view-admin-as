<?php
/**
 * Plugin Name: View Admin As
 * Description: View the WordPress admin as a specific role, switch between users and temporarily change your capabilities.
 * Plugin URI:  https://wordpress.org/plugins/view-admin-as/
 * Version:     1.5.4-dev
 * Author:      Jory Hogeveen
 * Author URI:  https://www.keraweb.nl
 * Text Domain: view-admin-as
 * Domain Path: /languages/
 */

/*
 * Copyright 2015-2016 Jory Hogeveen
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

! defined( 'ABSPATH' ) and die( 'You shall not pass!' );

if ( ! class_exists( 'VAA_View_Admin_As' ) ) {

define( 'VIEW_ADMIN_AS_VERSION', '1.5.3' );
define( 'VIEW_ADMIN_AS_DB_VERSION', '1.5' );
define( 'VIEW_ADMIN_AS_FILE', __FILE__ );
define( 'VIEW_ADMIN_AS_BASENAME', plugin_basename( VIEW_ADMIN_AS_FILE ) );
define( 'VIEW_ADMIN_AS_DIR', plugin_dir_path( VIEW_ADMIN_AS_FILE ) );
define( 'VIEW_ADMIN_AS_URL', plugin_dir_url( VIEW_ADMIN_AS_FILE ) );

$GLOBALS['required_php_version'] = '5.3.0';

final class VAA_View_Admin_As
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.4.1
	 * @var    VAA_View_Admin_As
	 */
	private static $_instance = null;

	/**
	 * Enable functionalities for this user?
	 *
	 * @since  0.1
	 * @var    bool
	 */
	private $enable = false;

	/**
	 * Classes that are allowed to use this class
	 *
	 * @since  1.5.x
	 * @var    array
	 */
	private static $vaa_class_names = array();

	/**
	 * Var that holds all the notices
	 *
	 * @since  1.5.1
	 * @var    array
	 */
	private $notices = array();

	/**
	 * VAA Store
	 *
	 * @since  1.5.x
	 * @var    array
	 */
	private $store = null;

	/**
	 * VAA UI classes that are loaded
	 *
	 * @since  1.5
	 * @var    array
	 */
	private $ui = array();

	/**
	 * Other VAA modules that are loaded
	 *
	 * @since  1.4
	 * @var    array
	 */
	private $modules = array();

	/**
	 * Init function to register plugin hook
	 * Private to make sure it isn't declared elsewhere
	 *
	 * @since   0.1
	 * @since   1.3.3   changes init hook to plugins_loaded for theme compatibility
	 * @since   1.4.1   creates instance
	 * @since   1.5     make private
	 * @since   1.5.1   added notice on class name conflict + validate versions
	 * @access  private
	 */
	private function __construct() {
		self::$_instance = $this;

		add_action( 'admin_notices', array( $this, 'do_admin_notices' ) );
		$this->validate_versions();

		if ( ! class_exists( 'VAA_View_Admin_As_Class_Base' ) && ! class_exists( 'VAA_View_Admin_As_Class_Store' ) && ! class_exists( 'VAA_API' ) ) {

			require_once( VIEW_ADMIN_AS_DIR . 'includes/class-api.php' );
			require_once( VIEW_ADMIN_AS_DIR . 'includes/class-store.php' );
			require_once( VIEW_ADMIN_AS_DIR . 'includes/class-base.php' );
			require_once( VIEW_ADMIN_AS_DIR . 'includes/class-update.php' );
			require_once( VIEW_ADMIN_AS_DIR . 'includes/class-compat.php' );
			self::$vaa_class_names[] = 'VAA_API';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Store';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Class_Base';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Update';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Compat';

			$this->store = VAA_View_Admin_As_Store::get_instance( $this );

			// Lets start!
			add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

		} else {

			$this->add_notice('class-error-base', array(
				'type' => 'notice-error',
				'message' => '<strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
					. __('Plugin not loaded because of a conflict with an other plugin or theme', 'view-admin-as')
					. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As_Class_Base' ) . ')</code>',
			) );

		}
	}

	/**
	 * Init function/action to check current user, load nessesary data and register all used hooks
	 *
	 * @since   0.1
	 * @access  public
	 * @return  void
	 */
	public function init() {

		// When a user logs in or out, reset the view to default
		add_action( 'wp_login', array( $this, 'cleanup_views' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'reset_view' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'reset_view' ) );

		// Not needed, the delete_user actions already remove all metadata
		//add_action( 'remove_user_from_blog', array( $this->store, 'delete_user_meta' ) );
		//add_action( 'wpmu_delete_user', array( $this->store, 'delete_user_meta' ) );
		//add_action( 'wp_delete_user', array( $this->store, 'delete_user_meta' ) );

		if ( is_user_logged_in() ) {

			$this->store->set_nonce( 'view-admin-as' );

			// Get the current user
			$this->store->set_curUser( wp_get_current_user() );

			// Get the current user session
			if ( function_exists( 'wp_get_session_token' ) ) {
				// WP 4.0+
				$this->store->set_curUserSession( (string) wp_get_session_token() );
			} else {
				$cookie = wp_parse_auth_cookie( '', 'logged_in' );
				if ( ! empty( $cookie['token'] ) ) {
					$this->store->set_curUserSession( (string) $cookie['token'] );
				} else {
					// Fallback. This disables the use of multiple views in different sessions
					$this->store->set_curUserSession( $this->store->get_curUser()->ID );
				}
			}

			/**
			 * Validate if the current user has access to the functionalities
			 *
			 * @since  0.1    Check if the current user had administrator rights (is_super_admin)
			 *                Disable plugin functions for nedwork admin pages
			 * @since  1.4    Make sure we have a session for the current user
			 * @since  1.5.1  If a user has the correct capability (view_admin_as + edit_users) this plugin is also enabled, use with care
			 *                Note that in network installations the non-admin user also needs the manage_network_users capability (of not the edit_users will return false)
			 * @since  1.5.3  Enable on network pages for superior admins
			 */
			if (   ( is_super_admin( $this->store->get_curUser()->ID )
				     || ( current_user_can( 'view_admin_as' ) && current_user_can( 'edit_users' ) ) )
				&& ( ! is_network_admin() || VAA_API::is_superior_admin( $this->store->get_curUser()->ID ) )
				&& $this->store->get_curUserSession() != ''
			) {
				$this->enable = true;
			}

			// Get database settings
			$this->store->set_optionData( get_option( $this->store->get_optionKey() ) );
			// Get database settings of the current user
			$this->store->set_userMeta( get_user_meta( $this->store->get_curUser()->ID, $this->store->get_userMetaKey(), true ) );

			// Check if a database update is needed
			VAA_View_Admin_As_Update::get_instance( $this )->maybe_db_update();

			// Reset view to default if something goes wrong, example: http://www.your.domain/wp-admin/?reset-view
			if ( isset( $_GET['reset-view'] ) ) {
				$this->reset_view();
			}
			// Clear all user views, example: http://www.your.domain/wp-admin/?reset-all-views
			if ( isset( $_GET['reset-all-views'] ) ) {
				$this->reset_all_views();
			}

			$this->load_modules();

			if ( $this->is_enabled() ) {

				// Fix some compatibility issues, more to come!
				VAA_View_Admin_As_Compat::get_instance( $this )->init();

				$this->load_textdomain();
				$this->load_ui();

				$this->store->store_caps();
				$this->store->store_roles();
				$this->store->store_users();

				// Get the current view (returns false if not found)
				$this->store->set_viewAs( $this->get_view() );
				// If view is set,
				if ( $this->store->get_viewAs() ) {
					// Force display of admin bar (older WP versions)
					if ( function_exists('show_admin_bar') ) {
						show_admin_bar( true );
					}
					// Force display of admin bar (WP 3.3+)
					remove_all_filters( 'show_admin_bar' );
					add_filter( 'show_admin_bar', '__return_true', 999999999 );

					// Change current user object so changes can be made on various screen settings
					// wp_set_current_user() returns the new user object
					if ( $this->store->get_viewAs('user') ) {
						$this->store->set_selectedUser( wp_set_current_user( $this->store->get_viewAs('user') ) );
					}

					if ( $this->store->get_viewAs('role') || $this->store->get_viewAs('caps') ) {
						// Change the capabilities (map_meta_cap is better for compatibility with network admins)
						add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 999999999, 4 );
					}
				}

				// Admin selector ajax return
				add_action( 'wp_ajax_view_admin_as', array( $this, 'ajax_view_admin_as' ) );
				//add_action( 'wp_ajax_nopriv_update_view_as', array( $this, 'ajax_update_view_as' ) );

				// Dúh..
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

				add_filter( 'wp_die_handler', array( $this, 'die_handler' ) );

				/**
				 * Init is finished. Hook is used for other classes related to View Admin As
				 * @since 	1.5
				 * @param 	object 	$this 	VAA_View_Admin_As
				 */
				do_action( 'vaa_view_admin_as_init', $this );

			} else {
				// Extra security check for non-admins who did something naughty or we're demoted to a lesser role
				// If they have settings etc. we'll keep them in case they get promoted again
				add_action( 'wp_login', array( $this, 'reset_all_views' ), 10, 2 );
			}
		}
	}

	/**
	 * Load the user interface
	 *
	 * @since   1.5
	 * @since   1.5.1 	added notice on class name conflict
	 * @access  private
	 * @return  void
	 */
	private function load_ui() {
		// The admin bar ui
		if ( ! class_exists('VAA_View_Admin_As_Admin_Bar') ) {
			include_once( VIEW_ADMIN_AS_DIR . 'ui/admin-bar.php' );
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Admin_Bar';
			$this->ui['admin_bar'] = VAA_View_Admin_As_Admin_Bar::get_instance( $this );
		} else {
			$this->add_notice('class-error-admin-bar', array(
				'type' => 'notice-error',
				'message' => '<strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
					. __('Plugin not loaded because of a conflict with an other plugin or theme', 'view-admin-as')
					. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As_Admin_Bar' ) . ')</code>',
			) );
		}
	}

	/**
	 * Load the modules
	 *
	 * @since   1.5
	 * @since   1.5.1 	added notice on class name conflict
	 * @access  private
	 * @return  void
	 */
	private function load_modules() {
		// The role defaults module (screen settings)
		if ( ! class_exists('VAA_View_Admin_As_Role_Defaults') ) {
			include_once( VIEW_ADMIN_AS_DIR . 'modules/role-defaults.php' );
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Role_Defaults';
			$this->modules['role_defaults'] = VAA_View_Admin_As_Role_Defaults::get_instance( $this );
		} else {
			$this->add_notice('class-error-role-defaults', array(
				'type' => 'notice-error',
				'message' =>'<strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
					. __('Plugin not loaded because of a conflict with an other plugin or theme', 'view-admin-as')
					. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As_Role_Defaults' ) . ')</code>',
			) );
		}
	}

	/**
	 * Change capabilities when the user has selected a view
	 * If the capability isn't in the chosen view, then make the value for this capability empty and add "do_not_allow"
	 *
	 * @since   0.1
	 * @since   1.5     Changed function name to map_meta_cap (was change_caps)
	 * @access  public
	 *
	 * @param   array   $caps       The actual (mapped) cap names, if the caps are not mapped this returns the requested cap
	 * @param   string  $cap        The capability that was requested
	 * @param   int     $user_id    The ID of the user (not used)
	 * @param   array   $args       Adds the context to the cap. Typically the object ID (not used)
	 * @return  array   $caps
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		$filter_caps = false;
		if ( $this->store->get_viewAs('role') && $this->store->get_roles() ) {
			// Role view
			$filter_caps = $this->store->get_roles( $this->store->get_viewAs('role') )->capabilities;
		} elseif ( $this->store->get_viewAs('caps') ) {
			// Caps view
			$filter_caps = $this->store->get_viewAs('caps');
		}

		if ( false != $filter_caps ) {
			foreach ( $caps as $actual_cap ) {
				if ( ! array_key_exists( $actual_cap, $filter_caps ) || ( 1 != (int) $filter_caps[ $actual_cap ] ) ) {
					// Regular
					$caps[ $cap ] = '';
					// Network admins
					$caps[] = 'do_not_allow';
				}
			}
		}

		return $caps;
	}

	/**
	 * AJAX handler
	 * Gets the AJAX input. If it is valid: store it in the current user metadata
	 *
	 * Store format: array( VIEW_NAME => VIEW_DATA );
	 *
	 * @since   0.1
	 * @since   1.3     Added caps key
	 * @since   1.4     Added module keys
	 * @since   1.5     Validate a nonce
	 *                  Added global and user setting keys
	 * @access  public
	 * @return  void
	 */
	public function ajax_view_admin_as() {

		if (   ! defined('DOING_AJAX')
			|| ! DOING_AJAX
			|| ! $this->is_enabled()
			|| ! isset( $_POST['view_admin_as'] )
			|| ! isset( $_POST['_vaa_nonce'] )
			|| ! wp_verify_nonce( $_POST['_vaa_nonce'], $this->store->get_nonce() )
		) {
			wp_send_json_error( __('Cheatin uh?', 'view-admin-as') );
			die();
		}

		define( 'VAA_DOING_AJAX', true );

		$success = false;
		$view_as = $this->validate_view_as_data( $_POST['view_admin_as'] );

		// Stop selecting the same view! :)
		if (   ( isset( $view_as['role'] ) && ( $this->store->get_viewAs('role') && $this->store->get_viewAs('role') == $view_as['role'] ) )
			|| ( isset( $view_as['user'] ) && ( $this->store->get_viewAs('user') && $this->store->get_viewAs('user') == $view_as['user'] ) )
			|| ( isset( $view_as['reset'] ) && false == $this->store->get_viewAs() )
		) {
			wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('This view is already selected!', 'view-admin-as') ) );
		}

		// Update user metadata with selected view
		if ( isset( $view_as['role'] ) || isset( $view_as['user'] ) ) {
			$success = $this->update_view( $view_as );
		} elseif ( isset( $view_as['caps'] ) ) {
			// Check if the selected caps are equal to the default caps
			if ( $this->store->get_caps() != $view_as['caps'] ) {
				foreach ( $this->store->get_caps() as $key => $value ) {
					// If the caps are valid (do not force append, see get_caps() & set_array_data() ), change them
					if ( isset( $view_as['caps'][ $key ] ) && $view_as['caps'][ $key ] == 1 ) {
						$this->store->set_caps( 1, $key );
					} else {
						$this->store->set_caps( 0, $key );
					}
				}
				$success = $this->update_view( array( 'caps' => $this->store->get_caps() ) );
				if ( $success != true ) {
					$db_view_value = $this->get_view();
					if ( $db_view_value['caps'] == $this->store->get_caps() ) {
						wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('This view is already selected!', 'view-admin-as') ) );
					}
				}
			} else {
				// The selected caps are equal to the current user default caps so we can reset the view
				$this->reset_view();
				if ( $this->store->get_viewAs('caps') ) {
					// The user was in a custom caps view, reset is valid
					$success = true; // and continue
				} else {
					// The user is in his default view, reset is invalid
					wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('These are your default capabilities!', 'view-admin-as') ) );
				}
			}
		} elseif ( isset( $view_as['reset'] ) ) {
			$success = $this->reset_view();
		} elseif ( isset( $view_as['user_setting'] ) ) {
			$success = $this->store->store_settings( $view_as['user_setting'], 'user' );
		} elseif ( isset( $view_as['setting'] ) ) {
			$success = $this->store->store_settings( $view_as['setting'], 'global' );
		} else {
			// Maybe a module?
			foreach ( $view_as as $key => $data ) {
				if ( array_key_exists( $key, $this->get_modules() ) ) {
					$module = $this->get_modules( $key );
					if ( method_exists( $module, 'ajax_handler' ) ) {
						$success = $module->ajax_handler( $data );
						if ( is_string( $success ) && ! empty( $success ) ) {
							wp_send_json_error( $success );
						}
					}
				}
				break; // POSSIBLY TODO: Only the first key is actually used at this point
			}
		}

		if ( true == $success ) {
			wp_send_json_success(); // ahw yeah
		} else {
			wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('Something went wrong, please try again.', 'view-admin-as') ) ); // fail
		}

		die(); // Just to make sure it's actually dead..
	}

	/**
	 * Validate data before changing the view
	 *
	 * @since   1.5
	 * @access  private
	 *
	 * @param   array       $view_as
	 * @return  array|bool  $view_as
	 */
	private function validate_view_as_data( $view_as ) {

		$allowed_keys = array( 'setting', 'user_setting', 'reset', 'caps', 'role', 'user' );

		// Add module keys to the allowed keys
		foreach ( $this->get_modules() as $key => $val ) {
			$allowed_keys[] = $key;
		}

		// We only want allowed keys and data, otherwise it's not added through this plugin.
		if ( is_array( $view_as ) ) {
			foreach ( $view_as as $key => $value ) {
				// Check for keys that are not allowed
				if ( ! in_array( $key, $allowed_keys ) ) {
					unset( $view_as[ $key ] );
				}
				switch ( $key ) {
					case 'caps':
						// Make sure we have the latest added capabilities
						$this->store->store_caps();
						if ( ! $this->store->get_caps() ) {
							unset( $view_as['caps'] );
							continue;
						}
						if ( is_array( $view_as['caps'] ) ) {
						// The data is an array, most likely from the database
							foreach ( $view_as['caps'] as $cap_key => $cap_value ) {
								if ( ! array_key_exists( $cap_key, $this->store->get_caps() ) ) {
									unset( $view_as['caps'][ $cap_key ] );
								}
							}
						} elseif ( is_string( $view_as['caps'] ) ) {
						// The data is a string so we'll need to convert it to an array
							$new_caps = explode( ',', $view_as['caps'] );
							$view_as['caps'] = array();
							foreach ( $new_caps as $cap_key => $cap_value ) {
								$cap = explode( ':', $cap_value );
								// Make sure the exploded values are valid
								if ( isset( $cap[1] ) && array_key_exists( $cap[0], $this->store->get_caps() ) ) {
									$view_as['caps'][ strip_tags( $cap[0] ) ] = (int) $cap[1];
								}
							}
							if ( is_array( $view_as['caps'] ) ) {
								ksort( $view_as['caps'] ); // Sort the new caps the same way we sort the existing caps
							} else {
								unset( $view_as['caps'] );
							}
						} else {
							// Caps data is not valid
							unset( $view_as['caps'] );
						}
					break;
					case 'role':
						// Role data must be a string and exists in the loaded array of roles
						if ( ! is_string( $view_as['role'] ) || ! $this->store->get_roles() || ! array_key_exists( $view_as['role'], $this->store->get_roles() ) ) {
							unset( $view_as['role'] );
						}
					break;
					case 'user':
						// User data must be a number and exists in the loaded array of user id's
						if ( ! is_numeric( $view_as['user'] ) || ! $this->store->get_userids() || ! array_key_exists( (int) $view_as['user'], $this->store->get_userids() ) ) {
							unset( $view_as['user'] );
						}
					break;
				}
			}
			return $view_as;
		}
		return false;
	}

	/**
	 * Add reset link to the access denied page when the user has selected a view and did something this view is not allowed
	 *
	 * @since   1.3
	 * @since   1.5.1   Check for SSL
	 * @access  public
	 *
	 * @param   string  $function_name  function callback
	 * @return  string  $function_name  function callback
	 */
	public function die_handler( $function_name ) {
		if ( false != $this->store->get_viewAs() ) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			// Check for existing query vars
			$url_comp = parse_url( $url );
			$url .= ( isset ( $url_comp['query'] ) ) ? '&reset-view' : '?reset-view';
			// Check protocol
			$url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $url;
			// Return message with link
			echo '<p>' . __('View Admin As', 'view-admin-as') . ': <a href="' . $url . '">' . __('Did something wrong? Reset the view by clicking me!', 'view-admin-as') . '</a></p>';
		}
		return $function_name;
	}

	/**
	 * Get current view for the current session
	 *
	 * @since   1.3.4
	 * @since   1.5     Single mode
	 * @access  public
	 * @return  array|string|bool
	 */
	public function get_view() {

		// Single mode
		if ( ( ! defined('DOING_AJAX') || ! DOING_AJAX )
			&& isset( $_POST['view_admin_as'] )
			&& $this->store->get_userSettings('view_mode') == 'single'
			&& isset( $this->store->get_curUser()->ID )
			&& isset( $_POST['_vaa_nonce'] )
			&& wp_verify_nonce( $_POST['_vaa_nonce'], $this->store->get_nonce() )
		) {
			return $this->validate_view_as_data( json_decode( stripcslashes( $_POST['view_admin_as'] ), true ) );
		}

		// Browse mode
		if ( $this->store->get_userSettings('view_mode') == 'browse' ) {
			$meta = $this->store->get_userMeta('views');
			if (   is_array( $meta )
				&& isset( $meta[ $this->store->get_curUserSession() ] )
				&& isset( $meta[ $this->store->get_curUserSession() ]['view'] )
			) {
				return $this->validate_view_as_data( $meta[ $this->store->get_curUserSession() ]['view'] );
			}
		}

		return false;
	}

	/**
	 * Update view for the current session
	 *
	 * @since   1.3.4
	 * @access  private
	 *
	 * @param   array|bool   $data
	 * @return  bool
	 */
	private function update_view( $data = false ) {
		if ( false != $data ) {
			$meta = $this->store->get_userMeta('views');
			// Make sure it is an array (no array means no valid data so we can safely clear it)
			if ( ! $meta || ! is_array( $meta ) ) {
				$meta = array();
			}
			// Add the new view metadata and expiration date
			$meta[ $this->store->get_curUserSession() ] = array(
				'view' => $this->validate_view_as_data( $data ),
				'expire' => ( time() + $this->store->get_metaExpiration() ),
			);
			// Update metadata (returns: true on success, false on failure)
			return $this->store->update_userMeta( $meta, 'views', true );
		}
		return false;
	}

	/**
	 * Reset view to default
	 * This function is also attached to the wp_login and wp_logout hook
	 *
	 * @since   1.3.4
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string|bool  $user_login  (not used) String provided by the wp_login hook
	 * @param   object|bool  $user        User object provided by the wp_login hook
	 * @return  bool
	 */
	public function reset_view( $user_login = false, $user = false ) {

		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// Check if this user session has metadata
			if ( isset( $meta['views'] ) && isset( $meta['views'][ $this->store->get_curUserSession() ] ) ) {
				// Remove metadata from this session
				unset( $meta['views'][ $this->store->get_curUserSession() ] );
				// Update current metadata if it is the current user
				if ( $this->store->get_curUser()->ID == $user->ID ){
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed
		return true;
	}

	/**
	 * Delete all expired View Admin As metadata for this user
	 * This function is also attached to the wp_login hook
	 *
	 * @since   1.3.4
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string|bool  $user_login  (not used) String provided by the wp_login hook
	 * @param   object|bool  $user        User object provided by the wp_login hook
	 * @return  bool
	 */
	public function cleanup_views( $user_login = false, $user = false ) {

		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// If meta exists, loop it
			if ( isset( $meta['views'] ) && 0 < count( $meta['views'] ) ) {
				foreach ( $meta['views'] as $key => $value ) {
					// Check expiration date: if it doesn't exist or is in the past, remove it
					if ( ! isset( $meta['views'][ $key ]['expire'] ) || time() > $meta['views'][ $key ]['expire'] ) {
						unset( $meta['views'][ $key ] );
					}
				}
				if ( empty( $meta['views'] ) ) {
					$meta['views'] = false;
				}
				// Update current metadata if it is the current user
				if ( $this->store->get_curUser()->ID == $user->ID ){
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no cleanup needed
		return true;
	}

	/**
	 * Reset all View Admin As metadata for this user
	 *
	 * @since   1.3.4
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string|bool  $user_login  (not used) String provided by the wp_login hook
	 * @param   object|bool  $user        User object provided by the wp_login hook
	 * @return  bool
	 */
	public function reset_all_views( $user_login = false, $user = false ) {

		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// If meta exists, reset it
			if ( isset( $meta['views'] ) ) {
				$meta['views'] = false;
				// Update current metadata if it is the current user
				if ( $this->store->get_curUser()->ID == $user->ID ){
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed
		return true;
	}

	/**
	 * Add necessary scripts and styles
	 *
	 * @since   0.1
	 * @access  public
	 * @return  void
	 */
	public function enqueue_scripts() {
		// Only enqueue scripts if the admin bar is enabled otherwise they have no use
		if ( is_admin_bar_showing() && $this->is_enabled() ) {
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min'; // Use non-minified versions
			$version = defined('WP_DEBUG') && WP_DEBUG ? time() : $this->store->get_version(); // Prevent browser cache

			wp_enqueue_style(   'vaa_view_admin_as_style', VIEW_ADMIN_AS_URL . 'assets/css/view-admin-as' . $suffix . '.css', array(), $version );
			wp_enqueue_script(  'vaa_view_admin_as_script', VIEW_ADMIN_AS_URL . 'assets/js/view-admin-as' . $suffix . '.js', array( 'jquery' ), $version, true );

			$script_localization = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'siteurl' => get_site_url(),
				'_debug' => ( defined('WP_DEBUG') && WP_DEBUG ) ? (bool) WP_DEBUG : false,
				'_vaa_nonce' => wp_create_nonce( $this->store->get_nonce() ),
				'__no_users_found' => esc_html__( 'No users found.', 'view-admin-as' ),
				'__success' => esc_html__( 'Success', 'view-admin-as' ),
				'__confirm' => esc_html__( 'Are you sure?', 'view-admin-as' ),
				'settings' => $this->store->get_settings(),
				'settings_user' => $this->store->get_userSettings()
			);
			foreach ( $this->get_modules() as $name => $module ) {
				$script_localization[ 'settings_' . $name ] = $module->get_scriptLocalization();
			}

			wp_localize_script( 'vaa_view_admin_as_script', 'VAA_View_Admin_As', $script_localization );
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 	1.2
	 * @access 	public
	 * @return	void
	 */
	public function load_textdomain() {
		/**
		 * Keep the third parameter pointing to the languages folder within this plugin to enable support for custom .mo files
		 *
		 * @todo look into 4.6 changes Maybe the same can be done in an other way
		 * @see https://make.wordpress.org/core/2016/07/06/i18n-improvements-in-4-6/
		 */
		load_plugin_textdomain( 'view-admin-as', false, VIEW_ADMIN_AS_DIR . '/languages/' );

		/**
		 * Frontend translation of roles is not working by default (Darn you WordPress!)
		 * Needs to be in init action to work
		 * @see  https://core.trac.wordpress.org/ticket/37539
		 */
		if ( ! is_admin() ) {
			add_action( 'init', function() {
				load_textdomain( 'default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo' );
			} );
		}
	}

	/**
	 * Is enabled?
	 *
	 * @since   1.5
	 * @access  public
	 * @return  bool
	 */
	public function is_enabled() {
		return (bool) $this->enable;
	}

	/**
	 * Get current modules
	 *
	 * @since   1.5
	 * @access  public
	 * @param   string|bool  $key  The module key
	 * @return  array|object
	 */
	public function get_modules( $key = false ) {
		return VAA_API::get_array_data( $this->modules, $key );
	}

	/**
	 * Add notices to generate
	 *
	 * @since   1.5.1
	 * @access  public
	 *
	 * @param   string  $id
	 * @param   array   $notice  Keys: 'type' and 'message'
	 * @return  void
	 */
	public function add_notice( $id, $notice ) {
		if ( isset( $notice['type'] ) && ! empty( $notice['message'] ) ) {
			$this->notices[ $id ] = array(
				'type' => $notice['type'],
				'message' => $notice['message'],
			);
		}
	}

	/**
	 * Echo admin notices
	 *
	 * @since   1.5.1
	 * @access  public
	 * @see     'admin_notices'
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	 * @return  void
	 */
	public function do_admin_notices() {
		foreach ( $this->notices as $notice ) {
			if ( isset( $notice['type'] ) && ! empty( $notice['message'] ) ) {
				echo '<div class="' . $notice['type'] . ' notice is-dismissible"><p>' . $notice['message'] . '</p></div>';
			}
		}
	}

	/**
	 * Validate plugin activate
	 * Checks for valid resources
	 *
	 * @since   1.5.1
	 * @access  private
	 * @return  void
	 */
	private function validate_versions() {
		global $wp_version;

		// Validate PHP
		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			$this->add_notice('php-version', array(
				'type' => 'notice-error',
				'message' => __('View Admin As', 'view-admin-as') . ': ' . sprintf( __('Plugin deactivated, %s version %s or higher is required', 'view-admin-as'), 'PHP', '5.3' ),
			) );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
		}
		// Validate WP
		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			$this->add_notice('wp-version', array(
				'type' => 'notice-error',
				'message' => __('View Admin As', 'view-admin-as') . ': ' . sprintf( __('Plugin deactivated, %s version %s or higher is required', 'view-admin-as'), 'WordPress', '3.5' ),
			) );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
		}
	}

	/**
	 * Main View Admin As Instance.
	 *
	 * Ensures only one instance of View Admin As is loaded or can be loaded.
	 *
	 * @since   1.4.1
	 * @since   1.5.x  Restrict access to known classes
	 * @access  public
	 * @static
	 * @see     View_Admin_As()
	 * @param   object  $caller
	 * @return  VAA_View_Admin_As
	 */
	public static function get_instance( $caller ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		} elseif ( in_array( $caller, self::$vaa_class_names ) ) {
			return self::$_instance;
		}
		return null;
	}

	/**
	 * Magic method to output a string if trying to use the object as a string.
	 *
	 * @since   1.5
	 * @access  public
	 * @return  string
	 */
	public function __toString() {
		return get_class( $this );
	}

	/**
	 * Magic method to keep the object from being cloned.
	 *
	 * @since   1.5
	 * @access  public
	 * @return  void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'view-admin-as' ), null );
	}

	/**
	 * Magic method to keep the object from being unserialized.
	 *
	 * @since   1.5
	 * @access  public
	 * @return  void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'view-admin-as' ), null );
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since   1.5
	 * @access  public
	 * @param   string
	 * @param   array
	 * @return  null
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong( get_class( $this ) . "::{$method}", esc_html__( 'Method does not exist.', 'view-admin-as' ), null );
		unset( $method, $args );
		return null;
	}

} // end class

/**
 * Main instance of View Admin As.
 *
 * Returns the main instance of VAA_View_Admin_As to prevent the need to use globals.
 *
 * @since   1.4.1
 * @since   1.5.x  $caller parameter
 * @param   object  $caller
 * @return  VAA_View_Admin_As
 */
function View_Admin_As( $caller = null ) {
	return VAA_View_Admin_As::get_instance( $caller );
}
View_Admin_As( null );

// end if class_exists
} else {
	// @since  1.5.1  added notice on class name conflict
	add_action( 'admin_notices', 'view_admin_as_conflict_admin_notice' );
	function view_admin_as_conflict_admin_notice() {
		echo '<div class="notice-error notice is-dismissible"><p><strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
			. __('Plugin not activated because of a conflict with an other plugin or theme', 'view-admin-as')
			. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As' ) . ')' . '</code></p></div>';
	}
	deactivate_plugins( plugin_basename( __FILE__ ) );
}