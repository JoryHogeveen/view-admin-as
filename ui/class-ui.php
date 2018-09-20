<?php
/**
 * View Admin As - Main UI class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * UI hooks for View Admin As.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.0
 * @since   1.7.0  Renamed from `VAA_View_Admin_As_Admin`.
 * @version 1.8.1
 * @uses    \VAA_View_Admin_As_Base Extends class
 */
final class VAA_View_Admin_As_UI extends VAA_View_Admin_As_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6.0
	 * @static
	 * @var    \VAA_View_Admin_As_UI
	 */
	private static $_instance = null;

	/**
	 * Plugin links.
	 *
	 * @since  1.6.1
	 * @var    array[]
	 */
	private $links = array();

	/**
	 * Construct function.
	 *
	 * @since   1.6.0
	 * @since   1.6.1  `$vaa` param.
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		$this->add_action( 'wp_meta', array( $this, 'action_wp_meta' ) );
		$this->add_action( 'plugin_row_meta', array( $this, 'action_plugin_row_meta' ), 10, 2 );
		$this->add_filter( 'removable_query_args', array( $this, 'filter_removable_query_args' ) );

		$this->add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		$this->add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// @since  1.7.6.1  Add scripts to the customizer container hook.
		$this->add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$this->add_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );

		/**
		 * Compat with front and WP version lower than 4.2.0.
		 * @since  1.6.4
		 * @link   https://developer.wordpress.org/reference/functions/wp_admin_canonical_url/
		 */
		if ( ! is_admin() || ! VAA_API::validate_wp_version( '4.2' ) ) {
			$this->add_action( 'wp_head', array( $this, 'remove_query_args' ) );
		}
	}

	/**
	 * Adds a 'View Admin As: Reset view' link to the Meta sidebar widget if the admin bar is hidden.
	 *
	 * @since   1.6.1
	 * @access  public
	 */
	public function action_wp_meta() {
		if ( ! VAA_API::is_toolbar_showing() && $this->store->get_view() ) {
			$link = __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ) . ': ' . __( 'Reset view', VIEW_ADMIN_AS_DOMAIN );
			$url  = VAA_API::get_reset_link();
			echo '<li id="vaa_reset_view"><a href="' . esc_url( $url ) . '">' . esc_html( $link ) . '</a></li>';
		}
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @since   1.6.1
	 * @see     \WP_Plugins_List_Table::single_row()
	 * @param   array[]  $links  The existing links.
	 * @param   string   $file   The plugin file.
	 * @return  array
	 */
	public function action_plugin_row_meta( $links, $file ) {
		if ( VIEW_ADMIN_AS_BASENAME === $file ) {
			$icon_attr = array(
				'style' => array(
					'font-size: inherit;',
					'line-height: inherit;',
					'display: inline;',
					'vertical-align: text-top;',
				),
			);
			foreach ( $this->get_links() as $id => $link ) {
				$title = VAA_View_Admin_As_Form::do_icon( $link['icon'], $icon_attr ) . ' ' . esc_html( $link['title'] );

				$links[ $id ] = '<a href="' . esc_url( $link['url'] ) . '" target="_blank">' . $title . '</a>';
			}
		}
		return $links;
	}

	/**
	 * Plugin links.
	 *
	 * @since   1.6.1
	 * @since   1.6.2  Added Slack channel link
	 * @return  array[]
	 */
	public function get_links() {
		if ( ! empty( $this->links ) ) {
			return $this->links;
		}

		$this->links = array(
			'support'   => array(
				'title'       => __( 'Support', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Need support?', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-sos',
				'url'         => 'https://wordpress.org/support/plugin/view-admin-as/',
			),
			'slack'     => array(
				'title'       => __( 'Slack', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Quick help via Slack', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-format-chat',
				'url'         => 'https://keraweb.slack.com/messages/plugin-vaa/',
			),
			'review'    => array(
				'title'       => __( 'Review', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Give 5 stars on WordPress.org!', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-star-filled',
				'url'         => 'https://wordpress.org/support/plugin/view-admin-as/reviews/',
			),
			'translate' => array(
				'title'       => __( 'Translate', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Help translating this plugin!', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-translation',
				'url'         => 'https://translate.wordpress.org/projects/wp-plugins/view-admin-as',
			),
			'issue'     => array(
				'title'       => __( 'Report issue', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Have ideas or a bug report?', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-lightbulb',
				'url'         => 'https://github.com/JoryHogeveen/view-admin-as/issues',
			),
			'docs'      => array(
				'title'       => __( 'Documentation', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Documentation', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-book-alt',
				'url'         => 'https://github.com/JoryHogeveen/view-admin-as/wiki',
			),
			'github'    => array(
				'title'       => __( 'GitHub', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Follow development on GitHub', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-editor-code',
				'url'         => 'https://github.com/JoryHogeveen/view-admin-as/tree/dev',
			),
			'donate'    => array(
				'title'       => __( 'Donate', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Buy me a coffee!', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-smiley',
				'url'         => 'https://www.keraweb.nl/donate.php?for=view-admin-as',
			),
			'plugins'   => array(
				'title'       => __( 'Plugins', VIEW_ADMIN_AS_DOMAIN ),
				'description' => __( 'Check out my other plugins', VIEW_ADMIN_AS_DOMAIN ),
				'icon'        => 'dashicons-admin-plugins',
				'url'         => 'https://profiles.wordpress.org/keraweb/#content-plugins',
			),
		);

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
	 * Remove query arguments from the url.
	 * Same logic as WP uses since v4.2.0.
	 *
	 * @since   1.6.4
	 * @see     wp_admin_canonical_url()
	 * @return  void
	 */
	public function remove_query_args() {
		$removable_query_args = $this->filter_removable_query_args( array() );

		if ( empty( $removable_query_args ) ) {
			return;
		}

		$request_uri = $_SERVER['REQUEST_URI'];
		// @since  1.7.6  Some plugins overwrite `REQUEST_URI` and set it to `ORIG_REQUEST_URI`.
		if ( ! empty( $_SERVER['ORIG_REQUEST_URI'] ) ) {
			$request_uri = $_SERVER['ORIG_REQUEST_URI'];
		}

		// Ensure we're using an absolute URL.
		$current_url  = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $request_uri );
		$filtered_url = remove_query_arg( $removable_query_args, $current_url );
		?>
		<link id="wp-vaa-canonical" rel="canonical" href="<?php echo esc_url( $filtered_url ); ?>" />
		<script>
			if ( window.history.replaceState ) {
				window.history.replaceState( null, null, document.getElementById( 'wp-vaa-canonical' ).href + window.location.hash );
			}
		</script>
		<?php
	}

	/**
	 * Add necessary scripts and styles.
	 *
	 * @since   0.1.0
	 * @since   1.7.0  Moved from `VAA_View_Admin_As`.
	 * @access  public
	 * @return  void
	 */
	public function enqueue_scripts() {
		// Only enqueue scripts if the admin bar is enabled otherwise they have no use.
		if ( ! VAA_API::is_toolbar_showing() ) {
			return;
		}

		// Use non-minified versions.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		// Prevent browser cache.
		$version = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : $this->store->get_version();

		wp_enqueue_style(
			'vaa_view_admin_as_style',
			VIEW_ADMIN_AS_URL . 'assets/css/view-admin-as' . $suffix . '.css',
			array( 'admin-bar' ),
			$version
		);
		wp_enqueue_script(
			'vaa_view_admin_as_script',
			VIEW_ADMIN_AS_URL . 'assets/js/view-admin-as' . $suffix . '.js',
			array( 'jquery', 'admin-bar' ),
			$version,
			true // load in footer.
		);

		/**
		 * Add data to the VAA script localization.
		 * @since   1.7.0
		 * @param   array  $array  Empty array (Will be overwritten with VAA core data so use unique keys).
		 * @return  array
		 */
		$script_localization = array_merge(
			(array) apply_filters( 'view_admin_as_script_localization', array() ),
			array(
				// Data.
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'siteurl'              => get_site_url(),
				'settings'             => $this->store->get_settings(),
				'settings_user'        => $this->store->get_userSettings(),
				'view'                 => $this->store->get_view(),
				'view_types'           => $this->vaa->controller()->get_view_types(),
				// Other.
				'_loader_icon'         => VIEW_ADMIN_AS_URL . 'assets/img/loader.gif',
				'_debug'               => ( defined( 'WP_DEBUG' ) ) ? (bool) WP_DEBUG : false,
				'_vaa_nonce'           => $this->store->get_nonce( true ),
				// i18n.
				'__no_users_found'     => esc_html__( 'No users found.', VIEW_ADMIN_AS_DOMAIN ),
				'__key_already_exists' => esc_html__( 'Key already exists.', VIEW_ADMIN_AS_DOMAIN ),
				'__success'            => esc_html__( 'Success', VIEW_ADMIN_AS_DOMAIN ),
				'__confirm'            => esc_html__( 'Are you sure?', VIEW_ADMIN_AS_DOMAIN ),
				'__download'           => esc_html__( 'Download', VIEW_ADMIN_AS_DOMAIN ),
			)
		);

		wp_localize_script( 'vaa_view_admin_as_script', 'VAA_View_Admin_As', $script_localization );
	}

	/**
	 * Add options to the access denied page when the user has selected a view and did something this view is not allowed.
	 *
	 * @since   1.3.0
	 * @since   1.5.1   Check for SSL (Moved to `VAA_API`).
	 * @since   1.6.0   More options and better description.
	 * @since   1.7.0   Moved from `VAA_View_Admin_As`.
	 * @since   1.8.0   Renamed from `die_handler()`.
	 * @access  public
	 * @see     wp_die()
	 *
	 * @param   callable  $callback  WP die callback.
	 * @return  callable  $callback  WP die callback.
	 */
	public function filter_wp_die_handler( $callback ) {

		// Only do something if a view is selected.
		if ( ! $this->store->get_view() ) {
			return $callback;
		}

		$options = array();

		if ( is_network_admin() ) {
			$options[] = array(
				'text' => __( 'Go to network dashboard', VIEW_ADMIN_AS_DOMAIN ),
				'url'  => network_admin_url(),
			);
		} else {
			$options[] = array(
				'text' => __( 'Go to dashboard', VIEW_ADMIN_AS_DOMAIN ),
				'url'  => admin_url(),
			);
			$options[] = array(
				'text' => __( 'Go to homepage', VIEW_ADMIN_AS_DOMAIN ),
				'url'  => get_bloginfo( 'url' ),
			);
		}

		// Reset url.
		$options[] = array(
			'text' => __( 'Reset the view', VIEW_ADMIN_AS_DOMAIN ),
			'url'  => VAA_API::get_reset_link(),
		);

		/**
		 * Add or remove options to the die/error handler pages.
		 *
		 * @since  1.6.2
		 * @param  array  $options {
		 *     Required array of arrays.
		 *     @type  array {
		 *         @type  string  $text  The text to show.
		 *         @type  string  $url   The link.
		 *     }
		 * }
		 * @return array[]
		 */
		$options = apply_filters( 'view_admin_as_error_page_options', $options );
		?>
		<div>
			<h3><?php esc_html_e( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ); ?>:</h3>
			<?php esc_html_e( 'The view you have selected is not permitted to access this page, please choose one of the options below.', VIEW_ADMIN_AS_DOMAIN ); ?>
			<ul>
			<?php foreach ( $options as $option ) { ?>
				<li><a href="<?php echo esc_attr( $option['url'] ); ?>"><?php echo esc_html( $option['text'] ); ?></a></li>
			<?php } ?>
			</ul>
		</div>
		<hr>
		<?php
		return $callback;
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class.
	 * @return  \VAA_View_Admin_As_UI  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_UI.
