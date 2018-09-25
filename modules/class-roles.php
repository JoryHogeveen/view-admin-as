<?php
/**
 * View Admin As - User switcher
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * User switcher view type.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   0.1.0  View type existed in core.
 * @since   1.8.0  Created this class.
 * @version 1.8.0
 * @uses    \VAA_View_Admin_As_Type Extends class
 */
class VAA_View_Admin_As_Roles extends VAA_View_Admin_As_Type
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.8.0
	 * @static
	 * @var    \VAA_View_Admin_As_Roles
	 */
	private static $_instance = null;

	/**
	 * @since  1.8.0
	 * @var    string
	 */
	protected $type = 'role';

	/**
	 * The icon for this view type.
	 *
	 * @since  1.8.0
	 * @var    string
	 */
	protected $icon = 'dashicons-groups';

	/**
	 * Populate the instance.
	 *
	 * @since   1.8.0
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;

		if ( is_network_admin() ) {
			return;
		}

		parent::__construct( $vaa );

		// Roles should always be stored because of dependencies.
		if ( ! $this->is_enabled() ) {
			$this->store_data();
		}

		if ( ! $this->has_access() ) {
			return;
		}

		$this->priorities = array(
			'toolbar'            => 20,
			'view_title'         => 8,
			'validate_view_data' => 10,
			'update_view'        => 10,
			'do_view'            => 5,
		);

		$this->label          = __( 'Roles', VIEW_ADMIN_AS_DOMAIN );
		$this->label_singular = __( 'Role', VIEW_ADMIN_AS_DOMAIN );
	}

	/**
	 * Apply the user view.
	 *
	 * @since   1.8.0
	 * @access  public
	 */
	public function do_view() {

		if ( parent::do_view() ) {

			$this->add_action( 'vaa_view_admin_as_modify_user', array( $this, 'modify_user' ), 2, 2 );
			$this->init_user_modifications();
		}
	}

	/**
	 * Modify the current user object.
	 *
	 * @since   1.8.0
	 * @param   \WP_User  $user  The modified user object.
	 */
	public function modify_user( $user ) {

		if ( $this->get_data( $this->selected ) instanceof WP_Role ) {
			// @since  1.6.3  Set the current user's role to the current view.
			$user->caps = array( $this->selected => 1 );
			// Sets the `allcaps` and `roles` properties correct.
			$user->get_role_caps();
			// Set the selected capabilities.
			$this->store->set_selectedCaps( $user->allcaps );
		}
	}

	/**
	 * Change the VAA admin bar menu title.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @param   array  $titles  The current title(s).
	 * @return  array
	 */
	public function view_title( $titles = array() ) {
		$current = $this->get_data( $this->selected );
		if ( $current ) {
			$titles[ $this->label_singular ] = $this->get_view_title( $current );
		}
		return $titles;
	}

	/**
	 * Get the view title.
	 *
	 * @since   1.8.0
	 * @param   \WP_Role  $role
	 * @return  string
	 */
	public function get_view_title( $role ) {
		$title = $this->store->get_rolenames( $role->name );

		/**
		 * Change the display title for role nodes.
		 *
		 * @since  1.8.0
		 * @param  string    $title  Role name (translated).
		 * @param  \WP_Role  $role   The role object.
		 * @return string
		 */
		$title = apply_filters( 'vaa_admin_bar_view_title_' . $this->type, $title, $role );

		return $title;
	}

	/**
	 * Validate data for this view type
	 *
	 * @since   1.7.0
	 * @since   1.8.0  Moved from `VAA_View_Admin_As_Controller`.
	 * @access  public
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	public function validate_view_data( $null, $data = null ) {
		// User data must be a number and exists in the loaded array of user id's.
		if ( is_string( $data ) && array_key_exists( $data, $this->get_data() ) ) {
			return $data;
		}
		return $null;
	}

	/**
	 * Add the admin bar items.
	 *
	 * @since   1.5.0
	 * @since   1.8.0  Moved from `VAA_View_Admin_As_Admin_Bar`.
	 * @access  public
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item.
	 */
	public function admin_bar_menu( $admin_bar, $root ) {
		static $done;
		if ( $done ) return;

		/**
		 * Make sure we have the latest added roles.
		 * It can be that a plugin/theme adds a role after the initial call to store_roles (hook: 'plugins_loaded').
		 *
		 * @see    \VAA_View_Admin_As::run()
		 * @since  1.6.3
		 */
		$this->store_data();

		if ( ! $this->get_data() ) {
			return;
		}

		$main_root = $root;
		$root      = $main_root . '-roles';

		$admin_bar->add_group( array(
			'id'     => $root,
			'parent' => $main_root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );
		$admin_bar->add_node( array(
			'id'     => $root . '-title',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_icon( $this->icon ) . $this->label,
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items at the beginning of the roles group.
		 *
		 * @since   1.5.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_roles_before', $admin_bar, $main_root );

		// Add the roles.
		include VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-role-items.php';

		/**
		 * Add items at the end of the roles group.
		 *
		 * @since   1.5.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_roles_after', $admin_bar, $root, $main_root );

		$done = true;
	}

	/**
	 * Store available roles.
	 *
	 * @since   1.5.0
	 * @since   1.5.2  Get role objects instead of arrays.
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.8.0  Moved from `VAA_View_Admin_As_Store`.
	 * @access  public
	 * @global  \WP_Roles  $wp_roles
	 * @return  void
	 */
	public function store_data() {

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

		$this->store->set_rolenames( $role_names );
		$this->set_data( $roles );
	}

	/**
	 * Set the view type data.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @param   mixed   $val
	 * @param   string  $key     (optional) The data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 */
	public function set_data( $val, $key = null, $append = true ) {
		$this->store->set_roles( $val, $key, $append );
	}

	/**
	 * Get the view type data.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @param   string  $key  (optional) The data key.
	 * @return  mixed
	 */
	public function get_data( $key = null ) {
		return $this->store->get_roles( $key );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class.
	 * @return  \VAA_View_Admin_As_Roles  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Roles.
