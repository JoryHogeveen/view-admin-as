<?php
/**
 * View Admin As - Role Defaults Module
 *
 * Set default screen settings for roles and apply them on users through various bulk actions.
 * 
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @version 1.5.2.1
 */
 
! defined( 'ABSPATH' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As_Role_Defaults extends VAA_View_Admin_As_Class_Base 
{
	/**
	 * The single instance of the class.
	 *
	 * @since	1.5
	 * @var		Class_Name
	 */
	private static $_instance = null;

	/**
	 * Option key
	 *
	 * @since  1.4
	 * @var    string
	 */
	protected $optionKey = 'vaa_role_defaults';

	/**
	 * Array of default meta strings that influence the screen settings
	 * %% stands for a wildcard and can be anything
	 * 
	 * @since  1.4
	 * @since  1.5.2	Set both values and keys to fix problem with unsetting a key through the filter
	 * @var    array
	 */
	private $meta = array( 
		'admin_color'            => 'admin_color',            // The admin color
		'rich_editing'           => 'rich_editing',           // Enable/Disable rich editing
		'metaboxhidden_%%'       => 'metaboxhidden_%%',       // Hidden metaboxes
		'meta-box-order_%%'      => 'meta-box-order_%%',      // Metabox order and locations
		'closedpostboxes_%%'     => 'closedpostboxes_%%',     // Hidden post boxes
		'edit_%%_per_page'       => 'edit_%%_per_page',       // Amount of items per page in edit pages (overview)
		'manage%%columnshidden'  => 'manage%%columnshidden',  // Hidden columns in overview pages
		'screen_layout_%%'       => 'screen_layout_%%',       // Screen layout (num of columns)
	);
	
	/**
	 * Array of forbidden meta strings
	 * %% gets replaced with the table prefix and added to this array on class construction
	 *
	 * @since  1.5.2
	 * @var    array
	 */
	private $meta_forbidden = array( 
		'vaa-view-admin-as',  // Meta value for this plugin
		'%%capabilities',     // The user's capabilities
		'%%user_level',       // The user's user level
		'session_tokens',     // The user's session tokens
		'nickname',           // The user's nickname
		'first_name',         // The user's first name
		'last_name',          // The user's last name
		'description',        // The user's description
	);

	/**
	 * Construct function
	 * Private to make sure it isn't declared elsewhere
	 *
	 * @since   1.4
	 * @access 	private
	 * @return	void
	 */
	private function __construct() {

		// Init VAA
		$this->load_vaa();
		add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );

		// Load data
		$this->set_optionData( get_option( $this->get_optionKey() ) );

		if ( true == $this->get_optionData('enable') && ( is_super_admin( $this->get_curUser()->ID ) || current_user_can('view_admin_as_role_defaults') ) ) {
			$this->enable = true;
		}
		
		// Only allow settings for admin users
		if ( is_super_admin( $this->get_curUser()->ID ) ) { // $this->is_vaa_enabled() 
			// Add adminbar menu items in settings section
			add_action( 'vaa_admin_bar_settings_after', array( $this, 'admin_bar_menu_settings' ) );
		}

		if ( $this->is_enabled() ) {
			$this->init();
		}
	}
	
	/**
	 * Init function
	 * Also handles functionality that could allways be enabled
	 *
	 * @since   1.4
	 * @access 	private
	 * @return	void
	 */
	private function init() {

		/**
		 * Replace %% with the current table prefix and add it to the array of forbidden meta keys
		 * @since 1.5.2
		 */
		global $wpdb;
		foreach ( $this->meta_forbidden as $key => $meta_key ) {
			if ( strpos($meta_key, '%%') !== false ) {
				$this->meta_forbidden[] = str_replace( '%%', (string) $wpdb->prefix, $meta_key );
			}
		}

		// Allow users to overwrite the meta keys
		$this->set_meta( apply_filters( 'view_admin_as_role_defaults_meta', $this->get_meta() ) );

		// Setting: Automatically apply defaults to new users
		if ( true == $this->get_optionData('apply_defaults_on_register') ) {
			if ( is_multisite() ) {
				add_action( 'add_user_to_blog', array( $this, 'update_user_with_role_defaults_multisite_register' ), 100, 3 );
			} else {
				add_action( 'user_register', array( $this, 'update_user_with_role_defaults' ), 100, 1 );
			}
		}

		// Setting: Hide the screen options for all users who can't access this plugin
		if ( true == $this->get_optionData('disable_user_screen_options') && ! $this->is_vaa_enabled() ) {
			add_filter( 'screen_options_show_screen', '__return_false', 99 );
		}

		if ( $this->is_vaa_enabled() ) {
			// Add adminbar menu items in role section
			add_action( 'vaa_admin_bar_settings_after', array( $this, 'admin_bar_menu' ) );
		}
	}
	
	/**
	 * init function to store data from the main class and enable functionality based on the current view
	 *
	 * @since   1.4
	 * @access 	public
	 * @param 	object
	 * @return	void
	 */
	public function vaa_init() {
		if ( $this->get_viewAs('role') ) {
			// Enable storage of role default settings
			$this->init_store_role_defaults();
		}
	}

	/**
	 * Get the metadata for meta compare
	 *
	 * @since   1.5
	 * @access 	private
	 * @return	array 	$this->meta
	 */
	private function get_meta() { return $this->meta; }

	/**
	 * Set the metadata for meta compare
	 * Used to enforce only 1 level depth array of strings
	 *
	 * @since   1.5
	 * @access 	private
	 * @param	array 	$var
	 * @return	void
	 */
	private function set_meta( $var ) {
		if ( is_array( $var ) ) {
			$this->meta = $this->validate_meta( $var );
		}
	}

	/**
	 * Validates meta keys in case forbitten or invalid meta keys are added
	 *
	 * @since   1.5.2
	 * @access 	private
	 * @param	array 	$metas
	 * @return	array
	 */
	private function validate_meta( $metas ) {
		if ( is_array( $metas ) ) {
			foreach( $metas as $key => $meta_key ) {
				// Remove forbidden or invalid meta keys
				if (   in_array( $meta_key, $this->meta_forbidden ) 
					|| strpos($meta_key, ' ') !== false 
					|| ! is_string( $meta_key )
				) {
					unset( $metas[ $key ] );
				}
			}
			return $metas;
		}
		return array();
	}
	
	/**
	 * Ajax handler, called from main plugin
	 *
	 * @since   1.4
	 * @access 	public
	 * @param	array
	 * @return	array|string|bool
	 */
	public function ajax_handler( $data ) {
		
		if (   ! defined('VAA_DOING_AJAX') 
			|| ! VAA_DOING_AJAX 
			|| ! $this->is_vaa_enabled() 
		) {
			return false;
		}
		
		$success = true;

		if ( is_super_admin( $this->get_curUser()->ID ) ) {

			if ( isset( $data['enable'] ) ) {
				if ( true == $data['enable'] ) {
					$success = $this->set_enable( true );
				} else {
					$success = $this->set_enable( false );
				}
			}

		}

		if ( isset( $data['apply_defaults_on_register'] ) ) {
			if ( true == $data['apply_defaults_on_register'] ) {
				$success = $this->update_optionData( true, 'apply_defaults_on_register', true );
			} else {
				$success = $this->update_optionData( false, 'apply_defaults_on_register', true );
			}
		}
		if ( isset( $data['disable_user_screen_options'] ) ) {
			if ( true == $data['disable_user_screen_options'] ) {
				$success = $this->update_optionData( true, 'disable_user_screen_options', true );
			} else {
				$success = $this->update_optionData( false, 'disable_user_screen_options', true );
			}
		}
		if ( isset( $data['apply_defaults_to_users'] ) && is_array( $data['apply_defaults_to_users'] ) ) {
			foreach ( $data['apply_defaults_to_users'] as $userData ) {
				$userData = explode( '|', $userData );
				if ( is_numeric( $userData[0] ) && isset( $userData[1] ) && is_string( $userData[1] ) ) {
					$success = $this->update_user_with_role_defaults( intval( $userData[0] ), $userData[1] );
				}
			}
		}
		if ( isset( $data['apply_defaults_to_users_by_role'] ) && is_string( $data['apply_defaults_to_users_by_role'] ) ) {
			$success = $this->apply_defaults_to_users_by_role( strip_tags( $data['apply_defaults_to_users_by_role'] ) );
		}
		if ( isset( $data['clear_role_defaults'] ) && is_string( $data['clear_role_defaults'] ) ) {
			$success = $this->clear_role_defaults( strip_tags( $data['clear_role_defaults'] ) );
		}

		if ( isset( $data['export_role_defaults'] ) && is_string( $data['export_role_defaults'] ) ) {
			$content = $this->export_role_defaults( strip_tags( $data['export_role_defaults'] ) );
			if ( is_array( $content) ) {
				wp_send_json_success( array( 
					'type' => 'textarea', 
					'content' => array( 
						'text' => esc_attr__('Copy code', 'view-admin-as') . ': ', 
						'textareacontent' => json_encode( $content ),
					),
				) );
			} else {
				wp_send_json_error( array( 'type' => 'error', 'content' => $content ) );
			}
			wp_die();
		}
		if ( isset( $data['import_role_defaults'] ) && is_string( $data['import_role_defaults'] ) ) {
			// $content format: array( 'text' => **text**, 'errors' => **error array** )
			$content = $this->import_role_defaults( json_decode( stripslashes( $data['import_role_defaults'] ), true ) );
			if ( true === $content ) {
				wp_send_json_success( array( 'type' => 'success', 'content' => $content ) );
			} else {
				wp_send_json_success( array( 'type' => 'errorlist', 'content' => $content ) );
			}
			wp_die();
		}

		return $success;
	}
	
	/**
	 * Update user settings with the a role default
	 * When no role is provided this function only checks the first existing user role. If the user has multiple roles, the other roles are ignored.
	 *
	 * @since   1.4
	 * @access 	public
	 * @param 	int		$user_id
	 * @param	string	$role
	 * @param	int		$blog_id
	 * @return	bool
	 */
	public function update_user_with_role_defaults( $user_id, $role = false, $blog_id = false ) {
		$success = true;
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			$userBlogs = false;
			if ( false != $blog_id && is_numeric( $blog_id ) ) {
				$optionData = get_blog_option( $blog_id, $this->get_optionKey() );
			} else {
				$optionData = get_option( $this->get_optionKey() );
			}
			if ( false == $role && isset( $user->roles[0] ) ) {
				$role = $user->roles[0];
			}
			if ( false != $role && false != $optionData ) {
				if ( isset( $optionData['roles'][ $role ] ) ) {
					foreach ( $optionData['roles'][ $role ] as $meta_key => $meta_value ) {
						update_user_meta( $user_id, $meta_key, $meta_value ); 
						// Do not return update_user_meta results since it's highly possible to be false (values are often the same)
					}
				}
			}
		}
		return $success;
	}
	
	/**
	 * In case of a multisite register, check if the user has multiple blogs. 
	 * If true, it is an existing user and it will not get the role defaults.
	 * If false, it is most likely a new user and it will get the role defaults.
	 *
	 * Used for hook "add_user_to_blog"
	 *
	 * @since   1.4
	 * @access 	public
	 * @param 	int		$user_id
	 * @param	string	$role
	 * @param	int		$blog_id
	 * @return	bool
	 */
	public function update_user_with_role_defaults_multisite_register( $user_id, $role, $blog_id ) {
		$user_blogs = get_blogs_of_user( $user_id );
		if ( 1 === count( $user_blogs ) ) {
			// If the user has access to one blog only it is safe to set defaults since it is most likely a new user.
			return $this->update_user_with_role_defaults( $user_id, $role, $blog_id );
		}
	}
	
	/**
	 * Apply default settings to all users of a role
	 *
	 * @since   1.4
	 * @access 	private
	 * @param	string	$role
	 * @return	bool
	 */
	private function apply_defaults_to_users_by_role( $role ) {
		$success = true;
		$roles = array();
		if ( is_array( $role ) ) {
			foreach( $role as $role_name ) {
				if ( array_key_exists( $role_name, $this->get_roles() ) ) {
					$roles[] = $role_name;
				}
			}
		} else {
			if ( array_key_exists( $role, $this->get_roles() ) ) {
				$roles[] = $role;
			} elseif ( $role == 'all' ) {
				foreach( $this->get_roles() as $role_name => $val ) {
					$roles[] = $role_name;
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
	 * IMPORTANT! This function should ONLY be used when a role view is selected!
	 *
	 * @since   1.4
	 * @access 	private
	 * @see 	vaa_init()
	 * @return	void
	 */
	private function init_store_role_defaults() {
		if ( $this->get_viewAs('role') && $this->is_enabled() ) {
			add_filter( 'get_user_metadata' , array( $this, 'filter_get_user_metadata' ), 10, 5 );
			add_filter( 'update_user_metadata' , array( $this, 'filter_update_user_metadata' ), 10, 5 );
		}
	}
	
	/**
	 * Check if the meta_key maches one of the predefined metakeys in the role defaults
	 * If there is a match and the role default value is set, return this value instead of the current user value.
	 * IMPORTANT! This filter should ONLY be used when a role view is selected!
	 * 
	 * Used by hook: get_user_metadata
	 *
	 * @since   1.4
	 * @access 	public
	 * @see 	init_store_role_defaults()
	 * @param	null	$null
	 * @param	int		$object_id
	 * @param	string	$meta_key
	 * @param	bool 	$single
	 * @return	mixed
	 */
	public function filter_get_user_metadata( $null, $object_id, $meta_key, $single ) {
		if ( true === $this->compare_metakey( $meta_key ) && $object_id == $this->get_curUser()->ID ) {
			$new_meta = $this->get_role_defaults( $this->get_viewAs('role'), $meta_key );
			if ( $single && is_array( $new_meta ) ) {
				return array( $new_meta );
			}
			return $new_meta;
		}
		return null; // Go on as normal
	}
	
	/**
	 * Check if the meta_key maches one of the predefined metakeys to store as defaults.
	 * If there is a match, store the update to the defaults and cancel the update for the current user.
	 * IMPORTANT! This filter should ONLY be used when a role view is selected!
	 * 
	 * Used by hook: update_user_metadata
	 *
	 * @since   1.4
	 * @access 	public
	 * @see 	init_store_role_defaults()
	 * @param	null	$null
	 * @param	int		$object_id
	 * @param	string	$meta_key
	 * @param	string	$meta_value
	 * @param	string	$prev_value
	 * @return	mixed
	 */
	public function filter_update_user_metadata( $null, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( true === $this->compare_metakey( $meta_key ) && $object_id == $this->get_curUser()->ID ) {
			$this->update_role_defaults( $this->get_viewAs('role'), $meta_key, $meta_value );
			return false; // Do not update current user meta
		}
		return null; // Go on as normal
	}
	
	/**
	 * Get defaults of a role
	 *
	 * @since   1.4
	 * @access 	private
	 * @param	string	$role
	 * @param	string	$meta_key
	 * @return	mixed
	 */
	private function get_role_defaults( $role, $meta_key ) {
		$role_defaults = $this->get_optionData( 'roles' );
		if ( isset( $role_defaults[ $role ][ $meta_key ] ) ) {
			return $role_defaults[ $role ][ $meta_key ];
		}
		return false;
	}
	
	/**
	 * Update a role with new defaults
	 *
	 * @since   1.4
	 * @access 	private
	 * @param	string	$role
	 * @param	string	$meta_key
	 * @param	string	$meta_value
	 * @return	void
	 */
	private function update_role_defaults( $role, $meta_key, $meta_value ) {
		$role_defaults = $this->get_optionData( 'roles' );
		if ( ! isset( $role_defaults[ $role ] ) ) {
			$role_defaults[ $role ] = array();
		}
		$role_defaults[ $role ][ $meta_key ] = $meta_value;
		return $this->update_optionData( $role_defaults, 'roles', true );
	}
	
	/**
	 * Remove defaults of a role
	 *
	 * @since   1.4
	 * @access 	private
	 * @param	string	$role
	 * @return	void
	 */
	private function clear_role_defaults( $role ) { // option to set $role to "all" or pass an array of multiple roles
		$role_defaults = $this->get_optionData( 'roles' );
		if ( ! is_array( $role ) ) {
			if ( isset( $role_defaults ) && $role == 'all' ) {
				$role_defaults = array();
			} else {
				$roles = array( $role );
			}
		} else {
			$roles = $role;
		}
		if ( isset( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( isset( $role_defaults[ $role ] ) ) {
					$role_defaults[ $role ] = array();
				}
			}
		}
		return $this->update_optionData( $role_defaults, 'roles' );
	}

	/**
	 * Export role defaults
	 *
	 * @since   1.5
	 * @access 	private
	 * @param	string	$role
	 * @return	mixed
	 */
	private function export_role_defaults( $role = 'all' ) {
		$role_defaults = $this->get_optionData( 'roles' );
		if ( 'all' != $role && isset( $role_defaults[ $role ] ) ) {
			$data = $role_defaults[ $role ];
			$data = array( $role => $data );
		} elseif ( 'all' == $role && isset( $role_defaults ) && ! empty( $role_defaults ) ) {
			$data = $role_defaults;
		}  else {
			$data = esc_attr__('No valid data found', 'view-admin-as');
		}
		return $data;
	}
	
	/**
	 * Import role defaults
	 *
	 * @since   1.5
	 * @access 	private
	 * @param	array	$data
	 * @return	mixed
	 */
	private function import_role_defaults( $data ) {
		$new_defaults = array();
		$error_list = array();
		if ( ! isset( $data ) || ! is_array( $data ) || empty( $data ) ) {
			return array( 'text' => esc_attr__('No valid data found', 'view-admin-as') );
		}
		foreach ( $data as $role => $role_data ) {
			// Make sure the role exists
			if ( array_key_exists( $role, $this->get_roles() ) ) {
				// Add the role to the new defaults
				$new_defaults[ $role ] = array();
				foreach ( $role_data as $data_key => $data_value ) {
					// Make sure the import data are valid meta keys
					if ( true === $this->compare_metakey( $data_key ) ) {
						// Add the key and data
						$new_defaults[ $role ][ $data_key ] = $data_value;
					} else {
						$error_list[] = esc_attr__('Key not allowed', 'view-admin-as') . ': ' . $data_key;
					}
				}
			} else {
				$error_list[] = esc_attr__('Role not found', 'view-admin-as') . ': ' . $role;
			}
		}
		if ( ! empty( $new_defaults ) ) {
			$role_defaults = $this->get_optionData( 'roles' );
			foreach ( $new_defaults as $role => $role_data ) {
				// Overwrite role defaults for each supplied role
				$role_defaults[ $role ] = $role_data;
			}
			$this->update_optionData( $role_defaults, 'roles', true );
			if ( ! empty( $error_list ) ) {
				// Close enough!
				return array( 'text' => esc_attr__('Data imported but there were some errors', 'view-admin-as') . ':', 'errors' => $error_list);
			}
			return true; // Yay!
		}
		// Nope..
		return array( 'text' => esc_attr__('No valid data found', 'view-admin-as') . ':', 'errors' => $error_list );
	}
	
	/**
	 * Match the meta key with predefined metakeys
	 * %% stands for a wildcard. This function only supports one wildcard!
	 *
	 * @since   1.4
	 * @access 	private
	 * @param	string	$meta_key_compare
	 * @return	bool
	 */
	private function compare_metakey( $meta_key_compare ) {
		$meta_keys = $this->get_meta();
		if ( is_array( $meta_keys ) ) {
			foreach( $meta_keys as $key => $meta_key ) {
				if ( is_numeric( $meta_key ) || empty( $meta_key ) || ! is_string( $meta_key ) ) {
					unset( $this->meta_key[ $key ] );
					continue;
				} else {
					$meta_key_parts = explode( '%%', $meta_key );
					
					$compare_start = false;
					if ( ! empty( $meta_key_parts[0] ) ) {
						$compare_start = $this->startsWith( $meta_key_compare, $meta_key_parts[0] );
					} else {
						$compare_start = true;
					}
					
					$compare_end = false;
					if ( ! empty( $meta_key_parts[1] ) ) {
						$compare_end = $this->endsWith( $meta_key_compare, $meta_key_parts[1] );
					} else {
						$compare_end = true;
					}
					
					if ( true == $compare_start && true == $compare_end ) {
						return true;
					}
				}
			}
		}
		return false;
	}
	private function startsWith( $haystack, $needle ) {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos( $haystack, $needle, -strlen( $haystack ) ) !== false;
	}
	private function endsWith( $haystack, $needle ) {
		// search forward starting from end minus needle length characters
		return $needle === "" || ( ( $temp = strlen( $haystack ) - strlen( $needle ) ) >= 0 && strpos( $haystack, $needle, $temp ) !== false);
	}
	
	/**
	 * Add admin bar setting items
	 * 
	 * Used by hook: vaa_admin_bar_settings_after
	 *
	 * @since   1.5
	 * @access 	public
	 * @param	object	$admin_bar
	 * @return	void
	 */
	public function admin_bar_menu_settings( $admin_bar ) {

		$admin_bar->add_group( array(
			'id' => 'settings-role-defaults',
			'parent' => 'settings',
			'meta'		=> array(
				'class'		=> 'ab-sub-secondary',
			),
		) );

		$admin_bar->add_node( array(
			'id'		=> 'settings-role-defaults-enable',
			'parent'	=> 'settings-role-defaults',
			'title'		=> '<input class="checkbox" value="1" id="vaa_role_defaults_enable" name="vaa_role_defaults_enable" type="checkbox" ' . checked( $this->get_optionData( 'enable' ), true, false ) . '>
							<label for="vaa_role_defaults_enable">' . __('Enable role defaults', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Set default screen settings for roles and apply them on users through various bulk and automatic actions', 'view-admin-as') . '</p>',
			'href'		=> false,
			'meta'		=> array(
				'class'		=> 'auto-height',
			),
		) );

	}

	/**
	 * Add admin bar menu's
	 * 
	 * @since 	1.5.2
	 * Used by hook: vaa_admin_bar_settings_after
	 * Previous hook: vaa_admin_bar_roles_before
	 *
	 * @since   1.4
	 * @access 	public
	 * @param	object	$admin_bar
	 * @return	void
	 */
	public function admin_bar_menu( $admin_bar ) {
		
		$admin_bar->add_node( array(
			'id'		=> 'role-defaults',
			'parent'	=> 'view-as',
			'title'		=> __('Role defaults', 'view-admin-as'),
			'href'		=> false,
			'meta'		=> array(
				'class'		=> '',
			),
		) );

		$role_select_options = '';
		foreach ( $this->get_roles() as $role_key => $role ) {
			$role_select_options .= '<option value="' . esc_attr( $role_key ) . '">' . translate_user_role( $role->name ) . '</option>';					
		}
		
		$admin_bar->add_node( array(
			'id'		=> 'role-defaults-setting-register-enable',
			'parent'	=> 'role-defaults',
			'title'		=> '<input class="checkbox" value="1" id="vaa_role_defaults_register_enable" name="vaa_role_defaults_register_enable" type="checkbox" ' . checked( $this->get_optionData( 'apply_defaults_on_register' ), true, false ) . '>
							<label for="vaa_role_defaults_register_enable">' . __('Automatically apply defaults to new users', 'view-admin-as') . '</label>',
			'href'		=> false,
			'meta'		=> array(
				'class'		=> 'auto-height',
			),
		) );
		$admin_bar->add_node( array(
			'id'		=> 'role-defaults-setting-disable-user-screen-options',
			'parent'	=> 'role-defaults',
			'title'		=> '<input class="checkbox" value="1" id="vaa_role_defaults_disable_user_screen_options" name="vaa_role_defaults_disable_user_screen_options" type="checkbox" ' . checked( $this->get_optionData( 'disable_user_screen_options' ), true, false ) . '>
							<label for="vaa_role_defaults_disable_user_screen_options">' . __('Disable screen options', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __("Hide the screen options for all users who can't access this plugin", 'view-admin-as') . '</p>',
			'href'		=> false,
			'meta'		=> array(
				'class'		=> 'auto-height',
			),
		) );
		
		/**
		 * Bulk actions 
		 */
		
		if ( $this->get_users() ) {
			// Users select
			$admin_bar->add_group( array(
				'id'		=> 'role-defaults-bulk-users',
				'parent'	=> 'role-defaults',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-bulk-users-title',
				'parent'	=> 'role-defaults-bulk-users',
				'title'		=> __('Apply defaults to users', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-bold ab-vaa-toggle',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-bulk-users-filter',
				'parent'	=> 'role-defaults-bulk-users',
				'title'		=> '<input id="role-defaults-bulk-users-filter" name="vaa-filter" placeholder="' . esc_attr__('Filter', 'view-admin-as') . ' (' . strtolower( __('Username') ) . ')" />',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'ab-vaa-filter',
				),
			) );
			$bulk_users_select_content = '';
			foreach ( $this->get_users() as $user ) {
				foreach ( $user->roles as $role ) {
					// TODO: (PHP 5.4+) Use getter get_roles( $role )['name']
					if ( $role_data = $this->get_roles( $role ) ) {
						$role_name = translate_user_role( $role_data->name );
						$bulk_users_select_content .= 
							'<div class="ab-item vaa-item">
								<input class="checkbox" value="' . $user->ID.'|'.$role . '" id="role-defaults-bulk-users-select-' . $user->ID . '" name="role-defaults-bulk-users-select[]" type="checkbox">
								<label for="role-defaults-bulk-users-select-' . $user->ID . '"><span class="user-name">' . $user->display_name . '</span> &nbsp; <span class="user-role">(' . $role_name . ')</span></label>
							</div>';
					}
				}
			}
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-bulk-users-select',
				'parent'	=> 'role-defaults-bulk-users',
				'title'		=> $bulk_users_select_content,
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'ab-vaa-multipleselect max-height',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-bulk-users-apply',
				'parent'	=> 'role-defaults-bulk-users',
				'title'		=> '<button id="role-defaults-bulk-users-apply" class="button button-primary" name="role-defaults-bulk-users-apply">' . __('Apply', 'view-admin-as') . '</button>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'vaa-button-container',
					'html'		=> '',
				),
			) );
		}
		
		if ( $this->get_users() && $this->get_roles() ) {
			// Roles select
			$admin_bar->add_group( array(
				'id'		=> 'role-defaults-bulk-roles',
				'parent'	=> 'role-defaults',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-bulk-roles-title',
				'parent'	=> 'role-defaults-bulk-roles',
				'title'		=> __('Apply defaults to users by role', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-bold ab-vaa-toggle',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-bulk-roles-select',
				'parent'	=> 'role-defaults-bulk-roles',
				'title'		=> '<select id="role-defaults-bulk-roles-select" name="role-defaults-bulk-roles-select"><option value=""> --- </option><option value="all">' . __('All roles', 'view-admin-as') . '</option>' 
								. $role_select_options . '</select>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
					'html'		=> '',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-bulk-roles-apply',
				'parent'	=> 'role-defaults-bulk-roles',
				'title'		=> '<button id="role-defaults-bulk-roles-apply" class="button button-primary" name="role-defaults-bulk-roles-apply">' . __('Apply', 'view-admin-as') . '</button>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'vaa-button-container',
					'html'		=> '',
				),
			) );
		}
		
		if ( $this->get_roles() ) {

			/* Export actions */
			$admin_bar->add_group( array(
				'id'		=> 'role-defaults-export',
				'parent'	=> 'role-defaults',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-export-roles',
				'parent'	=> 'role-defaults-export',
				'title'		=> __('Export defaults for role', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-bold ab-vaa-toggle',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-export-roles-select',
				'parent'	=> 'role-defaults-export',
				'title'		=> '<select id="role-defaults-export-roles-select" name="role-defaults-export-roles-select"><option value="all">' . __('All roles', 'view-admin-as') . '</option>' 
								. $role_select_options . '</select>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
					'html'		=> '',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-export-roles-export',
				'parent'	=> 'role-defaults-export',
				'title'		=> '<button id="role-defaults-export-roles-export" class="button button-secondary" name="role-defaults-export-roles-export">' . __('Export', 'view-admin-as') . '</button>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'vaa-button-container',
					'html'		=> '',
				),
			) );

			/* Import actions */
			$admin_bar->add_group( array(
				'id'		=> 'role-defaults-import',
				'parent'	=> 'role-defaults',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-import-roles',
				'parent'	=> 'role-defaults-import',
				'title'		=> __('Import defaults for role', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-bold ab-vaa-toggle',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-import-roles-input',
				'parent'	=> 'role-defaults-import',
				'title'		=> '<textarea id="role-defaults-import-roles-input" name="role-defaults-import-roles-input" placeholder="' . esc_attr__('Paste code here', 'view-admin-as') . '"></textarea>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'ab-vaa-textarea input-role', // vaa-column-one-half vaa-column-last
					'html'		=> '',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-import-roles-import',
				'parent'	=> 'role-defaults-import',
				'title'		=> '<button id="role-defaults-import-roles-import" class="button button-secondary" name="role-defaults-import-roles-import">' . __('Import', 'view-admin-as') . '</button>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'vaa-button-container',
					'html'		=> '',
				),
			) );

			/* Clear actions */
			$admin_bar->add_group( array(
				'id'		=> 'role-defaults-clear',
				'parent'	=> 'role-defaults',
				'meta'		=> array(
					'class'		=> 'ab-sub-secondary vaa-sub-transparent',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-clear-roles',
				'parent'	=> 'role-defaults-clear',
				'title'		=> __('Remove defaults for role', 'view-admin-as'),
				'href'		=> false,
				'meta'		=> array(
					'class'		=> 'ab-bold ab-vaa-toggle',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-clear-roles-select',
				'parent'	=> 'role-defaults-clear',
				'title'		=> '<select id="role-defaults-clear-roles-select" name="role-defaults-clear-roles-select"><option value=""> --- </option><option value="all">' . __('All roles', 'view-admin-as') . '</option>' 
								. $role_select_options . '</select>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
					'html'		=> '',
				),
			) );
			$admin_bar->add_node( array(
				'id'		=> 'role-defaults-clear-roles-apply',
				'parent'	=> 'role-defaults-clear',
				'title'		=> '<button id="role-defaults-clear-roles-apply" class="button button-secondary" name="role-defaults-clear-roles-apply">' . __('Apply', 'view-admin-as') . '</button>',
				'href'		=> false,
				'meta'		=> array(
					'class' 	=> 'vaa-button-container',
					'html'		=> '',
				),
			) );
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
