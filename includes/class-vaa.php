<?php
/**
 * View Admin As - Class Init
 *
 * Plugin initializer class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   0.1
 * @version 1.6
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.4.1
	 * @var    VAA_View_Admin_As
	 */
	private static $_instance = null;

	/**
	 * Classes that are allowed to use this class
	 *
	 * @see    get_instance()
	 *
	 * @since  1.6
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
	 * Var that holds all the notices
	 *
	 * @since  1.5.1
	 * @var    array
	 */
	private $notices = array();

	/**
	 * VAA Store
	 *
	 * @since  1.6
	 * @var    array
	 */
	private $store = null;

	/**
	 * VAA View handler
	 *
	 * @since  1.6
	 * @var    array
	 */
	private $view = null;

	/**
	 * VAA UI classes that are loaded
	 *
	 * @since  1.5
	 * @var    array
	 */
	private $ui = array();

	/**
	 * Other VAA modules that are loaded
	 *
	 * @since  1.4
	 * @var    array
	 */
	private $modules = array();

	/**
	 * Init function to register plugin hook
	 * Private to make sure it isn't declared elsewhere
	 *
	 * @since   0.1
	 * @since   1.3.3   changes init hook to plugins_loaded for theme compatibility
	 * @since   1.4.1   creates instance
	 * @since   1.5     make private
	 * @since   1.5.1   added notice on class name conflict + validate versions
	 * @access  private
	 */
	private function __construct() {
		self::$_instance = $this;

		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_action( 'admin_notices', array( $this, 'do_admin_notices' ) );

		// Returns true on conflict
		if ( (boolean) $this->validate_versions() ) {
			return;
		}

		if ( (boolean) $this->load() ) {

			$this->store = VAA_View_Admin_As_Store::get_instance( $this );
			$this->view = VAA_View_Admin_As_View::get_instance( $this );

			// Lets start!
			add_action( 'plugins_loaded', array( $this, 'init' ), 0 );

		} else {

			$this->add_notice('class-error-base', array(
				'type' => 'notice-error',
				'message' => '<strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
					. __('Plugin not loaded because of a conflict with an other plugin or theme', 'view-admin-as')
					. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As_Class_Base' ) . ')</code>',
			) );

		}
	}

	/**
	 * Load required classes and files
	 * Returns false on conflict
	 *
	 * @since  1.6
	 * @return bool
	 */
	private function load() {

		if (    ! class_exists( 'VAA_API' )
		     && ! class_exists( 'VAA_View_Admin_As_Class_Base' )
		     && ! class_exists( 'VAA_View_Admin_As_Store' )
		     && ! class_exists( 'VAA_View_Admin_As_View' )
		     && ! class_exists( 'VAA_View_Admin_As_Update' )
		     && ! class_exists( 'VAA_View_Admin_As_Compat' )
		) {

			self::$vaa_class_names[] = 'VAA_API';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Class_Base';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Store';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_View';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Update';
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Compat';

			require( VIEW_ADMIN_AS_DIR . 'includes/class-api.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-base.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-store.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-view.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-update.php' );
			require( VIEW_ADMIN_AS_DIR . 'includes/class-compat.php' );

			return true;
		}

		return false;
	}

	/**
	 * Instantiate function that checks if the plugin is already loaded
	 *
	 * @since  1.6
	 * @access public
	 */
	public function init() {
		static $loaded = false;

		if ( ! $loaded ) {
			$this->run();
			$loaded = true;
		}
	}

	/**
	 * Run the plugin!
	 * Check current user, load nessesary data and register all used hooks
	 *
	 * @since   0.1
	 * @access  private
	 * @return  void
	 */
	private function run() {

		// Not needed, the delete_user actions already remove all metadata
		//add_action( 'remove_user_from_blog', array( $this->store, 'delete_user_meta' ) );
		//add_action( 'wpmu_delete_user', array( $this->store, 'delete_user_meta' ) );
		//add_action( 'wp_delete_user', array( $this->store, 'delete_user_meta' ) );

		if ( is_user_logged_in() ) {

			$this->store->set_nonce( 'view-admin-as' );

			// Get the current user
			$this->store->set_curUser( wp_get_current_user() );

			// Get the current user session
			if ( function_exists( 'wp_get_session_token' ) ) {
				// WP 4.0+
				$this->store->set_curUserSession( (string) wp_get_session_token() );
			} else {
				$cookie = wp_parse_auth_cookie( '', 'logged_in' );
				if ( ! empty( $cookie['token'] ) ) {
					$this->store->set_curUserSession( (string) $cookie['token'] );
				} else {
					// Fallback. This disables the use of multiple views in different sessions
					$this->store->set_curUserSession( $this->store->get_curUser()->ID );
				}
			}

			/**
			 * Validate if the current user has access to the functionalities
			 *
			 * @since  0.1    Check if the current user had administrator rights (is_super_admin)
			 *                Disable plugin functions for nedwork admin pages
			 * @since  1.4    Make sure we have a session for the current user
			 * @since  1.5.1  If a user has the correct capability (view_admin_as + edit_users) this plugin is also enabled, use with care
			 *                Note that in network installations the non-admin user also needs the manage_network_users capability (of not the edit_users will return false)
			 * @since  1.5.3  Enable on network pages for superior admins
			 */
			if (   ( is_super_admin( $this->store->get_curUser()->ID )
				     || ( current_user_can( 'view_admin_as' ) && current_user_can( 'edit_users' ) ) )
				&& ( ! is_network_admin() || VAA_API::is_superior_admin( $this->store->get_curUser()->ID ) )
				&& $this->store->get_curUserSession() != ''
			) {
				$this->enable = true;
			}

			// Get database settings
			$this->store->set_optionData( get_option( $this->store->get_optionKey() ) );
			// Get database settings of the current user
			$this->store->set_userMeta( get_user_meta( $this->store->get_curUser()->ID, $this->store->get_userMetaKey(), true ) );

			$this->load_modules();

			// Check if a database update is needed
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
				 * Init is finished. Hook is used for other classes related to View Admin As
				 * @since  1.5
				 * @param  object  $this  VAA_View_Admin_As
				 */
				do_action( 'vaa_view_admin_as_init', $this );

			} else {
				// Extra security check for non-admins who did something naughty or we're demoted to a lesser role
				// If they have settings etc. we'll keep them in case they get promoted again
				add_action( 'wp_login', array( $this, 'reset_all_views' ), 10, 2 );
			}
		}
	}

	/**
	 * Load the user interface
	 *
	 * @since   1.5
	 * @since   1.5.1   added notice on class name conflict
	 * @since   1.6     added our toolbar class
	 * @access  private
	 * @return  void
	 */
	private function load_ui() {

		// The default admin bar ui
		if ( ! class_exists('VAA_View_Admin_As_Admin_Bar') ) {
			require( VIEW_ADMIN_AS_DIR . 'ui/class-admin-bar.php' );
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Admin_Bar';
			$this->ui['admin_bar'] = VAA_View_Admin_As_Admin_Bar::get_instance( $this );
		} else {
			$this->add_notice('class-error-admin-bar', array(
				'type' => 'notice-error',
				'message' => '<strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
					. __('Plugin not loaded because of a conflict with an other plugin or theme', 'view-admin-as')
					. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As_Admin_Bar' ) . ')</code>',
			) );
		}

		// Our custom toolbar
		if ( ! class_exists('VAA_View_Admin_As_Toolbar') ) {
			require( VIEW_ADMIN_AS_DIR . 'ui/class-toolbar.php' );
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Toolbar';
			$this->ui['toolbar'] = VAA_View_Admin_As_Toolbar::get_instance( $this );
		} else {
			$this->add_notice('class-error-toolbar', array(
				'type' => 'notice-error',
				'message' => '<strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
				    . __('Plugin not loaded because of a conflict with an other plugin or theme', 'view-admin-as')
				    . ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As_Toolbar' ) . ')</code>',
			) );
		}
	}

	/**
	 * Load the modules
	 *
	 * @since   1.5
	 * @since   1.5.1   added notice on class name conflict
	 * @access  private
	 * @return  void
	 */
	private function load_modules() {

		// The role defaults module (screen settings)
		if ( ! class_exists('VAA_View_Admin_As_Role_Defaults') ) {
			include_once( VIEW_ADMIN_AS_DIR . 'modules/class-role-defaults.php' );
			self::$vaa_class_names[] = 'VAA_View_Admin_As_Role_Defaults';
			$this->modules['role_defaults'] = VAA_View_Admin_As_Role_Defaults::get_instance( $this );
		} else {
			$this->add_notice('class-error-role-defaults', array(
				'type' => 'notice-error',
				'message' =>'<strong>' . __('View Admin As', 'view-admin-as') . ':</strong> '
					. __('Plugin not loaded because of a conflict with an other plugin or theme', 'view-admin-as')
					. ' <code>(' . sprintf( __('Class %s already exists', 'view-admin-as'), 'VAA_View_Admin_As_Role_Defaults' ) . ')</code>',
			) );
		}
	}

	/**
	 * Add options to the access denied page when the user has selected a view and did something this view is not allowed
	 *
	 * @since   1.3
	 * @since   1.5.1   Check for SSL
	 * @since   1.6     More options and better description
	 * @access  public
	 *
	 * @param   string  $function_name  function callback
	 * @return  string  $function_name  function callback
	 */
	public function die_handler( $function_name ) {

		if ( false != $this->store->get_viewAs() ) {

			$options = array();

			if ( is_network_admin() ) {
				$dashboard_url = network_admin_url();
				$options[] = array(
					'text' => __( 'Go to network dashboard', 'view-admin-as' ),
					'url' => $dashboard_url
				);
			} else {
				$dashboard_url = admin_url();
				$options[] = array(
					'text' => __( 'Go to dashboard', 'view-admin-as' ),
					'url' => $dashboard_url
				);
				$options[] = array(
					'text' => __( 'Go to homepage', 'view-admin-as' ),
					'url' => get_bloginfo( 'url' )
				);
			}

			// Reset url
			$options[] = array(
				'text' => __( 'Reset the view', 'view-admin-as' ),
				'url' => VAA_API::get_reset_link(),
			);
?>
<div>
	<h3><?php _e( 'View Admin As', 'view-admin-as' ) ?>:</h3>
	<?php _e( 'The view you have selected is not permitted to view this page, please choose one of the options below.', 'view-admin-as' ) ?>
	<ul>
		<?php foreach ( $options as $option ) { ?>
		<li><a href="<?php echo $option['url'] ?>"><?php echo $option['text'] ?></a></li>
		<?php } ?>
	</ul>
</div>
<hr>
<?php
		}
		return $function_name;
	}

	/**
	 * Call a view method
	 *
	 * @since  1.6
	 * @param  string           $method
	 * @param  object|int|bool  $user   (Optional: WP_User object or User ID)
	 * @return bool
	 */
	public function view( $method, $user = false ) {
		if ( is_int( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}
		switch( $method ) {
			case 'reset':
				return $this->view->reset_view( false, $user );
				break;
			case 'clean':
			case 'cleanup':
				return $this->view->cleanup_views( false, $user );
				break;
			case 'reset_all':
				return $this->view->reset_all_views( false, $user );
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Add necessary scripts and styles
	 *
	 * @since   0.1
	 * @access  public
	 * @return  void
	 */
	public function enqueue_scripts() {
		// Only enqueue scripts if the admin bar is enabled otherwise they have no use
		if ( ( is_admin_bar_showing() || VAA_API::is_vaa_toolbar_showing() ) && $this->is_enabled() ) {
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min'; // Use non-minified versions
			$version = defined('WP_DEBUG') && WP_DEBUG ? time() : $this->store->get_version(); // Prevent browser cache

			wp_enqueue_style(   'vaa_view_admin_as_style', VIEW_ADMIN_AS_URL . 'assets/css/view-admin-as' . $suffix . '.css', array(), $version );
			wp_enqueue_script(  'vaa_view_admin_as_script', VIEW_ADMIN_AS_URL . 'assets/js/view-admin-as' . $suffix . '.js', array( 'jquery' ), $version, true );

			$script_localization = array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'siteurl' => get_site_url(),
				'_debug' => ( defined('WP_DEBUG') && WP_DEBUG ) ? (bool) WP_DEBUG : false,
				'_vaa_nonce' => wp_create_nonce( $this->store->get_nonce() ),
				'__no_users_found' => esc_html__( 'No users found.', 'view-admin-as' ),
				'__success' => esc_html__( 'Success', 'view-admin-as' ),
				'__confirm' => esc_html__( 'Are you sure?', 'view-admin-as' ),
				'settings' => $this->store->get_settings(),
				'settings_user' => $this->store->get_userSettings()
			);
			foreach ( $this->get_modules() as $name => $module ) {
				$script_localization[ 'settings_' . $name ] = $module->get_scriptLocalization();
			}

			wp_localize_script( 'vaa_view_admin_as_script', 'VAA_View_Admin_As', $script_localization );
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since   1.2
	 * @since   1.6    Hooked into init hook, check for is_enabled() required
	 * @access  public
	 * @return  void
	 */
	public function load_textdomain() {

		if ( $this->is_enabled() ) {

			/**
			 * Keep the third parameter pointing to the languages folder within this plugin to enable support for custom .mo files
			 *
			 * @todo look into 4.6 changes Maybe the same can be done in an other way
			 * @see https://make.wordpress.org/core/2016/07/06/i18n-improvements-in-4-6/
			 */
			load_plugin_textdomain( 'view-admin-as', false, VIEW_ADMIN_AS_DIR . 'languages/' );

			/**
			 * Frontend translation of roles is not working by default (Darn you WordPress!)
			 * Needs to be in init action to work
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
	 * Get current modules
	 *
	 * @since   1.5
	 * @access  public
	 * @param   string|bool  $key  The module key
	 * @return  array|object
	 */
	public function get_modules( $key = false ) {
		return VAA_API::get_array_data( $this->modules, $key );
	}

	/**
	 * Add notices to generate
	 *
	 * @since   1.5.1
	 * @access  public
	 *
	 * @param   string  $id
	 * @param   array   $notice  Keys: 'type' and 'message'
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
	 * Echo admin notices
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
	 * Validate plugin activate
	 * Checks for valid resources
	 *
	 * @since   1.5.1
	 * @since   1.6    Returns conflict status
	 * @access  private
	 * @return  bool
	 */
	private function validate_versions() {
		global $wp_version;
		$conflict = false;

		// Validate PHP
		/*if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			$this->add_notice('php-version', array(
				'type' => 'notice-error',
				'message' => __('View Admin As', 'view-admin-as') . ': ' . sprintf( __('Plugin deactivated, %s version %s or higher is required', 'view-admin-as'), 'PHP', '5.3' ),
			) );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
		}*/

		// Validate WP
		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			$this->add_notice('wp-version', array(
				'type' => 'notice-error',
				'message' => __('View Admin As', 'view-admin-as') . ': ' . sprintf( __('Plugin deactivated, %s version %s or higher is required', 'view-admin-as'), 'WordPress', '3.5' ),
			) );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( VIEW_ADMIN_AS_BASENAME );
			$conflict = true;
		}
		return $conflict;
	}

	/**
	 * Main View Admin As Instance.
	 *
	 * Ensures only one instance of View Admin As is loaded or can be loaded.
	 *
	 * @since   1.4.1
	 * @since   1.6    Restrict access to known classes
	 * @access  public
	 * @static
	 * @see     View_Admin_As()
	 * @param   object  $caller
	 * @return  VAA_View_Admin_As
	 */
	public static function get_instance( $caller ) {
		if ( in_array( $caller, self::$vaa_class_names ) ) {
			return self::$_instance;
		}
		return null;
	}

	/**
	 * Populate the instance with this class
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @return  void
	 */
	public static function instantiate() {
		if ( is_null( self::$_instance ) ) {
			// First init, returns nothing
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
			get_class( $this ) . ': ' . esc_html__( 'This class does not want to be cloned', 'view-admin-as' ),
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
			get_class( $this ) . ': ' . esc_html__( 'This class does not want to wake up', 'view-admin-as' ),
			null
		);
	}

	/**
	 * Magic method to prevent a fatal error when calling a method that doesn't exist.
	 *
	 * @since   1.5
	 * @access  public
	 * @param   string
	 * @param   array
	 * @return  null
	 */
	public function __call( $method = '', $args = array() ) {
		_doing_it_wrong(
			get_class( $this ) . "::{$method}",
			esc_html__( 'Method does not exist.', 'view-admin-as' ),
			null
		);
		unset( $method, $args );
		return null;
	}

} // end class