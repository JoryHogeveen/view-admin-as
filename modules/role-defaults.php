<?php
/**
 * View Admin As - Role Defaults
 *
 * Role Defaults
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @version 1.4
 */
 
! defined( 'ABSPATH' ) and die( 'You shall not pass!' );

class VAA_Role_Defaults {

	/**
	 * Option key
	 *
	 * @since  1.4
	 * @var    String
	 */
	private $optionKey = 'vaa_role_defaults';

	/**
	 * Option data
	 *
	 * @since  1.4
	 * @var    Array
	 */
	private $optionData = false;
	
	/**
	 * Enable functionalities?
	 *
	 * @since  1.4
	 * @var    Boolean
	 */
	private $enable = false;
	
	/**
	 * Selected view mode
	 * 
	 * Format: array( VIEW_TYPE => NAME )
	 *
	 * @since  0.1
	 * @var    Array
	 */
	private $viewAs = false;
	
	/**
	 * Current user object
	 *
	 * @since  0.1
	 * @var    Object
	 */	
	private $curUser = false;
	
	/**
	 * Array of available roles
	 *
	 * @since  0.1
	 * @var    Array
	 */	
	private $roles;
		
	/**
	 * Array of default meta strings that influence the screen settings
	 * %% stands for a wildcard and can be anything
	 *
	 * @since  1.4
	 * @var    Array
	 */
	private $meta = array( 
		'admin_color', // The admin color
		'rich_editing', // Enable/Disable rich editing
		'metaboxhidden_%%', // Hidden metaboxes 
		'meta-box-order_%%', // Metabox order and locations
		'closedpostboxes_%%', // Hidden post boxes
		'edit_%%_per_page', // Amount of items per page in edit pages (overview)
		'manage%%columnshidden', // Hidden columns in overview pages
		'screen_layout_%%', // Screen layout (num of columns)
	);
	
	/**
	 * A role slug (only used to store role defaults)
	 *
	 * @since  1.4
	 * @var    String
	 */
	private $role = '';
	
	/**
	 * Construct function
	 *
	 * @since   1.4
	 * @return	void
	 */
	function __construct() {
		if (!defined('DOING_AJAX') || !DOING_AJAX) {
			//print_r( get_option( $this->optionKey ) );
		}
		
		// Get the current user
		$this->curUser = wp_get_current_user();
		
		$optionData = $this->optionData = get_option( $this->optionKey );
		if ( isset( $optionData['enable'] ) && $optionData['enable'] == true ) {
			$this->enable = true;
		}
		
		if ( $this->enable ) {
			$this->init();
		}
	}
	
	function init() {
		$optionData = get_option( $this->optionKey );
		
		// Allow users to overwrite the meta keys
		$this->meta = apply_filters( 'view_admin_as_role_defaults_meta', $this->meta );
		
		if ( isset( $optionData['apply_defaults_on_register'] ) && $optionData['apply_defaults_on_register'] == true ) {
			if ( is_multisite() ) {
				add_action( 'add_user_to_blog', array( $this, 'update_user_with_role_defaults_multisite' ), 100, 3 );
			} else {
				// Todo: Testing!
				add_action( 'user_register', array( $this, 'update_user_with_role_defaults' ), 100, 1 );
			}
		}
	}
	
	function is_enabled() {
		return $this->enable;
	}
	
	function set_available_roles( $roles ) {
		$this->roles = $roles;
	}
	
	private function set_enable($bool = false) {
		$optionData = get_option( $this->optionKey );
		if ( ! is_array( $optionData ) ) {
			$optionData = array();
		}
		$optionData['enable'] = $bool;
		$success = update_option( $this->optionKey, $optionData );
		if ( $success ) {
			$this->enable = $bool;
		}
		return $success;
	}
	private function set_apply_defaults_on_register($bool = false) {
		$optionData = get_option( $this->optionKey );
		if ( ! is_array( $optionData ) ) {
			$optionData = array();
		}
		$optionData['apply_defaults_on_register'] = $bool;
		$success = update_option( $this->optionKey, $optionData );
		return $success;
	}
	
	function ajax_handler( $data ) {
		$success = false;
		
		if ( isset( $data['enable'] ) && $data['enable'] == true ) {
			$success = $this->set_enable(true);
		}
		if ( isset( $data['disable'] ) && $data['disable'] == true ) {
			$success = $this->set_enable(false);
		}
		if ( isset( $data['apply_defaults_on_register'] ) && $data['apply_defaults_on_register'] == true ) {
			$success = $this->set_apply_defaults_on_register(true);
		}
		if ( isset( $data['disable_apply_defaults_on_register'] ) && $data['disable_apply_defaults_on_register'] == true ) {
			$success = $this->set_apply_defaults_on_register(false);
		}
		if ( isset( $data['apply_defaults_to_user_by_role'] ) && is_string( $data['apply_defaults_to_user_by_role'] ) ) {
			$success = $this->apply_defaults_to_user_by_role( strip_tags( $data['apply_defaults_to_user_by_role'] ) );
		}
		if ( isset( $data['clear_role_defaults'] ) && is_string( $data['clear_role_defaults'] ) ) {
			$success = $this->clear_role_defaults( strip_tags( $data['clear_role_defaults'] ) );
		}
		
		return $success;
	}
	
	/**
	 * Update user settings with the a role default
	 * When no role is provided this function only checks the first existing user role. If the user has multiple roles, the other roles are ignored.
	 *
	 * @since   1.4
	 * @return	void
	 */
	function update_user_with_role_defaults( $user_id, $role = false, $blog_id = false ) {
		$success = false;
		$user = get_user_by( 'id', $user_id );
		$userBlogs = false;
		if ( $blog_id != false && is_numeric( $blog_id ) ) {
			$optionData = get_blog_option( $blog_id, $this->optionKey );
		} else {
			$optionData = get_option( $this->optionKey );
		}
		if ( $role == false && isset( $user->roles[0] ) ) {
			$role = $user->roles[0];
		}
		if ( $role != false && $optionData != false ) {
			if ( isset( $optionData['roles'][ $role ] ) ) {
				foreach ( $optionData['roles'][ $role ] as $meta_key => $meta_value ) {
					$success = update_user_meta( $user_id, $meta_key, $meta_value );
				}
			}
		}
		if ( $success != false ) { $success = true; }
		return $success;
	}
	function update_user_with_role_defaults_multisite( $user_id, $role, $blog_id ) {
		$userBlogs = get_blogs_of_user( $user_id );
		if ( count($userBlogs) == 1 ) {
			// If the user has access to one blog only it is safe to set defaults since it is most likely a new user.
			$this->update_user_with_role_defaults( $user_id, $role, $blog_id );
		}
	}
	function apply_defaults_to_user_by_role( $role ) {
		$success = false;
		$roles = array();
		if ( is_array( $role ) ) {
			foreach( $role as $r ) {
				if ( array_key_exists( $r, $this->roles ) ) {
					$roles[] = $r;
				}
			}
		} else {
			if ( array_key_exists( $role, $this->roles ) ) {
				$roles[] = $role;
			} else if ( $role == 'all' ) {
				foreach( $this->roles as $r => $val ) {
					$roles[] = $r;
				}
			}
		}
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role ) {
				$users = get_users( array( 'role' => $role ) );
				if ( ! empty ( $users ) ) {
					foreach ( $users as $user ) {
						$success = $this->update_user_with_role_defaults( $user->ID, $role );
					}
				}
			}
		}
		return $success;
	}
	
	/**
	 * Initialize the sync funcionality (store defaults)
	 * Init function/action to load nessesary data and register all used hooks
	 *
	 * IMPORTANT! This function should ONLY be used when a role view is selected!
	 *
	 * @since   1.4
	 * @return	void
	 */
	function init_store_role_defaults( $role = false ) {
		if ( $role && $this->enable ) {
			$this->role = $role;
			add_filter( 'update_user_metadata' , array( $this, 'filter_update_user_metadata' ), 10, 5 );
			add_filter( 'get_user_metadata' , array( $this, 'filter_get_user_metadata' ), 10, 5 );
		}
	}
	
	/**
	 * Check if the meta_key maches one of the predefined metakeys to store as defaults.
	 * If there is a match, store the update to the defaults and cancel the update for the current user.
	 * 
	 * IMPORTANT! This filter should ONLY be used when a role view is selected!
	 *
	 * @since   1.4
	 * @return	mixed
	 */
	function filter_update_user_metadata( $null, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( $this->compare_metakey( $meta_key ) && $object_id == $this->curUser->ID ) {
			$this->update_role_defaults( $meta_key, $meta_value );
			return false; // Do not update current user meta
		}
		return null; // Go on as normal
	}
	function update_role_defaults( $meta_key, $meta_value ) {
		$optionData = get_option( $this->optionKey );
		if ( ! is_array( $optionData ) ) {
			$optionData = array();
		}
		if ( ! isset( $optionData['roles'][ $this->role ] ) ) {
			$optionData['roles'][ $this->role ] = array();
		}
		$optionData['roles'][ $this->role ][ $meta_key ] = $meta_value;
		update_option( $this->optionKey, $optionData );
	}
	
	function clear_role_defaults( $role ) { // option to set $role to "all" or pass an array of multiple roles
		$optionData = get_option( $this->optionKey );
		if ( ! is_array( $role ) ) {
			if ( isset( $optionData['roles'] ) && $role == 'all' ) {
				$optionData['roles'] = array();
			} else {
				$roles = array( $role );
			}
		} else {
			$roles = $role;
		}
		if ( isset( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( isset( $optionData['roles'][ $role ] ) ) {
					$optionData['roles'][ $role ] = array();
				}
			}
		}
		$success = update_option( $this->optionKey, $optionData );
		return $success;
	}
	
	/**
	 * Check if the meta_key maches one of the predefined metakeys in the role defaults
	 * If there is a match and the role default value is set, return this value instead of the current user value.
	 * 
	 * IMPORTANT! This filter should ONLY be used when a role view is selected!
	 *
	 * @since   1.4
	 * @return	mixed
	 */
	function filter_get_user_metadata( $null, $object_id, $meta_key, $single ) {
		if ( $this->compare_metakey( $meta_key ) && $object_id == $this->curUser->ID ) {
			$new_meta = $this->get_role_default_metadata( $meta_key );
			if ( $single && is_array( $new_meta ) ) {
				return array( $new_meta );
			}
			return $new_meta;
		}
		return null; // Go on as normal
	}
	function get_role_default_metadata( $meta_key ) {
		$optionData = get_option( $this->optionKey );
		if ( isset( $optionData['roles'][ $this->role ] ) ) {
			$optionData = $optionData['roles'][ $this->role ];
			if ( isset( $optionData[ $meta_key ] ) ) {
				return $optionData[ $meta_key ];
			}
		}
		return false;
	}
	
	/**
	 * Match the meta key with predefined metakeys
	 *
	 * @since   1.4
	 * @return	Boolean
	 */
	function compare_metakey( $meta_key ) {
		foreach( $this->meta as $key => $meta ) {
			if ( is_numeric( $meta ) || empty( $meta ) || ! is_string( $meta ) ) {
				unset( $this->meta[ $key ] );
				continue;
			} else {
				$metaParts = explode( '%%', $meta );
				
				$compareStart = false;
				if ( isset( $metaParts[0] ) && $metaParts[0] != '' ) {
					$compareStart = $this->startsWith( $meta_key, $metaParts[0] );
				} else {
					$compareStart = true;
				}
				
				$compareEnd = false;
				if ( isset( $metaParts[1] ) && $metaParts[1] != '' ) {
					$compareEnd = $this->endsWith( $meta_key, $metaParts[1] );
				} else {
					$compareEnd = true;
				}
				
				if ($compareStart == true && $compareEnd == true) {
					return true;
				}
			}
		}
		return false;
	}
	function startsWith($haystack, $needle) {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
	}
	function endsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
	}
	
	/**
	 * Add admin bar menu's
	 *
	 * @param	object	$admin_bar
	 * 
	 * @since   0.1
	 * @return	void
	 */
	function add_admin_bar_items( $admin_bar, $type ) {
		$optionData = get_option( $this->optionKey );
		if ( $type == 'pre' ) {
			// do pre default stuff
		}
		if ( $type == 'post' ) {
			if ( $this->enable ) {
				
				$roleSelectOptions = '';
				foreach ($this->roles as $rKey => $rValue) {
					$roleSelectOptions .= '<option value="' . $rKey . '">' . translate_user_role( $rValue['name'] ) . '</option>';					
				}				
				
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-enable',
					'parent'	=> 'role-defaults',
					'title'		=> '<input class="checkbox" value="1" id="vaa_role_defaults_enable" name="vaa_role_defaults_enable" type="checkbox" checked="checked">
									<label for="vaa_role_defaults_enable">' . __('Enable role defaults', 'view-admin-as') . '</label>',
					'href'		=> false,
					'meta'		=> array(
						'class'	=> 'ab-italic',
					),
				) );
				
				$checked = '';
				if ( isset( $optionData['apply_defaults_on_register'] ) && $optionData['apply_defaults_on_register'] == true ) {
					$checked = ' checked="checked"';
				}
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-register-enable',
					'parent'	=> 'role-defaults',
					'title'		=> '<input class="checkbox" value="1" id="vaa_role_defaults_register_enable" name="vaa_role_defaults_register_enable" type="checkbox"'.$checked.'>
									<label for="vaa_role_defaults_register_enable">' . __('Apply defaults to new users', 'view-admin-as') . '</label>',
					'href'		=> false,
					'meta'		=> array(
						'class'	=> 'ab-italic',
					),
				) );
				
				/* Bulk actions */
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-bulk',
					'parent'	=> 'role-defaults',
					'href'		=> false,
					'group'		=> true,
					'meta'		=> array(
						'class'	=> 'ab-sub-secondary',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-bulk-roles',
					'parent'	=> 'role-defaults-bulk',
					'title'		=> '' . __('Apply defaults for users by role', 'view-admin-as') . '',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class'	=> 'ab-bold',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-bulk-roles-select',
					'parent'	=> 'role-defaults-bulk',
					'title'		=> '<select id="role-defaults-bulk-roles-select" name="role-defaults-bulk-roles-select"><option value="all">' . __('All roles') . '</option>' . $roleSelectOptions . '</select>',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
						'html'	=> '',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-bulk-roles-apply',
					'parent'	=> 'role-defaults-bulk',
					'title'		=> '<button id="role-defaults-bulk-roles-apply" class="button button-primary" name="role-defaults-bulk-roles-apply">' . __('Apply') . '</button>',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'vaa-button-container',
						'html'	=> '',
					),
				) );
				
				/* Clear actions */
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-clear',
					'parent'	=> 'role-defaults',
					'href'		=> false,
					'group'		=> true,
					'meta'		=> array(
						'class'	=> 'ab-sub-secondary vaa-sub-transparent',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-clear-roles',
					'parent'	=> 'role-defaults-clear',
					'title'		=> '' . __('Clear defaults for role', 'view-admin-as') . '',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class'	=> 'ab-bold',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-clear-roles-select',
					'parent'	=> 'role-defaults-clear',
					'title'		=> '<select id="role-defaults-clear-roles-select" name="role-defaults-clear-roles-select"><option value="all">' . __('All roles') . '</option>' . $roleSelectOptions . '</select>',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
						'html'	=> '',
					),
				) );
				$admin_bar->add_node( array(
					'id'		=> 'role-defaults-clear-roles-apply',
					'parent'	=> 'role-defaults-clear',
					'title'		=> '<button id="role-defaults-clear-roles-apply" class="button button-secondary" name="role-defaults-clear-roles-apply">' . __('Apply') . '</button>',
					'href'		=> false,
					'group'		=> false,
					'meta'		=> array(
						'class' => 'vaa-button-container',
						'html'	=> '',
					),
				) );
			}
		}
	}
	
} // end class
