<?php

// -----------------------------
// === AdminSanity Admin Bar ===
// -----------------------------

// Adds a Switcher Icon to the Admin Bar between Default and Extra Menu Items

// Admin Bar Load Process Note
// _wp_admin_bar_init ->
// - WP_Admin_Bar->initialize 
// -> action: admin_bar_init
// - WP_Admin_Bar->add_menus
// - adds defaults to admin_bar_menu action
// -> action: add_admin_bar_menus
// wp_admin_bar_render ->
// - action: wp_before_admin_bar_render
// - WP_Admin_Bar->render();
// -- render -> _render -> _render_group(s) -> _render_item(s)
// - action: wp_after_admin_bar_render

// -------------------------
// Capture Default Admin Bar
// -------------------------
add_action( 'admin_bar_init', 'adminsanity_default_admin_bar' );
function adminsanity_default_admin_bar() {

	global $adminsanity, $wp_admin_bar;
	$original_bar = $wp_admin_bar;

	// --- prevent endless loop ---
	remove_action( 'admin_bar_init', 'adminsanity_default_admin_bar' );

	// --- initialize new admin bar class ---
	$admin_bar_class = apply_filters( 'wp_admin_bar_class', 'WP_Admin_Bar' );
	if ( !class_exists( $admin_bar_class ) ) {return false;}
	$wp_admin_bar = new $admin_bar_class;
	$wp_admin_bar->initialize();

	// --- add admin menus (add_menus) ---
	// ...but with different action hook!

	// User related, aligned right.
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_my_account_menu', 0 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_search_menu', 4 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_my_account_item', 7 );

	// Site related.
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_sidebar_toggle', 0 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_wp_menu', 10 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_my_sites_menu', 20 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_site_menu', 30 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_customize_menu', 40 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_updates_menu', 50 );

	// Content related.
	if ( ! is_network_admin() && ! is_user_admin() ) {
		add_action( 'default_admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		add_action( 'default_admin_bar_menu', 'wp_admin_bar_new_content_menu', 70 );
	}
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
	add_action( 'default_admin_bar_menu', 'wp_admin_bar_add_secondary_groups', 200 );
	
	// --- capture defaults by running action ---
	do_action_ref_array( 'default_admin_bar_menu', array( &$wp_admin_bar ) );
	$adminsanity['default_bar'] = $wp_admin_bar;
	
	// --- restore original to render ---
	$wp_admin_bar = $original_bar;
	
}

// -----------------------
// Before Admin Bar Render
// -----------------------
add_action( 'wp_before_admin_bar_render', 'adminsanity_admin_bar', 999999 );
function adminsanity_admin_bar() {

	global $adminsanity, $wp_admin_bar;
	$default_bar = $adminsanity['default_bar'];
	
	// --- test for default versus full ---
	$debug = false;
	if ( isset( $_GET['test'] ) && ( 'admin-bar' == $_GET['test'] ) ) {
		$debug = true;
	}
	
	$default_parents = array();
	$default_nodes = $default_bar->get_nodes();
	foreach ( $default_nodes as $id => $object ) {
		if ( empty( $object->parent ) ) {
			$default_parents[] = $id;
		}
	}

	// --- create new admin bar instance ---
	$admin_bar_class = apply_filters( 'wp_admin_bar_class', 'WP_Admin_Bar' );
	if ( !class_exists( $admin_bar_class ) ) {return false;}
	$new_admin_bar = new $admin_bar_class;
	$new_admin_bar->initialize();
	
	// --- add bar switcher node to admin bar ---
	$args = array(
		'id'		=> 'menu-switcher',
		'title'		=> '<span class="ab-icon"></span>',
		'parent'	=> false,
		'href'		=> '#',
		'group'		=> false,
		'meta'		=> array(
			'class'		=> 'admin-bar-toggle',
			'onclick'	=> 'adminsanity_toggle_admin_bar();',
			'title'		=> __( 'Switch between default and added admin bar menus.'),
		),
	);	
	$new_admin_bar->add_node( $args );	

	// --- add all nodes to new admin bar ---
	$new_parents = array();
	$admin_bar_nodes = $wp_admin_bar->get_nodes();
	foreach ( $admin_bar_nodes as $id => $object ) {
		$args = array(
			'id'		=> $object->id,
			'title'		=> $object->title,
			'parent'	=> $object->parent,
			'href'		=> $object->href,
			'group'		=> $object->group,
			'meta'		=> $object->meta,
		);	
		if ( empty( $object->parent ) && ( 'menu-toggle' != $id ) ) {
			if ( !in_array( $id, $default_parents ) ) {
				$new_parents[] = $id;
				// --- just add a new menu item class! ---
				if ( isset( $args['meta']['class'] ) ) {
					$args['meta']['class'] .= ' admin-bar-extra-menu';
				} else {
					$args['meta']['class'] = 'admin-bar-extra-menu';
				}
			} else {
				if ( isset( $args['meta']['class'] ) ) {
					$args['meta']['class'] .= ' admin-bar-default-menu';
				} else {
					$args['meta']['class'] = 'admin-bar-default-menu';
				}
			}
		}
		$new_admin_bar->add_node( $args );
	}

	if ( $debug ) {
		// echo "Default Bar: " . print_r( $default_bar, true ). "<br>" . PHP_EOL;
		echo "Default Nodes: " . print_r( $default_nodes, true ). "<br>" . PHP_EOL;
		echo "Default Parents: " . print_r( $default_parents, true ). "<br>" . PHP_EOL;
		// echo "Admin Bar: " . print_r( $wp_admin_bar, true ) . "<br>" . PHP_EOL;
		echo "Admin Bar Nodes: " . print_r( $admin_bar_nodes, true ). "<br>" . PHP_EOL;
		echo "New Parents: " . print_r( $new_parents, true ) . "<br>" . PHP_EOL;
		echo "New Admin Bar: " . print_r( $new_admin_bar, true ) . "<br>" . PHP_EOL;
	}

	// --- overwrite with new admin bar ---
	$wp_admin_bar = $new_admin_bar;

}


// ----------------------------
// Admin Bar Switcher Resources
// ----------------------------
add_action( 'wp_after_admin_bar_render', 'adminsanity_admin_bar_resources' );
function adminsanity_admin_bar_resources() {

	echo '<style>#wp-toolbar #wp-admin-bar-menu-switcher a {padding-right: 0;}
	#wp-toolbar #wp-admin-bar-root-default.ab-top-menu li {display: list-item;}
	@media screen and (min-width: 781px) {
		#wp-toolbar #wp-admin-bar-root-default #wp-admin-bar-menu-toggle {display: none;}
	}
	#wp-admin-bar-menu-switcher .ab-icon:before {content: "\f503"; top: 3px;}
	#wp-toolbar #wp-admin-bar-root-default.default-menu-items li.admin-bar-extra-menu,
	#wp-toolbar #wp-admin-bar-root-default.extra-menu-items li.admin-bar-default-menu {display:none;}
	</style>';

	echo "<script>
	function adminsanity_toggle_admin_bar() {
		if (jQuery('#wp-admin-bar-root-default').hasClass('default-menu-items')) {
			jQuery('#wp-admin-bar-root-default').removeClass('default-menu-items').addClass('extra-menu-items');
		} else if (jQuery('#wp-admin-bar-root-default').hasClass('extra-menu-items')) {
			jQuery('#wp-admin-bar-root-default').removeClass('extra-menu-items');
		} else {jQuery('#wp-admin-bar-root-default').addClass('default-menu-items');}
	}</script>";
	
}