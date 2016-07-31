<?php
/**
 * Plugin Name: View Admin As
 * Description: View the WordPress admin as a specific role, switch between users and temporarily change your capabilities.
 * Plugin URI:  https://wordpress.org/plugins/view-admin-as/
 * Version:     1.5.2.1
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

define( 'VIEW_ADMIN_AS_VERSION', '1.5.2' );
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
	 * @since	1.4.1
	 * @var		VAA_View_Admin_As
	 */
	private static $_instance = null;
	
	/**
	 * Plugin version
	 *
	 * @since  1.3.1
	 * @var    string
	 */
	private $version = VIEW_ADMIN_AS_VERSION;
	
	/**
	 * Database version
	 *
	 * @since  1.3.4
	 * @var    string
	 */
	private $dbVersion = '1.5';
	
	/**
	 * Database option key
	 *
	 * @since  1.4
	 * @var    string
	 */
	private $optionKey = 'vaa_view_admin_as';
	
	/**
	 * Database option data
	 *
	 * @since  1.4
	 * @var    array
	 */
	private $optionData = array(
		'db_version',
	);
	
	/**
	 * Array of default settings
	 *
	 * @since  1.5
	 * @var    array
	 */
	private $defaultSettings = array();

	/**
	 * Array of allowed settings
	 *
	 * @since  1.5
	 * @var    array
	 */
	private $allowedSettings = array();

	/**
	 * Array of default settings
	 *
	 * @since  1.5
	 * @var    array
	 */
	private $defaultUserSettings = array(
		'view_mode' => 'browse',
		'admin_menu_location' => 'top-secondary',
		'force_group_users' => "no",
	);

	/**
	 * Array of allowed settings
	 *
	 * @since  1.5
	 * @var    array
	 */
	private $allowedUserSettings = array(
		'view_mode' => array('browse', 'single'),
		'admin_menu_location' => array('top-secondary', 'my-account'),
		'force_group_users' => array("yes", "no"),
	);

	/**
	 * Meta key for view data
	 *
	 * @since  1.3.4
	 * @var    bool
	 */
	private $userMetaKey = 'vaa-view-admin-as';

	/**
	 * Complete meta value
	 *
	 * @since  1.5
	 * @var    bool
	 */
	private $userMeta = array(
		'settings',
		'views',
	);
	
	/**
	 * Expiration time for view data
	 *
	 * @since  1.3.4
	 * @var    int
 
	 */
	private $metaExpiration = 86400; // one day: ( 24 * 60 * 60 )
	
	/**
	 * Enable functionalities for this user?
	 *
	 * @since  0.1
	 * @var    bool
	 */
	private $enable = false;
	
	/**
	 * Var that holds all the notices
	 *
	 * @since  1.5.1
	 * @var    array
	 */
	private $notices = array();
	
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
	 * Current user object
	 *
	 * @since  0.1
	 * @var    object
	 */	
	private $curUser = false;
	
	/**
	 * Current user session
	 *
	 * @since  1.3.4
	 * @var    string
	 */	
	private $curUserSession = '';
	
	/**
	 * Current user session
	 *
	 * @since  1.3.4
	 * @var    string
	 */	
	private $nonce = '';
	
	/**
	 * Selected view mode
	 * 
	 * Format: array( VIEW_TYPE => NAME )
	 *
	 * @since  0.1
	 * @var    array
	 */
	private $viewAs = false;
	
	/**
	 * Array of available capabilities
	 *
	 * @since  1.3
	 * @var    array
	 */	
	private $caps;
	
	/**
	 * Array of available roles
	 *
	 * @since  0.1
	 * @var    array
	 */	
	private $roles;
	
	/**
	 * Array of available user objects
	 *
	 * @since  0.1
	 * @var    array
	 */	
	private $users;
	
	/**
	 * Array of available usernames (key) and display names (value)
	 *
	 * @since  0.1
	 * @var    array
	 */	
	private $usernames;
	
	/**
	 * Array of available user ID's (key) and display names (value)
	 *
	 * @since  0.1
	 * @var    array
	 */	
	private $userids;
	
	/**
	 * The selected user object (if the user view is selected)
	 *
	 * @since  0.1
	 * @var    object
	 */	
	private $selectedUser;

	/**
	 * Init function to register plugin hook
	 * Private to make sure it isn't declared elsewhere
	 *
	 * @since   0.1
	 * @access 	private
	 * @return	void
	 */
	private function __construct() {
		self::$_instance = $this;
		
		add_action( 'admin_notices', array( $this, 'do_admin_notices') );
		$this->validate_versions();

		if ( ! class_exists( 'VAA_View_Admin_As_Class_Base' ) ) {
			// Include the class base file
			require_once( VIEW_ADMIN_AS_DIR . 'includes/class-base.php' );

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
	 * @access 	public
	 * @return	void
	 */
	public function init() {
		
		// When a user logs in or out, reset the view to default
		add_action( 'wp_login', array( $this, 'cleanup_views' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'reset_view' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'reset_view' ) );
		
		// Not needed, the delete_user actions already remove all metadata
		//add_action( 'remove_user_from_blog', array( $this, 'delete_user_meta' ) );
		//add_action( 'wpmu_delete_user', array( $this, 'delete_user_meta' ) );
		//add_action( 'wp_delete_user', array( $this, 'delete_user_meta' ) );

		if ( is_user_logged_in() ) {

			$this->set_nonce( 'view-admin-as' );

			// Get the current user
			$this->set_curUser( wp_get_current_user() );

			// Get the current user session
			if ( function_exists( 'wp_get_session_token' ) ) {
				// WP 4.0+
				$this->set_curUserSession( (string) wp_get_session_token() );
			} else {
				$cookie = wp_parse_auth_cookie( '', 'logged_in' );
				if ( ! empty( $cookie['token'] ) ) {
					$this->set_curUserSession( (string) $cookie['token'] );
				} else {
					// Fallback. This disables the use of multiple views in different sessions
					$this->set_curUserSession( $this->get_curUser()->ID );
				}
			}

			/**
			 * - Check if current user is an admin or (in a network) super admin 
			 * - Disable plugin functions for nedwork admin pages
			 * 
			 * @since 	1.4 	Make sure we have a session for the current user
			 * @since 	1.5.1 	If a user has the correct capability (view_admin_as + edit_users) this plugin is also enabled, use with care
			 *   				Note that in network installations the non-admin user also needs the manage_network_users capability (of not the edit_users will return false)
			 */
			if (   ( is_super_admin( $this->get_curUser()->ID ) 
				   || ( current_user_can( 'view_admin_as' ) && current_user_can( 'edit_users' ) ) )
				&& ! is_network_admin()
				&& $this->get_curUserSession() != ''
			) {
				$this->enable = true;
			}
			
			// Get database settings
			$this->set_optionData( get_option( $this->get_optionKey() ) );
			// Get database settings of the current user
			$this->set_userMeta( get_user_meta( $this->get_curUser()->ID, $this->get_userMetaKey(), true ) );

			// Check if a database update is needed
			$this->maybe_db_update();
		
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
				
				$this->load_textdomain();
				$this->load_ui();
				
				$this->store_caps();
				$this->store_roles();
				$this->store_users();
				
				// Get the current view (returns false if not found)
				$this->set_viewAs( $this->get_view() );
				// If view is set, 
				if ( $this->get_viewAs() ) {
					// Force display of admin bar (older WP versions)
					if ( function_exists('show_admin_bar') ) {
						show_admin_bar( true );
					}
					// Force display of admin bar (WP 3.3+)
					remove_all_filters( 'show_admin_bar' );
					add_filter( 'show_admin_bar', '__return_true', 999999999 );
					
					// Change current user object so changes can be made on various screen settings
					// wp_set_current_user() returns the new user object
					if ( $this->get_viewAs('user') ) {
						$this->set_selectedUser( wp_set_current_user( $this->get_viewAs('user') ) );
					}
					
					if ( $this->get_viewAs('role') || $this->get_viewAs('caps') ) {
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
				
				// Fix some compatibility issues, more to come!
				$this->third_party_compatibility();

				// Init is finished. Hook is used for other classes related to View Admin As
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
	 * @access 	private
	 * @return	void
	 */
	private function load_ui() {
		// The admin bar ui
		if ( ! class_exists('VAA_View_Admin_As_Admin_Bar') ) {
			include_once( VIEW_ADMIN_AS_DIR . 'ui/admin-bar.php' );
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
	 * @access 	private
	 * @return	void
	 */
	private function load_modules() {
		// The role defaults module (screen settings)
		if ( ! class_exists('VAA_View_Admin_As_Role_Defaults') ) {
			include_once( VIEW_ADMIN_AS_DIR . 'modules/role-defaults.php' );
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
	 * Store available capabilities
	 *
	 * @since   1.4.1
	 * @access 	public
	 * @return	void
	 */
	public function store_caps() {
		
		// Get all available roles and capabilities
		global $wp_roles;
		// Get current user capabilities
		$caps = $this->get_curUser()->allcaps;
		
		// Only allow to add capabilities for an admin (or super admin)
		if ( is_super_admin( $this->get_curUser()->ID ) ) {
			
			// Store available capabilities
			$role_caps = array();
			foreach ( $wp_roles->role_objects as $key => $role ) {
				if ( is_array( $role->capabilities ) ) {
					foreach ( $role->capabilities as $cap => $grant ) {
						$role_caps[ $cap ] = $cap;
					}
				}
			}
			
			// Add compatibility for other cap managers, see
			$role_caps = apply_filters( 'view_admin_as_get_capabilities', $role_caps );
			
			$role_caps = array_unique( $role_caps );
			
			// Add new capabilities to the capability array as disabled
			foreach ( $role_caps as $capKey => $capVal ) {
				if ( is_string( $capVal ) && ! is_numeric( $capVal ) && ! array_key_exists( $capVal, $caps ) ) {
					$caps[ $capVal ] = 0;
				}
				if ( is_string( $capKey ) && ! is_numeric( $capKey ) && ! array_key_exists( $capKey, $caps ) ) {
					$caps[ $capKey ] = 0;
				}
			}
		}

		// Remove role names
		foreach ( $wp_roles->roles as $roleKey => $role ) {
			unset( $caps[ $roleKey ] );
		}
		ksort( $caps );

		$this->set_caps( $caps );
	}
	
	/**
	 * Store available roles
	 *
	 * @since   1.5
	 * @access 	public
	 * @return	void
	 */
	public function store_roles() {
		
		global $wp_roles;
		// Store available roles
		$roles = $wp_roles->role_objects; // role_objects for objects, roles for arrays
		$role_names = $wp_roles->role_names;
		$roles = apply_filters( 'editable_roles', $roles );
		if ( ! is_super_admin( $this->get_curUser()->ID ) ) {
			// The current user is not a super admin (or regular admin in single installations)
			unset( $roles['administrator'] );
			// Current user has the view_admin_as capability, otherwise this functions would'nt be called
			foreach ( $roles as $role_key => $role ) {
				// Remove roles that have the view_admin_as capability
				if ( is_array( $role->capabilities ) && array_key_exists( 'view_admin_as', $role->capabilities ) ) {
					unset( $roles[ $role_key ] );
				}
			}
		}
		// @since 	1.5.3 	Merge role names with the role objects
		foreach ( $roles as $role_key => $role ) {
			if ( isset( $role_names[ $role_key ] ) ) {
				$roles[ $role_key ]->name = $role_names[ $role_key ];
			}
		}

		$this->set_roles( $roles );
	}
	
	/**
	 * Store available users
	 *
	 * @since   1.5
	 * @access 	public
	 * @return	void
	 */
	public function store_users() {
		
		/*
		 * @since 1.5.2
		 * Grant admins the capability to view other admins. There is no UI for this!
		 */
		$superior_admins = array_filter( 
			(array) apply_filters( 'view_admin_as_superior_admins', array() ), 
			'is_numeric'  // Only allow numeric values (user id's)
		);

		// Is the current user a super admin?
		$is_super_admin = is_super_admin( $this->get_curUser()->ID );
		// Is it also one of the manually configured superior admins?
		$is_superior_admin = ( true === $is_super_admin && in_array( $this->get_curUser()->ID, $superior_admins ) ) ? true : false;

		$user_args = array(
			'orderby' => 'display_name',
			'exclude' => $this->get_curUser()->ID, // Exclude the current user
		);
		// Do not get regular admins for normal installs (WP 4.4+)
		if ( ! is_multisite() && ! $is_superior_admin ) {
			$user_args['role__not_in'] = 'administrator';
		}
		// Sort users by role and filter them on available roles
		$users = $this->filter_sort_users_by_role( get_users( $user_args ) );

		$userids = array();
		$usernames = array();
		// Loop though all users
		foreach ( $users as $user_key => $user ) {

			// If the current user is not a superior admin, run the user filters
			if ( true !== $is_superior_admin ) {

				/**
				 * Implement checks instead of is_super_admin() because it adds a lot unnecessary queries
				 * 
				 * @since 	1.5.2
				 * @See 	is_super_admin() at WP docs
				 */
				//if ( is_super_admin( $user->ID ) ) {
				if ( is_multisite() && in_array( $user->user_login, (array) get_super_admins() ) ) {
					// Remove super admins for multisites
					unset( $users[ $user_key ] );
					continue;
				} elseif ( ! is_multisite() && $user->has_cap('administrator') ) {
					// Remove regular admins for normal installs
					unset( $users[ $user_key ] );
					continue;	
				} elseif ( ! $is_super_admin && $user->has_cap('view_admin_as') ) {
					// Remove users who can access this plugin for non-admin users with the view_admin_as capability
					unset( $users[ $user_key ] );
					continue;
				}
			}
			
			// Add users who can't access this plugin to the users list
			$userids[ $user->data->ID ] = $user->data->display_name;
			$usernames[ $user->data->user_login ] = $user->data->display_name;
		}

		$this->set_users( $users );
		$this->set_userids( $userids );
		$this->set_usernames( $usernames );
	}
	
	/**
	 * Sort users by role
	 *
	 * @since   1.1
	 * @access 	public
	 * @param	array	$users
	 * @return	array	$users
	 */
	public function filter_sort_users_by_role( $users ) {
		if ( ! $this->get_roles() ) {
			return $users;
		}
		$tmp_users = array();
		foreach ( $this->get_roles() as $role => $role_data ) {
			foreach ( $users as $user ) {
				// Reset the array to make sure we find a key 
				// Only one key is needed to add the user to the list of available users
				reset( $user->roles );
				if ( $role == current( $user->roles ) ) {
					$tmp_users[] = $user;
				}
			}
		}
		$users = $tmp_users;
		return $users;
	}

	/**
	 * Change capabilities when the user has selected a view
	 * If the capability isn't in the chosen view, then make the value for this capability empty and add "do_not_allow"
	 *
	 * @since   0.1
	 * @access 	public
	 * @param 	array 	$caps 		The actual (mapped) cap names, if the caps are not mapped this returns the requested cap
	 * @param 	string 	$cap 		The capability that was requested
	 * @param 	int 	$user_id 	The ID of the user (not used)
	 * @param 	array 	$args 		Adds the context to the cap. Typically the object ID
	 * @return	array	$caps
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		$filter_caps = false;
		if ( $this->get_viewAs('role') && $this->get_roles() ) {
			$filter_caps = $this->get_roles( $this->get_viewAs('role') )->capabilities;
		} elseif ( $this->get_viewAs('caps') ) {
			$filter_caps = $this->get_viewAs('caps');
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
	 * Gets the AJAX input. If it is valid, then store it in the current user metadata
	 *
	 * Store format: array( VIEW_NAME => VIEW_DATA );
	 *
	 * @since   0.1
	 * @access 	public
	 * @return	void
	 */
	public function ajax_view_admin_as() {
		
		if (   ! defined('DOING_AJAX') 
			|| ! DOING_AJAX 
			|| ! $this->is_enabled() 
			|| ! isset( $this->get_curUser()->ID ) 
			|| ! isset( $_POST['view_admin_as'] ) 
			|| ! isset( $_POST['_vaa_nonce'] ) 
			|| ! wp_verify_nonce( $_POST['_vaa_nonce'], $this->get_nonce() ) 
		) {
			wp_send_json_error( __('Cheatin uh?', 'view-admin-as') );
			die();
		}

		define( 'VAA_DOING_AJAX', true );
		
		$success = false;
		$view_as = $this->validate_view_as_data( $_POST['view_admin_as'] );
		
		// Stop selecting the same view! :)
		if (   ( isset( $view_as['role'] ) && ( $this->get_viewAs('role') && $this->get_viewAs('role') == $view_as['role'] ) ) 
			|| ( isset( $view_as['user'] ) && ( $this->get_viewAs('user') && $this->get_viewAs('user') == $view_as['user'] ) ) 
			|| ( isset( $view_as['reset'] ) && false == $this->get_viewAs() ) 
		) {
			wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('This view is already selected!', 'view-admin-as') ) );
		}
		
		// Update user metadata with selected view
		if ( isset( $view_as['role'] ) || isset( $view_as['user'] ) ) {
			$success = $this->update_view( $view_as );
		} elseif ( isset( $view_as['caps'] ) ) {
			// Check if the selected caps are equal to the default caps
			if ( $this->get_caps() != $view_as['caps'] ) {
				foreach ( $this->get_caps() as $key => $value ) {
					// If the caps are valid (do not force append, see get_caps() & set_array_data() ), change them
					if ( isset( $view_as['caps'][ $key ] ) && $view_as['caps'][ $key ] == 1 ) {
						$this->set_caps( 1, $key );
					} else {
						$this->set_caps( 0, $key );
					}
				}
				$success = $this->update_view( array( 'caps' => $this->get_caps() ) );
				if ( $success != true ) {
					$db_view_value = $this->get_view();
					if ( $db_view_value['caps'] == $this->get_caps() ) {
						wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('This view is already selected!', 'view-admin-as') ) );
					}
				}
			} else { 
				// The selected caps are equal to the current user default caps so we can reset the view
				$this->reset_view();
				if ( $this->get_viewAs('caps') ) {
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
			$success = $this->store_settings( $view_as['user_setting'], 'user' );
		} elseif ( isset( $view_as['setting'] ) ) {
			$success = $this->store_settings( $view_as['setting'], 'global' );
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
	 * @access 	private
	 * @param 	array		$view_as
	 * @return	array|bool	$view_as
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
						$this->store_caps();
						if ( ! $this->get_caps() ) {
							unset( $view_as['caps'] );
							continue;
						}
						if ( is_array( $view_as['caps'] ) ) {
						// The data is an array, most likely from the database
							foreach ( $view_as['caps'] as $cap_key => $cap_value ) {
								if ( ! array_key_exists( $cap_key, $this->get_caps() ) ) {
									unset( $view_as['caps'][ $cap_key ] );
								}
							}
						} elseif ( is_string( $view_as['caps'] ) ) {
						// The data is a string so we'll need to convert it to an array
							$new_caps = explode( ',', $view_as['caps'] );
							$view_as['caps'] = array();
							foreach ( $new_caps as $key => $value ) {
								$cap = explode( ':', $value );
								// Make sure the exploded values are valid
								if ( isset( $cap[1] ) && array_key_exists( $cap[0], $this->get_caps() ) ) {
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
						if ( ! is_string( $view_as['role'] ) || ! $this->get_roles() || ! array_key_exists( $view_as['role'], $this->get_roles() ) ) {
							unset( $view_as['role'] );
						}
					break;
					case 'user':
						// User data must be a number and exists in the loaded array of user id's
						if ( ! is_numeric( $view_as['user'] ) || ! $this->get_userids() || ! array_key_exists( (int) $view_as['user'], $this->get_userids() ) ) {
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
	 * Validate setting data
	 *
	 * @since   1.5
	 * @access 	private
	 * @param 	array	$settings
	 * @param	string 	$type 		global / user
	 * @return	array	$settings
	 */
	private function validate_settings( $settings, $type ) {
		if ( $type == 'global' ) {
			$defaults = $this->get_defaultSettings();
			$allowed  = $this->get_allowedSettings();
		} elseif ( $type == 'user' ) {
			$defaults = $this->get_defaultUserSettings();
			$allowed  = $this->get_allowedUserSettings();
		} else {
			return false;
		}
		$settings = wp_parse_args( $settings, $defaults );
		foreach ( $settings as $setting => $value ) {
			if ( ! array_key_exists( $setting, $defaults ) ) {
				// We don't have such a setting
				unset( $settings[ $setting ] );
			} elseif ( ! in_array( $value, $allowed[ $setting ] ) ) {
				// Set it to default
				$settings[ $setting ] = $defaults[ $setting ];
			}
		}
		return $settings;
	}

	/**
	 * Store settings
	 *
	 * @since   1.5
	 * @access 	private
	 * @param 	array 	$settings
	 * @param	string 	$type 		global / user
	 * @return	bool
	 */
	private function store_settings( $settings, $type ) {
		if ( $type == 'global' ) {
			$current  = $this->get_settings();
			$defaults = $this->get_defaultSettings();
			$allowed  = $this->get_allowedSettings();
		} elseif ( $type == 'user' ) {
			$current  = $this->get_userSettings();
			$defaults = $this->get_defaultUserSettings();
			$allowed  = $this->get_allowedUserSettings();
		} else {
			return false;
		}
		if ( ! is_array( $current ) ) {
			$current = $defaults;
		}
		foreach ( $settings as $setting => $value ) {
			// Only allow the settings when it exists in the defaults and the value exists in the allowed settings
			if ( array_key_exists( $setting, $defaults ) && in_array( $value, $allowed[ $setting ] ) ) {
				$current[ $setting ] = $value;
				// Some settings need a reset
				if ( in_array( $setting, array( 'view_mode' ) ) ) {
					$this->reset_view();
				}
			}
		}
		if ( $type == 'global' ) {
			$new = $this->validate_settings( wp_parse_args( $current, $defaults ), 'global' );
			return $this->update_optionData( $new, 'settings', true );
		} elseif ( $type == 'user' ) {
			$new = $this->validate_settings( wp_parse_args( $current, $defaults ), 'user' );
			return $this->update_userMeta( $new, 'settings', true );
		}
	}

	/**
	 * Add reset link to the access denied page
	 *
	 * @since   1.3
	 * @access 	public
	 * @return	void
	 */
	public function die_handler( $default ) {
		if ( false != $this->get_viewAs() ) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			// Check for existing query vars
			$url_comp = parse_url( $url );
			$url .= ( isset ( $url_comp['query'] ) ) ? '&reset-view' : '?reset-view';
			// Check protocol
			$url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $url;
			// Return message with link
			echo '<p>' . __('View Admin As', 'view-admin-as') . ': <a href="' . $url . '">' . __('Did something wrong? Reset the view by clicking me!', 'view-admin-as') . '</a></p>';
		}
		return $default;
	}
	
	/**
	 * Get current view for the current session
	 *
	 * @since   1.3.4
	 * @access 	public
	 * @return	array|string|bool
	 */
	public function get_view() {
		if ( ( ! defined('DOING_AJAX') || ! DOING_AJAX )
			&& isset( $_POST['view_admin_as'] )
			&& $this->get_userSettings('view_mode') == 'single'
			&& isset( $this->get_curUser()->ID )
			&& isset( $_POST['_vaa_nonce'] )
			&& wp_verify_nonce( $_POST['_vaa_nonce'], $this->get_nonce() )
		) {
			return $this->validate_view_as_data( json_decode( stripcslashes( $_POST['view_admin_as'] ), true ) );
		}
		if ( $this->get_userSettings('view_mode') == 'browse' ) {
			$meta = $this->get_userMeta('views');
			if (   is_array( $meta ) 
				&& isset( $meta[ $this->get_curUserSession() ] ) 
				&& isset( $meta[ $this->get_curUserSession() ]['view'] ) 
			) {
				return $this->validate_view_as_data( $meta[ $this->get_curUserSession() ]['view'] );
			}
		}
		return false;
	}
	
	/**
	 * Update view for the current session
	 *
	 * @since   1.3.4
	 * @access 	private
	 * @param	array	$data
	 * @return	bool
	 */
	private function update_view( $data = false ) {
		if ( false != $data ) {
			$meta = $this->get_userMeta('views');
			// Make sure it is an array (no array means no valid data so we can safely clear it)
			if ( ! $meta || ! is_array( $meta ) ) {
				$meta = array();
			}
			// Add the new view metadata and expiration date
			$meta[ $this->get_curUserSession() ] = array(
				'view' => $this->validate_view_as_data( $data ),
				'expire' => ( time() + $this->get_metaExpiration() ),
			);
			// Update metadata (returns: true on success, false on failure)
			return $this->update_userMeta( $meta, 'views', true );
		}
		return false;
	}
	
	/**
	 * Reset view to default
	 * This function is also attached to the wp_login and wp_logout hook
	 *
	 * @since   1.3.4
	 * @access 	public
	 * @param	string	$user_login 	String provided by the wp_login hook (not used)
	 * @param	object	$user   		User object provided by the wp_login hook
	 * @return	bool
	 */
	public function reset_view( $user_login = false, $user = false ) {
		
		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->get_userMetaKey(), true );
			// Check if this user session has metadata
			if ( isset( $meta['views'] ) && isset( $meta['views'][ $this->get_curUserSession() ] ) ) {
				// Remove metadata from this session
				unset( $meta['views'][ $this->get_curUserSession() ] );
				// Update current metadata if it is the current user
				if ( $this->get_curUser()->ID == $user->ID ){
					$this->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed
		return true;
	}
	
	/**
	 * Deleta all expired View Admin As metadata for this user
	 * This function is also attached to the wp_login hook
	 *
	 * @since	1.3.4
	 * @access 	public
	 * @param	string	$user_login 	String provided by the wp_login hook (not used)
	 * @param	object	$user   		User object provided by the wp_login hook
	 * @return	bool
	 */
	public function cleanup_views( $user_login = false, $user = false ) {
		
		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->get_userMetaKey(), true );
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
				if ( $this->get_curUser()->ID == $user->ID ){
					$this->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no cleanup needed
		return true;
	}
	
	/**
	 * Reset all View Admin As metadata for this user
	 *
	 * @since   1.3.4
	 * @access 	public
	 * @param	string	$user_login 	String provided by the wp_login hook (not used)
	 * @param	object	$user   		User object provided by the wp_login hook
	 * @return	bool
	 */
	public function reset_all_views( $user_login = false, $user = false ) {
		
		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->get_userMetaKey(), true );
			// If meta exists, reset it
			if ( isset( $meta['views'] ) ) {
				$meta['views'] = false;
				// Update current metadata if it is the current user
				if ( $this->get_curUser()->ID == $user->ID ){
					$this->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed
		return true;
	}

	/**
	 * Delete all View Admin As metadata for this user
	 *
	 * @since   1.5
	 * @access 	public
	 * @param	int		$user_id   		ID of the user being deleted/removed
	 * @param	object	$user   		User object provided by the wp_login hook
	 * @param	bool	$reset_only   	Only reset (not delet) the user meta
	 * @return	bool
	 */
	public function delete_user_meta( $user_id = false, $user = false, $reset_only = true ) {
		$id = false;
		if ( is_numeric( $user_id ) ) {
			// Delete hooks
			$id = $user_id;
		} elseif ( isset( $user->ID ) ) {
			// Login/Logout hooks
			$id = $user->ID;
		}
		if ( false != $id ) {
			if ( $reset_only == true ) {
				// Reset db metadata (returns: true on success, false on failure)
				if ( get_user_meta( $id, $this->get_userMetaKey() ) ) {
					return update_user_meta( $id, $this->get_userMetaKey(), false );
				}
			} else {
				// Remove db metadata (returns: true on success, false on failure)
				return delete_user_meta( $id, $this->get_userMetaKey() );
			}
			// Update current metadata if it is the current user
			if ( $this->get_curUser()->ID == $id ){
				$this->set_userMeta( false );
			}
		}
		// No user or metadata found, no deletion needed
		return true;
	}
	
	/**
	 * Fix compatibility issues
	 *
	 * @since   0.1
	 * @access 	public
	 * @return	void
	 */
	public function third_party_compatibility() {
		
		if ( false !== $this->get_viewAs() ) {
			// WooCommerce
			remove_filter( 'show_admin_bar', 'wc_disable_admin_bar', 10 );
		}
		
		// Pods 2.x (only needed for the role selector)
		if ( $this->get_viewAs('role') ) {
			add_filter( 'pods_is_admin', array( $this, 'pods_caps_check' ), 10, 3 );
		}

		// Add caps from other plugins
		add_filter( 'view_admin_as_get_capabilities', function( $caps ) { 
			// PHP 5.3+ http://php.net/manual/en/functions.anonymous.php

			// To support Members filters
			$caps = apply_filters( 'members_get_capabilities', $caps );
			// To support Pods filters
			$caps = apply_filters( 'pods_roles_get_capabilities', $caps );

			return $caps;
		} );

	}
	
	/**
	 * Fix compatibility issues Pods Framework 2.x
	 *
	 * @since   1.0.1
	 * @access 	public
	 * @param	bool		$bool 			Boolean provided by the pods_is_admin hook (not used)
	 * @param	array		$cap 			String or Array provided by the pods_is_admin hook
	 * @param	string		$capability   	String provided by the pods_is_admin hook
	 * @return	bool
	 */
	public function pods_caps_check( $bool, $cap, $capability ) {
		
		// Pods gives arrays most of the time with the to-be-checked capability as the last item
		if ( is_array( $cap ) ) {
			$cap = end( $cap );
		}
		
		$role_caps = $this->get_roles( $this->get_viewAs('role') )->capabilities;
		if ( ! array_key_exists( $cap, $role_caps ) || ( 1 != $role_caps[$cap] ) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Add nessesary scripts and styles
	 *
	 * @since   0.1
	 * @access 	public
	 * @return	void
	 */
	public function enqueue_scripts() {
		// Only enqueue scripts if the admin bar is enabled otherwise they have no use
		if ( is_admin_bar_showing() && $this->is_enabled() ) {
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min'; // Use non-minified versions
			$version = defined('WP_DEBUG') && WP_DEBUG ? time() : $this->get_version(); // Prevent browser cache
			wp_enqueue_style( 'vaa_view_admin_as_style', VIEW_ADMIN_AS_URL . 'assets/css/view-admin-as' . $suffix . '.css', array(), $version );
			wp_enqueue_script( 'vaa_view_admin_as_script', VIEW_ADMIN_AS_URL . 'assets/js/view-admin-as' . $suffix . '.js', array( 'jquery' ), $version, true );
			wp_localize_script( 'vaa_view_admin_as_script', 'VAA_View_Admin_As', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'siteurl' => get_site_url(),
				'_debug' => ( defined('WP_DEBUG') && WP_DEBUG ) ? (bool) WP_DEBUG : false,
				'_vaa_nonce' => wp_create_nonce( $this->get_nonce() ),
				'__no_users_found' => esc_html__( 'No users found.', 'view-admin-as' ),
				'__success' => esc_html__( 'Success', 'view-admin-as' ),
			) );
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
		load_plugin_textdomain( 'view-admin-as', false, VIEW_ADMIN_AS_DIR . '/languages/' );
		
		//TODO: For frontend translation of roles > not working
		/*if ( ! is_admin() ) {
			load_textdomain( 'default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo' );
		}*/
	}
	
	/**
	 * Update settings
	 *
	 * @since 	1.4
	 * @access 	private
	 * @return	void
	 */
	private function db_update() {
		$defaults = array(
			'db_version' => $this->get_dbVersion(),
		);
		
		$db_version = strtolower( $this->get_optionData('db_version') );

		// Clear the user views for update to 1.5+
		if ( version_compare( $db_version, '1.5', '<' ) ) {
			// Reset user meta for all users
			global $wpdb;
			$all_users = $wpdb->get_results("SELECT ID FROM $wpdb->users");
			foreach ( $all_users as $user ) {
				$this->delete_user_meta( $user->ID, false, true ); // true for reset_only
			}
			// Reset currently loaded data
			$this->set_userMeta(false);
		}

		// Update version, append if needed
		$this->set_optionData( $this->get_dbVersion(), 'db_version', true );
		// Update option data
		$this->update_optionData( wp_parse_args( $this->get_optionData(), $defaults ) );

		// TODO: Maybe a hook??
		// Main update finished, hook used to update modules
		//do_action( 'vaa_view_admin_as_db_update', $this, $this->get_dbVersion() );

		foreach ( $this->get_modules() as $module ) {
			if ( method_exists( $module, 'db_update' ) ) {
				$module->db_update();
			}
		}
	}
	
	/**
	 * Check the correct DB version in the DB
	 *
	 * @since 	1.4
	 * @access 	public
	 * @return	void
	 */
	public function maybe_db_update() {
		$db_version = strtolower( $this->get_optionData('db_version') );
		if ( version_compare( $db_version, $this->get_dbVersion(), '<' ) ) {
			$this->db_update();
		}
	}

	/**
	 * Is enabled?
	 *
	 * @since   1.5
	 * @access 	public
	 * @return	bool
	 */
	public function is_enabled() { return (bool) $this->enable; }

	/**
	 * Get full array or array key
	 *
	 * @since   1.5
	 * @access 	public
	 * @param 	array 	$array 		The requested array
	 * @param 	string	$key 		Return only a key of the requested array (optional)
	 * @return	array|string
	 */
	public function get_array_data( $array, $key = false ) {
		if ( $key ) {
			if ( isset( $array[ $key ] ) ) {
				return $array[ $key ];
			}
			return false; // return false if key is not found
		} else if ( isset( $array ) ) { // This could not be set
			return $array;
		}
		return false;
	}

	/**
	 * Set full array or array key
	 *
	 * @since   1.5
	 * @access 	public
	 * @param 	array 	$array 		Original array
	 * @param 	mixed	$var 		The new value
	 * @param 	string	$key 		The array key for the value (optional)
	 * @param 	bool	$append 	If the key doesn't exist in the original array, append it (optional)
	 * @return	array|string
	 */
	public function set_array_data( $array, $var, $key = false, $append = false ) {
		if ( $key ) {
			if ( true === $append && ! is_array( $array ) ) {
				$array = array();
			}
			if ( true === $append || isset( $array[ $key ] ) ) {
				$array[ $key ] = $var;
				return $array;
			}
			return $array; // return no changes if key is not found or appeding is not allowed
			// Notify user if in debug mode
			if ( defined('WP_DEBUG') && true === WP_DEBUG ) {
				trigger_error('View Admin As: Key does not exist', E_USER_NOTICE);
				if ( ! defined('WP_DEBUG_DISPLAY') || ( defined('WP_DEBUG_DISPLAY') && true === WP_DEBUG_DISPLAY ) ) {
					debug_print_backtrace();
				}
			}
		}
		return $var;
	}
	
	/**
	 * Getters 
	 */
	public function get_curUser() { return $this->curUser; }
	public function get_curUserSession() { return (string) $this->curUserSession; }
	public function get_nonce() { return $this->nonce; }
	public function get_viewAs( $key = false ) { return $this->get_array_data( $this->viewAs, $key ); }
	public function get_caps( $key = false ) { return $this->get_array_data( $this->caps, $key ); }
	public function get_roles( $key = false ) { return $this->get_array_data( $this->roles, $key ); }
	public function get_users( $key = false ) { return $this->get_array_data( $this->users, $key ); }
	public function get_selectedUser() { return $this->selectedUser; }
	public function get_userids() { return $this->userids; }
	public function get_usernames() { return $this->usernames; }
	public function get_version() { return strtolower( $this->version ); }
	public function get_dbVersion() { return strtolower( $this->dbVersion ); }
	public function get_modules( $key = false ) { return $this->get_array_data( $this->modules, $key ); }
	public function get_defaultSettings( $key = false ) { return $this->get_array_data( $this->defaultSettings, $key ); }
	public function get_allowedSettings( $key = false ) { return $this->get_array_data( $this->allowedSettings, $key ); }
	public function get_defaultUserSettings( $key = false ) { return $this->get_array_data( $this->defaultUserSettings, $key ); }
	public function get_allowedUserSettings( $key = false ) { return $this->get_array_data( $this->allowedUserSettings, $key ); }
	public function get_settings( $key = false ) { return $this->get_array_data( $this->validate_settings( $this->get_optionData( 'settings' ), 'global' ), $key ); }
	public function get_userSettings( $key = false ) { return $this->get_array_data( $this->validate_settings( $this->get_userMeta( 'settings' ), 'user' ), $key ); }
	public function get_optionKey() { return (string) $this->optionKey; }
	public function get_optionData( $key = false ) { return $this->get_array_data( $this->optionData, $key ); }
	public function get_userMetaKey() { return $this->userMetaKey; }
	public function get_userMeta( $key = false ) { return $this->get_array_data( $this->userMeta, $key ); }
	public function get_metaExpiration() { return $this->metaExpiration; }
	
	/**
	 * Setters 
	 */
	private function set_curUser( $var ) { $this->curUser = $var; }
	private function set_curUserSession( $var ) { $this->curUserSession = $var; }
	private function set_nonce( $var ) { $this->nonce = $var; }
	private function set_viewAs( $var, $key = false, $append = false ) { $this->viewAs = $this->set_array_data( $this->viewAs, $var, $key, $append ); }
	private function set_caps( $var, $key = false, $append = false ) { $this->caps = $this->set_array_data( $this->caps, $var, $key, $append ); }
	private function set_roles( $var, $key = false, $append = false ) { $this->roles = $this->set_array_data( $this->roles, $var, $key, $append ); }
	private function set_users( $var, $key = false, $append = false ) { $this->users = $this->set_array_data( $this->users, $var, $key, $append ); }
	private function set_userids( $var ) { $this->userids = $var; }
	private function set_usernames( $var ) { $this->usernames = $var; }
	private function set_selectedUser( $var ) { $this->selectedUser = $var; }
	private function set_defaultSettings( $var ) { $this->defaultSettings = $var; }
	private function set_allowedSettings( $var ) { $this->allowedSettings = $var; }
	private function set_defaultUserSettings( $var ) { $this->defaultUserSettings = $var; }
	private function set_allowedUserSettings( $var ) { $this->allowedUserSettings = $var; }
	private function set_settings( $var, $key = false, $append = false ) { 
		$this->set_optionData( $this->validate_settings( $this->set_array_data( $this->get_settings(), $var, $key, $append ), 'global' ), 'settings', true ); 
	}
	private function set_userSettings( $var, $key = false, $append = false ) { 
		$this->set_userMeta( $this->validate_settings( $this->set_array_data( $this->get_userSettings(), $var, $key, $append ), 'user' ), 'settings', true ); 
	}
	private function set_optionData( $var, $key = false, $append = false ) { $this->optionData = $this->set_array_data( $this->optionData, $var, $key, $append ); }
	private function set_userMeta( $var, $key = false, $append = false ) { $this->userMeta = $this->set_array_data( $this->userMeta, $var, $key, $append ); }
	
	/**
	 * Update 
	 */
	private function update_optionData( $var, $key = false, $append = false ) {
		$this->set_optionData( $var, $key, $append );
		return update_option( $this->get_optionKey(), $this->get_optionData() );
	}
	private function update_userMeta( $var, $key = false, $append = false ) {
		$this->set_userMeta( $var, $key, $append );
		return update_user_meta( $this->get_curUser()->ID, $this->get_userMetaKey(), $this->get_userMeta() );
	}

	/**
	 * Add notices to generate
	 *
	 * @since	1.5.1
	 * @access 	public
	 * @param 	string 	$id
	 * @param 	array 	$notice 	Keys: 'type' and 'message'
	 * @return	void
	 */
	public function add_notice( $id, $notice ) {
		if ( isset( $notice['type'] ) && isset( $notice['message'] ) ) {
			$this->notices[ $id ] = array(
				'type' => $notice['type'],
				'message' => $notice['message'],
			);
		}
	}

	/**
	 * Echo admin notices
	 * Used by hook: admin_notices
	 *
	 * @since	1.5.1
	 * @access 	public
	 * @return	void
	 */
	public function do_admin_notices() {
		foreach ( $this->notices as $notice ) {
			if ( isset( $notice['type'] ) && isset( $notice['message'] ) ) {
				echo '<div class="' . $notice['type'] . ' notice is-dismissible"><p>' . $notice['message'] . '</p></div>';
			}
		}
	}

	/**
	 * Validate plugin activate
	 *
	 * @since	1.5.1
	 * @access 	private
	 * @return	void
	 */
	private function validate_versions() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			$this->add_notice('php-version', array(
				'type' => 'notice-error',
				'message' => __('View Admin As', 'view-admin-as') . ': ' . sprintf( __('Plugin deactivated, %s version %s or higher is required', 'view-admin-as'), 'PHP', '5.3' ),
			) );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
		}
		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			$this->add_notice('wp-version', array(
				'type' => 'notice-error',
				'message' => __('View Admin As', 'view-admin-as') . ': ' . sprintf( __('Plugin deactivated, %s version %s or higher is required', 'view-admin-as'), 'WordPress', '3.5' ),
			) );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
		}
	}

	/**
	 * Main View Admin As Instance.
	 *
	 * Ensures only one instance of View Admin As is loaded or can be loaded.
	 *
	 * @since	1.4.1
	 * @access 	public
	 * @static
	 * @see		View_Admin_As()
	 * @return	View Admin As - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Magic method to output a string if trying to use the object as a string.
	 *
	 * @since  1.5
	 * @access public
	 * @return void
	 */
	public function __toString() {
		return get_class( $this );
	}

	/**
	 * Magic method to keep the object from being cloned.
	 *
	 * @since  1.5
	 * @access public
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'view-admin-as' ), null );
	}

	/**
	 * Magic method to keep the object from being unserialized.
	 *
	 * @since  1.5
	 * @access public
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'view-admin-as' ), null );
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since  1.5
	 * @access public
	 * @return null
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
 * @since  1.4.1
 * @return VAA_View_Admin_As
 */
function View_Admin_As() {
	return VAA_View_Admin_As::get_instance();
}
View_Admin_As();

// end if class_exists
} else {
	add_action( 'admin_notices', 'view_admin_as_conflict_admin_notice' );
	function view_admin_as_conflict_admin_notice() {
		echo '<div class="notice-error notice is-dismissible"><p><strong>' . __('View Admin As', 'view-admin-as') . ':</strong> ' 
			. __('Plugin not activated because of a conflict with an other plugin or theme', 'view-admin-as') 
			. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As' ) . ')' . '</code></p></div>';
	}
	deactivate_plugins( plugin_basename( __FILE__ ) );
}