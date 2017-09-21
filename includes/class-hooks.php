<?php
/**
 * View Admin As - Class Hooks
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Hooks class that holds all registered actions and filters.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @link    https://github.com/JoryHogeveen/view-admin-as/wiki/Actions-&-Filters
 * @since   1.8
 * @version 1.8
 */
class VAA_View_Admin_As_Hooks
{
	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since   1.8
	 * @access  protected
	 * @var     array  $actions  The actions registered with WordPress.
	 */
	protected $_actions = array();

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since   1.8
	 * @access  protected
	 * @var     array  $filters  The filters registered with WordPress.
	 */
	protected $_filters = array();

	/**
	 * Convert callable into an identifier.
	 *
	 * @since   1.8
	 * @access  protected
	 * @see     _wp_filter_build_unique_id()
	 * @param   string    $hook      The name of the WordPress hook (that is, actions or filters).
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  The priority at which the function would be fired. Default is 10.
	 * @return  string
	 */
	protected function _get_identifier( $hook, $callback, $priority ) {
		// Fallback since `_wp_filter_build_unique_id()` is a private WP function.
		if ( ! function_exists( '_wp_filter_build_unique_id' ) ) {
			if ( is_array( $callback ) ) {
				if ( is_object( $callback[0] ) ) {
					$callback[0] = get_class( $callback[0] );
					$callback = implode( '->', $callback );
				} else {
					$callback = implode( '::', $callback );
				}
			}
			return $callback;
		}
		return _wp_filter_build_unique_id( $hook, $callback, $priority );
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since   1.8
	 * @see     add_action()
	 * @param   string    $hook           The name of the WordPress action.
	 * @param   callable  $callback       The callable.
	 * @param   int       $priority       (optional) The priority at which the function should be fired. Default is 10.
	 * @param   int       $accepted_args  (optional) The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_action( $hook, $callback, $priority, $accepted_args );
		$this->_actions = $this->_add( $this->_actions, $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since   1.8
	 * @see     add_filter()
	 * @param   string    $hook           The name of the WordPress filter.
	 * @param   callable  $callback       The callable.
	 * @param   int       $priority       (optional) The priority at which the function should be fired. Default is 10.
	 * @param   int       $accepted_args  (optional) The number of arguments that should be passed to the $callback. Default is 1
	 */
	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_filter( $hook, $callback, $priority, $accepted_args );
		$this->_filters = $this->_add( $this->_filters, $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the hooks into a single collection.
	 *
	 * @since   1.8
	 * @access  protected
	 * @param   array[]   $hooks          The collection of hooks (that is, actions or filters).
	 * @param   string    $hook           The name of the WordPress filter that is being registered.
	 * @param   callable  $callback       The callable.
	 * @param   int       $priority       The priority at which the function should be fired.
	 * @param   int       $accepted_args  The number of arguments that should be passed to the $callback.
	 * @return  array  The collection of actions and filters registered with WordPress.
	 */
	protected function _add( $hooks, $hook, $callback, $priority, $accepted_args ) {
		if ( ! isset( $hooks[ $hook ] ) ) {
			$hooks[ $hook ] = array();
		}
		if ( ! isset( $hooks[ $hook ][ $priority ] ) ) {
			$hooks[ $hook ][ $priority ] = array();
		}
		$hooks[ $hook ][ $priority ][ $this->_get_identifier( $hook, $callback, $priority ) ] = array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
		ksort( $hooks[ $hook ] );
		return $hooks;
	}

	/**
	 * Remove an action from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @see     remove_action()
	 * @param   string    $hook      The name of the WordPress action.
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_action( $hook, $callback, $priority = 10 ) {
		$priority = $this->_validate_priority( $this->_actions, $hook, $callback, $priority );
		remove_action( $hook, $callback, $priority );
		$this->_actions = $this->_remove( $this->_actions, $hook, $callback, $priority );
	}

	/**
	 * Remove a filter from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @see     remove_filter()
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_filter( $hook, $callback, $priority = 10 ) {
		$priority = $this->_validate_priority( $this->_filters, $hook, $callback, $priority );
		remove_filter( $hook, $callback, $priority );
		$this->_filters = $this->_remove( $this->_filters, $hook, $callback, $priority );
	}

	/**
	 * A utility function that is used to remove registered hooks from a single collection.
	 *
	 * @since   1.8
	 * @access  protected
	 * @param   array[]   $hooks     The collection of hooks (that is, actions or filters).
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  The priority at which the function should be fired.
	 * @return  array  The collection of actions and filters registered with WordPress.
	 */
	protected function _remove( $hooks, $hook, $callback, $priority ) {
		unset( $hooks[ $hook ][ $priority ][ $this->_get_identifier( $hook, $callback, $priority ) ] );
		if ( empty( $hooks[ $hook ][ $priority ] ) ) {
			unset( $hooks[ $hook ][ $priority ] );
		}
		return $hooks;
	}

	/**
	 * Remove all hooks from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @param   string    $hook      The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_all_hooks( $hook, $priority = false ) {
		$this->remove_all_actions( $hook, $priority );
		$this->remove_all_filters( $hook, $priority );
	}

	/**
	 * Remove all actions from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @see     remove_all_actions()
	 * @param   string    $hook      The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_all_actions( $hook, $priority = false ) {
		remove_all_actions( $hook, $priority );
		$this->_actions = $this->_remove_all( $this->_actions, $hook, $priority );
	}

	/**
	 * Remove all filters from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @see     remove_all_filters()
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_all_filters( $hook, $priority = false ) {
		remove_all_filters( $hook, $priority );
		$this->_filters = $this->_remove_all( $this->_filters, $hook, $priority );
	}

	/**
	 * A utility function that is used to remove all registered hooks from a single collection.
	 *
	 * @since   1.8
	 * @access  protected
	 * @param   array[]   $hooks     The collection of hooks (that is, actions or filters).
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   int|bool  $priority  The priority at which the function should be fired.
	 * @return  array  The collection of actions and filters registered with WordPress.
	 */
	protected function _remove_all( $hooks, $hook, $priority ) {
		if ( false !== $priority ) {
			unset( $hooks[ $hook ][ $priority ] );
			return $hooks;
		}
		unset( $hooks[ $hook ] );
		return $hooks;
	}

} // End class VAA_View_Admin_As_Hooks.
