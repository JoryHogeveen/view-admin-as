<?php
/**
 * View Admin As - Class Module
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Base class for modules that use option data etc.
 * Use this class as an extender for VAA modules other than view types.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.5.0  (This was one class with VAA_View_Admin_As_Class_Base)
 * @version 1.8.0
 * @uses    \VAA_View_Admin_As_Base Extends class
 */
abstract class VAA_View_Admin_As_Module extends VAA_View_Admin_As_Base
{
	/**
	 * Option key.
	 *
	 * @since  1.5.0
	 * @var    string
	 */
	protected $optionKey = '';

	/**
	 * Option data.
	 *
	 * @since  1.5.0
	 * @var    mixed
	 */
	protected $optionData = false;

	/**
	 * Enable functionalities?
	 *
	 * @since  1.5.0
	 * @var    bool
	 */
	protected $enable = false;

	/**
	 * Script localization data.
	 *
	 * @since  1.6.0
	 * @var    array
	 */
	protected $scriptLocalization = array();

	/**
	 * Is enabled?
	 *
	 * @since   1.5.0
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
	 * @since   1.8.0  Make this method public.
	 * @access  public
	 * @param   bool  $bool       Enable or disable?
	 * @param   bool  $update_db  Do database update? (default true).
	 * @return  bool
	 */
	public function set_enable( $bool = false, $update_db = true ) {
		$success = true;
		if ( $update_db && $this->get_optionKey() ) {
			$success = $this->update_optionData( (bool) $bool, 'enable', true );
		}
		if ( $success ) {
			$this->enable = (bool) $bool;
		}
		return $success;
	}

	/**
	 * Helper function for ajax return data.
	 * Merges second param with data defaults.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @param   bool    $success  Success return.
	 * @param   array   $data     Array of detailed info.
	 * @param   string  $type     Notice type.
	 * @return  array
	 */
	public function ajax_data_return( $success, $data, $type = null ) {
		if ( ! is_string( $type ) ) {
			$type = ( $success ) ? 'success' : 'error';
		}
		$data = wp_parse_args( $data, array(
			'display' => 'notice',
			'type'    => $type,
		) );
		return array(
			'success' => (bool) $success,
			'data'    => $data,
		);
	}

	/**
	 * Helper function for ajax notice return data.
	 * Merges second param with data defaults.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @param   bool    $success  Success return.
	 * @param   array   $data     Array of detailed info.
	 * @param   string  $type     Notice type.
	 * @return  array
	 */
	public function ajax_data_notice( $success, $data, $type = null ) {
		$data['display'] = 'notice';
		return $this->ajax_data_return( $success, $data, $type );
	}

	/**
	 * Helper function for ajax popup return data.
	 * Merges second param with data defaults.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @param   bool    $success  Success return.
	 * @param   array   $data     Array of detailed info.
	 * @param   string  $type     Popup type.
	 * @return  array
	 */
	public function ajax_data_popup( $success, $data, $type = null ) {
		$data['display'] = 'popup';
		return $this->ajax_data_return( $success, $data, $type );
	}

	/**
	 * Simple data validation.
	 * Meant to be overwritten by subclass.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @param   null   $null  Null.
	 * @param   mixed  $data  The view data.
	 * @return  mixed
	 */
	public function validate_view_data( $null, $data = null ) {
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

} // End class VAA_View_Admin_As_Module.
