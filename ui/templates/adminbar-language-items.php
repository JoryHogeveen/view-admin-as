<?php
/**
 * Add role items.
 *
 * @since    1.8.0
 * @version  1.8.2
 *
 * @var  \VAA_View_Admin_As_Languages  $this
 * @var  \WP_Admin_Bar                 $admin_bar  The toolbar object.
 * @var  string                        $root       The current root item.
 * @var  string                        $main_root  The main VAA root item.
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar && isset( $root ) ) {

	if ( ! isset( $main_root ) ) {
		$main_root = $root;
	}

	$parent = $root . '-languages';

	foreach ( $this->store->get_languages() as $locale => $language ) {
		$href  = VAA_API::get_vaa_action_link( array( $this->type => $locale ) );
		$class = 'vaa-' . $this->type . '-item';
		$title = $this->get_view_title( $locale );

		$view_title = ( $locale !== $title ) ? $language . ' &nbsp;<code>' . $locale . '</code>' : $locale;

		$view_title = VAA_View_Admin_As_Form::do_view_title( $view_title, $this, $locale );

		// Check if this role is the current view.
		if ( VAA_API::is_current_view( $locale, $this->type ) ) {
			$class .= ' current';
			if ( 1 === count( $this->store->get_view() ) ) {
				$href = false;
			}
		}

		$admin_bar->add_node(
			array(
				'id'     => $root . '-' . $this->type . '-' . $locale,
				'parent' => $parent,
				'title'  => $view_title,
				'href'   => $href,
				'meta'   => array(
					// Translators: %s stands for the translated role name.
					'title' => sprintf( __( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $title ),
					'class' => $class,
				),
			)
		);

	} // End foreach().

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
