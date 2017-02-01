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
	 * Database option key.
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
	 * Sets the default data
	 * @access  protected
	 */
	protected function __construct() {

		$this->set_optionKey( 'vaa_view_admin_as' );
		$this->set_optionData( array(
			'db_version',
		) );

		$this->set_userMetaKey( 'vaa-view-admin-as' );
		$this->set_userMeta( array(
			'settings',
			'views',
		) );

		/**
		 * Set the default global settings
		 *
		 * @since  1.6.x
		 * @param  array
		 * @return array
		 */
		$this->set_defaultSettings( apply_filters( 'vaa_view_admin_as_default_global_settings', array() ) );

		/**
		 * Set the allowed global settings
		 *
		 * @since  1.6.x
		 * @param  array {
		 *     Settings array (key = setting name)
		 *     @type  array  Array of allowed values
		 * }
		 * @return array
		 */
		$this->set_allowedSettings( apply_filters( 'vaa_view_admin_as_allowed_global_settings', array() ) );

		/**
		 * Set the default settings for users
		 *
		 * @since  1.6.x
		 * @param  array
		 * @return array
		 */
		$this->set_defaultUserSettings( apply_filters( 'vaa_view_admin_as_default_user_settings', array(
			'admin_menu_location' => 'top-secondary',
			'force_group_users'   => 'no',
			'freeze_locale'       => 'no',
			'hide_front'          => 'no',
			'view_mode'           => 'browse',
		) ) );

		/**
		 * Set the allowed settings for users
		 *
		 * @since  1.6.x
		 * @param  array {
		 *     Settings array (key = setting name)
		 *     @type  array  Array of allowed values
		 * }
		 * @return array
		 */
		$this->set_allowedUserSettings( apply_filters( 'vaa_view_admin_as_allowed_user_settings', array(
			'admin_menu_location' => array( 'top-secondary', 'my-account' ),
			'force_group_users'   => array( 'yes', 'no' ),
			'freeze_locale'       => array( 'yes', 'no' ),
			'hide_front'          => array( 'yes', 'no' ),
			'view_mode'           => array( 'browse', 'single' ),
		) ) );

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
		foreach ( $settings as $setting => $value ) {
			// Only allow the settings when it exists in the defaults and the value exists in the allowed settings.
			if ( array_key_exists( $setting, $defaults ) && in_array( $value, $allowed[ $setting ], true ) ) {
				$current[ $setting ] = $value;
				// Some settings need a reset.
				if ( in_array( $setting, array( 'view_mode' ), true ) ) {
					view_admin_as( $this )->view()->reset_view();
				}
			}
		}
		if ( 'global' === $type ) {
			$new = $this->validate_settings( wp_parse_args( $current, $defaults ), 'global' );
			return $this->update_optionData( $new, 'settings', true );
		} elseif ( 'user' === $type ) {
			$new = $this->validate_settings( wp_parse_args( $current, $defaults ), 'user' );
			return $this->update_userMeta( $new, 'settings', true );
		}
		return false;
	}

	/**
	 * Validate setting data based on allowed settings.
	 * Also merges with the default settings.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @since   1.6.x  Moved to this class from store class.
	 * @access  public
	 *
	 * @param   array       $settings  The new settings.
	 * @param   string      $type      The type of settings (global / user).
	 * @return  array|bool  $settings / false
	 */
	public function validate_settings( $settings, $type ) {
		if ( 'global' === $type ) {
			$defaults = $this->get_defaultSettings();
			$allowed  = $this->get_allowedSettings();
		} elseif ( 'user' === $type ) {
			$defaults = $this->get_defaultUserSettings();
			$allowed  = $this->get_allowedUserSettings();
		} else {
			return false;
		}
		$settings = wp_parse_args( $settings, $defaults );
		foreach ( $settings as $setting => $value ) {
			if ( ! array_key_exists( $setting, $defaults ) ) {
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
		$this->optionKey = (string) $val;
	}

	/**
	 * Set the option key as used in the options table.
	 * @param   string  $val  Option key.
	 */
	public function set_userMetaKey( $val ) {
		$this->userMetaKey = (string) $val;
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
