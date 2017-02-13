<?php
/**
 * Add user items.
 *
 * @since  1.6.x
 *
 * @var  WP_Admin_Bar  $admin_bar  The toolbar object.
 * @var  string        $root       The current root item.
 * @var  string        $main_root  The main VAA root item.
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

if ( isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar && isset( $root ) ) {

	if ( ! isset( $main_root ) ) {
		$main_root = $root;
	}

	foreach ( $this->store->get_users() as $user ) {
		$parent = $root;
		$href   = '#';
		$class  = 'vaa-user-item';
		$title  = $user->display_name;
		// Check if this user is the current view.
		if ( $this->store->get_view( 'user' ) && (int) $this->store->get_view( 'user' ) === (int) $user->ID ) {
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
				'title' => sprintf( esc_attr__( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $user->display_name ),
				'class' => $class,
				'rel'   => $user->ID,
			),
		);
		if ( true === $this->groupUserRoles ) {
			// Users grouped under roles.
			foreach ( $user->roles as $role ) {
				$user_node['id'] .= '-' . $role;
				$user_node['parent'] = $main_root . '-roles-role-' . $role;
				$admin_bar->add_node( $user_node );
			}
		} else {
			// Users displayed as normal.
			$user_roles = array();
			// Add the roles of this user in the name.
			foreach ( $user->roles as $role ) {
				$user_roles[] = $this->store->get_rolenames( $role );
			}
			$user_node['title'] = $title . ' &nbsp; <span class="user-role">(' . implode( ', ', $user_roles ) . ')</span>';
			$admin_bar->add_node( $user_node );
		}
	}

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.6.x' );
}
