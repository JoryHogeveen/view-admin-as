<?php
/**
 * Add role items.
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

	foreach ( $this->store->get_roles() as $role_key => $role ) {
		$parent = $root;
		$href   = '#';
		$class  = 'vaa-role-item';
		$title  = $this->store->get_rolenames( $role_key );
		// Check if the users need to be grouped under their roles.
		if ( true === $this->groupUserRoles ) {
			// make sure items are aligned properly when some roles don't have users.
			$class .= ' vaa-menupop';
			// Check if the current view is a user with this role.
			if ( $this->store->get_view( 'user' )
			     && in_array( $role_key, $this->store->get_selectedUser()->roles, true )
			) {
				$class .= ' current-parent';
			}
			// If there are users with this role, add a counter.
			$user_count = 0;
			foreach ( $this->store->get_users() as $user ) {
				if ( in_array( $role_key, $user->roles, true ) ) {
					$user_count ++;
				}
			}
			if ( 0 < $user_count ) {
				$title = $title . ' <span class="user-count">(' . $user_count . ')</span>';
			}
		}
		// Check if this role is the current view.
		if ( $this->store->get_view( 'role' ) === $role_key ) {
			$class .= ' current';
			if ( 1 === count( $this->store->get_view() ) ) {
				$href = false;
			}
		}
		$admin_bar->add_node(
			array(
				'id' => $root . '-role-' . $role_key,
				'parent' => $parent,
				'title' => $title,
				'href' => $href,
				'meta' => array(
					// Translators: %s stands for the translated role name.
					'title' => sprintf( esc_attr__( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $this->store->get_rolenames( $role_key )
					),
					'class' => $class,
					'rel'   => $role_key,
				),
			)
		);
	}

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.6.x' );
}
