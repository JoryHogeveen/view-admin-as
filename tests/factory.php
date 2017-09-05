<?php
/**
 * View Admin As - Unit tests installation
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

view_admin_as()->include_file( VIEW_ADMIN_AS_DIR . 'includes/class-base.php', 'VAA_View_Admin_As_Base' );
view_admin_as()->include_file( VIEW_ADMIN_AS_DIR . 'includes/class-module.php', 'VAA_View_Admin_As_Module' );

class VAA_UnitTest_Factory {

	/**
	 * The single instance of the class.
	 *
	 * @static
	 * @var    VAA_UnitTest_Factory
	 */
	private static $_instance = null;

	/**
	 * @var VAA_View_Admin_As
	 */
	public static $vaa = null;

	/**
	 * @var VAA_View_Admin_As_Store
	 */
	public static $store = null;

	/**
	 * @var array
	 */
	public static $vaa_users = array();

	/**
	 * VAA_UnitTest_Factory constructor.
	 */
	protected function __construct() {
		wp_set_current_user( 1 );
		add_filter( 'view_admin_as_superior_admins', array( 'VAA_UnitTest_Factory', 'vaa_filter_superior_admin' ) );
		self::vaa_reinit();
		self::add_users();
	}

	/**
	 * Set superior admin.
	 */
	static public function vaa_filter_superior_admin() {
		//$user = get_user_by( 'id', 1 );
		return 1; //$user->ID;
	}

	/**
	 * Setup all test users.
	 */
	static public function add_users() {
		static $done = false;
		if ( $done ) return;

		$superior_admin = get_user_by( 'id', 1 );
		if ( ! $superior_admin ) {
			$superior_admin = self::add_user( 'Admin', 'administrator', array(), true );
		}
		self::$vaa_users[ $superior_admin->user_login ] = $superior_admin;

		$author       = self::add_user( 'Author', 'author' );
		$editor       = self::add_user( 'Editor', 'editor' );
		$vaa_editor   = self::add_user( 'VAA Editor', 'editor', array( 'view_admin_as', 'edit_users', 'manage_network_users' ) );
		$admin        = self::add_user( 'Administrator', 'administrator' );
		$super_admin  = self::add_user( 'Super Admin', 'administrator', array(), true );

		$done = true;
	}

	/**
	 * Set the current user.
	 *
	 * @param   string  $name
	 * @param   string  $role          (optional) Only needed for a new user.
	 * @param   array   $capabilities  (optional) Only needed for a new user.
	 * @param   bool    $super_admin   (optional) Only needed for a new user.
	 * @return  WP_User
	 */
	static function set_current_user( $name, $role = '', $capabilities = array(), $super_admin = false ) {
		global $current_user;
		$username = strtolower( preg_replace( "/[^a-zA-Z0-9]+/", "", $name ) );

		$user = get_user_by( 'login', $username );
		if ( ! $user && empty( VAA_UnitTest_Factory::$vaa_users[ $username ] ) ) {
			//if ( ! isset( self::$vaa_users[ $username ] ) ) {
			$current_user = VAA_UnitTest_Factory::add_user( $name, $role, $capabilities, $super_admin );
		} else {
			$current_user = VAA_UnitTest_Factory::$vaa_users[ $username ];
		}

		$current_user = wp_set_current_user( $current_user->ID );

		self::vaa_reinit();

		echo PHP_EOL . 'User set: ' . $current_user->display_name . ' | ID: ' . $current_user->ID . ' | username: ' . $current_user->user_login . PHP_EOL;

		return $current_user;
	}

	/**
	 * Add a current user.
	 *
	 * @param   string  $name
	 * @param   string  $role
	 * @param   array   $capabilities
	 * @param   bool    $super_admin
	 * @return  WP_User
	 */
	static function add_user( $name, $role = '', $capabilities = array(), $super_admin = false ) {
		$username = strtolower( preg_replace( "/[^a-zA-Z0-9]+/", "", $name ) );

		$id = wp_create_user( $username, 'test' );
		$user = new WP_User( $id );
		/*global $wpdb;
		$wpdb->get_results( sprintf( "INSERT INTO {$wpdb->get_blog_prefix()}users (user_login, display_name) VALUES ('%s', '%s')",
			$username,
			$user->display_name
		) );*/

		// Effectively runs: add_user_to_blog( get_current_blog_id(), $id, $role );
		$user->set_role( $role );

		if ( ! empty( $capabilities ) ) {
			foreach( $capabilities as $cap => $grant ) {
				if ( is_string( $grant ) ) {
					$cap = $grant;
					$grant = true;
				}
				$user->add_cap( $cap, $grant );
				// WP 4.1 issue
				$user->get_role_caps();
			}
		}
		$user->display_name = $name;

		if ( $super_admin && $role === 'administrator' && is_multisite() ) {
			grant_super_admin( $user->ID );
			//global $super_admins;
			//$super_admins = array_merge( get_super_admins(), array( $user->user_login ) );
		}

		echo PHP_EOL . 'User added: ' . $user->display_name . ' | ID: ' . $user->ID . ' | username: ' . $user->user_login . PHP_EOL;
		self::$vaa_users[ $username ] = $user;

		return $user;
	}

	/**
	 * Get an already loaded user.
	 * @param  mixed   $value
	 * @param  string  $field
	 * @return WP_User|null
	 */
	static function get_user( $value, $field = 'ID' ) {

		if ( 'id' === $field ) {
			$field = 'ID';
		}

		if ( ! empty( self::$vaa_users[ $value ] ) ) {
			return self::$vaa_users[ $value ];
		}

		foreach ( self::$vaa_users as $user ) {
			if ( isset( $user->$field ) && $value === $user->$field ) {
				return $user;
			}
		}

		return null;
	}

	/**
	 * Re-init VAA.
	 */
	static function vaa_reinit() {
		remove_all_actions( 'vaa_view_admin_as_init' );
		self::$vaa = view_admin_as();
		self::$vaa->init( true );

		self::$store = self::$vaa->store();

		if ( self::$store ) {
			// Make a force reinit for the store user data.

			self::$store->init( true );
			self::$store->set_curUserSession( 'test' );

			// Resets is_enabled. Required because of force reinit.
			self::$vaa->set_enabled();

			// Reload VAA type data.
			self::$store->store_users();
			self::$store->store_roles();
			self::$store->store_caps();

		}

		//echo PHP_EOL . 'View Admin As re-init done.' . PHP_EOL;
	}

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}