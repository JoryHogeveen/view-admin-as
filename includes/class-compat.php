<?php
/**
 * View Admin As - Class Compat
 *
 * Compatibility class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.6
 * @version 1.6.1
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As_Compat extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since   1.6
	 * @var     VAA_View_Admin_As_Compat
	 */
	private static $_instance = null;

	/**
	 * Populate the instance
	 * @since   1.6
	 */
	protected function __construct() {
		self::$_instance = $this;
		parent::__construct();
	}

	/**
	 * Fix compatibility issues
	 *
	 * @since   0.1
	 * @since   1.6    Moved third_party_compatibility() to this class from main class
	 * @access  public
	 * @return  void
	 */
	public function init() {

		add_action( 'vaa_view_admin_as_init', array( $this, 'init_after' ) );

		/**
		 * Add our caps to the members plugin
		 * @since 1.6
		 */
		add_filter( 'members_get_capabilities', array( $this, 'add_capabilities' ) );
		add_action( 'members_register_cap_groups', array( $this, 'members_register_cap_group' ) );

		// Get caps from other plugins
		add_filter( 'view_admin_as_get_capabilities', array( $this, 'get_capabilities' ) );

	}

	/**
	 * Fix compatibility issues on load
	 * Called from 'vaa_view_admin_as_init' hook (after loading all data)
	 *
	 * @since   1.6.1
	 * @access  public
	 * @return  void
	 */
	public function init_after() {

		/*if ( false !== $this->store->get_viewAs() ) {
			// WooCommerce
			remove_filter( 'show_admin_bar', 'wc_disable_admin_bar', 10 );
		}*/

		if ( $this->store->get_viewAs('role') ) {
			// Pods 2.x (only needed for the role selector)
			add_filter( 'pods_is_admin', array( $this, 'pods_caps_check' ), 10, 3 );
		}
	}

	/**
	 * Get's current capabilities and merges with capabilities from other plugins
	 *
	 * @since   1.6
	 * @access  public
	 * @see     init()
	 *
	 * @param   array  $caps
	 * @return  array
	 */
	public function get_capabilities( $caps ) {

		// To support Members filters
		$caps = apply_filters( 'members_get_capabilities', $caps );
		// To support Pods filters
		$caps = apply_filters( 'pods_roles_get_capabilities', $caps );

		return $caps;
	}

	/**
	 * Add our capabilities to an existing list of capabilities
	 *
	 * @since   1.6
	 * @access  public
	 * @see     init()
	 *
	 * @param   array  $caps
	 * @return  array
	 */
	public function add_capabilities( $caps = array() ) {

		// Allow VAA modules to add their capabilities
		$vaa_caps = apply_filters( '_vaa_add_capabilities', array( 'view_admin_as' ) );
		foreach ( $vaa_caps as $cap ) {
			$caps[ $cap ] = $cap;
		}

		return $caps;
	}

	/**
	 * Fix compatibility issues Pods Framework 2.x
	 *
	 * @since   1.0.1
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @see     init()
	 *
	 * @param   bool     $bool        Boolean provided by the pods_is_admin hook (not used)
	 * @param   array    $cap         String or Array provided by the pods_is_admin hook
	 * @param   string   $capability  String provided by the pods_is_admin hook
	 * @return  bool
	 */
	public function pods_caps_check( $bool, $cap, $capability ) {

		// Pods gives arrays most of the time with the to-be-checked capability as the last item
		if ( is_array( $cap ) ) {
			$cap = end( $cap );
		}

		$role_caps = $this->store->get_roles( $this->store->get_viewAs('role') )->capabilities;
		if ( ! array_key_exists( $cap, $role_caps ) || ( 1 != $role_caps[ $cap ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add our capabilities to our own group in the members plugin
	 *
	 * @since   1.6
	 * @access  public
	 * @see     init()
	 */
	public function members_register_cap_group() {

		if ( function_exists( 'members_register_cap_group' ) ) {
			// Register the vaa group.
			members_register_cap_group( 'view_admin_as',
				array(
					'label'      => esc_html__( 'View Admin As', 'view-admin-as' ),
					'caps'       => $this->add_capabilities(),
					'icon'       => 'dashicons-visibility',
					'diff_added' => true
				)
			);
		}
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   object  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Compat
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' == get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		return null;
	}

} // end class