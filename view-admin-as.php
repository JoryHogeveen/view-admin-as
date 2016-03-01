<?php
/**
 * Plugin Name: View Admin As
 * Description: View the WordPress admin as a specific role, switch between users and temporarily change your capabilities.
 * Plugin URI:  https://wordpress.org/plugins/view-admin-as/
 * Version:     1.3.4
 * Author:      Jory Hogeveen
 * Author URI:  http://www.keraweb.nl
 * Text Domain: view-admin-as
 * Domain Path: /languages/
 * License: 	GPLv2
 */
 
! defined( 'ABSPATH' ) and die( 'You shall not pass!' );

$vaa_view_admin_as = new VAA_View_Admin_As();

class VAA_View_Admin_As {
	
	/**
	 * Plugin version
	 *
	 * @since  1.3.1
	 * @var    String
	 */
	protected $version = '1.3.4';

	/**
	 * Database version - not used yet
	 *
	 * @since  1.3.4
	 * @var    String
	 */
	protected $dbVersion = '1.3';

	/**
	 * Meta key for view data
	 *
	 * @since  1.3.4
	 * @var    Boolean
	 */
	protected $userMetaKey = 'vaa-view-admin-as';
	
	/**
	 * Expiration time for view data
	 *
	 * @since  1.3.4
	 * @var    Integer
	 */
	protected $metaExpiration = 86400; // one day: ( 24 * 60 * 60 )

	/**
	 * Enable functionalities?
	 *
	 * @since  0.1
	 * @var    Boolean
	 */
	protected $enable = false;
	
	/**
	 * Current user object
	 *
	 * @since  0.1
	 * @var    Object
	 */	
	protected $curUser = false;
	
	/**
	 * Current user session
	 *
	 * @since  1.3.4
	 * @var    String
	 */	
	protected $curUserSession = '';
	
	/**
	 * Selected view mode
	 * 
	 * Format: array( VIEW_TYPE => NAME )
	 *
	 * @since  0.1
	 * @var    Array
	 */
	protected $viewAs = false;
	
	/**
	 * Array of available capabilities
	 *
	 * @since  1.3
	 * @var    Array
	 */	
	protected $caps;
	
	/**
	 * Array of available roles
	 *
	 * @since  0.1
	 * @var    Array
	 */	
	protected $roles;
	
	/**
	 * Array of available user objects
	 *
	 * @since  0.1
	 * @var    Array
	 */	
	protected $users;
	
	/**
	 * Array of available usernames (key) and display names (value)
	 *
	 * @since  0.1
	 * @var    Array
	 */	
	protected $usernames;
	
	/**
	 * Array of available user ID's (key) and display names (value)
	 *
	 * @since  0.1
	 * @var    Array
	 */	
	protected $userids;
	
	/**
	 * The selected user object (if the user view is selected)
	 *
	 * @since  0.1
	 * @var    Object
	 */	
	protected $selectedUser;
	
	
	/**
	 * Init function to register plugin hook
	 *
	 * @since   0.1
	 * @return	void
	 */
	function __construct() {
		
		// Lets start!
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}
	
	/**
	 * Init function/action to check current user, load nessesary data and register all used hooks
	 *
	 * @since   0.1
	 * @return	void
	 */
	function init() {
		
		// Get the current user
		$this->curUser = wp_get_current_user();
		$this->curUserSession = wp_get_session_token();
		
		// Reset view to default if something goes wrong
		if ( is_user_logged_in() && isset( $_GET['reset-view'] ) ) {
			$this->reset_view();
		}
		// Clear user metadata
		if ( is_user_logged_in() && isset( $_GET['reset-all-views'] ) ) {
			$this->reset_metadata();
		}
		
		// When a user logs in or out, reset the view to default
		add_action( 'wp_login', array( $this, 'cleanup_metadata' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'reset_view' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'reset_view' ) );
		
		// Check if current user is logged in and is an admin or (in a network) super admin + disable plugin functions for nedwork admin pages
		if ( is_user_logged_in() && current_user_can( 'administrator' ) && ! is_network_admin() && $this->curUserSession != '' ) {
			$this->enable = true;
			
			// Load translations
			$this->load_textdomain();
			
			// Store available capabilities
			$this->caps = $this->curUser->allcaps;
			ksort( $this->caps );
			
			// Store available roles
			global $wp_roles;
			$this->roles = $wp_roles->roles;
			$this->roles = apply_filters( 'editable_roles', $this->roles );
			// Multisite install: Admin users are enabled only for super admins
			if ( is_multisite() && ! is_super_admin( $this->curUser->ID ) ) {
				unset( $this->roles['administrator'] );
			}
			
			// Store available users
			$userArgs = array(
				'orderby' => 'display_name'
			);
			// Sort users by role and filter them on available roles
			$this->users = $this->filter_sort_users_by_role( get_users( $userArgs ) );
			
			// Loop though all users
			foreach ( $this->users as $uKey => $uData ) {
				if ( is_super_admin( $uData->ID ) ) {
					// Remove super admins for multisites and regular admins for normal installs
					unset( $this->users[$uKey] );
				} else {
					// Add non super admins to the username list
					$this->usernames[$uData->data->user_login] = $uData->data->display_name;
					$this->userids[$uData->data->ID] = $uData->data->display_name;
				}
			}
			
			// Get the current view (returns false if not found)
			$this->viewAs = $this->get_view();
			// If view is set, 
			if ( $this->viewAs ) {
				// Force display of admin bar (older WP versions)
				show_admin_bar( true );
				// Force display of admin bar (WP 3.3+)
				add_filter( 'show_admin_bar', '__return_true', 999999999 );
				
				// Change current user object so changes can be made on various screen settings
				if ( isset( $this->viewAs['user'] ) ) {
					$this->selectedUser = wp_set_current_user( $this->viewAs['user'] );
				}
				
				if ( isset( $this->viewAs['role'] ) || isset ( $this->viewAs['caps'] ) ) {
					// Multisite admins
					add_filter( 'map_meta_cap', array( $this, 'change_caps' ), 0, 4 );
				}
			}
			
			// Add selector to admin bar
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_items' ) );
			
			// Admin selector ajax return
			add_action( 'wp_ajax_update_view_as', array( $this, 'ajax_update_view_as' ) );
			//add_action( 'wp_ajax_nopriv_update_view_as', array( $this, 'ajax_update_view_as' ) );
			
			// Dúh..
			add_action( 'admin_enqueue_scripts', array( $this, 'add_styles_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_styles_scripts' ) );
			
			add_filter( 'wp_die_handler', array( $this, 'die_handler' ) );
			
			// Fix some compatibility issues, more to come!
			$this->plugin_compatibility();
			
		} else if ( is_user_logged_in() && ! current_user_can( 'administrator' ) ) {
			
			// Extra security check for non-admins who did something naughty
			delete_user_meta( get_current_user_id(), 'vaa-view-admin-as' );
		}
			
	}
	
	/**
	 * Sort users by role
	 *
	 * @param	array	$users
	 * 
	 * @since   1.1
	 * @return	array	$users
	 */
	function filter_sort_users_by_role($users) {
		if ( ! isset( $this->roles ) ) {
			return $users;
		}
		$tmpUsers = array();
		foreach( $this->roles as $role => $roleAtts ) {
			foreach ( $users as $user ) {
				if ( in_array( 'administrator', $user->roles ) && is_multisite() && ! is_super_admin( $this->curUser->ID ) ) {
					// Do nothing, normal admins are not allowed to switch to other admins in a multisite
				} else {
					// Reset the array for make sure we find a key 
					// Only one key is needed to add the user to the list of available users
					reset($user->roles);
					if ( $role == current($user->roles) ) {
						$tmpUsers[] = $user;
					}
				}
			}
		}
		$users = $tmpUsers;
		return $users;
	}
	
	/**
	 * Change capabilities when the user has selected a view
	 * If the capability isn't in the chosen view, then return "do_not_allow"
	 *
	 * @since   0.1
	 * @return	Array	$caps
	 */
	function change_caps($caps, $cap, $user_id, $args) {
		
		$filterCaps = false;
		if ( isset( $this->viewAs['role'] ) && isset( $this->roles ) ) {
			$filterCaps = $this->roles[$this->viewAs['role']]['capabilities'];
		} else if ( isset( $this->viewAs['caps'] ) ) {
			$filterCaps = $this->viewAs['caps'];
		}
		
		if ( $filterCaps != false ) {
			if ( array_key_exists( $cap, $this->caps ) ) { // make sure this cap is known for the current user
				if ( ! isset( $filterCaps[$cap] ) || ( (int) $filterCaps[$cap] != 1 ) ) {
					$caps[] = 'do_not_allow';
				}
			}
		}
		
		return $caps;
	}
	
	/**
	 * Add admin bar menu's
	 *
	 * @param	object	$admin_bar
	 * 
	 * @since   0.1
	 * @return	void
	 */
	function add_admin_bar_items( $admin_bar ) {
		
		$viewAsIcon = 'dashicons-hidden';
		$topText = __('Default view (Off)', 'view-admin-as');
		
		if ( isset( $this->viewAs['caps'] ) ) {
			$viewAsIcon = 'dashicons-visibility';
			$topText = __('Modified view', 'view-admin-as');
		}
		if ( isset( $this->viewAs['role'] ) ) {
			$viewAsIcon = 'dashicons-visibility';
			$topText = __('Viewing as role', 'view-admin-as') . ': ' . translate_user_role( $this->roles[$this->viewAs['role']]['name'] );
		}
		if ( isset( $this->viewAs['user'] ) ) {
			$viewAsIcon = 'dashicons-visibility';
			$selectedUserRoles = array();
			foreach ( $this->selectedUser->roles as $role ) {
				$selectedUserRoles[] = translate_user_role( $this->roles[$role]['name'] );
			}
			$topText = __('Viewing as user', 'view-admin-as') . ': ' . $this->selectedUser->data->display_name . ' <span class="user-role">(' . implode( ', ', $selectedUserRoles ) . ')</span>';//$this->usernames[$this->viewAs['user']];
		}
		
		// Add menu item
		$admin_bar->add_menu( array(
			'id'		=> 'view-as',
			'parent'	=> 'top-secondary',
			'title'		=> '<span class="ab-label">' . $topText . '</span><span style="float:right !important; margin-right: 0; margin-left: 6px;" class="ab-icon alignright dashicons ' . $viewAsIcon . '"></span>',
			'href'		=> '#',
			'meta'		=> array(
				'title'	=> __('View as', 'view-admin-as'),
			),
		) );
		
		// Add reset button
		$admin_bar->add_menu( array(
			'id'		=> 'reset',
			'parent'	=> 'view-as',
			'title'		=> '<button id="reset-view" class="button button-secondary" name="reset-view">' . __('Default', 'view-admin-as') . '</button>',
			'href'		=> '#',
			'meta'		=> array(
				'title'	=> __('Reset to default', 'view-admin-as'),
			),
		) );
		
		// If there are more than 10 users, group them under their roles
		$groupUserRoles = false;
		$searchUsers = false;
		if ( $this->users && count( $this->users ) > 10 ) { 
			$groupUserRoles = true; 
			$searchUsers = true;
		}
		
		// Add capabilities group
		if ( $this->caps && count( $this->caps ) > 0 ) {
			
			$admin_bar->add_menu( array(
				'id'		=> 'caps',
				'parent'	=> 'view-as',
				'title'		=> __('Capabilities', 'view-admin-as'),
				'href'		=> '#',
				'group'		=> true,
				'meta'		=> array(
					'title'	=> __('Capabilities', 'view-admin-as'),
					'class'	=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_menu( array(
				'id'		=> 'caps-title',
				'parent'	=> 'caps',
				'title'		=> '-- ' . __('Capabilities', 'view-admin-as') . ' --',
				'href'		=> false,
				'group'		=> false,
				'meta'		=> array(
					'class'	=> 'ab-sub-title',
				),
			) );
			$capsQuickselectClass = '';
			if ( isset( $this->viewAs['caps'] ) ) {
				$capsQuickselectClass .= ' current';
			}
			$admin_bar->add_menu( array(
				'id'		=> 'caps-quickselect',
				'parent'	=> 'caps',
				'title'		=> __('Select'),
				'href'		=> false,
				'group'		=> false,
				'meta'		=> array(
					'class'	=> $capsQuickselectClass,
				),
			) );
			
			// Capabilities submenu
				$admin_bar->add_menu( array(
					'id'		=> 'applycaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '<button id="apply-caps-view" class="button button-primary" name="apply-caps-view">' . __('Apply') . '</button>
									<a id="close-caps-popup" class="button vaa-icon button-secondary" name="close-caps-popup"><span class="ab-icon dashicons dashicons-dismiss"></span></a>
									<a id="open-caps-popup" class="button vaa-icon button-secondary" name="open-caps-popup"><span class="ab-icon dashicons dashicons-plus-alt"></span></a>',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'vaa-button-container',
					),
				) );
				$admin_bar->add_menu( array(
					'id'		=> 'filtercaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '<input id="filter-caps" name="vaa-filter" placeholder="' . __('Filter') . '" />',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'ab-vaa-filter filter-caps vaa-column-one-half vaa-column-first',
					),
				) );
				$roleSelectOptions = '';
				foreach ($this->roles as $rKey => $rValue) {
					$roleSelectOptions .= '<option value="' . $rKey . '" data-caps=\'' . json_encode( $rValue['capabilities'] ) . '\'>= ' . translate_user_role( $rValue['name'] ) . '</option>';					
					$roleSelectOptions .= '<option value="reversed-' . $rKey . '" data-reverse="1" data-caps=\'' . json_encode( $rValue['capabilities'] ) . '\'>≠ ' . translate_user_role( $rValue['name'] ) . '</option>';					
				}				
				$admin_bar->add_menu( array(
					'id'		=> 'selectrolecaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '<select id="select-role-caps" name="vaa-selectrole"><option value="default">' . __('Default') . '</option>' . $roleSelectOptions . '</select>',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'ab-vaa-select select-role-caps vaa-column-one-half vaa-column-last',
						'html'	=> '',
					),
				) );
				$admin_bar->add_menu( array(
					'id'		=> 'bulkselectcaps',
					'parent'	=> 'caps-quickselect',
					'title'		=> '' . __('All') . ': &nbsp; 
									<button id="select-all-caps" class="button button-secondary" name="select-all-caps">' . __('Select') . '</button>
									<button id="deselect-all-caps" class="button button-secondary" name="deselect-all-caps">' . __('Deselect') . '</button>',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'vaa-button-container vaa-clear-float',
					),
				) );
				$admin_bar->add_menu( array(
					'id'		=> 'caps-quickselect-options',
					'parent'	=> 'caps-quickselect',
					'title'		=> '',
					'href'		=> false,
					'group'		=> true,
					'meta'		=> array(
						'class' => '',
					),
				) );
				foreach ($this->caps as $capName => $capVal) {
					$class = 'vaa-cap-item';
					if ( isset( $this->viewAs['caps'] ) && $this->viewAs['caps'][$capName] != 1 ) {
						$checked = '';						
					} else {
						$checked = ' checked="checked"';
					}
					$admin_bar->add_menu( array(
						'id'		=> 'cap-' . $capName,
						'parent'	=> 'caps-quickselect-options',
						'title'		=> '<input class="checkbox" value="' . $capName . '" id="vaa_' . $capName . '" name="vaa_' . $capName . '" type="checkbox"' . $checked . '>
										<label for="vaa_' . $capName . '">' . str_replace( '_', ' ', $capName ) . '</label>',
						'href'		=> false,
						'group'		=> false,
						'meta'		=> array(
							'class'	=> $class,
						),
					) );
				}
		}
				
		// Add roles group
		if ( $this->roles && count( $this->roles ) > 0 ) {
			$groupRoles = true;
			/*if ( count( $this->roles ) > 10 ) {
				$rolesGroup = false;
			}*/
			$admin_bar->add_menu( array(
				'id'		=> 'roles',
				'parent'	=> 'view-as',
				'title'		=> __('Roles', 'view-admin-as'),
				'href'		=> '#',
				'group'		=> $groupRoles,
				'meta'		=> array(
					'title'	=> __('Roles', 'view-admin-as'),
					'class'	=> 'ab-sub-secondary',
				),
			) );
			if ($groupRoles == true) {
				$admin_bar->add_menu( array(
					'id'		=> 'roles-title',
					'parent'	=> 'roles',
					'title'		=> '-- ' . __('Roles', 'view-admin-as') . ' --',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class'	=> 'ab-sub-title',
					),
				) );
			}
			
			// Add the roles
			foreach( $this->roles as $rKey => $rValue ) {
				$href = '#';
				$class = 'vaa-role-item';
				$title = translate_user_role( $rValue['name'] );
				if ( $groupUserRoles === true ) {
					if ( isset( $this->viewAs['user'] ) && isset( $this->selectedUser ) && in_array( strtolower( $rValue['name'] ), $this->selectedUser->roles ) ) {
						$class .= ' current-parent';
					}
					$userCount = 0;
					foreach ( $this->users as $user ) {
						if ( in_array( $rKey, $user->roles ) ) {
							$userCount++;
						}
					}
					if ( $userCount >= 0 ) {
						$title = $title . ' <span class="user-count">(' . $userCount . ')</span>';
					}
				}
				if ( isset( $this->viewAs['role'] ) && $this->viewAs['role'] == strtolower( $rValue['name'] ) ) {
					$class .= ' current';
				}
				$admin_bar->add_menu( array(
					'id'		=> 'role-' . $rKey,
					'parent'	=> 'roles',
					'title'		=> $title,
					'href'		=> $href,
					'meta'		=> array(
						'title'	=> __('View as', 'view-admin-as') . ' ' . translate_user_role( $rValue['name'] ),
						'class' => $class,
						'rel'	=> translate_user_role( $rValue['name'] ),
					),
				) );
			}
		}
		
		// Add users group
		if ( $this->users && count( $this->users ) > 0 ) {
			$groupUsers = true;
			//if ( count( $this->users ) > 10 ) {$groupUsers = false;}
			$admin_bar->add_menu( array(
				'id'		=> 'users',
				'parent'	=> 'view-as',
				'title'		=> __('Users', 'view-admin-as'),
				'href'		=>	'#',
				'group'		=> $groupUsers,
				'meta'		=> array(
					'title'	=> __('Users', 'view-admin-as'),
					'class'	=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_menu( array(
				'id'		=> 'users-title',
				'parent'	=> 'users',
				'title'		=> '-- ' . __('Users', 'view-admin-as') . ' --',
				'href'		=> false,
				'group'		=> false,
				'meta'		=> array(
					'class'	=> 'ab-sub-title',
				),
			) );
			if ( $searchUsers == true ) {
				$admin_bar->add_menu( array(
					'id'		=> 'searchuser',
					'parent'	=> 'users',
					'title'		=> '<input id="search" name="vaa-search" placeholder="' . __('Search') . ' (' . strtolower( __('Username') ) . ')" />',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'ab-vaa-search search-users',
						'html'	=> '<ul id="vaa-searchuser-results" class="ab-sub-secondary ab-submenu"></ul>',
					),
				) );
			}
			// Add the users
			$curRole = '';
			foreach( $this->users as $rKey => $rValue ) {
				$href = '#';
				$title = $rValue->data->display_name;
				$class = 'vaa-user-item';
				if ( isset( $this->viewAs['user'] ) && $this->viewAs['user'] == $rValue->data->ID ) {
					$class .= ' current';
				}
				$parent = 'users';
				
				if ($groupUserRoles === true) { // Users grouped under roles
					foreach ( $rValue->roles as $role ) {
						$curRole = $role;
						$parent = 'role-'.$curRole;
						$admin_bar->add_menu( array(
							'id'		=> 'user-' . $rValue->data->ID . '-' . $curRole,
							'parent'	=> $parent,
							'title'		=> $title,
							'href'		=> $href,
							'meta'		=> array(
								'title'	=> __('View as', 'view-admin-as') . ' ' . $rValue->data->display_name,
								'class' => $class,
								'data-switch' => 'user-' . $rValue->data->ID,
							),
						) );
					}
				} else { // Users displayed as normal
					$userRoles = array();
					foreach ( $rValue->roles as $role ) {
						$userRoles[] = translate_user_role( $this->roles[$role]['name'] );
					}
					$title = $title.' &nbsp; <span class="user-role">(' . implode( ', ', $userRoles ) . ')</span>';
					$admin_bar->add_menu( array(
						'id'		=> 'user-' . $rValue->data->ID,
						'parent'	=> $parent,
						'title'		=> $title,
						'href'		=> $href,
						'meta'		=> array(
							'title'	=> __('View as', 'view-admin-as') . ' ' . $rValue->data->display_name,
							'class' => $class,
							'data-switch' => 'user-' . $rValue->data->ID,
						),
					) );
				}
			}
		}
	}
	
	/**
	 * AJAX handler
	 * Gets the AJAX input. If it is valid, then store it in the current user metadata
	 *
	 * Store format: array( VIEW_TYPE => NAME );
	 *
	 * @since   0.1
	 * @return	String
	 */
	function ajax_update_view_as() {
		global $wpdb;
		
		$success = false;
		$allowedKeys = array('reset', 'caps', 'role', 'user');
		
		$view_as = $_POST['view_as'];

		// We only want allowed keys, otherwise it's not added through this plugin.
		foreach ( $view_as as $key => $value ) {
			 if ( ! in_array( $key, $allowedKeys ) ) {
				 unset( $view_as[$key] );
			 }
		}
		
		// Stop selecting the same view! :)
		if ( 
			( isset( $view_as['role'] ) && ( isset( $this->viewAs['role'] ) && $this->viewAs['role'] == $view_as['role'] ) ) || 
			( isset( $view_as['user'] ) && ( isset( $this->viewAs['user'] ) && $this->viewAs['user'] == $view_as['user'] ) ) || 
			( isset( $view_as['reset'] ) && $this->viewAs == false ) 
		) {
			wp_send_json_error( __('This view is already selected!', 'view-admin-as') );
		}
		
		// Update user metadata with selected view
		if ( ( isset( $view_as['role'] ) && array_key_exists( $view_as['role'], $this->roles ) ) ||
			 ( isset( $view_as['user'] ) && array_key_exists( $view_as['user'], $this->userids ) ) 
		) {
			$success = $this->update_view( $view_as );
			
		} else if ( isset( $view_as['caps'] ) ) {
			$newCaps = array();
			$tmpNewCaps = explode( ',', $view_as['caps'] );
			foreach ( $tmpNewCaps as $key => $value ) {
				$cap = explode( ':', $value );
				if ( isset( $cap[1] ) ) {
					$newCaps[ strip_tags( $cap[0] ) ] = (int) $cap[1];
				}
			}
			if ( $this->caps != $newCaps ) {
				foreach ( $this->caps as $key => $value ) {
					$this->caps[ $key ] = $newCaps[ $key ];
				}
				$success = $this->update_view( array( 'caps' => $this->caps ) );
				if ( $success != true ) {
					$dbValue = get_user_meta( $this->curUser->ID, 'vaa-view-admin-as', true );
					if ($dbValue['caps'] == $this->caps) {
						wp_send_json_error( __('This view is already selected!', 'view-admin-as') );
					}
				}
			} else { 
				// The selected caps are equal to the current user default caps
				$this->reset_view();
				if ( isset( $this->viewAs['caps'] ) ) {
					$success = true; // and continue
				} else {
					// The user is in his default view
					wp_send_json_error( __('These are your default capabilities!', 'view-admin-as') );
				}
			}
			
		} else if ( isset( $view_as['reset'] ) ) {
			$success = $this->reset_view();
		}
		
		if ( $success == true ) {
			wp_send_json_success(); // ahw yeah
		} else {
			wp_send_json_error( __('Something went wrong, please try again.', 'view-admin-as') ); // fail
		}
		
		wp_die(); // Just to make sure it's actually dead..
	}
	
	/**
	 * Add nessesary scripts and styles
	 *
	 * @since   0.1
	 * @return	void
	 */
	function add_styles_scripts() {
		if ( is_admin_bar_showing() ) {
			wp_enqueue_style( 'vaa_view_admin_as_style', plugin_dir_url( __FILE__ ) . 'style.css', array(), $this->version );
			wp_enqueue_script( 'vaa_view_admin_as_script', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), $this->version );
			wp_localize_script( 'vaa_view_admin_as_script', 'VAA_View_Admin_As', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'siteurl' => get_site_url(), '__no_users_found' => __('No users found.') ) );
		}
	}

	/**
	 * Add reset link to the access denied page
	 *
	 * @since   1.3
	 * @return	void
	 */
	function die_handler( $default ) {
		if ( $this->viewAs != false ) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$urlcomp = parse_url( $url );
			if ( isset ( $urlcomp['query'] ) ) {
				$url .= '&reset-view';
			} else {
				$url .= '?reset-view';
			}
			echo '<p>View Admin As: <a href="http://' . $url . '">' . __('Did something wrong? Reset the view by clicking me!', 'view-admin-as') . '</a></p>';
		}
		return $default;
	}
	
	/**
	 * Get current view for this session
	 *
	 * @since   1.3.4
	 * @return	Boolean
	 */
	function get_view() {
		$meta = get_user_meta( $this->curUser->ID, $this->userMetaKey, true );
		if ( isset( $meta[ $this->curUserSession ]['view'] ) ) {
			return $meta[ $this->curUserSession ]['view'];
		}
		return false;
	}
	
	/**
	 * Update view for this session
	 *
	 * @param	array	$data
	 *
	 * @since   1.3.4
	 * @return	Boolean
	 */
	function update_view( $data = false ) {
		if ( $data != false ) {
			$user = $this->curUser;
			$meta = get_user_meta( $user->ID, $this->userMetaKey, true );
			if ( ! $meta ) {
				$meta = array();
			}
			$meta[ $this->curUserSession ] = array(
				'view' => $data,
				'expire' => (time() + $this->metaExpiration),
			);
			return update_user_meta( $user->ID, $this->userMetaKey, $meta );
		}
		return false;
	}
	
	/**
	 * Reset view to default
	 * This function is also attached to the wp_login and wp_logout hook
	 *
	 * @param	string	$user_login 	String provided by the wp_login hook (not used)
	 * @param	object	$user   		User object provided by the wp_login hook
	 *
	 * @since   0.1
	 * @return	Boolean
	 */
	function reset_view( $user_login = false, $user = false ) {
		
		// function is not triggered by the wp_login action hook
		if ( $user == false ) {
			$user = $this->curUser;
		}
		
		// Get metadata
		$meta = get_user_meta( $user->ID, $this->userMetaKey, true );
		// Check if this user session has metadata
		if ( $meta && isset( $meta[ $this->curUserSession ] ) ) {
			// Remove metadata from this session
			unset( $meta[ $this->curUserSession ] );
			// Update metadata
			return update_user_meta( $user->ID, $this->userMetaKey, $meta );
		}
		// No meta found, no reset needed
		return true;
	}
	
	/**
	 * Deleta all expired View Admin As metadata for this user
	 * This function is also attached to the wp_login hook
	 *
	 * @since	1.3.4
	 * @return	Boolean
	 */
	function cleanup_metadata( $user_login = false, $user = false ) {
		
		// function is not triggered by the wp_login action hook
		if ( $user == false ) {
			$user = $this->curUser;
		}
		
		$meta = get_user_meta( $user->ID, $this->userMetaKey, true );
		// If meta exists, loop it
		if ( $meta && count( $meta ) >= 1 ) {
			foreach ( $meta as $key => $value ) {
				// Check expiration date: if it doesn't exist or is in the past, remove it
				if ( ! isset( $meta[ $key ]['expire'] ) || $meta[ $key ]['expire'] <= time() ) {
					unset( $meta[ $key ] );
				}
			}
			if ( empty( $meta ) ) {
				$meta = false;
			}
			return update_user_meta( $user->ID, $this->userMetaKey, $meta );
		}
		// No meta found, no cleanup needed
		return true;
	}
	
	/**
	 * Delete all View Admin As metadata for this user
	 *
	 * @param	string	$user_login 	String provided by the wp_login hook (not used)
	 * @param	object	$user   		User object provided by the wp_login hook
	 *
	 * @since   1.3.4
	 * @return	Boolean
	 */
	function reset_metadata( $user_login = false, $user = false ) {
		
		// function is not triggered by the wp_login action hook
		if ( $user == false ) {
			$user = $this->curUser;
		}
		
		// If meta exists, reset it
		if ( get_user_meta( $user->ID, $this->userMetaKey, true ) ) {
			return update_user_meta( $user->ID, $this->userMetaKey, false );
		}
		// No meta found, no reset needed
		return true;
	}
	
	/**
	 * Fix compatibility issues
	 *
	 * @since   0.1
	 * @return	void
	 */
	function plugin_compatibility() {
		
		if ( $this->viewAs !== false ) {
			// WooCommerce
			remove_filter( 'show_admin_bar', 'wc_disable_admin_bar', 10 );
		}
		
		// Pods 2.x (only needed for the role selector)
		if ( isset( $this->viewAs['role'] ) ) {
        	add_filter( 'pods_is_admin', array( $this, 'pods_caps_check' ), 10, 3 );
		}
	}
	
	/**
	 * Fix compatibility issues Pods Framework 2.x
	 *
	 * @param	boolean		$bool 			Boolean provided by the pods_is_admin hook (not used)
	 * @param	array		$cap 			String or Array provided by the pods_is_admin hook
	 * @param	string		$capability   	String provided by the pods_is_admin hook
	 *
	 * @since   1.0.1
	 * @return	boolean
	 */
	function pods_caps_check( $bool, $cap, $capability ) {
		
		// Pods gives arrays most of the time with the to-be-checked capability as last item
		if ( is_array( $cap ) ) {
			foreach ( $cap as $c ) {
				$tmp_cap = $c;
			}
			$cap = $tmp_cap;
		}
		
		$role_caps = $this->roles[ $this->viewAs['role'] ]['capabilities'];
		if ( !array_key_exists( $cap, $role_caps ) || ( $role_caps[$cap] != 1 ) ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.2
	 * @return	void
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'view-admin-as', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
	}
	
} // end class
