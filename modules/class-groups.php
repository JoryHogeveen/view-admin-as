<?php
/**
 * View Admin As - Groups plugin
 *
 * Compatibility class for the Groups plugin
 * Loaded from VAA_View_Admin_As_Compat->init() (includes/class-compat.php)
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.7
 * @version 1.7
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

add_action( 'vaa_view_admin_as_modules_loaded', array( 'VAA_View_Admin_As_Groups', 'get_instance' ) );

final class VAA_View_Admin_As_Groups extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.7
	 * @static
	 * @var    VAA_View_Admin_As_Groups
	 */
	private static $_instance = null;

	/**
	 * The existing groups
	 *
	 * @since  1.7
	 * @see    groups/lib/core/class-groups-group.php -> Groups_Groups
	 * @var    array of objects: Groups_Group
	 */
	private $groups;

	/**
	 * @since  1.7
	 * @see    groups/lib/core/class-groups-group.php -> Groups_Groups
	 * @var    Groups_Group
	 */
	private $selectedGroup;

	/**
	 * Populate the instance and validate Groups plugin
	 *
	 * @since   1.7
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( is_callable( array( 'Groups_Group', 'get_groups' ) )
		  && defined( 'GROUPS_ADMINISTER_GROUPS' )
		  && current_user_can( GROUPS_ADMINISTER_GROUPS )
		  && ! is_network_admin()
		) {

			$this->vaa->register_module( array(
				'id'       => 'groups',
				'instance' => self::$_instance
			) );

			$this->store_groups();

			add_action( 'vaa_view_admin_as_init', array( $this, 'init' ) );
		}
	}

	/**
	 * Initialize the Groups module
	 * @since   1.7
	 * @access  public
	 */
	public function init() {

		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), 40, 2 );

		if ( $this->get_viewAs('groups') && $this->get_groups( $this->get_viewAs('groups') ) ) {

			$this->selectedGroup = new Groups_Group( $this->get_viewAs('groups') );

			add_filter( 'vaa_admin_bar_viewing_as_title', array( $this, 'vaa_viewing_as_title' ) );

			// Filter group capabilities
			add_filter( 'groups_user_can', array( $this, 'groups_user_can' ), 20, 3 );

			// Filter user-group relationships
			add_filter( 'groups_user_is_member', array( $this, 'groups_user_is_member' ), 20, 3 );

			/**
			 * Filters
			 *
			 * - groups_post_access_user_can_read_post
			 *     class-groups-post-access -> line 419
			 */
		}
	}

	/**
	 * Ajax handler, called from main ajax handler
	 *
	 * @since   1.7
	 * @access  public
	 * @param   $data
	 * @return  bool
	 */
	public function ajax_handler( $data ) {

		if ( ! defined('VAA_DOING_AJAX')
		  || ! VAA_DOING_AJAX
		  || ! $this->is_vaa_enabled()
		) {
			return false;
		}

		if ( is_numeric( $data ) && $this->get_groups( (int) $data ) ) {
			$this->vaa->view()->update_view( array( 'groups' => (int) $data ) );
			return true;
		}
		return false;
	}

	/**
	 * Filter for the current view
	 * Only use this function if the current view is a validated group object!
	 *
	 * @see  groups/lib/core/class-groups-group.php -> Groups_Groups->can()
	 *
	 * @since   1.7
	 * @access  public
	 * @param   bool    $result
	 * @param   object  $object
	 * @param   string  $cap
	 * @return  bool
	 */
	public function groups_user_can( $result, $object = null, $cap = '' ) {
		// Fallback PHP < 5.4 due to apply_filters_ref_array
		// See https://codex.wordpress.org/Function_Reference/apply_filters_ref_array
		if ( is_array( $result ) ) {
			$cap = $result[2];
			//$object = $result[1];
			$result = $result[0];
		}

		if (    $this->selectedGroup
		     && is_callable( array( $this->selectedGroup, 'can' ) )
		     && ! $this->selectedGroup->can( $cap )
		) {
			$result = false;
		}
		return $result;
	}

	/**
	 * Filter the user-group relation
	 *
	 * @see  groups/lib/core/class-groups-user-group.php -> Groups_User_Group->read()
	 *
	 * @since   1.7
	 * @access  public
	 * @param   bool  $result
	 * @param   int   $user_id
	 * @param   int   $group_id
	 * @return  bool|object
	 */
	public function groups_user_is_member( $result, $user_id, $group_id ) {
		if ( $this->selectedGroup && $group_id == $this->selectedGroup->group->group_id ) {
			$result = $this->selectedGroup->group;
		}
		return $result;
	}

	/**
	 * Change the VAA admin bar menu title
	 * @since   1.7
	 * @access  public
	 * @param   string  $title
	 * @return  string
	 */
	public function vaa_viewing_as_title( $title ) {
		if ( $this->get_viewAs('groups') && $this->get_groups( $this->get_viewAs('groups') ) ) {
			$title = __( 'Viewing as group', 'view-admin-as' ) . ': ' . $this->get_groups( $this->get_viewAs('groups') )->name;
		}
		return $title;
	}

	/**
	 * Add the Groups admin bar items
	 * @since   1.7
	 * @access  public
	 * @param   WP_Admin_Bar  $admin_bar
	 * @param   string        $root
	 */
	public function admin_bar_menu( $admin_bar, $root ) {

		if ( $this->get_groups() && 0 < count( $this->get_groups() ) ) {

			$admin_bar->add_group( array(
				'id'        => $root . '-groups',
				'parent'    => $root,
				'meta'      => array(
					'class'     => 'ab-sub-secondary',
				),
			) );

			$root = $root . '-groups';

			$admin_bar->add_node( array(
				'id'        => $root . '-title',
				'parent'    => $root,
				'title'     => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-image-filter dashicons-itthinx-groups' ) . __('Groups', 'groups'),
				'href'      => false,
				'meta'      => array(
					'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
					'tabindex' => '0'
				),
			) );

			/**
			 * Add items at the beginning of the groups group
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_groups_before', $admin_bar );

			// Add the groups
			foreach ( $this->get_groups() as $group_key => $group ) {
				$href = '#';
				$class = 'vaa-group-item';
				$title = $group->name;
				// Check if this group is the current view
				if ( $this->get_viewAs('groups') ) {
					if ( $this->get_viewAs('groups') == $group->group_id ) {
						$class .= ' current';
						$href = false;
					}
					elseif ( $current_parent = $this->get_groups( $this->get_viewAs('groups') ) ) {
						if ( $current_parent->parent_id == $group->group_id ) {
							$class .= ' current-parent';
						}
					}
				}
				$parent = $root;
				if ( ! empty( $group->parent_id ) ) {
					$parent = $root .'-group-' . $group->parent_id;
				}
				$admin_bar->add_node( array(
					'id'        => $root . '-group-' . $group->group_id,
					'parent'    => $parent,
					'title'     => $title,
					'href'      => $href,
					'meta'      => array(
						'title'     => esc_attr__('View as', 'view-admin-as') . ' ' . $group->name,
						'class'     => $class,
						'rel'       => $group->group_id,
					),
				) );
			}

			/**
			 * Add items at the end of the groups group
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_groups_after', $admin_bar );
		}
	}

	/**
	 * Store the available groups
	 * @since   1.7
	 * @access  private
	 */
	private function store_groups() {
		$groups = Groups_Group::get_groups();

		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$this->groups[ $group->group_id ] = $group;
			}
		}
	}

	/**
	 * Get a group by ID
	 *
	 * @since   1.7
	 * @access  public
	 * @param   string  $key
	 * @return  mixed
	 */
	public function get_groups( $key = '' ) {
		if ( empty( $key ) ) {
			$key = false;
		}
		return VAA_API::get_array_data( $this->groups, $key );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.7
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Groups
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
