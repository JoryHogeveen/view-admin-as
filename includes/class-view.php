<?php
/**
 * View Admin As - Class View
 *
 * View handler class
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.6
 * @version 1.6.2
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

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
	 * VAA_View_Admin_As_View constructor.
	 *
	 * @since   1.6
	 * @since   1.6.1  $vaa param
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		// When a user logs in or out, reset the view to default
		add_action( 'wp_login', array( $this, 'cleanup_views' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'reset_view' ), 10, 2 );
		add_action( 'wp_logout', array( $this, 'reset_view' ) );
	}

	/**
	 * Initializes after VAA is enabled
	 *
	 * @since   1.6
	 * @access  public
	 * @return  void
	 */
	public function init() {

		// Reset view to default if something goes wrong, example: http://www.your.domain/wp-admin/?reset-view
		if ( isset( $_GET['reset-view'] ) ) {
			$this->reset_view();
		}
		// Clear all user views, example: http://www.your.domain/wp-admin/?reset-all-views
		if ( isset( $_GET['reset-all-views'] ) ) {
			$this->reset_all_views();
		}

		// Admin selector ajax return
		add_action( 'wp_ajax_view_admin_as', array( $this, 'ajax_view_admin_as' ) );
		//add_action( 'wp_ajax_nopriv_view_admin_as', array( $this, 'ajax_view_admin_as' ) );

		// Get the current view (returns false if not found)
		$this->store->set_viewAs( $this->get_view() );

		if ( $this->store->get_viewAs() ) {

			// Change current user object so changes can be made on various screen settings
			// wp_set_current_user() returns the new user object
			if ( $this->store->get_viewAs('user') ) {

				// @since  1.6.1  Force own locale on view
				if ( 'yes' == $this->store->get_userSettings('freeze_locale') ) {
					add_action( 'init', array( $this, 'freeze_locale' ) );
				}

				$this->store->set_selectedUser( wp_set_current_user( $this->store->get_viewAs('user') ) );

				// @since  1.6.2  Set the caps for this view
				if ( is_object( $this->store->get_selectedUser() ) ) {
					$this->store->set_selectedCaps( $this->store->get_selectedUser()->allcaps );
				}
			}

			if ( $this->store->get_viewAs('role') || $this->store->get_viewAs('caps') ) {

				// @since  1.6.2  Set the caps for this view
				if ( $this->store->get_viewAs('role') && $this->store->get_roles() ) {
					// Role view
					$this->store->set_selectedCaps( $this->store->get_roles( $this->store->get_viewAs('role') )->capabilities );
				} elseif ( $this->store->get_viewAs('caps') ) {
					// Caps view
					$this->store->set_selectedCaps( $this->store->get_viewAs('caps') );
				}

				// Change the capabilities (map_meta_cap is better for compatibility with network admins)
				add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 999999999, 4 );
			}

			// @since  1.6.2  Check for the visitor view
			if ( $this->store->get_viewAs('visitor') ) {

				// Short circuit needed for visitor view BEFORE the current user is set
				if ( defined('DOING_AJAX') && DOING_AJAX && 'view_admin_as' == $_POST['action'] ) {
					$this->ajax_view_admin_as();
				}

				// Set the current user to 0/false if viewing as a site visitor
				$this->store->set_selectedUser( wp_set_current_user( 0 ) );
			}
		}

	}

	/**
	 * Change capabilities when the user has selected a view
	 * If the capability isn't in the chosen view, then make the value for this capability empty and add "do_not_allow"
	 *
	 * @since   0.1
	 * @since   1.5     Changed function name to map_meta_cap (was change_caps)
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 *
	 * @param   array   $caps     The actual (mapped) cap names, if the caps are not mapped this returns the requested cap
	 * @param   string  $cap      The capability that was requested
	 * @param   int     $user_id  The ID of the user (not used)
	 * @param   array   $args     Adds the context to the cap. Typically the object ID (not used)
	 * @return  array   $caps
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		$filter_caps = $this->store->get_selectedCaps();

		if ( ! empty( $filter_caps ) ) {
			foreach ( $caps as $actual_cap ) {
				if ( ! $this->current_view_can( $actual_cap, $filter_caps ) ) {
					// Regular
					$caps[ $cap ] = '';
					// Network admins
					$caps[] = 'do_not_allow';
				}
			}
		}

		return $caps;
	}

	/**
	 * Similar function to current_user_can()
	 *
	 * @since   1.6.2
	 * @param   string  $cap
	 * @param   array   $caps  Optional, defaults to the selected caps for the current view
	 * @return  bool
	 */
	public function current_view_can( $cap, $caps = array() ) {

		if ( empty( $caps ) ) {
			$caps = $this->store->get_selectedCaps();
		}

		if (   is_array( $caps )
		    && array_key_exists( $cap, $caps )
		    && 1 == (int) $caps[ $cap ]
		    && 'do_not_allow' !== $caps[ $cap ]
		) {
			return true;
		}
		return false;
	}

	/**
	 * AJAX handler
	 * Gets the AJAX input. If it is valid: store it in the current user metadata
	 *
	 * Store format: array( VIEW_NAME => VIEW_DATA );
	 *
	 * @since   0.1
	 * @since   1.3     Added caps key
	 * @since   1.4     Added module keys
	 * @since   1.5     Validate a nonce
	 *                  Added global and user setting keys
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 * @return  void
	 */
	public function ajax_view_admin_as() {

		if (   ! defined('DOING_AJAX')
		    || ! DOING_AJAX
		    || ! $this->is_vaa_enabled()
		    || ! isset( $_POST['view_admin_as'] )
		    || ! isset( $_POST['_vaa_nonce'] )
		    || ! wp_verify_nonce( $_POST['_vaa_nonce'], $this->store->get_nonce() )
		) {
			wp_send_json_error( __('Cheatin uh?', 'view-admin-as') );
			die();
		}

		define( 'VAA_DOING_AJAX', true );

		$success = false;
		$view_as = $this->validate_view_as_data( $_POST['view_admin_as'] );

		// Stop selecting the same view! :)
		if (   ( isset( $view_as['role'] ) && ( $this->store->get_viewAs('role') && $this->store->get_viewAs('role') == $view_as['role'] ) )
		    || ( isset( $view_as['user'] ) && ( $this->store->get_viewAs('user') && $this->store->get_viewAs('user') == $view_as['user'] ) )
		    || ( isset( $view_as['visitor'] ) && ( $this->store->get_viewAs('visitor') ) )
		    || ( isset( $view_as['reset'] ) && false == $this->store->get_viewAs() )
		) {
			wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('This view is already selected!', 'view-admin-as') ) );
		}

		// Update user metadata with selected view
		if ( isset( $view_as['role'] ) || isset( $view_as['user'] ) ) {
			$success = $this->update_view( $view_as );
		}
		elseif ( isset( $view_as['caps'] ) ) {
			// Check if the selected caps are equal to the default caps
			if ( $this->store->get_caps() != $view_as['caps'] ) {
				foreach ( $this->store->get_caps() as $key => $value ) {
					// If the caps are valid (do not force append, see get_caps() & set_array_data() ), change them
					if ( isset( $view_as['caps'][ $key ] ) && $view_as['caps'][ $key ] == 1 ) {
						$this->store->set_caps( 1, $key );
					} else {
						$this->store->set_caps( 0, $key );
					}
				}
				$success = $this->update_view( array( 'caps' => $this->store->get_caps() ) );
				if ( $success != true ) {
					$db_view_value = $this->get_view();
					if ( $db_view_value['caps'] == $this->store->get_caps() ) {
						wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('This view is already selected!', 'view-admin-as') ) );
					}
				}
			} else {
				// The selected caps are equal to the current user default caps so we can reset the view
				$this->reset_view();
				if ( $this->store->get_viewAs('caps') ) {
					// The user was in a custom caps view, reset is valid
					$success = true; // and continue
				} else {
					// The user is in his default view, reset is invalid
					wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('These are your default capabilities!', 'view-admin-as') ) );
				}
			}
		}
		elseif ( isset( $view_as['visitor'] ) ) {
			$success = $this->update_view( $view_as );
		}
		elseif ( isset( $view_as['reset'] ) ) {
			$success = $this->reset_view();
		}
		elseif ( isset( $view_as['user_setting'] ) ) {
			$success = $this->store->store_settings( $view_as['user_setting'], 'user' );
		}
		elseif ( isset( $view_as['setting'] ) ) {
			$success = $this->store->store_settings( $view_as['setting'], 'global' );
		}
		else {
			// Maybe a module?
			foreach ( $view_as as $key => $data ) {
				if ( array_key_exists( $key, $this->get_modules() ) ) {
					$module = $this->get_modules( $key );
					if ( is_callable( array( $module, 'ajax_handler' ) ) ) {
						$success = $module->ajax_handler( $data );
						if ( is_string( $success ) && ! empty( $success ) ) {
							wp_send_json_error( $success );
						}
					}
				}
				break; // POSSIBLY TODO: Only the first key is actually used at this point
			}
		}

		if ( true == $success ) {
			wp_send_json_success(); // ahw yeah
		} else {
			wp_send_json_error( array( 'type' => 'error', 'content' => esc_html__('Something went wrong, please try again.', 'view-admin-as') ) ); // fail
		}

		die(); // Just to make sure it's actually dead..
	}

	/**
	 * Get current view for the current session
	 *
	 * @since   1.3.4
	 * @since   1.5     Single mode
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 * @return  array|string|bool
	 */
	public function get_view() {

		// Static actions
		if ( ( ! defined('DOING_AJAX') || ! DOING_AJAX )
		     && isset( $_GET['view_admin_as'] )
		     && $this->store->get_userSettings('view_mode') == 'browse'
		     && isset( $this->store->get_curUser()->ID )
		     && isset( $_GET['_vaa_nonce'] )
		     && wp_verify_nonce( (string) $_GET['_vaa_nonce'], $this->store->get_nonce() )
		) {
			$view = $this->validate_view_as_data( json_decode( stripcslashes( html_entity_decode( $_GET['view_admin_as'] ) ), true ) );
			$this->update_view( $view );
			if ( is_network_admin() ) {
				wp_redirect( network_admin_url() );
			} else {
				wp_redirect( admin_url() );
			}
		}

		// Single mode
		if ( ( ! defined('DOING_AJAX') || ! DOING_AJAX )
		     && isset( $_POST['view_admin_as'] )
		     && $this->store->get_userSettings('view_mode') == 'single'
		     && isset( $this->store->get_curUser()->ID )
		     && isset( $_POST['_vaa_nonce'] )
		     && wp_verify_nonce( (string) $_POST['_vaa_nonce'], $this->store->get_nonce() )
		) {
			return $this->validate_view_as_data( json_decode( stripcslashes( $_POST['view_admin_as'] ), true ) );
		}

		// Browse mode
		if ( $this->store->get_userSettings('view_mode') == 'browse' ) {
			$meta = $this->store->get_userMeta('views');
			if ( isset( $meta[ $this->store->get_curUserSession() ]['view'] ) ) {
				return $this->validate_view_as_data( $meta[ $this->store->get_curUserSession() ]['view'] );
			}
		}

		return false;
	}

	/**
	 * Update view for the current session
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 *
	 * @param   array|bool  $data
	 * @return  bool
	 */
	public function update_view( $data = false ) {
		if ( false != $data && $data = $this->validate_view_as_data( $data ) ) {
			$meta = $this->store->get_userMeta('views');
			// Make sure it is an array (no array means no valid data so we can safely clear it)
			if ( ! is_array( $meta ) ) {
				$meta = array();
			}
			// Add the new view metadata and expiration date
			$meta[ $this->store->get_curUserSession() ] = array(
				'view' => $data,
				'expire' => ( time() + $this->store->get_metaExpiration() ),
			);
			// Update metadata (returns: true on success, false on failure)
			return $this->store->update_userMeta( $meta, 'views', true );
		}
		return false;
	}

	/**
	 * Reset view to default
	 * This function is also attached to the wp_login and wp_logout hook
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string|bool  $user_login  (not used) String provided by the wp_login hook
	 * @param   object|bool  $user        User object provided by the wp_login hook
	 * @return  bool
	 */
	public function reset_view( $user_login = false, $user = false ) {

		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// Check if this user session has metadata
			if ( isset( $meta['views'][ $this->store->get_curUserSession() ] ) ) {
				// Remove metadata from this session
				unset( $meta['views'][ $this->store->get_curUserSession() ] );
				// Update current metadata if it is the current user
				if ( $this->store->get_curUser() && $this->store->get_curUser()->ID == $user->ID ){
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed
		return true;
	}

	/**
	 * Delete all expired View Admin As metadata for this user
	 * This function is also attached to the wp_login hook
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string|bool  $user_login  (not used) String provided by the wp_login hook
	 * @param   object|bool  $user        User object provided by the wp_login hook
	 * @return  bool
	 */
	public function cleanup_views( $user_login = false, $user = false ) {

		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// If meta exists, loop it
			if ( isset( $meta['views'] ) ) {
				if ( ! is_array( $meta['views'] ) ) {
					$meta['views'] = array();
				}
				foreach ( $meta['views'] as $key => $value ) {
					// Check expiration date: if it doesn't exist or is in the past, remove it
					if ( ! isset( $meta['views'][ $key ]['expire'] ) || time() > $meta['views'][ $key ]['expire'] ) {
						unset( $meta['views'][ $key ] );
					}
				}
				if ( empty( $meta['views'] ) ) {
					$meta['views'] = false;
				}
				// Update current metadata if it is the current user
				if ( $this->store->get_curUser() && $this->store->get_curUser()->ID == $user->ID ){
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no cleanup needed
		return true;
	}

	/**
	 * Reset all View Admin As metadata for this user
	 *
	 * @since   1.3.4
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 * @link    https://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 *
	 * @param   string|bool  $user_login  (not used) String provided by the wp_login hook
	 * @param   object|bool  $user        User object provided by the wp_login hook
	 * @return  bool
	 */
	public function reset_all_views( $user_login = false, $user = false ) {

		// function is not triggered by the wp_login action hook
		if ( false == $user ) {
			$user = $this->store->get_curUser();
		}
		if ( isset( $user->ID ) ) {
			$meta = get_user_meta( $user->ID, $this->store->get_userMetaKey(), true );
			// If meta exists, reset it
			if ( isset( $meta['views'] ) ) {
				$meta['views'] = false;
				// Update current metadata if it is the current user
				if ( $this->store->get_curUser() && $this->store->get_curUser()->ID == $user->ID ){
					$this->store->set_userMeta( $meta );
				}
				// Update db metadata (returns: true on success, false on failure)
				return update_user_meta( $user->ID, $this->store->get_userMetaKey(), $meta );
			}
		}
		// No meta found, no reset needed
		return true;
	}

	/**
	 * Validate data before changing the view
	 *
	 * @since   1.5
	 * @since   1.6     Moved to this class from main class
	 * @access  public
	 *
	 * @param   array       $view_as
	 * @return  array|bool  $view_as
	 */
	public function validate_view_as_data( $view_as ) {

		$allowed_keys = array( 'setting', 'user_setting', 'reset', 'caps', 'role', 'user', 'visitor' );

		// Add module keys to the allowed keys
		foreach ( $this->get_modules() as $key => $val ) {
			$allowed_keys[] = $key;
		}

		// We only want allowed keys and data, otherwise it's not added through this plugin.
		if ( is_array( $view_as ) ) {
			foreach ( $view_as as $key => $value ) {
				// Check for keys that are not allowed
				if ( ! in_array( $key, $allowed_keys ) ) {
					unset( $view_as[ $key ] );
				}
				switch ( $key ) {
					case 'caps':
						// Make sure we have the latest added capabilities
						$this->store->store_caps();
						if ( ! $this->store->get_caps() ) {
							unset( $view_as['caps'] );
							continue;
						}
						if ( is_array( $view_as['caps'] ) ) {
							// The data is an array, most likely from the database
							foreach ( $view_as['caps'] as $cap_key => $cap_value ) {
								if ( ! array_key_exists( $cap_key, $this->store->get_caps() ) ) {
									unset( $view_as['caps'][ $cap_key ] );
								}
							}
						} elseif ( is_string( $view_as['caps'] ) ) {
							// The data is a string so we'll need to convert it to an array
							$new_caps = explode( ',', $view_as['caps'] );
							$view_as['caps'] = array();
							foreach ( $new_caps as $cap_key => $cap_value ) {
								$cap = explode( ':', $cap_value );
								// Make sure the exploded values are valid
								if ( isset( $cap[1] ) && array_key_exists( $cap[0], $this->store->get_caps() ) ) {
									$view_as['caps'][ strip_tags( $cap[0] ) ] = (int) $cap[1];
								}
							}
							if ( is_array( $view_as['caps'] ) ) {
								ksort( $view_as['caps'] ); // Sort the new caps the same way we sort the existing caps
							} else {
								unset( $view_as['caps'] );
							}
						} else {
							// Caps data is not valid
							unset( $view_as['caps'] );
						}
						break;
					case 'role':
						// Role data must be a string and exists in the loaded array of roles
						if ( ! is_string( $view_as['role'] ) || ! $this->store->get_roles() || ! array_key_exists( $view_as['role'], $this->store->get_roles() ) ) {
							unset( $view_as['role'] );
						}
						break;
					case 'user':
						// User data must be a number and exists in the loaded array of user id's
						if ( ! is_numeric( $view_as['user'] ) || ! $this->store->get_userids() || ! array_key_exists( (int) $view_as['user'], $this->store->get_userids() ) ) {
							unset( $view_as['user'] );
						}
						break;
					case 'visitor':
						$view_as['visitor'] = (bool) $view_as['visitor'];
						break;
				}
			}
			return $view_as;
		}
		return false;
	}

	/**
	 * Set the locale for the current view
	 *
	 * @since   1.6.1
	 * @access  public
	 */
	public function freeze_locale() {
		if ( function_exists( 'get_user_locale' ) && function_exists( 'switch_to_locale' ) ) {
			$locale = get_user_locale( $this->store->get_curUser()->ID );
			if ( $locale != get_locale() ) {
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
	 * @param   VAA_View_Admin_As  $caller  The referrer class
	 * @return  VAA_View_Admin_As_View
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' == get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $caller );
			}
			return self::$_instance;
		}
		return null;
	}

} // end class