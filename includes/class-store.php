<?php
/**
 * View Admin As - Class Store
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * Store class that stores the VAA data for use
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.6.4
 */
final class VAA_View_Admin_As_Store
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_Store
	 */
	private static $_instance = null;

	/**
	 * The nonce.
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class.
	 * @var    string
	 */
	private $nonce = '';

	/**
	 * The parsed nonce.
	 *
	 * @since  1.6.2
	 * @var    string
	 */
	private $nonce_parsed = '';

	/**
	 * Database option key.
	 *
	 * @since  1.4
	 * @since  1.6    Moved to this class from main class.
	 * @var    string
	 */
	private $optionKey = 'vaa_view_admin_as';

	/**
	 * Database option data.
	 *
	 * @since  1.4
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $optionData = array(
		'db_version',
	);

	/**
	 * User meta key for settings ans views.
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class.
	 * @var    bool
	 */
	private $userMetaKey = 'vaa-view-admin-as';

	/**
	 * User meta value for settings ans views.
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $userMeta = array(
		'settings',
		'views',
	);

	/**
	 * Array of default settings.
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $defaultSettings = array();

	/**
	 * Array of allowed settings.
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $allowedSettings = array();

	/**
	 * Array of default settings.
	 *
	 * @since  1.5
	 * @since  1.5.2  Added force_group_users.
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.1  Added freeze_locale.
	 * @var    array
	 */
	private $defaultUserSettings = array(
		'admin_menu_location' => 'top-secondary',
		'force_group_users'   => 'no',
		'freeze_locale'       => 'no',
		'hide_front'          => 'no',
		'view_mode'           => 'browse',
	);

	/**
	 * Array of allowed settings.
	 * Setting name (key) => array( values ).
	 *
	 * @since  1.5
	 * @since  1.5.2  Added force_group_users.
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.1  Added freeze_locale.
	 * @var    array
	 */
	private $allowedUserSettings = array(
		'admin_menu_location' => array( 'top-secondary', 'my-account' ),
		'force_group_users'   => array( 'yes', 'no' ),
		'freeze_locale'       => array( 'yes', 'no' ),
		'hide_front'          => array( 'yes', 'no' ),
		'view_mode'           => array( 'browse', 'single' ),
	);

	/**
	 * Array of available capabilities.
	 *
	 * @since  1.3
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $caps = array();

	/**
	 * Array of available roles (WP_Role objects).
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $roles = array();

	/**
	 * Array of translated role names.
	 *
	 * @since  1.6.4
	 * @var    array
	 */
	private $rolenames = array();

	/**
	 * Array of available users (WP_User objects).
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $users = array();

	/**
	 * Array of available user ID's (key) and display names (value).
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $userids = array();

	/**
	 * Current user object.
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class.
	 * @var    WP_User
	 */
	private $curUser;

	/**
	 * Current user session.
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class.
	 * @var    string
	 */
	private $curUserSession = '';

	/**
	 * Current user data.
	 * Will contain all properties of the original current user object.
	 *
	 * @since  1.6.3
	 * @var    array
	 */
	private static $curUserData = array();

	/**
	 * Is the original current user a super admin?
	 *
	 * @since  1.6.3
	 * @var    bool
	 */
	private static $isCurUserSuperAdmin = false;

	/**
	 * Selected view data as stored in the user meta.
	 * Format: array( VIEW_TYPE => VIEW_DATA ).
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class.
	 * @var    array
	 */
	private $view = array();

	/**
	 * The selected user object (if a view is selected).
	 * Can be the same as $curUser depending on the selected view.
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class.
	 * @var    WP_User
	 */
	private $selectedUser;

	/**
	 * The selected capabilities (if a view is selected).
	 *
	 * @since  1.6.2
	 * @var    array
	 */
	private $selectedCaps = array();

	/**
	 * Populate the instance.
	 * @since  1.6
	 */
	private function __construct() {
		self::$_instance = $this;
	}

	/**
	 * Store the current user and other user related data.
	 *
	 * @since   1.6.3  Moved to this class.
	 * @access  public
	 * @param   bool  $redo  (optional) Force re-init?
	 */
	public function init( $redo = false ) {
		static $done = false;
		if ( ( $done && ! $redo ) ) return;

		$this->set_nonce( 'view-admin-as' );

		// Get the current user.
		$this->set_curUser( wp_get_current_user() );

		// Get the current user session.
		if ( function_exists( 'wp_get_session_token' ) ) {
			// WP 4.0+.
			$this->set_curUserSession( (string) wp_get_session_token() );
		} else {
			$cookie = wp_parse_auth_cookie( '', 'logged_in' );
			if ( ! empty( $cookie['token'] ) ) {
				$this->set_curUserSession( (string) $cookie['token'] );
			} else {
				// Fallback. This disables the use of multiple views in different sessions.
				$this->set_curUserSession( $this->get_curUser()->ID );
			}
		}

		if ( is_super_admin( $this->get_curUser()->ID ) ) {
			self::$isCurUserSuperAdmin = true;
		}

		self::$curUserData = get_object_vars( $this->get_curUser() );

		// Get database settings.
		$this->set_optionData( get_option( $this->get_optionKey() ) );
		// Get database settings of the current user.
		$this->set_userMeta( get_user_meta( $this->get_curUser()->ID, $this->get_userMetaKey(), true ) );

		$done = true;
	}

	/**
	 * Store available roles.
	 *
	 * @since   1.5
	 * @since   1.5.2  Get role objects instead of arrays.
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 * @global  WP_Roles  $wp_roles
	 * @return  void
	 */
	public function store_roles() {

		// @since  1.6.3  Check for the wp_roles() function in WP 4.3+.
		if ( function_exists( 'wp_roles' ) ) {
			$wp_roles = wp_roles();
		} else {
			global $wp_roles;
		}

		// Store available roles (role_objects for objects, roles for arrays).
		$roles = $wp_roles->role_objects;

		if ( ! self::is_super_admin() ) {

			// The current user is not a super admin (or regular admin in single installations).
			unset( $roles['administrator'] );

			// @see   https://codex.wordpress.org/Plugin_API/Filter_Reference/editable_roles.
			$editable_roles = apply_filters( 'editable_roles', $wp_roles->roles );

			// Current user has the view_admin_as capability, otherwise this functions would'nt be called.
			foreach ( $roles as $role_key => $role ) {
				if ( ! array_key_exists( $role_key, $editable_roles ) ) {
					// Remove roles that this user isn't allowed to edit.
					unset( $roles[ $role_key ] );
				}
				elseif ( $role instanceof WP_Role && $role->has_cap( 'view_admin_as' ) ) {
					// Remove roles that have the view_admin_as capability.
					unset( $roles[ $role_key ] );
				}
			}
		}

		// @since  1.6.4  Set role names
		$role_names = array();
		foreach ( $roles as $role_key => $role ) {
			if ( isset( $wp_roles->role_names[ $role_key ] ) ) {
				$role_names[ $role_key ] = $wp_roles->role_names[ $role_key ];
			} else {
				$role_names[ $role_key ] = $role->name;
			}
		}

		$this->set_rolenames( $role_names );
		$this->set_roles( $roles );
	}

	/**
	 * Store available users.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @since   1.6.2  Reduce user queries to 1 for non-network pages with custom query handling.
	 * @access  public
	 * @global  wpdb  $wpdb
	 * @return  void
	 */
	public function store_users() {
		global $wpdb;

		$super_admins = get_super_admins();
		// Load the superior admins.
		$superior_admins = VAA_API::get_superior_admins();

		// Is the current user a super admin?
		$is_super_admin = self::is_super_admin();
		// Is it also one of the manually configured superior admins?
		$is_superior_admin = VAA_API::is_superior_admin();

		/**
		 * Base user query.
		 * Also gets the roles from the user meta table.
		 * Reduces queries to 1 when getting the available users.
		 *
		 * @since  1.6.2
		 * @todo   Use it for network pages as well?
		 * @todo   Check options https://github.com/JoryHogeveen/view-admin-as/issues/24.
		 */
		$user_query = array(
			'select'    => "SELECT users.*, usermeta.meta_value AS roles",
			'from'      => "FROM {$wpdb->users} users",
			'left_join' => "INNER JOIN {$wpdb->usermeta} usermeta ON ( users.ID = usermeta.user_id )",
			'where'     => "WHERE ( usermeta.meta_key = '{$wpdb->get_blog_prefix()}capabilities' )",
			'order_by'  => "ORDER BY users.display_name ASC",
		);

		if ( is_network_admin() ) {

			/**
			 * Super admins are only available for superior admins.
			 * (short circuit return for performance).
			 * @since  1.6.3
			 */
			if ( ! $is_superior_admin ) {
				return;
			}

			// Get super admins (returns login's).
			$users = $super_admins;
			// Remove current user.
			if ( in_array( $this->get_curUser()->user_login, $users, true ) ) {
				unset( $users[ array_search( $this->get_curUser()->user_login, $users, true ) ] );
			}

			// Convert login to WP_User objects and filter them for superior admins.
			foreach ( $users as $key => $user_login ) {
				$user = get_user_by( 'login', $user_login );
				// Compare user ID with superior admins array.
				if ( $user && ! in_array( (int) $user->ID, $superior_admins, true ) ) {
					$users[ $key ] = $user;
				} else {
					unset( $users[ $key ] );
				}
			}

			// @todo Maybe build network super admins where clause for SQL instead of `get_user_by`.

			/*
			if ( ! empty( $users ) && $include = implode( ',', array_map( 'strval', $users ) ) ) {
				$user_query['where'] .= " AND users.user_login IN ({$include})";
			}
			*/

		} else {

			/**
			 * Exclude current user and superior admins (values are user ID's).
			 *
			 * @since  1.5.2  Exclude the current user.
			 * @since  1.6.2  Exclude in SQL format.
			 */
			$exclude = implode( ',',
				array_unique(
					array_map( 'absint',
						array_merge( $superior_admins, array( $this->get_curUser()->ID ) )
					)
				)
			);
			$user_query['where'] .= " AND users.ID NOT IN ({$exclude})";

			/**
			 * Do not get regular admins for normal installs.
			 *
			 * @since  1.5.2  WP 4.4+ only >> ( 'role__not_in' => 'administrator' ).
			 * @since  1.6.2  Exclude in SQL format (Not WP dependent).
			 */
			if ( ! is_multisite() && ! $is_superior_admin ) {
				$user_query['where'] .= " AND usermeta.meta_value NOT LIKE '%administrator%'";
			}

			/**
			 * Do not get super admins for network installs (values are usernames).
			 * These we're filtered after query in previous versions.
			 *
			 * @since  1.6.3
			 */
			if ( is_multisite() && ! $is_superior_admin ) {
				if ( is_array( $super_admins ) && ! empty( $super_admins[0] ) ) {

					// Escape usernames just to be sure.
					$super_admins = array_filter( $super_admins, 'validate_username' );
					// Pre WP 4.4 - Remove empty usernames since these return true before WP 4.4.
					$super_admins = array_filter( $super_admins );

					$exclude_siblings = "'" . implode( "','", $super_admins ) . "'";
					$user_query['where'] .= " AND users.user_login NOT IN ({$exclude_siblings})";
				}
			}

			// Run query (OBJECT_K to set the user ID as key).
			$users_results = $wpdb->get_results( implode( ' ', $user_query ), OBJECT_K );

			if ( $users_results ) {

				$users = array();
				// Temp set users.
				$this->set_users( $users_results );
				// @hack  Short circuit the meta queries (not needed).
				add_filter( 'get_user_metadata', array( $this, '_filter_get_user_capabilities' ), 10, 3 );

				// Turn query results into WP_User objects.
				foreach ( $users_results as $user ) {
					$user->roles = unserialize( $user->roles );
					$users[ $user->ID ] = new WP_User( $user );
				}

				// @hack  Restore the default meta queries.
				remove_filter( 'get_user_metadata', array( $this, '_filter_get_user_capabilities' ) );
				// Clear temp users.
				$this->set_users( array() );

			} else {

				// Fallback to WP native functions.
				$user_args = array(
					'orderby' => 'display_name',
					// @since  1.5.2  Exclude the current user.
					'exclude' => array_merge( $superior_admins, array( $this->get_curUser()->ID ) ),
				);
				// @since  1.5.2  Do not get regular admins for normal installs (WP 4.4+).
				if ( ! is_multisite() && ! $is_superior_admin ) {
					$user_args['role__not_in'] = 'administrator';
				}

				$users = get_users( $user_args );
			}

			// Sort users by role and filter them on available roles.
			$users = $this->filter_sort_users_by_role( $users );
		}

		// @todo Maybe $userids isn't needed anymore
		$userids = array();

		foreach ( $users as $user_key => $user ) {

			// If the current user is not a superior admin, run the user filters.
			if ( true !== $is_superior_admin ) {

				/**
				 * Implement in_array() on get_super_admins() check instead of is_super_admin().
				 * Reduces the amount of queries while the end result is the same.
				 *
				 * @since  1.5.2
				 * @See    wp-includes/capabilities.php >> get_super_admins()
				 * @See    wp-includes/capabilities.php >> is_super_admin()
				 * @link   https://developer.wordpress.org/reference/functions/is_super_admin/
				 */
				if ( is_multisite() && in_array( $user->user_login, (array) $super_admins, true ) ) {
					// Remove super admins for multisites.
					unset( $users[ $user_key ] );
					continue;
				} elseif ( ! is_multisite() && $user->has_cap( 'administrator' ) ) {
					// Remove regular admins for normal installs.
					unset( $users[ $user_key ] );
					continue;
				} elseif ( ! $is_super_admin && $user->has_cap( 'view_admin_as' ) ) {
					// Remove users who can access this plugin for non-admin users with the view_admin_as capability.
					unset( $users[ $user_key ] );
					continue;
				}
			}

			// Add users who can't access this plugin to the users list.
			$userids[ $user->ID ] = $user->display_name;
		}

		$this->set_users( $users );
		$this->set_userids( $userids );
	}

	/**
	 * Filter the WP_User object construction to short circuit the extra meta queries.
	 *
	 * FOR INTERNAL USE ONLY!!!
	 * @hack
	 * @internal
	 *
	 * @since   1.6.2
	 * @see     wp-includes/class-wp-user.php WP_User->_init_caps()
	 * @see     get_user_metadata filter in get_metadata()
	 * @link    https://developer.wordpress.org/reference/functions/get_metadata/
	 *
	 * @global  wpdb    $wpdb
	 * @param   null    $null      The value get_metadata() should return.
	 * @param   int     $user_id   Object ID.
	 * @param   string  $meta_key  Meta key.
	 * @return  mixed
	 */
	public function _filter_get_user_capabilities( $null, $user_id, $meta_key ) {
		global $wpdb;
		if ( $wpdb->get_blog_prefix() . 'capabilities' === $meta_key && array_key_exists( $user_id, $this->get_users() ) ) {

			$roles = $this->get_users( $user_id )->roles;
			if ( is_string( $roles ) ) {
				// It is still raw DB data, unserialize it.
				$roles = unserialize( $roles );
			}

			// Always return an array format due to $single handling (unused 4th parameter).
			return array( $roles );
		}
		return $null;
	}

	/**
	 * Sort users by role.
	 *
	 * @since   1.1
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 *
	 * @see     store_users()
	 *
	 * @param   array  $users  Array of user objects (WP_User).
	 * @return  array  $users
	 */
	public function filter_sort_users_by_role( $users ) {
		if ( ! $this->get_roles() ) {
			return $users;
		}
		$tmp_users = array();
		foreach ( $this->get_roles() as $role => $role_data ) {
			foreach ( $users as $user ) {
				// Reset the array to make sure we find a key.
				// Only one key is needed to add the user to the list of available users.
				reset( $user->roles );
				if ( current( $user->roles ) === $role ) {
					$tmp_users[] = $user;
				}
			}
		}
		$users = $tmp_users;
		return $users;
	}

	/**
	 * Store available capabilities.
	 *
	 * @since   1.4.1
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 * @global  WP_Roles  $wp_roles
	 * @return  void
	 */
	public function store_caps() {

		// Get all available roles and capabilities.
		global $wp_roles;

		// Get current user capabilities.
		$caps = self::get_originalUserData( 'allcaps' );
		if ( empty( $caps ) ) {
			// Fallback.
			$caps = $this->get_curUser()->allcaps;
		}

		// Only allow to add capabilities for an admin (or super admin).
		if ( self::is_super_admin() ) {

			// Store available capabilities.
			$all_caps = array();
			foreach ( $wp_roles->role_objects as $key => $role ) {
				if ( is_array( $role->capabilities ) ) {
					foreach ( $role->capabilities as $cap => $grant ) {
						$all_caps[ $cap ] = $cap;
					}
				}
			}

			/**
			 * Add compatibility for other cap managers.
			 *
			 * @since  1.5
			 * @see    VAA_View_Admin_As_Compat->init()
			 * @param  array  $all_caps  All capabilities found in the existing roles.
			 * @return array
			 */
			$all_caps = apply_filters( 'view_admin_as_get_capabilities', $all_caps );

			$all_caps = array_unique( $all_caps );

			// Add new capabilities to the capability array as disabled.
			foreach ( $all_caps as $cap_key => $cap_val ) {
				if ( is_string( $cap_val ) && ! is_numeric( $cap_val ) && ! array_key_exists( $cap_val, $caps ) ) {
					$caps[ $cap_val ] = 0;
				}
				if ( is_string( $cap_key ) && ! is_numeric( $cap_key ) && ! array_key_exists( $cap_key, $caps ) ) {
					$caps[ $cap_key ] = 0;
				}
			}

			/**
			 * Add network capabilities.
			 *
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

		// Remove role names.
		foreach ( $wp_roles->roles as $role_key => $role ) {
			unset( $caps[ $role_key ] );
		}
		ksort( $caps );

		$this->set_caps( $caps );
	}

	/**
	 * Store settings based on allowed settings.
	 * Also merges with the default settings.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 *
	 * @param   array   $settings  The new settings.
	 * @param   string  $type      The type of settings (global / user).
	 * @return  bool
	 */
	public function store_settings( $settings, $type ) {
		if ( 'global' === $type ) {
			$current  = $this->get_settings();
			$defaults = $this->get_defaultSettings();
			$allowed  = $this->get_allowedSettings();
		} elseif ( 'user' === $type ) {
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
			// Only allow the settings when it exists in the defaults and the value exists in the allowed settings.
			if ( array_key_exists( $setting, $defaults ) && in_array( $value, $allowed[ $setting ], true ) ) {
				$current[ $setting ] = $value;
				// Some settings need a reset.
				if ( in_array( $setting, array( 'view_mode' ), true ) ) {
					view_admin_as( $this )->view()->reset_view();
				}
			}
		}
		if ( 'global' === $type ) {
			$new = $this->validate_settings( wp_parse_args( $current, $defaults ), 'global' );
			return $this->update_optionData( $new, 'settings', true );
		} elseif ( 'user' === $type ) {
			$new = $this->validate_settings( wp_parse_args( $current, $defaults ), 'user' );
			return $this->update_userMeta( $new, 'settings', true );
		}
		return false;
	}

	/**
	 * Validate setting data based on allowed settings.
	 * Also merges with the default settings.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 *
	 * @param   array       $settings  The new settings.
	 * @param   string      $type      The type of settings (global / user).
	 * @return  array|bool  $settings / false
	 */
	public function validate_settings( $settings, $type ) {
		if ( 'global' === $type ) {
			$defaults = $this->get_defaultSettings();
			$allowed  = $this->get_allowedSettings();
		} elseif ( 'user' === $type ) {
			$defaults = $this->get_defaultUserSettings();
			$allowed  = $this->get_allowedUserSettings();
		} else {
			return false;
		}
		$settings = wp_parse_args( $settings, $defaults );
		foreach ( $settings as $setting => $value ) {
			if ( ! array_key_exists( $setting, $defaults ) ) {
				// We don't have such a setting.
				unset( $settings[ $setting ] );
			} elseif ( ! in_array( $value, $allowed[ $setting ], true ) ) {
				// Set it to default.
				$settings[ $setting ] = $defaults[ $setting ];
			}
		}
		return $settings;
	}

	/**
	 * Delete all View Admin As metadata for this user.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @since   1.6.2  Option to remove the VAA metadata for all users.
	 * @access  public
	 *
	 * @global  wpdb        $wpdb
	 * @param   int|string  $user_id     ID of the user being deleted/removed (pass `all` for all users).
	 * @param   object      $user        User object provided by the wp_login hook.
	 * @param   bool        $reset_only  Only reset (not delete) the user meta.
	 * @return  bool
	 */
	public function delete_user_meta( $user_id = null, $user = null, $reset_only = true ) {
		global $wpdb;

		/**
		 * Set the first parameter to `all` to remove the meta value for all users.
		 *
		 * @since  1.6.2
		 * @see    https://developer.wordpress.org/reference/classes/wpdb/update/
		 * @see    https://developer.wordpress.org/reference/classes/wpdb/delete/
		 */
		if ( 'all' === $user_id ) {
			if ( $reset_only ) {
				return (bool) $wpdb->update(
					$wpdb->usermeta, // table.
					array( 'meta_value', false ), // data.
					array( 'meta_key' => $this->get_userMetaKey() ) // where.
				);
			} else {
				return (bool) $wpdb->delete(
					$wpdb->usermeta, // table.
					array( 'meta_key' => $this->get_userMetaKey() ) // where.
				);
			}
		}

		$id = false;
		if ( is_numeric( $user_id ) ) {
			// Delete hooks.
			$id = (int) $user_id;
		} elseif ( isset( $user->ID ) ) {
			// Login/Logout hooks.
			$id = (int) $user->ID;
		}
		if ( $id ) {
			$success = true;
			if ( $reset_only ) {
				// Reset db metadata (returns: true on success, false on failure).
				if ( get_user_meta( $id, $this->get_userMetaKey() ) ) {
					$success = update_user_meta( $id, $this->get_userMetaKey(), false );
				}
			} else {
				// Remove db metadata (returns: true on success, false on failure).
				$success = delete_user_meta( $id, $this->get_userMetaKey() );
			}
			// Update current metadata if it is the current user.
			if ( $success && (int) $this->get_curUser()->ID === $id ) {
				$this->set_userMeta( false );
			}

			return $success;
		}
		// No user or metadata found, no deletion needed
		return true;
	}

	/**
	 * Helper function for is_super_admin().
	 * Will validate the original user if it is the current user or no user ID is passed.
	 * This can prevent invalid checks after a view is applied.
	 *
	 * @since   1.6.3
	 * @access  public
	 * @static
	 * @param   int  $user_id  (optional).
	 * @return  bool
	 */
	public static function is_super_admin( $user_id = null ) {
		if ( null === $user_id || (int) get_current_user_id() === (int) $user_id ) {
			return self::$isCurUserSuperAdmin;
		}
		return is_super_admin( $user_id );
	}

	/**
	 * Get data from the current user, similar to the WP_User object.
	 * Unlike the current user object this data isn't modified after in a view.
	 * This has all public WP_User properties stored as an array.
	 *
	 * @since   1.6.3
	 * @access  public
	 * @static
	 * @param   string  $key  (optional).
	 * @return  mixed
	 */
	public static function get_originalUserData( $key = null ) {
		return VAA_API::get_array_data( self::$curUserData, $key );
	}

	/**
	 * Get current user.
	 * @return  WP_User  $curUser  Current user object.
	 */
	public function get_curUser() {
		return $this->curUser;
	}

	/**
	 * Get current user session.
	 * @return  string
	 */
	public function get_curUserSession() {
		return (string) $this->curUserSession;
	}

	/**
	 * Get view data (meta).
	 * @since   1.6.x
	 * @param   string  $key  Key for array.
	 * @return  mixed
	 */
	public function get_view( $key = null ) {
		return VAA_API::get_array_data( $this->view, $key );
	}

	/**
	 * Get view data (meta).
	 * @todo    Remove in future.
	 * @deprecated
	 * @param   string  $key  Key for array.
	 * @return  mixed
	 */
	public function get_viewAs( $key = null ) {
		_deprecated_function( __METHOD__, '1.6.x', 'VAA_View_Admin_As_Store::get_view()' );
		return $this->get_view( $key );
	}

	/**
	 * Get available capabilities.
	 * @param   string  $key  Cap name.
	 * @return  mixed   Array of capabilities or a single capability value.
	 */
	public function get_caps( $key = null ) {
		return VAA_API::get_array_data( $this->caps, $key );
	}

	/**
	 * Get available roles.
	 * @param   string  $key  Role slug/key.
	 * @return  mixed   Array of role objects or a single role object.
	 */
	public function get_roles( $key = null ) {
		return VAA_API::get_array_data( $this->roles, $key );
	}

	/**
	 * Get the role names. Translated by default.
	 * If key is provided but not found it will return the key (untranslated).
	 * @since   1.6.4
	 * @param   string  $key        Role slug.
	 * @param   bool    $translate  Translate the role name?
	 * @return  array|string
	 */
	public function get_rolenames( $key = null, $translate = true ) {
		$val = VAA_API::get_array_data( $this->rolenames, $key );
		if ( ! $val ) {
			return ( $key ) ? $key : $val;
		}
		if ( ! $translate ) {
			return $val;
		}
		if ( is_array( $val ) ) {
			return array_map( 'translate_user_role', $val );
		}
		return translate_user_role( $val );
	}

	/**
	 * Get available users.
	 * @todo Key as user ID.
	 * @param   string  $key  User key.
	 * @return  mixed   Array of user objects or a single user object.
	 */
	public function get_users( $key = null ) {
		return VAA_API::get_array_data( $this->users, $key );
	}

	/**
	 * Get available users.
	 * @param   string  $key  User key.
	 * @return  mixed   Array of user display names or a single user display name.
	 */
	public function get_userids( $key = null ) {
		return VAA_API::get_array_data( $this->userids, $key );
	}

	/**
	 * Get selected capabilities of a view.
	 * @param   string  $key  Cap name.
	 * @return  mixed   Array of capabilities or a single capability value.
	 */
	public function get_selectedCaps( $key = null ) {
		return VAA_API::get_array_data( $this->selectedCaps, $key );
	}

	/**
	 * Get the selected user object of a view.
	 * @return  WP_User
	 */
	public function get_selectedUser() {
		return $this->selectedUser;
	}

	/**
	 * Get the option key as used in the options table.
	 * @return  string
	 */
	public function get_optionKey() {
		return (string) $this->optionKey;
	}

	/**
	 * Get the option data as used in the options table.
	 * @param   string  $key  Key in the option array.
	 * @return  mixed
	 */
	public function get_optionData( $key = null ) {
		return VAA_API::get_array_data( $this->optionData, $key );
	}

	/**
	 * Get the user meta key as used in the usermeta table.
	 * @return  string
	 */
	public function get_userMetaKey() {
		return (string) $this->userMetaKey;
	}

	/**
	 * Get the user metadata as used in the usermeta table.
	 * @param   string  $key  Key in the meta array.
	 * @return  mixed
	 */
	public function get_userMeta( $key = null ) {
		return VAA_API::get_array_data( $this->userMeta, $key );
	}

	/**
	 * Get the default settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_defaultSettings( $key = null ) {
		return VAA_API::get_array_data( $this->defaultSettings, $key );
	}

	/**
	 * Get the default user settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_defaultUserSettings( $key = null ) {
		return VAA_API::get_array_data( $this->defaultUserSettings, $key );
	}

	/**
	 * Get the allowed settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_allowedSettings( $key = null ) {
		return (array) VAA_API::get_array_data( $this->allowedSettings, $key );
	}

	/**
	 * Get the allowed user settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_allowedUserSettings( $key = null ) {
		return (array) VAA_API::get_array_data( $this->allowedUserSettings, $key );
	}

	/**
	 * Get the nonce.
	 * @param   string  $parsed  Return parsed nonce?
	 * @return  string
	 */
	public function get_nonce( $parsed = null ) {
		return ( $parsed ) ? $this->nonce_parsed : $this->nonce;
	}

	/**
	 * Get the settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_settings( $key = null ) {
		return VAA_API::get_array_data(
			$this->validate_settings(
				$this->get_optionData( 'settings' ),
				'global'
			),
			$key
		);
	}

	/**
	 * Get the user settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_userSettings( $key = null ) {
		return VAA_API::get_array_data(
			$this->validate_settings(
				$this->get_userMeta( 'settings' ),
				'user'
			),
			$key
		);
	}

	/**
	 * Get plugin version.
	 * @return  string
	 */
	public function get_version() {
		return strtolower( (string) VIEW_ADMIN_AS_VERSION );
	}

	/**
	 * Get plugin database version.
	 * @return  string
	 */
	public function get_dbVersion() {
		return strtolower( (string) VIEW_ADMIN_AS_DB_VERSION );
	}

	/**
	 * Set the view data.
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) View key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_view( $val, $key = null, $append = false ) {
		$this->view = VAA_API::set_array_data( $this->view, $val, $key, $append );
	}

	/**
	 * Set the view data.
	 * @todo    Remove in future.
	 * @deprecated
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) View key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_viewAs( $val, $key = null, $append = false ) {
		_deprecated_function( __METHOD__, '1.6.x', 'VAA_View_Admin_As_Store::set_view()' );
		$this->set_view( $val, $key, $append );
	}

	/**
	 * Set the available capabilities.
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) Cap key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_caps( $val, $key = null, $append = false ) {
		$this->caps = VAA_API::set_array_data( $this->caps, $val, $key, $append );
	}

	/**
	 * Set the available roles.
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) Role name.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_roles( $val, $key = null, $append = false ) {
		$this->roles = VAA_API::set_array_data( $this->roles, $val, $key, $append );
	}

	/**
	 * Set the role name translations.
	 * @since   1.6.4
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) Role name.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_rolenames( $val, $key = null, $append = false ) {
		$this->rolenames = VAA_API::set_array_data( $this->rolenames, $val, $key, $append );
	}

	/**
	 * Set the available users.
	 * @todo Key as user ID.
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) User key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_users( $val, $key = null, $append = false ) {
		$this->users = VAA_API::set_array_data( $this->users, $val, $key, $append );
	}

	/**
	 * Set the available user display names.
	 * @param   array  $val  Array of available user ID's (key) and display names (value).
	 * @return  void
	 */
	public function set_userids( $val ) {
		$this->userids = array_map( 'strval', (array) $val );
	}

	/**
	 * Set the current user object.
	 * @param   WP_User  $val  User object.
	 * @return  void
	 */
	public function set_curUser( $val ) {
		$this->curUser = $val;
	}

	/**
	 * Set the current user session.
	 * @param   string  $val  User session ID.
	 * @return  void
	 */
	public function set_curUserSession( $val ) {
		$this->curUserSession = (string) $val;
	}

	/**
	 * Set the selected user object for the current view.
	 * @param   WP_User  $val  User object.
	 * @return  void
	 */
	public function set_selectedUser( $val ) {
		$this->selectedUser = $val;
	}

	/**
	 * Set the selected capabilities for the current view.
	 * @param   array  $val  Selected capabilities.
	 * @return  void
	 */
	public function set_selectedCaps( $val ) {
		$this->selectedCaps = array_filter( (array) $val );
	}

	/**
	 * Set the default settings.
	 * @param   array  $val  Settings.
	 * @return  void
	 */
	public function set_defaultSettings( $val ) {
		$this->defaultSettings = array_map( 'strval', (array) $val );
	}

	/**
	 * Set the default user settings.
	 * @param   array  $val  Settings.
	 * @return  void
	 */
	public function set_defaultUserSettings( $val ) {
		$this->defaultUserSettings = array_map( 'strval', (array) $val );
	}

	/**
	 * Set the nonce.
	 * Also sets a parsed version of the nonce with wp_create_nonce()
	 * @param   string  $val  Nonce.
	 * @return  void
	 */
	public function set_nonce( $val ) {
		$this->nonce = (string) $val;
		$this->nonce_parsed = wp_create_nonce( (string) $val );
	}

	/**
	 * Set the allowed settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_allowedSettings( $val, $key = null, $append = false ) {
		$this->allowedSettings = VAA_API::set_array_data( $this->allowedSettings, $val, $key, $append );
	}

	/**
	 * Set the allowed user settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_allowedUserSettings( $val, $key = null, $append = false ) {
		$this->allowedUserSettings = VAA_API::set_array_data( $this->allowedUserSettings, $val, $key, $append );
	}

	/**
	 * Set the settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_settings( $val, $key = null, $append = false ) {
		$this->set_optionData(
			$this->validate_settings(
				VAA_API::set_array_data( $this->get_settings(), $val, $key, $append ),
				'global'
			),
			'settings',
			true
		);
	}

	/**
	 * Set the user settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_userSettings( $val, $key = null, $append = false ) {
		$this->set_userMeta(
			$this->validate_settings(
				VAA_API::set_array_data( $this->get_userSettings(), $val, $key, $append ),
				'user'
			),
			'settings',
			true
		);
	}

	/**
	 * Set the plugin option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_optionData( $val, $key = null, $append = false ) {
		$this->optionData = VAA_API::set_array_data( $this->optionData, $val, $key, $append );
	}

	/**
	 * Set the user metadata.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_userMeta( $val, $key = null, $append = false ) {
		$this->userMeta = VAA_API::set_array_data( $this->userMeta, $val, $key, $append );
	}

	/**
	 * Update the plugin option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  bool
	 */
	public function update_optionData( $val, $key = null, $append = false ) {
		$this->set_optionData( $val, $key, $append );
		return update_option( $this->get_optionKey(), $this->get_optionData() );
	}

	/**
	 * Update the user metadata.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  bool
	 */
	public function update_userMeta( $val, $key = null, $append = false ) {
		$this->set_userMeta( $val, $key, $append );
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
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  VAA_View_Admin_As_Store
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' === get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $caller );
			}
			return self::$_instance;
		}
		return null;
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
			esc_html( get_class( $this ) . ': ' . __( 'This class does not want to be cloned', VIEW_ADMIN_AS_DOMAIN ) ),
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
			esc_html( get_class( $this ) . ': ' . __( 'This class does not want to wake up', VIEW_ADMIN_AS_DOMAIN ) ),
			null
		);
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since  1.6
	 * @access public
	 * @param  string  $method  The method name.
	 * @param  array   $args    The method arguments.
	 * @return null
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong(
			esc_html( get_class( $this ) . "::{$method}" ),
			esc_html__( 'Method does not exist.', VIEW_ADMIN_AS_DOMAIN ),
			null
		);
		unset( $method, $args );
		return null;
	}

} // end class.
