<?php
/**
 * View Admin As - Class Init (Main class)
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Plugin initializer class.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   0.1.0
 * @version 1.8.2
 */
final class VAA_View_Admin_As
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.4.1
	 * @static
	 * @var    \VAA_View_Admin_As
	 */
	private static $_instance = null;

	/**
	 * Enable functionalities for this user?
	 *
	 * @since  0.1.0
	 * @var    bool
	 */
	private $enable = false;

	/**
	 * Var that holds all the notices.
	 *
	 * @since  1.5.1
	 * @var    array[] {
	 *     @type  string  $message  The notice message.
	 *     @type  string  $type     (optional) The WP notice type class(es).
	 * }
	 */
	private $notices = array();

	/**
	 * VAA Hooks.
	 *
	 * @since  1.8.0
	 * @var    \VAA_View_Admin_As_Hooks
	 */
	private $hooks = null;

	/**
	 * VAA Store.
	 *
	 * @since  1.6.0
	 * @var    \VAA_View_Admin_As_Store
	 */
	private $store = null;

	/**
	 * VAA Controller.
	 *
	 * @since  1.6.0
	 * @var    \VAA_View_Admin_As_Controller
	 */
	private $controller = null;

	/**
	 * VAA View handler.
	 *
	 * @since  1.6.0
	 * @var    \VAA_View_Admin_As_View
	 */
	private $view = null;

	/**
	 * VAA UI classes that are loaded.
	 *
	 * @since  1.5.0
	 * @see    \VAA_View_Admin_As::load_ui()
	 * @var    object[]
	 */
	private $ui = array();

	/**
	 * Other VAA modules that are loaded.
	 *
	 * @since  1.4.0
	 * @see    \VAA_View_Admin_As::load_modules()
	 * @see    \VAA_View_Admin_As::register_module()
	 * @var    \VAA_View_Admin_As_Module[]
	 */
	private $modules = array();

	/**
	 * View types.
	 *
	 * @since  1.8.0
	 * @see    \VAA_View_Admin_As::load_modules()
	 * @see    \VAA_View_Admin_As::register_view_type()
	 * @var    \VAA_View_Admin_As_Type[]
	 */
	private $view_types = array();

	/**
	 * Class registry.
	 *
	 * @since  1.8.0
	 * @var    array
	 */
	private $classes = array(
		'VAA_API'                      => 'includes/class-api.php',
		'VAA_View_Admin_As_Base'       => 'includes/class-base.php',
		'VAA_View_Admin_As_Hooks'      => 'includes/class-hooks.php',
		'VAA_View_Admin_As_Settings'   => 'includes/class-settings.php',
		'VAA_View_Admin_As_Store'      => 'includes/class-store.php',
		'VAA_View_Admin_As_Controller' => 'includes/class-controller.php',
		'VAA_View_Admin_As_View'       => 'includes/class-view.php',
		'VAA_View_Admin_As_Update'     => 'includes/class-update.php',
		'VAA_View_Admin_As_Compat'     => 'includes/class-compat.php',
		'VAA_View_Admin_As_Type'       => 'includes/class-type.php',
		'VAA_View_Admin_As_Module'     => 'includes/class-module.php',
		'VAA_View_Admin_As_Form'       => 'includes/class-form.php',
	);

	/**
	 * Init function to register plugin hook.
	 * Private to make sure it isn't declared elsewhere.
	 *
	 * @since   0.1.0
	 * @since   1.3.3   Changes init hook to plugins_loaded for theme compatibility.
	 * @since   1.4.1   Creates instance.
	 * @since   1.5.0   Make private.
	 * @since   1.5.1   Added notice on class name conflict + validate versions.
	 * @since   1.8.0   spl_autoload_register().
	 * @access  private
	 */
	private function __construct() {
		self::$_instance = $this;

		spl_autoload_register( array( $this, '_autoload' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'do_admin_notices' ) );
		}

		// Returns false on conflict.
		if ( ! $this->validate_versions() ) {
			return;
		}

		// Lets start!
		add_action( 'plugins_loaded', array( $this, 'init' ), -99999 );
	}

	/**
	 * Class autoloader if needed.
	 *
	 * @since   1.8.0
	 * @access  private
	 * @internal
	 * @param   string  $class  The class name.
	 */
	public function _autoload( $class ) {
		if ( 0 !== strpos( $class, 'VAA_' ) ) {
			return;
		}
		if ( isset( $this->classes[ $class ] ) ) {
			$this->include_file( VIEW_ADMIN_AS_DIR . $this->classes[ $class ], $class );
		}
	}

	/**
	 * Instantiate function that checks if the plugin is already loaded.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @param   bool  $redo  (optional) Force re-init?
	 */
	public function init( $redo = false ) {
		static $done = false;
		if ( $done && ! $redo ) return;

		// We can't do this check before `plugins_loaded` hook.
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! $done && ! $this->load() ) {
			return;
		}

		$this->run();

		$done = true;
	}

	/**
	 * Verify that our classes don't exist yet.
	 * Returns false on conflict.
	 *
	 * @since   1.6.0
	 * @access  private
	 * @return  bool  Load successfully completed?
	 */
	private function load() {

		foreach ( $this->classes as $class => $file ) {
			if ( ! $this->include_file( VIEW_ADMIN_AS_DIR . $file, $class ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Run the plugin!
	 * Check current user, load necessary data and register all used hooks.
	 *
	 * @since   0.1.0
	 * @access  private
	 * @return  void
	 */
	private function run() {

		$this->hooks      = new VAA_View_Admin_As_Hooks();
		$this->store      = VAA_View_Admin_As_Store::get_instance( $this );
		$this->controller = VAA_View_Admin_As_Controller::get_instance( $this );
		$this->view       = VAA_View_Admin_As_View::get_instance( $this );

		$this->set_enabled();

		$this->load_modules();

		// Check if a database update is needed.
		VAA_View_Admin_As_Update::get_instance( $this )->maybe_db_update();

		if ( $this->is_enabled() ) {

			if ( VAA_View_Admin_As_Update::$fresh_install ) {
				$this->welcome_notice();
			}

			// Third party compatibility.
			VAA_View_Admin_As_Compat::get_instance( $this )->init();

			/**
			 * Plugin enabled + update and compat scripts done.
			 *
			 * @since  1.8.0
			 * @param  \VAA_View_Admin_As  $this  The main View Admin As object instance.
			 */
			do_action( 'vaa_view_admin_as_pre_init', $this );

			$this->controller->init();
			$this->view->init();

			$this->load_ui();

			/**
			 * Init is finished. Hook is used for other classes related to View Admin As.
			 *
			 * @since  1.5.0
			 * @param  \VAA_View_Admin_As  $this  The main View Admin As object instance.
			 */
			do_action( 'vaa_view_admin_as_init', $this );

		}
	}

	/**
	 * Try to enable plugin functionality.
	 *
	 * @since   1.7.2
	 * @access  public
	 * @return  bool
	 */
	public function set_enabled() {
		$this->enable = $this->validate_user();
		return $this->is_enabled();
	}

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
	 * Validate if the current user has access to the functionalities.
	 *
	 * @since   0.1.0  Check if the current user had administrator rights (is_super_admin).
	 *                 Disable plugin functions for network admin pages.
	 * @since   1.4.0  Make sure we have a session for the current user.
	 * @since   1.5.1  If a user has the correct capability (view_admin_as + edit_users) this plugin is also enabled, use with care.
	 *                 Note that in network installations the non-admin user also needs the manage_network_users
	 *                 capability (of not the edit_users will return false).
	 * @since   1.5.3  Enable on network pages for superior admins.
	 * @since   1.6.3  Created this function.
	 * @since   1.8.2  Refactor (simplify) + remove check for user session.
	 * @access  public
	 *
	 * @return  bool
	 */
	public function validate_user() {

		if ( is_network_admin() ) {
			$valid = VAA_API::is_superior_admin( $this->store->get_curUser()->ID );
		} else {
			$valid = (
				VAA_API::is_super_admin()
				|| ( current_user_can( 'view_admin_as' ) && current_user_can( 'edit_users' ) )
			);
		}

		//@todo Removed check for a session: Maybe add a debug notice?

		return (bool) $valid;
	}

	/**
	 * Include a file. Optionally checks if the class already exists.
	 *
	 * @since   1.7.1
	 * @access  public
	 *
	 * @param   string  $file   The file name.
	 * @param   string  $class  (optional) The class name.
	 * @return  bool
	 */
	public function include_file( $file, $class = '' ) {
		static $loaded = array();

		if ( in_array( $file, $loaded, true ) ) {
			return true;
		}

		if ( ! file_exists( $file ) ) {
			return false;
		}

		// Load file.
		if ( empty( $class ) || ! class_exists( $class, false ) ) {
			include_once $file;
		} else {
			$this->add_error_notice(
				$class . '::' . __METHOD__,
				array(
					'type'    => 'notice-error',
					'message' => __( 'Plugin not fully loaded because of a conflict with an other plugin or theme', VIEW_ADMIN_AS_DOMAIN )
						// Translators: %s stands for the class name.
						. ' <code>(' . sprintf( __( 'Class %s already exists', VIEW_ADMIN_AS_DOMAIN ), $class ) . ')</code>',
				)
			);
			return false;
		}

		$loaded[] = $file;
		return true;
	}

	/**
	 * Helper function to include files. Checks class existence and throws an error if needed.
	 * Also adds the class to a supplied group if available.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @param   array[]|string[]  $includes {
	 *     An array of files to include.
	 *     @type  string  $file   The file to include. Directory starts from the plugin folder.
	 *     @type  string  $class  The class name.
	 * }
	 * @param   array  $group     A reference array.
	 * @return  array  $group
	 */
	public function load_files( $includes, &$group = null ) {

		$group = (array) $group;

		foreach ( $includes as $key => $inc ) {

			if ( is_string( $inc ) ) {
				$inc = array(
					'file' => $inc,
				);
				if ( is_string( $key ) ) {
					$inc['class'] = $key;
				}
			}

			if ( empty( $inc['file'] ) ) {
				continue;
			}

			$class = ( ! empty( $inc['class'] ) ) ? $inc['class'] : '';

			$this->include_file( VIEW_ADMIN_AS_DIR . $inc['file'], $class );

			// If it's a class file, add the class instance to the group.
			if ( ! empty( $class ) && VAA_API::exists_callable( array( $class, 'get_instance' ) ) ) {
				$group[ $key ] = call_user_func( array( $class, 'get_instance' ), $this );
			}
		}
		return $group;
	}

	/**
	 * Load the user interface.
	 *
	 * @since   1.5.0
	 * @since   1.5.1   Added notice on class name conflict.
	 * @since   1.6.0   Added our toolbar class.
	 * @access  private
	 * @return  void
	 */
	private function load_ui() {

		$includes = array(
			'ui'        => array(
				'file'  => 'ui/class-ui.php',
				'class' => 'VAA_View_Admin_As_UI',
			),
			'admin_bar' => array(
				'file'  => 'ui/class-admin-bar.php',
				'class' => 'VAA_View_Admin_As_Admin_Bar',
			),
		);

		// Compat for < 4.2 since it breaks due to WP calling require() instead of require_once().
		if ( VAA_API::validate_wp_version( '4.2' ) ) {
			$includes['toolbar'] = array(
				'file'  => 'ui/class-toolbar.php',
				'class' => 'VAA_View_Admin_As_Toolbar',
			);
		}

		// Include UI files and add them to the `ui` property.
		$this->load_files( $includes, $this->ui );
	}

	/**
	 * Load the modules.
	 *
	 * @since   1.5.0
	 * @access  private
	 * @return  void
	 */
	private function load_modules() {

		$includes = array(
			'role_switcher'       => array(
				'file'  => 'modules/class-roles.php',
				'class' => 'VAA_View_Admin_As_Roles',
			),
			'user_switcher'       => array(
				'file'  => 'modules/class-users.php',
				'class' => 'VAA_View_Admin_As_Users',
			),
			'capability_switcher' => array(
				'file'  => 'modules/class-caps.php',
				'class' => 'VAA_View_Admin_As_Caps',
			),
			'language_switcher'   => array(
				'file'  => 'modules/class-languages.php',
				'class' => 'VAA_View_Admin_As_Languages',
			),
			'role_defaults'       => array(
				'file'  => 'modules/class-role-defaults.php',
				'class' => 'VAA_View_Admin_As_Role_Defaults',
			),
			'role_manager'        => array(
				'file'  => 'modules/class-role-manager.php',
				'class' => 'VAA_View_Admin_As_Role_Manager',
			),
		);

		if ( VAA_API::exists_callable( array( 'RUA_App', 'instance' ) ) ) {
			$includes['rua_level'] = array(
				'file'  => 'modules/class-restrict-user-access.php',
				'class' => 'VAA_View_Admin_As_RUA',
			);
		}

		if ( VAA_API::exists_callable( array( 'Groups_Group', 'get_groups' ) ) ) {
			$includes['groups'] = array(
				'file'  => 'modules/class-groups.php',
				'class' => 'VAA_View_Admin_As_Groups',
			);
		}

		// Run include code but do not register modules yet (leave that to the modules).
		$this->load_files( $includes );

		/**
		 * Modules loaded. Hook is used for other modules related to View Admin As.
		 *
		 * @since  1.6.2
		 * @param  \VAA_View_Admin_As  $this  The main View Admin As object instance.
		 */
		do_action( 'vaa_view_admin_as_modules_loaded', $this );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since   1.2.0
	 * @since   1.6.0  Hooked into init hook, check for is_enabled() required.
	 * @access  public
	 * @return  void
	 */
	public function load_textdomain() {

		if ( ! $this->is_enabled() && empty( $this->notices ) ) {
			return;
		}

		load_plugin_textdomain( VIEW_ADMIN_AS_DOMAIN );

		/**
		 * Frontend translation of roles is not working by default (Darn you WordPress!).
		 * Needs to be in init action to work.
		 * @see  https://core.trac.wordpress.org/ticket/37539
		 */
		$wp_mo = WP_LANG_DIR . '/admin-' . get_locale() . '.mo';
		if ( ! is_admin() && file_exists( $wp_mo ) ) {
			load_textdomain( 'default', $wp_mo );
		}
	}

	/**
	 * Get the hooks class.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @return  \VAA_View_Admin_As_Hooks
	 */
	public function hooks() {
		return $this->hooks;
	}

	/**
	 * Get the store class.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @return  \VAA_View_Admin_As_Store
	 */
	public function store() {
		return $this->store;
	}

	/**
	 * Get the controller class.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @return  \VAA_View_Admin_As_Controller
	 */
	public function controller() {
		return $this->controller;
	}

	/**
	 * Get the view class.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @return  \VAA_View_Admin_As_View
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
	 * @see     \VAA_View_Admin_As::load_ui()
	 * @param   string  $key  (optional) UI class name.
	 * @return  \VAA_View_Admin_As_Module|\VAA_View_Admin_As_Module[]
	 */
	public function get_ui( $key = null ) {
		return VAA_API::get_array_data( $this->ui, $key );
	}

	/**
	 * Get view types.
	 * If a key is provided it will only return that view type.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @param   string  $key           (optional) The type key.
	 * @param   bool    $check_access  (optional) Check if the user has access? Default: true.
	 * @return  \VAA_View_Admin_As_Type|\VAA_View_Admin_As_Type[]
	 */
	public function get_view_types( $key = null, $check_access = true ) {
		$view_types = $this->view_types;
		if ( $check_access ) {
			foreach ( $view_types as $type => $instance ) {
				if ( ! $instance->has_access() ) {
					unset( $view_types[ $type ] );
				}
			}
		}
		$view_types = VAA_API::get_array_data( $view_types, $key );
		return $view_types;
	}

	/**
	 * Register view types.
	 *
	 * @since   1.8.0
	 * @param   array  $data {
	 *     Required. An array of module info.
	 *     @type  string                  $id        The view type name, choose wisely since this is used for validation.
	 *     @type  VAA_View_Admin_As_Type  $instance  The view type class reference/instance.
	 * }
	 * @return  bool  Successfully registered?
	 */
	public function register_view_type( $data ) {
		if (
			! empty( $data['id'] ) && is_string( $data['id'] ) &&
			! empty( $data['instance'] ) && $data['instance'] instanceof VAA_View_Admin_As_Type
		) {
			$this->view_types[ $data['id'] ] = $data['instance'];
			return true;
		}
		return false;
	}

	/**
	 * Get current modules.
	 * If a key is provided it will only return that module.
	 *
	 * @since   1.5.0
	 * @access  public
	 * @see     VAA_View_Admin_As::load_modules()
	 * @param   string  $key  (optional) The module key.
	 * @return  object|object[]
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
	 *     @type  string                    $id        The module name, choose wisely since this is used for validation.
	 *     @type  VAA_View_Admin_As_Module  $instance  The module class reference/instance.
	 * }
	 * @return  bool  Successfully registered?
	 */
	public function register_module( $data ) {
		if (
			! empty( $data['id'] ) && is_string( $data['id'] ) &&
			! empty( $data['instance'] ) && $data['instance'] instanceof VAA_View_Admin_As_Module
		) {
			$this->modules[ $data['id'] ] = $data['instance'];
			return true;
		}
		return false;
	}

	/**
	 * Add a welcome notice for new users.
	 *
	 * @since   1.7.0
	 * @access  public
	 */
	public function welcome_notice() {
		$this->add_notice(
			'vaa-welcome',
			array(
				'type'    => 'notice-success',
				'message' => sprintf(
					// Translators: %s stands for `Dashboard` (link element).
					__( 'For the best experience you can start from the %s since not all views are allowed to access all admin pages.', VIEW_ADMIN_AS_DOMAIN ),
					'<a class="button button-primary" href="' . admin_url() . '">' . __( 'Dashboard' ) . '</a>'
				),
				'prepend' => __( 'Thank you for installing View Admin As!', VIEW_ADMIN_AS_DOMAIN ),
			)
		);
	}

	/**
	 * Add error notices to generate.
	 * Automatically generated a bug report link at the end of the notice.
	 *
	 * @since   1.7.2
	 * @access  public
	 *
	 * @param   string  $id
	 * @param   array   $notice {
	 *     Required array.
	 *     @type  string  $message  The notice message.
	 *     @type  string  $type     (optional) The WP notice type class(es).
	 *     @type  string  $prepend  (optional) Prepend the message (bold). Default: View Admin As.
	 *                              Pass `false` or `null` to remove.
	 * }
	 * @return  void
	 */
	public function add_error_notice( $id, $notice ) {
		if ( empty( $notice['message'] ) ) {
			return;
		}

		$notice['type'] = ( ! empty( $notice['type'] ) ) ? $notice['type'] : 'notice-error';

		// @todo Add debug_backtrace to body?
		$report = array(
			'title' => __( 'Error', VIEW_ADMIN_AS_DOMAIN ) . ': ' . $id,
			'body'  => $notice['message'],
		);

		$report_link = add_query_arg( $report, 'https://github.com/JoryHogeveen/view-admin-as/issues/new' );

		$notice['message'] = $notice['message']
			. ' <a href="' . $report_link . '" target="_blank">'
			. __( 'Click here to report this error!', VIEW_ADMIN_AS_DOMAIN )
			. '</a>';

		$this->add_notice( $id, $notice );
	}

	/**
	 * Add notices to generate.
	 *
	 * @since   1.5.1
	 * @access  public
	 *
	 * @param   string        $id
	 * @param   array|string  $notice {
	 *     Required array.
	 *     @type  string  $message  The notice message.
	 *     @type  string  $type     (optional) The WP notice type class(es).
	 *     @type  string  $prepend  (optional) Prepend the message (bold). Default: View Admin As.
	 *                              Pass `false` or `null` to remove.
	 * }
	 * @return  void
	 */
	public function add_notice( $id, $notice ) {
		if ( ! empty( $notice ) ) {

			if ( ! is_array( $notice ) ) {
				$notice = array(
					'message' => $notice,
				);
			}

			$defaults = array(
				'type'    => '',
				'prepend' => __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ),
			);

			$notice = array_merge( $defaults, $notice );

			if ( $notice['prepend'] ) {
				$notice['message'] = '<strong>' . $notice['prepend'] . ':</strong> ' . $notice['message'];
			}

			$this->notices[ $id ] = array(
				'type'    => $notice['type'],
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
	 * @since   1.6.0  Returns conflict status.
	 * @access  private
	 * @global  string  $wp_version  WordPress version.
	 * @return  bool
	 */
	private function validate_versions() {
		global $wp_version;
		// Start positive!
		$valid = true;

		// Validate WP.
		$min_wp_version = '4.1';
		if ( version_compare( $wp_version, $min_wp_version, '<' ) ) {
			$this->add_notice(
				'wp-version',
				array(
					'type'    => 'notice-error',
					'message' => sprintf(
				        // Translators: %1$s stands for "WordPress", %2$s stands for the version.
						__( 'Plugin deactivated, %1$s version %2$s or higher is required', VIEW_ADMIN_AS_DOMAIN ),
						'WordPress',
						$min_wp_version
				    ),
				)
			);
			$valid = false;
		}

		if ( ! $valid ) {
			// Too bad..
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
		}

		return $valid;
	}

	/**
	 * Sets update class to run a DB update.
	 * @since   1.8.0
	 */
	public static function run_db_update() {
		// Make sure the main class is initialized.
		view_admin_as();
		// Set the update class to a fresh installation which will trigger the update.
		VAA_View_Admin_As_Update::$fresh_install = true;
	}

	/**
	 * Is this plugin network enabled.
	 *
	 * @since   1.7.5
	 * @return  bool
	 */
	public static function is_network_active() {
		static $check;
		if ( is_bool( $check ) ) {
			return $check;
		}
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$check = (bool) is_plugin_active_for_network( VIEW_ADMIN_AS_BASENAME );
		return $check;
	}

	/**
	 * Main View Admin As instance.
	 * Ensures only one instance of View Admin As is loaded or can be loaded.
	 *
	 * @since   1.4.1
	 * @access  public
	 * @static
	 * @see     view_admin_as()
	 * @return  \VAA_View_Admin_As  $this  The main View Admin As object instance.
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
	 * @since   1.5.0
	 * @access  public
	 * @return  string
	 */
	public function __toString() {
		return get_class( $this );
	}

	/**
	 * Magic method to keep the object from being cloned.
	 *
	 * @since   1.5.0
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
	 * @since   1.5.0
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
	 * @since   1.5.0
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

} // End class VAA_View_Admin_As.
