<?php
/**
 * Add user items.
 *
 * @since    1.7.0
 * @version  1.8.0
 *
 * @var  \VAA_View_Admin_As_Users  $this
 * @var  \WP_Admin_Bar             $admin_bar  The toolbar object.
 * @var  string                    $root       The current root item.
 * @var  string                    $main_root  The main VAA root item.
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar && isset( $root ) ) {

	if ( ! isset( $main_root ) ) {
		$main_root = $root;
	}
	if ( ! isset( $parent ) ) {
		$parent = $root;
	}

	if ( ! isset( $title_submenu ) ) {
		$title_submenu = false;
	}

	foreach ( $this->store->get_users() as $user ) {
		// Reset parent for each loop due to groupUserRoles.
		$item_parent = $parent;

		$href  = VAA_API::get_vaa_action_link( array( $this->type => $user->ID ) );
		$class = 'vaa-' . $this->type . '-item';
		$title = $this->get_view_title( $user );

		$view_title = VAA_View_Admin_As_Form::do_view_title( $title, $this, $user->ID );

		/**
		 * Add the user roles to the user title?
		 * Only available if users are not grouped under their roles.
		 * @see VAA_View_Admin_As_Users::get_view_title_roles()
		 */
		if ( ! $this->group_user_roles() ) {
			$view_title .= $this->get_view_title_roles( $user );
		}

		// Check if this user is the current view.
		if ( VAA_API::is_current_view( $user->ID, $this->type ) ) {
			$class .= ' current';
			if ( 1 === count( $this->store->get_view() ) ) {
				$href = false;
			}
		}

		$user_node = array(
			'id'     => $root . '-' . $this->type . '-' . $user->ID,
			'parent' => $item_parent,
			'title'  => $view_title,
			'href'   => $href,
			'meta'   => array(
				// Translators: %s stands for the user view title (display name by default).
				'title' => sprintf( __( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $title ),
				'class' => $class,
			),
		);

		if ( $this->group_user_roles() ) {
			// Users grouped under roles.
			foreach ( $user->roles as $role ) {
				$user_role_node = $user_node;
				$item_parent    = $main_root . '-roles-role-' . $role;
				$group          = $item_parent . '-users';
				if ( ! $admin_bar->get_node( $group ) ) {
					$admin_bar->add_group( array(
						'id'     => $group,
						'parent' => $item_parent,
						'meta'   => array(
							'class' => 'vaa-auto-max-height',
						),
					) );
				}
				$user_role_node['id']    .= '-' . $role;
				$user_role_node['parent'] = $group;
				$admin_bar->add_node( $user_role_node );
			}
		} else {
			$admin_bar->add_node( $user_node );
		}

	} // End foreach().

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
