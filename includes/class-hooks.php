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
 * @since   1.8.0
 * @version 1.8.7
 */
class VAA_View_Admin_As_Hooks
{
	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since   1.8.0
	 * @access  protected
	 * @var     array  $actions  The actions registered with WordPress.
	 */
	protected $_actions = array();

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since   1.8.0
	 * @access  protected
	 * @var     array  $filters  The filters registered with WordPress.
	 */
	protected $_filters = array();

	/**
	 * Log of actions run through this instance.
	 *
	 * @since   1.8.7
	 * @access  protected
	 * @var     array  $filters  Actions run through this instance.
	 */
	protected $_logged_actions = array();

	/**
	 * Calls the callback functions that have been added to a filter hook.
	 * This method will also log the initial call and store the params.
	 *
	 * @since   1.8.7
	 * @param   string  $tag      The name of the filter hook.
	 * @param   mixed   $value    The value to filter.
	 * @param   mixed   ...$args  Additional parameters to pass to the callback functions.
	 * @return  mixed   The filtered value after all hooked functions are applied to it.
	 */
	public function do_action( $tag, $value ) {
		$args = func_get_args();

		$log = $args;
		array_shift( $log );
		if ( ! isset( $this->_logged_actions[ $tag ] ) ) {
			$this->_logged_actions[ $tag ] = array();
		}
		$this->_logged_actions[ $tag ][] = $log;

		return call_user_func_array( 'do_action', $args );
	}

	/**
	 * Retrieve the number of times an action is fired.
	 *
	 * @since   1.8.7
	 * @param   string  $tag  The name of the action hook.
	 * @return  int     The number of times action hook $tag is fired.
	 */
	public function did_action( $tag ) {
		return did_action( $tag );
	}

	/**
	 * Get the arguments from a specific action that has been fired.
	 *
	 * @since   1.8.7
	 * @param   string  $tag         The name of the action hook.
	 * @param   int     $occurrence  The # time it was fired.
	 * @param   bool    $objects     Return the full object of a callback? Default: false, can cause PHP memory issues.
	 * @return  array
	 */
	public function get_action_log( $tag = null, $occurrence = null, $objects = false ) {
		$log = VAA_API::get_array_data( $this->_logged_actions, $tag );
		if ( $log && is_int( $occurrence ) ) {
			// Subtract one since the counter starts at 0;
			$occurrence--;
			$log = VAA_API::get_array_data( $log, $occurrence );
		}
		if ( ! $objects ) {
			$log = $this->_convert_callback( $log );
		}
		return $log;
	}

	/**
	 * Convert callable into an identifier.
	 *
	 * @since   1.8.0
	 * @access  protected
	 * @see     _wp_filter_build_unique_id()
	 * @param   string    $hook      The name of the WordPress hook (that is, actions or filters).
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  The priority at which the function would be fired. Default: 10.
	 * @return  string
	 */
	protected function _get_identifier( $hook, $callback, $priority ) {
		if ( function_exists( '_wp_filter_build_unique_id' ) ) {
			return _wp_filter_build_unique_id( $hook, $callback, $priority );
		}
		// Fallback since `_wp_filter_build_unique_id()` is a private WP function.
		return VAA_API::callable_to_string( $callback );
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since   1.8.0
	 * @see     add_action()
	 * @param   string    $hook           The name of the WordPress action.
	 * @param   callable  $callback       The callable.
	 * @param   int       $priority       (optional) The priority at which the function should be fired. Default: 10.
	 * @param   int       $accepted_args  (optional) The number of arguments that should be passed to the $callback. Default: 1.
	 */
	public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_action( $hook, $callback, $priority, $accepted_args );
		$this->_actions = $this->_add( $this->_actions, $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @since   1.8.0
	 * @see     add_filter()
	 * @param   string    $hook           The name of the WordPress filter.
	 * @param   callable  $callback       The callable.
	 * @param   int       $priority       (optional) The priority at which the function should be fired. Default: 10.
	 * @param   int       $accepted_args  (optional) The number of arguments that should be passed to the $callback. Default: 1.
	 */
	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_filter( $hook, $callback, $priority, $accepted_args );
		$this->_filters = $this->_add( $this->_filters, $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the hooks into a single collection.
	 *
	 * @since   1.8.0
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
	 * @since   1.8.0
	 * @see     remove_action()
	 * @param   string    $hook      The name of the WordPress action.
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  (optional) The priority at which the function would be fired. Default: 10.
	 *                               Pass `null` to let this class try to find the priority.
	 */
	public function remove_action( $hook, $callback, $priority = 10 ) {
		$priority = $this->_validate_priority( $this->_actions, $hook, $callback, $priority );
		remove_action( $hook, $callback, $priority );
		$this->_actions = $this->_remove( $this->_actions, $hook, $callback, $priority );
	}

	/**
	 * Remove a filter from the collection registered with WordPress.
	 *
	 * @since   1.8.0
	 * @see     remove_filter()
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  (optional) The priority at which the function would be fired. Default: 10.
	 *                               Pass `null` to let this class try to find the priority.
	 */
	public function remove_filter( $hook, $callback, $priority = 10 ) {
		$priority = $this->_validate_priority( $this->_filters, $hook, $callback, $priority );
		remove_filter( $hook, $callback, $priority );
		$this->_filters = $this->_remove( $this->_filters, $hook, $callback, $priority );
	}

	/**
	 * A utility function that is used to remove registered hooks from a single collection.
	 *
	 * @since   1.8.0
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
	 * @since   1.8.0
	 * @param   string    $hook      The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default: false (all).
	 */
	public function remove_all_hooks( $hook, $priority = false ) {
		$this->remove_all_actions( $hook, $priority );
		$this->remove_all_filters( $hook, $priority );
	}

	/**
	 * Remove all actions from the collection registered with WordPress.
	 *
	 * @since   1.8.0
	 * @see     remove_all_actions()
	 * @param   string    $hook      The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default: false (all).
	 */
	public function remove_all_actions( $hook, $priority = false ) {
		remove_all_actions( $hook, $priority );
		$this->_actions = $this->_remove_all( $this->_actions, $hook, $priority );
	}

	/**
	 * Remove all filters from the collection registered with WordPress.
	 *
	 * @since   1.8.0
	 * @see     remove_all_filters()
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default: false (all).
	 */
	public function remove_all_filters( $hook, $priority = false ) {
		remove_all_filters( $hook, $priority );
		$this->_filters = $this->_remove_all( $this->_filters, $hook, $priority );
	}

	/**
	 * A utility function that is used to remove all registered hooks from a single collection.
	 *
	 * @since   1.8.0
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

	/**
	 * Remove all plugin hooks from the collection registered with WordPress.
	 *
	 * @since   1.8.0
	 * @param   string    $hook      (optional) The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default: false (all).
	 * @param   string    $class     (optional) Only remove filters from a specific class.
	 */
	public function remove_own_hooks( $hook = null, $priority = false, $class = '' ) {
		$this->remove_own_actions( $hook, $priority, $class );
		$this->remove_own_filters( $hook, $priority, $class );
	}

	/**
	 * Remove all plugin actions from the collection registered with WordPress.
	 *
	 * @since   1.8.0
	 * @param   string    $hook      (optional) The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default: false (all).
	 * @param   string    $class     (optional) Only remove filters from a specific class.
	 */
	public function remove_own_actions( $hook = null, $priority = false, $class = '' ) {
		$this->_actions = $this->_remove_own( $this->_actions, $hook, $priority, 'remove_action', $class );
	}

	/**
	 * Remove all plugin filters from the collection registered with WordPress.
	 *
	 * @since   1.8.0
	 * @param   string    $hook      (optional) The name of the WordPress filter.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default: false (all).
	 * @param   string    $class     (optional) Only remove filters from a specific class.
	 */
	public function remove_own_filters( $hook = null, $priority = false, $class = '' ) {
		$this->_filters = $this->_remove_own( $this->_filters, $hook, $priority, 'remove_filter', $class );
	}

	/**
	 * A utility function that is used to remove all registered plugin hooks from a single collection.
	 *
	 * Disable some PHPMD checks for this method.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @todo Refactor to enable above checks?
	 *
	 * @since   1.8.0
	 * @access  protected
	 * @param   array[]   $hooks     The collection of hooks (that is, actions or filters).
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   int|bool  $priority  The priority at which the function should be fired.
	 * @param   callable  $function  The function to use for removal.
	 * @param   string    $class     Only remove filters from a specific class.
	 * @return  array  The collection of actions and filters registered with WordPress.
	 */
	protected function _remove_own( $hooks, $hook, $priority, $function, $class ) {

		// Remove specific priority from hook.
		if ( false !== $priority ) {
			if ( isset( $hooks[ $hook ][ $priority ] ) ) {
				foreach ( (array) $hooks[ $hook ][ $priority ] as $id => $args ) {
					if ( $class ) {
						$class_compare = ( isset( $args['callback'][0] ) ) ? $args['callback'][0] : '';
						if ( is_object( $class_compare ) ) {
							$class_compare = get_class( $class_compare );
						}
						if ( $class !== $class_compare ) {
							continue;
						}
					}
					// Remove it from WordPress.
					$this->$function( $hook, $args['callback'], $priority );
					unset( $hooks[ $hook ][ $priority ][ $id ] );
				}
				if ( empty( $hooks[ $hook ][ $priority ] ) ) {
					unset( $hooks[ $hook ][ $priority ] );
				}
			}
			return $hooks;
		}

		// Remove specific hook.
		if ( null !== $hook ) {
			if ( isset( $hooks[ $hook ] ) ) {
				foreach ( (array) $hooks[ $hook ] as $priority => $foo ) {
					$hooks = $this->_remove_own( $hooks, $hook, $priority, $function, $class );
				}
				if ( empty( $hooks[ $hook ] ) ) {
					unset( $hooks[ $hook ] );
				}
			}
			return $hooks;
		}

		// Remove everything.
		foreach ( (array) $hooks as $hook => $foo ) {
			$hooks = $this->_remove_own( $hooks, $hook, false, $function, $class );
		}

		return $hooks;
	}

	/**
	 * Validates the priority value.
	 * If it's passed as `null` it will attempt to find it.
	 *
	 * @since   1.8.0
	 * @param   array[]   $hooks     The collection of hooks (that is, actions or filters).
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  The priority at which the function should be fired.
	 * @return  int  Default: 10.
	 */
	protected function _validate_priority( $hooks, $hook, $callback, $priority ) {
		if ( ! is_numeric( $priority ) ) {
			$priority = $this->_find_priority( $hooks, $hook, $callback );
			if ( ! is_numeric( $priority ) ) {
				return 10;
			}
		}
		return (int) $priority;
	}

	/**
	 * Finds the priority of a hook if unknown.
	 *
	 * @since   1.8.0
	 * @param   array[]   $hooks     The collection of hooks (that is, actions or filters).
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   callable  $callback  The callable.
	 * @return  int
	 */
	protected function _find_priority( $hooks, $hook, $callback ) {
		if ( ! isset( $hooks[ $hook ] ) ) {
			return null;
		}
		foreach ( (array) $hooks[ $hook ] as $priority => $registered ) {
			foreach ( $registered as $args ) {
				if ( $callback === $args['callback'] ) {
					return $priority;
				}
			}
		}
		return null;
	}

	/**
	 * Return all registered hooks data.
	 * Can be used for debugging.
	 *
	 * @since   1.8.0
	 * @param   string|array  $keys  The hook array keys to look for. Each key stands for a level deeper in the array.
	 *                               Order: hook type >> hook name >> priority >> function id >> hook args.
	 *                               In case of a string it will stand for the hook type.
	 * @param   bool  $objects  Return the full object of a callback? Default: false, can cause PHP memory issues.
	 * @return  array[]|mixed
	 */
	public function _get_hooks( $keys = null, $objects = false ) {
		$data = array(
			'actions' => $this->_actions,
			'filters' => $this->_filters,
		);
		if ( ! $objects ) {
			// Don't return full objects.
			$data = $this->_convert_callback( $data );
		}
		if ( $keys ) {
			$keys = (array) $keys;
			foreach ( $keys as $key ) {
				$data = VAA_API::get_array_data( $data, $key );
			}
		}
		return $data;
	}

	/**
	 * Return all registered actions.
	 * Can be used for debugging.
	 *
	 * @since   1.8.0
	 * @param   string|array  $keys  The hook array keys to look for. Each key stands for a level deeper in the array.
	 *                               Order: hook name >> priority >> function id >> hook args.
	 *                               In case of a string it will stand for the hook name.
	 * @param   bool  $objects  Return the full object of a callback? Default: false, can cause PHP memory issues.
	 * @return  array[]|mixed
	 */
	public function _get_actions( $keys = null, $objects = false ) {
		$keys = (array) $keys;
		array_unshift( $keys, 'actions' );
		return $this->_get_hooks( $keys, $objects );
	}

	/**
	 * Return all registered filters.
	 * Can be used for debugging.
	 *
	 * @since   1.8.0
	 * @param   string|array  $keys  The hook array keys to look for. Each key stands for a level deeper in the array.
	 *                               Order: hook name >> priority >> function id >> hook args.
	 *                               In case of a string it will stand for the hook name.
	 * @param   bool  $objects  Return the full object of a callback? Default: false, can cause PHP memory issues.
	 * @return  array[]|mixed
	 */
	public function _get_filters( $keys = null, $objects = false ) {
		$keys = (array) $keys;
		array_unshift( $keys, 'filters' );
		return $this->_get_hooks( $keys, $objects );
	}

	/**
	 * Convert object types into object class names instead of full object data.
	 * @since   1.8.0
	 * @param   array  $array  The collection of arrays that might contain objects.
	 * @return  array
	 */
	protected function _convert_callback( $array ) {
		foreach ( (array) $array as $key => $val ) {
			if ( is_object( $val ) ) {
				$array[ $key ] = get_class( $val );
				continue;
			}
			if ( is_array( $val ) ) {
				$array[ $key ] = $this->_convert_callback( $val );
			}
		}
		return $array;
	}

} // End class VAA_View_Admin_As_Hooks.
