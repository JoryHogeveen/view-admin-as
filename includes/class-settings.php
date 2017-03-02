<?php
/**
 * View Admin As - Class Settings
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * Settings class that stores the VAA settings for use
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.x
 * @version 1.6.x
 */
class VAA_View_Admin_As_Settings {

	/**
	 * The main VAA settings instance.
	 *
	 * @since  1.6.x
	 * @static
	 * @var    VAA_View_Admin_As_Settings
	 */
	private static $_vaa_instance = null;

	/**
	 * Database option key.
	 * Always starts with `vaa_`.
	 * Keys are parsed with underscores as spacing.
	 *
	 * @since  1.4
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    string
	 */
	protected $optionKey = null;

	/**
	 * Database option data.
	 *
	 * @since  1.4
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    array
	 */
	protected $optionData = array();

	/**
	 * User meta key for settings ans views.
	 * Always starts with `vaa-`.
	 * Keys are parsed with dashes as spacing.
	 *
	 * @since  1.3.4
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    bool
	 */
	protected $userMetaKey = null;

	/**
	 * User meta value for settings ans views.
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    array
	 */
	protected $userMeta = array();

	/**
	 * Array of default settings.
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    array
	 */
	protected $defaultSettings = array();

	/**
	 * Array of allowed settings.
	 *
	 * @since  1.5
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    array
	 */
	protected $allowedSettings = array();

	/**
	 * Array of default settings.
	 *
	 * @since  1.5
	 * @since  1.5.2  Added force_group_users.
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.1  Added freeze_locale.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    array
	 */
	protected $defaultUserSettings = array();

	/**
	 * Array of allowed settings.
	 * Setting name (key) => array( values ).
	 *
	 * @since  1.5
	 * @since  1.5.2  Added force_group_users.
	 * @since  1.6    Moved to this class from main class.
	 * @since  1.6.1  Added freeze_locale.
	 * @since  1.6.x  Moved to this class from store class.
	 * @var    array
	 */
	protected $allowedUserSettings = array();

	/**
	 * Sets the default data.
	 * @since   1.6.x
	 * @access  protected
	 * @param   string  $id  Identifier for this settings instance.
	 */
	protected function __construct( $id ) {

		if ( empty( $id ) || ! is_string( $id ) ) {
			return null;
		}

		$default = array();
		$allowed = array();

		$default_user = array();
		$allowed_user = array();

		if ( 'VAA_View_Admin_As_Store' === get_class( $this ) && null === self::$_vaa_instance ) {

			self::$_vaa_instance = $this;

			$this->set_optionKey( 'vaa_view_admin_as' );
			$this->set_optionData( array(
				'db_version',
			) );

			$this->set_userMetaKey( 'vaa-view-admin-as' );
			$this->set_userMeta( array(
				'settings',
				'views',
			) );

			$default_user = array(
				'admin_menu_location' => 'top-secondary',
				'force_group_users'   => 'no',
				'freeze_locale'       => 'no',
				'hide_front'          => 'no',
				'view_mode'           => 'browse',
			);
			$allowed_user = array(
				'admin_menu_location' => array( 'top-secondary', 'my-account' ),
				'force_group_users'   => array( 'yes', 'no' ),
				'freeze_locale'       => array( 'yes', 'no' ),
				'hide_front'          => array( 'yes', 'no' ),
				'view_mode'           => array( 'browse', 'single' ),
			);

			// @todo Remove?
			add_filter( 'view_admin_as_validate_view_data_setting', array( $this, 'filter_validate_settings' ), 10, 3 );
			add_filter( 'view_admin_as_validate_view_data_user_setting', array( $this, 'filter_validate_settings' ), 10, 3 );

			add_filter( 'view_admin_as_handle_ajax_setting', array( $this, 'filter_store_settings' ), 10, 3 );
			add_filter( 'view_admin_as_handle_ajax_user_setting', array( $this, 'filter_store_settings' ), 10, 3 );

			// Make identifier empty for the filters.
			$id = '';

		} else {

			if ( 'view-admin-as' === sanitize_title_with_dashes( $id ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						__( 'The setting key %1$s is reserved for class %2$s', VIEW_ADMIN_AS_DOMAIN ),
						$id, 'VAA_View_Admin_As_Store'
					),
					''
				);
				return;
			}

			$this->set_optionKey( 'vaa_' . $id );
			$this->set_userMetaKey( 'vaa-' . $id );

			// Append underscore to the identifier for the filters.
			$id = '_' . $id;
		}

		/**
		 * Set the default global settings.
		 *
		 * @since  1.6.x
		 * @param  array
		 * @return array
		 */
		$this->set_defaultSettings( apply_filters( 'vaa_view_admin_as_default_global_settings' . $id, $default ) );

		/**
		 * Set the allowed global settings.
		 *
		 * @since  1.6.x
		 * @param  array {
		 *     Settings array (key = setting name).
		 *     @type  array  Array of allowed values.
		 * }
		 * @return array
		 */
		$this->set_allowedSettings( apply_filters( 'vaa_view_admin_as_allowed_global_settings' . $id, $allowed ) );

		/**
		 * Set the default settings for users.
		 *
		 * @since  1.6.x
		 * @param  array
		 * @return array
		 */
		$this->set_defaultUserSettings( apply_filters( 'vaa_view_admin_as_default_user_settings' . $id, $default_user ) );

		/**
		 * Set the allowed settings for users.
		 *
		 * @since  1.6.x
		 * @param  array {
		 *     Settings array (key = setting name).
		 *     @type  array  Array of allowed values.
		 * }
		 * @return array
		 */
		$this->set_allowedUserSettings( apply_filters( 'vaa_view_admin_as_allowed_user_settings' . $id, $allowed_user ) );

	}

	/**
	 * Validate hook for settings.
	 *
	 * @since   1.6.x
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
	 * @since   1.6.x
	 * @param   null    $null  Default return (invalid).
	 * @param   mixed   $data  The view data.
	 * @param   string  $key   The data key.
	 * @return  mixed
	 */
	public function filter_store_settings( $null, $data, $key ) {
		if ( ! empty( $data ) && ! empty( $key ) ) {
			if ( 'setting' === $key ) {
				return $this->store_settings( $data, 'global' );
			}
			if ( 'user_setting' === $key ) {
				return $this->store_settings( $data, 'user' );
			}
		}
		return $null;
	}

	/**
	 * Validate setting data based on allowed settings.
	 * Will also merge with the default settings unless third $merge parameter is false.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @since   1.6.x  Moved to this class from store class. Added third $merge parameter.
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
			// Only pass the settings if the key and value matched the data in the allowed settings
			if ( ! array_key_exists( $setting, $allowed ) || ! in_array( $value, $allowed[ $setting ], true ) ) {
				unset( $settings[ $setting ] );
			}
		}
		return $settings;
	}

	/**
	 * Store settings based on allowed settings.
	 * Also merges with the default settings.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @since   1.6.x  Moved to this class from store class.
	 * @access  public
	 *
	 * @param   array   $settings  The new settings.
	 * @param   string  $type      The type of settings (global / user).
	 * @return  bool
	 */
	public function store_settings( $settings, $type ) {
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
	 * @since   1.6.x
	 * @param   array  $settings  The new settings
	 * @param   array  $defaults  The default settings
	 * @param   array  $allowed   The allowed settings
	 * @return  array
	 */
	public function parse_settings( $settings, $defaults, $allowed ) {
		$settings = wp_parse_args( $settings, $defaults );
		foreach ( $settings as $setting => $value ) {
			if ( ! array_key_exists( $setting, $allowed ) ) {
				// We don't have such a setting.
				unset( $settings[ $setting ] );
			} elseif ( ! in_array( $value, $allowed[ $setting ], true ) ) {
				// Set it to default.
				$settings[ $setting ] = $defaults[ $setting ];
			}
		}
		return $settings;
	}

	/**
	 * Delete or reset all View Admin As metadata for this user.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @since   1.6.2  Option to remove the VAA metadata for all users.
	 * @since   1.6.x  Moved to this class from store class.
	 * @access  public
	 *
	 * @param   int|string  $user_id     ID of the user being deleted/removed (pass `all` for all users).
	 * @param   object      $user        User object provided by the wp_login hook.
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
		// No user or metadata found, no deletion needed
		return true;
	}

	/**
	 * Delete or reset all View Admin As metadata for all users.
	 *
	 * @since   1.6.x
	 * @access  public
	 *
	 * @see    https://developer.wordpress.org/reference/classes/wpdb/update/
	 * @see    https://developer.wordpress.org/reference/classes/wpdb/delete/
	 *
	 * @global  wpdb  $wpdb
	 * @param   bool  $reset_only  Only reset (not delete) the user meta.
	 * @return  bool
	 */
	public function delete_all_user_meta( $reset_only = true ) {
		global $wpdb;
		if ( $reset_only ) {
			// Reset
			return (bool) $wpdb->update(
				$wpdb->usermeta, // table.
				array( 'meta_value', false ), // data.
				array( 'meta_key' => $this->get_userMetaKey() ) // where.
			);
		} else {
			// Delete
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
	 */
	public function set_optionKey( $val ) {
		$this->optionKey = (string) str_replace( array( ' ', '-' ), '_', sanitize_title_with_dashes( $val ) );
	}

	/**
	 * Set the option key as used in the options table.
	 * @param   string  $val  Option key.
	 */
	public function set_userMetaKey( $val ) {
		$this->userMetaKey = (string) sanitize_title_with_dashes( $val );
	}

	/**
	 * Set the default settings.
	 * @param   array  $val  Settings.
	 * @return  void
	 */
	public function set_defaultSettings( $val ) {
		$this->defaultSettings = array_map( 'strval', (array) $val );
	}

	/**
	 * Set the default user settings.
	 * @param   array  $val  Settings.
	 * @return  void
	 */
	public function set_defaultUserSettings( $val ) {
		$this->defaultUserSettings = array_map( 'strval', (array) $val );
	}

	/**
	 * Set the allowed settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_allowedSettings( $val, $key = null, $append = false ) {
		$this->allowedSettings = VAA_API::set_array_data( $this->allowedSettings, $val, $key, $append );
	}

	/**
	 * Set the allowed user settings.
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	public function set_allowedUserSettings( $val, $key = null, $append = false ) {
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
	 * Update the plugin option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  bool
	 */
	public function update_optionData( $val, $key = null, $append = false ) {
		$this->set_optionData( $val, $key, $append );
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
		return update_user_meta( get_current_user_id(), $this->get_userMetaKey(), $this->get_userMeta() );
	}

}
