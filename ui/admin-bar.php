<?php
/**
 * View Admin As - Admin Bar UI
 *
 * Admin Bar UI for View Admin As
 * 
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @version 1.5.2
 */
 
! defined( 'ABSPATH' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As_Admin_Bar extends VAA_View_Admin_As_Class_Base 
{
	/**
	 * The single instance of the class.
	 *
	 * @since	1.5
	 * @var		Class_Name
	 */
	private static $_instance = null;
	
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
	 * Private to make sure it isn't declared elsewhere
	 *
	 * @since   1.5
	 * @access 	private
	 * @return	void
	 */
	private function __construct() {

		// Init VAA
		$this->load_vaa();
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
	 * @access 	public
	 * @param 	object
	 * @return	void
	 */
	public function vaa_init() {

		// If the amount of items (roles and users combined) is more than 15 users, group them under their roles
		if ( "yes" == $this->get_userSettings('force_group_users') 
			 || 15 < ( count( $this->get_users() ) + count( $this->get_roles() ) ) ) { 
			$this->groupUserRoles = true;
			$this->searchUsers = true;
		}
		
		// Add the default nodes to the admin bar
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );
		// Add the caps nodes to the admin bar
		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_settings' ) );
		
		if ( ! is_network_admin() ) {
			// Add the caps nodes to the admin bar
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_caps' ) );
			// Add the roles nodes to the admin bar
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_roles' ) );
			// Add the users nodes to the admin bar
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu_users' ) );
		}
	}
	
	/**
	 * Add admin bar menu items
	 *
	 * @since   1.5
	 * @access 	public
	 * @param	object	$admin_bar
	 * @return	void
	 */
	public function admin_bar_menu( $admin_bar ) {
		
		$icon = 'dashicons-hidden';
		$title = __('Default view (Off)', 'view-admin-as');
		
		if ( $this->get_viewAs('caps') ) {
			$icon = 'dashicons-visibility';
			$title = __('Modified view', 'view-admin-as');
		}
		if ( $this->get_viewAs('role') ) {
			$icon = 'dashicons-visibility';
			// TODO: (PHP 5.4+) Use getter get_roles( $this->get_viewAs('role') )['name']
			$role = $this->get_roles( $this->get_viewAs('role') );
			$title = __('Viewing as role', 'view-admin-as') . ': ' . translate_user_role( $role->name );
		}
		if ( $this->get_viewAs('user') ) {
			$icon = 'dashicons-visibility';
			$selected_user_roles = array();
			foreach ( $this->get_selectedUser()->roles as $role ) {
				// TODO: (PHP 5.4+) Use getter get_roles( $role )['name']
				$role = $this->get_roles( $role );
				$selected_user_roles[] = translate_user_role( $role->name );
			}
			$title = __('Viewing as user', 'view-admin-as') . ': ' . $this->get_selectedUser()->data->display_name . ' <span class="user-role">(' . implode( ', ', $selected_user_roles ) . ')</span>';//$this->usernames[$this->viewAs['user']];
		}

		$view_as_location = 'top-secondary';
		if ( $this->get_userSettings('admin_menu_location') && in_array( $this->get_userSettings('admin_menu_location'), $this->get_allowedUserSettings('admin_menu_location') ) ) {
			$view_as_location = $this->get_userSettings('admin_menu_location');
		}
		
		// Add menu item
		$admin_bar->add_node( array(
			'id'		=> 'view-as',
			'parent'	=> $view_as_location,
			'title'		=> '<span class="ab-label">' . $title . '</span><span class="ab-icon alignright dashicons ' . $icon . '"></span>',
			'href'		=> false,
			'meta'		=> array(
				'title'		=> __('View Admin As', 'view-admin-as'),
			),
		) );
			
		// Add items at the beginning
		do_action( 'vaa_admin_bar_menu_before', $admin_bar );
		
		// Add reset button
		if ( $this->get_viewAs() ) {
			$rel = 'reset';
			$name = 'reset-view';
			if ( $this->get_userSettings('view_mode') == 'single' ) {
				$rel = 'reload';
				$name = 'reload';
			}
			$admin_bar->add_node( array(
				'id'		=> 'reset',
				'parent'	=> 'view-as',
				'title'		=> '<button id="reset-view" class="button button-secondary" name="' . $name . '">' . __('Reset to default', 'view-admin-as') . '</button>', // __('Default', 'view-admin-as')
				'href'		=> false,
				'meta'		=> array(
					'title'		=> esc_attr__('Reset to default', 'view-admin-as'),
					'class' 	=> 'vaa-reset-item',
					'rel'		=> $rel,
				),
			) );
		}
		
		// Add items
		do_action( 'vaa_admin_bar_menu', $admin_bar );
		
	}

	/**
	 * Add admin bar menu settings items
	 *
	 * @since   1.5
	 * @access 	public
	 * @param	object	$admin_bar
	 * @return	void
	 */
	public function admin_bar_menu_settings( $admin_bar ) {

		$admin_bar->add_node( array(
			'id'		=> 'settings',
			'parent'	=> 'view-as',
			'title'		=> __('Settings', 'view-admin-as'),
			'href'		=> false,
			'meta'		=> array(
				'class'		=> '',
			),
		) );

		// Add items at the beginning of the caps group
		do_action( 'vaa_admin_bar_settings_before', $admin_bar );

		$admin_bar->add_node( array(
			'id'		=> 'settings-admin-menu-location',
			'parent'	=> 'settings',
			'title'		=> '<label for="vaa_settings_admin_menu_location">' . __('Location', 'view-admin-as') . ': </label> 
							<select class="select" id="vaa_settings_admin_menu_location" name="vaa_settings_admin_menu_location">
									<option value="top-secondary" ' . selected( $this->get_userSettings('admin_menu_location'), 'top-secondary', false ) . ' >' . __( 'Default', 'view-admin-as' ) . '</option>
									<option value="my-account" ' . selected( $this->get_userSettings('admin_menu_location'), 'my-account', false ) . ' >' . __( 'My account', 'view-admin-as' ) . '</option>
							</select>
							<p class="description ab-item">' . __('Change the location of this menu node', 'view-admin-as') . '</p>',
			'href'		=> false,
			'meta'		=> array(
				'class'		=> 'auto-height',
			),
		) );

		$admin_bar->add_node( array(
			'id'		=> 'settings-view-mode',
			'parent'	=> 'settings',
			'title'		=> //'<p for="vaa_settings_view_mode">' . __('View mode', 'view-admin-as') . '</p>
							'<input type="radio" class="radio vaa_settings_view_mode" value="browse" id="vaa_settings_view_mode_browse" name="vaa_settings_view_mode" ' . checked( $this->get_userSettings('view_mode'), 'browse', false ) . '> <label for="vaa_settings_view_mode_browse">' . __('Browse mode', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Store view and use WordPress with this view', 'view-admin-as') . ' (' . __('default', 'view-admin-as') . ')</p>
							<input type="radio" class="radio vaa_settings_view_mode" value="single" id="vaa_settings_view_mode_single" name="vaa_settings_view_mode" ' . checked( $this->get_userSettings('view_mode'), 'single', false ) . '> <label for="vaa_settings_view_mode_single">' . __('Single switch mode', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Choose view on every pageload. This setting doesn\'t store views', 'view-admin-as') . '</p>',
			'href'		=> false,
			'meta'		=> array(
				'class'		=> 'auto-height',
			),
		) );

		if ( true !== $this->groupUserRoles || 15 >= ( count( $this->get_users() ) + count( $this->get_roles() ) ) ) { 
			$admin_bar->add_node( array(
				'id'		=> 'settings-force-group-users',
				'parent'	=> 'settings',
				'title'		=> '<input class="checkbox" value="1" id="vaa_settings_force_group_users" name="vaa_settings_force_group_users" type="checkbox" ' . checked( $this->get_userSettings('force_group_users'), "yes", false ) . '>
								<label for="vaa_settings_force_group_users">' . __('Group users', 'view-admin-as') . '</label>
								<p class="description ab-item">' . __('Group users under their assigned roles', 'view-admin-as') . '</p>',
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'auto-height',
				),
			) );
		}

		// Add items at the end of the caps group
		do_action( 'vaa_admin_bar_settings_after', $admin_bar );
	}

	/**
	 * Add admin bar menu caps items
	 *
	 * @since   1.5
	 * @access 	public
	 * @param	object	$admin_bar
	 * @return	void
	 */
	public function admin_bar_menu_caps( $admin_bar ) {
		
		// Make sure we have the latest added capabilities
		$this->vaa->store_caps();
		// Add capabilities group
		if ( $this->get_caps() && 0 < count( $this->get_caps() ) ) {

			$admin_bar->add_group( array(
				'id'		=> 'caps',
				'parent'	=> 'view-as',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'caps-title',
				'parent'	=> 'caps',
				'title'		=> __('Capabilities', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-vaa-title ab-vaa-toggle active',
				),
			) );
			
			// Add items at the beginning of the caps group
			do_action( 'vaa_admin_bar_caps_before', $admin_bar );
			
			$caps_quickselect_class = '';
			if ( $this->get_viewAs('caps') ) {
				$caps_quickselect_class .= ' current';
			}
			$admin_bar->add_node( array(
				'id'		=> 'caps-quickselect',
				'parent'	=> 'caps',
				'title'		=> __('Select', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> $caps_quickselect_class,
				),
			) );
			
			// Capabilities submenu
				$admin_bar->add_node( array(
					'id'		=> 'applycaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '<button id="apply-caps-view" class="button button-primary" name="apply-caps-view">' . __('Apply', 'view-admin-as') . '</button>
									<a id="close-caps-popup" class="button vaa-icon button-secondary" name="close-caps-popup"><span class="ab-icon dashicons dashicons-dismiss"></span></a>
									<a id="open-caps-popup" class="button vaa-icon button-secondary" name="open-caps-popup"><span class="ab-icon dashicons dashicons-plus-alt"></span></a>',
					'href'		=> false,
					'meta'		=> array(
						'class' 	=> 'vaa-button-container',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'filtercaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '<input id="filter-caps" name="vaa-filter" placeholder="' . esc_attr__('Filter', 'view-admin-as') . '" />',
					'href'		=> false,
					'meta'		=> array(
						'class' 	=> 'ab-vaa-filter filter-caps vaa-column-one-half vaa-column-first',
					),
				) );
				$role_select_options = '';
				foreach ( $this->get_roles() as $role_key => $role ) {
					$role_select_options .= '<option value="' . esc_attr( $role_key ) . '" data-caps=\'' . json_encode( $role->capabilities ) . '\'>= ' . translate_user_role( $role->name ) . '</option>';					
					$role_select_options .= '<option value="reversed-' . esc_attr( $role_key ) . '" data-reverse="1" data-caps=\'' . json_encode( $role->capabilities ) . '\'>≠ ' . translate_user_role( $role->name ) . '</option>';					
				}				
				$admin_bar->add_node( array(
					'id'		=> 'selectrolecaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '<select id="select-role-caps" name="vaa-selectrole"><option value="default">' . __('Default', 'view-admin-as') . '</option>' . $role_select_options . '</select>',
					'href'		=> false,
					'meta'		=> array(
						'class' 	=> 'ab-vaa-select select-role-caps vaa-column-one-half vaa-column-last',
						'html'		=> '',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'bulkselectcaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '' . __('All', 'view-admin-as') . ': &nbsp; 
									<button id="select-all-caps" class="button button-secondary" name="select-all-caps">' . __('Select', 'view-admin-as') . '</button>
									<button id="deselect-all-caps" class="button button-secondary" name="deselect-all-caps">' . __('Deselect', 'view-admin-as') . '</button>',
					'href'		=> false,
					'meta'		=> array(
						'class' 	=> 'vaa-button-container vaa-clear-float',
					),
				) );
				$caps_quickselect_content = '';
				foreach ( $this->get_caps() as $cap_name => $cap_val ) {
					$class = 'vaa-cap-item';
					$checked = '';
					// check if we've selected a capability view and we've changed some capabilities
					// TODO: (PHP 5.4+) Use getter get_viewAs('caps')[ $cap_name ]
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
					'id'		=> 'caps-quickselect-options',
					'parent'	=> 'caps-quickselect',
					'title'		=> $caps_quickselect_content,
					'href'		=> false,
					'meta'		=> array(
						'class' 	=> 'ab-vaa-multipleselect auto-height',
					),
				) );
			
			// Add items at the end of the caps group
			do_action( 'vaa_admin_bar_caps_after', $admin_bar );
		}
	}
	
	/**
	 * Add admin bar menu roles items
	 *
	 * @since   1.5
	 * @access 	public
	 * @param	object	$admin_bar
	 * @return	void
	 */
	public function admin_bar_menu_roles( $admin_bar ) {
		
		if ( $this->get_roles() && 0 < count( $this->get_roles() ) ) {
			
			$admin_bar->add_group( array(
				'id'		=> 'roles',
				'parent'	=> 'view-as',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'roles-title',
				'parent'	=> 'roles',
				'title'		=> __('Roles', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-vaa-title ab-vaa-toggle active',
				),
			) );
			
			// Add items at the beginning of the roles group
			do_action( 'vaa_admin_bar_roles_before', $admin_bar );
			
			// Add the roles
			foreach( $this->get_roles() as $role_key => $role ) {
				$href = '#';
				$class = 'vaa-role-item';
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
				// Check if this role is the current view
				if ( $this->get_viewAs('role') && $this->get_viewAs('role') == strtolower( $role->name ) ) {
					$class .= ' current';
					$href = false;
				}
				$admin_bar->add_node( array(
					'id'		=> 'role-' . $role_key,
					'parent'	=> 'roles',
					'title'		=> $title,
					'href'		=> $href,
					'meta'		=> array(
						'title'		=> esc_attr__('View as', 'view-admin-as') . ' ' . translate_user_role( $role->name ),
						'class' 	=> $class,
						'rel'		=> $role_key,
					),
				) );
			}
			
			// Add items at the end of the roles group
			do_action( 'vaa_admin_bar_roles_after', $admin_bar );
		}
	}
	
	/**
	 * Add admin bar menu users items
	 *
	 * @since   1.5
	 * @access 	public
	 * @param	object	$admin_bar
	 * @return	void
	 */
	public function admin_bar_menu_users( $admin_bar ) {
		
		if ( $this->get_users() && 0 < count( $this->get_users() ) ) {
			
			$admin_bar->add_group( array(
				'id'		=> 'users',
				'parent'	=> 'view-as',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'users-title',
				'parent'	=> 'users',
				'title'		=> __('Users', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-vaa-title ab-vaa-toggle active',
				),
			) );
			
			// Add items at the beginning of the users group
			do_action( 'vaa_admin_bar_users_before', $admin_bar );
			
			if ( true === $this->searchUsers ) {
				$admin_bar->add_node( array(
					'id'		=> 'searchuser',
					'parent'	=> 'users',
					'title'		=> '<input id="search" name="vaa-search" placeholder="' . esc_attr__('Search', 'view-admin-as') . ' (' . strtolower( __('Username', 'view-admin-as') ) . ')" />',
					'href'		=> false,
					'meta'		=> array(
						'class' 	=> 'ab-vaa-search search-users',
						'html'		=> '<ul id="vaa-searchuser-results" class="ab-sub-secondary ab-submenu"></ul>',
					),
				) );
			}
			// Add the users
			$cur_role = '';
			foreach( $this->get_users() as $user_key => $user ) {
				$href = '#';
				$title = $user->data->display_name;
				$class = 'vaa-user-item';
				// Check if this user is the current view
				if ( $this->get_viewAs('user') && $this->get_viewAs('user') == $user->data->ID ) {
					$class .= ' current';
					$href = false;
				}
				$parent = 'users';
				
				if ( true === $this->groupUserRoles ) { // Users grouped under roles
					foreach ( $user->roles as $role ) {
						$cur_role = $role;
						$parent = 'role-' . $cur_role;
						$admin_bar->add_node( array(
							'id'		=> 'user-' . $user->data->ID . '-' . $cur_role,
							'parent'	=> $parent,
							'title'		=> $title,
							'href'		=> $href,
							'meta'		=> array(
								'title'		=> esc_attr__('View as', 'view-admin-as') . ' ' . $user->data->display_name,
								'class' 	=> $class,
								'rel' 		=> $user->data->ID,
							),
						) );
					}
				} else { // Users displayed as normal
					$all_roles = $this->get_roles();
					$user_roles = array();
					// Add the roles of this user in the name
					foreach ( $user->roles as $role ) {
						$user_roles[] = translate_user_role( $all_roles[ $role ]->name );
					}
					$title = $title.' &nbsp; <span class="user-role">(' . implode( ', ', $user_roles ) . ')</span>';
					$admin_bar->add_node( array(
						'id'		=> 'user-' . $user->data->ID,
						'parent'	=> $parent,
						'title'		=> $title,
						'href'		=> $href,
						'meta'		=> array(
							'title'		=> esc_attr__('View as', 'view-admin-as') . ' ' . $user->data->display_name,
							'class' 	=> $class,
							'rel' 		=> $user->data->ID,
						),
					) );
				}
			}
			
			// Add items at the end of the users group
			do_action( 'vaa_admin_bar_users_after', $admin_bar );
		}
	}
	
	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since	1.5
	 * @access 	public
	 * @static
	 * @return	Main instance.
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
