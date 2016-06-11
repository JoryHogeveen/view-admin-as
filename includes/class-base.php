<?php
/**
 * View Admin As - Class Base
 *
 * Base class that gets the VAA data from the main class
 * 
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @version 1.5.2.1
 */
 
! defined( 'ABSPATH' ) and die( 'You shall not pass!' );

abstract class VAA_View_Admin_As_Class_Base 
{
	/**
	 * Option key
	 *
	 * @since  1.5 
	 * @var    string
	 */
	protected $optionKey = false;

	/**
	 * Option data
	 *
	 * @since  1.5
	 * @var    array
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
	 * View Admin As object
	 *
	 * @since  1.5
	 * @var    object
	 */ 
	protected $vaa = false;

	/**
	 * init function to store data from the main class and enable functionality based on the current view
	 *
	 * @since   1.5
	 * @access  public
	 * @return  void
	 */
	final public function load_vaa() {
		$this->vaa = View_Admin_As();
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
	 * Get full array or array key
	 *
	 * @since   1.5
	 * @access  public
	 * @param   array   $array  The requested array
	 * @param   string  $key    Return only a key of the requested array (optional)
	 * @return  array|string
	 */
	final public function get_array_data( $array, $key = false ) {
		if ( $key ) {
			if ( isset( $array[ $key ] ) ) {
				return $array[ $key ];
			}
			return false; // return false if key is not found
		} else if ( isset( $array ) ) { // This could not be set
			return $array;
		}
		return false;
	}

	/**
	 * Set full array or array key
	 *
	 * @since   1.5
	 * @access  public
	 * @param   array   $array   Original array
	 * @param   mixed   $var     The new value
	 * @param   string  $key     The array key for the value (optional)
	 * @param   bool    $append  If the key doesn't exist in the original array, append it (optional)
	 * @return  array|string
	 */
	final public function set_array_data( $array, $var, $key = false, $append = false ) {
		if ( $key ) {
			if ( true === $append && ! is_array( $array ) ) {
				$array = array();
			}
			if ( true === $append || isset( $array[ $key ] ) ) {
				$array[ $key ] = $var;
				return $array;
			}
			return $array; // return no changes if key is not found or appeding is not allowed
			// Notify user if in debug mode
			if ( defined('WP_DEBUG') && true === WP_DEBUG ) {
				trigger_error('View Admin As: Key does not exist', E_USER_NOTICE);
				if ( ! defined('WP_DEBUG_DISPLAY') || ( defined('WP_DEBUG_DISPLAY') && true === WP_DEBUG_DISPLAY ) ) {
					debug_print_backtrace();
				}
			}
		}
		return $var;
	}

	/* 
	 * VAA Getters 
	 * Make sure that you've called vaa_init(); BEFORE using these functions!
	 */
	protected function get_curUser() { return $this->vaa->get_curUser(); }
	protected function get_curUserSession() { return $this->vaa->get_curUserSession(); }
	protected function get_viewAs( $key = false ) { return $this->vaa->get_viewAs( $key ); }
	protected function get_caps( $key = false ) { return $this->vaa->get_caps( $key ); }
	protected function get_roles( $key = false ) { return $this->vaa->get_roles( $key ); }
	protected function get_users( $key = false ) { return $this->vaa->get_users( $key ); }
	protected function get_selectedUser() { return $this->vaa->get_selectedUser(); }
	protected function get_userids() { return $this->vaa->get_userids(); }
	protected function get_usernames() { return $this->vaa->get_usernames(); }
	protected function get_version() { return $this->vaa->get_version(); }
	protected function get_dbVersion() { return $this->vaa->get_dbVersion(); }
	protected function get_modules( $key = false ) { return $this->vaa->get_modules( $key ); }
	protected function get_settings( $key = false ) { return $this->vaa->get_settings( $key ); }
	protected function get_userSettings( $key = false ) { return $this->vaa->get_userSettings( $key ); }
	protected function get_defaultSettings( $key = false ) { return $this->vaa->get_defaultSettings( $key ); }
	protected function get_allowedSettings( $key = false ) { return $this->vaa->get_allowedSettings( $key ); }
	protected function get_defaultUserSettings( $key = false ) { return $this->vaa->get_defaultUserSettings( $key ); }
	protected function get_allowedUserSettings( $key = false ) { return $this->vaa->get_allowedUserSettings( $key ); }

	/**
	 * Native Getters 
	 */
	protected function get_optionKey() { return (string) $this->optionKey; }
	protected function get_optionData( $key = false ) { return $this->get_array_data( $this->optionData, $key ); }

	/**
	 * Native Setters 
	 */
	protected function set_optionKey( $var ) { $this->optionKey = (string) $var; }
	protected function set_optionData( $var, $key = false, $append = false ) { $this->optionData = $this->set_array_data( $this->optionData, $var, $key, $append ); }

	/**
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
	 * @return void
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
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'view-admin-as' ), null );
	}

	/**
	 * Magic method to keep the object from being unserialized.
	 *
	 * @since  1.5.1
	 * @access public
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'view-admin-as' ), null );
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since  1.5.1
	 * @access public
	 * @return null
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong( get_class( $this ) . "::{$method}", esc_html__( 'Method does not exist.', 'view-admin-as' ), null );
		unset( $method, $args );
		return null;
	}

} // end class
