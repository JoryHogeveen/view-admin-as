<?php
/**
 * View Admin As - Toolbar UI
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( ! class_exists( 'WP_Admin_Bar' ) && file_exists( ABSPATH . WPINC . '/class-wp-admin-bar.php' ) ) {
	require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
}

if ( class_exists( 'WP_Admin_Bar' ) ) {

/**
 * Toolbar UI for View Admin As.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.0
 * @version 1.8.0
 * @see     wp-includes/class-wp-admin-bar.php
 * @uses    \WP_Admin_Bar Extends class
 */
final class VAA_View_Admin_As_Toolbar extends WP_Admin_Bar
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6.0
	 * @static
	 * @var    \VAA_View_Admin_As_Toolbar
	 */
	private static $_instance = null;

	/**
	 * Is this toolbar being rendered?
	 *
	 * @since  1.6.0
	 * @static
	 * @var    bool
	 */
	public static $showing = false;

	/**
	 * View Admin As store.
	 *
	 * @since  1.6.0
	 * @var    \VAA_View_Admin_As_Store
	 */
	private $vaa_store = null;

	/**
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.6.0
	 * @since   1.6.1  `$vaa` param.
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		$this->vaa_store = view_admin_as()->store();

		view_admin_as()->hooks()->add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
	}

	/**
	 * Init function that initializes this plugin after the main VAA class is loaded.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @see     'vaa_view_admin_as_init' action
	 * @return  void
	 */
	public function vaa_init() {
		// @since  1.7.6  Changed hook from `init` to `wp_loaded` (later).
		view_admin_as()->hooks()->add_action( 'wp_loaded', array( $this, 'vaa_toolbar_init' ) );
	}

	/**
	 * Init function for the toolbar.
	 *
	 * @since   1.6.0
	 * @since   1.6.2  Check for customizer preview.
	 * @since   1.7.6  Add customizer support by only enabling it in the container, not the preview window.
	 * @access  public
	 * @return  void
	 */
	public function vaa_toolbar_init() {
		// Stop if the admin bar is already showing or we're in the customizer preview window.
		if ( is_admin_bar_showing() || ( ! is_admin() && is_customize_preview() ) ) {
			return;
		}

		if (
			( is_customize_preview() && ! $this->vaa_store->get_userSettings( 'hide_customizer' ) )
			|| ( ! is_admin() && ! $this->vaa_store->get_userSettings( 'hide_front' ) )
			|| $this->vaa_store->get_view()
		) {

			self::$showing = true;

			view_admin_as()->hooks()->add_action( 'wp_footer', array( $this, 'vaa_toolbar_render' ), 100 );
			view_admin_as()->hooks()->add_action( 'customize_controls_print_footer_scripts', array( $this, 'vaa_toolbar_render' ), 100 );
		}
	}

	/**
	 * Render our toolbar using the render function from WP_Admin_bar.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @return  void
	 */
	public function vaa_toolbar_render() {

		$this->add_group( array(
			'id'   => 'top-secondary',
			'meta' => array(
				'class' => 'ab-top-secondary',
			),
		) );

		// Load our admin bar nodes and force the location.
		do_action( 'vaa_toolbar_menu', $this, 'top-secondary' );

		/**
		 * Add classes to the toolbar menu (front only).
		 * @since   1.6.0
		 * @param   array  $array  Empty array.
		 * @return  array
		 */
		$toolbar_classes = apply_filters( 'view_admin_as_toolbar_classes', array() );
		echo '<div id="vaa_toolbar" class="' . esc_attr( implode( ' ', $toolbar_classes ) ) . '">';

		$this->render();

		echo '</div>';
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
	 * @return  \VAA_View_Admin_As_Toolbar  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Toolbar.

} // End if().
