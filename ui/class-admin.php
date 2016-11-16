<?php
/**
 * View Admin As - Admin UI
 *
 * Admin UI hooks for View Admin As
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.6
 * @version 1.6.1
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

if ( ! class_exists( 'VAA_View_Admin_As_Static_Actions' ) ) {

final class VAA_View_Admin_As_Admin extends VAA_View_Admin_As_Class_Base
{
	/**
	 * Plugin links
	 *
	 * @since  1.6.1
	 * @var    array
	 */
	private $links = array();

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
	 * @since   1.6.1  $vaa param
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( $this->store->get_userSettings('view_mode') == 'browse' ) {
			add_filter( 'user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );
		}
		add_action( 'wp_meta', array( $this, 'action_wp_meta' ) );
		add_action( 'plugin_row_meta', array( $this, 'action_plugin_row_meta' ), 10, 2 );
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
	 * @since   1.6.1
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
	 * Show row meta on the plugin screen
	 *
	 * @since   1.6.1
	 * @param   array   $links
	 * @param   string  $file
	 * @return  array
	 */
	public function action_plugin_row_meta( $links, $file ) {
		if ( $file == VIEW_ADMIN_AS_BASENAME ) {
			foreach ( $this->get_links() as $id => $link ) {
				$links[ $id ] = '<a href="' . esc_url( $link['url'] ) . '" target="_blank">' . esc_html( $link['title'] ) . '</a>';
			}
		}
		return $links;
	}

	/**
	 * Plugin links
	 *
	 * @since   1.6.1
	 * @return  array
	 */
	public function get_links() {
		if ( empty( $this->links ) ) {
			$this->links = array(
				'support' => array(
					'title' => __( 'Support', 'view-admin-as' ),
					'description' => __( 'Need support?', 'view-admin-as' ),
					'icon'  => 'dashicons-testimonial',
					'url'   => 'https://wordpress.org/support/plugin/view-admin-as/',
				),
				'review' => array(
					'title' => __( 'Review', 'view-admin-as' ),
					'description' => __( 'Give 5 stars on WordPress.org!', 'view-admin-as' ),
					'icon'  => 'dashicons-star-filled',
					'url'   => 'https://wordpress.org/support/plugin/view-admin-as/reviews/',
				),
				'translate' => array(
					'title' => __( 'Translate', 'view-admin-as' ),
					'description' => __( 'Help translating this plugin!', 'view-admin-as' ),
					'icon'  => 'dashicons-translation',
					'url'   => 'https://translate.wordpress.org/projects/wp-plugins/view-admin-as',
				),
				'issue' => array(
					'title' => __( 'Report issue', 'view-admin-as' ),
					'description' => __( 'Have ideas or a bug report?', 'view-admin-as' ),
					'icon'  => 'dashicons-lightbulb',
					'url'   => 'https://github.com/JoryHogeveen/view-admin-as/issues',
				),
				'docs' => array(
					'title' => __( 'Documentation', 'view-admin-as' ),
					'description' => __( 'Documentation', 'view-admin-as' ),
					'icon'  => 'dashicons-book-alt',
					'url'   => 'https://github.com/JoryHogeveen/view-admin-as/wiki',
				),
				'github' => array(
					'title' => __( 'GitHub', 'view-admin-as' ),
					'description' => __( 'Follow development on GitHub', 'view-admin-as' ),
					'icon'  => 'dashicons-editor-code',
					'url'   => 'https://github.com/JoryHogeveen/view-admin-as/tree/dev',
				),
				'donate' => array(
					'title' => __( 'Donate', 'view-admin-as' ),
					'description' => __( 'Buy me a coffee!', 'view-admin-as' ),
					'icon'  => 'dashicons-smiley',
					'url'   => 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest',
				)
			);
		}

		return $this->links;
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Admin
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' == get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $caller );
			}
			return self::$_instance;
		}
		return null;
	}

} // end class

}
