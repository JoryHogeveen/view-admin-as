<?php
/**
 * View Admin As - Role Defaults Module
 *
 * Set default screen settings for roles and apply them on users through various bulk actions.
 *
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.4
 * @version 1.6.x
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As_Role_Defaults extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since   1.5
	 * @var     VAA_View_Admin_As_Role_Defaults
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
	 * @since  1.5.2    Set both values and keys to fix problem with unsetting a key through the filter
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
	 * Protected to make sure it isn't declared elsewhere
	 *
	 * @since   1.4
	 * @access  protected
	 */
	protected function __construct() {
		self::$_instance = $this;
		parent::__construct();

		// Load data
		$this->set_optionData( get_option( $this->get_optionKey() ) );

		/**
		 * Checks if the management part of module should be enabled
		 *
		 * @since  1.4    Validate option data
		 * @since  1.6    Also calls init()
		 */
		if ( true == $this->get_optionData('enable') ) {
			$this->enable = true;
			$this->init();
		}

		/**
		 * Only allow settings for admin users or users with the correct apabilities
		 *
		 * @since  1.5.2    Validate custom capability view_admin_as_role_defaults
		 * @since  1.5.2.1  Validate is_super_admin (bug in 1.5.2)
		 * @since  1.5.3    Disable for network pages
		 */
		if (   $this->is_vaa_enabled()
			&& ! is_network_admin()
			&& ( is_super_admin( $this->get_curUser()->ID ) || current_user_can('view_admin_as_role_defaults') )
		) {
			add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
		}
	}

	/**
	 * Init function for global functions (not user dependent)
	 *
	 * @since   1.4
	 * @access  private
	 * @return  void
	 */
	private function init() {

		/**
		 * Add capabilities for this module
		 * @since 1.6
		 */
		$this->capabilities = array( 'view_admin_as_role_defaults' );
		add_filter( '_vaa_add_capabilities', array( $this, 'add_capabilities' ) );

		/**
		 * Replace %% with the current table prefix and add it to the array of forbidden meta keys
		 * @since 1.5.2
		 */
		global $wpdb;
		foreach ( $this->meta_forbidden as $key => $meta_key ) {
			if ( strpos( $meta_key, '%%' ) !== false ) {
				$this->meta_forbidden[] = str_replace( '%%', (string) $wpdb->prefix, $meta_key );
			}
		}

		/**
		 * Allow users to overwrite the meta keys
		 * @since   1.4
		 * @param   array  $meta  Default metadata
		 * @return  array  $meta
		 */
		$this->set_meta( apply_filters( 'view_admin_as_role_defaults_meta', $this->get_meta() ) );

		// Setting: Automatically apply defaults to new users
		if ( true == $this->get_optionData('apply_defaults_on_register') ) {
			if ( is_multisite() ) {
				add_action( 'add_user_to_blog', array( $this, 'update_user_with_role_defaults_multisite_register' ), 100, 3 );
			} else {
				add_action( 'user_register', array( $this, 'update_user_with_role_defaults' ), 100, 1 );
			}
		}

		// Setting: Hide the screen options for all users who can't access role defaults
		if ( true == $this->get_optionData('disable_user_screen_options')
			&& ! ( $this->is_vaa_enabled() && ( is_super_admin( $this->get_curUser()->ID ) || current_user_can('view_admin_as_role_defaults') ) )
		) {
			add_filter( 'screen_options_show_screen', '__return_false', 100 );
		}

		/**
		 * Print script in the admin header
		 * Also handles the lock_meta_boxes setting
		 * @since 1.6
		 */
		add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ), 100 );
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view
	 *
	 * @since   1.4
	 * @access  public
	 * @param   object
	 * @return  void
	 */
	public function vaa_init() {

		// Enabling this module can only be done by a super admin
		if ( is_super_admin( $this->get_curUser()->ID ) ) {

			// Add adminbar menu items in settings section
			add_action( 'vaa_admin_bar_settings_after', array( $this, 'admin_bar_menu_settings' ), 10, 2 );
		}

		// Add adminbar menu items in role section
		if ( $this->is_enabled() ) {

			// Enable storage of role default settings
			$this->init_store_role_defaults();

			// Show the admin bar node
			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), 10, 5 );
		}
	}

	/**
	 * Print scripts in the admin section
	 *
	 * @since   1.6
	 * @access  public
	 */
	public function admin_print_scripts() {

		/**
		 * Setting: Lock meta box order and locations for all users who can't access role defaults
		 *
		 * @since  1.6
		 */
		if ( true == $this->get_optionData('lock_meta_boxes')
		     && ! ( $this->is_vaa_enabled() && ( is_super_admin( $this->get_curUser()->ID ) || current_user_can('view_admin_as_role_defaults') ) )
		) {
			?>
			<script type="text/javascript">
				jQuery(document).ready( function($) {
					/**
					 * Lock meta boxes in position by disabling sorting.
					 *
					 * Credits go to Chris Van Patten:
					 * http://wordpress.stackexchange.com/a/44539
					 */
					$('.meta-box-sortables').sortable( { disabled: true } );
					$('.postbox .hndle').css( 'cursor', 'pointer' );
				});
			</script>
			<?php
		}
	}

	/**
	 * Get the metadata for meta compare
	 *
	 * @since   1.5
	 * @access  private
	 * @return  array   $this->meta
	 */
	private function get_meta() { return $this->meta; }

	/**
	 * Set the metadata for meta compare
	 * Used to enforce only 1 level depth array of strings
	 *
	 * @since   1.5
	 * @access  private
	 * @param   array   $var
	 * @return  void
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
	 * @access  private
	 * @param   array   $metas
	 * @return  array
	 */
	private function validate_meta( $metas ) {
		if ( is_array( $metas ) ) {
			foreach( $metas as $key => $meta_key ) {
				// Remove forbidden or invalid meta keys
				if (   in_array( $meta_key, $this->meta_forbidden )
					|| strpos( $meta_key, ' ' ) !== false
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
	 * Ajax handler, called from main ajax handler
	 *
	 * @since   1.4
	 * @access  public
	 * @param   array
	 * @return  array|string|bool
	 */
	public function ajax_handler( $data ) {

		if (   ! defined('VAA_DOING_AJAX')
		    || ! VAA_DOING_AJAX
		    || ! $this->is_vaa_enabled()
		) {
			return false;
		}

		$success = true;

		// Validate super admin
		if ( is_super_admin( $this->get_curUser()->ID ) ) {

			if ( isset( $data['enable'] ) ) {
				if ( true == $data['enable'] ) {
					$success = $this->set_enable( true );
				} else {
					$success = $this->set_enable( false );
				}
				// Prevent further processing
				return $success;
			}

		}

		// From here all featured need this module enabled first
		if ( ! $this->is_enabled() ) {
			return $success;
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
		if ( isset( $data['lock_meta_boxes'] ) ) {
			if ( true == $data['lock_meta_boxes'] ) {
				$success = $this->update_optionData( true, 'lock_meta_boxes', true );
			} else {
				$success = $this->update_optionData( false, 'lock_meta_boxes', true );
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
			die();
		}
		if ( isset( $data['import_role_defaults'] ) && is_string( $data['import_role_defaults'] ) ) {
			// $content format: array( 'text' => **text**, 'errors' => **error array** )
			$content = $this->import_role_defaults( json_decode( stripslashes( $data['import_role_defaults'] ), true ) );
			if ( true === $content ) {
				wp_send_json_success();
			} else {
				wp_send_json_success( array( 'type' => 'errorlist', 'content' => $content ) );
			}
			die();
		}

		return $success;
	}

	/**
	 * Update user settings with the a role default
	 * When no role is provided this function only checks the first existing user role. If the user has multiple roles, the other roles are ignored.
	 *
	 * @since   1.4
	 * @access  public
	 * @see     update_user_with_role_defaults_multisite_register()
	 * @see     apply_defaults_to_users_by_role()
	 * @see     ajax_handler()
	 *
	 * @see     'user_register' action
	 * @link    https://developer.wordpress.org/reference/hooks/user_register/
	 *
	 * @param   int          $user_id
	 * @param   string|bool  $role
	 * @param   int|bool     $blog_id
	 * @return  bool
	 */
	public function update_user_with_role_defaults( $user_id, $role = false, $blog_id = false ) {
		$success = true;
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			if ( false != $blog_id && is_numeric( $blog_id ) ) {
				$optionData = get_blog_option( $blog_id, $this->get_optionKey() );
			} else {
				$optionData = get_option( $this->get_optionKey() );
			}
			// If no role was set, use the first role found for this user
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
	 * @since   1.4
	 * @access  public
	 * @see     'add_user_to_blog' action
	 * @link    https://developer.wordpress.org/reference/hooks/add_user_to_blog/
	 *
	 * @param   int     $user_id
	 * @param   string  $role
	 * @param   int     $blog_id
	 * @return  bool
	 */
	public function update_user_with_role_defaults_multisite_register( $user_id, $role, $blog_id ) {
		$user_blogs = get_blogs_of_user( $user_id );
		if ( 1 === count( $user_blogs ) ) {
			// If the user has access to one blog only it is safe to set defaults since it is most likely a new user.
			return $this->update_user_with_role_defaults( $user_id, $role, $blog_id );
		}
		return false;
	}

	/**
	 * Apply default settings to all users of a role
	 *
	 * @since   1.4
	 * @access  private
	 * @param   string  $role
	 * @return  bool
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
				if ( ! empty( $users ) ) {
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
	 * @access  private
	 * @see     vaa_init()
	 * @return  void
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
	 *
	 * IMPORTANT! This filter should ONLY be used when a role view is selected!
	 *
	 * @since   1.4
	 * @since   1.5.3   Stop checking $single parameter
	 * @access  public
	 * @see     init_store_role_defaults()
	 *
	 * @see     'get_user_metadata' filter
	 * @link    https://codex.wordpress.org/Plugin_API/Filter_Reference/get_(meta_type)_metadata
	 * @link    http://hookr.io/filters/get_user_metadata/
	 *
	 * @param   null    $null
	 * @param   int     $object_id
	 * @param   string  $meta_key
	 * @param   bool    $single
	 * @return  mixed
	 */
	public function filter_get_user_metadata( $null, $object_id, $meta_key, $single ) {
		if ( true === $this->compare_metakey( $meta_key ) && $object_id == $this->get_curUser()->ID ) {
			$new_meta = $this->get_role_defaults( $this->get_viewAs('role'), $meta_key );
			// Do not check $single, this logic is in wp-includes/meta.php line 487
			return array( $new_meta );
		}
		return null; // Go on as normal
	}

	/**
	 * Check if the meta_key maches one of the predefined metakeys to store as defaults.
	 * If there is a match, store the update to the defaults and cancel the update for the current user.
	 *
	 * IMPORTANT! This filter should ONLY be used when a role view is selected!
	 *
	 * @since   1.4
	 * @access  public
	 * @see     init_store_role_defaults()
	 *
	 * @see     'update_user_metadata' filter
	 * @link    https://codex.wordpress.org/Plugin_API/Filter_Reference/update_(meta_type)_metadata
	 * @link    http://hookr.io/filters/update_user_metadata/
	 *
	 * @param   null    $null
	 * @param   int     $object_id
	 * @param   string  $meta_key
	 * @param   string  $meta_value
	 * @param   string  $prev_value
	 * @return  mixed
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
	 * @access  private
	 *
	 * @param   string  $role
	 * @param   string  $meta_key
	 * @return  mixed
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
	 * @access  private
	 *
	 * @param   string  $role
	 * @param   string  $meta_key
	 * @param   string  $meta_value
	 * @return  bool
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
	 * @access  private
	 * @param   string  $role
	 * @return  bool
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
					//$role_defaults[ $role ] = array();
					unset( $role_defaults[ $role ] );
				}
			}
		}
		if ( $this->get_optionData( 'roles' ) !== $role_defaults ) {
			return $this->update_optionData( $role_defaults, 'roles' );
		}
		return true; // No changes needed
	}

	/**
	 * Export role defaults
	 *
	 * @since   1.5
	 * @access  private
	 * @param   string  $role
	 * @return  mixed
	 */
	private function export_role_defaults( $role = 'all' ) {
		$role_defaults = $this->get_optionData( 'roles' );
		if ( 'all' != $role && isset( $role_defaults[ $role ] ) ) {
			$data = $role_defaults[ $role ];
			$data = array( $role => $data );
		} elseif ( 'all' == $role && ! empty( $role_defaults ) ) {
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
	 * @access  private
	 * @param   array   $data
	 * @return  mixed
	 */
	private function import_role_defaults( $data ) {
		$new_defaults = array();
		$error_list = array();
		if ( empty( $data ) || ! is_array( $data ) ) {
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
	 * @access  private
	 * @param   string  $meta_key_compare
	 * @return  bool
	 */
	private function compare_metakey( $meta_key_compare ) {
		$meta_keys = $this->get_meta();
		if ( is_array( $meta_keys ) ) {
			foreach( $meta_keys as $key => $meta_key ) {
				if ( empty( $meta_key ) || ! is_string( $meta_key ) ) {
					continue;
				} else {
					$meta_key_parts = explode( '%%', $meta_key );

					$compare_start = true;
					if ( ! empty( $meta_key_parts[0] ) ) {
						$compare_start = $this->startsWith( $meta_key_compare, $meta_key_parts[0] );
					}

					$compare_end = true;
					if ( ! empty( $meta_key_parts[1] ) ) {
						$compare_end = $this->endsWith( $meta_key_compare, $meta_key_parts[1] );
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
	 * @since   1.5
	 * @access  public
	 * @see     'vaa_admin_bar_settings_after' action
	 *
	 * @param   object  $admin_bar
	 * @param   string  $root  The root item (vaa-settings)
	 * @return  void
	 */
	public function admin_bar_menu_settings( $admin_bar, $root ) {

		$admin_bar->add_group( array(
			'id' => $root . '-role-defaults',
			'parent' => $root,
			'meta'      => array(
				'class'    => 'ab-sub-secondary',
			),
		) );

		$root = $root . '-role-defaults';

		$admin_bar->add_node( array(
			'id'        => $root . '-enable',
			'parent'    => $root,
			'title'     => '<input class="checkbox" value="1" id="' . $root . '-enable" name="vaa_role_defaults_enable" type="checkbox" ' . checked( $this->get_optionData( 'enable' ), true, false ) . '>
							<label for="' . $root . '-enable">' . __('Enable role defaults', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __('Set default screen settings for roles and apply them on users through various bulk and automatic actions', 'view-admin-as') . '</p>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );

	}

	/**
	 * Add admin bar menu's
	 *
	 *
	 * @since   1.4
	 * @since   1.5.2   Changed hook to vaa_admin_bar_settings_after (previous: 'vaa_admin_bar_roles_before')
	 * @access  public
	 * @see     'vaa_admin_bar_menu' action
	 *
	 * @param   object  $admin_bar
	 * @param   string  $root  The root item (vaa)
	 * @return  void
	 */
	public function admin_bar_menu( $admin_bar, $root ) {

		$admin_bar->add_node( array(
			'id'        => $root . '-role-defaults',
			'parent'    => $root,
			'title'     => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-id-alt' ) . __('Role defaults', 'view-admin-as'),
			'href'      => false,
			'meta'      => array(
				'class'    => 'vaa-has-icon',
				'tabindex' => '0'
			),
		) );

		$root = $root . '-role-defaults';

		$role_select_options = '';
		foreach ( $this->get_roles() as $role_key => $role ) {
			$role_select_options .= '<option value="' . esc_attr( $role_key ) . '">' . translate_user_role( $role->name ) . '</option>';
		}

		$admin_bar->add_node( array(
			'id'        => $root . '-setting-register-enable',
			'parent'    => $root,
			'title'     => '<input class="checkbox" value="1" id="' . $root . '-register-enable" name="vaa_role_defaults_register_enable" type="checkbox" ' . checked( $this->get_optionData( 'apply_defaults_on_register' ), true, false ) . '>
							<label for="' . $root . '-register-enable">' . __('Automatically apply defaults to new users', 'view-admin-as') . '</label>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );
		$admin_bar->add_node( array(
			'id'        => $root . '-setting-disable-user-screen-options',
			'parent'    => $root,
			'title'     => '<input class="checkbox" value="1" id="' . $root . '-disable-user-screen-options" name="vaa_role_defaults_disable_user_screen_options" type="checkbox" ' . checked( $this->get_optionData( 'disable_user_screen_options' ), true, false ) . '>
							<label for="' . $root . '-_disable_user_screen_options">' . __('Disable screen options', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __("Hide the screen options for all users who can't access role defaults", 'view-admin-as') . '</p>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );
		$admin_bar->add_node( array(
			'id'        => $root . '-setting-lock-meta-boxes',
			'parent'    => $root,
			'title'     => '<input class="checkbox" value="1" id="' . $root . '-lock-meta-boxes" name="vaa_role_defaults_lock_meta_boxes" type="checkbox" ' . checked( $this->get_optionData( 'lock_meta_boxes' ), true, false ) . '>
							<label for="' . $root . '-lock-meta-boxes">' . __('Lock meta boxes', 'view-admin-as') . '</label>
							<p class="description ab-item">' . __("Lock meta box order and locations for all users who can't access role defaults", 'view-admin-as') . '</p>',
			'href'      => false,
			'meta'      => array(
				'class'    => 'auto-height',
			),
		) );

		/**
		 * Bulk actions
		 */

		if ( $this->get_users() ) {
			// Users select
			$admin_bar->add_group( array(
				'id'        => $root . '-bulk-users',
				'parent'    => $root,
				'meta'      => array(
					'class'    => 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-bulk-users-title',
				'parent'    => $root . '-bulk-users',
				'title'     => __('Apply defaults to users', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-bold ab-vaa-toggle',
					'tabindex' => '0'
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-bulk-users-filter',
				'parent'    => $root . '-bulk-users',
				'title'     => '<input id="' . $root . '-bulk-users-filter" name="vaa-filter" placeholder="' . esc_attr__('Filter', 'view-admin-as') . ' (' . strtolower( __('Username') ) . ')" />',
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-vaa-filter',
				),
			) );
			$bulk_users_select_content = '';
			foreach ( $this->get_users() as $user ) {
				foreach ( $user->roles as $role ) {
					if ( $role_data = $this->get_roles( $role ) ) {
						$role_name = translate_user_role( $role_data->name );
						$bulk_users_select_content .=
							'<div class="ab-item vaa-item">
								<input class="checkbox" value="' . $user->ID.'|'.$role . '" id="' . $root . '-bulk-users-select-' . $user->ID . '" name="role-defaults-bulk-users-select[]" type="checkbox">
								<label for="' . $root . '-bulk-users-select-' . $user->ID . '"><span class="user-name">' . $user->display_name . '</span> &nbsp; <span class="user-role">(' . $role_name . ')</span></label>
							</div>';
					}
				}
			}
			$admin_bar->add_node( array(
				'id'        => $root . '-bulk-users-select',
				'parent'    => $root . '-bulk-users',
				'title'     => $bulk_users_select_content,
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-vaa-multipleselect max-height',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-bulk-users-apply',
				'parent'    => $root . '-bulk-users',
				'title'     => '<button id="' . $root . '-bulk-users-apply" class="button button-primary" name="role-defaults-bulk-users-apply">' . __('Apply', 'view-admin-as') . '</button>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-button-container',
				),
			) );
		}

		if ( $this->get_users() && $this->get_roles() ) {
			// Roles select
			$admin_bar->add_group( array(
				'id'        => $root . '-bulk-roles',
				'parent'    => $root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-bulk-roles-title',
				'parent'    => $root . '-bulk-roles',
				'title'     => __('Apply defaults to users by role', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-bold ab-vaa-toggle',
					'tabindex' => '0'
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-bulk-roles-select',
				'parent'    => $root . '-bulk-roles',
				'title'     => '<select id="' . $root . '-bulk-roles-select" name="role-defaults-bulk-roles-select"><option value=""> --- </option><option value="all">' . __('All roles', 'view-admin-as') . '</option>'
								. $role_select_options . '</select>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-bulk-roles-apply',
				'parent'    => $root . '-bulk-roles',
				'title'     => '<button id="' . $root . '-bulk-roles-apply" class="button button-primary" name="role-defaults-bulk-roles-apply">' . __('Apply', 'view-admin-as') . '</button>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-button-container',
				),
			) );
		}

		if ( $this->get_roles() ) {

			/* Export actions */
			$admin_bar->add_group( array(
				'id'        => $root . '-export',
				'parent'    => $root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-export-roles',
				'parent'    => $root . '-export',
				'title'     => __('Export defaults for role', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-bold ab-vaa-toggle',
					'tabindex' => '0'
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-export-roles-select',
				'parent'    => $root . '-export',
				'title'     => '<select id="' . $root . '-export-roles-select" name="role-defaults-export-roles-select"><option value="all">' . __('All roles', 'view-admin-as') . '</option>'
								. $role_select_options . '</select>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-export-roles-export',
				'parent'    => $root . '-export',
				'title'     => '<button id="' . $root . '-export-roles-export" class="button button-secondary" name="role-defaults-export-roles-export">' . __('Export', 'view-admin-as') . '</button>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-button-container',
				),
			) );

			/* Import actions */
			$admin_bar->add_group( array(
				'id'        => $root . '-import',
				'parent'    => $root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-import-roles',
				'parent'    => $root . '-import',
				'title'     => __('Import defaults for role', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-bold ab-vaa-toggle',
					'tabindex' => '0'
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-import-roles-input',
				'parent'    => $root . '-import',
				'title'     => '<textarea id="' . $root . '-import-roles-input" name="role-defaults-import-roles-input" placeholder="' . esc_attr__('Paste code here', 'view-admin-as') . '"></textarea>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-vaa-textarea input-role', // vaa-column-one-half vaa-column-last
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-import-roles-import',
				'parent'    => $root . '-import',
				'title'     => '<button id="' . $root . '-import-roles-import" class="button button-secondary" name="role-defaults-import-roles-import">' . __('Import', 'view-admin-as') . '</button>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-button-container',
				),
			) );

			/* Clear actions */
			$admin_bar->add_group( array(
				'id'        => $root . '-clear',
				'parent'    => $root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary vaa-sub-transparent',
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-clear-roles',
				'parent'    => $root . '-clear',
				'title'     => __('Remove defaults for role', 'view-admin-as'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-bold ab-vaa-toggle',
					'tabindex' => '0'
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-clear-roles-select',
				'parent'    => $root . '-clear',
				'title'     => '<select id="' . $root . '-clear-roles-select" name="role-defaults-clear-roles-select"><option value=""> --- </option><option value="all">' . __('All roles', 'view-admin-as') . '</option>'
								. $role_select_options . '</select>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'ab-vaa-select select-role', // vaa-column-one-half vaa-column-last
				),
			) );
			$admin_bar->add_node( array(
				'id'        => $root . '-clear-roles-apply',
				'parent'    => $root . '-clear',
				'title'     => '<button id="' . $root . '-clear-roles-apply" class="button button-secondary" name="role-defaults-clear-roles-apply">' . __('Apply', 'view-admin-as') . '</button>',
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-button-container',
				),
			) );
		}
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
	 * @return  VAA_View_Admin_As_Role_Defaults|bool
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
