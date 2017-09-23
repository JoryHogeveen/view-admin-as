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
		if ( function_exists( '_wp_filter_build_unique_id' ) ) {
			return _wp_filter_build_unique_id( $hook, $callback, $priority );
		}
		// Fallback since `_wp_filter_build_unique_id()` is a private WP function.
		if ( is_string( $callback ) ) {
			return $callback;
		}
		if ( is_object( $callback ) ) {
			$callback = array( $callback, '' );
		}
		if ( is_array( $callback ) ) {
			if ( is_object( $callback[0] ) ) {
				$callback[0] = get_class( $callback[0] );
				$callback = implode( '->', $callback );
			} else {
				$callback = implode( '::', $callback );
			}
		}
		return (string) $callback;
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

	/**
	 * Remove all plugin hooks from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @param   string    $hook      (optional) The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_own_hooks( $hook = null, $priority = false ) {
		$this->remove_own_actions( $hook, $priority );
		$this->remove_own_filters( $hook, $priority );
	}

	/**
	 * Remove all plugin actions from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @param   string    $hook      (optional) The name of the WordPress action.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_own_actions( $hook = null, $priority = false ) {
		$this->_actions = $this->_remove_own( $this->_actions, $hook, $priority, 'remove_action' );
	}

	/**
	 * Remove all plugin filters from the collection registered with WordPress.
	 *
	 * @since   1.8
	 * @param   string    $hook      (optional) The name of the WordPress filter.
	 * @param   int|bool  $priority  (optional) The priority at which the function would be fired. Default is 10.
	 */
	public function remove_own_filters( $hook = null, $priority = false ) {
		$this->_filters = $this->_remove_own( $this->_filters, $hook, $priority, 'remove_filter' );
	}

	/**
	 * A utility function that is used to remove all registered plugin hooks from a single collection.
	 *
	 * @since   1.8
	 * @access  protected
	 * @param   array[]   $hooks     The collection of hooks (that is, actions or filters).
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   int|bool  $priority  The priority at which the function should be fired.
	 * @param   callable  $function  The function to use for removal.
	 * @return  array  The collection of actions and filters registered with WordPress.
	 */
	protected function _remove_own( $hooks, $hook, $priority, $function ) {
		// Remove specific priority from hook.
		if ( false !== $priority ) {
			if ( isset( $hooks[ $hook ][ $priority ] ) ) {
				foreach ( (array) $hooks[ $hook ][ $priority ] as $args ) {
					// Remove it from WordPress.
					$this->$function( $hook, $args['callback'], $priority );
				}
				unset( $hooks[ $hook ][ $priority ] );
			}
			return $hooks;
		}
		// Remove specific hook.
		if ( null !== $hook ) {
			if ( isset( $hooks[ $hook ] ) ) {
				foreach ( (array) $hooks[ $hook ] as $priority => $foo ) {
					$hooks = $this->_remove_own( $hooks, $hook, $priority, $function );
				}
				unset( $hooks[ $hook ] );
			}
			return $hooks;
		}
		// Remove everything.
		foreach ( (array) $hooks as $hook => $foo ) {
			$hooks = $this->_remove_own( $hooks, $hook, false, $function );
		}
		return array(); // Should be empty by now.
	}

	/**
	 * Validates the priority value.
	 * If it's passed as `null` it will attempt to find it.
	 *
	 * @since   1.8
	 * @param   array[]   $hooks     The collection of hooks (that is, actions or filters).
	 * @param   string    $hook      The name of the WordPress filter.
	 * @param   callable  $callback  The callable.
	 * @param   int       $priority  The priority at which the function should be fired.
	 * @return  int  Default is 10.
	 */
	protected function _validate_priority( $hooks, $hook, $callback, $priority ) {
		if ( null === $priority ) {
			$priority = $this->_find_priority( $hooks, $hook, $callback );
			if ( ! $priority ) {
				return 10;
			}
		}
		return (int) $priority;
	}

	/**
	 * Finds the priority of a hook if unknown.
	 *
	 * @since   1.8
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
	 * @since   1.8
	 * @param   string|array  $keys  The hook array keys to look for. Each key stands for a level deeper in the array.
	 *                               Order: hook type >> hook name >> priority >> function id >> hook args.
	 *                               In case of a string it will stand for the hook type.
	 * @param   bool  $objects  Return the full object of a callback? Default is false, can cause PHP memory issues.
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
	 * @since   1.8
	 * @param   string|array  $keys  The hook array keys to look for. Each key stands for a level deeper in the array.
	 *                               Order: hook name >> priority >> function id >> hook args.
	 *                               In case of a string it will stand for the hook name.
	 * @param   bool  $objects  Return the full object of a callback? Default is false, can cause PHP memory issues.
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
	 * @since   1.8
	 * @param   string|array  $keys  The hook array keys to look for. Each key stands for a level deeper in the array.
	 *                               Order: hook name >> priority >> function id >> hook args.
	 *                               In case of a string it will stand for the hook name.
	 * @param   bool  $objects  Return the full object of a callback? Default is false, can cause PHP memory issues.
	 * @return  array[]|mixed
	 */
	public function _get_filters( $keys = null, $objects = false ) {
		$keys = (array) $keys;
		array_unshift( $keys, 'filters' );
		return $this->_get_hooks( $keys, $objects );
	}

	/**
	 * Convert object types into object class names instead of full object data.
	 * @since   1.8
	 * @param   array  $hooks  The collection of hooks (that is, actions or filters).
	 * @return  array
	 */
	protected function _convert_callback( $hooks ) {
		foreach ( (array) $hooks as $key => $val ) {
			if ( is_object( $val ) ) {
				$hooks[ $key ] = get_class( $val );
				continue;
			}
			if ( is_array( $val ) ) {
				$hooks[ $key ] = $this->_convert_callback( $val );
			}
		}
		return $hooks;
	}

} // End class VAA_View_Admin_As_Hooks.
