<?php
/**
 * View Admin As - Class Module
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * Base class for modules that use option data etc.
 * Use this class as an extender for VAA modules other than view types.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.5
 * @version 1.6.x
 */
abstract class VAA_View_Admin_As_Module extends VAA_View_Admin_As_Class_Base
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
	 * Script localization data.
	 *
	 * @since  1.6
	 * @var    array
	 */
	protected $scriptLocalization = array();

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
	 * Simple data validation.
	 * Meant to be overwritten by subclass.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   null   $null  Null.
	 * @param   mixed  $data  The view data.
	 * @return  mixed
	 */
	public function validate_view_data( $null, $data ) {
		if ( $data ) {
			return $data;
		}
		return $null;
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
	 * Set the class localisation strings
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 */
	protected function set_scriptLocalization( $val, $key = null, $append = false ) {
		$this->scriptLocalization = (array) VAA_API::set_array_data( $this->scriptLocalization, $val, $key, $append );
	}

	/**
	 * Set the option key as used in the options table.
	 * @param   string  $val  Option key.
	 */
	protected function set_optionKey( $val ) {
		$this->optionKey = (string) $val;
	}

	/**
	 * Set the class option data.
	 * @param   mixed   $val     Data.
	 * @param   string  $key     (optional) Data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 */
	protected function set_optionData( $val, $key = null, $append = false ) {
		$this->optionData = VAA_API::set_array_data( $this->optionData, $val, $key, $append );
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

} // end class.