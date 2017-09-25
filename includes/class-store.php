<?php
/**
 * View Admin As - Class Store
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Store class that stores the VAA data for use.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.7.4
 * @uses    VAA_View_Admin_As_Settings Extends class
 */
final class VAA_View_Admin_As_Store extends VAA_View_Admin_As_Settings
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
	 * View type data.
	 * You can add custom view data with VAA_View_Admin_As_Store::set_data().
	 *
	 * @see    VAA_View_Admin_As_Store::set_data()
	 * @since  1.7
	 * @var    array {
	 *     Default view data.
	 *     @type  bool[]      $caps       Since 1.3    Array of available capabilities.
	 *     @type  \WP_Role[]  $roles      Since 0.1    Array of available roles (WP_Role objects).
	 *     @type  string[]    $rolenames  Since 1.6.4  Array of role names (used for role translations).
	 *     @type  \WP_User[]  $users      Since 0.1    Array of available users (WP_User objects).
	 *     @type  string[]    $userids    Since 0.1    Array of available user ID's (key) and display names (value).
	 * }
	 */
	private $data = array(
		'caps'      => array(),
		'roles'     => array(),
		'rolenames' => array(),
		'users'     => array(),
		'userids'   => array(),
	);

	/**
	 * Current user object.
	 *
	 * @since  0.1
	 * @since  1.6    Moved to this class from main class.
	 * @var    \WP_User
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
	 * @since  1.7.3  Not static anymore.
	 * @var    array
	 */
	private $curUserData = array();

	/**
	 * Is the original current user a super admin?
	 *
	 * @since  1.6.3
	 * @since  1.7.3  Not static anymore.
	 * @var    bool
	 */
	private $isCurUserSuperAdmin = false;

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
	 * @var    \WP_User
	 */
	private $selectedUser;

	/**
	 * The selected capabilities (if a view is selected).
	 *
	 * @since  1.6.2
	 * @var    bool[]
	 */
	private $selectedCaps = array();

	/**
	 * Populate the instance.
	 * @since  1.6
	 */
	protected function __construct() {
		parent::__construct( 'view-admin-as' );
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
		if ( $done && ! $redo ) return;

		$this->set_nonce( 'view-admin-as' );

		// Get the current user.
		$this->set_curUser( wp_get_current_user() );

		// Get the current user session (WP 4.0+).
		$this->set_curUserSession( (string) wp_get_session_token() );

		$this->isCurUserSuperAdmin = is_super_admin( $this->get_curUser()->ID );
		$this->curUserData = get_object_vars( $this->get_curUser() );

		// Get database settings.
		$this->set_optionData( get_option( $this->get_optionKey() ) );
		// Get database settings of the current user.
		$this->set_userMeta( get_user_meta( $this->get_curUser()->ID, $this->get_userMetaKey(), true ) );

		$done = true;
	}

	/**
	 * Store available capabilities.
	 *
	 * @since   1.4.1
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 * @return  void
	 */
	public function store_caps() {

		// Get current user capabilities.
		$caps = $this->get_originalUserData( 'allcaps' );
		if ( empty( $caps ) ) {
			// Fallback.
			$caps = $this->get_curUser()->allcaps;
		}

		// Only allow to add capabilities for an admin (or super admin).
		if ( VAA_API::is_super_admin() ) {

			/**
			 * Add compatibility for other cap managers.
			 *
			 * @since  1.5
			 * @see    VAA_View_Admin_As_Compat->init()
			 * @param  array  $caps  An empty array, waiting to be filled with capabilities.
			 * @return array
			 */
			$all_caps = apply_filters( 'view_admin_as_get_capabilities', array() );

			$add_caps = array();
			// Add new capabilities to the capability array as disabled.
			foreach ( $all_caps as $cap_key => $cap_val ) {
				if ( is_numeric( $cap_key ) ) {
					// Try to convert numeric (faulty) keys. Some developers just don't get it..
					$add_caps[ (string) $cap_val ] = 0;
				} else {
					$add_caps[ (string) $cap_key ] = 0;
				}
			}

			$caps = array_merge( $add_caps, $caps );

		} // End if().

		// Remove role names.
		$caps = array_diff_key( $caps, $this->get_roles() );
		// And sort alphabetical.
		ksort( $caps );

		$this->set_caps( $caps );
	}

	/**
	 * Store available roles.
	 *
	 * @since   1.5
	 * @since   1.5.2  Get role objects instead of arrays.
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 * @global  \WP_Roles  $wp_roles
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

		if ( ! VAA_API::is_super_admin() ) {

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

		// @since  1.6.4  Set role names.
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
	 * @global  \wpdb  $wpdb
	 * @return  void
	 */
	public function store_users() {
		global $wpdb;

		$super_admins = get_super_admins();
		// Load the superior admins.
		$superior_admins = VAA_API::get_superior_admins();

		// Is the current user a super admin?
		$is_super_admin = VAA_API::is_super_admin();
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
				if ( isset( $user->ID ) && ! in_array( (int) $user->ID, $superior_admins, true ) ) {
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
			if ( is_multisite() && ! $is_superior_admin &&
			     is_array( $super_admins ) && ! empty( $super_admins[0] )
			) {
				// Escape usernames just to be sure.
				$super_admins = array_filter( $super_admins, 'validate_username' );
				// Pre WP 4.4 - Remove empty usernames since these return true before WP 4.4.
				$super_admins = array_filter( $super_admins );

				$exclude_siblings = "'" . implode( "','", $super_admins ) . "'";
				$user_query['where'] .= " AND users.user_login NOT IN ({$exclude_siblings})";
			}

			// Run query (OBJECT_K to set the user ID as key).
			// @codingStandardsIgnoreLine >> $wpdb->prepare() not needed
			$users_results = $wpdb->get_results( implode( ' ', $user_query ), OBJECT_K );

			if ( $users_results ) {

				$users = array();
				// Temp set users.
				$this->set_users( $users_results );
				// @hack  Short circuit the meta queries (not needed).
				add_filter( 'get_user_metadata', array( $this, '_filter_get_user_capabilities' ), 10, 3 );

				// Turn query results into WP_User objects.
				foreach ( $users_results as $user ) {
					$user->roles = maybe_unserialize( $user->roles );
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
		} // End if().

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
				 * @see    get_super_admins() >> wp-includes/capabilities.php
				 * @see    is_super_admin() >> wp-includes/capabilities.php
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
	 * @see     \WP_User->_init_caps() >> wp-includes/class-wp-user.php
	 * @see     get_metadata() >> `get_user_metadata` filter
	 * @link    https://developer.wordpress.org/reference/functions/get_metadata/
	 *
	 * @global  \wpdb    $wpdb
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
				$roles = maybe_unserialize( $roles );
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
	 * @since   1.7.1  User ID as array key.
	 * @access  public
	 *
	 * @see     store_users()
	 *
	 * @param   \WP_User[]  $users  Array of user objects (WP_User).
	 * @return  \WP_User[]  $users
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
					$tmp_users[ $user->ID ] = $user;
				}
			}
		}
		$users = $tmp_users;
		return $users;
	}

	/**
	 * Helper function for is_super_admin().
	 * Will validate the original user if it is the current user or no user ID is passed.
	 * This can prevent invalid checks after a view is applied.
	 *
	 * @since   1.6.3
	 * @since   1.7.3  Not static anymore.
	 * @see     VAA_API::is_super_admin()
	 * @access  public
	 * @param   int  $user_id  (optional).
	 * @return  bool
	 */
	public function is_super_admin( $user_id = null ) {
		if ( null === $user_id || (int) get_current_user_id() === (int) $user_id ) {
			return $this->isCurUserSuperAdmin;
		}
		return is_super_admin( $user_id );
	}

	/**
	 * Get data from the current user, similar to the WP_User object.
	 * Unlike the current user object this data isn't modified after in a view.
	 * This has all public WP_User properties stored as an array.
	 *
	 * @since   1.6.3
	 * @since   1.7.3  Not static anymore.
	 * @access  public
	 * @param   string  $key  (optional).
	 * @return  mixed
	 */
	public function get_originalUserData( $key = null ) {
		return VAA_API::get_array_data( $this->curUserData, $key );
	}

	/**
	 * Get current user.
	 * @return  \WP_User  $curUser  Current user object.
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
	 * @since   1.7
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
		_deprecated_function( __METHOD__, '1.7', 'VAA_View_Admin_As_Store::get_view()' );
		return $this->get_view( $key );
	}

	/**
	 * Get view type data
	 *
	 * @since   1.7
	 * @param   string  $type  Type key.
	 * @param   string  $key   (optional) Type data key.
	 * @return  mixed
	 */
	public function get_data( $type, $key = null ) {
		if ( isset( $this->data[ $type ] ) ) {
			return VAA_API::get_array_data( $this->data[ $type ], $key );
		}
		return null;
	}

	/**
	 * Get available capabilities.
	 * @param   string  $key  Cap name.
	 * @return  bool[]|bool  Array of capabilities or a single capability value.
	 */
	public function get_caps( $key = null ) {
		return $this->get_data( 'caps', $key );
	}

	/**
	 * Get available roles.
	 * @param   string  $key  Role slug/key.
	 * @return  \WP_Role[]|\WP_Role  Array of role objects or a single role object.
	 */
	public function get_roles( $key = null ) {
		return $this->get_data( 'roles', $key );
	}

	/**
	 * Get the role names. Translated by default.
	 * If key is provided but not found it will return the key (untranslated).
	 * @since   1.6.4
	 * @param   string  $key        Role slug.
	 * @param   bool    $translate  Translate the role name?
	 * @return  string[]|string
	 */
	public function get_rolenames( $key = null, $translate = true ) {
		$val = $this->get_data( 'rolenames', $key );
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
	 * @param   string  $key  User key.
	 * @return  \WP_User[]|\WP_User  Array of user objects or a single user object.
	 */
	public function get_users( $key = null ) {
		return $this->get_data( 'users', $key );
	}

	/**
	 * Get available users.
	 * @todo    Remove in future.
	 * @deprecated
	 * @param   string  $key  User key.
	 * @return  string[]|string  Array of user display names or a single user display name.
	 */
	public function get_userids( $key = null ) {
		return $this->get_data( 'userids', $key );
	}

	/**
	 * Get selected capabilities of a view.
	 * @param   string  $key  Cap name.
	 * @return  bool[]|bool  Array of capabilities or a single capability value.
	 */
	public function get_selectedCaps( $key = null ) {
		return VAA_API::get_array_data( $this->selectedCaps, $key );
	}

	/**
	 * Get the selected user object of a view.
	 * @return  \WP_User
	 */
	public function get_selectedUser() {
		return $this->selectedUser;
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
		$this->view = (array) VAA_API::set_array_data( $this->view, $val, $key, $append );
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
		_deprecated_function( __METHOD__, '1.7', 'VAA_View_Admin_As_Store::set_view()' );
		$this->set_view( $val, $key, $append );
	}

	/**
	 * Set view type data
	 *
	 * @since   1.7
	 * @param   string  $type
	 * @param   mixed   $val
	 * @param   string  $key
	 * @param   bool    $append
	 * @return  void
	 */
	public function set_data( $type, $val, $key = null, $append = false ) {
		if ( VAA_API::exists_callable( array( $this, 'set_' . $type ) ) ) {
			$method = 'set_' . $type;
			$this->$method( $val, $key, $append );
			return;
		}
		$current = ( isset( $this->data[ $type ] ) ) ? $this->data[ $type ] : array();
		$this->data[ $type ] = (array) VAA_API::set_array_data( $current, $val, $key, $append );
	}

	/**
	 * Set the available capabilities.
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) Cap key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_caps( $val, $key = null, $append = false ) {
		$this->data['caps'] = (array) VAA_API::set_array_data( $this->data['caps'], $val, $key, $append );
	}

	/**
	 * Set the available roles.
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) Role name.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_roles( $val, $key = null, $append = false ) {
		$this->data['roles'] = (array) VAA_API::set_array_data( $this->data['roles'], $val, $key, $append );
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
		$this->data['rolenames'] = (array) VAA_API::set_array_data( $this->data['rolenames'], $val, $key, $append );
	}

	/**
	 * Set the available users.
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) User key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_users( $val, $key = null, $append = false ) {
		$this->data['users'] = (array) VAA_API::set_array_data( $this->data['users'], $val, $key, $append );
	}

	/**
	 * Set the available user display names.
	 * @todo    Remove in future.
	 * @deprecated
	 * @param   array  $val  Array of available user ID's (key) and display names (value).
	 * @return  void
	 */
	public function set_userids( $val ) {
		$this->data['userids'] = array_map( 'strval', (array) $val );
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
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  $this  VAA_View_Admin_As_Store
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Store.
