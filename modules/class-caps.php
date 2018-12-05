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
 * @since   1.3.0  View type existed in core.
 * @since   1.8.0  Created this class.
 * @version 1.8.3
 * @uses    \VAA_View_Admin_As_Type Extends class
 */
class VAA_View_Admin_As_Caps extends VAA_View_Admin_As_Type
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.8.0
	 * @static
	 * @var    \VAA_View_Admin_As_Caps
	 */
	private static $_instance = null;

	/**
	 * @since  1.8.0
	 * @var    string
	 */
	protected $type = 'caps';

	/**
	 * The icon for this view type.
	 *
	 * @since  1.8.0
	 * @var    string
	 */
	protected $icon = 'dashicons-forms';

	/**
	 * Populate the instance.
	 *
	 * @since   1.8.0
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( ! $this->has_access() ) {
			return;
		}

		$this->priorities = array(
			'toolbar'            => 10,
			'view_title'         => 80,
			'validate_view_data' => 10,
			'update_view'        => 10,
			'do_view'            => 8,
		);

		$this->label          = __( 'Capabilities', VIEW_ADMIN_AS_DOMAIN );
		$this->label_singular = __( 'Capability', VIEW_ADMIN_AS_DOMAIN );
	}

	/**
	 * Apply the user view.
	 *
	 * @since   1.8.0
	 * @access  public
	 */
	public function do_view() {

		if ( parent::do_view() ) {

			$this->add_filter( 'view_admin_as_user_has_cap_priority', array( $this, 'filter_user_has_cap_priority' ) );
			$this->add_action( 'vaa_view_admin_as_modify_user', array( $this, 'modify_user' ), 2, 2 );
			$this->init_user_modifications();
		}
	}

	/**
	 * Make sure to run `user_has_cap` view filter as last if this view is active.
	 *
	 * @since   1.8.3
	 * @return  int
	 */
	public function filter_user_has_cap_priority() {
		return 999999999;
	}

	/**
	 * Modify the current user object.
	 *
	 * @since  1.3.0
	 * @param  \WP_User  $user  The modified user object.
	 */
	public function modify_user( $user ) {

		$view_data = $this->store->get_view( $this->type );

		if ( is_array( $view_data ) ) {
			// @since  1.6.3  Set the current user's caps (roles) to the current view.
			$user->allcaps = array_merge(
				(array) array_filter( $view_data ),
				(array) $user->caps // Contains the current user roles.
			);
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
		if ( $this->selected ) {
			$titles[] = $this->label;
		}
		return $titles;
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
		// Caps data must be an array
		if ( is_array( $data ) ) {

			// The data is an array, most likely from the database.
			$data = array_map( 'absint', $data );
			// Sort the new caps the same way we sort the existing caps.
			ksort( $data );

			// Only allow assigned capabilities if it isn't a super admin.
			if ( ! VAA_API::is_super_admin() ) {
				$data = array_intersect_key( $data, $this->store->get_caps() );
			}

			// @since  1.7.4  Forbidden capabilities.
			unset( $data['do_not_allow'] );
			unset( $data['vaa_do_not_allow'] );

			return $data;
		}
		return $null;
	}

	/**
	 * View update handler (Ajax probably), called from main handler.
	 *
	 * @since   1.8.0   Renamed from `ajax_handler()`.
	 * @access  public
	 * @param   null    $null    Null.
	 * @param   array   $data    The ajax data for this module.
	 * @param   string  $type    The view type.
	 * @return  bool
	 */
	public function update_view( $null, $data, $type = null ) {
		$success = $null;
		if ( ! is_array( $data ) || $this->type !== $type ) {
			return $success;
		}

		// Check if the selected caps are equal to the default caps.
		if ( VAA_API::array_equal( $this->store->get_curUser()->allcaps, $data ) ) {
			// The selected caps are equal to the current user default caps so we can reset the view.
			$this->vaa->controller()->reset_view();
			if ( $this->selected ) {
				// The user was in a custom caps view.
				$success = true; // and continue.
			} else {
				// The user was in his default view, notify the user.
				$success = array(
					'success' => false,
					'data'    => array(
						'type' => 'message',
						'text' => esc_html__( 'These are your default capabilities!', VIEW_ADMIN_AS_DOMAIN ),
					),
				);
			}
		} else {
			// Store the selected caps.
			$new_caps = array_map( 'absint', $data );

			// Check if the new caps selection is different.
			if ( VAA_API::array_equal( $this->selected, $new_caps ) ) {
				$success = array(
					'success' => false,
					'data'    => array(
						'type' => 'message',
						'text' => esc_html__( 'This view is already selected!', VIEW_ADMIN_AS_DOMAIN ),
					),
				);
			} else {
				$this->store->set_view( $data, $type, true );
				$success = true;
			}
		}
		return $success;
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
		 * Make sure we have the latest added capabilities.
		 * It can be that a plugin/theme adds a capability after the initial call to store_caps (hook: 'plugins_loaded').
		 *
		 * @see    \VAA_View_Admin_As::run()
		 * @since  1.4.1
		 */
		$this->store_data();

		if ( ! $this->get_data() ) {
			return;
		}

		/**
		 * Whether the capability manager should be loaded as a submenu from the title element or as a separate node below the title.
		 * Default: true.
		 * Useful if you have a plugin that adds another sub-node below the capability title.
		 *
		 * @since  1.7.5
		 * @return bool
		 */
		$title_submenu = (bool) apply_filters( 'vaa_admin_bar_caps_do_title_submenu', true );

		$main_root = $root;
		$root      = $main_root . '-caps';

		$admin_bar->add_group( array(
			'id'     => $root,
			'parent' => $main_root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$title_class = '';
		if ( $title_submenu ) {
			$title_class .= ( $this->selected ) ? ' current' : '';
		} else {
			$title_class .= ' ab-vaa-toggle active';
		}

		$admin_bar->add_node( array(
			'id'     => $root . '-title',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_icon( $this->icon ) . $this->label,
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title' . $title_class,
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items at the beginning of the caps group.
		 *
		 * @since   1.5.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_before', $admin_bar, $root, $main_root );

		if ( $title_submenu ) {
			$admin_bar->add_group( array(
				'id'     => $root . '-manager',
				'parent' => $root . '-title',
			) );
		} else {
			$admin_bar->add_node( array(
				'id'     => $root . '-manager',
				'parent' => $root,
				'title'  => __( 'Manager', VIEW_ADMIN_AS_DOMAIN ),
				'href'   => false,
				'meta'   => array(
					'class'    => ( $this->selected ) ? 'current' : '',
					'tabindex' => '0',
				),
			) );
		}

		// Capabilities submenu.
		$admin_bar->add_node( array(
			'id'     => $root . '-applycaps',
			'parent' => $root . '-manager',
			'title'  => VAA_View_Admin_As_Form::do_button( array(
				'name'  => 'apply-caps-view',
				'label' => __( 'Apply view', VIEW_ADMIN_AS_DOMAIN ),
				'class' => 'button-primary',
			) )
			. VAA_View_Admin_As_Form::do_button( array(
				'name'    => 'close-caps-popup',
				'label'   => VAA_View_Admin_As_Form::do_icon( 'dashicons-editor-contract' ),
				'class'   => 'button-secondary vaa-icon vaa-hide-responsive',
				'element' => 'a',
			) )
			. VAA_View_Admin_As_Form::do_button( array(
				'name'    => 'open-caps-popup',
				'label'   => VAA_View_Admin_As_Form::do_icon( 'dashicons-editor-expand' ),
				'class'   => 'button-secondary vaa-icon vaa-hide-responsive',
				'element' => 'a',
			) ),
			'href'   => false,
			'meta'   => array(
				'class' => 'vaa-button-container',
			),
		) );

		/**
		 * Add items at the before of the caps selection options.
		 *
		 * @since   1.7.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item. ($root.'-manager')
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_manager_before', $admin_bar, $root . '-manager', $main_root );

		$admin_bar->add_group( array(
			'id'     => $root . '-select',
			'parent' => $root . '-manager',
		) );

		// Used in templates
		$parent = $root . '-select';

		/**
		 * Add items at the before of the caps actions.
		 *
		 * @since   1.7.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $parent     The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_actions_before', $admin_bar, $parent, $main_root );

		// Add caps actions.
		include VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-caps-actions.php';

		/**
		 * Add items at the after of the caps actions.
		 *
		 * @since   1.7.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $parent     The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_actions_after', $admin_bar, $parent, $main_root );

		// Add the caps.
		include VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-caps-items.php';

		/**
		 * Add items at the end of the caps group.
		 *
		 * @since   1.5.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_after', $admin_bar, $root, $main_root );

		$done = true;
	}

	/**
	 * Store available capabilities.
	 *
	 * @since   1.4.1
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.8.0  Moved from `VAA_View_Admin_As_Store`.
	 * @access  public
	 * @return  void
	 */
	public function store_data() {

		// Get current user capabilities.
		$caps = $this->store->get_originalUserData( 'allcaps' );
		if ( empty( $caps ) ) {
			// Fallback.
			$caps = $this->store->get_curUser()->allcaps;
		}

		// Only allow to add capabilities for an admin (or super admin).
		if ( VAA_API::is_super_admin() ) {

			/**
			 * Add compatibility for other cap managers.
			 *
			 * @since  1.5.0
			 * @see    \VAA_View_Admin_As_Compat->init()
			 * @param  array  $caps  An empty array, waiting to be filled with capabilities.
			 * @return array
			 */
			$all_caps = apply_filters( 'view_admin_as_get_capabilities', array() );

			$add_caps = array();
			// Add new capabilities to the capability array as disabled.
			foreach ( $all_caps as $cap_key => $cap_val ) {
				if ( is_numeric( $cap_key ) ) {
					// Try to convert numeric (faulty) keys.
					$add_caps[ (string) $cap_val ] = 0;
				} else {
					$add_caps[ (string) $cap_key ] = 0;
				}
			}

			$caps = array_merge( $add_caps, $caps );

		} // End if().

		// Remove role names.
		$caps = array_diff_key( $caps, $this->store->get_roles() );
		// And sort alphabetical.
		ksort( $caps );

		$this->set_data( $caps );
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
		$this->store->set_caps( $val, $key, $append );
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
		return $this->store->get_caps( $key );
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
	 * @return  \VAA_View_Admin_As_Caps  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Caps.
