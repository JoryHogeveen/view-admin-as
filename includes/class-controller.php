<?php
/**
 * View Admin As - Class Controller
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * View controller class. Handles all view data.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.x
 * @version 1.6.x
 * @uses    VAA_View_Admin_As_Class_Base Extends class
 */
class VAA_View_Admin_As_Controller extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_Controller
	 */
	private static $_instance = null;

	/**
	 * Expiration time for view data.
	 *
	 * @since  1.3.4  (as $metaExpiration).
	 * @since  1.6.2  Moved from main class.
	 * @var    int
	 */
	private $viewExpiration = 86400; // one day: ( 24 * 60 * 60 ).

	/**
	 * Available view types.
	 *
	 * @since  1.6.x
	 * @var    array
	 */
	private $viewTypes = array();

	/**
	 * VAA_View_Admin_As_Controller constructor.
	 *
	 * @since   1.6
	 * @since   1.6.1  $vaa param.
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		// When a user logs in or out, reset the view to default.
		add_action( 'wp_login',  array( $this, 'cleanup_views' ), 10, 2 );
		add_action( 'wp_login',  array( $this, 'reset_view' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'reset_view' ) );

		// Not needed, the delete_user actions already remove all metadata, keep code for possible future use.
		//add_action( 'remove_user_from_blog', array( $this->store, 'delete_user_meta' ) );
		//add_action( 'wpmu_delete_user', array( $this->store, 'delete_user_meta' ) );
		//add_action( 'wp_delete_user', array( $this->store, 'delete_user_meta' ) );

		/**
		 * Change expiration time for view meta.
		 *
		 * @example  You can set it to 1 to always clear everything after login.
		 * @example  0 will be overwritten!
		 *
		 * @param  int  $viewExpiration  86400 (1 day in seconds).
		 * @return int
		 */
		$this->viewExpiration = absint( apply_filters( 'view_admin_as_view_expiration', $this->viewExpiration ) );
	}

	/**
	 * Initializes after VAA is enabled.
	 *
	 * @since   1.6
	 * @access  public
	 * @return  void
	 */
	public function init() {

		/**
		 * Reset view to default if something goes wrong.
		 *
		 * @since    0.1
		 * @since    1.2  Only check for key
		 * @example  http://www.your.domain/wp-admin/?reset-view
		 */
		if ( isset( $_GET['reset-view'] ) ) {
			$this->reset_view();
		}
		/**
		 * Clear all user views.
		 *
		 * @since    1.3.4
		 * @example  http://www.your.domain/wp-admin/?reset-all-views
		 */
		if ( isset( $_GET['reset-all-views'] ) ) {
			$this->reset_all_views();
		}

		// Reset view will always return true.
		add_filter( 'view_admin_as_validate_view_data_reset', '__return_true' );
		// Visitor view is always a boolean.
		add_filter( 'view_admin_as_validate_view_data_visitor', '__return_true' );

		// Validation checks for caps, role and user views.
		add_filter( 'view_admin_as_validate_view_data_caps', array( $this, 'validate_view_data_caps' ), 10, 2 );
		add_filter( 'view_admin_as_validate_view_data_role', array( $this, 'validate_view_data_role' ), 10, 2 );
		add_filter( 'view_admin_as_validate_view_data_user', array( $this, 'validate_view_data_user' ), 10, 2 );

		// Get the current view (returns false if not found).
		$this->store->set_view( $this->get_view() );

		// Short circuit needed for visitor view (BEFORE the current user is set).
		if ( VAA_API::is_ajax_request( 'view_admin_as' ) ) {
			$this->ajax_view_admin_as();
		} else {
			// Admin selector ajax return (fallback).
			add_action( 'wp_ajax_view_admin_as', array( $this, 'ajax_view_admin_as' ) );
			//add_action( 'wp_ajax_nopriv_view_admin_as', array( $this, 'ajax_view_admin_as' ) );
		}
	}

	/**
	 * AJAX handler.
	 * Gets the AJAX input. If it is valid: store it in the current user metadata.
	 *
	 * Store format: array( VIEW_NAME => VIEW_DATA );
	 *
	 * @since   0.1
	 * @since   1.3     Added caps handler.
	 * @since   1.4     Added module handler.
	 * @since   1.5     Validate a nonce.
	 *                  Added global and user setting handler.
	 * @since   1.6     Moved to this class from main class.
	 * @since   1.6.2   Added visitor view handler + JSON view data.
	 * @access  public
	 * @return  void
	 */
	public function ajax_view_admin_as() {

		$data = VAA_API::get_ajax_request( $this->store->get_nonce(), 'view_admin_as' );
		if ( ! $data ) {
			wp_send_json_error( __( 'Cheatin uh?', VIEW_ADMIN_AS_DOMAIN ) );
			die();
		}

		define( 'VAA_DOING_AJAX', true );

		$success = false;
		$data = $this->validate_view_data( json_decode( stripslashes( $data ), true ) );

		// Stop selecting the same view!
		if ( $this->is_current_view( $data ) ) {
			wp_send_json_error( array(
				'type' => 'notice',
				'content' => esc_html__( 'This view is already selected!', VIEW_ADMIN_AS_DOMAIN ),
			) );
		}

		// Update user metadata with selected view.
		if ( isset( $data['role'] ) || isset( $data['user'] ) || isset( $data['visitor'] ) ) {
			$success = $this->update_view( $data );
			if ( isset( $data['visitor'] ) ) {
				$success = array(
					'redirect' => esc_url( home_url() ),
				);
			}
		}
		elseif ( isset( $data['caps'] ) ) {
			$success = $this->ajax_handler_caps( $data['caps'] );
		}
		elseif ( isset( $data['reset'] ) ) {
			$success = $this->reset_view();
		}
		elseif ( isset( $data['user_setting'] ) ) {
			$success = $this->store->store_settings( $data['user_setting'], 'user' );
		}
		elseif ( isset( $data['setting'] ) ) {
			$success = $this->store->store_settings( $data['setting'], 'global' );
		}
		else {
			// Maybe a module?
			foreach ( $data as $key => $key_data ) {
				$module = $this->vaa->get_modules( $key );
				if ( is_callable( array( $module, 'ajax_handler' ) ) ) {
					$success = $module->ajax_handler( $key_data );
					if ( ! is_bool( $success ) && ! empty( $success ) ) {
						wp_send_json_error( $success );
					} elseif ( false === $success ) {
						break; // Default error
					}
				}
				break; // @todo Maybe check for multiple keys
			}
		}

		if ( $success ) {
			wp_send_json_success( $success ); // ahw yeah.
			die();
		}

		wp_send_json_error( array(
			'type' => 'error',
			'content' => esc_html__( 'Something went wrong, please try again.', VIEW_ADMIN_AS_DOMAIN ),
		) );
		die();
	}

	/**
	 * Handles the caps view since it's a bit more complex
	 *
	 * @since   1.6.x
	 * @access  private
	 * @param   array  $data  Caps view data
	 * @return  bool
	 */
	private function ajax_handler_caps( $data ) {
		$success = false;
		$db_view = $this->store->get_view( 'caps' );

		// Check if the selected caps are equal to the default caps.
		if ( VAA_API::array_equal( $this->store->get_curUser()->allcaps, $data ) ) {
			// The selected caps are equal to the current user default caps so we can reset the view.
			$this->reset_view();
			if ( $db_view ) {
				// The user was in a custom caps view.
				$success = true; // and continue.
			} else {
				// The user was in his default view, notify the user.
				wp_send_json_error( array(
					'type' => 'notice',
					'content' => esc_html__( 'These are your default capabilities!', VIEW_ADMIN_AS_DOMAIN ),
				) );
			}
		} else {
			// Store the selected caps.
			$this->store->set_caps( array_map( 'absint', $data ) );

			// Check if the new caps selection is different.
			if ( VAA_API::array_equal( $db_view, $this->store->get_caps() ) ) {
				wp_send_json_error( array(
					'type' => 'notice',
					'content' => esc_html__( 'This view is already selected!', VIEW_ADMIN_AS_DOMAIN ),
				) );
			} else {
				$success = $this->update_view( array( 'caps' => $this->store->get_caps() ) );
			}
		}
		return $success;
	}

	/**
	 * Check if the provided data is the same as the current view.
	 *
	 * @todo Improve this..
	 *
	 * @since   1.6.x
	 * @param   array  $data
	 * @return  bool
	 */
	private function is_current_view( $data ) {
		if ( 1 !== count( $this->store->get_view() ) ) {
			return false;
		}
		// Check role view
		if ( isset( $data['role'] ) ) {
			return (bool) ( $this->store->get_view( 'role' ) && $this->store->get_view( 'role' ) === $data['role'] );
		}
		// Check user view
		if ( isset( $data['user'] ) ) {
			return (bool) ( $this->store->get_view( 'user' ) && (int) $this->store->get_view( 'user' ) === (int) $data['user'] );
		}
		// Check visitor view
		if ( isset( $data['visitor'] ) ) {
			return (bool) ( $this->store->get_view( 'visitor' ) );
		}
		return false;
	}

	/**
	 * Get the available view types
	 *
	 * @since   1.6.x
	 * @access  public
	 * @return  array
	 */
	public function get_view_types() {
		if ( ! empty( $this->viewTypes ) ) {
			return $this->viewTypes;
		}

		/**
		 * Add basic view types for automated use in JS and through VAA.
		 *
		 * - Menu items require the class vaa-{TYPE}-item (through the add_node() meta key).
		 * - Menu items require the rel attribute for the view data to be send (string or numeric).
		 * - Menu items require the href attribute (the node needs to be an <a> element), I'd set it to '#'.
		 *
		 * @since  1.6.2
		 * @param  array  $array  Empty array.
		 * @return array  An array of strings (view types).
		 */
		$this->viewTypes = array_unique( array_merge(
			array_filter( apply_filters( 'view_admin_as_view_types', array() ), 'is_string' ),
			array( 'user', 'role', 'caps', 'visitor' )
		) );

		return $this->viewTypes;
	}

	/**
	 * Get current view for the current session.
	 *
	 * @since   1.3.4
	 * @since   1.5     Single mode.
	 * @since   1.6     Moved to this class from main class.
	 * @since   1.6.x   Private method. Use store.
	 * @access  public
	 * @return  array
	 */
	public function get_view() {

		$view_mode = $this->store->get_userSettings( 'view_mode' );

		// Static actions.
		$request = VAA_API::get_normal_request( $this->store->get_nonce(), 'view_admin_as', 'get' );
		if ( $request && 'browse' === $view_mode ) {
			$view = $this->validate_view_data( json_decode( stripcslashes( html_entity_decode( $request ) ), true ) );
			$this->update_view( $view );
			if ( is_network_admin() ) {
				wp_redirect( network_admin_url() );
			} else {
				wp_redirect( admin_url() );
			}
		}

		// Single mode.
		$request = VAA_API::get_normal_request( $this->store->get_nonce(), 'view_admin_as' );
		if ( $request && 'single' === $view_mode ) {
			return $this->validate_view_data( json_decode( stripcslashes( $request ), true ) );
		}

		// Browse mode.
		if ( 'browse' === $view_mode ) {
			$meta = $this->store->get_userMeta( 'views' );
			if ( isset( $meta[ $this->store->get_curUserSession() ]['view'] ) ) {
				return $this->validate_view_data( $meta[ $this->store->get_curUserSession() ]['view'] );
			}
		}

		return null;
	}

	/**
	 * Update view for the current session.
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class.
	 * @access  public
	 *
	 * @param   array  $data  The view data.
	 * @return  bool
	 */
	public function update_view( $data ) {
		$data = $this->validate_view_data( $data );
		if ( $data ) {
			$meta = $this->store->get_userMeta( 'views' );
			// Make sure it is an array (no array means no valid data so we can safely clear it).
			if ( ! is_array( $meta ) ) {
				$meta = array();
			}
			// Add the new view metadata and expiration date.
			$meta[ $this->store->get_curUserSession() ] = array(
				'view' => $data,
				'expire' => ( time() + (int) $this->viewExpiration ),
			);
			// Update metadata (returns: true on success, false on failure).
			return $this->store->update_userMeta( $meta, 'views', true );
		}
		return false;
	}

	/**
	 * Reset view to default.
	 * This function is also attached to the wp_login and wp_logout hook.
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class.
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string   $user_login  (not used) String provided by the wp_login hook.
	 * @param   WP_User  $user        User object provided by the wp_login hook.
	 * @return  bool
	 */
	public function reset_view( $user_login = null, $user = null ) {

		// function is not triggered by the wp_login action hook.
		if ( null === $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			// Do not use the store as it currently doesn't support a different user ID.
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// Check if this user session has metadata.
			if ( isset( $meta['views'][ $this->store->get_curUserSession() ] ) ) {
				// Remove metadata from this session.
				unset( $meta['views'][ $this->store->get_curUserSession() ] );
				// Update current metadata if it is the current user.
				if ( $this->store->get_curUser() && (int) $this->store->get_curUser()->ID === (int) $user->ID ) {
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure).
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed.
		return true;
	}

	/**
	 * Delete all expired View Admin As metadata for this user.
	 * This function is also attached to the wp_login hook.
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class.
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string   $user_login  (not used) String provided by the wp_login hook.
	 * @param   WP_User  $user        User object provided by the wp_login hook.
	 * @return  bool
	 */
	public function cleanup_views( $user_login = null, $user = null ) {

		// function is not triggered by the wp_login action hook.
		if ( null === $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			// Do not use the store as it currently doesn't support a different user ID.
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// If meta exists, loop it.
			if ( isset( $meta['views'] ) ) {

				foreach ( (array) $meta['views'] as $key => $value ) {
					// Check expiration date: if it doesn't exist or is in the past, remove it.
					if ( ! isset( $meta['views'][ $key ]['expire'] ) || time() > (int) $meta['views'][ $key ]['expire'] ) {
						unset( $meta['views'][ $key ] );
					}
				}
				// Update current metadata if it is the current user.
				if ( $this->store->get_curUser() && (int) $this->store->get_curUser()->ID === (int) $user->ID ) {
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure).
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no cleanup needed.
		return true;
	}

	/**
	 * Reset all View Admin As metadata for this user.
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class.
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string   $user_login  (not used) String provided by the wp_login hook.
	 * @param   WP_User  $user        User object provided by the wp_login hook.
	 * @return  bool
	 */
	public function reset_all_views( $user_login = null, $user = null ) {

		// function is not triggered by the wp_login action hook.
		if ( null === $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// If meta exists, reset it.
			if ( isset( $meta['views'] ) ) {
				$meta['views'] = array();
				// Update current metadata if it is the current user.
				if ( $this->store->get_curUser() && (int) $this->store->get_curUser()->ID === (int) $user->ID ) {
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure).
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed.
		return true;
	}

	/**
	 * Validate data before changing the view.
	 *
	 * @since   1.5
	 * @since   1.6     Moved to this class from main class.
	 * @since   1.6.x   Changed name to `validate_view_data` from `validate_view_as_data`
	 * @access  public
	 *
	 * @param   array  $data  Unvalidated data.
	 * @return  array  $data  Validated data.
	 */
	public function validate_view_data( $data ) {

		if ( ! is_array( $data ) || empty( $data ) ) {
			return array();
		}

		$allowed_keys = array( 'setting', 'user_setting', 'reset' );

		// Add module keys to the allowed keys.
		foreach ( $this->vaa->get_modules() as $key => $val ) {
			$allowed_keys[] = $key;
		}

		$allowed_keys = array_unique( array_merge(
			$this->get_view_types(),
			$allowed_keys
		) );

		// We only want allowed keys and data, otherwise it's not added through this plugin.
		foreach ( $data as $key => $value ) {

			// Check for keys that are not allowed.
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				unset( $data[ $key ] );
				continue;
			}

			/**
			 * Validate the data.
			 * Hook is required!
			 *
			 * @since  1.6.2
			 * @since  1.6.x   Added third $key parameter
			 * @param  null    $null   Ensures a validation filter is required.
			 * @param  mixed   $value  Unvalidated view data.
			 * @param  string  $key    The data key.
			 * @return mixed   validated view data.
			 */
			$data[ $key ] = apply_filters( 'view_admin_as_validate_view_data_' . $key, null, $value, $key );

			if ( null === $data[ $key ] ) {
				unset( $data[ $key ] );
			}
		}
		return $data;
	}

	/**
	 * Validate data for role view type
	 *
	 * @since   1.6.x
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	function validate_view_data_caps( $null, $data ) {
		// Caps data must be an array
		if ( is_array( $data ) ) {
			// The data is an array, most likely from the database.
			$data = array_map( 'absint', $data );
			ksort( $data ); // Sort the new caps the same way we sort the existing caps.
			return $data;
		}
		return $null;
	}

	/**
	 * Validate data for role view type
	 *
	 * @since   1.6.x
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	function validate_view_data_role( $null, $data ) {
		// Role data must be a string and exists in the loaded array of roles.
		if ( is_string( $data ) && array_key_exists( $data, $this->store->get_roles() ) ) {
			return $data;
		}
		return $null;
	}

	/**
	 * Validate data for user view type
	 *
	 * @since   1.6.x
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	function validate_view_data_user( $null, $data ) {
		// User data must be a number and exists in the loaded array of user id's.
		if ( is_numeric( $data ) && array_key_exists( (int) $data, $this->store->get_userids() ) ) {
			return $data;
		}
		return $null;
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
	 * @return  VAA_View_Admin_As_Controller
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
