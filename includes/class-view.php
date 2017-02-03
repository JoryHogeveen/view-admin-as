<?php
/**
 * View Admin As - Class View
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * View handler class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.6.4
 * @uses    VAA_View_Admin_As_Class_Base Extends class
 */
final class VAA_View_Admin_As_View extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_View
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
	 * VAA_View_Admin_As_View constructor.
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

		// Reset view will always return true
		add_filter( 'view_admin_as_validate_view_data_reset', '__return_true' );
		// Visitor view is always a boolean
		add_filter( 'view_admin_as_validate_view_data_visitor', '__return_true' );

		// Validation checks for caps, role and user views
		add_filter( 'view_admin_as_validate_view_data_caps', array( $this, 'validate_view_data_caps' ), 10, 2 );
		add_filter( 'view_admin_as_validate_view_data_role', array( $this, 'validate_view_data_role' ), 10, 2 );
		add_filter( 'view_admin_as_validate_view_data_user', array( $this, 'validate_view_data_user' ), 10, 2 );

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

		// Get the current view (returns false if not found).
		$this->store->set_view( $this->get_view() );

		// Short circuit needed for visitor view (BEFORE the current user is set).
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST['action'] ) && 'view_admin_as' === $_POST['action'] ) {
			$this->ajax_view_admin_as();
		}

		// Admin selector ajax return (fallback).
		add_action( 'wp_ajax_view_admin_as', array( $this, 'ajax_view_admin_as' ) );
		//add_action( 'wp_ajax_nopriv_view_admin_as', array( $this, 'ajax_view_admin_as' ) );

		if ( $this->store->get_view() ) {
			$this->do_view();
		}
	}

	/**
	 * Apply view data.
	 *
	 * @since   1.6.3    Put logic in it's own function.
	 * @access  private
	 * @return  void
	 */
	private function do_view() {

		// @since  1.6.4  Set the current user as the selected user by default.
		$this->store->set_selectedUser( $this->store->get_curUser() );

		/**
		 * USER & VISITOR.
		 * Current user object views (switches current user).
		 *
		 * @since  0.1    User view.
		 * @since  1.6.2  Visitor view.
		 */
		if ( $this->store->get_view( 'user' ) || $this->store->get_view( 'visitor' ) ) {

			/**
			 * Change current user object so changes can be made on various screen settings.
			 * wp_set_current_user() returns the new user object.
			 *
			 * If it is a visitor view it will convert the false return from 'user' to 0.
			 */
			$this->store->set_selectedUser( wp_set_current_user( (int) $this->store->get_view( 'user' ) ) );

			// @since  1.6.2  Set the caps for this view (user view).
			if ( isset( $this->store->get_selectedUser()->allcaps ) ) {
				$this->store->set_selectedCaps( $this->store->get_selectedUser()->allcaps );
			}
		}

		/**
		 * ROLES & CAPS.
		 * Capability based views (modifies current user).
		 *
		 * @since  0.1  Role view
		 * @since  1.3  Caps view
		 */
		if ( $this->store->get_view( 'role' ) || $this->store->get_view( 'caps' ) ) {
			$this->init_user_modifications();
		}

		/**
		 * View data is set, apply the view.
		 * This hook can be used by other modules to enable a view.
		 *
		 * Temporary modifications to the current user are set on priority 99.
		 * This functionality has a separate action: `vaa_view_admin_as_modify_current_user`.
		 *
		 * @since  1.6.3
		 * @param  array
		 */
		do_action( 'vaa_view_admin_as_do_view', $this->store->get_view() );

		/**
		 * Force own locale on view.
		 *
		 * @since  1.6.1
		 */
		if ( 'yes' === $this->store->get_userSettings( 'freeze_locale' )
			&& (int) $this->store->get_curUser()->ID !== (int) $this->store->get_selectedUser()->ID
		) {
			add_action( 'init', array( $this, 'freeze_locale' ), 0 );
		}
	}

	/**
	 * Adds the actions and filters to modify the current user object.
	 * Can only be run once.
	 *
	 * @since   1.6.3
	 * @access  public
	 * @return  void
	 */
	public function init_user_modifications() {
		static $done;
		if ( $done ) return;

		add_action( 'vaa_view_admin_as_do_view', array( $this, 'modify_user' ), 99 );

		/**
		 * Make sure the $current_user view data isn't overwritten again by switch_blog functions.
		 *
		 * @see  This filter is documented in wp-includes/ms-blogs.php
		 * @since  1.6.3
		 */
		add_action( 'switch_blog', array( $this, 'modify_user' ) );

		/**
		 * Prevent some meta updates for the current user while in modification to the current user are active.
		 *
		 * @since  1.6.3
		 */
		add_filter( 'update_user_metadata' , array( $this, 'filter_prevent_update_user_metadata' ), 999999999, 3 );

		/**
		 * Get capabilities and user level from current user view object instead of database.
		 *
		 * @since  1.6.4
		 */
		add_filter( 'get_user_metadata' , array( $this, 'filter_overrule_get_user_metadata' ), 999999999, 3 );

		/**
		 * Change the capabilities (map_meta_cap is better for compatibility with network admins).
		 *
		 * @since  0.1
		 */
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 999999999, 3 ); //4

		// @todo maybe also use the user_has_cap filter?
		//add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 999999999, 4 );

		$done = true;
	}

	/**
	 * Update the current user's WP_User instance with the current view capabilities.
	 *
	 * @since   1.6.3
	 * @access  public
	 * @return  void
	 */
	public function modify_user() {

		// Can be the current or selected WP_User object (depending on the user view).
		$user = $this->store->get_selectedUser();

		/**
		 * Validate if the WP_User properties are still accessible.
		 * Currently everything is public but this could possibly change.
		 *
		 * @since  1.6.3
		 */
		$accessible = false;
		$public_props = get_object_vars( $user );
		if (    array_key_exists( 'caps', $public_props )
		     && array_key_exists( 'allcaps', $public_props )
			 && is_callable( array( $user, 'get_role_caps' ) )
		) {
			$accessible = true;
		}

		/**
		 * Role view.
		 *
		 * @since  0.1
		 */
		if ( $this->store->get_roles( $this->store->get_view( 'role' ) ) instanceof WP_Role ) {
			if ( ! $accessible ) {
				// @since  1.6.2  Set the caps for this view here instead of in the mapper function.
				$this->store->set_selectedCaps(
					$this->store->get_roles( $this->store->get_view( 'role' ) )->capabilities
				);
			} else {
				// @since  1.6.3  Set the current user's role to the current view.
				$user->caps = array( $this->store->get_view( 'role' ) => 1 );
				// Sets the `allcaps` and `roles` properties correct.
				$user->get_role_caps();
			}
		}

		/**
		 * Caps view.
		 *
		 * @since  1.3
		 */
		if ( is_array( $this->store->get_view( 'caps' ) ) ) {
			if ( ! $accessible ) {
				$this->store->set_selectedCaps( $this->store->get_view( 'caps' ) );
			} else {
				// @since  1.6.3  Set the current user's caps (roles) to the current view.
				$user->allcaps = array_merge(
					(array) array_filter( $this->store->get_view( 'caps' ) ),
					(array) $user->caps // Contains the current user roles.
				);
			}
		}

		if ( $accessible ) {
			$this->store->set_selectedCaps( $user->allcaps );
		}

		/**
		 * Allow other modules to hook after the initial changes to the current user.
		 *
		 * @since  1.6.3
		 * @since  1.6.4    Changed name (was: `vaa_view_admin_as_modify_current_user`).
		 * @param  WP_User  $user        The modified user object.
		 * @param  bool     $accessible  Are the needed WP_User properties and methods accessible?
		 */
		do_action( 'vaa_view_admin_as_modify_user', $user, $accessible );
	}

	/**
	 * Prevent some updates to the current user like roles and capabilities.
	 * to prevent problems when making changes within a view.
	 *
	 * IMPORTANT! This filter should ONLY be used when a view is selected!
	 *
	 * @since   1.6.3
	 * @access  public
	 * @see     init_current_user_modifications()
	 *
	 * @see     'update_user_metadata' filter
	 * @link    https://codex.wordpress.org/Plugin_API/Filter_Reference/update_(meta_type)_metadata
	 * @link    http://hookr.io/filters/update_user_metadata/
	 *
	 * @global  wpdb    $wpdb
	 * @param   null    $null       Whether to allow updating metadata for the given type.
	 * @param   int     $object_id  Object ID.
	 * @param   string  $meta_key   Meta key.
	 * @return  mixed
	 */
	public function filter_prevent_update_user_metadata( $null, $object_id, $meta_key ) {
		global $wpdb;
		$user = $this->store->get_selectedUser();

		// Check if the object being updated is the current user.
		if ( (int) $user->ID === (int) $object_id ) {

			// Capabilities meta key check.
			if ( empty( $user->cap_key ) ) {
				$user->cap_key = $wpdb->get_blog_prefix() . 'capabilities';
			}

			// Do not update the current user capabilities or user level while in a view.
			if ( in_array( $meta_key, array(
				$user->cap_key,
				$wpdb->get_blog_prefix() . 'capabilities',
				$wpdb->get_blog_prefix() . 'user_level',
			), true ) ) {
				return false;
			}
		}
		return $null;
	}

	/**
	 * Return view roles when getting the current user data to prevent reloading current user data within a view.
	 *
	 * IMPORTANT! This filter should ONLY be used when a view is selected!
	 *
	 * @since   1.6.4
	 * @access  public
	 * @see     init_current_user_modifications()
	 *
	 * @see     'get_user_metadata' filter
	 * @link    https://codex.wordpress.org/Plugin_API/Filter_Reference/get_(meta_type)_metadata
	 *
	 * @global  wpdb    $wpdb
	 * @param   null    $null       The value update_metadata() should return.
	 * @param   int     $object_id  Object ID.
	 * @param   string  $meta_key   Meta key.
	 * @return  mixed
	 */
	public function filter_overrule_get_user_metadata( $null, $object_id, $meta_key ) {
		global $wpdb;
		$user = $this->store->get_selectedUser();

		// Check if the object being updated is the current user.
		if ( (int) $user->ID === (int) $object_id ) {

			// Return the current user capabilities or user level while in a view.
			// Always return an array to fix $single usage.

			// Current user cap key should be equal to the meta_key for capabilities.
			if ( ! empty( $user->cap_key ) && $meta_key === $user->cap_key ) {
				return array( $user->caps );
			}
			// Fallback if cap_key doesn't exists.
			if ( $meta_key === $wpdb->get_blog_prefix() . 'capabilities' ) {
				return array( $user->caps );
			}
			if ( $meta_key === $wpdb->get_blog_prefix() . 'user_level' ) {
				if ( ! isset( $user->user_level ) ) {
					// Make sure the key exists. Result will be filtered in `filter_prevent_update_user_metadata()`
					$user->update_user_level_from_caps();
				}
				return array( $user->user_level );
			}
		}
		return $null;
	}

	/**
	 * Change capabilities when the user has selected a view.
	 * If the capability isn't in the chosen view, then make the value for this capability empty and add "do_not_allow".
	 *
	 * @since   0.1
	 * @since   1.5     Changed function name to map_meta_cap (was change_caps).
	 * @since   1.6     Moved to this class from main class.
	 * @since   1.6.2   Use logic from current_view_can().
	 * @since   1.6.3   Prefix function name with `filter_`.
	 * @access  public
	 *
	 * @param   array   $caps     The actual (mapped) cap names, if the caps are not mapped this returns the requested cap.
	 * @param   string  $cap      The capability that was requested.
	 * @param   int     $user_id  The ID of the user.
	 * param   array   $args     Adds the context to the cap. Typically the object ID (not used).
	 * @return  array   $caps
	 */
	public function filter_map_meta_cap( $caps, $cap, $user_id ) {

		if ( (int) $this->store->get_selectedUser()->ID !== (int) $user_id ) {
			return $caps;
		}

		$filter_caps = (array) $this->store->get_selectedCaps();

		foreach ( (array) $caps as $actual_cap ) {
			if ( ! $this->current_view_can( $actual_cap, $filter_caps ) ) {
				// Regular users.
				$caps[ $cap ] = 0;
				// Network admins.
				$caps[] = 'do_not_allow';
			}
		}

		return $caps;
	}

	/**
	 * Overwrite the user's capabilities.
	 *
	 * @since   1.6.3
	 * @param   array    $allcaps  All the capabilities of the user.
	 * @param   array    $caps     Actual capabilities for meta capability.
	 * @param   array    $args     [0] Requested capability.
	 *                             [1] User ID.
	 *                             [2] Associated object ID.
	 * @param   WP_User  $user     (WP 3.7+) The user object.
	 * @return  array
	 */
	public function filter_user_has_cap( $allcaps, $caps, $args, $user = null ) {
		$user_id = ( $user ) ? $user->ID : $args[1];
		if ( ! is_numeric( $user_id ) || (int) $user_id !== (int) $this->store->get_selectedUser()->ID ) {
			return $allcaps;
		}
		return $this->store->get_selectedCaps();
	}

	/**
	 * Similar function to current_user_can().
	 *
	 * @since   1.6.2
	 * @param   string  $cap   The capability.
	 * @param   array   $caps  (optional) Capabilities to compare to.
	 *                         Defaults to the selected caps for the current view.
	 * @return  bool
	 */
	public function current_view_can( $cap, $caps = array() ) {

		if ( empty( $caps ) ) {
			$caps = $this->store->get_selectedCaps();
		}

		if ( is_array( $caps )
		    && array_key_exists( $cap, $caps )
		    && 1 === (int) $caps[ $cap ]
		    && 'do_not_allow' !== $caps[ $cap ]
		) {
			return true;
		}
		return false;
	}

	/**
	 * Ajax call validator. Verifies caller and nonce.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @return  bool
	 */
	public function is_valid_ajax() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX
		    && $this->is_vaa_enabled()
		    && isset( $_POST['view_admin_as'] )
		    && isset( $_POST['_vaa_nonce'] )
		    && wp_verify_nonce( $_POST['_vaa_nonce'], $this->store->get_nonce() )
		) {
			return true;
		}
		return false;
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

		if ( ! $this->is_valid_ajax() ) {
			wp_send_json_error( __( 'Cheatin uh?', VIEW_ADMIN_AS_DOMAIN ) );
			die();
		}

		define( 'VAA_DOING_AJAX', true );

		$success = false;
		$data = $this->validate_view_data( json_decode( stripslashes( $_POST['view_admin_as'] ), true ) );

		// Stop selecting the same view!
		if ( 1 === count( $this->store->get_view() )
		    && (
		       ( isset( $data['role'] ) && ( $this->store->get_view( 'role' ) && $this->store->get_view( 'role' ) === $data['role'] ) )
		    || ( isset( $data['user'] ) && ( $this->store->get_view( 'user' ) && (int) $this->store->get_view( 'user' ) === (int) $data['user'] ) )
		    || ( isset( $data['visitor'] ) && ( $this->store->get_view( 'visitor' ) ) )
		    )
		) {
			wp_send_json_error( array(
				'type' => 'error',
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
		} else {
			wp_send_json_error( array(
				'type' => 'error',
				'content' => esc_html__( 'Something went wrong, please try again.', VIEW_ADMIN_AS_DOMAIN ),
			) );
		}

		die(); // Just to make sure it's actually dead..
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
		// === comparison nor working due to key order.
		$difference = array_diff_key(
			array_filter( $this->store->get_curUser()->allcaps ),
			array_filter( $data )
		);
		// Check if the selected caps are equal to the default caps.
		if ( ! $difference ) {
			// The selected caps are equal to the current user default caps so we can reset the view.
			$this->reset_view();
			if ( $db_view ) {
				// The user was in a custom caps view.
				$success = true; // and continue.
			} else {
				// The user was in his default view, notify the user.
				wp_send_json_error( array(
					'type' => 'error',
					'content' => esc_html__( 'These are your default capabilities!', VIEW_ADMIN_AS_DOMAIN ),
				) );
			}
		} else {
			// Store the selected caps.
			$this->store->set_caps( array_map( 'absint', $data ) );

			// Check if the new caps selection is different.
			// === comparison not working due to key order.
			$difference = array_diff_key(
				array_filter( (array) $db_view ),
				array_filter( $this->store->get_caps() )
			);
			if ( ! $difference ) {
				wp_send_json_error( array(
					'type' => 'error',
					'content' => esc_html__( 'This view is already selected!', VIEW_ADMIN_AS_DOMAIN ),
				) );
			} else {
				$success = $this->update_view( array( 'caps' => $this->store->get_caps() ) );
			}
		}
		return $success;
	}

	/**
	 * Get current view for the current session.
	 *
	 * @since   1.3.4
	 * @since   1.5     Single mode.
	 * @since   1.6     Moved to this class from main class.
	 * @access  public
	 * @return  array|string|bool
	 */
	public function get_view() {

		// Static actions.
		if ( ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
		     && isset( $_GET['view_admin_as'] )
		     && 'browse' === $this->store->get_userSettings( 'view_mode' )
		     && isset( $_GET['_vaa_nonce'] )
		     && wp_verify_nonce( (string) $_GET['_vaa_nonce'], $this->store->get_nonce() )
		) {
			$view = $this->validate_view_data( json_decode( stripcslashes( html_entity_decode( $_GET['view_admin_as'] ) ), true ) );
			$this->update_view( $view );
			if ( is_network_admin() ) {
				wp_redirect( network_admin_url() );
			} else {
				wp_redirect( admin_url() );
			}
		}

		// Single mode.
		if ( ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
		     && isset( $_POST['view_admin_as'] )
		     && 'single' === $this->store->get_userSettings( 'view_mode' )
		     && isset( $_POST['_vaa_nonce'] )
		     && wp_verify_nonce( (string) $_POST['_vaa_nonce'], $this->store->get_nonce() )
		) {
			return $this->validate_view_data( json_decode( stripcslashes( $_POST['view_admin_as'] ), true ) );
		}

		// Browse mode.
		if ( 'browse' === $this->store->get_userSettings( 'view_mode' ) ) {
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

		$allowed_keys = array( 'setting', 'user_setting', 'reset', 'caps', 'role', 'user', 'visitor' );

		// Add module keys to the allowed keys.
		foreach ( $this->vaa->get_modules() as $key => $val ) {
			$allowed_keys[] = $key;
		}

		// @since  1.6.2  Filter is documented in VAA_View_Admin_As::enqueue_scripts (includes/class-vaa.php).
		$allowed_keys = array_unique( array_merge(
			array_filter( apply_filters( 'view_admin_as_view_types', array() ), 'is_string' ),
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
			 * @param  null    $null          Ensures a validation filter is required.
			 * @param  mixed   $data[ $key ]  Unvalidated view data.
			 * @param  string  $key           The data key.
			 * @return mixed   validated view data.
			 */
			$data[ $key ] = apply_filters( 'view_admin_as_validate_view_data_' . $key, null, $data[ $key ], $key );

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
	 * Set the locale for the current view.
	 *
	 * @since   1.6.1
	 * @access  public
	 */
	public function freeze_locale() {
		if ( function_exists( 'get_user_locale' ) && function_exists( 'switch_to_locale' ) ) {
			$locale = get_user_locale( $this->store->get_curUser()->ID );
			if ( get_locale() !== $locale ) {
				switch_to_locale( $locale );
			}
			return true;
		}
		return false;
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
	 * @return  VAA_View_Admin_As_View
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
