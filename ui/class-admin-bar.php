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
 * @since   1.5.0
 * @version 1.8.3
 * @uses    \VAA_View_Admin_As_Base Extends class
 */
final class VAA_View_Admin_As_Admin_Bar extends VAA_View_Admin_As_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.5.0
	 * @static
	 * @var    \VAA_View_Admin_As_Admin_Bar
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
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.5.0
	 * @since   1.6.1  `$vaa` param.
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( $this->is_vaa_enabled() ) {
			$this->add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
		}
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view.
	 *
	 * @since   1.5.0
	 * @access  public
	 * @see     'vaa_view_admin_as_init' action
	 * @return  void
	 */
	public function vaa_init() {

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
		$this->add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), $priority );
		$this->add_action( 'vaa_toolbar_menu', array( $this, 'admin_bar_menu' ), 10, 2 );

		// Add the global nodes to the admin bar.
		$this->add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_info' ), 1 );
		$this->add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_settings' ), 2 );
		$this->add_action( 'vaa_admin_bar_settings_after', array( $this, 'admin_bar_menu_view_types' ), 1, 2 );
		$this->add_action( 'vaa_admin_bar_settings_after', array( $this, 'admin_bar_menu_modules' ), 2, 2 );

		if ( ! is_network_admin() ) {

			if ( $this->current_user_can( 'view_admin_as_combinations' ) ) {
				// View combinations.
				$this->add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_combine' ), 8, 2 );
			}

			// There are no outside visitors on network pages.
			// Add the visitor view nodes under roles with a fallback to users.
			$this->add_action( 'vaa_admin_bar_roles_after', array( $this, 'admin_bar_menu_visitor' ), 10, 2 );
			$this->add_action( 'vaa_admin_bar_users_before', array( $this, 'admin_bar_menu_visitor' ), 10, 2 );
			// Fallback action for when there are no roles or users available.
			$this->add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_visitor' ), 31 );
		}
	}

	/**
	 * Get the toolbar title for the main VAA node.
	 *
	 * @since   1.7.2
	 * @since   1.8.3  Made public.
	 * @access  public
	 * @see     \VAA_View_Admin_As_Admin_Bar::admin_bar_menu()
	 * @return  string
	 */
	public function get_admin_bar_menu_title() {
		if ( ! $this->store->get_view() ) {
			return __( 'View As', VIEW_ADMIN_AS_DOMAIN );
		}

		$titles = array();

		if ( $this->store->get_view( 'visitor' ) ) {
			$titles[] = __( 'Site visitor', VIEW_ADMIN_AS_DOMAIN );
		}

		/**
		 * Filter what to show when a view is applied.
		 *
		 * @hooked
		 * 5:   user
		 * 8:   role
		 * 10:  group (Groups)
		 * 10:  rua_level (Restrict User Access)
		 * 80:  caps
		 * 90:  locale (Languages)
		 * 999: role defaults (appends an icon)
		 *
		 * @since  1.7.5
		 *
		 * @param  array  $titles   The current title(s).
		 * @param  array  $view     The view data.
		 *
		 * @return array|string
		 */
		$titles = apply_filters( 'vaa_admin_bar_view_titles', $titles, (array) $this->store->get_view() );

		if ( is_array( $titles ) ) {
			if ( 1 < count( $titles ) ) {
				// @todo Help icon for view info?
				// Translators: Context is a list of view types. Not the verb.
				$title = __( 'View', VIEW_ADMIN_AS_DOMAIN ) . ': ' . implode( ', ', $titles );
			} else {
				$type  = key( $titles );
				$name  = reset( $titles );
				$title = __( 'Viewing as', VIEW_ADMIN_AS_DOMAIN );
				if ( $type ) {
					$title .= ' ' . $type;
				}
				$title .= ': ';
				if ( $name ) {
					$title .= $name;
				}
			}
		} else {
			$title = (string) $titles;
		}

		/**
		 * Filter what to show when a view is applied.
		 * This filter is hooked after the initial parsing of view titles.
		 *
		 * @since  1.6.0
		 * @since  1.7.5  Renamed from `vaa_admin_bar_viewing_as_title`.
		 * @param  string  $title   The current title.
		 * @param  string  $view    The view data.
		 * @return string
		 */
		$title = apply_filters( 'vaa_admin_bar_title', $title, (array) $this->store->get_view() );

		return $title;
	}

	/**
	 * Add admin bar menu items.
	 *
	 * @since   1.5.0
	 * @access  public
	 * @see     'admin_bar_menu' action
	 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item ID/Name. If set it will overwrite the user setting.
	 * @return  void
	 */
	public function admin_bar_menu( $admin_bar, $root = '' ) {

		$icon    = 'dashicons-hidden';
		$tooltip = __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN );

		if ( $this->store->get_view() ) {
			$icon     = 'dashicons-visibility';
			$tooltip .= ' - ' . __( 'View active', VIEW_ADMIN_AS_DOMAIN );
		}

		$title = $this->get_admin_bar_menu_title();

		if ( empty( $root ) ) {
			$root = self::$parent;
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
		 * @since   1.5.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         self::$root  The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_menu_before', $admin_bar, self::$root, self::$root );

		// Add reset button.
		if ( $this->store->get_view() ) {
			$name = 'reset-view';
			if ( 'single' === $this->store->get_userSettings( 'view_mode' ) ) {
				$name = 'reload';
			}
			$admin_bar->add_node( array(
				'id'     => self::$root . '-reset',
				'parent' => self::$root,
				'title'  => VAA_View_Admin_As_Form::do_button( array(
					'name'  => self::$root . '-' . $name,
					'label' => __( 'Reset to default', VIEW_ADMIN_AS_DOMAIN ),
					'class' => 'button-secondary',
				) ),
				'href'   => VAA_API::get_reset_link(),
				'meta'   => array(
					'title' => esc_attr__( 'Reset to default', VIEW_ADMIN_AS_DOMAIN ),
					'class' => 'vaa-reset-item vaa-button-container',
				),
			) );
		}

		/**
		 * Add items.
		 *
		 * @since   1.5.0
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
	 * @hooked
	 * 1:  Info
	 * 2:  Settings
	 * 5:  Role Defaults module
	 * 6:  Role Manager module
	 * 8:  View combinations
	 * 9:  Languages view
	 * 10: Capabilities view
	 * 20: Roles view
	 * 30: Users view
	 * 31: Visitor view
	 * 40: RUA & Groups view modules
	 *
	 * @since   1.6.0
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
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-info' ) . __( 'Info', VIEW_ADMIN_AS_DOMAIN ),
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
		 * @since   1.6.0
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
					'title'  => VAA_View_Admin_As_Form::do_icon( $link['icon'] ) . $link['description'],
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
		 * @since   1.6.0
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
	 * @since   1.5.0
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
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-admin-settings' ) . __( 'Settings', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon',
				'tabindex' => '0',
			),
		) );

		/**
		 * Add items at the beginning of the settings group.
		 *
		 * @since   1.5.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_settings_before', $admin_bar, $root, self::$root );

		// Add user setting nodes.
		include VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-settings-user.php';

		/**
		 * Add items at the end of the settings group.
		 *
		 * @since   1.5.0
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_settings_after', $admin_bar, $root, self::$root );
	}

	/**
	 * Add admin bar menu view type items.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The current root item.
	 * @return  void
	 */
	public function admin_bar_menu_view_types( $admin_bar, $root ) {

		if ( ! VAA_API::is_super_admin() ) {
			return;
		}

		$view_types = $this->vaa->get_view_types();

		// Do not render the view_types group if there are no view types to show.
		if ( ! $view_types ) {
			return;
		}

		$admin_bar->add_group( array(
			'id'     => self::$root . '-view_types',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$root = self::$root . '-view_types';

		$admin_bar->add_node( array(
			'id'     => $root . '-title',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-visibility' ) . __( 'View types', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
				'tabindex' => '0',
			),
		) );

		$parent = $root;// . '-title';

		$view_type_nodes = array();

		foreach ( $view_types as $type ) {

			$view_type_node = array(
				'name'          => $root . '-' . $type->get_type(),
				'value'         => $type->is_enabled(),
				'compare'       => true,
				'label'         => $type->get_label(),
				'auto_showhide' => true,
				'auto_js'       => array(
					'setting' => 'setting',
					'key'     => 'view_types',
					'values'  => array(
						$type->get_type() => array(
							'values' => array(
								'enabled' => array(),
							),
						),
					),
					'refresh' => true,
				),
			);

			if ( $type->get_description() ) {
				$view_type_node['description'] = $type->get_description();
				$view_type_node['help']        = true;
			}

			$view_type_nodes[ $type->get_priority() ][] = array(
				'id'     => $root . '-' . $type->get_type(),
				'parent' => $parent,
				'title'  => VAA_View_Admin_As_Form::do_checkbox( $view_type_node ),
				'href'   => false,
				'meta'   => array(
					'class' => 'auto-height',
				),
			);
		}

		ksort( $view_type_nodes );
		foreach ( $view_type_nodes as $nodes ) {
			foreach ( $nodes as $node ) {
				$admin_bar->add_node( $node );
			}
		}

		/**
		 * Add items to the view_types group.
		 *
		 * @since   1.7.1
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 * @param   string         self::$root  The main root item.
		 */
		do_action( 'vaa_admin_bar_view_types', $admin_bar, $root, self::$root );
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
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-admin-plugins' ) . __( 'Modules', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
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
		$class     = 'vaa-visitor-item vaa-has-icon';

		if ( empty( $root ) || $root === $main_root ) {

			$admin_bar->add_group( array(
				'id'     => $main_root . '-visitor',
				'parent' => $main_root,
				'meta'   => array(
					'class' => 'ab-sub-secondary',
				),
			) );

			$root   = $main_root . '-visitor';
			$class .= ' ab-vaa-title';
		} else {
			$class .= ' vaa-menupop';
		}

		$admin_bar->add_node( array(
			'id'     => $main_root . '-visitor-view',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-universal-access' )
			            . VAA_View_Admin_As_Form::do_view_title( __( 'Site visitor', VIEW_ADMIN_AS_DOMAIN ), 'visitor', true ),
			'href'   => '#',
			'meta'   => array(
				'title' => esc_attr__( 'View as site visitor', VIEW_ADMIN_AS_DOMAIN ),
				'class' => $class,
			),
		) );

		$done = true;
	}

	/**
	 * Add admin bar menu for view combinations.
	 * Combine views node as last item in the default group.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       (optional) The root item.
	 * @return  void
	 */
	public function admin_bar_menu_combine( $admin_bar, $root = '' ) {

		$admin_bar->add_node( array(
			'id'     => $root . '-combine-views',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_checkbox( array(
				'name'  => $root . '-combine-views',
				'label' => __( 'Combine views', VIEW_ADMIN_AS_DOMAIN ),
			) ) . VAA_View_Admin_As_Form::do_button( array(
				'name'  => $root . '-combine-views-apply',
				'label' => __( 'Apply', VIEW_ADMIN_AS_DOMAIN ),
				'class' => 'button-primary ab-vaa-conditional vaa-alignright',
				'attr'  => array(
					'vaa-condition-target' => '#' . $root . '-combine-views',
				),
			) ) . '<ul id="vaa-combine-views-selection" class="ab-sub-secondary ab-vaa-results" style="display: none;"></ul>',
			'href'   => false,
			'meta'   => array(
				'title' => esc_attr__( 'Make view combinations', VIEW_ADMIN_AS_DOMAIN ),
				'class' => 'vaa-button-container',
			),
		) );

	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.5.0
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class
	 * @return  \VAA_View_Admin_As_Admin_Bar  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Admin_Bar.
