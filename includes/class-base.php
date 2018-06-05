<?php
/**
 * View Admin As - Class Base
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Base class that gets the VAA data from the main class.
 * Use this class as an extender for other classes.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.5.0
 * @since   1.7.3  Renamed from `VAA_View_Admin_As_Class_Base`.
 * @version 1.8.0
 */
abstract class VAA_View_Admin_As_Base
{
	/**
	 * View Admin As object.
	 *
	 * @since  1.5.0
	 * @var    \VAA_View_Admin_As
	 */
	protected $vaa = null;

	/**
	 * View Admin As store object.
	 *
	 * @since  1.6.0
	 * @var    \VAA_View_Admin_As_Store
	 */
	protected $store = null;

	/**
	 * Custom capabilities.
	 *
	 * @since  1.6.0
	 * @var    string[]
	 */
	protected $capabilities = array();

	/**
	 * Construct function.
	 * Protected to make sure it isn't declared elsewhere.
	 *
	 * @since   1.5.3
	 * @since   1.6.0  `$vaa` param.
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  (optional) Pass VAA object.
	 */
	protected function __construct( $vaa = null ) {
		// Load resources.
		$this->load_vaa( $vaa );
	}

	/**
	 * Init function to store data from the main class and enable functionality based on the current view.
	 *
	 * @since   1.5.0
	 * @since   1.6.0  `$vaa` param.
	 * @access  public
	 * @final
	 * @param   \VAA_View_Admin_As  $vaa  (optional) Pass VAA object.
	 * @return  void
	 */
	final public function load_vaa( $vaa = null ) {
		$this->vaa = $vaa;
		if ( ! is_object( $vaa ) || 'VAA_View_Admin_As' !== get_class( $vaa ) ) {
			$this->vaa = view_admin_as();
		}
		if ( $this->vaa && 'VAA_View_Admin_As_Store' !== get_class( $this ) ) {
			$this->store = $this->vaa->store();
		}
	}

	/**
	 * Is the main functionality enabled?
	 *
	 * @since   1.5.0
	 * @access  public
	 * @final
	 * @return  bool
	 */
	final public function is_vaa_enabled() {
		return (bool) $this->vaa->is_enabled();
	}

	/**
	 * Check if the AJAX call is ok.
	 * Must always be used before AJAX data is processed.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @return  bool
	 */
	public function is_valid_ajax() {
		if ( defined( 'VAA_DOING_AJAX' ) && VAA_DOING_AJAX && $this->is_vaa_enabled() ) {
			return true;
		}
		return false;
	}

	/**
	 * Extender function for WP current_user_can().
	 * Also checks if VAA is enabled.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @param   string  $capability  (optional) The capability to check when the user isn't a super admin.
	 * @return  bool
	 */
	public function current_user_can( $capability = null ) {
		if ( $capability ) {
			return ( $this->is_vaa_enabled() && ( VAA_API::is_super_admin() || current_user_can( $capability ) ) );
		}
		return ( $this->is_vaa_enabled() && VAA_API::is_super_admin() );
	}

	/**
	 * Add capabilities.
	 * Used for the _vaa_add_capabilities hook.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @param   array  $caps  The capabilities.
	 * @return  string[]
	 */
	public function add_capabilities( $caps ) {
		foreach ( (array) $this->capabilities as $cap ) {
			$caps[ $cap ] = $cap;
		}
		return $caps;
	}

	/**
	 * Add a new action to this plugin hooks registry.
	 *
	 * @since   1.8.0
	 * @see     \VAA_View_Admin_As_Hooks::add_action()
	 * @inheritdoc
	 */
	public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		view_admin_as()->hooks()->add_action( $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to this plugin hooks registry.
	 *
	 * @since   1.8.0
	 * @see     \VAA_View_Admin_As_Hooks::add_filter()
	 * @inheritdoc
	 */
	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		view_admin_as()->hooks()->add_filter( $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Magic method to output a string if trying to use the object as a string.
	 *
	 * @since   1.5.1
	 * @access  public
	 * @return  string
	 */
	public function __toString() {
		return get_class( $this );
	}

	/**
	 * Magic method to keep the object from being cloned.
	 *
	 * @since   1.5.1
	 * @access  public
	 * @return  void
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
	 * @since   1.5.1
	 * @access  public
	 * @return  void
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
	 * @since   1.5.1
	 * @access  public
	 * @param   string  $method  The method name.
	 * @param   array   $args    The method arguments.
	 * @return  null
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

} // End class VAA_View_Admin_As_Class_Base.
