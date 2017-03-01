<?php
/**
 * View Admin As - Role Manager Module
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

if ( ! class_exists( 'VAA_View_Admin_As_Role_Manager' ) ) {

add_action( 'vaa_view_admin_as_modules_loaded', array( 'VAA_View_Admin_As_Role_Manager', 'get_instance' ) );

/**
 * Add or remove roles and grant or deny them capabilities
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.x
 * @version 1.6.x
 * @uses    VAA_View_Admin_As_Module Extends class
 */
final class VAA_View_Admin_As_Role_Manager extends VAA_View_Admin_As_Module
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6.x
	 * @static
	 * @var    VAA_View_Admin_As_Role_Manager
	 */
	private static $_instance = null;

	/**
	 * Option key.
	 *
	 * @since  1.6.x
	 * @var    string
	 */
	protected $optionKey = 'vaa_role_manager';

	/**
	 * The WP_Roles object
	 *
	 * @since  1.6.x
	 * @var    WP_Roles
	 */
	public $wp_roles = null;

	/**
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.6.x
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		// Add this class to the modules in the main class.
		$this->vaa->register_module( array(
			'id'       => 'role_manager',
			'instance' => self::$_instance,
		) );

		/**
		 * Add capabilities for this module.
		 *
		 * @since 1.6.x
		 */
		$this->capabilities = array( 'view_admin_as_role_manager' );
		add_filter( 'view_admin_as_add_capabilities', array( $this, 'add_capabilities' ) );

		// Load data.
		$this->set_optionData( get_option( $this->get_optionKey() ) );

		/**
		 * Checks if the management part of module should be enabled.
		 *
		 * @since  1.6.x
		 */
		if ( $this->get_optionData( 'enable' ) ) {
			$this->enable = true;
			$this->init();
		}

		/**
		 * Only allow settings for admin users or users with the correct capabilities.
		 *
		 * @since  1.6.x
		 */
		if ( ! is_network_admin() && $this->current_user_can( 'view_admin_as_role_manager' ) ) {
			add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
			add_filter( 'view_admin_as_handle_data_role_manager', array( $this, 'ajax_handler' ), 10, 2 );
		}
	}

	/**
	 * Init function for global functions (not user dependent).
	 *
	 * @since   1.6.x
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
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @return  void
	 */
	public function vaa_init() {

		// Enabling this module can only be done by a super admin.
		if ( VAA_API::is_super_admin() ) {

			// Add adminbar menu items in settings section.
			add_action( 'vaa_admin_bar_settings_after', array( $this, 'admin_bar_menu_settings' ), 10, 2 );
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
	 * @since   1.6.x
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

		if ( VAA_API::array_has( $data, 'save_role', array( 'validation' => 'is_array' ) ) ) {
			if ( empty( $data['save_role']['role'] ) || empty( $data['save_role']['capabilities'] ) ) {
				$success = array(
					'success' => false,
					'data' => __( 'No valid data found', VIEW_ADMIN_AS_DOMAIN ),
				);
			} else {
				$success = $this->save_role( $data['save_role']['role'], $data['save_role']['capabilities'] );
			}
		}

		if ( VAA_API::array_has( $data, 'clone_role', array( 'validation' => 'is_array' ) ) ) {
			if ( empty( $data['clone_role']['role'] ) || empty( $data['clone_role']['new_role'] ) ) {
				$success = array(
					'success' => false,
					'data' => __( 'No valid data found', VIEW_ADMIN_AS_DOMAIN ),
				);
			} else {
				$success = $this->clone_role( $data['clone_role']['role'], $data['clone_role']['new_role'] );
			}
		}

		if ( VAA_API::array_has( $data, 'delete_role', array( 'validation' => 'is_string' ) ) ) {
			$success = $this->delete_role( $data['delete_role'] );
		}

		return $success;
	}

	/**
	 * Save a role.
	 * Can also add a new role when it doesn't exist.
	 *
	 * @since   1.6.x
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
		$capabilities = array_map( 'boolval', $capabilities );
		$existing_role = $this->wp_roles->get_role( $role );
		if ( $existing_role ) {
			$role = $existing_role;
			// Update existing role.
			foreach ( $capabilities as $cap => $grant ) {
				// @todo Option to deny capability (like Members).
				if ( $grant ) {
					$role->add_cap( (string) $cap, (bool) $grant );
				} else {
					$role->remove_cap( (string) $cap );
				}
			}
		} else {
			// Add new role.
			$role_name = ucfirst( $role );
			$role = str_replace( array( ' ', '-' ), '_', sanitize_title_with_dashes( $role ) );
			$capabilities = array_filter( $capabilities );
			$this->wp_roles->add_role( $role, $role_name, $capabilities );
		}
		return true;
	}

	/**
	 * Clone a role.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $role      The role name.
	 * @param   string  $new_role  The new role name.
	 * @return  mixed
	 */
	public function clone_role( $role, $new_role ) {
		$role = $this->store->get_roles( $role );
		if ( $role ) {
			$new_role = str_replace( array( ' ', '-' ), '_', sanitize_title_with_dashes( $new_role ) );
			$this->wp_roles->add_role( $new_role, ucfirst( $new_role ), $role->capabilities );
			return true;
		}
		return __( 'Role not found', VIEW_ADMIN_AS_DOMAIN );
	}

	/**
	 * Delete a role from the database.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $role  The role name.
	 * @return  mixed
	 */
	public function delete_role( $role ) {
		if ( $this->store->get_roles( $role ) ) {
			$this->wp_roles->remove_role( $role );
			return true;
		}
		return __( 'Role not found', VIEW_ADMIN_AS_DOMAIN );
	}

	/**
	 * Add admin bar setting items.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @see     'vaa_admin_bar_settings_after' action
	 *
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item (vaa-settings).
	 * @return  void
	 */
	public function admin_bar_menu_settings( $admin_bar, $root ) {

		$admin_bar->add_group( array(
			'id'     => $root . '-role-manager',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$root = $root . '-role-manager';

		$admin_bar->add_node( array(
			'id'     => $root . '-enable',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Admin_Bar::do_checkbox( array(
				'name'        => $root . '-enable',
				'value'       => $this->get_optionData( 'enable' ),
				'compare'     => true,
				'label'       => __( 'Enable role manager', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Add or remove roles and grant or deny them capabilities', VIEW_ADMIN_AS_DOMAIN ),
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
	 * @since   1.6.x
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
			'title'  => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-id-alt' ) . __( 'Role manager', VIEW_ADMIN_AS_DOMAIN ),
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
			'meta'   => array(
				'tabindex' => '0',
			),
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
	 * @since   1.6.x  Separated the tools from the main function.
	 * @access  public
	 * @see     admin_bar_menu()
	 *
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item (vaa).
	 * @return  void
	 */
	private function admin_bar_menu_bulk_actions( $admin_bar, $root ) {

		$role_select_options = array(
			array(
				'label' => ' --- ' . __( 'Select role', VIEW_ADMIN_AS_DOMAIN ) . ' --- ',
			),
		);
		foreach ( $this->store->get_rolenames() as $role_key => $role_name ) {
			$role_select_options[] = array(
				'value' => esc_attr( $role_key ),
				'label' => esc_html( $role_name ),
			);
		}

		/*
		 * Apply current view capabilities to role.
		 */

		/*
		 * Clone role.
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
			'title'  => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-star-half' ) . __( 'Clone role', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-bold vaa-has-icon ab-vaa-toggle',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-clone-select',
			'parent' => $root . '-clone',
			'title'  => VAA_View_Admin_As_Admin_Bar::do_select(
				array(
					'name'   => $root . '-clone-select',
					'values' => $role_select_options,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-vaa-select select-role',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-clone-input',
			'parent' => $root . '-clone',
			'title'  => VAA_View_Admin_As_Admin_Bar::do_input(
				array(
					'name'   => $root . '-clone-input',
					'placeholder' => __( 'New role name', VIEW_ADMIN_AS_DOMAIN ),
				)
			),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-vaa-input clone-role',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-clone-apply',
			'parent' => $root . '-clone',
			'title'  => VAA_View_Admin_As_Admin_Bar::do_button( array(
				'name'  => $root . '-clone-apply',
				'label' => __( 'Apply', VIEW_ADMIN_AS_DOMAIN ),
				'class' => 'button-primary',
			) ),
			'href'   => false,
			'meta'   => array(
				'class' => 'vaa-button-container',
			),
		) );

		/*
		 * Delete role.
		 */
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
			'title'  => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-trash' ) . __( 'Delete role', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-bold vaa-has-icon ab-vaa-toggle',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-delete-select',
			'parent' => $root . '-delete',
			'title'  => VAA_View_Admin_As_Admin_Bar::do_select(
				array(
					'name'   => $root . '-delete-select',
					'values' => $role_select_options,
				)
			),
			'href'   => false,
			'meta'   => array(
				'class'    => 'ab-vaa-select select-role',
				'tabindex' => '0',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-delete-apply',
			'parent' => $root . '-delete',
			'title'  => VAA_View_Admin_As_Admin_Bar::do_button( array(
				'name'  => $root . '-delete-apply',
				'label' => __( 'Delete', VIEW_ADMIN_AS_DOMAIN ),
				'class' => 'button-primary',
			) ),
			'href'   => false,
			'meta'   => array(
			'class' => 'vaa-button-container',
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
	 * @since   1.6.x
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
			'title'  => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-id-alt' ) . __( 'Role manager', VIEW_ADMIN_AS_DOMAIN ),
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
			array(
				'label' => ' --- ' . __( 'Select role', VIEW_ADMIN_AS_DOMAIN ) . ' --- ',
				'attr'  => array(
					'data-caps' => wp_json_encode( $caps ),
				),
			),
			array(
				'value' => '__new__',
				'label' => ' ++ ' . __( 'New role', VIEW_ADMIN_AS_DOMAIN ) . ' ++ ',
			),
		);
		foreach ( $this->store->get_roles() as $role_key => $role ) {
			$data_caps = wp_json_encode( $role->capabilities );
			$role_select_options[] = array(
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
			'title'  => VAA_View_Admin_As_Admin_Bar::do_select(
				array(
					'name'   => $root . '-edit-role',
					'values' => $role_select_options,
				)
			) . VAA_View_Admin_As_Admin_Bar::do_button(
				array(
					'name'  => $root . '-save-role',
					'label' => __( 'Save role', VIEW_ADMIN_AS_DOMAIN ),
					'class'       => 'button-primary input-overlay',
				)
			),
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-select',
				'tabindex' => '0',
			),
		) );

		$admin_bar->add_node( array(
			'id'     => $root . '-new-role',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Admin_Bar::do_input(
				array(
					'name'        => $root . '-new-role',
					'placeholder' => __( 'New role name', VIEW_ADMIN_AS_DOMAIN ),
					'class'       => 'ab-vaa-conditional',
					'attr' => array(
						'data-condition' => '__new__',
						'data-condition-target' => '#' . $root . '-edit-role',
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
			'title'  => VAA_View_Admin_As_Admin_Bar::do_input( array(
				'name'        => $root . '-new-cap',
				'placeholder' => esc_attr__( 'Add new capability', VIEW_ADMIN_AS_DOMAIN ),
			) ) . VAA_View_Admin_As_Admin_Bar::do_button( array(
				'name'        => $root . '-add-cap',
				'label'       => __( 'Add', VIEW_ADMIN_AS_DOMAIN ),
				'class'       => 'button-primary input-overlay',
			) )
			. '<div id="' . $root . '-cap-template" style="display: none;"><div class="ab-item vaa-cap-item">'
			. VAA_View_Admin_As_Admin_Bar::do_checkbox( array(
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

} // end class.

} // end if class_exists.
