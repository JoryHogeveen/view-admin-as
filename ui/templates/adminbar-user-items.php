<?php
/**
 * Add user items.
 *
 * @since    1.7
 * @version  1.7.4
 *
 * @var  \WP_Admin_Bar  $admin_bar  The toolbar object.
 * @var  string         $root       The current root item.
 * @var  string         $main_root  The main VAA root item.
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar && isset( $root ) ) {

	if ( ! isset( $main_root ) ) {
		$main_root = $root;
	}

	foreach ( $this->store->get_users() as $user ) {
		$parent = $root;
		$href   = VAA_API::get_vaa_action_link( array( 'user' => $user->ID ), $this->store->get_nonce( true ) );
		$class  = 'vaa-user-item';
		$title  = VAA_View_Admin_As_Form::do_view_title( $user->display_name, 'user', $user->ID );
		// Check if this user is the current view.
		if ( VAA_API::is_current_view( $user->ID, 'user' ) ) {
			$class .= ' current';
			if ( 1 === count( $this->store->get_view() ) ) {
				$href = false;
			}
		}
		$user_node = array(
			'id'     => $root . '-user-' . $user->ID,
			'parent' => $parent,
			'title'  => $title,
			'href'   => $href,
			'meta'   => array(
				// Translators: %s stands for the user display name.
				'title' => sprintf( __( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $user->display_name ),
				'class' => $class,
				'rel'   => $user->ID,
			),
		);
		if ( true === $this->groupUserRoles ) {
			// Users grouped under roles.
			foreach ( $user->roles as $role ) {
				$user_role_node = $user_node;
				$parent = $main_root . '-roles-role-' . $role;
				$group  = $parent . '-users';
				if ( ! $admin_bar->get_node( $group ) ) {
					$admin_bar->add_group( array(
						'id' => $group,
						'parent' => $parent,
						'meta'   => array(
							'class' => 'vaa-auto-max-height',
						),
					) );
				}
				$user_role_node['id'] .= '-' . $role;
				$user_role_node['parent'] = $group;
				$admin_bar->add_node( $user_role_node );
			}
		} else {
			// Users displayed as normal.
			$user_roles = array();
			// Add the roles of this user in the name.
			foreach ( $user->roles as $role ) {
				$user_roles[] = $this->store->get_rolenames( $role );
			}
			$user_node['title'] = $title . ' &nbsp; <span class="user-role ab-italic">(' . implode( ', ', $user_roles ) . ')</span>';
			$admin_bar->add_node( $user_node );
		}
	} // End foreach().

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
