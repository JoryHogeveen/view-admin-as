<?php
/**
 * View Admin As - Admin UI
 *
 * Admin UI hooks for View Admin As
 *
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.6
 * @version 1.6
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

if ( ! class_exists( 'VAA_View_Admin_As_Static_Actions' ) ) {

final class VAA_View_Admin_As_Admin extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_Admin
	 */
	private static $_instance = null;

	/**
	 * Construct function
	 *
	 * @since   1.6
	 * @access  protected
	 */
	protected function __construct() {
		self::$_instance = $this;
		parent::__construct();

		if ( $this->store->get_userSettings('view_mode') == 'browse' ) {
			add_filter( 'user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );
		}
		//add_action( 'wp_meta', array( $this, 'action_wp_meta' ) );
	}

	/**
	 * Filter function to add view-as links on user rows in users.php
	 *
	 * @since   1.6
	 * @access  public
	 * @param   array   $actions
	 * @param   object  $user  WP_User
	 * @return  array
	 */
	public function filter_user_row_actions( $actions, $user ) {
		$data = array( 'user' => $user->ID );

		if ( is_network_admin() ) {
			$link = network_admin_url();
		} else {
			$link = admin_url();
		}
		$params = array(
			'action'        => 'view_admin_as',
			'view_admin_as' => htmlentities( json_encode( $data ) ),
			'_vaa_nonce'    => $this->store->get_nonce( true )
		);
		$link .= '?' . http_build_query( $params );

		$actions['vaa_view'] = '<a href="' . $link . '">' . __( 'View as', 'view-admin-as' ) . '</a>';
		return $actions;
	}

	/**
	 * Adds a 'View Admin As: Reset view' link to the Meta sidebar widget if the admin bar is hidden
	 *
	 * @since   1.6
	 * @access  public
	 */
	public function action_wp_meta() {

		if ( ! is_admin_bar_showing() && $this->store->get_viewAs() ) {
			$link = __( 'View Admin As', 'view-admin-as' ) . ': ' . __( 'Reset view', 'view-admin-as' );
			$url = VAA_API::get_reset_link();
			echo '<li id="vaa_reset_view"><a href="' . esc_url( $url ) . '">' . esc_html( $link ) . '</a></li>';
		}
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   object  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Admin_Bar
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' == get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		return null;
	}

} // end class

}
