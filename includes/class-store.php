<?php
/**
 * View Admin As - Class Store
 *
 * Store class that stores the VAA data for use
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.6
 * @version 1.6
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As_Store
{
	/**
	 * The single instance of the class.
	 *
	 * @since   1.6
	 * @var     VAA_View_Admin_As_Store
	 */
	private static $_instance = null;

	/**
	 * Classes that are allowed to use this class
	 *
	 * @since  1.6
	 * @var    array
	 */
	private static $vaa_class_names = array(
		'VAA_View_Admin_As',
		'VAA_View_Admin_As_View',
		'VAA_View_Admin_As_Compat',
		'VAA_View_Admin_As_Update',
		'VAA_View_Admin_As_Admin_Bar',
		'VAA_View_Admin_As_Toolbar',
		'VAA_View_Admin_As_Role_Defaults',
	);

	/**
	 * Current user session
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class
	 * @var    string
	 */
	private $nonce = '';

	/**
	 * Database option key
	 *
	 * @since  1.4
	 * @since  1.6    Moved to this class from main class
	 * @var    string
	 */
	private $optionKey = 'vaa_view_admin_as';

	/**
	 * Database option data
	 *
	 * @since  1.4
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $optionData = array(
		'db_version',
	);

	/**
	 * Array of default settings
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $defaultSettings = array();

	/**
	 * Array of allowed settings
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $allowedSettings = array();

	/**
	 * Array of default settings
	 *
	 * @since  1.5
	 * @since  1.5.2  added force_group_users
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $defaultUserSettings = array(
		'view_mode' => 'browse',
		'admin_menu_location' => 'top-secondary',
		'force_group_users' => 'no',
		'hide_front' => 'no',
	);

	/**
	 * Array of allowed settings
	 *
	 * @since  1.5
	 * @since  1.5.2  added force_group_users
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $allowedUserSettings = array(
		'view_mode' => array( 'browse', 'single' ),
		'admin_menu_location' => array( 'top-secondary', 'my-account' ),
		'force_group_users' => array( 'yes', 'no' ),
		'hide_front' => array( 'yes', 'no' ),
	);

	/**
	 * Meta key for view data
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class
	 * @var    bool
	 */
	private $userMetaKey = 'vaa-view-admin-as';

	/**
	 * Complete meta value
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $userMeta = array(
		'settings',
		'views',
	);

	/**
	 * Array of available capabilities
	 *
	 * @since  1.3
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $caps;

	/**
	 * Array of available roles
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $roles;

	/**
	 * Array of available user objects
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $users;

	/**
	 * Expiration time for view data
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class
	 * @var    int
	 */
	private $metaExpiration = 86400; // one day: ( 24 * 60 * 60 )

	/**
	 * Current user object
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class
	 * @var    object
	 */
	private $curUser = false;

	/**
	 * Current user session
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class
	 * @var    string
	 */
	private $curUserSession = '';

	/**
	 * Selected view mode
	 *
	 * Format: array( VIEW_TYPE => NAME )
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class
	 * @var    array|bool
	 */
	private $viewAs = false;

	/**
	 * Array of available usernames (key) and display names (value)
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $usernames;

	/**
	 * Array of available user ID's (key) and display names (value)
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class
	 * @var    array
	 */
	private $userids;

	/**
	 * The selected user object (if the user view is selected)
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class
	 * @var    object
	 */
	private $selectedUser;

	/**
	 * Populate the instance
	 * @since  1.6
	 */
	private function __construct() {
		self::$_instance = $this;
	}

	/**
	 * Store available roles
	 *
	 * @since   1.5
	 * @since   1.5.2  Get role objects instead of arrays
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @return  void
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
		// @since 	1.5.2.1 	Merge role names with the role objects
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
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @return  void
	 */
	public function store_users() {

		// Is the current user a super admin?
		$is_super_admin = is_super_admin( $this->get_curUser()->ID );
		// Is it also one of the manually configured superior admins?
		$is_superior_admin = VAA_API::is_superior_admin( $this->get_curUser()->ID );

		if ( is_network_admin() ) {

			// Get super admins (returns logins)
			$users = get_super_admins();
			// Remove current user
			if ( in_array( $this->get_curUser()->user_login, $users ) ) {
				unset( $users[ array_search( $this->get_curUser()->user_login, $users ) ] );
			}
			// Convert logins to WP_User objects and filter them for superior admins
			foreach ( $users as $key => $user_login ) {
				$user = get_user_by( 'login', $user_login );
				if ( $user && ! in_array( $user->user_login, VAA_API::get_superior_admins() ) ) {
					$users[ $key ] = get_user_by( 'login', $user_login );
				} else {
					unset( $users[ $key ] );
				}
			}

		} else {

			$user_args = array(
				'orderby' => 'display_name',
				// @since  1.5.2  Exclude the current user
				'exclude' => array_merge( VAA_API::get_superior_admins(), array( $this->get_curUser()->ID ) ),
			);
			// Do not get regular admins for normal installs (WP 4.4+)
			if ( ! is_multisite() && ! $is_superior_admin ) {
				$user_args['role__not_in'] = 'administrator';
			}
			// Sort users by role and filter them on available roles
			$users = $this->filter_sort_users_by_role( get_users( $user_args ) );
		}

		$userids = array();
		$usernames = array();
		// Loop though all users
		foreach ( $users as $user_key => $user ) {

			// If the current user is not a superior admin, run the user filters
			if ( true !== $is_superior_admin ) {

				/**
				 * Implement checks instead of is_super_admin() because it adds a lot unnecessary queries
				 *
				 * @since  1.5.2
				 * @See    is_super_admin()
				 * @link   https://developer.wordpress.org/reference/functions/is_super_admin/
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
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 *
	 * @see     store_users()
	 *
	 * @param   array   $users
	 * @return  array   $users
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
	 * Store available capabilities
	 *
	 * @since   1.4.1
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @return  void
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

			/**
			 * Add compatibility for other cap managers
			 * @since  1.5
			 * @see    VAA_View_Admin_As_Compat->init()
			 * @param  array  $role_caps  All capabilities found in the existing roles
			 */
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

			/**
			 * Add network capabilities
			 * @since  1.5.3
			 * @see    https://codex.wordpress.org/Roles_and_Capabilities
			 * @todo   Move this to VAA_View_Admin_As_Compat?
			 */
			if ( is_multisite() ) {
				$network_caps = array(
					'manage_network' => 1,
					'manage_sites' => 1,
					'manage_network_users' => 1,
					'manage_network_plugins' => 1,
					'manage_network_themes' => 1,
					'manage_network_options' => 1,
				);
				$caps = array_merge( $network_caps, $caps );
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
	 * Validate setting data based on allowed settings
	 * Also merges with the default settings
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 *
	 * @param   array       $settings
	 * @param   string      $type      global / user
	 * @return  array|bool  $settings
	 */
	public function validate_settings( $settings, $type ) {
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
	 * Store settings based on allowed settings
	 * Also merges with the default settings
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 *
	 * @param   array   $settings
	 * @param   string  $type      global / user
	 * @return  bool
	 */
	public function store_settings( $settings, $type ) {
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
					View_Admin_As( $this )->view( 'reset' );
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
		return false;
	}

	/**
	 * Delete all View Admin As metadata for this user
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 *
	 * @param   int|bool     $user_id     ID of the user being deleted/removed
	 * @param   object|bool  $user        User object provided by the wp_login hook
	 * @param   bool         $reset_only  Only reset (not delet) the user meta
	 * @return  bool
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
			$success = true;
			if ( $reset_only == true ) {
				// Reset db metadata (returns: true on success, false on failure)
				if ( get_user_meta( $id, $this->get_userMetaKey() ) ) {
					$success = update_user_meta( $id, $this->get_userMetaKey(), false );
				}
			} else {
				// Remove db metadata (returns: true on success, false on failure)
				$success = delete_user_meta( $id, $this->get_userMetaKey() );
			}
			// Update current metadata if it is the current user
			if ( $success && $this->get_curUser()->ID == $id ){
				$this->set_userMeta( false );
			}

			return $success;
		}
		// No user or metadata found, no deletion needed
		return true;
	}

	/*
	 * Getters
	 */
	public function get_curUser()                           { return $this->curUser; }
	public function get_curUserSession()                    { return (string) $this->curUserSession; }
	public function get_viewAs( $key = false )              { return VAA_API::get_array_data( $this->viewAs, $key ); }
	public function get_caps( $key = false )                { return VAA_API::get_array_data( $this->caps, $key ); }
	public function get_roles( $key = false )               { return VAA_API::get_array_data( $this->roles, $key ); }
	public function get_users( $key = false )               { return VAA_API::get_array_data( $this->users, $key ); }
	public function get_selectedUser()                      { return $this->selectedUser; }
	public function get_userids()                           { return $this->userids; }
	public function get_usernames()                         { return $this->usernames; }
	public function get_optionKey()                         { return (string) $this->optionKey; }
	public function get_optionData( $key = false )          { return VAA_API::get_array_data( $this->optionData, $key ); }
	public function get_userMetaKey()                       { return (string) $this->userMetaKey; }
	public function get_userMeta( $key = false )            { return VAA_API::get_array_data( $this->userMeta, $key ); }
	public function get_metaExpiration()                    { return $this->metaExpiration; }
	public function get_nonce( $parsed = false )            { return ( $parsed ) ? wp_create_nonce( $this->nonce ) : $this->nonce; }
	public function get_defaultSettings( $key = false )     { return VAA_API::get_array_data( $this->defaultSettings, $key ); }
	public function get_allowedSettings( $key = false )     { return VAA_API::get_array_data( $this->allowedSettings, $key ); }
	public function get_defaultUserSettings( $key = false ) { return VAA_API::get_array_data( $this->defaultUserSettings, $key ); }
	public function get_allowedUserSettings( $key = false ) { return VAA_API::get_array_data( $this->allowedUserSettings, $key ); }
	public function get_settings( $key = false )            {
		return VAA_API::get_array_data( $this->validate_settings( $this->get_optionData( 'settings' ), 'global' ), $key );
	}
	public function get_userSettings( $key = false )        {
		return VAA_API::get_array_data( $this->validate_settings( $this->get_userMeta( 'settings' ), 'user' ), $key );
	}

	public function get_version()                           { return strtolower( (string) VIEW_ADMIN_AS_VERSION ); }
	public function get_dbVersion()                         { return strtolower( (string) VIEW_ADMIN_AS_DB_VERSION ); }

	/*
	 * Setters
	 */
	public function set_viewAs( $var, $key = false, $append = false )   { $this->viewAs = VAA_API::set_array_data( $this->viewAs, $var, $key, $append ); }
	public function set_caps( $var, $key = false, $append = false )     { $this->caps   = VAA_API::set_array_data( $this->caps, $var, $key, $append ); }
	public function set_roles( $var, $key = false, $append = false )    { $this->roles  = VAA_API::set_array_data( $this->roles, $var, $key, $append ); }
	public function set_users( $var, $key = false, $append = false )    { $this->users  = VAA_API::set_array_data( $this->users, $var, $key, $append ); }
	public function set_curUser( $var )                                 { $this->curUser = $var; }
	public function set_curUserSession( $var )                          { $this->curUserSession = (string) $var; }
	public function set_nonce( $var )                                   { $this->nonce = (string) $var; }
	public function set_userids( $var )                                 { $this->userids = array_map( 'strval', (array) $var ); }
	public function set_usernames( $var )                               { $this->usernames = array_map( 'strval', (array) $var ); }
	public function set_selectedUser( $var )                            { $this->selectedUser = $var; }
	public function set_defaultSettings( $var )                         { $this->defaultSettings = array_map( 'strval', (array) $var ); }
	public function set_allowedSettings( $var )                         { $this->allowedSettings = $var; }
	public function set_defaultUserSettings( $var )                     { $this->defaultUserSettings = array_map( 'strval', (array) $var ); }
	public function set_allowedUserSettings( $var )                     { $this->allowedUserSettings = $var; }
	public function set_settings( $var, $key = false, $append = false ) {
		$this->set_optionData( $this->validate_settings( VAA_API::set_array_data( $this->get_settings(), $var, $key, $append ), 'global' ), 'settings', true );
	}
	public function set_userSettings( $var, $key = false, $append = false ) {
		$this->set_userMeta( $this->validate_settings( VAA_API::set_array_data( $this->get_userSettings(), $var, $key, $append ), 'user' ), 'settings', true );
	}
	public function set_optionData( $var, $key = false, $append = false ) { $this->optionData = VAA_API::set_array_data( $this->optionData, $var, $key, $append ); }
	public function set_userMeta( $var, $key = false, $append = false ) { $this->userMeta = VAA_API::set_array_data( $this->userMeta, $var, $key, $append ); }

	/*
	 * Update
	 */
	public function update_optionData( $var, $key = false, $append = false ) {
		$this->set_optionData( $var, $key, $append );
		return update_option( $this->get_optionKey(), $this->get_optionData() );
	}
	public function update_userMeta( $var, $key = false, $append = false ) {
		$this->set_userMeta( $var, $key, $append );
		return update_user_meta( $this->get_curUser()->ID, $this->get_userMetaKey(), $this->get_userMeta() );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   object|bool  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Store|bool
	 */
	public static function get_instance( $caller = false ) {
		if ( is_object( $caller ) && in_array( get_class( $caller ), self::$vaa_class_names ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		return false;
	}

	/**
	 * Magic method to output a string if trying to use the object as a string.
	 *
	 * @since  1.6
	 * @access public
	 * @return string
	 */
	public function __toString() {
		return get_class( $this );
	}

	/**
	 * Magic method to keep the object from being cloned.
	 *
	 * @since  1.6
	 * @access public
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			get_class( $this ) . ': ' . esc_html__( 'This class does not want to be cloned', 'view-admin-as' ),
			null
		);
	}

	/**
	 * Magic method to keep the object from being unserialized.
	 *
	 * @since  1.6
	 * @access public
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			get_class( $this ) . ': ' . esc_html__( 'This class does not want to wake up', 'view-admin-as' ),
			null
		);
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since  1.6
	 * @access public
	 * @param  string
	 * @param  array
	 * @return null
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong(
			get_class( $this ) . "::{$method}",
			esc_html__( 'Method does not exist.', 'view-admin-as' ),
			null
		);
		unset( $method, $args );
		return null;
	}

} // end class
