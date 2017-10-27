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
	require_once( ABSPATH . WPINC . '/class-wp-admin-bar.php' );
}

if ( class_exists( 'WP_Admin_Bar' ) ) {

/**
 * Toolbar UI for View Admin As.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.7.4
 * @see     wp-includes/class-wp-admin-bar.php
 * @uses    \WP_Admin_Bar Extends class
 */
final class VAA_View_Admin_As_Toolbar extends WP_Admin_Bar
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_Toolbar
	 */
	private static $_instance = null;

	/**
	 * Is this toolbar being rendered?
	 *
	 * @since  1.6
	 * @static
	 * @var    bool
	 */
	public static $showing = false;

	/**
	 * View Admin As store.
	 *
	 * @since  1.6
	 * @var    VAA_View_Admin_As_Store
	 */
	private $vaa_store = null;

	/**
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.6
	 * @since   1.6.1  $vaa param
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		$this->vaa_store = view_admin_as()->store();

		if ( ! is_admin() ) {
			add_action( 'vaa_view_admin_as_init', array( $this, 'vaa_init' ) );
		}
	}

	/**
	 * Init function that initializes this plugin after the main VAA class is loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @see     'vaa_view_admin_as_init' action
	 * @return  void
	 */
	public function vaa_init() {
		add_action( 'init', array( $this, 'vaa_toolbar_init' ) );
	}

	/**
	 * Init function for the toolbar.
	 *
	 * @since   1.6
	 * @access  public
	 * @return  void
	 */
	public function vaa_toolbar_init() {

		// @since  1.6.2  Check for customizer preview.
		if ( ! is_admin_bar_showing() && ! is_customize_preview()
		     && ( ! $this->vaa_store->get_userSettings( 'hide_front' ) || $this->vaa_store->get_view() )
		) {

			self::$showing = true;

			wp_enqueue_script( 'admin-bar' );
			wp_enqueue_style( 'admin-bar' );

			add_action( 'wp_footer', array( $this, 'vaa_toolbar_render' ), 100 );
		}
	}

	/**
	 * Render our toolbar using the render function from WP_Admin_bar.
	 *
	 * @since   1.6
	 * @access  public
	 * @return  void
	 */
	public function vaa_toolbar_render() {

		$this->add_group( array(
			'id'     => 'top-secondary',
			'meta'   => array(
				'class' => 'ab-top-secondary',
			),
		) );

		// Load our admin bar nodes and force the location.
		do_action( 'vaa_toolbar_menu', $this, 'top-secondary' );

		/**
		 * Add classes to the toolbar menu (front only).
		 * @since   1.6
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
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  $this  VAA_View_Admin_As_Toolbar
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Toolbar.

} // End if().
