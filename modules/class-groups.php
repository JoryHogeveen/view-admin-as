<?php
/**
 * View Admin As - Groups plugin
 *
 * Compatibility class for the Groups plugin
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.7
 * @version 1.7
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

final class VAA_View_Admin_As_Groups extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since   1.7
	 * @var     VAA_View_Admin_As_Compat
	 */
	private static $_instance = null;

	/**
	 *
	 * @since   1.7
	 * @var     array
	 */
	private $groups;

	/**
	 *
	 * @since   1.7
	 * @var     Groups_Group
	 */
	private $selectedGroup;

	/**
	 * Populate the instance
	 * @since   1.7
	 * @access  protected
	 */
	protected function __construct() {
		self::$_instance = $this;

		add_action( 'vaa_view_admin_as_init', array( $this, 'init' ) );
	}

	/**
	 * Initialize the Groups module
	 * @since   1.7
	 * @access  public
	 * @param   VAA_View_Admin_As  $vaa
	 */
	public function init( $vaa ) {
		parent::__construct( $vaa );

			// @todo dev
		$this->store->set_viewAs( array( 'group' => 2 ) );

		if ( is_callable( array( 'Groups_Group', 'get_groups' ) ) ) {

			$this->store_groups();

			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), 40, 2 );

			if ( $this->get_viewAs('group') && $this->get_groups( $this->get_viewAs('group') ) ) {

				$this->selectedGroup = new Groups_Group( $this->get_viewAs('group') );
				add_filter( 'vaa_admin_bar_viewing_as_title', array( $this, 'vaa_viewing_as_title' ) );
				//add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
				add_filter( 'groups_user_can', array( $this, 'groups_user_can' ), 20, 3 );
				add_filter( 'groups_user_is_member', array( $this, 'groups_user_is_member' ), 20, 3 );

				/**
				 * Filters
				 *
				 * - groups_post_access_user_can_read_post
				 *     class-groups-post-access -> line 419
				 */
			}
		}
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
			$object = $result[1];
			$result = $result[0];
		}

		if ( is_callable( array( $this->selectedGroup, 'can' ) ) && ! $this->selectedGroup->can( $cap ) ) {
			$result = false;
		}

		return $result;
	}

	public function groups_user_is_member( $result, $user_id, $group_id ) {
		if ( $group_id == $this->selectedGroup->group->group_id ) {
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
		if ( $this->get_viewAs('group') && $this->get_groups( $this->get_viewAs('group') ) ) {
			$title = __( 'Viewing as group', 'view-admin-as' ) . ': ' . $this->get_groups( $this->get_viewAs('group') )->name;
		}
		return $title;
	}

	/**
	 * Add the Groups admin bar items
	 * @since   1.7
	 * @access  public
	 * @param   WP_Admin_Bar  $admin_bar
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
				'title'     => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-image-filter' ) . __('Groups', 'groups'),
				'href'      => false,
				'meta'      => array(
					'class'     => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active'
				),
			) );

			/**
			 * Add items at the beginning of the groups group
			 * @see     'admin_bar_menu' action
			 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
			 */
			do_action( 'vaa_admin_bar_groups_before', $admin_bar );

			// Add the groups
			foreach( $this->groups as $group_key => $group ) {
				$href = '#';
				$class = 'vaa-group-item';
				$title = $group->name;
				// Check if this group is the current view
				if ( $this->get_viewAs('group') ) {
					if ( $this->get_viewAs('group') == $group->group_id ) {
						$class .= ' current';
						$href = false;
					}
					elseif ( $current_parent = $this->get_groups( $this->get_viewAs('group') ) ) {
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
			foreach( $groups as $group ) {
				$this->groups[ $group->group_id ] = $group;
			}
		}
	}

	/**
	 * Get a group by ID
	 *
	 * @since  1.7
	 * @access  public
	 * @param  string  $key
	 * @return mixed
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
	 * @param   object|bool  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Compat|bool
	 */
	public static function get_instance( $caller = false ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' == get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		return false;
	}

} // end class