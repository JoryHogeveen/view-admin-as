<?php
/**
 * View Admin As - Class Init (Main class)
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * Plugin initializer class.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   0.1
 * @version 1.7
 */
final class VAA_View_Admin_As
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.4.1
	 * @static
	 * @var    VAA_View_Admin_As
	 */
	private static $_instance = null;

	/**
	 * Enable functionalities for this user?
	 *
	 * @since  0.1
	 * @var    bool
	 */
	private $enable = false;

	/**
	 * Var that holds all the notices.
	 *
	 * @since  1.5.1
	 * @var    array
	 */
	private $notices = array();

	/**
	 * VAA Store.
	 *
	 * @since  1.6
	 * @var    VAA_View_Admin_As_Store
	 */
	private $store = null;

	/**
	 * VAA Controller.
	 *
	 * @since  1.6
	 * @var    VAA_View_Admin_As_Controller
	 */
	private $controller = null;

	/**
	 * VAA View handler.
	 *
	 * @since  1.6
	 * @var    VAA_View_Admin_As_View
	 */
	private $view = null;

	/**
	 * VAA UI classes that are loaded.
	 *
	 * @since  1.5
	 * @see    load_ui()
	 * @var    array of objects
	 */
	private $ui = array();

	/**
	 * Other VAA modules that are loaded.
	 *
	 * @since  1.4
	 * @see    load_modules()
	 * @see    register_module()
	 * @var    array of objects
	 */
	private $modules = array();

	/**
	 * Init function to register plugin hook.
	 * Private to make sure it isn't declared elsewhere.
	 *
	 * @since   0.1
	 * @since   1.3.3   changes init hook to plugins_loaded for theme compatibility.
	 * @since   1.4.1   creates instance.
	 * @since   1.5     make private.
	 * @since   1.5.1   added notice on class name conflict + validate versions.
	 * @access  private
	 */
	private function __construct() {
		self::$_instance = $this;

		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'do_admin_notices' ) );
		}

		// Returns false on conflict.
		if ( ! (boolean) $this->validate_versions() ) {
			return;
		}

		if ( (boolean) $this->load() ) {

			// Lets start!
			add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

		} else {

			$this->add_notice( 'class-error-core', array(
				'type' => 'notice-error',
				'message' => '<strong>' . __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ) . ':</strong> '
					. __( 'Plugin not loaded because of a conflict with an other plugin or theme', VIEW_ADMIN_AS_DOMAIN )
					. ' <code>(' . sprintf( __( 'Class %s already exists', VIEW_ADMIN_AS_DOMAIN ), 'VAA_View_Admin_As_Class_Base' ) . ')</code>',
			) );

		}
	}

	/**
	 * Load required classes and files.
	 * Returns false on conflict.
	 *
	 * @since   1.6
	 * @return  bool
	 */
	private function load() {

		$classes = array(
			'VAA_API',
			'VAA_View_Admin_As_Class_Base',
			'VAA_View_Admin_As_Settings',
			'VAA_View_Admin_As_Store',
			'VAA_View_Admin_As_Controller',
			'VAA_View_Admin_As_View',
			'VAA_View_Admin_As_Update',
			'VAA_View_Admin_As_Compat',
			'VAA_View_Admin_As_Module',
		);

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				return false;
			}
		}

		require( VIEW_ADMIN_AS_DIR . 'includes/class-api.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-base.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-settings.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-store.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-controller.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-view.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-update.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-compat.php' );
		require( VIEW_ADMIN_AS_DIR . 'includes/class-module.php' );

		return true;
	}

	/**
	 * Instantiate function that checks if the plugin is already loaded.
	 *
	 * @since   1.6
	 * @access  public
	 */
	public function init() {
		static $done;

		if ( ! $done ) {
			$this->run();
			$done = true;
		}
	}

	/**
	 * Run the plugin!
	 * Check current user, load necessary data and register all used hooks.
	 *
	 * @since   0.1
	 * @access  private
	 * @return  void
	 */
	private function run() {

		// We can't do this check before `plugins_loaded` hook.
		if ( ! is_user_logged_in() ) {
			return;
		}

		$this->store      = VAA_View_Admin_As_Store::get_instance( $this );
		$this->controller = VAA_View_Admin_As_Controller::get_instance( $this );
		$this->view       = VAA_View_Admin_As_View::get_instance( $this );

		$this->store->init();

		// Sets enabled.
		$this->validate_user();

		$this->load_modules();

		// Check if a database update is needed.
		VAA_View_Admin_As_Update::get_instance( $this )->maybe_db_update();

		if ( $this->is_enabled() ) {

			if ( VAA_View_Admin_As_Update::$fresh_install ) {
				$this->welcome_notice();
			}

			// Fix some compatibility issues, more to come!
			VAA_View_Admin_As_Compat::get_instance( $this )->init();

			$this->store->store_caps();
			$this->store->store_roles();
			$this->store->store_users();

			$this->controller->init();
			$this->view->init();

			$this->load_ui();

			/**
			 * Init is finished. Hook is used for other classes related to View Admin As.
			 *
			 * @since  1.5
			 * @param  VAA_View_Admin_As  $this  The main View Admin As object.
			 */
			do_action( 'vaa_view_admin_as_init', $this );

		}
	}

	/**
	 * Validate if the current user has access to the functionalities.
	 * Sets enabled if user passes validation.
	 *
	 * @since   0.1    Check if the current user had administrator rights (is_super_admin).
	 *                 Disable plugin functions for network admin pages.
	 * @since   1.4    Make sure we have a session for the current user.
	 * @since   1.5.1  If a user has the correct capability (view_admin_as + edit_users) this plugin is also enabled, use with care.
	 *                 Note that in network installations the non-admin user also needs the manage_network_users
	 *                 capability (of not the edit_users will return false).
	 * @since   1.5.3  Enable on network pages for superior admins.
	 * @since   1.6.3  Created this function.
	 * @access  private
	 */
	private function validate_user() {
		if ( ( VAA_API::is_super_admin()
		       || ( current_user_can( 'view_admin_as' ) && current_user_can( 'edit_users' ) ) )
		     && ( ! is_network_admin() || VAA_API::is_superior_admin( $this->store->get_curUser()->ID ) )
		     && $this->store->get_curUserSession()
		) {
			$this->enable = true;
		}
	}

	/**
	 * Helper function to include files. Checks class existence and throws an error if needed.
	 * Also adds the class to a supplied group if available.
	 *
	 * @since   1.7
	 * @access  private
	 * @param   array  $includes
	 * @param   array  $group
	 * @return  array  $group
	 */
	private function include_files( $includes, &$group = null ) {

		$group = (array) $group;

		foreach ( $includes as $key => $inc ) {
			if ( empty( $inc['class'] ) || ! class_exists( $inc['class'] ) ) {
				include( VIEW_ADMIN_AS_DIR . $inc['file'] );
				if ( ! empty( $inc['class'] ) && is_callable( array( $inc['class'], 'get_instance' ) ) ) {
					$group[ $key ] = call_user_func( array( $inc['class'], 'get_instance' ), $this );
				}
			} else {
				$this->add_notice( 'class-error-' . $key, array(
					'type' => 'notice-error',
					'message' => '<strong>' . __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ) . ':</strong> '
					             . __( 'Plugin not fully loaded because of a conflict with an other plugin or theme', VIEW_ADMIN_AS_DOMAIN )
					             . ' <code>(' . sprintf( __( 'Class %s already exists', VIEW_ADMIN_AS_DOMAIN ), $inc['class'] ) . ')</code>',
				) );
			}
		}
		return $group;
	}

	/**
	 * Load the user interface.
	 *
	 * @since   1.5
	 * @since   1.5.1   added notice on class name conflict.
	 * @since   1.6     added our toolbar class.
	 * @access  private
	 * @return  void
	 */
	private function load_ui() {

		$includes = array(
			'ui' => array(
				'file'  => 'ui/class-ui.php',
				'class' => 'VAA_View_Admin_As_UI',
			),
			'admin_bar' => array(
				'file'  => 'ui/class-admin-bar.php',
				'class' => 'VAA_View_Admin_As_Admin_Bar',
			),
			'toolbar' => array(
				'file'  => 'ui/class-toolbar.php',
				'class' => 'VAA_View_Admin_As_Toolbar',
			),
		);

		// Include UI files and add them to the `ui` property.
		$this->include_files( $includes, $this->ui );
	}

	/**
	 * Load the modules.
	 *
	 * @since   1.5
	 * @access  private
	 * @return  void
	 */
	private function load_modules() {

		$includes = array(
			'role_defaults' => array(
				'file'  => 'modules/class-role-defaults.php',
				'class' => 'VAA_View_Admin_As_Role_Defaults',
			),
			'role_manager' => array(
				'file'  => 'modules/class-role-manager.php',
				'class' => 'VAA_View_Admin_As_Role_Manager',
			),
		);

		if ( is_callable( array( 'RUA_App', 'instance' ) ) ) {
			$includes['rua_level'] = array(
				'file'  => 'modules/class-restrict-user-access.php',
				'class' => 'VAA_View_Admin_As_RUA',
			);
		}

		// Run include code but do not register modules yet (leave that to the modules).
		$this->include_files( $includes );

		/**
		 * Modules loaded. Hook is used for other modules related to View Admin As.
		 *
		 * @since  1.6.2
		 * @param  VAA_View_Admin_As  $this  The main View Admin As object.
		 */
		do_action( 'vaa_view_admin_as_modules_loaded', $this );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since   1.2
	 * @since   1.6    Hooked into init hook, check for is_enabled() required.
	 * @access  public
	 * @return  void
	 */
	public function load_textdomain() {

		if ( $this->is_enabled() ) {

			/**
			 * Keep the third parameter pointing to the languages folder within this plugin
			 * to enable support for custom .mo files.
			 *
			 * @todo look into 4.6 changes Maybe the same can be done in an other way
			 * @see https://make.wordpress.org/core/2016/07/06/i18n-improvements-in-4-6/
			 */
			load_plugin_textdomain( 'view-admin-as', false, VIEW_ADMIN_AS_DIR . 'languages/' );

			/**
			 * Frontend translation of roles is not working by default (Darn you WordPress!).
			 * Needs to be in init action to work.
			 * @see  https://core.trac.wordpress.org/ticket/37539
			 */
			if ( ! is_admin() ) {
				load_textdomain( 'default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo' );
			}
		}
	}

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
	 * Get the store class.
	 *
	 * @since   1.6
	 * @access  public
	 * @return  VAA_View_Admin_As_Store
	 */
	public function store() {
		return $this->store;
	}

	/**
	 * Get the controller class.
	 *
	 * @since   1.7
	 * @access  public
	 * @return  VAA_View_Admin_As_Controller
	 */
	public function controller() {
		return $this->controller;
	}

	/**
	 * Get the view class.
	 *
	 * @since   1.6
	 * @access  public
	 * @return  VAA_View_Admin_As_View
	 */
	public function view() {
		return $this->view;
	}

	/**
	 * Get UI classes.
	 * If a key is provided it will only return that UI class.
	 *
	 * @since   1.6.1
	 * @access  public
	 * @param   string  $key  (optional) UI class name.
	 * @return  array|object
	 */
	public function get_ui( $key = null ) {
		return VAA_API::get_array_data( $this->ui, $key );
	}

	/**
	 * Get current modules.
	 * If a key is provided it will only return that module.
	 *
	 * @since   1.5
	 * @access  public
	 * @param   string  $key  (optional) The module key.
	 * @return  array|object
	 */
	public function get_modules( $key = null ) {
		return VAA_API::get_array_data( $this->modules, $key );
	}

	/**
	 * Register extra modules.
	 *
	 * @since   1.6.1
	 * @param   array  $data {
	 *     Required. An array of module info.
	 *     @type  string  $id        The module name, choose wisely since this is used for validation.
	 *     @type  object  $instance  The module class reference/instance.
	 * }
	 * @return  bool
	 */
	public function register_module( $data ) {
		if (    ! empty( $data['id'] )       && is_string( $data['id'] )
		     && ! empty( $data['instance'] ) && is_object( $data['instance'] )
		) {
			$this->modules[ $data['id'] ] = $data['instance'];
			return true;
		}
		return false;
	}

	/**
	 * Add a welcome notice for new users
	 *
	 * @since   1.7
	 * @access  private
	 */
	private function welcome_notice() {
		$this->add_notice( 'vaa-welcome', array(
			'type' => 'notice-success',
			'message' => '<strong>' . __( 'Thank you for installing View Admin As!', VIEW_ADMIN_AS_DOMAIN ) . '</strong> '
	            . sprintf(
	                // Translators: %s stands for `Dashboard` (link element).
	                __( 'For the best experience you can start from the %s since not all views are allowed to access all admin pages.', VIEW_ADMIN_AS_DOMAIN ),
					'<a class="button button-primary" href="' . admin_url() . '">' . __( 'Dashboard' ) . '</a>'
				),
		) );
	}

	/**
	 * Add notices to generate.
	 *
	 * @since   1.5.1
	 * @access  public
	 *
	 * @param   string  $id
	 * @param   array   $notice {
	 *     Required array.
	 *     @type  string  $type     The WP notice type class(es).
	 *     @type  string  $message  The notice message.
	 * }
	 * @return  void
	 */
	public function add_notice( $id, $notice ) {
		if ( isset( $notice['type'] ) && ! empty( $notice['message'] ) ) {
			$this->notices[ $id ] = array(
				'type' => $notice['type'],
				'message' => $notice['message'],
			);
		}
	}

	/**
	 * Echo admin notices.
	 *
	 * @since   1.5.1
	 * @access  public
	 * @see     'admin_notices'
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	 * @return  void
	 */
	public function do_admin_notices() {
		foreach ( $this->notices as $notice ) {
			if ( isset( $notice['type'] ) && ! empty( $notice['message'] ) ) {
				echo '<div class="' . $notice['type'] . ' notice is-dismissible"><p>' . $notice['message'] . '</p></div>';
			}
		}
	}

	/**
	 * Validate plugin activate.
	 * Checks for valid resources.
	 *
	 * @since   1.5.1
	 * @since   1.6    Returns conflict status.
	 * @access  private
	 * @global  string  $wp_version  WordPress version.
	 * @return  bool
	 */
	private function validate_versions() {
		global $wp_version;
		// Start positive!
		$valid = true;

		// Validate WP
		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			$this->add_notice( 'wp-version', array(
				'type' => 'notice-error',
				'message' => __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ) . ': '
				             // Translators, %1$s stands for "WordPress", %2$s stands for version 3.5
				             . sprintf( __( 'Plugin deactivated, %1$s version %2$s or higher is required', VIEW_ADMIN_AS_DOMAIN ), 'WordPress', '3.5' ),
			) );
			$valid = false;
		}

		if ( ! $valid ) {
			// Too bad..
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
		}

		return $valid;
	}

	/**
	 * Main View Admin As instance.
	 * Ensures only one instance of View Admin As is loaded or can be loaded.
	 *
	 * @since   1.4.1
	 * @access  public
	 * @static
	 * @see     View_Admin_As()
	 * @return  VAA_View_Admin_As
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Magic method to output a string if trying to use the object as a string.
	 *
	 * @since   1.5
	 * @access  public
	 * @return  string
	 */
	public function __toString() {
		return get_class( $this );
	}

	/**
	 * Magic method to keep the object from being cloned.
	 *
	 * @since   1.5
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
	 * @since   1.5
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
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since   1.5
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

} // end class
