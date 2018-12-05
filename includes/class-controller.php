<?php
/**
 * View Admin As - Class Controller
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * View controller class. Handles all view data.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.7.0
 * @version 1.8.3
 * @uses    \VAA_View_Admin_As_Base Extends class
 */
final class VAA_View_Admin_As_Controller extends VAA_View_Admin_As_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6.0
	 * @static
	 * @var    \VAA_View_Admin_As_Controller
	 */
	private static $_instance = null;

	/**
	 * Expiration time for view data.
	 *
	 * @since  1.3.4  (as $metaExpiration).
	 * @since  1.6.2  Moved from `VAA_View_Admin_As`.
	 * @var    int
	 */
	private $viewExpiration = 86400; // one day: ( 24 * 60 * 60 ).

	/**
	 * VAA_View_Admin_As_Controller constructor.
	 *
	 * @since   1.6.0
	 * @since   1.6.1  `$vaa` param.
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		// When a user logs in or out, reset the view to default.
		$this->add_action( 'wp_login',  array( $this, 'cleanup_views' ), 10, 2 );
		$this->add_action( 'wp_login',  array( $this, 'reset_view' ), 10, 2 );
		$this->add_action( 'wp_logout', array( $this, 'reset_view' ) );

		// Not needed, the delete_user actions already remove all metadata, keep code for possible future use.
		//$this->add_action( 'remove_user_from_blog', array( $this->store, 'delete_user_meta' ) );
		//$this->add_action( 'wpmu_delete_user', array( $this->store, 'delete_user_meta' ) );
		//$this->add_action( 'wp_delete_user', array( $this->store, 'delete_user_meta' ) );

		/**
		 * Change expiration time for view meta.
		 *
		 * @example  You can set it to 1 to always clear everything after login.
		 * @example  0 will be overwritten!
		 *
		 * @since  1.6.2
		 * @param  int  $viewExpiration  86400 (1 day in seconds).
		 * @return int
		 */
		$this->viewExpiration = absint( apply_filters( 'view_admin_as_view_expiration', $this->viewExpiration ) );
	}

	/**
	 * Initializes after VAA is enabled.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @return  void
	 */
	public function init() {

		/**
		 * Reset view to default if something goes wrong.
		 *
		 * @since    0.1.0
		 * @since    1.2.0  Only check for key
		 * @example  http://www.your.domain/wp-admin/?reset-view
		 */
		if ( VAA_API::is_request( 'reset-view', 'get' ) ) {
			$this->reset_view();
		}
		/**
		 * Clear all user views.
		 *
		 * @since    1.3.4
		 * @example  http://www.your.domain/wp-admin/?reset-all-views
		 */
		if ( VAA_API::is_request( 'reset-all-views', 'get' ) ) {
			$this->reset_all_views();
		}

		// Reset hook.
		$this->add_filter( 'view_admin_as_handle_ajax_reset', array( $this, 'reset_view' ) );

		// Validation & update hooks for visitor view.
		$this->add_filter( 'view_admin_as_validate_view_data_visitor', '__return_true' );
		$this->add_filter( 'view_admin_as_update_view_visitor', array( $this, 'filter_update_view' ), 10, 3 );

		// Get the current view.
		$this->store->set_view( $this->get_view() );

		// Short circuit needed for visitor view (BEFORE the current user is set).
		if ( VAA_API::is_ajax_request( 'view_admin_as' ) ) {
			$this->ajax_view_admin_as();
		} else {
			// Admin selector ajax return (fallback).
			$this->add_action( 'wp_ajax_view_admin_as', array( $this, 'ajax_view_admin_as' ) );
			//$this->add_action( 'wp_ajax_nopriv_view_admin_as', array( $this, 'ajax_view_admin_as' ) );
		}
	}

	/**
	 * AJAX listener.
	 * Gets the AJAX input. If it is valid: pass it to the handler.
	 *
	 * @since   0.1.0
	 * @since   1.3.0  Added caps handler.
	 * @since   1.4.0  Added module handler.
	 * @since   1.5.0  Validate a nonce.
	 *                 Added global and user setting handler.
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.6.2  Added visitor view handler + JSON view data.
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

		$data = $this->validate_data_keys( $data );

		// Stop selecting the same view!
		if ( $this->is_current_view( $data ) ) {
			wp_send_json_error(
				array(
					'type' => 'message',
					'text' => esc_html__( 'This view is already selected!', VIEW_ADMIN_AS_DOMAIN ),
				)
			);
		}

		$success = false;
		if ( ! empty( $data ) ) {
			$success = $this->update( $data );
		}

		if ( true === $success ) {
			wp_send_json_success( $success ); // ahw yeah.
			die();
		} elseif ( $success ) {
			wp_send_json( $success );
			die();
		}

		wp_send_json_error(
			array(
				'type' => 'error',
				'text' => esc_html__( 'Something went wrong, please try again.', VIEW_ADMIN_AS_DOMAIN ),
			)
		);
		die();
	}

	/**
	 * Applies the data input to update views and settings.
	 *
	 * @since   1.7.0
	 * @since   1.8.3  Renamed from `ajax_handler` + made public.
	 * @param   array  $data  Post data.
	 * @return  array|bool
	 */
	public function update( $data ) {
		$success    = false;
		$view_types = array();
		$data       = $this->validate_data_keys( $data );

		/**
		 * Update data return filters.
		 *
		 * @see     `view_admin_as_update_view_{$key}`
		 * @see     `view_admin_as_handle_ajax_{$key}`
		 *
		 * @since   1.7.0
		 * @param   null    $null    Null.
		 * @param   mixed   $value   View data value.
		 * @param   string  $key     View data key.
		 * @return  bool|array {
		 *     In case of array. Uses wp_json_return() when AJAX is used.
		 *     @type  bool   $success  Send JSON success or error?
		 *     @type  array  $data {
		 *         Optional extra data to send with the JSON return.
		 *         In case of a view the page normally refreshes.
		 *         @type  string  $redirect  (URL) Redirect the user? (Only works on success).
		 *         @type  string  $display   Options: `notice`   A notice type in the admin bar.
		 *                                            `popup`    A popup/overlay with content.
		 *         @type  string  $type      Options: `success`  Ureka! (green)      - Default when $success is true.
		 *                                            `error`    Send an error (red) - Default when $success is false.
		 *                                            `message`  Just a message (blue).
		 *                                            `warning`  Send a warning (orange).
		 *         @type  string  $text      The text to show.
		 *         @type  array   $list      Show multiple messages (Popup only).
		 *         @type  string  $textarea  Textarea content (Popup only).
		 *     }
		 * }
		 */
		foreach ( $data as $key => $value ) {
			if ( $this->is_view_type( $key ) ) {
				$view_types[] = $key;

				$success = apply_filters( 'view_admin_as_update_view_' . $key, null, $value, $key );
			} else {
				// @todo 1.9 Rename this to `view_admin_as_update_{$key}`
				$success = apply_filters( 'view_admin_as_handle_ajax_' . $key, null, $value, $key );
			}
			if ( true !== $success ) {
				break;
			}
		}

		if ( empty( $view_types ) ) {
			return $success;
		}

		// Update the view on success.
		if ( true === $success || ( isset( $success['success'] ) && true === $success['success'] ) ) {

			// Remove view type keys that are not in the new view anymore.
			$view = $this->store->get_view();
			$view = array_intersect_key( $view, array_flip( $view_types ) );
			$this->store->set_view( $view );

			$this->update_view();
		}

		return $success;
	}

	/**
	 * Update regular view types.
	 *
	 * @since   1.7.0
	 * @param   null    $null  Null.
	 * @param   mixed   $data  The view data.
	 * @param   string  $type  The view type.
	 * @return  bool|array
	 */
	public function filter_update_view( $null, $data, $type ) {
		$success = $null;
		if ( ! empty( $data ) && ! empty( $type ) ) {
			$this->store->set_view( $data, $type, true );
			$success = true;
		}
		if ( $success && 'visitor' === $type && VAA_API::is_admin() ) {
			$success = array(
				'success' => true,
				'data'    => array(
					'redirect' => esc_url( home_url() ),
				),
			);
		}
		return $success;
	}

	/**
	 * Check if the provided data is the same as the current view.
	 *
	 * @since   1.7.0
	 * @since   1.7.2  Data options: `null` for active view & `false` for only/single view.
	 * @param   mixed  $data  The view data to compare with.
	 * @param   bool   $type  Only compare a single view type instead of all view data?
	 *                        If set, the data value should be the single view type data or `null`.
	 *                        If data is `null` then it will return true if that view type is active.
	 *                        If data is `false` then it will return true if this is the only active view type.
	 * @return  bool
	 */
	public function is_current_view( $data, $type = null ) {
		if ( ! empty( $type ) ) {
			$current = $this->store->get_view( $type );
			if ( ! $current ) {
				return false;
			}
			if ( is_array( $data ) ) {
				return VAA_API::array_equal( $data, $current );
			}
			if ( null === $data ) {
				return true;
			}
			if ( false === $data ) {
				return ( 1 === count( $this->store->get_view() ) );
			}
			return ( (string) $data === (string) $current );
		}
		return VAA_API::array_equal( $data, $this->store->get_view() );
	}

	/**
	 * Is it a view type?
	 *
	 * @since   1.7.0
	 * @param   string  $type  View type name to check
	 * @return  bool
	 */
	public function is_view_type( $type ) {
		return ( in_array( $type, $this->get_view_types(), true ) );
	}

	/**
	 * Get the available view type keys.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @return  string[]
	 */
	public function get_view_types() {
		static $view_types;
		if ( ! is_null( $view_types ) ) return $view_types;

		$view_types   = array_keys( (array) view_admin_as()->get_view_types() );
		$view_types[] = 'visitor';

		/**
		 * Add basic view types for automated use in JS and through VAA.
		 *
		 * - Menu items require the class vaa-{TYPE}-item (through the add_node() meta key).
		 * - Menu items require the href attribute (the node needs to be an <a> element).
		 * @see \VAA_View_Admin_As_Form::do_view_title()
		 *
		 * @deprecated  1.8.0
		 *
		 * @since  1.6.2
		 * @param  array  $array  Empty array.
		 * @return array  An array of strings (view types).
		 */
		$dep_view_types = apply_filters( 'view_admin_as_view_types', array() );
		if ( $dep_view_types ) {

			/** @see https://developer.wordpress.org/reference/functions/apply_filters_deprecated/ */
			if ( function_exists( '_deprecated_hook' ) ) {
				_deprecated_hook( 'view_admin_as_view_types', 1.8, 'view_admin_as()->register_view_type()' );
			}

			$view_types = array_unique(
				array_merge(
					array_filter( $dep_view_types, 'is_string' ),
					$view_types
				)
			);
		}

		return $view_types;
	}

	/**
	 * Get current view for the current session.
	 *
	 * @since   1.3.4
	 * @since   1.5.0   Single mode.
	 * @since   1.6.0   Moved from `VAA_View_Admin_As`.
	 * @since   1.7.0   Private method. Use store.
	 * @access  private
	 * @return  array
	 */
	private function get_view() {

		$view_mode = $this->store->get_userSettings( 'view_mode' );

		if ( 'browse' === $view_mode ) {

			// Static actions.
			$request = VAA_API::get_normal_request( $this->store->get_nonce(), 'view_admin_as', 'get' );
			if ( $request ) {
				$this->store->set_view( $this->validate_view_data( $request ) );
				$this->update_view();
				// Trigger page refresh.
				// @todo fix WP referrer/nonce checks and allow switching on any page without ajax. See VAA_API.
				if ( is_network_admin() ) {
					wp_safe_redirect( network_admin_url(), 302, VIEW_ADMIN_AS_DOMAIN );
				} else {
					wp_safe_redirect( admin_url(), 302, VIEW_ADMIN_AS_DOMAIN );
				}
				die();
			}

			// Browse mode.
			$meta = $this->store->get_userMeta( 'views' );
			if ( isset( $meta[ $this->store->get_curUserSession() ]['view'] ) ) {
				return $this->validate_view_data( $meta[ $this->store->get_curUserSession() ]['view'] );
			}

		} elseif ( 'single' === $view_mode ) {

			// Single mode.
			$request = VAA_API::get_normal_request( $this->store->get_nonce(), 'view_admin_as' );
			if ( $request ) {
				return $this->validate_view_data( $request );
			}
		}

		return array();
	}

	/**
	 * Update view for the current session.
	 *
	 * @since   1.3.4
	 * @since   1.6.0   Moved from `VAA_View_Admin_As`.
	 * @since   1.8.3   Made public.
	 * @access  public
	 *
	 * @return  bool
	 */
	public function update_view() {
		$data = $this->validate_view_data( $this->store->get_view() );
		if ( $data ) {
			$meta = $this->store->get_userMeta( 'views' );
			// Make sure it is an array (no array means no valid data so we can safely clear it).
			if ( ! is_array( $meta ) ) {
				$meta = array();
			}
			// Add the new view metadata and expiration date.
			$meta[ $this->store->get_curUserSession() ] = array(
				'view'   => $data,
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
	 * @since   1.6.0   Moved from `VAA_View_Admin_As`.
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string    $user_login  (not used) String provided by the wp_login hook.
	 * @param   \WP_User  $user        User object provided by the wp_login hook.
	 * @return  bool
	 */
	public function reset_view( $user_login = null, $user = null ) {

		if ( null === $user ) {
			// Function is not triggered by the wp_login action hook.
			$user = $this->store->get_curUser();
		}
		if ( ! empty( $user->ID ) ) {
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
	 * @since   1.6.0   Moved from `VAA_View_Admin_As`.
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string    $user_login  (not used) String provided by the wp_login hook.
	 * @param   \WP_User  $user        User object provided by the wp_login hook.
	 * @return  bool
	 */
	public function cleanup_views( $user_login = null, $user = null ) {

		if ( null === $user ) {
			// Function is not triggered by the wp_login action hook.
			$user = $this->store->get_curUser();
		}
		if ( ! empty( $user->ID ) ) {
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
	 * @since   1.6.0   Moved from `VAA_View_Admin_As`.
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string    $user_login  (not used) String provided by the wp_login hook.
	 * @param   \WP_User  $user        User object provided by the wp_login hook.
	 * @return  bool
	 */
	public function reset_all_views( $user_login = null, $user = null ) {

		if ( null === $user ) {
			// Function is not triggered by the wp_login action hook.
			$user = $this->store->get_curUser();
		}
		if ( ! empty( $user->ID ) ) {
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
	 * Remove all unsupported keys.
	 *
	 * @since   1.7.0
	 * @param   array  $data  Input data.
	 * @return  array
	 */
	public function validate_data_keys( $data ) {

		if ( ! is_array( $data ) || empty( $data ) ) {
			return array();
		}

		$allowed_keys = array_unique(
			array_merge(
				// View types.
				$this->get_view_types(),
				// Module keys.
				array_keys( $this->vaa->get_modules() ),
				// VAA core keys.
				array( 'setting', 'user_setting', 'reset' )
			)
		);

		$data = array_intersect_key( $data, array_flip( $allowed_keys ) );

		return $data;
	}

	/**
	 * Validate data before changing the view.
	 *
	 * @since   1.5.0
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.7.0  Changed name to `validate_view_data` from `validate_view_as_data`
	 * @access  public
	 *
	 * @param   array  $data  Unvalidated data.
	 * @return  array  $data  Validated data.
	 */
	public function validate_view_data( $data ) {

		if ( ! is_array( $data ) || empty( $data ) ) {
			return array();
		}

		// Only leave keys that are view types.
		$data = array_intersect_key( $data, array_flip( $this->get_view_types() ) );

		// We only want allowed keys and data, otherwise it's not added through this plugin.
		foreach ( $data as $key => $value ) {

			/**
			 * Validate the data.
			 * Hook is required!
			 *
			 * @since  1.6.2
			 * @since  1.7.0   Added third `$key` parameter
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
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class.
	 * @return  \VAA_View_Admin_As_Controller  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Controller.
