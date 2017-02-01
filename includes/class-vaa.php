<?php
/**
 * View Admin As - Class Init (Main class)
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * Plugin initializer class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   0.1
 * @version 1.6.4
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
	 * Classes that are allowed to access this class directly.
	 *
	 * @since  1.6
	 * @static
	 * @see    get_instance()
	 * @var    array
	 */
	private static $vaa_class_names = array();

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

		add_action( 'admin_notices', array( $this, 'do_admin_notices' ) );

		// Returns false on conflict.
		if ( ! (boolean) $this->validate_versions() ) {
			return;
		}

		if ( (boolean) $this->load() ) {

			$this->store = VAA_View_Admin_As_Store::get_instance( $this );
			$this->view  = VAA_View_Admin_As_View::get_instance( $this );

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

		if (    ! class_exists( 'VAA_API' )
		     && ! class_exists( 'VAA_View_Admin_As_Class_Base' )
		     && ! class_exists( 'VAA_View_Admin_As_Settings' )
		     && ! class_exists( 'VAA_View_Admin_As_Store' )
		     && ! class_exists( 'VAA_View_Admin_As_View' )
		     && ! class_exists( 'VAA_View_Admin_As_Update' )
		     && ! class_exists( 'VAA_View_Admin_As_Compat' )
		) {

			self::$vaa_class_names[] = 'VAA_API';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Class_Base';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Settings';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Store';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_View';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Update';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Compat';

			require( VIEW_ADMIN_AS_DIR . 'includes/class-api.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-base.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-settings.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-store.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-view.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-update.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-compat.php' );

			return true;
		}

		return false;
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

		$this->store->init();

		// Sets enabled.
		$this->validate_user();

		$this->load_modules();

		// Check if a database update is needed.
		VAA_View_Admin_As_Update::get_instance( $this )->maybe_db_update();

		if ( $this->is_enabled() ) {

			// Fix some compatibility issues, more to come!
			VAA_View_Admin_As_Compat::get_instance( $this )->init();

			$this->store->store_caps();
			$this->store->store_roles();
			$this->store->store_users();

			$this->view->init();

			$this->load_ui();

			// Dúh..
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_filter( 'wp_die_handler', array( $this, 'die_handler' ) );

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
	 * Load the user interface.
	 *
	 * @since   1.5
	 * @since   1.5.1   added notice on class name conflict.
	 * @since   1.6     added our toolbar class.
	 * @access  private
	 * @return  void
	 */
	private function load_ui() {

		$include = array(
			'ui' => array(
				'file'  => 'class-ui.php',
				'class' => 'VAA_View_Admin_As_UI',
			),
			'admin-bar' => array(
				'file'  => 'class-admin-bar.php',
				'class' => 'VAA_View_Admin_As_Admin_Bar',
			),
			'toolbar' => array(
				'file'  => 'class-toolbar.php',
				'class' => 'VAA_View_Admin_As_Toolbar',
			),
		);

		foreach ( $include as $key => $inc ) {
			if ( empty( $inc['class'] ) || ! class_exists( $inc['class'] ) ) {
				require( VIEW_ADMIN_AS_DIR . 'ui/' . $inc['file'] );
				if ( ! empty( $inc['class'] ) && is_callable( array( $inc['class'], 'get_instance' ) ) ) {
					self::$vaa_class_names[] = $inc['class'];
					$this->ui[ $key ] = call_user_func( array( $inc['class'], 'get_instance' ), $this );
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
	}

	/**
	 * Load the modules.
	 *
	 * @since   1.5
	 * @since   1.5.1   Added notice on class name conflict (removed in 1.6.2).
	 * @since   1.6.2   Generic loading of modules.
	 * @access  private
	 * @return  void
	 */
	private function load_modules() {

		$files = scandir( VIEW_ADMIN_AS_DIR . 'modules' );

		foreach ( $files as $file ) {
			if ( ! in_array( $file, array( '.', '..', 'index.php' ), true ) ) {
				$file_info = pathinfo( $file );

				// Single file modules.
				if ( ! empty( $file_info['extension'] ) ) {
					if ( 'php' === $file_info['extension'] && is_file( VIEW_ADMIN_AS_DIR . 'modules/' . $file ) ) {
						include( VIEW_ADMIN_AS_DIR . 'modules/' . $file );
					}
				}
				// Directory modules.
				elseif ( is_file( VIEW_ADMIN_AS_DIR . 'modules/' . $file . '/' . $file . '.php' ) ) {
					include( VIEW_ADMIN_AS_DIR . 'modules/' . $file . '/' . $file . '.php' );
				}
			}
		}

		/**
		 * Modules loaded. Hook is used for other modules related to View Admin As.
		 *
		 * @since  1.6.2
		 * @param  VAA_View_Admin_As  $this  The main View Admin As object.
		 */
		do_action( 'vaa_view_admin_as_modules_loaded', $this );
	}

	/**
	 * Add options to the access denied page when the user has selected a view and did something this view is not allowed.
	 *
	 * @since   1.3
	 * @since   1.5.1   Check for SSL.
	 * @since   1.6     More options and better description.
	 * @access  public
	 *
	 * @param   string  $function_name  function callback.
	 * @return  string  $function_name  function callback.
	 */
	public function die_handler( $function_name ) {

		// only do something if a view is selected.
		if ( ! $this->store->get_view() ) {
			return $function_name;
		}

		$options = array();

		if ( is_network_admin() ) {
			$dashboard_url = network_admin_url();
			$options[] = array(
				'text' => __( 'Go to network dashboard', VIEW_ADMIN_AS_DOMAIN ),
				'url' => $dashboard_url,
			);
		} else {
			$dashboard_url = admin_url();
			$options[] = array(
				'text' => __( 'Go to dashboard', VIEW_ADMIN_AS_DOMAIN ),
				'url' => $dashboard_url,
			);
			$options[] = array(
				'text' => __( 'Go to homepage', VIEW_ADMIN_AS_DOMAIN ),
				'url' => get_bloginfo( 'url' ),
			);
		}

		// Reset url.
		$options[] = array(
			'text' => __( 'Reset the view', VIEW_ADMIN_AS_DOMAIN ),
			'url' => VAA_API::get_reset_link(),
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
		 * @return array
		 */
		$options = apply_filters( 'view_admin_as_error_page_options', $options );
?>
<div>
	<h3><?php esc_html_e( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ) ?>:</h3>
	<?php esc_html_e( 'The view you have selected is not permitted to access this page, please choose one of the options below.', VIEW_ADMIN_AS_DOMAIN ) ?>
	<ul>
		<?php foreach ( $options as $option ) { ?>
		<li><a href="<?php echo $option['url'] ?>"><?php echo $option['text'] ?></a></li>
		<?php } ?>
	</ul>
</div>
<hr>
<?php
		return $function_name;
	}

	/**
	 * Add necessary scripts and styles.
	 *
	 * @since   0.1
	 * @access  public
	 * @return  void
	 */
	public function enqueue_scripts() {
		// Only enqueue scripts if the admin bar is enabled otherwise they have no use.
		if ( ( is_admin_bar_showing() || VAA_API::is_vaa_toolbar_showing() ) && $this->is_enabled() ) {

			// Use non-minified versions.
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			// Prevent browser cache.
			$version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : $this->store->get_version();

			wp_enqueue_style(
				'vaa_view_admin_as_style',
				VIEW_ADMIN_AS_URL . 'assets/css/view-admin-as' . $suffix . '.css',
				array(),
				$version
			);
			wp_enqueue_script(
				'vaa_view_admin_as_script',
				VIEW_ADMIN_AS_URL . 'assets/js/view-admin-as' . $suffix . '.js',
				array( 'jquery' ),
				$version,
				true // load in footer.
			);

			$script_localization = array(
				// Data.
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'siteurl'       => get_site_url(),
				'settings'      => $this->store->get_settings(),
				'settings_user' => $this->store->get_userSettings(),
				'view'          => $this->store->get_view(),
				// Other.
				'_debug'     => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? (bool) WP_DEBUG : false,
				'_vaa_nonce' => $this->store->get_nonce( true ),
				// i18n.
				'__no_users_found' => esc_html__( 'No users found.', VIEW_ADMIN_AS_DOMAIN ),
				'__success'        => esc_html__( 'Success', VIEW_ADMIN_AS_DOMAIN ),
				'__confirm'        => esc_html__( 'Are you sure?', VIEW_ADMIN_AS_DOMAIN ),
			);

			/**
			 * Add basic view types for automated use in JS.
			 *
			 * - Menu items require the class vaa-{TYPE}-item (through the add_node() meta key).
			 * - Menu items require the rel attribute for the view data to be send (string or numeric).
			 * - Menu items require the href attribute (the node needs to be an <a> element), I'd set it to '#'.
			 *
			 * @since  1.6.2
			 * @param  array  $array  Empty array.
			 * @return array  An array of strings (view types).
			 */
			$script_localization['view_types'] = array_unique( array_merge(
				array_filter( apply_filters( 'view_admin_as_view_types', array() ), 'is_string' ),
				array( 'user', 'role', 'caps', 'visitor' )
			) );

			foreach ( $this->get_modules() as $name => $module ) {
				if ( is_callable( array( $module, 'get_scriptLocalization' ) ) ) {
					$script_localization[ 'settings_' . $name ] = $module->get_scriptLocalization();
				}
			}

			wp_localize_script( 'vaa_view_admin_as_script', 'VAA_View_Admin_As', $script_localization );
		}
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
		$valid = true;

		// Validate WP
		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			$this->add_notice( 'wp-version', array(
				'type' => 'notice-error',
				'message' => __( 'View Admin As', VIEW_ADMIN_AS_DOMAIN ) . ': '
				             // Translators, %1$s stands for "WordPress", %2$s stands for version 3.5
				             . sprintf( __( 'Plugin deactivated, %1$s version %2$s or higher is required', VIEW_ADMIN_AS_DOMAIN ), 'WordPress', '3.5' ),
			) );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Main View Admin As instance.
	 *
	 * Ensures only one instance of View Admin As is loaded or can be loaded.
	 *
	 * @since   1.4.1
	 * @since   1.6    Restrict direct access to known classes.
	 * @access  public
	 * @static
	 * @see     View_Admin_As()
	 * @param   object  $caller  The referrer class.
	 * @return  VAA_View_Admin_As
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && in_array( get_class( $caller ), self::$vaa_class_names, true ) ) {
			return self::$_instance;
		}
		return null;
	}

	/**
	 * Populate the instance with this class.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @return  void
	 */
	public static function instantiate() {
		if ( is_null( self::$_instance ) ) {
			// First init, returns nothing.
			self::$_instance = new self();
		}
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
