<?php
/**
 * View Admin As - Class Base
 *
 * Base class that gets the VAA data from the main class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.5
 * @version 1.6
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

abstract class VAA_View_Admin_As_Class_Base
{
	/**
	 * Option key
	 *
	 * @since  1.5
	 * @var    string|bool
	 */
	protected $optionKey = false;

	/**
	 * Option data
	 *
	 * @since  1.5
	 * @var    array|bool
	 */
	protected $optionData = false;

	/**
	 * Enable functionalities?
	 *
	 * @since  1.5
	 * @var    bool
	 */
	protected $enable = false;

	/**
	 * Custom capabilities
	 *
	 * @since  1.6
	 * @var    array
	 */
	protected $capabilities = array();

	/**
	 * View Admin As object
	 *
	 * @since  1.5
	 * @var    object|bool
	 */
	protected $vaa = false;

	/**
	 * View Admin As store object
	 *
	 * @since  1.6
	 * @var    object|bool
	 */
	protected $store = false;

	/**
	 * Script localization data
	 *
	 * @since  1.6
	 * @var    array
	 */
	protected $scriptLocalization = array();

	/**
	 * Construct function
	 * Protected to make sure it isn't declared elsewhere
	 *
	 * @since   1.5.3
	 * @param   VAA_View_Admin_As  $vaa  (optional) Pass VAA object
	 * @access  protected
	 */
	protected function __construct( $vaa = null ) {
		// Load resources
		$this->load_vaa( $vaa );
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view
	 *
	 * @since   1.5
	 * @access  public
	 * @param   VAA_View_Admin_As
	 * @return  void
	 */
	final public function load_vaa( $vaa ) {
		$this->vaa = $vaa;
		if ( null == $vaa ) {
			$this->vaa = View_Admin_As( $this );
		}
		$this->store = $this->vaa->store();
	}

	/**
	 * Is the main class enabled? (for other classes)
	 *
	 * @since   1.5
	 * @access  public
	 * @return  bool
	 */
	final public function is_vaa_enabled() { return (bool) $this->vaa->is_enabled(); }

	/**
	 * Is enabled? (for other classes)
	 *
	 * @since   1.5
	 * @access  public
	 * @return  bool
	 */
	final public function is_enabled() { return (bool) $this->enable; }

	/**
	 * Set plugin enabled true/false
	 *
	 * @since   1.5.1
	 * @access  protected
	 * @param   bool
	 * @return  bool
	 */
	protected function set_enable( $bool = false ) {
		$success = $this->update_optionData( $bool, 'enable', true );
		if ( $success ) {
			$this->enable = $bool;
		}
		return $success;
	}

	/**
	 * Add capabilities
	 * Used for the _vaa_add_capabilities hook
	 *
	 * @since   1.6
	 * @access  public
	 * @param   array  $caps
	 * @return  array
	 */
	public function add_capabilities( $caps ) {
		foreach ( $this->capabilities as $cap ) {
			$caps[ $cap ] = $cap;
		}
		return $caps;
	}

	/*
	 * VAA Store Getters
	 * Make sure that you've called vaa_init(); BEFORE using these functions!
	 */
	protected function get_curUser()                           { return $this->store->get_curUser(); }
	protected function get_curUserSession()                    { return $this->store->get_curUserSession(); }
	protected function get_viewAs( $key = false )              { return $this->store->get_viewAs( $key ); }
	protected function get_caps( $key = false )                { return $this->store->get_caps( $key ); }
	protected function get_roles( $key = false )               { return $this->store->get_roles( $key ); }
	protected function get_users( $key = false )               { return $this->store->get_users( $key ); }
	protected function get_selectedUser()                      { return $this->store->get_selectedUser(); }
	protected function get_userids()                           { return $this->store->get_userids(); }
	protected function get_usernames()                         { return $this->store->get_usernames(); }
	protected function get_settings( $key = false )            { return $this->store->get_settings( $key ); }
	protected function get_userSettings( $key = false )        { return $this->store->get_userSettings( $key ); }
	protected function get_defaultSettings( $key = false )     { return $this->store->get_defaultSettings( $key ); }
	protected function get_allowedSettings( $key = false )     { return $this->store->get_allowedSettings( $key ); }
	protected function get_defaultUserSettings( $key = false ) { return $this->store->get_defaultUserSettings( $key ); }
	protected function get_allowedUserSettings( $key = false ) { return $this->store->get_allowedUserSettings( $key ); }
	protected function get_version()                           { return $this->store->get_version(); }
	protected function get_dbVersion()                         { return $this->store->get_dbVersion(); }

	/*
	 * VAA Getters
	 * Make sure that you've called vaa_init(); BEFORE using these functions!
	 */
	protected function get_modules( $key = false ) { return $this->vaa->get_modules( $key ); }

	/*
	 * Native Getters
	 */
	public function get_optionKey()                        { return (string) $this->optionKey; }
	public function get_optionData( $key = false )         { return VAA_API::get_array_data( $this->optionData, $key ); }
	public function get_scriptLocalization( $key = false ) { return VAA_API::get_array_data( $this->scriptLocalization, $key ); }

	/*
	 * Native Setters
	 */
	protected function set_optionKey( $var ) { $this->optionKey = (string) $var; }
	protected function set_optionData( $var, $key = false, $append = false ) {
		$this->optionData = VAA_API::set_array_data( $this->optionData, $var, $key, $append );
	}
	protected function set_scriptLocalization( $var, $key = false, $append = false ) {
		$this->scriptLocalization = VAA_API::set_array_data( $this->scriptLocalization, $var, $key, $append );
	}

	/*
	 * Update
	 */
	protected function update_optionData( $var, $key = false, $append = false ) {
		$this->set_optionData( $var, $key, $append );
		return update_option( $this->get_optionKey(), $this->optionData );
	}

	/**
	 * Magic method to output a string if trying to use the object as a string.
	 *
	 * @since  1.5.1
	 * @access public
	 * @return string
	 */
	public function __toString() {
		return get_class( $this );
	}

	/**
	 * Magic method to keep the object from being cloned.
	 *
	 * @since  1.5.1
	 * @access public
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			get_class( $this ) . ': ' . esc_html__( 'This class does not want to be cloned', 'view-admin-as' ),
			null
		);
	}

	/**
	 * Magic method to keep the object from being unserialized.
	 *
	 * @since  1.5.1
	 * @access public
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			get_class( $this ) . ': ' . esc_html__( 'This class does not want to wake up', 'view-admin-as' ),
			null
		);
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that does not exist.
	 *
	 * @since  1.5.1
	 * @access public
	 * @param  string
	 * @param  array
	 * @return null
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong(
			get_class( $this ) . "::{$method}",
			esc_html__( 'Method does not exist.', 'view-admin-as' ),
			null
		);
		unset( $method, $args );
		return null;
	}

} // end class
