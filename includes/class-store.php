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
 * @since   1.6.0
 * @version 1.8.0
 * @uses    \VAA_View_Admin_As_Settings Extends class
 */
final class VAA_View_Admin_As_Store extends VAA_View_Admin_As_Settings
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6.0
	 * @static
	 * @var    \VAA_View_Admin_As_Store
	 */
	private static $_instance = null;

	/**
	 * The nonce.
	 *
	 * @since  1.3.4
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
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
	 * @see    \VAA_View_Admin_As_Store::set_data()
	 * @since  1.7.0
	 * @var    array {
	 *     Default view data.
	 *     @type  bool[]      $caps       Since 1.3.0  Array of available capabilities.
	 *     @type  \WP_Role[]  $roles      Since 0.1.0  Array of available roles (WP_Role objects).
	 *     @type  string[]    $rolenames  Since 1.6.4  Array of role names (used for role translations).
	 *     @type  \WP_User[]  $users      Since 0.1.0  Array of available users (WP_User objects).
	 *     @type  string[]    $languages  Since 1.8.0  Array of available locale/languages.
	 * }
	 */
	private $data = array(
		'caps'      => array(),
		'roles'     => array(),
		'rolenames' => array(),
		'users'     => array(),
		'languages' => array(),
	);

	/**
	 * Current (initial) user object.
	 *
	 * @since  0.1.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @var    \WP_User
	 */
	private $curUser;

	/**
	 * Current (initial) user session.
	 *
	 * @since  1.3.4
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @var    string
	 */
	private $curUserSession = '';

	/**
	 * Current (initial) user data.
	 * Will contain all properties of the original current user object.
	 *
	 * @since  1.6.3
	 * @since  1.7.3  Not static anymore.
	 * @var    array
	 */
	private $curUserData = array();

	/**
	 * Does the current (initial) user has full access to all features of this plugin?
	 *
	 * @since  1.6.3
	 * @since  1.7.3  Not static anymore.
	 * @since  1.7.6  Renamed from `$isCurUserSuperAdmin`.
	 * @var    bool
	 */
	private $curUserHasFullAccess = false;

	/**
	 * Selected view data as stored in the user meta.
	 * Format: array( VIEW_TYPE => VIEW_DATA ).
	 *
	 * @since  0.1.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @var    array
	 */
	private $view = array();

	/**
	 * The selected user object (if a view is selected).
	 * Can be the same as $curUser depending on the selected view.
	 *
	 * @since  0.1.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
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
	 * @since  1.6.0
	 */
	protected function __construct() {
		parent::__construct( 'view-admin-as' );
		self::$_instance = $this;

		$this->init( true );
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

		$this->curUserHasFullAccess = VAA_API::user_has_full_access( $this->get_curUser() );
		$this->curUserData          = get_object_vars( $this->get_curUser() );

		// Get database settings.
		$this->store_optionData( VAA_View_Admin_As::is_network_active() );
		// Get database settings of the current user.
		$this->store_userMeta( get_current_user_id() );

		$done = true;
	}

	/**
	 * Does the current (original) user has full access to this plugin?
	 * @since   1.8.0
	 * @return  bool
	 */
	public function cur_user_has_full_access() {
		return (bool) $this->curUserHasFullAccess;
	}

	/**
	 * Compare user to the current (original) user.
	 *
	 * @since   1.8.0
	 * @param   \WP_User|int  $user  The user to compare.
	 * @return  bool
	 */
	public function is_curUser( $user ) {
		if ( $user instanceof WP_User ) {
			$user = $user->ID;
		}
		if ( ! is_numeric( $user ) ) {
			return false;
		}
		return (bool) ( (int) $this->get_curUser()->ID === (int) $user );
	}

	/**
	 * Helper function for is_super_admin().
	 * Will validate the original user if it is the current user or no user ID is passed.
	 * This can prevent invalid checks after a view is applied.
	 *
	 * @see     \VAA_API::is_super_admin()
	 * @deprecated  1.8.0
	 * @todo    Remove in 1.9
	 *
	 * @since   1.6.3
	 * @since   1.7.3  Not static anymore.
	 * @access  public
	 * @param   int  $user_id  (optional).
	 * @return  bool
	 */
	public function is_super_admin( $user_id = null ) {
		_deprecated_function( __FUNCTION__, '1.8', 'VAA_API::is_super_admin()' );
		if ( null === $user_id || (int) $this->curUser->ID === (int) $user_id ) {
			return $this->curUserHasFullAccess;
		}
		return VAA_API::user_has_full_access( $user_id );
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
	 * Get view data.
	 * @since   1.7.0
	 * @param   string  $key  Key for array.
	 * @return  mixed
	 */
	public function get_view( $key = null ) {
		return VAA_API::get_array_data( $this->view, $key );
	}

	/**
	 * Get view type data
	 * @since   1.7.0
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

			/**
			 * Try to fetch role name from WP core. No security risk here.
			 * Check for the wp_roles() function in WP 4.3+.
			 * @since  1.8.0
			 */
			if ( function_exists( 'wp_roles' ) ) {
				$wp_roles = wp_roles();
			} else {
				global $wp_roles;
			}
			if ( isset( $wp_roles->role_names[ $key ] ) ) {
				$this->set_rolenames( $wp_roles->role_names[ $key ], $key, true );
				return $this->get_rolenames( $key, $translate );
			}

			return ( $key ) ? $key : $val;
		}
		if ( $translate ) {
			if ( is_array( $val ) ) {
				$val = array_map( 'translate_user_role', $val );
			} else {
				$val = translate_user_role( $val );
			}
		}
		return $val;
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
	 * Get available languages.
	 * @since   1.8.0
	 * @param   string  $key  Locale key.
	 * @return  string[]|string  Array of language names or a single language name.
	 */
	public function get_languages( $key = null ) {
		return $this->get_data( 'languages', $key );
	}

	/**
	 * Get the selected user object of a view.
	 * @return  \WP_User
	 */
	public function get_selectedUser() {
		return $this->selectedUser;
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
	 * Get the nonce.
	 * @param   string  $parsed  Return parsed nonce?
	 * @return  string
	 */
	public function get_nonce( $parsed = null ) {
		return ( $parsed ) ? $this->nonce_parsed : $this->nonce;
	}

	/**
	 * Get plugin version.
	 * @todo    Move to API.
	 * @return  string
	 */
	public function get_version() {
		return strtolower( (string) VIEW_ADMIN_AS_VERSION );
	}

	/**
	 * Get plugin database version.
	 * @todo    Move to API.
	 * @return  string
	 */
	public function get_dbVersion() {
		return strtolower( (string) VIEW_ADMIN_AS_DB_VERSION );
	}

	/**
	 * Set the current user object.
	 * @param   \WP_User  $val  User object.
	 * @return  void
	 */
	public function set_curUser( WP_User $val ) {
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
	 * Set view type data.
	 *
	 * @since   1.7.0
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
		$current             = ( isset( $this->data[ $type ] ) ) ? $this->data[ $type ] : array();
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
	 * Set the languages.
	 * @since   1.8.0
	 * @param   mixed   $val     Value.
	 * @param   string  $key     (optional) Role name.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_languages( $val, $key = null, $append = false ) {
		$this->data['languages'] = (array) VAA_API::set_array_data( $this->data['languages'], $val, $key, $append );
	}

	/**
	 * Set the selected user object for the current view.
	 * @param   \WP_User  $val  User object.
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
	 * Also sets a parsed version of the nonce with wp_create_nonce().
	 * @param   string  $val  Nonce.
	 * @return  void
	 */
	public function set_nonce( $val ) {
		$this->nonce        = (string) $val;
		$this->nonce_parsed = wp_create_nonce( (string) $val );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class.
	 * @return  \VAA_View_Admin_As_Store  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Store.
