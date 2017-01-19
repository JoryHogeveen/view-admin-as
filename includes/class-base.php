<?php
/**
 * View Admin As - Class Base
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * Base class that gets the VAA data from the main class
 * Use this class as an extender for other classes
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.5
 * @version 1.6.3
 */
abstract class VAA_View_Admin_As_Class_Base
{
	/**
	 * Option key.
	 *
	 * @since  1.5
	 * @var    string
	 */
	protected $optionKey = '';

	/**
	 * Option data.
	 *
	 * @since  1.5
	 * @var    mixed
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
	 * Custom capabilities.
	 *
	 * @since  1.6
	 * @var    array
	 */
	protected $capabilities = array();

	/**
	 * View Admin As object.
	 *
	 * @since  1.5
	 * @var    VAA_View_Admin_As
	 */
	protected $vaa = null;

	/**
	 * View Admin As store object.
	 *
	 * @since  1.6
	 * @var    VAA_View_Admin_As_Store
	 */
	protected $store = null;

	/**
	 * Script localization data.
	 *
	 * @since  1.6
	 * @var    array
	 */
	protected $scriptLocalization = array();

	/**
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.5.3
	 * @since   1.6    $vaa param.
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  (optional) Pass VAA object.
	 */
	protected function __construct( $vaa = null ) {
		// Load resources
		$this->load_vaa( $vaa );
	}

	/**
	 * init function to store data from the main class and enable functionality based on the current view.
	 *
	 * @since   1.5
	 * @since   1.6    $vaa param.
	 * @access  public
	 * @param   VAA_View_Admin_As  $vaa  (optional) Pass VAA object.
	 * @return  void
	 */
	final public function load_vaa( $vaa = null ) {
		$this->vaa = $vaa;
		if ( ! is_object( $vaa ) || 'VAA_View_Admin_As' !== get_class( $vaa ) ) {
			$this->vaa = View_Admin_As( $this );
		}
		if ( $this->vaa ) {
			$this->store = $this->vaa->store();
		}
	}

	/**
	 * Is the main functionality enabled?
	 *
	 * @since   1.5
	 * @access  public
	 * @return  bool
	 */
	final public function is_vaa_enabled() {
		return (bool) $this->vaa->is_enabled();
	}

	/**
	 * Is enabled?
	 *
	 * @since   1.5
	 * @access  public
	 * @return  bool
	 */
	public function is_enabled() {
		return (bool) $this->enable;
	}

	/**
	 * Set plugin enabled true/false.
	 *
	 * @since   1.5.1
	 * @since   1.6.2  Make database update optional.
	 * @access  protected
	 * @param   bool  $bool    Enable or disable?
	 * @param   bool  $update  Do database update? (default true).
	 * @return  bool
	 */
	protected function set_enable( $bool = false, $update = true ) {
		$success = true;
		if ( $update && $this->get_optionKey() ) {
			$success = $this->update_optionData( (bool) $bool, 'enable', true );
		}
		if ( $success ) {
			$this->enable = (bool) $bool;
		}
		return $success;
	}

	/**
	 * Add capabilities.
	 * Used for the _vaa_add_capabilities hook.
	 *
	 * @since   1.6
	 * @access  public
	 * @param   array  $caps  The capabilities.
	 * @return  array
	 */
	public function add_capabilities( $caps ) {
		foreach ( (array) $this->capabilities as $cap ) {
			$caps[ $cap ] = $cap;
		}
		return $caps;
	}

	/**
	 * Get the option key as used in the options table.
	 * @return  string
	 */
	public function get_optionKey() {
		return (string) $this->optionKey;
	}

	/**
	 * Get the class option data.
	 * @param   string  $key  (optional) Data key.
	 * @return  mixed
	 */
	public function get_optionData( $key = null ) {
		return VAA_API::get_array_data( $this->optionData, $key );
	}

	/**
	 * Get the class localisation strings
	 * @param   string  $key  (optional) Data key.
	 * @return  mixed
	 */
	public function get_scriptLocalization( $key = null ) {
		return VAA_API::get_array_data( $this->scriptLocalization, $key );
	}

	/**
	 * Set the option key as used in the options table.
	 * @param   string  $val  Option key.
	 * @return  string
	 */
	protected function set_optionKey( $val ) {
		$this->optionKey = (string) $val;
	}

	/**
	 * Set the class option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	protected function set_optionData( $val, $key = null, $append = false ) {
		$this->optionData = VAA_API::set_array_data( $this->optionData, $val, $key, $append );
	}

	/**
	 * Set the class localisation strings
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	protected function set_scriptLocalization( $val, $key = null, $append = false ) {
		$this->scriptLocalization = VAA_API::set_array_data( $this->scriptLocalization, $val, $key, $append );
	}

	/**
	 * Update the class option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  bool
	 */
	protected function update_optionData( $val, $key = null, $append = false ) {
		$this->set_optionData( $val, $key, $append );
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
			esc_html( get_class( $this ) . ': ' . __( 'This class does not want to be cloned', VIEW_ADMIN_AS_DOMAIN ) ),
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
			esc_html( get_class( $this ) . ': ' . __( 'This class does not want to wake up', VIEW_ADMIN_AS_DOMAIN ) ),
			null
		);
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that does not exist.
	 *
	 * @since  1.5.1
	 * @access public
	 * @param  string  $method  The method name.
	 * @param  array   $args    The method arguments.
	 * @return null
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong(
			esc_html( get_class( $this ) . "::{$method}" ),
			esc_html__( 'Method does not exist.', VIEW_ADMIN_AS_DOMAIN ),
			null
		);
		unset( $method, $args );
		return null;
	}

} // end class.
