<?php
/**
 * View Admin As - Admin UI
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

if ( ! class_exists( 'VAA_View_Admin_As_Admin' ) ) {

/**
 * Admin UI hooks for View Admin As
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.6.4
 * @uses    VAA_View_Admin_As_Class_Base Extends class
 */
final class VAA_View_Admin_As_Admin extends VAA_View_Admin_As_Class_Base
{
	/**
	 * Plugin links.
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
	 * Construct function.
	 *
	 * @since   1.6
	 * @since   1.6.1  $vaa param
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( 'browse' === $this->store->get_userSettings( 'view_mode' ) ) {
			add_filter( 'user_row_actions', array( $this, 'filter_user_row_actions' ), 10, 2 );
		}
		add_action( 'wp_meta', array( $this, 'action_wp_meta' ) );
		add_action( 'plugin_row_meta', array( $this, 'action_plugin_row_meta' ), 10, 2 );
		add_filter( 'removable_query_args', array( $this, 'filter_removable_query_args' ) );
	}

	/**
	 * Filter function to add view-as links on user rows in users.php.
	 *
	 * @since   1.6
	 * @since   1.6.3   Check whether to place link + reset link for current user.
	 * @access  public
	 * @param   array    $actions  The existing actions.
	 * @param   WP_User  $user     The user object.
	 * @return  array
	 */
	public function filter_user_row_actions( $actions, $user ) {

		if ( is_network_admin() ) {
			$link = network_admin_url();
		} else {
			$link = admin_url();
		}

		if ( $user->ID === $this->store->get_curUser()->ID ) {
			// Add reset link if it is the current user and a view is selected.
			if ( $this->store->get_viewAs() ) {
				$link = VAA_API::get_reset_link( $link );
			} else {
				$link = false;
			}
		}
		elseif ( $this->store->get_userids( $user->ID ) ) {
			$params = array(
				'action'        => 'view_admin_as',
				'view_admin_as' => htmlentities( json_encode( array( 'user' => $user->ID ) ) ),
				'_vaa_nonce'    => $this->store->get_nonce( true ),
			);
			$link .= '?' . http_build_query( $params );
		} else {
			$link = false;
		}

		if ( $link ) {
			$actions['vaa_view'] = '<a href="' . $link . '">' . __( 'View as', VIEW_ADMIN_AS_DOMAIN ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Adds a 'View Admin As: Reset view' link to the Meta sidebar widget if the admin bar is hidden.
	 *
	 * @since   1.6.1
	 * @access  public
	 */
	public function action_wp_meta() {
		if ( ! is_admin_bar_showing() && $this->store->get_viewAs() ) {
			$link = __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ) . ': ' . __( 'Reset view', VIEW_ADMIN_AS_DOMAIN );
			$url = VAA_API::get_reset_link();
			echo '<li id="vaa_reset_view"><a href="' . esc_url( $url ) . '">' . esc_html( $link ) . '</a></li>';
		}
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @since   1.6.1
	 * @param   array   $links  The existing links.
	 * @param   string  $file   The plugin file.
	 * @return  array
	 */
	public function action_plugin_row_meta( $links, $file ) {
		if ( VIEW_ADMIN_AS_BASENAME === $file ) {
			foreach ( $this->get_links() as $id => $link ) {
				$links[ $id ] = '<a href="' . esc_url( $link['url'] ) . '" target="_blank">' . esc_html( $link['title'] ) . '</a>';
			}
		}
		return $links;
	}

	/**
	 * Plugin links.
	 *
	 * @since   1.6.1
	 * @since   1.6.2  Added Slack channel link
	 * @return  array
	 */
	public function get_links() {
		if ( empty( $this->links ) ) {
			$this->links = array(
				'support' => array(
					'title' => __( 'Support', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Need support?', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-testimonial',
					'url'   => 'https://wordpress.org/support/plugin/view-admin-as/',
				),
				'slack' => array(
					'title' => __( 'Slack', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Quick help via Slack', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-format-chat',
					'url'   => 'https://keraweb.slack.com/messages/plugin-vaa/',
				),
				'review' => array(
					'title' => __( 'Review', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Give 5 stars on WordPress.org!', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-star-filled',
					'url'   => 'https://wordpress.org/support/plugin/view-admin-as/reviews/',
				),
				'translate' => array(
					'title' => __( 'Translate', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Help translating this plugin!', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-translation',
					'url'   => 'https://translate.wordpress.org/projects/wp-plugins/view-admin-as',
				),
				'issue' => array(
					'title' => __( 'Report issue', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Have ideas or a bug report?', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-lightbulb',
					'url'   => 'https://github.com/JoryHogeveen/view-admin-as/issues',
				),
				'docs' => array(
					'title' => __( 'Documentation', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Documentation', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-book-alt',
					'url'   => 'https://github.com/JoryHogeveen/view-admin-as/wiki',
				),
				'github' => array(
					'title' => __( 'GitHub', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Follow development on GitHub', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-editor-code',
					'url'   => 'https://github.com/JoryHogeveen/view-admin-as/tree/dev',
				),
				'donate' => array(
					'title' => __( 'Donate', VIEW_ADMIN_AS_DOMAIN ),
					'description' => __( 'Buy me a coffee!', VIEW_ADMIN_AS_DOMAIN ),
					'icon'  => 'dashicons-smiley',
					'url'   => 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest',
				),
			);
		}

		return $this->links;
	}

	/**
	 * Filter the list of query arguments which get removed from admin area URLs in WordPress.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @link    https://core.trac.wordpress.org/ticket/23367
	 *
	 * @param   array  $args  List of removable query arguments.
	 * @return  array         Updated list of removable query arguments.
	 */
	public function filter_removable_query_args( $args ) {
		return array_merge( $args, array(
			'reset-view',
			'reset-all-views',
			'view_admin_as',
			'_vaa_nonce',
		) );
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
	 * @return  VAA_View_Admin_As_Admin
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' === get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $caller );
			}
			return self::$_instance;
		}
		return null;
	}

} // end class.

} // end if class_exists.
