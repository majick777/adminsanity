<?php

// -----------------------------
// === AdminSanity Admin Bar ===
// -----------------------------

// - Adds a Cycler Icon to the Admin Bar between Default and Extra Menu Items.
// - Adds a Dropdown Icon to the Admin Bar to display all Menu and Submenu Items.

// === AdminSanity Bar ===
// - Capture Default Admin Bar
// - Replace Admin Bar
// - Admin Bar Styles
// - Admin Bar Scripts

// ----------------------
// Module Option Settings
// ----------------------

// Features
// --------
// All Features are boolean true/false and default to true.
// Feature             | Constant                 | Filter
// Frontend Loading    | ADMINSANITY_BAR_FRONTEND | adminsanity_bar_frontend
// Bar Item Cycler     | ADMINSANITY_BAR_CYCLER   | adminsanity_bar_cycler
// Bar Dropdown Toggle | ADMINSANITY_BAR_DROPDOWN | adminsanity_bar_dropdown

// Filters
// -------
// Filter Setting | Filter                  | Type   | Default
// Module Styles  | adminsanity_bar_styles  | string | plugin css
// Module Scripts | adminsanity_bar_scripts | string | plugin js

// -----------------
// Development TODOs
// -----------------
// - debug current_screen->base property not exists errors (on old WP versions) ?
// ? check icon positions on admin bar for RTL languages ?

// --------------------------------------
// WordPress Admin Bar Load Process Notes
// --------------------------------------
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

// --- abort on negative load constant ---
// 0.9.9: standardize loader constant names
if ( defined( 'ADMINSANITY_LOAD_BAR' ) && !ADMINSANITY_MODULE_LOAD_BAR ) {
	return;
}

// 1.0.3: bug out if on WordPress.Com (custom/no admin bar)
if ( defined( 'WPCOMSH_VERSION' ) ) {
	return;
}

// --- get frontend override setting ---
// 0.9.9: added frontend override setting
if ( !is_admin() ) {
	$frontend = true;
	// 1.0.1: fix to frontend constant (remove CYCLER_)
	if ( defined( 'ADMINSANITY_BAR_FRONTEND' ) ) {
		$frontend = (bool) ADMINSANITY_BAR_FRONTEND;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$frontend = (bool) adminsanity_get_setting( 'bar_frontend' );
	} else {
		$frontend = (bool) apply_filters( 'adminsanity_bar_frontend', $frontend );
	}
	if ( !$frontend ) {
		return;
	}
}

// --- conflict check ---
// 1.0.1: add check for WooCommerce product attributes page
if ( isset( $_REQUEST['page'] ) && ( 'product_attributes' == $_REQUEST['page'] ) ) {
	return;
}

// --- allow for use as an mu-plugin ---
// 0.9.9: attempt to prevent double load conflicts
// 1.0.1: use return instead of function wrapper
if ( !function_exists( 'adminsanity_bar_default' ) ) {

// note: unlike menu/notices, loader is not needed for scripts/styles
// as they are already hooked to admin bar rendering actions


// -------------------------
// Capture Default Admin Bar
// -------------------------
add_action( 'admin_bar_init', 'adminsanity_bar_default' );
function adminsanity_bar_default() {

	global $adminsanity, $wp_admin_bar;
	$original_bar = $wp_admin_bar;

	$adminsanity['bar-debug'] = false;
	// 1.0.0: explicitly validate GET value
	if ( isset( $_GET['as-debug'] ) && in_array( $_GET['as-debug'], array( 'bar', 'all' ) ) ) {
		$adminsanity['bar-debug'] = true;
	}

	// --- prevent endless loop ---
	remove_action( 'admin_bar_init', 'adminsanity_bar_default' );

	// --- initialize new admin bar class ---
	$admin_bar_class = apply_filters( 'wp_admin_bar_class', 'WP_Admin_Bar' );
	if ( !class_exists( $admin_bar_class ) ) {
		return false;
	}
	$wp_admin_bar = new $admin_bar_class;
	$wp_admin_bar->initialize();

	// --- add admin menus (add_menus) ---
	// via /wp-includes/class-wp-admin-bar.php function add_menus
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


	// --- current screen fix ---
	add_action( 'default_admin_bar_menu', 'adminsanity_current_screen_fix', 79 );

	// --- capture defaults by running action ---
	do_action_ref_array( 'default_admin_bar_menu', array( &$wp_admin_bar ) );
	$adminsanity['default_bar'] = $wp_admin_bar;

	// --- restore original to render ---
	$wp_admin_bar = $original_bar;

}


// ------------------
// Current Screen Fix
// ------------------
// since admin_bar_init is hooked to admin_init, current screen object is not yet loaded
// which gives current_screen->base property not exists errors in wp_admin_bar_edit_menu (/wp-includes/admin-bar.php)
// ...so this duplicates admin.php current screen initialization to fix that (yeesh)
function adminsanity_current_screen_fix() {

	global $adminsanity;

	// --- fix not needed on frontend ---
	if ( !is_admin() ) {
		return;
	}

	// --- check current screen ---
	$current_screen = get_current_screen();
	if ( $adminsanity['bar-debug'] ) {
		echo '<span class="current-screen-test" style="display:none;">Current Screen A:' . esc_html( print_r( $current_screen, true ) ) . '</span>' . "\n";
	}
	if ( !is_object( $current_screen ) || !property_exists( $current_screen, 'base' ) ) {
		
		global $pagenow, $page_hook, $typenow;
		// 1.0.3: fix to set global for plugin_page and hook_suffix ?
		// global $plugin_page, $hook_suffix;
		if ( isset( $_GET['page'] ) ) {
			// 1.0.0: use sanitize_title on GET value
			$plugin_page = sanitize_title( wp_unslash( $_GET['page'] ) );
			$plugin_page = plugin_basename( $plugin_page );
		}
		if ( isset( $_REQUEST['post_type'] ) ) {
			// 1.0.0: use sanitize_title on REQUEST value
			// 1.0.2: fix to gone-missing $_REQUEST prefix
			$post_type = sanitize_title( $_REQUEST['post_type'] );
			if ( post_type_exists( $post_type ) ) {
				$typenow = $post_type;
			} else {
				$typenow = '';
			}
		} else {
			$typenow = '';
		}

		if ( isset( $plugin_page ) ) {
			if ( !empty( $typenow ) ) {
				$the_parent = $pagenow . '?post_type=' . $typenow;
			} else {
				$the_parent = $pagenow;
			}
			if ( !$page_hook = get_plugin_page_hook( $plugin_page, $the_parent ) ) {
				$page_hook = get_plugin_page_hook( $plugin_page, $plugin_page );
			}
			unset( $the_parent );
		}

		$hook_suffix = '';
		if ( isset( $page_hook ) ) {
			$hook_suffix = $page_hook;
		} elseif ( isset( $plugin_page ) ) {
			$hook_suffix = $plugin_page;
		} elseif ( isset( $pagenow ) ) {
			$hook_suffix = $pagenow;
		}

		if ( $adminsanity['bar-debug'] ) {
			echo '<span style="display:none;">Hook Suffix: ' . $hook_suffix . '</span>' . "\n";
			echo '<span style="display:none;">Pagenow: ' . $pagenow . '</span>' . "\n";
			echo '<span style="display:none;">Plugin Page: ' . $plugin_page . '</span>' . "\n";
			echo '<span style="display:none;">Page Hook: ' . $page_hook . '</span>' . "\n";
		}
	
		// --- set current screen ---
		// we cannot use function set_current_screen directly as this calls set_current_screen action
		// this causes conflicts on classes loaded via set_current_screen action
		// eg. WooCommerce Screen class, but possibly with other plugins hooking to that action
		// set_current_screen( $pagenow );
		// so instead we duplicate function WP_Screen->set_current_screen but without firing the action
		global $current_screen; // $taxnow, $typenow;
		$current_screen = WP_Screen::get( $hook_suffix );
		// 1.0.3: disabled these as causing some template editor problems
		// $taxnow = $current_screen->taxonomy;
		// $typenow = $current_screen->post_type;

		if ( $adminsanity['bar-debug'] ) {
			echo '<span style="display:none;">Taxonomy: ' . $current_screen->taxonomy . '</span>' . "\n";
			echo '<span style="display:none;">Post Type: ' . $current_screen->post_type . '</span>' . "\n";
		}

		// 1.0.2: fix for undefined post->post_type, also in wp_admin_bar_edit_menu
		// (base is now set, but post object may not yet be set on post edit pages)
		if ( 'post' == $current_screen->base ) {
			global $post;
			$post = get_post();
			if ( !is_object( $post ) && isset( $_REQUEST['post'] ) ) {
				$post = get_post( absint( $_REQUEST['post'] ) );
			}
		}
			
	}

	if ( $adminsanity['bar-debug'] ) {
		// $current_screen = get_current_screen();
		echo '<span class="current-screen-test" style="display:none;">Current Screen B: ' . esc_html( print_r( $current_screen, true ) ) .  '</span>' . "\n";
	}
}

// -----------------
// Replace Admin Bar
// -----------------
add_action( 'wp_before_admin_bar_render', 'adminsanity_bar_replace', 999999 );
function adminsanity_bar_replace() {

	global $adminsanity, $wp_admin_bar;
	$default_bar = $adminsanity['default_bar'];

	// --- test for default versus full ---
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

	// --- get bar cycler setting ---
	// 0.9.9: check bar cycler settings
	$cycler = true;
	if ( defined( 'ADMINSANITY_BAR_CYCLER' ) ) {
		$cycler = (bool) ADMINSANITY_BAR_CYCLER;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$cycler = (bool) adminsanity_get_setting( 'bar_cycler' );
	} else {
		$cycler = (bool) apply_filters( 'adminsanity_bar_cycler', $cycler );
	}

	// --- add bar cycler node to admin bar ---
	if ( $cycler ) {
		$args = array(
			'id'		=> 'menu-cycler',
			'title'		=> '<span class="ab-icon"></span>',
			'parent'	=> false,
			'href'		=> '#',
			'group'		=> false,
			'meta'		=> array(
				'class'		=> 'admin-bar-cycler',
				'onclick'	=> 'adminsanity_bar_cycle();',
				'title'		=> __( 'Cycle between all, default and extra admin bar menu items.', 'adminsanity' ),
			),
		);
		$new_admin_bar->add_node( $args );
	}

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

	// --- get bar dropdown setting ---
	// 0.9.9: check bar dropdown setting
	$dropdown = true;
	if ( defined( 'ADMINSANITY_BAR_DROPDOWN' ) ) {
		$dropdown = (bool) ADMINSANITY_BAR_DROPDOWN;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$dropdown = (bool) adminsanity_get_setting( 'bar_dropdown' );
	} else {
		$dropdown = (bool) apply_filters( 'adminsanity_bar_dropdown', $dropdown );
	}

	if ( $dropdown ) {

		// --- add bar dropdown node to secondary menu ---
		// 0.9.9: add dropdown toggle menu item
		$args = array(
			'id'		=> 'menu-dropdown',
			'title'		=> '<span class="ab-icon"></span>',
			'parent'	=> 'top-secondary',
			'href'		=> '#',
			'group'		=> false,
			'meta'		=> array(
				'class'		=> 'admin-bar-dropdown',
				'onclick'	=> 'adminsanity_bar_dropdown();',
				'title'		=> __( 'Expand display of admin bar menu items.', 'adminsanity' ),
			),
		);
		$new_admin_bar->add_node( $args );

		// --- add retract toggle to secondary menu ---
		// 0.9.9: added retract toggle menu item
		$args = array(
			'id'		=> 'menu-retract',
			'title'		=> '<span class="ab-icon"></span>',
			'parent'	=> 'top-secondary',
			'href'		=> '#',
			'group'		=> false,
			'meta'		=> array(
				'class'		=> 'admin-bar-dropdown',
				'onclick'	=> 'adminsanity_bar_dropdown();',
				'title'		=> __( 'Collapse display of admin bar menu items.', 'adminsanity' ),
			),
		);
		$new_admin_bar->add_node( $args );
	}

	// --- debug output ---
	if ( $adminsanity['bar-debug'] ) {
		echo "<span id='bar-debug' style='display:none;'>";
		// echo "Default Bar: " . esc_html( print_r( $default_bar, true ) ) . "<br>" . "\n";
		echo "Default Nodes: " . esc_html( print_r( $default_nodes, true ) ) . "<br>" . "\n";
		echo "Default Parents: " . esc_html( print_r( $default_parents, true ) ) . "<br>" . "\n";
		// echo "Admin Bar Object: " . esc_html( print_r( $wp_admin_bar, true ) ) . "<br>" . "\n";
		echo "Admin Bar Nodes: " . esc_html( print_r( $admin_bar_nodes, true ) ) . "<br>" . "\n";
		echo "New Parents: " . esc_html( print_r( $new_parents, true ) ) . "<br>" . "\n";
		echo "New Admin Bar: " . esc_html( print_r( $new_admin_bar, true ) ) . "<br>" . "\n";
		echo "</span>";
	}

	// --- overwrite with new admin bar ---
	$wp_admin_bar = $new_admin_bar;

}

// ----------------
// Admin Bar Styles
// ----------------
// 0.9.9: load styles before bar render instead of after
add_action( 'wp_before_admin_bar_render', 'adminsanity_bar_styles' );
function adminsanity_bar_styles() {

	$css = '';

	// --- get bar cycler setting -
	// 0.9.9: check bar cycler settings
	$cycler = true;
	if ( defined( 'ADMINSANITY_BAR_CYCLER' ) ) {
		$cycler = (bool) ADMINSANITY_BAR_CYCLER;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$cycler = (bool) adminsanity_get_setting( 'bar_cycler' );
	} else {
		$cycler = (bool) apply_filters( 'adminsanity_bar_cycler', $cycler );
	}

	// --- get bar dropdown setting ---
	// 0.9.9: check bar dropdown settings
	$dropdown = true;
	if ( defined( 'ADMINSANITY_BAR_DROPDOWN' ) ) {
		$dropdown = (bool) ADMINSANITY_BAR_DROPDOWN;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$dropdown = (bool) adminsanity_get_setting( 'bar_dropdown' );
	} else {
		$dropdown = (bool) apply_filters( 'adminsanity_bar_dropdown', $dropdown );
	}

	if ( !$cycler && !$dropdown ) {
		return;
	}

	if ( $cycler ) {

		// --- cycle toggle icon ---
		$css .= '#wp-toolbar #wp-admin-bar-menu-cycler a {padding-right: 0;}
		#wp-toolbar #wp-admin-bar-root-default.ab-top-menu li {display: list-item;}
		@media screen and (min-width: 781px) {
			#wp-toolbar #wp-admin-bar-root-default #wp-admin-bar-menu-toggle {display: none;}
		}
		#wp-admin-bar-menu-cycler .ab-icon:before {content: "\f503"; top: 3px;}
		#wpadminbar #wp-toolbar #wp-admin-bar-root-default.default-menu-items li.admin-bar-extra-menu,
		#wpadminbar #wp-toolbar #wp-admin-bar-root-default.extra-menu-items li.admin-bar-default-menu {display:none;}
		#wpadminbar #wp-toolbar #wp-admin-bar-root-default #wp-admin-bar-menu-cycler {display: block;}
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-root-default #wp-admin-bar-menu-cycler {display: inline-block;}' . "\n";

	}

	if ( $dropdown ) {

		// --- dropdown icon ---
		// 0.9.9: add dropdown icon
		// TODO: check possible need for further admin menu z-index rules ?
		$css .= '#wpadminbar #wp-toolbar #wp-admin-bar-my-account {float: left;}
		#wpadminbar #wp-admin-bar-menu-dropdown {float: right; display: block;}
		#wpadminbar #wp-admin-bar-menu-dropdown a {margin-right: 0px; padding-right: 0px;}
		#wpadminbar #wp-admin-bar-menu-dropdown .ab-icon:before {content: "\f347"; top: 3px;}
		#wpadminbar #wp-toolbar #wp-admin-bar-menu-retract {display: none;}
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-menu-dropdown {display: none;}
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-menu-retract {float: right; display: block;}
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-menu-retract a {margin-right: 0px; padding-right: 0px;}
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-menu-retract .ab-icon:before {content: "\f343"; top: 3px;}
		#wpadminbar.dropdown #wp-admin-bar-top-secondary {z-index: 99999;}
		#wpadminbar.dropdown ul, #wpadminbar.dropdown ul li {z-index: 99998;}' . "\n";

		// --- dropdown bar ---
		// 0.9.9: add dropdown styles
		$css .= '#wpadminbar.dropdown {height: auto; position: absolute; margin-top: -32px; display: block;
			overflow: auto; background: transparent; margin-bottom: 20px; padding-bottom: 40px;}
		#wpadminbar #wp-admin-bar-root-default {float: left;}
		#wpadminbar.dropdown #wp-admin-bar-root-default {float: none;}
		.wp-admin #wpadminbar.dropdown {position: relative;}
		#wpadminbar.dropdown ul.ab-top-menu {height: auto;}
		#wpadminbar.dropdown ul.ab-top-menu.ab-secondary-menu {float: right; overflow: hidden;}
		#wpadminbar.dropdown #wp-toolbar ul li.admin-bar-default-menu,
		#wpadminbar.dropdown #wp-toolbar ul li.admin-bar-extra-menu,
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-menu-cycler,
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-menu-retract {
			float: none; display: inline-block; vertical-align: top; border-top: 1px solid #AAA; border-right: 1px solid #AAA;}
		#wpadminbar.dropdown #wp-toolbar ul li.admin-bar-default-menu.menupop a,
		#wpadminbar.dropdown #wp-toolbar ul li.admin-bar-extra-menu.menupop a {border-bottom: 1px solid #777;}
		#wpadminbar.dropdown #wp-toolbar ul li.admin-bar-default-menu.menupop li a,
		#wpadminbar.dropdown #wp-toolbar ul li.admin-bar-extra-menu.menupop li a {border-bottom: 0;}
		#wpadminbar.dropdown ul li.admin-bar-default-menu .ab-sub-wrapper,
		#wpadminbar.dropdown ul li.admin-bar-extra-menu .ab-sub-wrapper,
		#wpadminbar.dropdown #wp-admin-bar-my-account .ab-sub-wrapper {display: block;}
		#wpadminbar.dropdown ul li.admin-bar-default-menu ul li .ab-sub-wrapper,
		#wpadminbar.dropdown ul li.admin-bar-extra-menu ul li .ab-sub-wrapper {display: none;}
		#wpadminbar.dropdown ul li ul.ab-submenu li {float: none;}
		#wpadminbar.dropdown ul li ul li a .wp-admin-bar-arrow,
		#wpadminbar ul li ul li .dropdown-arrow {display: none;}
		#wpadminbar.dropdown ul li ul li .dropdown-arrow {display: block; font: normal 24px/1 dashicons; float: right;}
		#wpadminbar.dropdown ul li ul li .ab-sub-wrapper.dropped {display: block; margin-left: 0; margin-top: 0; width: 100%;}
		#wpadminbar.dropdown ul li ul li .ab-sub-wrapper.dropped ul li a {text-indent: 10px;}
		@media screen and (max-width: 782px) {
			#wpadminbar.dropdown ul li ul li .dropdown-arrow {font-size: 36px;}
		}
		@media screen and (max-width: 600px) {
			#wpadminbar.dropdown .ab-top-menu>.menupop>.ab-sub-wrapper {width: auto; left: inherit;}
		}
		' . "\n";
		// TODO: fix admin bar underside padding on mobile widths
	}

	// --- filter and output ---
	$css = apply_filters( 'adminsanity_bar_styles', $css );
	echo "<style>" . $css . "</style>";
}

// -----------------
// Admin Bar Scripts
// -----------------
// 0.9.9: emqueue scripts with wp_after_admin_bar_render hook
add_action( 'wp_after_admin_bar_render', 'adminsanity_bar_enqueue_scripts' );
function adminsanity_bar_enqueue_scripts() {
	add_action( 'admin_footer', 'adminsanity_bar_scripts' );
	add_action( 'wp_footer', 'adminsanity_bar_scripts' );
}
function adminsanity_bar_scripts() {

	$js = '';

	// --- get bar cycler setting ---
	// 0.9.9: check bar cycler setting
	$cycler = true;
	if ( defined( 'ADMINSANITY_BAR_CYCLER' ) ) {
		$cycler = (bool) ADMINSANITY_BAR_CYCLER;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$cycler = (bool) adminsanity_get_setting( 'bar_cycler' );
	} else {
		$cycler = (bool) apply_filters( 'adminsanity_bar_cycler', $cycler );
	}

	// --- get dropdown toggle setting ---
	// 0.9.9: check bar dropdown settings
	$dropdown = true;
	if ( defined( 'ADMINSANITY_BAR_DROPDOWN' ) ) {
		$dropdown = (bool) ADMINSANITY_BAR_DROPDOWN;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$dropdown = (bool) adminsanity_get_setting( 'bar_dropdown' );
	} else {
		$dropdown = (bool) apply_filters( 'adminsanity_bar_dropdown', $dropdown );
	}

	// 0.9.9: set bar script debug mode
	/* $valid = false;
	if ( isset( $_GET['as-debug'] ) ) {
		$debug = sanitize_title( $_GET['as-debug'] );
		if ( in_array( $debug, array( 'all', 'bar' ) ) ) {
			$js .= "as_bar_debug = true; ";
			$valid = true;
		}
	}
	if ( !$valid ) {
		$js .= "as_bar_debug = false; ";
	} */
	
	// --- document ready load functions ---
	// 0.9.9: secondary menu and dropdown background fix
	// 0.9.9: added responsive dropdown resizing
	// $js .= "var as_bar_arrows = false; ";
	$js .= "var as_bar_background = jQuery('#wpadminbar').css('background');" . "\n";
	$js .= "jQuery(document).ready(function() {
		jQuery('#wp-admin-bar-root-default, #wp-admin-bar-top-secondary').css('background',as_bar_background);
		adminsanity_bar_items(); adminsanity_bar_height();
		setTimeout(function() {adminsanity_bar_height();}, 5000);
		jQuery(window).resize(function () {";
		if ( $dropdown ) {
			$js .= "adminsanity_bar_responsive(); ";
		}
		$js .= "adminsanity_bar_height(); });
	});";

	// --- check bar height function ---
	// 0.9.9: add fix for overflowing admin bar
	$js .= "function adminsanity_bar_height() {
		jQuery('#wpadminbar #wp-toolbar').children().each(function() {
			jQuery(this).css('height', jQuery(this).find('li').first().height());
		});
		if (!jQuery('#wpadminbar').hasClass('dropdown')) {
			jQuery('#wpadminbar').css({'height':'','margin-top':'','background-color':''});
			jQuery('html, body, #wpcontent').css('padding-top','');
			barheight = jQuery('#wpadminbar').prop('scrollHeight');
			if (jQuery('body').hasClass('wp-admin')) {
				jQuery('#adminmenu').css('margin-top','');
				if (jQuery(window).width() > 600) {jQuery('html').css('padding-top',barheight);}
				else {
					jQuery('#wpcontent').css('padding-top',barheight);
					jQuery('#adminmenu').css('margin-top',barheight - 46);
				}
				if (barheight > 32) {jQuery('#wpadminbar').css('background-color','transparent');}
				else {jQuery('#wpadminbar').css('background-color','');}
			} else {
				htmlmargin = jQuery('html').css('margin-top').replace('px','');
				jQuery('html').css('padding-top',barheight - htmlmargin);
			}
			jQuery('#wp-admin-bar-root-default').css('width','');
			if ((jQuery('#wp-admin-bar-root-default').width() + jQuery('#wp-admin-bar-top-secondary').width()) > jQuery('#wpadminbar').width()) {
				jQuery('#wp-admin-bar-root-default').css('width','100%');
			}
		}
		jQuery('#wpadminbar').css('height','').css('height',jQuery('#wpadminbar').prop('scrollHeight'));
	}" . "\n";

	// --- check bar items function ---
	// 0.9.9: add fix for admin bar items background color
	// 0.9.9: class fix for rogue (non-standard) menu items
	$js .= "function adminsanity_bar_items() {
		jQuery('#wpadminbar #wp-toolbar').children().children().each(function() {
			jQuery(this).css('background', as_bar_background);
		});
		jQuery('#wp-admin-bar-root-default').children().each(function() {
			if ( (!jQuery(this).hasClass('admin-bar-default-menu'))
			  && (!jQuery(this).hasClass('admin-bar-extra-menu')) ) {
				jQuery(this).addClass('admin-bar-extra-menu');
			}
		});
	}" . "\n";

	if ( !$cycler && !$dropdown ) {
		return;
	}

	// --- bar cycler script ---
	// 0.9.9 add check for dropdown bar_responsive function
	if ( $cycler ) {

		// --- bar cycler function ---
		$js .= "function adminsanity_bar_cycle() {
			adminsanity_bar_items();
			if (jQuery('#wp-admin-bar-root-default').hasClass('default-menu-items')) {
				jQuery('#wp-admin-bar-root-default').removeClass('default-menu-items').addClass('extra-menu-items');
			} else if (jQuery('#wp-admin-bar-root-default').hasClass('extra-menu-items')) {
				jQuery('#wp-admin-bar-root-default').removeClass('extra-menu-items');
			} else {jQuery('#wp-admin-bar-root-default').addClass('default-menu-items');}
			adminsanity_bar_height();
			if (typeof adminsanity_bar_responsive == 'function') {adminsanity_bar_responsive();}
			else {jQuery('#wpadminbar').css('height','').css('height',jQuery('#wpadminbar').prop('scrollHeight'));}
		}" . "\n";
	}

	if ( $dropdown ) {

		// --- responsive dropdown heights function ---
		// 0.9.9: added responsive dropdown resizing
		$js .= "function adminsanity_bar_responsive() {
			if (jQuery('#wpadminbar').hasClass('dropdown')) {
				jQuery('#wp-toolbar li.admin-bar-default-menu, #wp-toolbar li.admin-bar-extra-menu, #wp-admin-bar-my-account').each(function() {
					if (jQuery(this).hasClass('menupop')) {
						height = jQuery(this).find('a').first().height() + jQuery(this).find('.ab-sub-wrapper').first().height();
						jQuery(this).css('height', height).css('width', jQuery(this).find('.ab-sub-wrapper').first().width());
					}
				});
				barheight = jQuery('#wpadminbar').css('margin-top','').prop('scrollHeight');
				if (jQuery('body').hasClass('wp-admin')) {
					if (jQuery(window).width() < 600) {jQuery('html').css('padding-top','32px');}
				} else {
					jQuery('body').css('padding-top',barheight);
					jQuery('#wpadminbar').css('margin-top','0px');
				}
			} else {
				jQuery('body, html').css('padding-top','');
				jQuery('#wpadminbar').css('margin-top','');
				jQuery('#wpadminbar').css('height','').css('height',jQuery('#wpadminbar').prop('scrollHeight'));
			}
		}" . "\n";

		// --- dropdown toggle function ---
		// 0.9.9 add dropdown toggle function
		$js .= "function adminsanity_bar_dropdown() {
			adminsanity_bar_items();
			if (jQuery('#wpadminbar').hasClass('dropdown')) {
				jQuery('#wpadminbar').animate({height:'32px'},750,function() {
					jQuery('#wpadminbar .ab-submenu').css('background', '');
					jQuery('#wpadminbar').removeClass('dropdown');
					jQuery('#wpadminbar #wp-toolbar').children().css('background',as_bar_background);
					jQuery('#wp-admin-bar-root-default').insertBefore('#wp-admin-bar-top-secondary');
					jQuery('#wp-toolbar li.admin-bar-default-menu, #wp-toolbar li.admin-bar-extra-menu, #wp-admin-bar-my-account').each(function() {
						if (jQuery(this).hasClass('menupop')) {jQuery(this).css('width','').css('height','');}
					});
					adminsanity_bar_responsive(); adminsanity_bar_height();
				});
			} else {
				if (!jQuery('body').hasClass('wp-admin')) {jQuery('#wpadminbar').appendTo('body');}
				jQuery('#wpadminbar').css({'height':'','background-color':'transparent'});
				jQuery('html, body, #wpcontent').css('padding-top','');
				jQuery('#wpadminbar').addClass('dropdown');
				jQuery('#wpadminbar .ab-submenu').css('background', as_bar_background);
				jQuery('#wp-admin-bar-top-secondary').insertBefore('#wp-admin-bar-root-default');
				jQuery('#wpadminbar #wp-toolbar').children().children().css('background',as_bar_background);
				jQuery('#wpadminbar #wp-toolbar').children().css('height','').css('background','');
				jQuery('#wp-toolbar li.admin-bar-default-menu.menupop, #wp-toolbar li.admin-bar-extra-menu.menupop, #wp-admin-bar-my-account.menupop').each(function() {
					jQuery(this).css('width', jQuery(this).find('.ab-sub-wrapper').width());
					jQuery(this).css('height', jQuery(this).find('.ab-sub-wrapper').height());
					jQuery(this).find('.ab-submenu .menupop a').each(function() {
						if (jQuery(this).parent().hasClass('menupop') && !jQuery(this).parent().find('.dropdown-arrow').length) {
							span = '<span class=\"dashicons dashicons-arrow-down dropdown-arrow\" onclick=\"adminsanity_bar_submenu(this);\"></span>';
							jQuery(span).insertBefore(jQuery(this));
						}
					});
				});
				barheight = jQuery('#wpadminbar').prop('scrollHeight');
				jQuery('#wpadminbar').css('height','0').animate({'height':barheight},1500);
				adminsanity_bar_responsive();
			}
		}" . "\n";

		// --- submenu dropdown toggle ----
		// 0.9.9: added submenu dropdown function
		$js .= "function adminsanity_bar_submenu(id) {
			jQuery(id).parent().find('.ab-sub-wrapper').toggleClass('dropped');
			if (jQuery(id).hasClass('dashicons-arrow-down')) {
				height = jQuery(id).height() + jQuery(id).parent().find('.ab-sub-wrapper').height();
				jQuery(id).parent().css('height', height); adminsanity_bar_responsive();
				jQuery(id).removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
			} else {
				jQuery(id).parent().css('height',''); adminsanity_bar_responsive();
				jQuery(id).removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
			}
		}" . "\n";
	}

	// --- filter and output ---
	$js = apply_filters( 'adminsanity_bar_scripts', $js );
	echo "<script>" . $js . "</script>";
}

// --- end function load wrapper ---
}

