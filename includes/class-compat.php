<?php
/**
 * View Admin As - Class Compat
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Compatibility class.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.7
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
		 *  - User Role Editor (URE) >> Own filter: `ure_full_capabilites`
		 *  - WPFront User Role Editor
		 *  - Capability Manager Enhanced >> Own filter: `capsman_get_capabilities`
		 *  - Pods
		 *
		 * @since  1.6
		 */
		add_filter( 'members_get_capabilities', array( $this, 'add_capabilities' ) );
		add_action( 'members_register_cap_groups', array( $this, 'action_members_register_cap_group' ) );

		/**
		 * Add our caps to the User Role Editor plugin (URE).
		 * @since  1.6.4
		 */
		add_filter( 'ure_capabilities_groups_tree', array( $this, 'filter_ure_capabilities_groups_tree' ) );
		add_filter( 'ure_custom_capability_groups', array( $this, 'filter_ure_custom_capability_groups' ), 10, 2 );

		/**
		 * Get caps from other plugins.
		 * @since  1.5
		 */
		add_filter( 'view_admin_as_get_capabilities', array( $this, 'get_capabilities' ), 10, 2 );

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
	 * @param   bool   $all   Get all or only VAA related capabilities?
	 * @return  array
	 */
	public function get_capabilities( $caps = array(), $all = true ) {

		$caps = $this->add_capabilities( $caps );

		if ( ! $all ) {
			return $caps;
		}

		$caps = array_merge( $this->get_wordpress_capabilities(), $caps );

		$caps = array_merge( $this->get_plugin_capabilities(), $caps );

		return $caps;
	}

	/**
	 * Get all capabilities from WP core or WP objects.
	 *
	 * @since   1.7.1
	 * @param   array  $caps  The capabilities.
	 * @return  array
	 */
	public function get_wordpress_capabilities( $caps = array() ) {

		// @since  1.7.1  Store available capabilities existing in roles.
		foreach ( $this->store->get_roles() as $key => $role ) {
			if ( is_array( $role->capabilities ) ) {
				foreach ( $role->capabilities as $cap => $grant ) {
					$caps[ $cap ] = $cap;
				}
			}
		}

		// @since  1.7.1  Add post type and taxonomy caps.
		$wp_objects = array_merge(
			(array) get_post_types( array(), 'objects' ),
			(array) get_taxonomies( array(), 'objects' )
		);
		foreach ( $wp_objects as $obj ) {
			if ( isset( $obj->cap ) ) {
				// WP stores the object caps as general_cap_name => actual_cap.
				$caps = array_merge( array_combine( (array) $obj->cap, (array) $obj->cap ), $caps );
			}
		}

		/**
		 * Network capabilities.
		 * @since  1.5.3
		 * @see    https://codex.wordpress.org/Roles_and_Capabilities
		 */
		if ( is_multisite() ) {
			$network_caps = array(
				'manage_network',
				'manage_sites',
				'manage_network_users',
				'manage_network_plugins',
				'manage_network_themes',
				'manage_network_options',
			);
			$caps = array_merge( $network_caps, $caps );
		}

		return $caps;
	}

	/**
	 * Get all capabilities from other plugins.
	 *
	 * Disable some PHPMD checks for this method.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @todo Refactor to enable above checks?
	 *
	 * @since   1.7.1
	 * @param   array  $caps  The capabilities.
	 * @return  array
	 */
	public function get_plugin_capabilities( $caps = array() ) {

		// WooCommerce caps are not accessible but are assigned to roles on install.
		// get_wordpress_capabilities() will find them.

		// @since  1.7.1  Gravity Forms.
		if ( is_callable( array( 'GFCommon', 'all_caps' ) ) ) {
			$caps = array_merge( (array) GFCommon::all_caps(), $caps );
		}

		// @since  1.7.1  User Role Editor.
		if ( is_callable( array( 'URE_Own_Capabilities', 'get_caps' ) ) ) {
			$caps = array_merge( (array) URE_Own_Capabilities::get_caps(), $caps );
		}
		$caps = apply_filters( 'ure_full_capabilites', $caps );

		// @since  1.7.1  WPFront User Role Editor.
		if ( class_exists( 'WPFront_User_Role_Editor' ) && isset( WPFront_User_Role_Editor::$ROLE_CAPS ) ) {
			$caps = array_merge( (array) WPFront_User_Role_Editor::$ROLE_CAPS, $caps );
		}

		// @since  1.7.1  User Roles and Capabilities.
		if ( is_callable( array( 'Solvease_Roles_Capabilities_User_Caps', 'solvease_roles_capabilities_caps' ) ) ) {
			$caps = array_merge( (array) Solvease_Roles_Capabilities_User_Caps::solvease_roles_capabilities_caps(), $caps );
		}

		// @since  1.7.1  bbPress.
		if ( function_exists( 'bbp_get_caps_for_role' ) ) {
			if ( function_exists( 'bbp_get_keymaster_role' ) ) {
				$bbp_keymaster_role = bbp_get_keymaster_role();
			} else {
				$bbp_keymaster_role = apply_filters( 'bbp_get_keymaster_role', 'bbp_keymaster' );
			}
			$caps = array_merge( (array) bbp_get_caps_for_role( $bbp_keymaster_role ), $caps );
		}

		// @since  1.7.1  BuddyPress.
		if ( class_exists( 'BuddyPress' ) ) {
			$caps = array_merge(
				array(
					'bp_moderate',
					'bp_xprofile_change_field_visibility',
					// @todo Check usage of capabilities below.
					/*
					'throttle',
					'keep_gate',
					'moderate_comments',
					'edit_cover_image',
					'edit_avatar',
					'edit_favorites',
					'edit_favorites_of',
					'add_tag_to',
					'edit_tag_by_on',
					'change_user_password',
					'moderate',
					'browse_deleted',
					'view_by_ip',
					'write_posts',
					'write_topic',
					'write_topics',
					'move_topic',
					'stick_topic',
					'close_topic',
					'edit_topic',
					'delete_topic',
					'delete_forum',
					'manage_forums',
					'manage_tags',
					*/
				),
				// @see bp-core-caps.php >> bp_get_community_caps().
				apply_filters( 'bp_get_community_caps', array() ),
				$caps
			);
		} // End if().

		// Members.
		if ( function_exists( 'members_get_plugin_capabilities' ) ) {
			$caps = array_merge( (array) members_get_plugin_capabilities(), $caps );
		}
		// Get caps from multiple plugins through the Members filter.
		$caps = apply_filters( 'members_get_capabilities', $caps );

		// Pods.
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

		// Allow VAA modules to add their capabilities.
		foreach ( (array) apply_filters( 'view_admin_as_add_capabilities', array( 'view_admin_as' ) ) as $cap ) {
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

} // End class VAA_View_Admin_As_Compat.
