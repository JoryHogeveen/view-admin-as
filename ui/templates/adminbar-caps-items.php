<?php
/**
 * Add caps items.
 *
 * @since    1.7.0
 * @version  1.8.0
 *
 * @var  \VAA_View_Admin_As_Caps  $this
 * @var  \WP_Admin_Bar            $admin_bar  The toolbar object.
 * @var  string                   $root       The current root item.
 * @var  string                   $main_root  The main VAA root item.
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

	$caps_items = '';
	foreach ( $this->store->get_caps() as $cap => $granted ) {
		$class   = 'vaa-cap-item';
		$checked = (bool) $granted;
		// check if we've selected a capability view and we've changed some capabilities.
		$selected_caps = $this->store->get_view( $this->type );
		if ( isset( $selected_caps[ $cap ] ) ) {
			$checked = (bool) $selected_caps[ $cap ];
		}
		// Check for this capability in any view set.
		if ( $this->vaa->view()->current_view_can( $cap ) ) {
			$class .= ' current';
		}
		// The list of capabilities.
		$caps_items .=
			'<div class="ab-item ' . $class . '">'
			. VAA_View_Admin_As_Form::do_checkbox(
				array(
					'name'           => 'vaa_cap_' . esc_attr( $cap ),
					'value'          => $checked,
					'compare'        => true,
					'checkbox_value' => esc_attr( $cap ),
					'label'          => $cap,
				)
			)
			. '</div>';
	}
	$admin_bar->add_node(
		array(
			'id'     => $root . '-select-options',
			'parent' => $parent,
			'title'  => $caps_items,
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-multipleselect vaa-auto-max-height',
			),
		)
	);

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
