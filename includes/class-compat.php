<?php
/**
 * View Admin As - Class Compat
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * Compatibility class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.6.4
 * @uses    VAA_View_Admin_As_Class_Base Extends class
 */
final class VAA_View_Admin_As_Compat extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_Compat
	 */
	private static $_instance = null;

	/**
	 * Populate the instance.
	 *
	 * @since   1.6
	 * @since   1.6.1  $vaa param.
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );
	}

	/**
	 * Fix compatibility issues.
	 *
	 * @since   0.1
	 * @since   1.6    Moved third_party_compatibility() to this class from main class.
	 * @access  public
	 * @return  void
	 */
	public function init() {

		add_action( 'vaa_view_admin_as_init', array( $this, 'init_after' ) );

		/**
		 * Add our caps to the members plugin.
		 * Hook `members_get_capabilities` also used by:
		 *  - User Role Editor (URE)
		 *  - WPFront User Role Editor
		 *  - Pods
		 *
		 * @since  1.6
		 */
		add_filter( 'members_get_capabilities', array( $this, 'add_capabilities' ) );
		add_action( 'members_register_cap_groups', array( $this, 'action_members_register_cap_group' ) );

		/**
		 * Add our caps to the User Role Editor plugin (URE)
		 * @since  1.6.4
		 */
		add_filter( 'ure_capabilities_groups_tree', array( $this, 'filter_ure_capabilities_groups_tree' ) );
		add_filter( 'ure_custom_capability_groups', array( $this, 'filter_ure_custom_capability_groups' ), 10, 2 );

		/**
		 * Get caps from other plugins.
		 * @since  1.5
		 */
		add_filter( 'view_admin_as_get_capabilities', array( $this, 'get_capabilities' ) );

	}

	/**
	 * Fix compatibility issues on load.
	 * Called from 'vaa_view_admin_as_init' hook (after loading all data).
	 *
	 * @since   1.6.1
	 * @access  public
	 * @return  void
	 */
	public function init_after() {

		if ( $this->store->get_view()
		     && (int) $this->store->get_curUser()->ID === (int) $this->store->get_selectedUser()->ID
		) {
			// Only apply the filter if the current user is modified
			add_filter( 'pods_is_admin', array( $this, 'filter_pods_caps_check' ), 99, 2 );
		}
	}

	/**
	 * Get's current capabilities and merges with capabilities from other plugins.
	 *
	 * @since   1.6
	 * @access  public
	 * @see     init()
	 *
	 * @param   array  $caps  The capabilities.
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
	 * Add our capabilities to an existing list of capabilities.
	 *
	 * @since   1.6
	 * @access  public
	 * @see     init()
	 *
	 * @param   array  $caps  The capabilities.
	 * @return  array
	 */
	public function add_capabilities( $caps = array() ) {

		// Allow VAA modules to add their capabilities
		$vaa_caps = apply_filters( 'view_admin_as_add_capabilities', array( 'view_admin_as' ) );
		foreach ( $vaa_caps as $cap ) {
			$caps[ $cap ] = $cap;
		}

		return $caps;
	}

	/**
	 * Fix compatibility issues Pods Framework 2.0+.
	 *
	 * @since   1.0.1
	 * @since   1.6    Moved to this class from main class.
	 * @since   1.6.2  Check for all provided capabilities.
	 * @access  public
	 * @see     init()
	 *
	 * @param   bool   $bool  Boolean provided by the pods_is_admin hook (not used).
	 * @param   array  $caps  String or Array provided by the pods_is_admin hook.
	 * @return  bool
	 */
	public function filter_pods_caps_check( $bool, $caps ) {

		foreach ( (array) $caps as $capability ) {
			if ( $this->vaa->view()->current_view_can( $capability ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add our capabilities to our own group in the members plugin.
	 *
	 * @since   1.6
	 * @access  public
	 * @see     init()
	 */
	public function action_members_register_cap_group() {

		if ( function_exists( 'members_register_cap_group' ) ) {
			// Register the vaa group.
			members_register_cap_group( 'view_admin_as',
				array(
					'label'      => esc_html__( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ),
					'caps'       => $this->add_capabilities(),
					'icon'       => 'dashicons-visibility',
					'diff_added' => true,
				)
			);
		}
	}

	/**
	 * Add our our own capability group in the URE plugin.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @see     init()
	 * @see     URE_Capabilities_Groups_Manager::get_groups_tree()
	 * @param   array  $groups  Current groups
	 * @return  array
	 */
	public function filter_ure_capabilities_groups_tree( $groups ) {
		$groups['view_admin_as'] = array(
			'caption' => esc_html__( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ),
			'parent'  => 'custom',
			'level'   => 3,
		);
		return $groups;
	}

	/**
	 * Add our capabilities to our own group in the URE plugin.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @see     init()
	 * @see     URE_Capabilities_Groups_Manager::get_cap_groups()
	 * @param   array   $groups  Current capability groups
	 * @param   string  $cap_id  Capability identifier
	 * @return  array
	 */
	public function filter_ure_custom_capability_groups( $groups, $cap_id ) {
		if ( in_array( $cap_id, $this->add_capabilities(), true ) ) {
			$groups = (array) $groups;
			$groups[] = 'view_admin_as';
		}
		return $groups;
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
	 * @return  VAA_View_Admin_As_Compat
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // end class
