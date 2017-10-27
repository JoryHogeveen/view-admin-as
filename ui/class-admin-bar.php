<?php
/**
 * View Admin As - Admin Bar UI
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Admin Bar UI for View Admin As.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.5
 * @version 1.7.4
 * @uses    VAA_View_Admin_As_Form Extends class
 */
final class VAA_View_Admin_As_Admin_Bar extends VAA_View_Admin_As_Form
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.5
	 * @static
	 * @var    VAA_View_Admin_As_Admin_Bar
	 */
	private static $_instance = null;

	/**
	 * Admin bar root item ID.
	 *
	 * @since  1.6.1
	 * @static
	 * @var    string
	 */
	public static $root = 'vaa';

	/**
	 * Admin bar parent item ID.
	 *
	 * @since  1.7.4
	 * @static
	 * @var    string
	 */
	public static $parent = 'top-secondary';

	/**
	 * Group the users under their roles?
	 *
	 * @since  1.5
	 * @var    bool
	 */
	private $groupUserRoles = false;

	/**
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.5
	 * @since   1.6.1  $vaa param
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( $this->is_vaa_enabled() ) {
			add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
		}
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view.
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_view_admin_as_init' action
	 * @return  void
	 */
	public function vaa_init() {

		// If the amount of items (roles and users combined) is more than 15 users, group them under their roles.
		// There are no roles to group users on network pages.
		if ( ! is_network_admin() && (
			$this->store->get_userSettings( 'force_group_users' ) ||
			15 < ( count( $this->store->get_users() ) + count( $this->store->get_roles() ) )
		) ) {
			$this->groupUserRoles = true;
		}

		$priority = 10;
		$location = $this->store->get_userSettings( 'admin_menu_location' );
		if ( $location && in_array( $location, $this->store->get_allowedUserSettings( 'admin_menu_location' ), true ) ) {
			self::$parent = $location;
			if ( 'my-account' === $location ) {
				$priority = -10;
			}
		}
		/**
		 * Set the priority in which the adminbar root node is added.
		 * @since  1.7.4
		 * @param  int     $priority
		 * @param  string  $parent  The main VAA node parent.
		 * @return int
		 */
		$priority = (int) apply_filters( 'vaa_admin_bar_priority', $priority, self::$parent );

		// Add the default nodes to the WP admin bar.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), $priority );
		add_action( 'vaa_toolbar_menu', array( $this, 'admin_bar_menu' ), 10, 2 );

		// Add the global nodes to the admin bar.
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_info' ), 1 );
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_settings' ), 2 );
		add_action( 'vaa_admin_bar_settings_after', array( $this, 'admin_bar_menu_modules' ), 1, 2 );

		// Add the caps nodes to the admin bar.
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_caps' ), 10 );

		if ( ! is_network_admin() ) {

			// Add the roles nodes to the admin bar.
			// Roles are not used on network pages.
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_roles' ), 20 );

			// Add the visitor view nodes under roles.
			// There are no outside visitors on network pages.
			add_action( 'vaa_admin_bar_roles_after', array( $this, 'admin_bar_menu_visitor' ), 10, 2 );
			// Fallback action for when there are no roles available.
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_visitor' ), 31 );
		}

		// Add the users nodes to the admin bar.
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_users' ), 30 );
	}

	/**
	 * Get the toolbar title for the main VAA node.
	 *
	 * @since   1.7.2
	 * @access  private
	 * @see     VAA_View_Admin_As_Admin_Bar::admin_bar_menu()
	 * @return  string
	 */
	private function get_admin_bar_menu_title() {
		$title = __( 'Default view (Off)', VIEW_ADMIN_AS_DOMAIN );

		if ( $this->store->get_view( 'caps' ) ) {
			$title = __( 'Modified view', VIEW_ADMIN_AS_DOMAIN );
		}
		if ( $this->store->get_view( 'role' ) ) {
			$title = __( 'Viewing as role', VIEW_ADMIN_AS_DOMAIN ) . ': '
			         . $this->store->get_rolenames( $this->store->get_view( 'role' ) );
		}
		if ( $this->store->get_view( 'user' ) ) {
			$selected_user_roles = array();
			foreach ( $this->store->get_selectedUser()->roles as $role ) {
				$selected_user_roles[] = $this->store->get_rolenames( $role );
			}
			$title = __( 'Viewing as user', VIEW_ADMIN_AS_DOMAIN ) . ': '
			         . $this->store->get_selectedUser()->data->display_name
			         . ' <span class="user-role">(' . implode( ', ', $selected_user_roles ) . ')</span>';
		}
		if ( $this->store->get_view( 'visitor' ) ) {
			$title = __( 'Viewing as site visitor', VIEW_ADMIN_AS_DOMAIN );
		}

		/**
		 * Filter the text to show when a view is applied.
		 *
		 * @since  1.6
		 * @param  string      $title   The current title.
		 * @param  bool|array  $viewAs  The view data.
		 * @return string
		 */
		$title = apply_filters( 'vaa_admin_bar_viewing_as_title', $title, $this->store->get_view() );

		return $title;
	}

	/**
	 * Add admin bar menu items.
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'admin_bar_menu' action
	 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item ID/Name. If set it will overwrite the user setting.
	 * @return  void
	 */
	public function admin_bar_menu( $admin_bar, $root = '' ) {

		$icon = 'dashicons-hidden';

		if ( $this->store->get_view() ) {
			$icon = 'dashicons-visibility';
		}

		$title = $this->get_admin_bar_menu_title();

		if ( empty( $root ) ) {
			$root = self::$parent;
		}

		$tooltip = __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN );
		if ( $this->store->get_view() ) {
			$tooltip .= ' - ' . __( 'View active', VIEW_ADMIN_AS_DOMAIN );
		}

		// Add menu item.
		$admin_bar->add_node( array(
			'id'     => self::$root,
			'parent' => $root,
			'title'  => '<span class="ab-label">' . $title . '</span>' . VAA_View_Admin_As_Form::do_icon(
				$icon,
				array( 'class' => 'alignright' )
			),
			'href'   => false,
			'meta'   => array(
				'title'    => $tooltip,
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items as first.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         self::$root  The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_menu_before', $admin_bar, self::$root, self::$root );

		// Add reset button.
		if ( $this->store->get_view() ) {
			$rel = 'reset';
			$name = 'reset-view';
			if ( 'single' === $this->store->get_userSettings( 'view_mode' ) ) {
				$rel = 'reload';
				$name = 'reload';
			}
			$admin_bar->add_node( array(
				'id'     => self::$root . '-reset',
				'parent' => self::$root,
				'title'  => self::do_button( array(
					'name'  => self::$root . '-' . $name,
					'label' => __( 'Reset to default', VIEW_ADMIN_AS_DOMAIN ),
					'class' => 'button-secondary',
				) ),
				'href'   => VAA_API::get_reset_link(),
				'meta'   => array(
					'title' => esc_attr__( 'Reset to default', VIEW_ADMIN_AS_DOMAIN ),
					'class' => 'vaa-reset-item vaa-button-container',
					'rel'   => $rel,
				),
			) );
		}

		/**
		 * Add items.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         self::$root  The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_menu', $admin_bar, self::$root, self::$root );

	}

	/**
	 * Add admin bar menu info items.
	 *
	 * @since   1.6
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @return  void
	 */
	public function admin_bar_menu_info( $admin_bar ) {

		$root = self::$root . '-info';

		$admin_bar->add_node( array(
			'id'     => $root,
			'parent' => self::$root,
			'title'  => self::do_icon( 'dashicons-info' ) . __( 'Info', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon',
				'tabindex' => '0',
			),
		) );

		$admin_bar->add_group( array(
			'id'     => $root . '-about',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$admin_bar->add_node(
			array(
				'parent' => $root . '-about',
				'id'     => $root . '-about-version',
				'title'  => __( 'Version', VIEW_ADMIN_AS_DOMAIN ) . ': ' . VIEW_ADMIN_AS_VERSION,
				'href'   => false,
			)
		);
		$admin_bar->add_node(
			array(
				'parent' => $root . '-about',
				'id'     => $root . '-about-author',
				'title'  => 'Keraweb • Jory Hogeveen',
				'href'   => 'https://profiles.wordpress.org/keraweb/',
				'meta'   => array(
					'target' => '_blank',
				),
			)
		);

		/**
		 * Add items at the beginning of the info group.
		 *
		 * @since   1.6
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_info_before', $admin_bar, $root, self::$root );

		// Add the general admin links.
		if ( VAA_API::exists_callable( array( $this->vaa->get_ui( 'ui' ), 'get_links' ), true ) ) {
			$info_links = $this->vaa->get_ui( 'ui' )->get_links();

			$admin_bar->add_group( array(
				'id'     => $root . '-links',
				'parent' => $root,
			) );

			foreach ( $info_links as $id => $link ) {
				$admin_bar->add_node( array(
					'parent' => $root . '-links',
					'id'     => $root . '-' . $id,
					'title'  => self::do_icon( $link['icon'] ) . $link['description'],
					'href'   => esc_url( $link['url'] ),
					'meta'   => array(
						'class'  => 'auto-height vaa-has-icon',
						'target' => '_blank',
					),
				) );
			}
		}

		/**
		 * Add items at the end of the info group.
		 *
		 * @since   1.6
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_info_after', $admin_bar, $root, self::$root );

	}

	/**
	 * Add admin bar menu settings items.
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @return  void
	 */
	public function admin_bar_menu_settings( $admin_bar ) {

		$root = self::$root . '-settings';

		$admin_bar->add_node( array(
			'id'     => $root,
			'parent' => self::$root,
			'title'  => self::do_icon( 'dashicons-admin-settings' ) . __( 'Settings', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon',
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items at the beginning of the settings group.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_settings_before', $admin_bar, $root, self::$root );

		// Add user setting nodes.
		include( VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-settings-user.php' );

		/**
		 * Add items at the end of the settings group.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_settings_after', $admin_bar, $root, self::$root );
	}

	/**
	 * Add admin bar menu modules items.
	 *
	 * @since   1.7.1
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The current root item.
	 * @return  void
	 */
	public function admin_bar_menu_modules( $admin_bar, $root ) {

		// Do not render the modules group if there are no modules to show.
		if ( ! has_action( 'vaa_admin_bar_modules' ) ) {
			return;
		}

		$admin_bar->add_group( array(
			'id'     => self::$root . '-modules',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$root = self::$root . '-modules';

		$admin_bar->add_node( array(
			'id'     => $root . '-title',
			'parent' => $root,
			'title'  => self::do_icon( 'dashicons-admin-plugins' ) . __( 'Modules', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title', // ab-vaa-toggle active.
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items to the modules group.
		 *
		 * @since   1.7.1
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_modules', $admin_bar, $root, self::$root );
	}

	/**
	 * Add admin bar menu caps items.
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @return  void
	 */
	public function admin_bar_menu_caps( $admin_bar ) {
		static $done;
		if ( $done ) return;

		/**
		 * Make sure we have the latest added capabilities.
		 * It can be that a plugin/theme adds a capability after the initial call to store_caps (hook: 'plugins_loaded').
		 *
		 * @see    VAA_View_Admin_As::run()
		 * @since  1.4.1
		 */
		$this->store->store_caps();

		if ( ! $this->store->get_caps() ) {
			return;
		}

		$main_root = self::$root;
		$root = $main_root . '-caps';

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
			'title'  => self::do_icon( 'dashicons-forms' ) . __( 'Capabilities', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items at the beginning of the caps group.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_before', $admin_bar, $root, $main_root );

		$admin_bar->add_node( array(
			'id'     => $root . '-manager',
			'parent' => $root,
			'title'  => __( 'Select', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => ( $this->store->get_view( 'caps' ) ) ? 'current' : '',
				'tabindex' => '0',
			),
		) );

		// Capabilities submenu.
		$admin_bar->add_node( array(
			'id'     => $root . '-applycaps',
			'parent' => $root . '-manager',
			'title'  => self::do_button( array(
				'name'    => 'apply-caps-view',
				'label'   => __( 'Apply', VIEW_ADMIN_AS_DOMAIN ),
				'class'   => 'button-primary',
			) )
			. self::do_button( array(
				'name'    => 'close-caps-popup',
				'label'   => self::do_icon( 'dashicons-editor-contract' ),
				'class'   => 'button-secondary vaa-icon vaa-hide-responsive',
				'element' => 'a',
			) )
			. self::do_button( array(
				'name'    => 'open-caps-popup',
				'label'   => self::do_icon( 'dashicons-editor-expand' ),
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
		 * @since   1.7
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
		 * @since   1.7
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $parent     The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_actions_before', $admin_bar, $parent, $main_root );

		// Add caps actions.
		include( VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-caps-actions.php' );

		/**
		 * Add items at the after of the caps actions.
		 *
		 * @since   1.7
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $parent     The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_caps_actions_after', $admin_bar, $parent, $main_root );

		// Add the caps.
		include( VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-caps-items.php' );

		/**
		 * Add items at the end of the caps group.
		 *
		 * @since   1.5
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
	 * Add admin bar menu roles items.
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @return  void
	 */
	public function admin_bar_menu_roles( $admin_bar ) {
		static $done;
		if ( $done ) return;

		/**
		 * Make sure we have the latest added roles.
		 * It can be that a plugin/theme adds a role after the initial call to store_roles (hook: 'plugins_loaded').
		 *
		 * @see    VAA_View_Admin_As::run()
		 * @since  1.6.3
		 */
		$this->store->store_roles();

		if ( ! $this->store->get_roles() ) {
			return;
		}

		$main_root = self::$root;
		$root = $main_root . '-roles';

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
			'title'  => self::do_icon( 'dashicons-groups' ) . __( 'Roles', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items at the beginning of the roles group.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_roles_before', $admin_bar, $main_root );

		// Add the roles.
		include( VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-role-items.php' );

		/**
		 * Add items at the end of the roles group.
		 *
		 * @since   1.5
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
	 * Add admin bar menu users items.
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @return  void
	 */
	public function admin_bar_menu_users( $admin_bar ) {
		static $done;
		if ( $done ) return;

		if ( ! $this->store->get_users() ) {
			return;
		}

		$main_root = self::$root;
		$root = $main_root . '-users';

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
			'title'  => self::do_icon( 'dashicons-admin-users' ) . __( 'Users', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items at the beginning of the users group.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_users_before', $admin_bar, $root, $main_root );

		if ( true === $this->groupUserRoles ) {
			$admin_bar->add_node( array(
				'id'     => $root . '-searchusers',
				'parent' => $root,
				'title'  => self::do_description( __( 'Users are grouped under their roles', VIEW_ADMIN_AS_DOMAIN ) )
					. self::do_input( array(
						'name'        => $root . '-searchusers',
						'placeholder' => esc_attr__( 'Search', VIEW_ADMIN_AS_DOMAIN ) . ' (' . strtolower( __( 'Username', VIEW_ADMIN_AS_DOMAIN ) ) . ')',
					) ),
				'href'   => false,
				'meta'   => array(
					'class' => 'ab-vaa-search search-users',
					'html'  => '<ul id="vaa-searchuser-results" class="ab-sub-secondary ab-submenu ab-vaa-results"></ul>',
				),
			) );
		}

		// Add the users.
		include( VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-user-items.php' );

		/**
		 * Add items at the end of the users group.
		 *
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
		 * @param   string         $root       The current root item.
		 * @param   string         $main_root  The main root item.
		 */
		do_action( 'vaa_admin_bar_users_after', $admin_bar, $root, $main_root );

		$done = true;
	}

	/**
	 * Add admin bar menu visitor view.
	 *
	 * @since   1.6.2
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       (optional) The root item.
	 * @return  void
	 */
	public function admin_bar_menu_visitor( $admin_bar, $root = '' ) {
		static $done;
		if ( $done ) return;

		$main_root = self::$root;
		$class = 'vaa-visitor-item vaa-has-icon';

		if ( empty( $root ) || $root === $main_root ) {

			$admin_bar->add_group( array(
				'id'     => $main_root . '-visitor',
				'parent' => $main_root,
				'meta'   => array(
					'class' => 'ab-sub-secondary',
				),
			) );

			$root = $main_root . '-visitor';
			$class .= ' ab-vaa-title';
		} else {
			$class .= ' vaa-menupop';
		}

		$admin_bar->add_node( array(
			'id'     => $main_root . '-visitor-view',
			'parent' => $root,
			'title'  => self::do_icon( 'dashicons-universal-access' )
			            . self::do_view_title( __( 'Site visitor', VIEW_ADMIN_AS_DOMAIN ), 'visitor', true ),
			'href'   => '#',
			'meta'   => array(
				'title' => esc_attr__( 'View as site visitor', VIEW_ADMIN_AS_DOMAIN ),
				'class' => $class,
				'rel'   => true,
			),
		) );

		$done = true;
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.5
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class
	 * @return  $this  VAA_View_Admin_As_Admin_Bar
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Admin_Bar.
