<?php
/**
 * View Admin As - Class Update
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Update class used for version control and updates.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.7.4
 * @uses    VAA_View_Admin_As_Base Extends class
 */
final class VAA_View_Admin_As_Update extends VAA_View_Admin_As_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_Update
	 */
	private static $_instance = null;

	/**
	 * Is this a new installation?
	 *
	 * @since  1.7
	 * @static
	 * @var    bool
	 */
	public static $fresh_install = false;

	/**
	 * Populate the instance.
	 *
	 * @since   1.6
	 * @since   1.6.1  $vaa param.
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );
	}

	/**
	 * Check the correct DB version in the DB.
	 *
	 * @since   1.4
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 * @return  void
	 */
	public function maybe_db_update() {
		$db_version = strtolower( $this->store->get_optionData( 'db_version' ) );
		if ( empty( $db_version ) ) {
			self::$fresh_install = true;
		}
		if ( self::$fresh_install || version_compare( $db_version, $this->store->get_dbVersion(), '<' ) ) {
			$this->db_update();
		}
	}

	/**
	 * Update settings.
	 *
	 * @since   1.4
	 * @since   1.6    Moved to this class from main class.
	 * @access  private
	 * @return  void
	 */
	private function db_update() {
		$defaults = array(
			'db_version' => $this->store->get_dbVersion(),
		);

		$current_db_version = strtolower( $this->store->get_optionData( 'db_version' ) );

		// Clear the user views for update to 1.5+.
		if ( version_compare( $current_db_version, '1.5', '<' ) ) {
			/**
			 * Reset user meta for all users.
			 * @since  1.6.2  Use `all` param from delete_user_meta().
			 */
			$this->store->delete_user_meta( 'all', false, true ); // true for reset_only.
			// Reset currently loaded data.
			$this->store->set_userMeta( false );
		}

		if ( version_compare( $current_db_version, '1.7.2', '<' ) ) {
			$this->update_1_7_2();
		}

		// Update version, append if needed.
		$this->store->set_optionData( $this->store->get_dbVersion(), 'db_version', true );
		// Update option data.
		$this->store->update_optionData( wp_parse_args( $this->store->get_optionData(), $defaults ) );

		// Main update finished, hook used to update modules.
		do_action( 'vaa_view_admin_as_db_update' );
	}

	/**
	 * Update to version 1.7.2.
	 * Changes yes/no options to boolean types.
	 *
	 * @since   1.7.2
	 * @global  \wpdb  $wpdb
	 * @access  private
	 * @return  void
	 */
	private function update_1_7_2() {
		global $wpdb;

		$sql = 'SELECT * FROM ' . $wpdb->usermeta . ' WHERE meta_key = %s';
		// @codingStandardsIgnoreLine >> $wpdb->prepare(), check returning false error.
		$results = (array) $wpdb->get_results( $wpdb->prepare( $sql, 'vaa-view-admin-as' ) );

		foreach ( $results as $meta ) {
			if ( ! empty( $meta->meta_value ) ) {
				$value = maybe_unserialize( $meta->meta_value );
				if ( ! empty( $value['settings'] ) ) {
					foreach ( $value['settings'] as $key => $val ) {
						if ( in_array( $key, array( 'force_group_users', 'freeze_locale', 'hide_front' ), true ) ) {
							$value['settings'][ $key ] = ( 'yes' === $val ) ? true : false;
						}
					}
					update_user_meta( $meta->user_id, 'vaa-view-admin-as', $value );
				}
			}
		}

		// Re-init VAA store.
		$this->store->init( true );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  $this  VAA_View_Admin_As_Update
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Update.
