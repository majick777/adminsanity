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

// -------------------------------
// Abort on Negative Load Constant
// -------------------------------
// 0.9.9: standardize loader constant names
if ( defined( 'ADMINSANITY_LOAD_BAR' ) && !ADMINSANITY_MODULE_LOAD_BAR ) {return;}

// --- get frontend override setting ---
// 0.9.9: added frontend override setting
if ( !is_admin() ) {
	$frontend = true;
	if ( defined( 'ADMINSANITY_BAR_CYCLER_FRONTEND' ) ) {
		$frontend = (bool)ADMINSANITY_BAR_FRONTEND;
	} elseif ( function_exists( 'adminsanity_get_settings' ) ) {
		$frontend = (bool)adminsanity_get_settings( 'bar_frontend' );
	} else {
		$frontend = (bool)apply_filters( 'adminsanity_bar_frontend', $frontend );
	}
	if ( !$frontend ) {return;}
}
	
// --- allow for use as an mu-plugin ---
// 0.9.9: attempt to prevent double load conflicts
if ( !function_exists( 'adminsanity_bar_default' ) ) {

// -------------------------
// Capture Default Admin Bar
// -------------------------
add_action( 'admin_bar_init', 'adminsanity_bar_default' );
function adminsanity_bar_default() {

	global $adminsanity, $wp_admin_bar;
	$original_bar = $wp_admin_bar;

	$adminsanity['bar-debug'] = false;
	if ( isset( $_GET['admin-test'] ) && ( 'bar' == $_GET['admin-test'] ) ) {
		$adminsanity['bar-debug'] = true;
	}

	// --- prevent endless loop ---
	remove_action( 'admin_bar_init', 'adminsanity_bar_default' );

	// --- initialize new admin bar class ---
	$admin_bar_class = apply_filters( 'wp_admin_bar_class', 'WP_Admin_Bar' );
	if ( !class_exists( $admin_bar_class ) ) {return false;}
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


	// --- debug point ---
	// for current_screen->base property not exists errors
	// error on (older?) WP installs to this point so far
	if ( $adminsanity['bar-debug'] ) {
		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			if ( !is_object( $current_screen ) ) {
				error_log( "No Current Screen Object" );
			} elseif ( !property_exists( $current_screen, 'base' ) ) {
				echo '<span id="current-screen-test" style="display:none;">' . print_r( $current_screen, true ) . '</span>';
				error_log( "No Current Screen Base" );
			}
			error_log( var_dump( $current_screen, true ) );
		} else {
			error_log( "No Current Screen Function" );
		}
	}
	
	// --- capture defaults by running action ---
	do_action_ref_array( 'default_admin_bar_menu', array( &$wp_admin_bar ) );
	$adminsanity['default_bar'] = $wp_admin_bar;
	
	// --- restore original to render ---
	$wp_admin_bar = $original_bar;
	
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
		$cycler = (bool)ADMINSANITY_BAR_CYCLER;
	} elseif ( function_exists( 'adminsanity_get_settings' ) ) {
		$cycler = (bool)adminsanity_get_settings( 'bar_cycler' );
	} else {
		$cycler = (bool)apply_filters( 'adminsanity_bar_cycler', $cycler );
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
		$dropdown = (bool)ADMINSANITY_BAR_DROPDOWN;
	} elseif ( function_exists( 'adminsanity_get_settings' ) ) {
		$dropdown = (bool)adminsanity_get_settings( 'bar_dropdown' );
	} else {
		$dropdown = (bool)apply_filters( 'adminsanity_bar_dropdown', $dropdown );
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
		// echo "Default Bar: " . print_r( $default_bar, true ). "<br>" . PHP_EOL;
		echo "Default Nodes: " . print_r( $default_nodes, true ). "<br>" . PHP_EOL;
		echo "Default Parents: " . print_r( $default_parents, true ). "<br>" . PHP_EOL;
		// echo "Admin Bar: " . print_r( $wp_admin_bar, true ) . "<br>" . PHP_EOL;
		echo "Admin Bar Nodes: " . print_r( $admin_bar_nodes, true ). "<br>" . PHP_EOL;
		echo "New Parents: " . print_r( $new_parents, true ) . "<br>" . PHP_EOL;
		echo "New Admin Bar: " . print_r( $new_admin_bar, true ) . "<br>" . PHP_EOL;
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
		$cycler = (bool)ADMINSANITY_BAR_CYCLER;
	} elseif ( function_exists( 'adminsanity_get_settings' ) ) {
		$cycler = (bool)adminsanity_get_settings( 'bar_cycler' );
	} else {
		$cycler = (bool)apply_filters( 'adminsanity_bar_cycler', $cycler );
	}

	// --- get bar dropdown setting ---
	// 0.9.9: check bar dropdown settings
	$dropdown = true;
	if ( defined( 'ADMINSANITY_BAR_DROPDOWN' ) ) {
		$dropdown = (bool)ADMINSANITY_BAR_DROPDOWN;
	} elseif ( function_exists( 'adminsanity_get_settings' ) ) {
		$dropdown = (bool)adminsanity_get_settings( 'bar_dropdown' );
	} else {
		$dropdown = (bool)apply_filters( 'adminsanity_bar_dropdown', $dropdown );
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
		#wpadminbar.dropdown #wp-toolbar #wp-admin-bar-root-default #wp-admin-bar-menu-cycler {display: inline-block;}' . PHP_EOL;
		
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
		#wpadminbar.dropdown ul, #wpadminbar.dropdown ul li {z-index: 99998;}' . PHP_EOL;

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
		';
		// TODO: fix admin bar underside padding on mobile width
	}

	// --- filter and output ---
	$css = apply_filters( 'adminsanity_bar_styles', $css );
	echo "<style>" . $css . "</style>";
}

// -----------------
// Admin Bar Scripts
// -----------------
// 0.9.9: moved from wp_after_admin_bar_render hook
add_action( 'admin_footer', 'adminsanity_bar_scripts' );
function adminsanity_bar_scripts() {

	$js = '';

	// --- get bar cycler setting ---
	// 0.9.9: check bar cycler setting
	$cycler = true;
	if ( defined( 'ADMINSANITY_BAR_CYCLER' ) ) {
		$cycler = (bool)ADMINSANITY_BAR_CYCLER;
	} elseif ( function_exists( 'adminsanity_get_settings' ) ) {
		$cycler = (bool)adminsanity_get_settings( 'bar_cycler' );
	} else {
		$cycler = (bool)apply_filters( 'adminsanity_bar_cycler', $cycler );
	}

	// --- get dropdown toggle setting ---
	// 0.9.9: check bar dropdown settings
	$dropdown = true;
	if ( defined( 'ADMINSANITY_BAR_DROPDOWN' ) ) {
		$dropdown = (bool)ADMINSANITY_BAR_DROPDOWN;
	} elseif ( function_exists( 'adminsanity_get_settings' ) ) {
		$dropdown = (bool)adminsanity_get_settings( 'bar_dropdown' );
	} else {
		$dropdown = (bool)apply_filters( 'adminsanity_bar_dropdown', $dropdown );
	}
		
	// --- document ready load functions ---
	// 0.9.9: secondary menu and dropdown background fix
	// 0.9.9: added responsive dropdown resizing
	// $js .= "var as_bar_arrows = false; ";
	$js .= "var as_bar_background = jQuery('#wpadminbar').css('background');" . PHP_EOL;
	$js .= "jQuery(document).ready(function() {
		adminsanity_bar_items(); adminsanity_bar_height();
		setTimeout(function() {adminsanity_bar_height();}, 5000);
		jQuery(window).resize(function () {";
		if ( $dropdown ) {$js .= "adminsanity_bar_responsive(); ";}
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
		}
		jQuery('#wpadminbar').css('height','').css('height',jQuery('#wpadminbar').prop('scrollHeight'));
	}" . PHP_EOL;

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
	}" . PHP_EOL;

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
		}" . PHP_EOL;
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
		}" . PHP_EOL;

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
		}" . PHP_EOL;

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
		}" . PHP_EOL;
	}
	
	// --- filter and output ---
	$js = apply_filters( 'adminsanity_bar_scripts', $js );
	echo "<script>" . $js . "</script>";
}

// --- close function load wrapper ---
}