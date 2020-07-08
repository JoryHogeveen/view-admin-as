<?php
/**
 * View Admin As - Class Compat
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

namespace View_Admin_As;

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Compatibility class.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.0
 * @version 1.8.6
 * @uses    \View_Admin_As\Base Extends class
 */
final class Compat extends Base
{
	/**
	 * Fix compatibility issues.
	 *
	 * @since   0.1.0
	 * @since   1.6.0  Moved `third_party_compatibility()` from `VAA_View_Admin_As`.
	 * @access  public
	 * @return  void
	 */
	public function init() {

		$this->add_action( 'vaa_view_admin_as_init', array( $this, 'init_after' ) );

		/**
		 * Add our caps to the members plugin.
		 * Hook `members_get_capabilities` also used by:
		 *  - User Role Editor (URE) >> Own filter: `ure_full_capabilites`
		 *  - WPFront User Role Editor
		 *  - Capability Manager Enhanced >> Own filter: `capsman_get_capabilities`
		 *  - Pods
		 *  - Yoast SEO 5.8+
		 *
		 * @since  1.6.0
		 */
		$this->add_filter( 'members_get_capabilities', array( $this, 'get_vaa_capabilities' ) );
		$this->add_action( 'members_register_cap_groups', array( $this, 'action_members_register_cap_group' ) );

		/**
		 * Add our caps to the User Role Editor plugin (URE).
		 * @since  1.6.4
		 */
		$this->add_filter( 'ure_capabilities_groups_tree', array( $this, 'filter_ure_capabilities_groups_tree' ) );
		$this->add_filter( 'ure_custom_capability_groups', array( $this, 'filter_ure_custom_capability_groups' ), 10, 2 );

		/**
		 * Get all capabilities.
		 * @since  1.5.0
		 */
		$this->add_filter( 'view_admin_as_get_capabilities', array( $this, 'get_capabilities' ), 10, 2 );

		/**
		 * WP Admin UI Customize 1.5.11+.
		 * @since  1.7.4
		 */
		// wauc_admin_bar_default_load
		$this->add_filter( 'wauc_admin_bar_menu_add_nodes', array( $this, 'filter_wauc_admin_bar_menu_add_nodes' ), 10, 2 );
		$this->add_filter( 'wauc_admin_bar_filter_load', array( $this, 'filter_wauc_admin_bar_filter_load' ) );
		$this->add_filter( 'wauc_admin_bar_menu_widget_no_submenu', array( $this, 'filter_wauc_admin_bar_menu_widget_no_submenu' ) );
		$this->add_filter( 'wauc_admin_bar_menu_widget_title_readonly_vaa', '__return_true' );
		$this->add_filter( 'wauc_admin_bar_menu_widget_disable_target_vaa', '__return_true' );

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

		if ( API::is_user_modified() ) {
			// Only apply the filter if the current user is modified.
			$this->add_filter( 'pods_is_admin', array( $this, 'filter_pods_caps_check' ), 99, 2 );
		}
	}

	/**
	 * Get's current capabilities and merges with capabilities from other plugins.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @see     \View_Admin_As\Compat::init()
	 *
	 * @param   array   $caps  The capabilities.
	 * @param   bool[]  $args  Pass arguments to get only certain capabilities.
	 * @return  array   Both key and values are the capabilities.
	 */
	public function get_capabilities( $caps = array(), $args = array() ) {

		$defaults = array(
			'vaa'     => true, // Get VAA related capabilities?
			'wp'      => true, // Get WP core related capabilities?
			'plugins' => true, // Get capabilities from plugin hooks and filters?
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['vaa'] ) {
			$caps = $this->get_vaa_capabilities( $caps );
		}

		if ( $args['wp'] ) {
			$caps = $this->get_wordpress_capabilities( $caps );
		}

		if ( $args['plugins'] ) {
			$caps = $this->get_plugin_capabilities( $caps );
		}

		// @since  1.8.0  Make sure both keys and values are capabilities.
		$all_caps = array();
		foreach ( $caps as $cap_key => $cap ) {
			if ( is_string( $cap ) && ! is_numeric( $cap ) ) {
				$all_caps[ $cap ] = $cap;
			} elseif ( is_string( $cap_key ) && ! is_numeric( $cap_key ) ) {
				$all_caps[ $cap_key ] = $cap_key;
			}
		}

		return $all_caps;
	}

	/**
	 * Add our capabilities to an existing list of capabilities.
	 *
	 * @since   1.6.0
	 * @since   1.7.3  Renamed from `add_capabilities()`.
	 * @access  public
	 * @see     \View_Admin_As\Compat::init()
	 *
	 * @param   array  $caps  The capabilities.
	 * @return  array
	 */
	public function get_vaa_capabilities( $caps = array() ) {

		// Allow VAA modules to add their capabilities.
		foreach ( (array) apply_filters( 'view_admin_as_add_capabilities', array( 'view_admin_as' ) ) as $cap ) {
			$caps[ $cap ] = $cap;
		}

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
		foreach ( $this->store->get_roles() as $role ) {
			if ( is_array( $role->capabilities ) ) {
				$role_caps = array_keys( $role->capabilities );
				$caps      = array_merge( array_combine( $role_caps, $role_caps ), $caps );
			}
		}

		/**
		 * @since  1.7.1  Add post type and taxonomy caps.
		 * @since  1.7.3  Prevent duplicate array keys.
		 */
		$wp_objects = array_merge(
			array_values( (array) get_post_types( array(), 'objects' ) ),
			array_values( (array) get_taxonomies( array(), 'objects' ) )
		);
		foreach ( $wp_objects as $obj ) {
			if ( ! empty( $obj->cap ) ) {
				// WP stores the object caps as general_cap_name => actual_cap.
				$caps = array_merge( array_combine( (array) $obj->cap, (array) $obj->cap ), $caps );
			}
		}

		/**
		 * Other WordPress capabilities.
		 * @since  1.7.4  WordPress 4.9 capabilities.
		 * @since  1.8.3  WordPress 4.9.6 privacy capabilities.
		 */
		if ( API::validate_wp_version( '4.9' ) ) {
			$caps['activate_plugin']    = 'activate_plugin';
			$caps['deactivate_plugin']  = 'deactivate_plugin';
			$caps['deactivate_plugins'] = 'deactivate_plugins';
			$caps['install_languages']  = 'install_languages';
			$caps['update_languages']   = 'update_languages';
		}
		if ( API::validate_wp_version( '4.9.6' ) ) {
			$caps['erase_others_personal_data']  = 'erase_others_personal_data';
			$caps['export_others_personal_data'] = 'export_others_personal_data';
			$caps['manage_privacy_options']      = 'manage_privacy_options';
		}

		/**
		 * Network capabilities.
		 * @since  1.5.3
		 * @since  1.7.2  Added new WP 4.8 caps.
		 *                https://make.wordpress.org/core/2017/05/22/multisite-focused-changes-in-4-8/
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
			if ( API::validate_wp_version( '4.8' ) ) {
				$network_caps[] = 'upgrade_network';
				$network_caps[] = 'setup_network';
			}
			$caps = array_merge( array_combine( $network_caps, $network_caps ), $caps );
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
		if ( API::exists_callable( array( '\GFCommon', 'all_caps' ) ) ) {
			$caps = array_merge( (array) \GFCommon::all_caps(), $caps );
		}

		// @since  1.7.1  User Role Editor.
		if ( API::exists_callable( array( '\URE_Own_Capabilities', 'get_caps' ) ) ) {
			$caps = array_merge( (array) \URE_Own_Capabilities::get_caps(), $caps );
		}
		// @since  1.8.6  Parse hooked caps.
		foreach ( apply_filters( 'ure_full_capabilites', array() ) as $cap ) {
			if ( is_array( $cap ) ) {
				if ( empty( $cap['inner'] ) ) {
					continue;
				}
				$cap = $cap['inner'];
			}
			$caps[ $cap ] = $cap;
		}

		// @since  1.7.1  WPFront User Role Editor.
		if ( class_exists( '\WPFront_User_Role_Editor' ) && ! empty( \WPFront_User_Role_Editor::$ROLE_CAPS ) ) {
			$caps = array_merge( (array) \WPFront_User_Role_Editor::$ROLE_CAPS, $caps );
		}

		// @since  1.7.1  User Roles and Capabilities.
		if ( API::exists_callable( array( '\Solvease_Roles_Capabilities_User_Caps', 'solvease_roles_capabilities_caps' ) ) ) {
			$caps = array_merge( (array) \Solvease_Roles_Capabilities_User_Caps::solvease_roles_capabilities_caps(), $caps );
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
				// @see bp_get_community_caps() >> bp-core-caps.php.
				apply_filters( 'bp_get_community_caps', array() ),
				$caps
			);
		} // End if().

		// @since  1.7.4  Yoast SEO 5.5+  Load integration on front end.
		if ( ! is_admin() ) {
			/**
			 * @since  1.8.0  Yoast SEO 8.3+ - Check for API function.
			 * @link   https://github.com/Yoast/wordpress-seo/pull/9365
			 */
			if ( function_exists( 'wpseo_get_capabilities' ) ) {
				$caps = array_merge( (array) wpseo_get_capabilities(), $caps );
			} elseif ( defined( 'WPSEO_VERSION' ) ) {
				$caps = array_merge(
					array(
						// Yoast SEO caps.
						'wpseo_bulk_edit',
						'wpseo_edit_advanced_metadata',
						'wpseo_manage_options',
					),
					$caps
				);
			}
		}

		// @since  1.8.6  Google Site Kit.
		if ( defined( 'GOOGLESITEKIT_VERSION' ) ) {
			// @todo https://github.com/JoryHogeveen/view-admin-as/issues/110
			$caps = array_merge(
				array(
					'googlesitekit_authenticate',
					'googlesitekit_setup',
					'googlesitekit_view_posts_insights',
					'googlesitekit_view_dashboard',
					'googlesitekit_view_module_details',
					'googlesitekit_manage_options',
					'googlesitekit_publish_posts',
				),
				$caps
			);
		}

		// Members.
		if ( function_exists( 'members_get_plugin_capabilities' ) ) {
			$caps = array_merge( (array) members_get_plugin_capabilities(), $caps );
		}
		// Get caps from multiple plugins through the Members filter.
		$caps = array_merge( (array) apply_filters( 'members_get_capabilities', $caps ), $caps );

		// Pods.
		$caps = array_merge( (array) apply_filters( 'pods_roles_get_capabilities', $caps ), $caps );

		return $caps;

	}

	/**
	 * Fix compatibility issues Pods Framework 2.0+.
	 *
	 * @since   1.0.1
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.6.2  Check for all provided capabilities.
	 * @access  public
	 * @see     \View_Admin_As\Compat::init()
	 *
	 * @param   bool   $bool  Boolean provided by the pods_is_admin hook (not used).
	 * @param   array  $caps  String or Array provided by the pods_is_admin hook.
	 * @return  bool
	 */
	public function filter_pods_caps_check( $bool, $caps ) {

		foreach ( (array) $caps as $capability ) {
			if ( view_admin_as()->view()->current_view_can( $capability ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add our capabilities to our own group in the Members plugin.
	 *
	 * @see  members_register_cap_group()
	 *
	 * @since   1.6.0
	 * @access  public
	 * @see     \View_Admin_As\Compat::init()
	 */
	public function action_members_register_cap_group() {
		if ( ! function_exists( 'members_register_cap_group' ) ) {
			return;
		}
		// Register the vaa group.
		members_register_cap_group(
			'view_admin_as',
			array(
				'label'      => esc_html__( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ),
				'caps'       => $this->get_vaa_capabilities(),
				'icon'       => 'dashicons-visibility',
				'diff_added' => true,
			)
		);
	}

	/**
	 * Add our our own capability group in the URE plugin.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @see     \View_Admin_As\Compat::init()
	 * @see     \URE_Capabilities_Groups_Manager::get_groups_tree()
	 * @param   array  $groups  Current groups
	 * @return  array
	 */
	public function filter_ure_capabilities_groups_tree( $groups ) {
		$groups = (array) $groups;

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
	 * @see     \View_Admin_As\Compat::init()
	 * @see     \URE_Capabilities_Groups_Manager::get_cap_groups()
	 * @param   array   $groups  Current capability groups.
	 * @param   string  $cap_id  Capability identifier.
	 * @return  array
	 */
	public function filter_ure_custom_capability_groups( $groups, $cap_id ) {
		if ( in_array( $cap_id, $this->get_vaa_capabilities(), true ) ) {
			$groups   = (array) $groups;
			$groups[] = 'view_admin_as';
		}
		return $groups;
	}

	/**
	 * Force user setting location when WAUC is active.
	 *
	 * Disable some PHPMD checks for this method.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @todo Refactor to enable above checks?
	 *
	 * @since   1.7.4
	 * @access  public
	 * @see     \WP_Admin_UI_Customize::admin_bar_menu()
	 * @param   array  $wauc_nodes
	 * @param   array  $all_nodes
	 * @return  array
	 */
	public function filter_wauc_admin_bar_menu_add_nodes( $wauc_nodes, $all_nodes ) {

		$admin_menu_location = $this->store->get_userSettings( 'admin_menu_location' );
		$vaa_root            = Admin_Bar::$root;

		$check = array(
			'depth' => 'main',
		);
		if ( 'my-account' === $admin_menu_location ) {
			$check['depth']    = 'sub';
			$check['location'] = 'right';
			$check['parent']   = $admin_menu_location;
		}

		// Compat when this node is added twice.
		foreach ( $wauc_nodes as $location => $levels ) {
			foreach ( $levels as $depth => $nodes ) {
				foreach ( $nodes as $id => $node ) {
					if ( isset( $node['id'] ) && $vaa_root === $node['id'] ) {

						$current = array(
							'depth'    => $depth,
							'location' => $location,
							'parent'   => $node['parent'],
						);

						foreach ( $check as $key => $val ) {
							if ( $val !== $current[ $key ] ) {
								unset( $wauc_nodes[ $location ][ $depth ][ $id ] );
								break;
							}
						}

					}
				}
			}
		}

		// Make sure all VAA nodes will be added.
		$wauc_nodes['vaa'] = array( 'sub' => array() );
		foreach ( $all_nodes as $node ) {
			$id = ( ! empty( $node->id ) ) ? $node->id : null;
			if ( $vaa_root !== $id && 0 === strpos( $id, $vaa_root ) ) {
				$wauc_nodes['vaa']['sub'][] = (array) $node;
			}
		}

		return $wauc_nodes;
	}

	/**
	 * Remove our nodes from the WAUC admin bar editor.
	 * @since   1.7.4
	 * @access  public
	 * @see     \WP_Admin_UI_Customize::admin_bar_filter_load()
	 * @param   array  $all_nodes
	 * @return  array
	 */
	public function filter_wauc_admin_bar_filter_load( $all_nodes ) {

		if ( empty( $all_nodes['right'] ) ) {
			return $all_nodes;
		}

		$slug = Admin_Bar::$root;

		foreach ( (array) $all_nodes['right'] as $location => $nodes ) {
			if ( 0 !== strpos( $location, 'sub' ) ) {
				continue;
			}
			foreach ( $nodes as $key => $node ) {
				// Check if node ID starts with `vaa-` and node parent starts with `vaa`.
				if ( 0 === strpos( $node->id, $slug . '-' ) && 0 === strpos( $node->parent, $slug ) ) {
					unset( $all_nodes['right'][ $location ][ $key ] );
				}
			}
		}
		return $all_nodes;
	}

	/**
	 * Remove submenu options for WAUC.
	 * @since   1.7.4
	 * @see     \WP_Admin_UI_Customize::admin_bar_menu_widget()
	 * @param   array  $no_submenu
	 * @return  array
	 */
	public function filter_wauc_admin_bar_menu_widget_no_submenu( $no_submenu ) {
		$no_submenu[] = Admin_Bar::$root;
		return $no_submenu;
	}

} // End class \View_Admin_As\Compat.
