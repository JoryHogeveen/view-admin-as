<?php
/**
 * View Admin As - Role Manager Module
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Add or remove roles and grant or deny them capabilities.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.7
 * @version 1.7.2
 * @uses    VAA_View_Admin_As_Module Extends class
 */
final class VAA_View_Admin_As_Role_Manager extends VAA_View_Admin_As_Module
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.7
	 * @static
	 * @var    VAA_View_Admin_As_Role_Manager
	 */
	private static $_instance = null;

	/**
	 * Module key.
	 *
	 * @since  1.7.2
	 * @var    string
	 */
	protected $moduleKey = 'role_manager';

	/**
	 * Option key.
	 *
	 * @since  1.7
	 * @var    string
	 */
	protected $optionKey = 'vaa_role_manager';

	/**
	 * The WP_Roles object.
	 *
	 * @since  1.7
	 * @var    WP_Roles
	 */
	public $wp_roles = null;

	/**
	 * Protected roles.
	 * These roles cannot be removed.
	 *
	 * @since  1.7
	 * @var    array
	 */
	private $protected_roles = array();

	/**
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.7
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		/**
		 * Only allow module for admin users.
		 *
		 * @since  1.7
		 */
		if ( is_network_admin() || ! VAA_API::is_super_admin() ) {
			return;
		}

		// Add this class to the modules in the main class.
		$this->vaa->register_module( array(
			'id'       => $this->moduleKey,
			'instance' => self::$_instance,
		) );

		// Load data.
		$this->set_optionData( get_option( $this->get_optionKey() ) );

		/**
		 * Checks if the management part of module should be enabled.
		 *
		 * @since  1.7
		 */
		if ( $this->get_optionData( 'enable' ) ) {
			$this->enable = true;
			$this->init();
		}

		add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
		add_filter( 'view_admin_as_handle_ajax_' . $this->moduleKey, array( $this, 'ajax_handler' ), 10, 2 );
	}

	/**
	 * Init function for global functions (not user dependent).
	 *
	 * @since   1.7
	 * @access  private
	 * @global  WP_Roles  $wp_roles
	 * @return  void
	 */
	private function init() {

		// Check for the wp_roles() function in WP 4.3+.
		if ( function_exists( 'wp_roles' ) ) {
			$this->wp_roles = wp_roles();
		} else {
			global $wp_roles;
			$this->wp_roles = $wp_roles;
		}

		if ( ! $this->wp_roles->use_db ) {
			$this->set_enable( false );
			$this->vaa->add_notice(
				'role_manager_no_db',
				array(
					'type' => 'warning',
					'message' => __( 'The Role Manager module was disabled because database storage for roles is disabled in WordPress.', VIEW_ADMIN_AS_DOMAIN ),
				)
			);
		}

		// Define protected roles.
		$default_role = get_option( 'default_role' ); // Normally `subscriber`.
		$this->protected_roles = array(
			'administrator' => 'administrator',
			$default_role => $default_role,
		);
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view.
	 *
	 * @since   1.7
	 * @access  public
	 * @return  void
	 */
	public function vaa_init() {

		// Enabling this module can only be done by a super admin.
		if ( VAA_API::is_super_admin() ) {

			// Add adminbar menu items in settings section.
			add_action( 'vaa_admin_bar_modules', array( $this, 'admin_bar_menu_modules' ), 10, 2 );
		}

		// Add adminbar menu items in role section.
		if ( $this->is_enabled() ) {

			// Show the admin bar node.
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), 6, 2 );
			add_action( 'vaa_admin_bar_caps_manager_before', array( $this, 'admin_bar_menu_caps' ), 6, 2 );
		}
	}

	/**
	 * Data update handler (Ajax probably), called from main handler.
	 *
	 * Disable some PHPMD checks for this method.
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @todo Refactor to enable above checks?
	 *
	 * @since   1.7
	 * @access  public
	 * @param   null   $null  Null.
	 * @param   array  $data  The ajax data for this module.
	 * @return  array|string|bool
	 */
	public function ajax_handler( $null, $data ) {

		if ( ! $this->is_valid_ajax() ) {
			return $null;
		}

		$success = false;

		// Validate super admin.
		if ( VAA_API::is_super_admin() ) {

			if ( isset( $data['enable'] ) ) {
				$success = $this->set_enable( (bool) $data['enable'] );
				// Prevent further processing.
				return $success;
			}

		}

		// From here all features need this module enabled.
		if ( ! $this->is_enabled() ) {
			return $success;
		}

		$options = array(
			'apply_view_to_role' => array(
				'validation' => 'is_array',
				'values'     => array( 'role' => '', 'capabilities' => '' ),
				'callback'   => 'save_role',
			),
			'save_role' => array(
				'validation' => 'is_array',
				'values'     => array( 'role' => '', 'capabilities' => '' ),
				'callback'   => 'save_role',
			),
			'rename_role' => array(
				'validation' => 'is_array',
				'values'     => array( 'role' => '', 'new_name' => '' ),
				'callback'   => 'rename_role',
			),
			'clone_role' => array(
				'validation' => 'is_array',
				'values'     => array( 'role' => '', 'new_role' => '' ),
				'callback'   => 'clone_role',
			),
			'delete_role'    => array(
				'validation' => 'is_string',
				'callback'   => 'delete_role',
			),
		);

		foreach ( $options as $key => $val ) {
			if ( VAA_API::array_has( $data, $key, array( 'validation' => $val['validation'] ) ) ) {
				if ( 'is_array' === $val['validation'] && array_diff_key( $val['values'], $data[ $key ] ) ) {
					$success = array(
						'success' => false,
						'data' => __( 'No valid data found', VIEW_ADMIN_AS_DOMAIN ),
					);
				} else {
					$args = (array) $data[ $key ];
					if ( VAA_API::array_has( $val, 'values', array( 'validation' => 'is_array' ) ) ) {
						// Make sure the arguments are in the right order.
						$args = array_merge( $val['values'], $args );
					}
					$success = call_user_func_array( array( $this, $val['callback'] ), $args );
				}
				// @todo Maybe allow more settings to be applied at the same time?
				break;
			}
		}

		return $success;
	}

	/**
	 * Save a role.
	 * Can also add a new role when it doesn't exist.
	 *
	 * @since   1.7
	 * @access  public
	 * @param   string  $role          The role name (ID).
	 * @param   array   $capabilities  The new role capabilities.
	 * @return  mixed
	 */
	public function save_role( $role, $capabilities ) {
		if ( ! is_string( $role ) || ! is_array( $capabilities ) ) {
			return array(
				'success' => false,
				'data' => __( 'No valid data found', VIEW_ADMIN_AS_DOMAIN ),
			);
		}

		// @see wp-includes/capabilities.php
		$existing_role = get_role( $role );
		// Build role name. (Only used for adding a new role).
		$role_name     = self::sanitize_role_name( $role );
		/**
		 * Sanitize capabilities.
		 * @since  1.7
		 * @since  1.7.2  Use foreach loop. boolval() is PHP 5.5+.
		 * @example  $capabilities = array_map( 'boolval', $capabilities );
		 */
		foreach ( $capabilities as $cap => $grant ) {
			$capabilities[ $cap ] = (bool) $grant;
		}

		if ( ! $existing_role ) {
			// Sanitize role slug/key.
			$role = self::sanitize_role_slug( $role );
			// Recheck for an existing role.
			$existing_role = get_role( $role );
		}

		if ( $existing_role ) {
			// Update role.
			$role = $existing_role;
			$this->update_role_caps( $role, $capabilities );
		} else {
			// Add new role.
			// Only leave granted capabilities.
			// @todo Option to deny capability (like Members).
			$capabilities = array_filter( $capabilities );
			// @see wp-includes/capabilities.php
			$new_role = add_role( $role, $role_name, $capabilities );

			if ( $new_role ) {
				return true;
			}
			// Very unlikely that this will happen but still..
			return array(
				'success' => false,
				'data' => __( 'Role already exists', VIEW_ADMIN_AS_DOMAIN ),
			);
		}
		return true;
	}

	/**
	 * Update a role with new capabilities.
	 *
	 * @since   1.7
	 * @access  public
	 * @param   WP_Role  $role          The role object.
	 * @param   array    $capabilities  The new role capabilities.
	 */
	public function update_role_caps( $role, $capabilities ) {
		if ( $role instanceof WP_Role ) {
			// Ensure we have all the caps (even old ones that need to be removed).
			$caps = array_merge( $role->capabilities, $capabilities );
			// Update existing role.
			foreach ( $caps as $cap => $grant ) {
				// @todo Option to deny capability (like Members).
				// @todo Do this in one call (prevent a lot of queries).
				if ( ! empty( $capabilities[ $cap ] ) ) {
					$role->add_cap( (string) $cap, (bool) $grant );
				} else {
					$role->remove_cap( (string) $cap );
				}
			}
		}
	}

	/**
	 * Clone a role.
	 *
	 * @since   1.7
	 * @access  public
	 * @param   string  $role      The role name.
	 * @param   string  $new_role  The new role name.
	 * @return  mixed
	 */
	public function clone_role( $role, $new_role ) {
		// Do not use WP's get_role() because one can only clone a role it's allowed to see.
		$role = $this->store->get_roles( $role );
		if ( $role ) {
			$this->save_role( $new_role, $role->capabilities );
			return true;
		}
		return __( 'Role not found', VIEW_ADMIN_AS_DOMAIN );
	}

	/**
	 * Rename a role.
	 *
	 * @since   1.7
	 * @access  public
	 * @param   string  $role          The source role slug/ID.
	 * @param   string  $new_name      The new role label.
	 * @return  bool|string
	 */
	public function rename_role( $role, $new_name ) {
		$slug = $role;
		// Do not use WP's get_role() because one can only clone a role it's allowed to see.
		$role = $this->store->get_roles( $role );
		if ( $role ) {
			// @todo Check https://core.trac.wordpress.org/ticket/40320.
			$new_name = self::sanitize_role_name( $new_name );

			$this->wp_roles->role_objects[ $slug ]->name = $new_name;
			$this->wp_roles->role_names[ $slug ] = $new_name;
			$this->wp_roles->roles[ $slug ]['name'] = $new_name;

			update_option( $this->wp_roles->role_key, $this->wp_roles->roles );

			return true;
		}
		return __( 'Role not found', VIEW_ADMIN_AS_DOMAIN );
	}

	/**
	 * Delete a role from the database.
	 *
	 * @since   1.7
	 * @access  public
	 * @param   string  $role  The role name.
	 * @return  bool|string
	 */
	public function delete_role( $role ) {
		if ( $this->store->get_roles( $role ) ) {
			if ( ! in_array( $role, $this->protected_roles, true ) ) {
				remove_role( $role );
				return true;
			}
			return __( 'This role cannot be removed', VIEW_ADMIN_AS_DOMAIN );
		}
		return __( 'Role not found', VIEW_ADMIN_AS_DOMAIN );
	}

	/**
	 * Convert role slug into a role name.
	 * Formats the name by default (capitalize and convert underscores to spaces).
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   string  $role_name  The role ID/slug.
	 * @param   bool    $format     Apply string formatting.
	 * @return  string
	 */
	public static function sanitize_role_name( $role_name, $format = true ) {
		$role_name = strip_tags( $role_name );
		if ( $format ) {
			$role_name = str_replace( array( '_' ), ' ', $role_name );
			$role_name = ucwords( $role_name );
		}
		return trim( $role_name );
	}

	/**
	 * Convert role name/label into a role slug.
	 * Similar to sanitize_key but it converts spaces and dashed to underscores.
	 *
	 * @since   1.7.1
	 * @access  public
	 * @param   string  $role_name  The role name/label.
	 * @return  string
	 */
	public static function sanitize_role_slug( $role_name ) {
		$role_name = sanitize_title_with_dashes( $role_name );
		$role_name = str_replace( array( ' ', '-' ), '_', $role_name );
		//$role_name = sanitize_key( $role_name );
		return $role_name;
	}

	/**
	 * Add admin bar module setting items.
	 *
	 * @since   1.7
	 * @access  public
	 * @see     'vaa_admin_bar_modules' action
	 *
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item (vaa-settings).
	 * @return  void
	 */
	public function admin_bar_menu_modules( $admin_bar, $root ) {

		$root_prefix = $root . '-role-manager';

		$admin_bar->add_node( array(
			'id'     => $root_prefix . '-enable',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_checkbox( array(
				'name'        => $root_prefix . '-enable',
				'value'       => $this->get_optionData( 'enable' ),
				'compare'     => true,
				'label'       => __( 'Enable role manager', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Add or remove roles and grant or deny them capabilities', VIEW_ADMIN_AS_DOMAIN ),
				'auto_js' => array(
					'setting' => $this->moduleKey,
					'key'     => 'enable',
					'refresh' => true,
				),
			) ),
			'href'   => false,
			'meta'   => array(
				'class' => 'auto-height',
			),
		) );

	}

	/**
	 * Add admin bar menu's.
	 *
	 * @since   1.7
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 *
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item (vaa).
	 * @return  void
	 */
	public function admin_bar_menu( $admin_bar, $root ) {

		$admin_bar->add_node( array(
			'id'     => $root . '-role-manager',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-id-alt' ) . __( 'Role manager', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon',
				'tabindex' => '0',
			),
		) );

		$root = $root . '-role-manager';

		// Notice for capability editor location.
		$admin_bar->add_node( array(
			'id'     => $root . '-intro',
			'parent' => $root,
			// Translators: %s stands for "Capabilities".
			'title'  => sprintf( __( 'You can add/edit roles under "%s"', VIEW_ADMIN_AS_DOMAIN ), __( 'Capabilities', VIEW_ADMIN_AS_DOMAIN ) ),
			'href'   => false,
		) );

		$this->admin_bar_menu_bulk_actions( $admin_bar, $root );
	}

	/**
	 * Add admin bar menu bulk actions.
	 *
	 * Disable some PHPMD checks for this method.
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @todo Refactor to enable above checks?
	 *
	 * @since   1.7  Separated the tools from the main function.
	 * @access  public
	 * @see     admin_bar_menu()
	 *
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item (vaa).
	 * @return  void
	 */
	private function admin_bar_menu_bulk_actions( $admin_bar, $root ) {

		$role_select_options = array(
			'' => array(
				'label' => ' --- ' . __( 'Select role', VIEW_ADMIN_AS_DOMAIN ) . ' --- ',
			),
		);
		foreach ( $this->store->get_rolenames() as $role_key => $role_name ) {
			// Add the default role names/keys for reference.
			$desc = array();
			$org_name = $this->store->get_rolenames( $role_key, false );
			if ( $org_name !== $role_name ) {
				$desc[] = $org_name;
			}
			if ( self::sanitize_role_slug( $org_name ) !== $role_key ) {
				$desc[] = $role_key;
			}
			if ( $desc ) {
				$role_name .= ' &nbsp; (' . implode( ', ', $desc ) . ')';
			}
			$role_select_options[ $role_key ] = array(
				'value' => esc_attr( $role_key ),
				'label' => esc_html( $role_name ),
			);
		}

		/**
		 * @since  1.7  Apply current view capabilities to role.
		 */
		$icon = 'dashicons-hidden';
		if ( $this->store->get_view() ) {
			$icon = 'dashicons-visibility';
		}
		$admin_bar->add_group( array(
			'id'     => $root . '-apply-view',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-apply-view-title',
			'parent' => $root . '-apply-view',
			'title'  => VAA_View_Admin_As_Form::do_icon( $icon ) . __( 'Apply current view capabilities to role', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-bold vaa-has-icon ab-vaa-toggle',
				'tabindex' => '0',
			),
		) );

		if ( $this->store->get_selectedCaps() ) {
			$admin_bar->add_node( array(
				'id'     => $root . '-apply-view-select',
				'parent' => $root . '-apply-view',
				'title'  => VAA_View_Admin_As_Form::do_select(
					array(
						'name'   => $root . '-apply-view-select',
						'values' => $role_select_options,
					)
				),
				'href'   => false,
				'meta'   => array(
					'class' => 'ab-vaa-select select-role',
				),
			) );
			// @todo Find a way to get the current view server-side (view capabilities aren't available yet in ajax handling).
			$admin_bar->add_node( array(
				'id'     => $root . '-apply-view-apply',
				'parent' => $root . '-apply-view',
				'title'  => VAA_View_Admin_As_Form::do_button( array(
					'name'  => $root . '-apply-view-apply',
					'label' => __( 'Apply', VIEW_ADMIN_AS_DOMAIN ),
					'class' => 'button-primary',
					'attr'  => array(
						'vaa-view-caps' => wp_json_encode( $this->store->get_selectedCaps() ),
					),
					'auto_js' => array(
						'setting' => $this->moduleKey,
						'key'     => 'apply_view_to_role',
						'refresh' => false,
						'values'  => array(
							'role' => array(
								'element' => '#wp-admin-bar-' . $root . '-apply-view-select select#' . $root . '-apply-view-select',
								'parser'  => '', // Default.
							),
							'capabilities' => array(
								'attr' => 'vaa-view-caps',
								'json' => true,
							),
						),
					),
				) ),
				'href'   => false,
				'meta'   => array(
					'class' => 'vaa-button-container',
				),
			) );
		} else {
			$admin_bar->add_node( array(
				'id'     => $root . '-apply-view-notice',
				'parent' => $root . '-apply-view',
				'title'  => __( 'No view selected', VIEW_ADMIN_AS_DOMAIN ),
				'href'   => false,
			) );
		} // End if().

		/**
		 * @since  1.7.1  Rename role.
		 */
		$admin_bar->add_group( array(
			'id'     => $root . '-rename',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-rename-title',
			'parent' => $root . '-rename',
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-edit' ) . __( 'Rename role', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-bold vaa-has-icon ab-vaa-toggle',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-rename-select',
			'parent' => $root . '-rename',
			'title'  => VAA_View_Admin_As_Form::do_select(
				array(
					'name'   => $root . '-rename-select',
					'values' => $role_select_options,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-select select-role',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-rename-input',
			'parent' => $root . '-rename',
			'title'  => VAA_View_Admin_As_Form::do_input(
				array(
					'name'        => $root . '-rename-input',
					'placeholder' => __( 'New role name', VIEW_ADMIN_AS_DOMAIN ),
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-input rename-role',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-rename-apply',
			'parent' => $root . '-rename',
			'title'  => VAA_View_Admin_As_Form::do_button( array(
				'name'    => $root . '-rename-apply',
				'label'   => __( 'Apply', VIEW_ADMIN_AS_DOMAIN ),
				'class'   => 'button-primary',
				'auto_js' => array(
					'setting' => $this->moduleKey,
					'key'     => 'rename_role',
					'refresh' => true,
					'values'  => array(
						'role' => array(
							'element' => '#wp-admin-bar-' . $root . '-rename-select select#' . $root . '-rename-select',
							'parser'  => '', // Default.
						),
						'new_name' => array(
							'element' => '#wp-admin-bar-' . $root . '-rename-input input#' . $root . '-rename-input',
							'parser'  => '',
						),
					),
				),
			) ),
			'href'   => false,
			'meta'   => array(
				'class' => 'vaa-button-container',
			),
		) );

		/**
		 * @since  1.7  Clone role.
		 */
		$admin_bar->add_group( array(
			'id'     => $root . '-clone',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-clone-title',
			'parent' => $root . '-clone',
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-pressthis' ) . __( 'Clone role', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-bold vaa-has-icon ab-vaa-toggle',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-clone-select',
			'parent' => $root . '-clone',
			'title'  => VAA_View_Admin_As_Form::do_select(
				array(
					'name'   => $root . '-clone-select',
					'values' => $role_select_options,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-select select-role',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-clone-input',
			'parent' => $root . '-clone',
			'title'  => VAA_View_Admin_As_Form::do_input(
				array(
					'name'        => $root . '-clone-input',
					'placeholder' => __( 'New role name', VIEW_ADMIN_AS_DOMAIN ),
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-input clone-role',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-clone-apply',
			'parent' => $root . '-clone',
			'title'  => VAA_View_Admin_As_Form::do_button( array(
				'name'    => $root . '-clone-apply',
				'label'   => __( 'Apply', VIEW_ADMIN_AS_DOMAIN ),
				'class'   => 'button-primary',
				'auto_js' => array(
					'setting' => $this->moduleKey,
					'key'     => 'clone_role',
					'refresh' => true,
					'values'  => array(
						'role' => array(
							'element' => '#wp-admin-bar-' . $root . '-clone-select select#' . $root . '-clone-select',
							'parser'  => '', // Default.
						),
						'new_role' => array(
							'element' => '#wp-admin-bar-' . $root . '-clone-input input#' . $root . '-clone-input',
							'parser'  => '',
						),
					),
				),
			) ),
			'href'   => false,
			'meta'   => array(
				'class' => 'vaa-button-container',
			),
		) );

		/**
		 * @since  1.7  Delete role.
		 */
		$role_select_options = array_diff_key( $role_select_options, $this->protected_roles );
		$admin_bar->add_group( array(
			'id'     => $root . '-delete',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary vaa-sub-transparent',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-delete-title',
			'parent' => $root . '-delete',
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-trash' ) . __( 'Delete role', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-bold vaa-has-icon ab-vaa-toggle',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-delete-select',
			'parent' => $root . '-delete',
			'title'  => VAA_View_Admin_As_Form::do_select(
				array(
					'name'   => $root . '-delete-select',
					'values' => $role_select_options,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-select select-role',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-delete-apply',
			'parent' => $root . '-delete',
			'title'  => VAA_View_Admin_As_Form::do_button( array(
				'name'    => $root . '-delete-apply',
				'label'   => __( 'Delete', VIEW_ADMIN_AS_DOMAIN ),
				'class'   => 'button-primary',
				'auto_js' => array(
					'setting' => $this->moduleKey,
					'key'     => 'delete_role',
					'confirm' => true,
					'refresh' => true,
					'value'   => array(
						'element' => '#wp-admin-bar-' . $root . '-delete-select select#' . $root . '-delete-select',
						'parser'  => '', // Default.
					),
				),
			) ),
			'href'   => false,
			'meta'   => array(
				'class'  => 'vaa-button-container',
			),
		) );
	}

	/**
	 * Add admin bar items to the capability node.
	 *
	 * Disable some PHPMD checks for this method.
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @todo Refactor to enable above checks?
	 *
	 * @since   1.7
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 *
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item (vaa).
	 * @return  void
	 */
	public function admin_bar_menu_caps( $admin_bar, $root ) {

		$admin_bar->add_group( array(
			'id'     => $root . '-role-manager',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-vaa-spacing-top',
			),
		) );

		$root = $root . '-role-manager';

		$admin_bar->add_node( array(
			'id'     => $root . '-roles-title',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-id-alt' ) . __( 'Role manager', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-bold vaa-has-icon ab-vaa-toggle',
				'tabindex' => '0',
			),
		) );

		$caps = $this->store->get_curUser()->allcaps;
		if ( $this->store->get_view() ) {
			$caps = $this->store->get_selectedCaps();
		}
		$role_select_options = array(
			'' => array(
				'label' => ' --- ' . __( 'Add/Edit role', VIEW_ADMIN_AS_DOMAIN ) . ' --- ',
				'attr'  => array(
					'data-caps' => wp_json_encode( $caps ),
				),
			),
			'__new__' => array(
				'value' => '__new__',
				'label' => ' ++ ' . __( 'New role', VIEW_ADMIN_AS_DOMAIN ) . ' ++ ',
			),
		);
		foreach ( $this->store->get_roles() as $role_key => $role ) {
			$data_caps = wp_json_encode( $role->capabilities );
			$role_select_options[ $role_key ] = array(
				'compare' => esc_attr( $role_key ),
				'label'   => $this->store->get_rolenames( $role_key ),
				'attr'    => array(
					'data-caps' => $data_caps,
				),
			);
		}

		$admin_bar->add_node( array(
			'id'     => $root . '-edit-role',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_select(
				array(
					'name'   => $root . '-edit-role',
					'values' => $role_select_options,
				)
			) . VAA_View_Admin_As_Form::do_button(
				array(
					'name'  => $root . '-save-role',
					'label' => __( 'Save role', VIEW_ADMIN_AS_DOMAIN ),
					'class' => 'button-primary input-overlay',
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-select',
			),
		) );

		$admin_bar->add_node( array(
			'id'     => $root . '-new-role',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_input(
				array(
					'name'        => $root . '-new-role',
					'placeholder' => __( 'New role name', VIEW_ADMIN_AS_DOMAIN ),
					'class'       => 'ab-vaa-conditional',
					'attr' => array(
						'vaa-condition'        => '__new__',
						'vaa-condition-target' => '#' . $root . '-edit-role',
					),
				)
			),
			'href' => false,
			'meta' => array(
				'class' => 'ab-vaa-input vaa-hidden',
			),
		) );

		$admin_bar->add_node( array(
			'id'     => $root . '-new-cap',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_input( array(
				'name'        => $root . '-new-cap',
				'placeholder' => esc_attr__( 'Add new capability', VIEW_ADMIN_AS_DOMAIN ),
			) ) . VAA_View_Admin_As_Form::do_button( array(
				'name'        => $root . '-add-cap',
				'label'       => __( 'Add', VIEW_ADMIN_AS_DOMAIN ),
				'class'       => 'button-primary input-overlay',
			) )
			. '<div id="' . $root . '-cap-template" style="display: none;"><div class="ab-item vaa-cap-item">'
			. VAA_View_Admin_As_Form::do_checkbox( array(
				'name'           => 'vaa_cap_vaa_new_item',
				'value'          => true,
				'compare'        => true,
				'checkbox_value' => 'vaa_new_item',
				'label'          => 'vaa_new_item',
				'removable'      => true,
			) ) . '</div></div>',
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-input',
			),
		) );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.5
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  VAA_View_Admin_As_Role_Manager
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Role_Manager.
