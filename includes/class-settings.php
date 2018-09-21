<?php
/**
 * View Admin As - Class Settings
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Settings class that stores the VAA settings for use.
 *
 * @see VAA_View_Admin_As_Store
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.7.0
 * @version 1.8.1
 * @uses    \VAA_View_Admin_As_Base Extends class
 */
class VAA_View_Admin_As_Settings extends VAA_View_Admin_As_Base
{
	/**
	 * The key to use for filters.
	 * Passed to __construct() as first parameter.
	 *
	 * @since  1.8.0
	 * @var    string
	 */
	private $_filter_postfix = '';

	/**
	 * Is this option for a network installation?
	 * Can only be set with set_for_network().
	 *
	 * @since  1.7.5
	 * @see    \VAA_View_Admin_As_Settings::store_optionData()
	 * @var    bool
	 */
	protected $for_network = false;

	/**
	 * The user ID for whom this metadata is for.
	 * Can only be set with store_userMeta().
	 *
	 * @since  1.7.5
	 * @see    \VAA_View_Admin_As_Settings::store_userMeta()
	 * @var    int
	 */
	protected $for_user = null;

	/**
	 * Database option key.
	 * Always starts with `vaa_`.
	 * Keys are parsed with underscores as spacing.
	 *
	 * @since  1.4.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    string
	 */
	protected $optionKey = null;

	/**
	 * Database option data.
	 *
	 * @since  1.4.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    array
	 */
	protected $optionData = array();

	/**
	 * User meta key for settings ans views.
	 * Always starts with `vaa-`.
	 * Keys are parsed with dashes as spacing.
	 *
	 * @since  1.3.4
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    string
	 */
	protected $userMetaKey = null;

	/**
	 * User meta value for settings ans views.
	 *
	 * @since  1.5.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    array
	 */
	protected $userMeta = array();

	/**
	 * User meta from all users.
	 *
	 * @since  1.8.0
	 * @var    array
	 */
	protected $allUserMeta = array();

	/**
	 * Array of default settings.
	 *
	 * @since  1.5.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    array
	 */
	protected $defaultSettings = array();

	/**
	 * Array of allowed settings.
	 *
	 * @since  1.5.0
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    array
	 */
	protected $allowedSettings = array();

	/**
	 * Array of default settings.
	 *
	 * @since  1.5.0
	 * @since  1.5.2  Added force_group_users.
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.6.1  Added freeze_locale.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    array
	 */
	protected $defaultUserSettings = array();

	/**
	 * Array of allowed settings.
	 * Setting name (key) => array( values ).
	 *
	 * @since  1.5.0
	 * @since  1.5.2  Added force_group_users.
	 * @since  1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since  1.6.1  Added freeze_locale.
	 * @since  1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @var    array
	 */
	protected $allowedUserSettings = array();

	/**
	 * Sets the default data.
	 *
	 * @since   1.7.0
	 * @access  protected
	 * @param   string  $id    Identifier for this settings instance.
	 * @param   array   $args  {
	 *     (optional) Setting arguments.
	 *     @type  array  $default  The default settings (option)
	 *     @type  array  $allowed  The allowed settings (option). Use arrays to define all possible values for a setting.
	 *     @type  array  $default_user  The default user settings (meta)
	 *     @type  array  $allowed_user  The allowed user settings (meta). Use arrays to define all possible values for a setting.
	 * }
	 */
	protected function __construct( $id, $args = array() ) {
		parent::__construct();

		if ( empty( $id ) || ! is_string( $id ) ) {
			return null;
		}

		$args = wp_parse_args( $args, array(
			'default'      => array(),
			'allowed'      => array(),
			'default_user' => array(),
			'allowed_user' => array(),
		) );

		$default = $args['default'];
		$allowed = $args['allowed'];

		$default_user = $args['default_user'];
		$allowed_user = $args['allowed_user'];

		if ( 'VAA_View_Admin_As_Store' === get_class( $this ) ) {

			$this->set_optionKey( 'vaa_view_admin_as' );
			$this->set_optionData( array(
				'db_version' => null,
				'settings'   => null,
			) );

			$this->set_userMetaKey( 'vaa-view-admin-as' );
			$this->set_userMeta( array(
				'settings' => null,
				'views'    => null,
			) );

			$default = array(
				'view_types' => array(),
			);
			$allowed = array(
				'view_types' => array(), // No restriction to values.
			);

			$default_user = array(
				'admin_menu_location' => 'top-secondary',
				'disable_super_admin' => true,
				'force_group_users'   => false,
				'force_ajax_users'    => false,
				'freeze_locale'       => false,
				'hide_customizer'     => false,
				'hide_front'          => false,
				'view_mode'           => 'browse',
			);
			$allowed_user = array(
				'admin_menu_location' => array( 'top-secondary', 'my-account' ),
				'disable_super_admin' => array( true, false ),
				'force_group_users'   => array( true, false ),
				'force_ajax_users'    => array( true, false ),
				'freeze_locale'       => array( true, false ),
				'hide_customizer'     => array( true, false ),
				'hide_front'          => array( true, false ),
				'view_mode'           => array( 'browse', 'single' ),
			);

			// @todo Remove?
			$this->add_filter( 'view_admin_as_validate_view_data_setting', array( $this, 'filter_validate_settings' ), 10, 3 );
			$this->add_filter( 'view_admin_as_validate_view_data_user_setting', array( $this, 'filter_validate_settings' ), 10, 3 );

			$this->add_filter( 'view_admin_as_handle_ajax_setting', array( $this, 'filter_update_settings' ), 10, 3 );
			$this->add_filter( 'view_admin_as_handle_ajax_user_setting', array( $this, 'filter_update_settings' ), 10, 3 );

			// Make identifier empty for the filters.
			$id = '';

		} else {

			if ( 'view-admin-as' === sanitize_title_with_dashes( $id ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						// Translators: %1$s stands for an option key and %2$s stands for a class name.
						esc_html__( 'The setting key %1$s is reserved for class %2$s', VIEW_ADMIN_AS_DOMAIN ),
						esc_html( $id ),
						'VAA_View_Admin_As_Store'
					),
					''
				);
				return;
			}

			$this->set_optionKey( 'vaa_' . $id );
			$this->set_userMetaKey( 'vaa-' . $id );

			// Append underscore to the identifier for the filters.
			$id = '_' . $id;

		} // End if().

		$this->_filter_postfix = $id;

		/**
		 * Set the default global settings.
		 *
		 * @since  1.7.0
		 * @param  array
		 * @return array
		 */
		$this->set_defaultSettings( apply_filters( 'view_admin_as_default_global_settings' . $id, $default ) );

		/**
		 * Set the allowed global settings.
		 *
		 * @since  1.7.0
		 * @param  array {
		 *     Settings array (key = setting name).
		 *     @type  array  Array of allowed values.
		 * }
		 * @return array
		 */
		$this->set_allowedSettings( apply_filters( 'view_admin_as_allowed_global_settings' . $id, $allowed ) );

		/**
		 * Set the default settings for users.
		 *
		 * @since  1.7.0
		 * @param  array
		 * @return array
		 */
		$this->set_defaultUserSettings( apply_filters( 'view_admin_as_default_user_settings' . $id, $default_user ) );

		/**
		 * Set the allowed settings for users.
		 *
		 * @since  1.7.0
		 * @param  array {
		 *     Settings array (key = setting name).
		 *     @type  array  Array of allowed values.
		 * }
		 * @return array
		 */
		$this->set_allowedUserSettings( apply_filters( 'view_admin_as_allowed_user_settings' . $id, $allowed_user ) );

	}

	/**
	 * Validate hook for settings.
	 *
	 * @since   1.7.0
	 * @param   null    $null  Default return (invalid).
	 * @param   mixed   $data  The view data.
	 * @param   string  $key   The data key.
	 * @return  mixed
	 */
	public function filter_validate_settings( $null, $data, $key ) {
		if ( ! empty( $data ) && ! empty( $key ) ) {
			if ( 'setting' === $key ) {
				return $this->validate_settings( $data, 'global', false );
			}
			if ( 'user_setting' === $key ) {
				return $this->validate_settings( $data, 'user', false );
			}
		}
		return $null;
	}

	/**
	 * Validate hook for settings.
	 *
	 * @since   1.7.0
	 * @since   1.7.3   Renamed from `filter_store_settings()`.
	 * @param   null    $null  Default return (invalid).
	 * @param   mixed   $data  The view data.
	 * @param   string  $key   The data key.
	 * @return  mixed
	 */
	public function filter_update_settings( $null, $data, $key ) {
		if ( ! empty( $data ) && ! empty( $key ) ) {
			if ( 'setting' === $key ) {
				return $this->update_settings( $data, 'global' );
			}
			if ( 'user_setting' === $key ) {
				return $this->update_settings( $data, 'user' );
			}
		}
		return $null;
	}

	/**
	 * Validate setting data based on allowed settings.
	 * Will also merge with the default settings unless third $merge parameter is false.
	 *
	 * @since   1.5.0
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.7.0  Moved from `VAA_View_Admin_As_Store`. Added third `$merge` parameter.
	 * @access  public
	 *
	 * @param   array       $settings  The new settings.
	 * @param   string      $type      The type of settings (global / user).
	 * @param   bool        $merge     Merge with defaults? (will return all settings).
	 * @return  array|bool  $settings / false
	 */
	public function validate_settings( $settings, $type, $merge = true ) {
		if ( 'global' === $type ) {
			$defaults = $this->get_defaultSettings();
			$allowed  = $this->get_allowedSettings();
		} elseif ( 'user' === $type ) {
			$defaults = $this->get_defaultUserSettings();
			$allowed  = $this->get_allowedUserSettings();
		} else {
			return false;
		}

		if ( $merge ) {
			return $this->parse_settings( $settings, $defaults, $allowed );
		}

		foreach ( $settings as $setting => $value ) {
			// Only pass the settings if the key and value matched the data in the allowed settings.
			if ( ! array_key_exists( $setting, $allowed ) ) {
				unset( $settings[ $setting ] );
			}
			// If setting key is allowed value is empty we don't need to validate.
			if ( ! empty( $allowed[ $setting ] ) && ! in_array( $value, $allowed[ $setting ], true ) ) {
				unset( $settings[ $setting ] );
			}
		}
		return $settings;
	}

	/**
	 * Store settings based on allowed settings.
	 * Also merges with the default settings.
	 *
	 * @since   1.5.0
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @since   1.7.3  Renamed from `store_settings()`.
	 * @access  public
	 *
	 * @param   array   $settings  The new settings.
	 * @param   string  $type      The type of settings (global / user).
	 * @return  bool
	 */
	public function update_settings( $settings, $type ) {
		if ( 'global' === $type ) {
			$current  = $this->get_settings();
			$defaults = $this->get_defaultSettings();
			$allowed  = $this->get_allowedSettings();
		} elseif ( 'user' === $type ) {
			$current  = $this->get_userSettings();
			$defaults = $this->get_defaultUserSettings();
			$allowed  = $this->get_allowedUserSettings();
		} else {
			return false;
		}
		if ( ! is_array( $current ) ) {
			$current = $defaults;
		}

		/**
		 * Filter the settings before they are validated.
		 *
		 * @since  1.8.0
		 * @param  array  $settings  New settings.
		 * @param  array  $current   Current settings.
		 * @param  array  $defaults  Default settings.
		 * @param  array  $allowed   Allowed settings.
		 * @return array
		 */
		$filter   = 'view_admin_as_update_' . $type . '_settings' . $this->_filter_postfix;
		$settings = apply_filters( $filter, $settings, $current, $defaults, $allowed );

		$settings = $this->validate_settings( $settings, $type, false );

		foreach ( $settings as $setting => $value ) {
			$current[ $setting ] = $value;
			// Some settings need a reset.
			if ( in_array( $setting, array( 'view_mode' ), true ) ) {
				view_admin_as()->controller()->reset_view();
			}
		}

		$new = $this->parse_settings( $current, $defaults, $allowed );

		if ( 'global' === $type ) {
			return $this->update_optionData( $new, 'settings', true );
		} elseif ( 'user' === $type ) {
			return $this->update_userMeta( $new, 'settings', true );
		}
		return false;
	}

	/**
	 * Parse the settings.
	 * Checks if the setting exists, removes it otherwise.
	 * Checks if the setting is allowed, otherwise sets it to the default value.
	 *
	 * @since   1.7.0
	 * @param   array  $settings  The new settings.
	 * @param   array  $defaults  The default settings.
	 * @param   array  $allowed   The allowed settings.
	 * @return  array
	 */
	public function parse_settings( $settings, $defaults, $allowed ) {
		$settings = wp_parse_args( $settings, $defaults );
		foreach ( $settings as $setting => $value ) {
			if ( ! array_key_exists( $setting, $allowed ) ) {
				// We don't have such a setting.
				unset( $settings[ $setting ] );
			} elseif ( ! empty( $allowed[ $setting ] ) && ! in_array( $value, $allowed[ $setting ], true ) ) {
				// Set it to default if the allowed values are set and the value isn't allowed.
				$settings[ $setting ] = $defaults[ $setting ];
			}
		}
		return $settings;
	}

	/**
	 * Get the meta key results for all users.
	 *
	 * @since   1.8.0
	 * @global  \wpdb  $wpdb
	 * @return  array {
	 *     User ID's as array keys.
	 *     @type  array  $meta_values  The meta values. Column ID's as array keys.
	 * }
	 */
	public function get_all_user_meta() {
		if ( ! empty( $this->allUserMeta ) ) {
			return $this->allUserMeta;
		}

		global $wpdb;
		$key = $this->get_userMetaKey();

		// @todo Use WP_Meta_Query ?
		$sql = 'SELECT * FROM ' . $wpdb->usermeta . ' WHERE meta_key = %s';
		// @codingStandardsIgnoreLine >> $wpdb->prepare(), check returning false error.
		$results = (array) $wpdb->get_results( $wpdb->prepare( $sql, $key ) );

		$metas = array();

		foreach ( $results as $key => $meta ) {
			if ( ! isset( $metas[ $meta->user_id ] ) ) {
				$metas[ $meta->user_id ] = array();
			}
			if ( ! empty( $meta->meta_value ) ) {
				$metas[ $meta->user_id ][ $meta->umeta_id ] = maybe_unserialize( $meta->meta_value );
			}
		}

		$this->allUserMeta = $metas;

		return $metas;
	}

	/**
	 * Set the meta values for other users.
	 * Should be used together with get_all_user_meta() to get column id's.
	 *
	 * @since   1.8.0
	 * @see     \VAA_View_Admin_As_Settings::get_all_user_meta()
	 * @param   mixed  $value
	 * @param   int    $user_id
	 * @param   int    $column_id
	 * @return  bool
	 */
	public function update_other_user_meta( $value, $user_id, $column_id = null ) {
		if ( ! $this->allUserMeta ) {
			$this->get_all_user_meta();
		}

		// Validate settings.
		$value = wp_parse_args( $value, array(
			'settings' => array(),
		) );

		$value['settings'] = $this->validate_settings( $value['settings'], 'user', true );

		if ( ! isset( $this->allUserMeta[ $user_id ] ) ) {
			$column_id = 0;

			$this->allUserMeta[ $user_id ] = array( $column_id => $value );
		}

		if ( ! is_int( $column_id ) ) {
			reset( $this->allUserMeta[ $user_id ] );
			$column_id = key( $this->allUserMeta[ $user_id ] );
		}

		$this->allUserMeta[ $user_id ][ $column_id ] = $value;

		// @todo handle multiple columns.
		return update_user_meta( $user_id, $this->get_userMetaKey(), $value );
	}

	/**
	 * Delete or reset all View Admin As metadata for this user.
	 *
	 * @since   1.5.0
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.6.2  Option to remove the VAA metadata for all users.
	 * @since   1.7.0  Moved from `VAA_View_Admin_As_Store`.
	 * @access  public
	 *
	 * @param   int|string  $user_id     ID of the user being deleted/removed (pass `all` for all users).
	 * @param   \WP_User    $user        User object provided by the wp_login hook.
	 * @param   bool        $reset_only  Only reset (not delete) the user meta.
	 * @return  bool
	 */
	public function delete_user_meta( $user_id = null, $user = null, $reset_only = true ) {
		/**
		 * Set the first parameter to `all` to remove the meta value for all users.
		 * @since  1.6.2
		 */
		if ( 'all' === $user_id ) {
			return $this->delete_all_user_meta( $reset_only );
		}

		$id = false;
		if ( is_numeric( $user_id ) ) {
			// Delete hooks.
			$id = (int) $user_id;
		} elseif ( isset( $user->ID ) ) {
			// Login/Logout hooks.
			$id = (int) $user->ID;
		}
		if ( $id ) {
			$success = true;
			if ( $reset_only ) {
				// Reset db metadata (returns: true on success, false on failure).
				if ( get_user_meta( $id, $this->get_userMetaKey() ) ) {
					$success = update_user_meta( $id, $this->get_userMetaKey(), false );
				}
			} else {
				// Remove db metadata (returns: true on success, false on failure).
				$success = delete_user_meta( $id, $this->get_userMetaKey() );
			}
			// Update current metadata if it is the current user.
			if ( $success && (int) get_current_user_id() === $id ) {
				$this->set_userMeta( false );
			}

			return $success;
		}
		// No user or metadata found, no deletion needed.
		return true;
	}

	/**
	 * Delete or reset all View Admin As metadata for all users.
	 *
	 * @since   1.7.0
	 * @access  public
	 *
	 * @see    https://developer.wordpress.org/reference/classes/wpdb/update/
	 * @see    https://developer.wordpress.org/reference/classes/wpdb/delete/
	 *
	 * @global  \wpdb  $wpdb
	 * @param   bool   $reset_only  Only reset (not delete) the user meta.
	 * @return  bool
	 */
	public function delete_all_user_meta( $reset_only = true ) {
		global $wpdb;
		if ( $reset_only ) {
			// Reset.
			return (bool) $wpdb->update(
				$wpdb->usermeta, // table.
				array( 'meta_value' => '' ), // data.
				array( 'meta_key' => $this->get_userMetaKey() ) // where.
			);
		} else {
			// Delete.
			return (bool) $wpdb->delete(
				$wpdb->usermeta, // table.
				array( 'meta_key' => $this->get_userMetaKey() ) // where.
			);
		}
	}

	/**
	 * Get the option key as used in the options table.
	 * @return  string
	 */
	public function get_optionKey() {
		return (string) $this->optionKey;
	}

	/**
	 * Get the user meta key as used in the usermeta table.
	 * @return  string
	 */
	public function get_userMetaKey() {
		return (string) $this->userMetaKey;
	}

	/**
	 * Get the option data as used in the options table.
	 * @param   string  $key  Key in the option array.
	 * @return  mixed
	 */
	public function get_optionData( $key = null ) {
		return VAA_API::get_array_data( $this->optionData, $key );
	}

	/**
	 * Get the user metadata as used in the usermeta table.
	 * @param   string  $key  Key in the meta array.
	 * @return  mixed
	 */
	public function get_userMeta( $key = null ) {
		return VAA_API::get_array_data( $this->userMeta, $key );
	}

	/**
	 * Get the default settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_defaultSettings( $key = null ) {
		return VAA_API::get_array_data( $this->defaultSettings, $key );
	}

	/**
	 * Get the default user settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_defaultUserSettings( $key = null ) {
		return VAA_API::get_array_data( $this->defaultUserSettings, $key );
	}

	/**
	 * Get the allowed settings.
	 * @param   string  $key  Setting key.
	 * @return  array
	 */
	public function get_allowedSettings( $key = null ) {
		return (array) VAA_API::get_array_data( $this->allowedSettings, $key );
	}

	/**
	 * Get the allowed user settings.
	 * @param   string  $key  Setting key.
	 * @return  array
	 */
	public function get_allowedUserSettings( $key = null ) {
		return (array) VAA_API::get_array_data( $this->allowedUserSettings, $key );
	}

	/**
	 * Get the settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_settings( $key = null ) {
		return VAA_API::get_array_data(
			$this->validate_settings(
				$this->get_optionData( 'settings' ),
				'global'
			),
			$key
		);
	}

	/**
	 * Get the user settings.
	 * @param   string  $key  Setting key.
	 * @return  mixed
	 */
	public function get_userSettings( $key = null ) {
		return VAA_API::get_array_data(
			$this->validate_settings(
				$this->get_userMeta( 'settings' ),
				'user'
			),
			$key
		);
	}

	/**
	 * Set the option key as used in the options table.
	 * @param   string  $val  Option key.
	 * @return  void
	 */
	protected function set_optionKey( $val ) {
		$this->optionKey = (string) str_replace( array( ' ', '-' ), '_', sanitize_title_with_dashes( $val ) );
	}

	/**
	 * Set the option key as used in the options table.
	 * @param   string  $val  Option key.
	 * @return  void
	 */
	protected function set_userMetaKey( $val ) {
		$this->userMetaKey = (string) sanitize_title_with_dashes( $val );
	}

	/**
	 * Set the default settings.
	 * @param   array   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	protected function set_defaultSettings( $val, $key = null, $append = false ) {
		$this->defaultSettings = VAA_API::set_array_data( $this->defaultSettings, $val, $key, $append );
	}

	/**
	 * Set the default user settings.
	 * @param   array   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	protected function set_defaultUserSettings( $val, $key = null, $append = false ) {
		$this->defaultUserSettings = VAA_API::set_array_data( $this->defaultUserSettings, $val, $key, $append );
	}

	/**
	 * Set the allowed settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	protected function set_allowedSettings( $val, $key = null, $append = false ) {
		$this->allowedSettings = VAA_API::set_array_data( $this->allowedSettings, $val, $key, $append );
	}

	/**
	 * Set the allowed user settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	protected function set_allowedUserSettings( $val, $key = null, $append = false ) {
		$this->allowedUserSettings = VAA_API::set_array_data( $this->allowedUserSettings, $val, $key, $append );
	}

	/**
	 * Set the settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_settings( $val, $key = null, $append = false ) {
		$this->set_optionData(
			$this->validate_settings(
				VAA_API::set_array_data( $this->get_settings(), $val, $key, $append ),
				'global'
			),
			'settings',
			true
		);
	}

	/**
	 * Set the user settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_userSettings( $val, $key = null, $append = false ) {
		$this->set_userMeta(
			$this->validate_settings(
				VAA_API::set_array_data( $this->get_userSettings(), $val, $key, $append ),
				'user'
			),
			'settings',
			true
		);
	}

	/**
	 * Set the plugin option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_optionData( $val, $key = null, $append = false ) {
		$this->optionData = VAA_API::set_array_data( $this->optionData, $val, $key, $append );
	}

	/**
	 * Set the user metadata.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_userMeta( $val, $key = null, $append = false ) {
		$this->userMeta = VAA_API::set_array_data( $this->userMeta, $val, $key, $append );
	}

	/**
	 * Store the option data.
	 * @param   bool  $network  Is network option?
	 * @since   1.7.5
	 */
	protected function store_optionData( $network = false ) {
		$this->set_for_network( $network );

		if ( $this->is_for_network() ) {
			$this->set_optionData( get_site_option( $this->get_optionKey() ) );
		} else {
			$this->set_optionData( get_option( $this->get_optionKey() ) );
		}
	}

	/**
	 * Store the user meta.
	 * @since   1.7.5
	 * @param   int   $user_id  The user ID this metadata is for.
	 * @param   bool  $single NOT SUPPORTED YET!
	 */
	protected function store_userMeta( $user_id, $single = true ) {
		if ( ! is_int( $user_id ) ) {
			return;
		}
		$this->for_user = $user_id;
		$this->set_userMeta( get_user_meta( $this->for_user, $this->get_userMetaKey(), true ) );
	}

	/**
	 * Update the plugin option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  bool
	 */
	public function update_optionData( $val, $key = null, $append = false ) {
		$this->set_optionData( $val, $key, $append );

		if ( $this->is_for_network() ) {
			return update_site_option( $this->get_optionKey(), $this->get_optionData() );
		}
		return update_option( $this->get_optionKey(), $this->get_optionData() );
	}

	/**
	 * Update the user metadata.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  bool
	 */
	public function update_userMeta( $val, $key = null, $append = false ) {
		$this->set_userMeta( $val, $key, $append );
		return update_user_meta( $this->for_user, $this->get_userMetaKey(), $this->get_userMeta() );
	}

	/**
	 * Set whether this instance if for a network option.
	 * @since   1.7.5
	 * @param   bool  $bool
	 */
	protected function set_for_network( $bool ) {
		$this->for_network = (bool) $bool;
	}

	/**
	 * Set whether this instance if for a network option.
	 * @since   1.7.5
	 * @return  bool
	 */
	public function is_for_network() {
		return (bool) $this->for_network;
	}

} // End class VAA_View_Admin_As_Settings.
