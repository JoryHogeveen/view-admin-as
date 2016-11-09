<?php
/**
 * View Admin As - Admin Bar UI
 *
 * Admin Bar UI for View Admin As
 *
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.5
 * @version 1.6.x
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As_Admin_Bar extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since   1.5
	 * @var     VAA_View_Admin_As_Admin_Bar
	 */
	private static $_instance = null;

	/**
	 * Admin bar root item ID
	 *
	 * @since  1.6-dev
	 * @var    string
	 */
	public static $root = 'vaa';

	/**
	 * Database option key
	 *
	 * @since  1.5
	 * @var    string
	 */
	protected $optionKey = 'vaa_view_admin_as';

	/**
	 * Group the users under their roles?
	 *
	 * @since  1.5
	 * @var    bool
	 */
	private $groupUserRoles = false;

	/**
	 * Enable search bar for users?
	 *
	 * @since  1.5
	 * @var    bool
	 */
	private $searchUsers = false;

	/**
	 * Construct function
	 * Protected to make sure it isn't declared elsewhere
	 *
	 * @since   1.5
	 * @access  protected
	 */
	protected function __construct() {
		self::$_instance = $this;
		parent::__construct();

		if ( $this->is_vaa_enabled() ) {
			add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
		}

		// Load data
		$this->set_optionData( get_option( $this->get_optionKey() ) );
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_view_admin_as_init' action
	 * @return  void
	 */
	public function vaa_init() {

		// If the amount of items (roles and users combined) is more than 15 users, group them under their roles
		if ( "yes" == $this->get_userSettings('force_group_users')
			 || 15 < ( count( $this->get_users() ) + count( $this->get_roles() ) ) ) {
			$this->groupUserRoles = true;
			$this->searchUsers = true;
		}

		// There are no roles to group users on network pages
		if ( is_network_admin() ) {
			$this->groupUserRoles = false;
		}

		// Add the default nodes to the WP admin bar
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );
		add_action( 'vaa_toolbar_menu', array( $this, 'admin_bar_menu' ), 10, 2 );

		// Add the global nodes to the admin bar
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_info' ), 1 );
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_settings' ), 2 );

		// Add the caps nodes to the admin bar
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_caps' ), 10 );

		// Roles are not used on network pages
		if ( ! is_network_admin() ) {
			// Add the roles nodes to the admin bar
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_roles' ), 20 );
		}

		// Add the users nodes to the admin bar
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_users' ), 30 );
	}

	/**
	 * Add admin bar menu items
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'admin_bar_menu' action
	 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
	 * @param   object  $admin_bar
	 * @param   string  $root
	 * @return  void
	 */
	public function admin_bar_menu( $admin_bar, $root = '' ) {

		$icon = 'dashicons-hidden';
		$title = __('Default view (Off)', 'view-admin-as');

		if ( $this->get_viewAs() ) {
			$icon = 'dashicons-visibility';
		}

		if ( $this->get_viewAs('caps') ) {
			$title = __('Modified view', 'view-admin-as');
		}
		if ( $this->get_viewAs('role') ) {
			$title = __('Viewing as role', 'view-admin-as') . ': ' . translate_user_role( $this->get_roles( $this->get_viewAs('role') )->name );
		}
		if ( $this->get_viewAs('user') ) {
			$selected_user_roles = array();
			foreach ( $this->get_selectedUser()->roles as $role ) {
				$selected_user_roles[] = translate_user_role( $this->get_roles( $role )->name );
			}
			$title = __('Viewing as user', 'view-admin-as') . ': ' . $this->get_selectedUser()->data->display_name . ' <span class="user-role">(' . implode( ', ', $selected_user_roles ) . ')</span>';
		}

		/**
		 * Filter the text to show when a view is applied
		 *
		 * @since  1.6
		 * @param  string      $title
		 * @param  bool|array  The view
		 * @return string
		 */
		$title = apply_filters( 'vaa_admin_bar_viewing_as_title', $title, $this->get_viewAs() );

		if ( empty( $root ) ) {
			$root = 'top-secondary';
			if ( $this->get_userSettings('admin_menu_location') && in_array( $this->get_userSettings('admin_menu_location'), $this->get_allowedUserSettings('admin_menu_location') ) ) {
				$root = $this->get_userSettings('admin_menu_location');
			}
		}

		// Add menu item
		$admin_bar->add_node( array(
			'id'        => self::$root,
			'parent'    => $root,
			'title'     => '<span class="ab-label">' . $title . '</span><span class="ab-icon alignright dashicons ' . $icon . '"></span>',
			'href'      => false,
			'meta'      => array(
				'title'    => __('View Admin As', 'view-admin-as'),
				'tabindex' => '0'
			),
		) );

		/**
		 * Add items as first
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_menu_before', $admin_bar, self::$root );

		// Add reset button
		if ( $this->get_viewAs() ) {
			$rel = 'reset';
			$name = 'reset-view';
			if ( $this->get_userSettings('view_mode') == 'single' ) {
				$rel = 'reload';
				$name = 'reload';
			}
			$admin_bar->add_node( array(
				'id'        => self::$root . '_reset',
				'parent'    => self::$root,
				'title'     => '<button id="reset-view" class="button button-secondary" name="' . $name . '">' . __('Reset to default', 'view-admin-as') . '</button>', // __('Default', 'view-admin-as')
				'href'      => false,
				'meta'      => array(
					'title'    => esc_attr__('Reset to default', 'view-admin-as'),
					'class'    => 'vaa-reset-item',
					'rel'      => $rel
				),
			) );
		}

		/**
		 * Add items
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_menu', $admin_bar, self::$root );

	}

	/**
	 * Add admin bar menu info items
	 *
	 * @since   1.6
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   object  $admin_bar
	 * @return  void
	 */
	public function admin_bar_menu_info( $admin_bar ) {

		$root = self::$root . '-info';

		$admin_bar->add_node( array(
			'id'        => $root,
			'parent'    => self::$root,
			'title'     => self::do_icon( 'dashicons-info' ) . __('Info', 'view-admin-as'),
			'href'      => false,
			'meta'      => array(
				'class'    => 'vaa-has-icon',
				'tabindex' => '0'
			),
		) );

		$admin_bar->add_group( array(
			'id'        => $root . '-about',
			'parent'    => $root,
			'meta'      => array(
				'class'     => 'ab-sub-secondary',
			)
		) );

		$admin_bar->add_node(
			array(
				'parent' => $root . '-about',
				'id'     => $root . '-about-version',
				'title'  => __( 'Version', 'view-admin-as' ) . ': ' . VIEW_ADMIN_AS_VERSION,
				'href'   => false,
			)
		);
		$admin_bar->add_node(
			array(
				'parent' => $root . '-about',
				'id'     => $root . '-about-author',
				'title'  => 'Keraweb (Jory Hogeveen)',
				'href'   => 'https://profiles.wordpress.org/keraweb/',
				'meta'   => array(
					'target' => '_blank'
				)
			)
		);

		/**
		 * Add items at the beginning of the info group
		 * @since   1.6
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_info_before', $admin_bar, $root, self::$root );

		$info_links = array(
			array(
				'id'    => $root . '-support',
				'title' => self::do_icon( 'dashicons-testimonial' ) . __( 'Need support?', 'view-admin-as' ),
				'href'  => 'https://wordpress.org/support/plugin/view-admin-as/',
			),
			array(
				'id'    => $root . '-review',
				'title' => self::do_icon( 'dashicons-star-filled' ) . __( 'Give 5 stars on WordPress.org!', 'view-admin-as' ),
				'href'  => 'https://wordpress.org/support/plugin/view-admin-as/reviews/',
			),
			array(
				'id'    => $root . '-translate',
				'title' => self::do_icon( 'dashicons-translation' ) . __( 'Help translating this plugin!', 'view-admin-as' ),
				'href'  => 'https://translate.wordpress.org/projects/wp-plugins/view-admin-as',
			),
			array(
				'id'    => $root . '-issue',
				'title' => self::do_icon( 'dashicons-lightbulb' ) . __( 'Have ideas or a bug report?', 'view-admin-as' ),
				'href'  => 'https://github.com/JoryHogeveen/view-admin-as/issues',
			),
			array(
				'id'    => $root . '-docs',
				'title' => self::do_icon( 'dashicons-book-alt' ) . __( 'Documentation', 'view-admin-as' ),
				'href'  => 'https://github.com/JoryHogeveen/view-admin-as/wiki',
			),
			array(
				'id'    => $root . '-github',
				'title' => self::do_icon( 'dashicons-editor-code' ) . __( 'Follow development on GitHub', 'view-admin-as' ),
				'href'  => 'https://github.com/JoryHogeveen/view-admin-as/tree/dev',
			),
			array(
				'id'    => $root . '-donate',
				'title' => self::do_icon( 'dashicons-smiley' ) . __( 'Buy me a coffee!', 'view-admin-as' ),
				'href'  => 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest',
			)
		);

		foreach ( $info_links as $link ) {
			$admin_bar->add_node( array(
				'parent' => $root,
				'id'     => $link['id'],
				'title'  => $link['title'],
				'href'   => $link['href'],
				'meta'   => array(
					'class'  => 'auto-height vaa-has-icon',
					'target' => '_blank'
				),
			) );
		}

		/**
		 * Add items at the end of the info group
		 * @since   1.6
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_info_after', $admin_bar, $root, self::$root );

	}

	/**
	 * Add admin bar menu settings items
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   object  $admin_bar
	 * @return  void
	 */
	public function admin_bar_menu_settings( $admin_bar ) {

		$root = self::$root . '-settings';

		$admin_bar->add_node( array(
			'id'        => $root,
			'parent'    => self::$root,
			'title'     => self::do_icon( 'dashicons-admin-settings' ) . __('Settings', 'view-admin-as'),
			'href'      => false,
			'meta'      => array(
				'class'    => 'vaa-has-icon',
				'tabindex' => '0'
			),
		) );

		/**
		 * Add items at the beginning of the settings group
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_settings_before', $admin_bar, $root, self::$root );

		$admin_bar->add_node( array(
			'id'        => $root . '-admin-menu-location',
			'parent'    => $root,
			'title'     => '<label for="' . $root . '-admin-menu-location">' . __('Location', 'view-admin-as') . ': &nbsp; </label>
							<select class="select" id="' . $root . '-admin-menu-location" name="vaa_settings_admin_menu_location">
									<option value="top-secondary" ' . selected( $this->get_userSettings('admin_menu_location'), 'top-secondary', false ) . ' >' . __( 'Default', 'view-admin-as' ) . '</option>
									<option value="my-account" ' . selected( $this->get_userSettings('admin_menu_location'), 'my-account', false ) . ' >' . __( 'My account', 'view-admin-as' ) . '</option>
							</select>
							<p class="description ab-item">' . __('Change the location of this menu node', 'view-admin-as') . '</p>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );

		$admin_bar->add_node( array(
			'id'        => $root . '-view-mode',
			'parent'    => $root,
			'title'     => //'<p for="vaa_settings_view_mode">' . __('View mode', 'view-admin-as') . '</p>
							'<input type="radio" value="browse" class="radio ' . $root . '-view-mode" id="' . $root . '-view-mode-browse" name="vaa_settings_view_mode" ' . checked( $this->get_userSettings('view_mode'), 'browse', false ) . '> <label for="' . $root . '-view-mode-browse">' . __('Browse mode', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Store view and use WordPress with this view', 'view-admin-as') . ' (' . __('default', 'view-admin-as') . ')</p>
							<input type="radio" value="single" class="radio ' . $root . '-view-mode" id="' . $root . '-view-mode-single" name="vaa_settings_view_mode" ' . checked( $this->get_userSettings('view_mode'), 'single', false ) . '> <label for="' . $root . '-view-mode-single">' . __('Single switch mode', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Choose view on every pageload. This setting doesn\'t store views', 'view-admin-as') . '</p>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );

		$admin_bar->add_node( array(
			'id'        => $root . '-hide-front',
			'parent'    => $root,
			'title'     => '<input type="checkbox" value="1" class="checkbox ' . $root . '-hide-front" id="' . $root . '-hide-front" name="vaa_settings_hide_front" ' . checked( $this->get_userSettings('hide_front'), 'yes', false ) . '>
							<label for="' . $root . '-hide-front">' . __('Hide on frontend', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Hide on frontend when no view is selected and the admin bar is not shown', 'view-admin-as') . '</p>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );

		$admin_bar->add_node( array(
			'id'        => $root . '-freeze-locale',
			'parent'    => $root,
			'title'     => '<input type="checkbox" value="1" class="checkbox ' . $root . '-freeze-locale" id="' . $root . '-freeze-locale" name="vaa_settings_freeze_locale" ' . checked( $this->get_userSettings('freeze_locale'), 'yes', false ) . '>
							<label for="' . $root . '-freeze-locale">' . __('Freeze locale', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Force your own locale setting to the current view', 'view-admin-as') . '</p>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );

		/**
		 * force_group_users setting
		 * @since   1.5.2
		 */
		if ( true !== $this->groupUserRoles || 15 >= ( count( $this->get_users() ) + count( $this->get_roles() ) ) ) {
			$admin_bar->add_node( array(
				'id'        => $root . '-force-group-users',
				'parent'    => $root,
				'title'     => '<input type="checkbox" value="1" class="checkbox" id="' . $root . '-force-group-users" name="vaa_settings_force_group_users" ' . checked( $this->get_userSettings('force_group_users'), "yes", false ) . '>
								<label for="' . $root . '-force-group-users">' . __('Group users', 'view-admin-as') . '</label>
								<p class="description ab-item">' . __('Group users under their assigned roles', 'view-admin-as') . '</p>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'auto-height',
					'tabindex' => '0'
				),
			) );
		}

		/**
		 * Add items at the end of the settings group
		 * @since   1.5
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_settings_after', $admin_bar, $root, self::$root );
	}

	/**
	 * Add admin bar menu caps items
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   object  $admin_bar
	 * @return  void
	 */
	public function admin_bar_menu_caps( $admin_bar ) {

		// Make sure we have the latest added capabilities
		$this->store->store_caps();
		// Add capabilities group
		if ( $this->get_caps() && 0 < count( $this->get_caps() ) ) {

			$root = self::$root . '-caps';

			$admin_bar->add_group( array(
				'id'        => $root,
				'parent'    => self::$root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-title',
				'parent'    => $root,
				'title'     => self::do_icon( 'dashicons-admin-generic' ) . __('Capabilities', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
					'tabindex' => '0'
				),
			) );

			/**
			 * Add items at the beginning of the caps group
			 * @since   1.5
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_caps_before', $admin_bar, $root, self::$root );

			$caps_quickselect_class = '';
			if ( $this->get_viewAs('caps') ) {
				$caps_quickselect_class .= ' current';
			}
			$admin_bar->add_node( array(
				'id'        => $root . '-quickselect',
				'parent'    => $root,
				'title'     => __('Select', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => $caps_quickselect_class,
					'tabindex' => '0'
				),
			) );

			// Capabilities submenu
				$admin_bar->add_node( array(
					'id'        => $root . '-applycaps',
					'parent'    => $root . '-quickselect',
					'title'     => '<button id="apply-caps-view" class="button button-primary" name="apply-caps-view">' . __('Apply', 'view-admin-as') . '</button>
									<a tabindex="0" id="close-caps-popup" class="button vaa-icon button-secondary" name="close-caps-popup"><span class="ab-icon dashicons dashicons-dismiss"></span></a>
									<a tabindex="0" id="open-caps-popup" class="button vaa-icon button-secondary" name="open-caps-popup"><span class="ab-icon dashicons dashicons-plus-alt"></span></a>',
					'href'      => false,
					'meta'      => array(
						'class'    => 'vaa-button-container',
					),
				) );
				$admin_bar->add_node( array(
					'id'        => $root . '-filtercaps',
					'parent'    => $root . '-quickselect',
					'title'     => '<input id="filter-caps" name="vaa-filter" placeholder="' . esc_attr__('Filter', 'view-admin-as') . '" />',
					'href'      => false,
					'meta'      => array(
						'class'    => 'ab-vaa-filter filter-caps vaa-column-one-half vaa-column-first',
					),
				) );
				$role_select_options = '';
				foreach ( $this->get_roles() as $role_key => $role ) {
					$role_select_options .= '<option value="' . esc_attr( $role_key ) . '" data-caps=\'' . json_encode( $role->capabilities ) . '\'>= ' . translate_user_role( $role->name ) . '</option>';
					$role_select_options .= '<option value="reversed-' . esc_attr( $role_key ) . '" data-reverse="1" data-caps=\'' . json_encode( $role->capabilities ) . '\'>≠ ' . translate_user_role( $role->name ) . '</option>';
				}
				$admin_bar->add_node( array(
					'id'        => $root . '-selectrolecaps',
					'parent'    => $root . '-quickselect',
					'title'     => '<select id="select-role-caps" name="vaa-selectrole"><option value="default">' . __('Default', 'view-admin-as') . '</option>' . $role_select_options . '</select>',
					'href'      => false,
					'meta'      => array(
						'class'     => 'ab-vaa-select select-role-caps vaa-column-one-half vaa-column-last',
						'html'      => '',
					),
				) );
				$admin_bar->add_node( array(
					'id'        => $root . '-bulkselectcaps',
					'parent'    => $root . '-quickselect',
					'title'     => '' . __('All', 'view-admin-as') . ': &nbsp;
									<button id="select-all-caps" class="button button-secondary" name="select-all-caps">' . __('Select', 'view-admin-as') . '</button>
									<button id="deselect-all-caps" class="button button-secondary" name="deselect-all-caps">' . __('Deselect', 'view-admin-as') . '</button>',
					'href'      => false,
					'meta'      => array(
						'class'     => 'vaa-button-container vaa-clear-float',
					),
				) );
				$caps_quickselect_content = '';
				foreach ( $this->get_caps() as $cap_name => $cap_val ) {
					$class = 'vaa-cap-item';
					$checked = '';
					// check if we've selected a capability view and we've changed some capabilities
					$selected_caps = $this->get_viewAs('caps');
					if ( isset( $selected_caps[ $cap_name ] ) ) {
						if ( 1 == $selected_caps[ $cap_name ] ) {
							$checked = ' checked="checked"';
						}
					} elseif ( 1 == $cap_val ) {
						$checked = ' checked="checked"';
					}
					// The list of capabilities
					$caps_quickselect_content .=
						'<div class="ab-item '.$class.'">
							<input class="checkbox" value="' . esc_attr( $cap_name ) . '" id="vaa_' . esc_attr( $cap_name ) . '" name="vaa_' . esc_attr( $cap_name ) . '" type="checkbox"' . $checked . '>
							<label for="vaa_' . esc_attr( $cap_name ) . '">' . str_replace( '_', ' ', $cap_name ) . '</label>
						</div>';
				}
				$admin_bar->add_node( array(
					'id'        => $root . '-quickselect-options',
					'parent'    => $root . '-quickselect',
					'title'     => $caps_quickselect_content,
					'href'      => false,
					'meta'      => array(
						'class'     => 'ab-vaa-multipleselect auto-height',
					),
				) );

			/**
			 * Add items at the end of the caps group
			 * @since   1.5
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_caps_after', $admin_bar, $root, self::$root );
		}
	}

	/**
	 * Add admin bar menu roles items
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   object  $admin_bar
	 * @return  void
	 */
	public function admin_bar_menu_roles( $admin_bar ) {

		if ( $this->get_roles() && 0 < count( $this->get_roles() ) ) {

			$root = self::$root . '-roles';

			$admin_bar->add_group( array(
				'id'        => $root,
				'parent'    => self::$root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-title',
				'parent'    => $root,
				'title'     => self::do_icon( 'dashicons-groups' ) . __('Roles', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
					'tabindex' => '0'
				),
			) );

			/**
			 * Add items at the beginning of the roles group
			 * @since   1.5
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_roles_before', $admin_bar, self::$root );

			// Add the roles
			foreach( $this->get_roles() as $role_key => $role ) {
				$parent = $root;
				$href = '#';
				$class = 'vaa-role-item';
				$has_icon = false;
				$title = translate_user_role( $role->name );
				// Check if the users need to be grouped under their roles
				if ( true === $this->groupUserRoles ) {
					$class .= ' vaa-menupop'; // make sure items are aligned properly when some roles don't have users
					// Check if the current view is a user with this role
					if ( $this->get_viewAs('user') && $this->get_selectedUser() && in_array( $role_key, $this->get_selectedUser()->roles ) ) {
						$class .= ' current-parent';
					}
					// If there are users with this role, add a counter
					$user_count = 0;
					foreach ( $this->get_users() as $user ) {
						if ( in_array( $role_key, $user->roles ) ) {
							$user_count++;
						}
					}
					if ( 0 < $user_count ) {
						$title = $title . ' <span class="user-count">(' . $user_count . ')</span>';
					}
				}
				if ( $has_icon ) {
					$class .= ' vaa-has-icon';
				}
				// Check if this role is the current view
				if ( $this->get_viewAs('role') && $this->get_viewAs('role') == strtolower( $role->name ) ) {
					$class .= ' current';
					$href = false;
				}
				$admin_bar->add_node( array(
					'id'        => $root . '-role-' . $role_key,
					'parent'    => $parent,
					'title'     => $title,
					'href'      => $href,
					'meta'      => array(
						'title'     => esc_attr__('View as', 'view-admin-as') . ' ' . translate_user_role( $role->name ),
						'class'     => $class,
						'rel'       => $role_key
					),
				) );
			}

			/**
			 * Add items at the end of the roles group
			 * @since   1.5
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_roles_after', $admin_bar, $root, self::$root );
		}
	}

	/**
	 * Add admin bar menu users items
	 *
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 * @param   object  $admin_bar
	 * @return  void
	 */
	public function admin_bar_menu_users( $admin_bar ) {

		if ( $this->get_users() && 0 < count( $this->get_users() ) ) {

			$root = self::$root . '-users';

			$admin_bar->add_group( array(
				'id'        => $root,
				'parent'    => self::$root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-title',
				'parent'    => $root,
				'title'     => self::do_icon( 'dashicons-admin-users' ) . __('Users', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
					'tabindex' => '0'
				),
			) );

			/**
			 * Add items at the beginning of the users group
			 * @since   1.5
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_users_before', $admin_bar, $root, self::$root );

			if ( true === $this->searchUsers ) {
				$admin_bar->add_node( array(
					'id'        => self::$root . 'searchuser',
					'parent'    => $root,
					'title'     => '<input id="search" name="vaa-search" placeholder="' . esc_attr__('Search', 'view-admin-as') . ' (' . strtolower( __('Username', 'view-admin-as') ) . ')" />',
					'href'      => false,
					'meta'      => array(
						'class'     => 'ab-vaa-search search-users',
						'html'      => '<ul id="vaa-searchuser-results" class="ab-sub-secondary ab-submenu"></ul>',
					),
				) );
			}
			// Add the users
			foreach( $this->get_users() as $user_key => $user ) {
				$parent = $root;
				$href = '#';
				$title = $user->data->display_name;
				$class = 'vaa-user-item';
				// Check if this user is the current view
				if ( $this->get_viewAs('user') && $this->get_viewAs('user') == $user->data->ID ) {
					$class .= ' current';
					$href = false;
				}

				if ( true === $this->groupUserRoles ) {
					// Users grouped under roles
					foreach ( $user->roles as $role ) {
						$parent = self::$root . '-roles-role-' . $role;
						$admin_bar->add_node( array(
							'id'        => $root . '-user-' . $user->data->ID . '-' . $role,
							'parent'    => $parent,
							'title'     => $title,
							'href'      => $href,
							'meta'      => array(
								'title'     => esc_attr__('View as', 'view-admin-as') . ' ' . $user->data->display_name,
								'class'     => $class,
								'rel'       => $user->data->ID,
							),
						) );
					}
				} else {
					// Users displayed as normal
					$all_roles = $this->get_roles();
					$user_roles = array();
					// Add the roles of this user in the name
					foreach ( $user->roles as $role ) {
						$user_roles[] = translate_user_role( $all_roles[ $role ]->name );
					}
					$title = $title.' &nbsp; <span class="user-role">(' . implode( ', ', $user_roles ) . ')</span>';
					$admin_bar->add_node( array(
						'id'        => $root . '-user-' . $user->data->ID,
						'parent'    => $parent,
						'title'     => $title,
						'href'      => $href,
						'meta'      => array(
							'title'     => esc_attr__('View as', 'view-admin-as') . ' ' . $user->data->display_name,
							'class'     => $class,
							'rel'       => $user->data->ID,
						),
					) );
				}
			}

			/**
			 * Add items at the end of the users group
			 * @since   1.5
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_users_after', $admin_bar, $root, self::$root );
		}
	}

	/**
	 * Returns icon html for WP admin bar
	 * @since   1.6.x
	 * @param   string  $icon
	 * @return  string
	 */
	public static function do_icon( $icon ) {
		return '<span class="ab-icon dashicons ' . $icon . '" aria-hidden="true"></span>';
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.5
	 * @access  public
	 * @static
	 * @param   object|bool  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Admin_Bar|bool
	 */
	public static function get_instance( $caller = false ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' == get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		return false;
	}

} // end class
