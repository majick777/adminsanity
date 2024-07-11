<?php

// ------------------------------
// === AdminSanity Admin Menu ===
// ------------------------------

// Automatically splits WordPress Admin Menus into 3 sections:
// A = "Content" = Dashboard and Post Type Menu Items
// B = "Manage" = All Other Default WordPress Menu Items
// C = "Extensions" = Any Other Added Menu Items

// Use adminsanity_menu_keep_positions filter to maintain specific menu positions
// via an array of menu IDs (see example filter usage at end of file)

// === AdminSanity Menus ===
// - Store Default Admin Menu
// - Ordered Split Admin Menu
// - Filter Administrator Menu Order
// - Admin Menu Styles
// - Admin Menu Scripts
// - Clone Styles Script
// - Plugin Settings Current Menu Fix
// - Example User Menu Order (Admin)
// - Test Keep Menu Order Filter

// ----------------------
// Module Option Settings
// ----------------------

// Features
// --------
// All Features are boolean true/false and default to true.
// Feature               | Constant                  | Filter
// Meta Menu Headings    | ADMINSANITY_MENU_METAS    | adminsanity_menu_metas
// Plugin Settings Menu  | ADMINSANITY_MENU_PLUGINS  | adminsanity_menu_plugins
// Expand Menu Icon      | ADMINSANITY_MENU_EXPANDER | adminsanity_menu_expander

// Filters
// -------
// Filter Setting        | Filter                          | Type    | Default
// Keep Menu Positions   | adminsanity_menu_keep_positions | array | | none
// Meta Menu Order       | adminsanity_menu_meta_order     | array   | array a,b,c
// Meta Menu Labels      | adminsanity_menu_meta_labels    | array   | default labels
// Plugin Settings Item  | adminsanity_menu_plugins_item   | boolean | item array
// Menu Module Styles    | adminsanity_menu_styles         | string  | plugin css
// Menu Module Scripts   | adminsanity_menu_scripts        | string  | plugin js

// Administrator Reorder | ADMINSANITY_MENU_ADMIN_REORDER | adminsanity_menu_admin_reorder

// -----------------
// Development TODOs
// -----------------
// ? check for separator display glitch when reordering meta sections
// - optimize javascript element adding using jQuery ?
// / fix full page cloned menu hover rules (very hard!!!)
// ? add dropdown arrows to main menu items for submenu item display ?
// ? add user profile setting for toggling meta menu reordering preference ?
// ? save user meta menu (and submenu?) expanded / collapsed states ?


// --- abort on negative load constant ---
// 0.9.9: standardize loader constant names
if ( defined( 'ADMINSANITY_LOAD_MENU' ) && !ADMINSANITY_LOAD_MENU ) {
	return;
}

// --- allow for use as an mu-plugin ---
// 0.9.9: attempt to prevent double load conflicts
if ( !function_exists( 'adminsanity_menu_loader' ) ) {

// ------------------
// Menu Loader Action
// ------------------
// 1.0.3: added loader action to avoid block editor conflicts
add_action( 'admin_menu', 'adminsanity_menu_loader', 9 );
function adminsanity_menu_loader() {

	// --- check for block editor or gutenberg plugin page ---
	if ( function_exists( 'get_current_screen' ) ) {
		$current_screen = get_current_screen();
		if ( is_object( $current_screen ) && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return;
		}
	} elseif ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
		return;
	}

	// 1.0.3: enqueue scripts/styles only if admin_menu action called
	add_action( 'admin_footer', 'adminsanity_menu_scripts', 99 );
	add_action( 'admin_print_styles', 'adminsanity_menu_styles');

	// 1.0.3: custom menu order only if admin menu action called
	add_action( 'custom_menu_order', '__return_true', 11 );
	add_filter( 'menu_order', 'adminsanity_menu_split_order', 20 );
	
}

// ------------------------
// Store Default Admin Menu
// ------------------------
add_action( '_network_admin_menu', 'adminsanity_menu_store_default', 0 );
add_action( '_user_admin_menu', 'adminsanity_menu_store_default', 0 );
add_action( '_admin_menu', 'adminsanity_menu_store_default', 0 );
function adminsanity_menu_store_default() {
	global $adminsanity, $menu, $submenu;
	$adminsanity['default_menu'] = $menu;
	$adminsanity['default_submenu'] = $submenu;
}

// ------------------------
// Ordered Split Admin Menu
// ------------------------
function adminsanity_menu_split_order( $menu_order ) {

	global $menu, $submenu, $adminsanity;

	$default_menu = $adminsanity['default_menu'];
	$default_submenu = $adminsanity['default_submenu'];

	// --- add separator IDs as classnames ---
	// 0.9.6: added for meta menu javascript
	foreach ( $menu as $i => $item ) {
		if ( 'separator' == substr( $item[2], 0, 9 ) ) {
			$menu[$i][4] .= ' ' . $item[2];
		}
	}

	// Setting Submenu Split
	// ---------------------
	// 0.9.7: added for settings menu splitting (default and extras)

	// --- check whether to add extra settings menu ---
	$plugin_settings = true;
	if ( defined( 'ADMINSANITY_MENU_PLUGINS' ) ) {
		$plugin_settings = (bool) ADMINSANITY_MENU_PLUGINS;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$plugin_settings = (bool) adminsanity_get_setting( 'menu_plugins' );
	} else {
		$plugin_settings = (bool) apply_filters( 'adminsanity_menu_plugins', $plugin_settings );
	}

	if ( $plugin_settings ) {

		$extra_settings = array();

		// --- get default Settings submenus ---
		if ( isset( $submenu['options-general.php'] ) ) {

			$settings_links = $extra_settings = array();

			// --- get default settings item links ---
			foreach ( $default_submenu['options-general.php'] as $settings_item ) {
				$settings_links[] = $settings_item[2];
			}

			// --- loop to distinguish added settings items ---
			foreach ( $submenu['options-general.php'] as $i => $settings_item ) {
				// 0.9.8: fix for some submenu page URLs
				if ( !in_array( $settings_item[2], $settings_links ) ) {
					// 0.9.9: fix to not move direct options.php link
					if ( 'options.php' != $settings_item[2] )  {
						if ( !strstr( $settings_item[2], 'page=' ) ) {
							$settings_item[2] = 'options-general.php?page=' . $settings_item[2];
						}
						$extra_settings[] = $settings_item;
						unset( $submenu['options-general.php'][$i] );
					}
				}
			}
		}

		if ( count( $extra_settings ) > 0 ) {

			$submenu['options.php'] = $extra_settings;
			add_action( 'admin_footer', 'adminsanity_menu_settings_fix' );

			// --- loop to split menu position ---
			$menua = $menub = $menuc = array();
			$found = false;
			foreach ( $menu as $i => $item ) {
				if ( !$found ) {
					$menua[$i] = $item;
					if ( $item[2] == 'options-general.php' ) {
						$found = true;
					}
				} else {
					$menub[$i] = $item;
				}
			}

			// --- increment second-half menu indexes ---
			foreach ( $menub as $i => $item ) {
				$menuc[$i+1] = $item;
			}

			// --- add extra settings menu ---
			$settings_item = array(
				__( 'Plugin Settings', 'adminsanity' ),	// menu name
				'manage_options',						// capability
				'options.php',							// base link
				'', 									// ???
				'menu-top menu-icon-settings',			// classes
				'menu-extra-settings',					// id
				'dashicons-admin-generic',				// icon
			);
			// 0.9.8: add filter for extra settings menu
			$settings_item = apply_filters( 'adminsanity_menu_plugins_item', $settings_item );
			$position = max( array_keys( $menua ) ) + 1;
			$menua[$position] = $settings_item;
			$menu = array_merge( $menua, $menuc );
		}

	}

	// Main Menu Reorder
	// -----------------

	// --- filter menu items to move to top ---
	// 1.0.3: added for top menu items (above mega menus)
	$top = apply_filters( 'adminsanity_menu_top_positions', array() );

	// --- filter menu items whose position to keep ---
	$keep = apply_filters( 'adminsanity_menu_keep_positions', array() );

	// --- set empty split menu arrays ---
	$menutop = $menua = $menua2 = $menub = $menuc = array();

	// --- loop the menu items ---
	foreach ( $menu as $i => $item ) {
		$found = false;
		foreach ( $adminsanity['default_menu'] as $j => $menu_item ) {
			if ( $item[2] == $menu_item[2] ) {
				$found = true;
			}
		}

		// 1.0.3: check for top menu position Items
		if ( in_array( $item[2], $top ) ) {
			$menutop[] = $item[2];
		} else {

			// 0.9.6: remove some separators from menu order temporarily
			// 0.9.6: check for explicit content items for menu a
			$separators = array( 'separator1', 'separator2', 'separator-last' );
			$contentitems = array( 'upload.php', 'link-manager.php', 'edit-comments.php' );
			if ( $item[2] == 'separator1' ) {
				$separator1 = array( $i => 'separator1' );
			} elseif ( !in_array( $item[2], $separators ) ) {
				if ( ( 'index.php' == $item[2] ) || ( strpos( $item[2], 'edit.php' ) === 0 ) ) {
					$menua[$i] = $item[2];
				} elseif ( in_array( $item[2], $contentitems ) ) {
					$menua2[$i] = $item[2];
				} elseif ( $item[2] == 'options.php' ) {
					$menub[$i] = $item[2];
				} elseif ( !$found ) {
					$menuc[$i] = $item[2];
				} else {
					$menub[$i] = $item[2];
				}
			}

		}
	}
	// 0.9.6: move separator1 to split menu A
	$menua[] = 'separator1';
	foreach ( $menua2 as $item ) {
		$menua[] = $item;
	}
	unset( $menua2 );

	// --- exception - move Settings item to top of section! ---
	// 0.9.6: do not rely on separator2 position
	if ( !in_array( 'options-general.php', $keep ) ) {
		$menub2 = array();
		foreach ( $menub as $i => $itemname ) {
			if ( $itemname != 'options-general.php' ) {
				$menub2[$i] = $itemname;
			}
		}
		// $menub = array_merge( array( 'options-general.php' ), $menub2 );
		$optionsgeneral = array( 'options-general.php' );
		$menub = array_flip( array_merge( array_flip( $optionsgeneral ), array_flip( $menub2 ) ) );
	}
	// 0.9.6: add exception for extra settings menu
	if ( $plugin_settings ) {
		if ( !in_array( 'options.php', $keep ) ) {
			$menub2 = array(); $found = false;
			foreach ( $menub as $i => $itemname ) {
				if ( $found ) {
					$i++;
				}
				if ( $itemname != 'options.php' ) {
					$menub2[$i] = $itemname;
				}
				if ( $itemname == 'options-general.php' ) {
					$i++;
					$found = true;
					$menub2[$i] = 'options.php';
				}
			}
			$menub = $menub2;
		}
	}

	// --- get the menu items whose positions to keep ---
	if ( count( $keep ) > 0 ) {
		$sep = 0;
		$menua_keep = $menub_keep = $menuc_keep = array();
		foreach ( $menu as $i => $item ) {
			if ( $item[2] == 'separator2' ) {
				$sep = 1;
			} elseif ( $item[2] == 'separator-last' ) {
				$sep = 2;
			}
			if ( in_array( $item[2], $keep ) ) {
				if ( $sep == 0 ) {
					$menua_keep[$i] = $item[2];
					if ( in_array( $item[2], $menub ) ) {
						$key = array_search( $item[2], $menub );
						unset( $menub[$key] );
					} elseif ( in_array( $item[2], $menuc ) ) {
						$key = array_search( $item[2], $menuc );
						unset( $menuc[$key] );
					}
				} elseif ( $sep == 1 ) {
					$menub_keep[$i] = $item[2];
					if ( in_array( $item[2], $menua ) ) {
						$key = array_search( $item[2], $menua );
						unset( $menua[$key] );
					} elseif ( in_array( $item[2], $menuc ) ) {
						$key = array_search( $item[2], $menuc );
						unset( $menuc[$key] );
					}
				} elseif ( $sep == 2 ) {
					$menuc_keep[$i] = $item[2];
					if ( in_array( $item[2], $menua ) ) {
						$key = array_search( $item[2], $menua );
						unset( $menua[$key] );
					} elseif ( in_array( $item[2], $menub ) ) {
						$key = array_search( $item[2], $menub );
						unset( $menub[$key] );
					}
				}
			}
		}
	}

	// --- debug point ---
	// 1.0.0: explicitly validatate GET value
	// 1.0.2: fix to incorrect get key (as-menu-debug)
	if ( isset( $_GET['as-debug'] ) && in_array( $_GET['as-debug'], array( 'all', 'menu' ) ) ) {
		echo '<span style="display:none;">[AdminSanity]';
		echo 'Keep Menu Items A: ' . esc_html( print_r( $menua_keep, true ) ) . "\n";
		echo 'Keep Menu Items B: ' . esc_html( print_r( $menub_keep, true ) ) . "\n";
		echo 'Keep Menu Items C: ' . esc_html( print_r( $menuc_keep, true ) ) . "\n";
		echo '</span>';
	}

	// --- merge the kept menu items with split menus ---
	// (use double array_flip to preserve position keys)
	if ( count( $menua_keep ) > 0 ) {
		$menua = array_flip( array_merge( array_flip( $menua_keep ), array_flip( $menua ) ) );
	}
	if ( count( $menub_keep ) > 0 ) {
		$menub = array_flip( array_merge( array_flip( $menub_keep ), array_flip( $menub ) ) );
	}
	if ( count( $menuc_keep ) > 0 ) {
		$menuc = array_flip( array_merge( array_flip( $menuc_keep ), array_flip( $menuc ) ) );
	}

	// --- debug point ---
	// 1.0.3: update debug point output
	if ( isset( $_GET['as-debug'] ) && in_array( $_GET['as-debug'], array( 'all', 'menu' ) ) ) {
		echo '<span style="display:none;">[AdminSanity]';
		echo 'Top Menu Items: ' . esc_html( print_r( $menutop, true ) ) . "\n";
		echo 'Menu Items A: ' . esc_html( print_r( $menua, true ) ) . "\n";
		echo 'Menu Items B: ' . esc_html( print_r( $menub, true ) ) . "\n";
		echo 'Menu Items C: ' . esc_html( print_r( $menuc, true ) ) . "\n";
		echo '</span>';
	}
	
	// --- resort split menus and merge to final menu order ---
	// 0.9.9: use single keyed array for menu items
	ksort( $menua );
	ksort( $menub );
	ksort( $menuc );
	$menu_items = array();
	foreach ( $menua as $item ) {
		$menu_items['a'][] = $item;
	}
	foreach ( $menub as $item ) {
		$menu_items['b'][] = $item;
	}
	foreach ( $menuc as $item ) {
		$menu_items['c'][] = $item;
	}

	// --- debug point ---
	// 1.0.3: update debug point output
	if ( isset( $_GET['as-debug'] ) && in_array( $_GET['as-debug'], array( 'all', 'menu' ) ) ) {
		echo '<span style="display:none;">[AdminSanity]';
		echo 'Sorted Menu Items A: ' . esc_html( print_r( $menu_items['a'], true ) ) . "\n";
		echo 'Sorted Menu Items B: ' . esc_html( print_r( $menu_items['b'], true ) ) . "\n";
		echo 'Sorted Menu Items C: ' . esc_html( print_r( $menu_items['c'], true ) ) . "\n";
		echo '</span>';
	}

	// --- get meta menu setting ---
	$meta_menus = true;
	if ( defined( 'ADMINSANITY_MENU_METAS' ) ) {
		$meta_menus = (bool) ADMINSANITY_MENU_METAS;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$meta_menus = (bool) adminsanity_get_setting( 'menu_metas' );
	} else {
		$meta_menus = (bool) apply_filters( 'adminsanity_menu_metas', $meta_menus );
	}

	// --- loop to mark mega menu classes ---
	// 0.9.6: added mega menu classes to menu items
	if ( $meta_menus ) {
		foreach ( $menu as $i => $item ) {
			if ( in_array( $item[2], $menu_items['a'] ) ) {
				$menu[$i][4] .= ' content-menu-item';
			} elseif ( in_array( $item[2], $menu_items['b'] ) ) {
				$menu[$i][4] .= ' manage-menu-item';
			} elseif ( in_array( $item[2], $menu_items['c'] ) ) {
				$menu[$i][4] .= ' extensions-menu-item';
			}
		}
	}

	// --- filter order and merge ---
	// A = Dashboard and Post Type Menus
	// B = All Other Default WordPress Menus
	// C = Any Other Added Menu Items
	// 0.9.6: added meta menu order filtering
	// 0.9.9: improved filtering for menu order
	// TODO: check/fix separator display glitch on reordering
	$separator2 = array( 'separator2' );
	$separatorlast = array( 'separator-last' );
	$meta_menu_order = array( 'a', 'b', 'c' );
	$meta_menu_order = apply_filters( 'adminsanity_menu_meta_order', $meta_menu_order );
	if ( $meta_menu_order && is_array( $meta_menu_order ) ) {
		// 1.0.3: insert top menu items first
		// $new_menu_order = array();
		$new_menu_order = $menutop;
		foreach ( $meta_menu_order as $i => $key ) {
			$key = strtolower( $key );
			if ( array_key_exists( $key, $menu_items ) ) {
				$new_menu_order = array_merge( $new_menu_order, $menu_items[$key] );
				if ( ( $i == 0 ) && ( count( $meta_menu_order ) > 1 ) ) {
					$new_menu_order = array_merge( $new_menu_order, $separator2 );
				}
				if ( ( $i == 1 ) && ( count( $meta_menu_order ) > 2 ) ) {
					$new_menu_order = array_merge( $new_menu_order, $separatorlast );
				}
			}
		}
		if ( count( $new_menu_order ) > 0 ) {
			$menu_order = $new_menu_order;
		}

		// --- debug point ---
		// 1.0.0: explicitly validate GET value
		if ( isset( $_GET['as-debug'] ) && in_array( $_GET['as-debug'], array( 'all', 'menu' ) ) ) {
			echo '<span style="display:none;">[AdminSanity] Menu Order:' . esc_html( print_r( $menu_order, true ) ) . '</span>' . "\n";
			print_r( get_defined_constants() );
		}
	}

	return $menu_order;
}

// -----------------
// Admin Menu Styles
// -----------------
function adminsanity_menu_styles() {

	// --- set admin menu styles ---

	// - remove top margin -
	$css = '#adminmenuwrap #adminmenu {margin-top: 0;}';

	// - separator lines -
	$css .= 'ul#adminmenu li.wp-menu-separator {border-bottom: 1px solid #F1F1F1; margin-bottom:0;}';

	// - mini triangles on separators -
	// ::after ?
	$css .= '#adminmenu div.separator:after {
		position: absolute; right: 0; height: 0; width: 0; margin-top: 1px; pointer-events: none;
		border: 4px solid transparent; border-right-color: #f1f1f1; content: " ";}';

	// --- get meta menu setting ---
	$meta_menus = true;
	if ( defined( 'ADMINSANITY_MENU_METAS' ) ) {
		$meta_menus = (bool) ADMINSANITY_MENU_METAS;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$meta_menus = (bool) adminsanity_get_setting( 'menu_metas' );
	} else {
		$meta_menus = (bool) apply_filters( 'adminsanity_menu_metas', $meta_menus );
	}

	// --- get user colour scheme ---
	// 0.9.9: added get user colour scheme
	$current_user = wp_get_current_user();
	$scheme = $current_user->admin_color;

	// --- meta menu styles ---
	if ( $meta_menus ) {

		// - meta menu items -
		$css .= '#adminmenu .meta-menu {';
		$css .= 'display:none; text-align: center; font-size: 1em; line-height: 2em;';
		$css .= 'font-weight: bold; text-transform: uppercase; letter-spacing: 2px;}' . "\n";
		// 0.9.9 copy other color scheme rules explicitly via javascript
		if ( 'fresh' == $scheme ) {
			$css .= '#adminmenu .meta-menu {color: #FFF;}';
		}

		// - current meta menu -
		// 0.9.9 copy other color scheme rules explicitly via javascript
		if ( 'fresh' == $scheme ) {
			// #444444
			$css .= '#adminmenu .meta-menu.current-meta {background-color: #0073aa;}' . "\n";
		}

		// - background fix for separators with meta menus -
		if ( 'fresh' == $scheme ) {
			$css .= '#adminmenu .separator2.bgfix, #adminmenu .separator-last.bgfix {background-color: #0073aa;}' . "\n";
		}

		// - current meta menu separators -
		$css .= '#adminmenu .wp-menu-separator.current-meta {margin-bottom: 0;}' . "\n";

		// - fix for collapsed menu -
		$css .= '.folded #adminmenu .meta-menu {overflow: hidden; font-size: 1.2em; text-indent: 13px; letter-spacing: 30px;}' . "\n";
		$css .= '.wp-responsive-open #adminmenu .meta-menu {text-indent: 0; font-size: 1.3em; letter-spacing: 3px;}' . "\n";

		// - spacing fix for M in Manage -
		$css .= '.folded #adminmenu #manage-meta-menu {text-indent: 10px;}' . "\n";

		// - fix for auto-collapsed manu -
		$css .= '@media only screen and (max-width: 960px) {
			#adminmenu .meta-menu {overflow: hidden; font-size: 1.2em; text-indent: 13px; letter-spacing: 30px;}
			#adminmenu #manage-meta-menu {text-indent: 10px;}
		}' . "\n";
	}

	// --- get expander setting ---
	$expander = true;
	if ( defined( 'ADMINSANITY_MENU_EXPANDER' ) ) {
		$expander = (bool) ADMINSANITY_MENU_EXPANDER;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$expander = (bool) adminsanity_get_setting( 'menu_expander' );
	} else {
		$expander = (bool) apply_filters( 'adminsanity_menu_expander', $expander );
	}

	// --- full page menu styles ---
	// 0.9.9: added for expanded full page menu
	if ( $expander ) {

		// - expand menu button -
		// 0.9.9 copy other color scheme rules explicitly via javascript
		if ( 'fresh' == $scheme ) {
			$css .= '#adminsanity-menu-toggle #expand-button {color: #aaa ;}' . "\n";
			$css .= '#adminsanity-menu-toggle:hover span {color: #00b9eb;}' . "\n";
		}

		$css .= '#adminsanity-menu-toggle .expand-button-icon {width: 36px; height: 34px;}' . "\n";
		$css .= '#adminsanity-menu-toggle #expand-button {display: block; width: 100%;';
			$css .= ' height: 34px; margin: 0; border: none; padding: 0; position: relative; overflow: visible;';
			$css .= ' line-height: 34px; background: 0 0; cursor: pointer; outline: 0;}' . "\n";
		$css .= '#adminsanity-menu-toggle .expand-button-icon:after {';
    			$css .= ' content: "\f148"; display: block; position: relative; top: 7px;';
    			$css .= ' text-align: center; font: 400 20px/1 dashicons !important; speak: none;';
    			$css .= '-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;';
    			$css .= '-webkit-transform: rotate(180deg); transform: rotate(180deg);}' . "\n";
		$css .= '#adminsanity-menu-toggle .expand-button-icon, #adminsanity-menu-toggle .expand-button-label {';
    			$css .= 'display: block; position: absolute; top: 0; left: 0; line-height: 34px;}' . "\n";
	    	$css .= '#adminsanity-menu-toggle .expand-button-label {padding: 0 0 0 36px;}' . "\n";
	    	$css .= '.folded #adminmenu #adminsanity-menu-toggle .expand-button-label {display: none;}' . "\n";
	    	$css .= '@media only screen and (max-width: 960px) { ';
			$css .= '.auto-fold #adminmenu #adminsanity-menu-toggle .expand-button-label {display: none;}';
		$css .= '}' . "\n";
	    	$css .= '.wp-responsive-open #adminmenu #adminsanity-menu-toggle .expand-button-label {display: block;}' . "\n";
		$css .= '#adminsanity-menu-toggle .expand-button-icon:after, #adminsanity-menu-toggle .expand-button-label {';
			$css .= 'transition: all .1s ease-in-out;}' . "\n";
		$css .= '#expand-button.expanded .expand-button-icon:after, .rtl #expand-button .expand-button-icon:after {';
		 	$css .= '-webkit-transform: rotate(0deg); transform: rotate(0deg);}' . "\n";
		$css .= '.folded.rtl #expand-button.expanded .expand-button-icon:after {';
			$css .= 'transform: rotate(180deg); -webkit-transform: rotate(180deg);}' . "\n";

		// - admin menu expander bar icon ---
		$css .= '@media screen and (min-width: 781px) {' . "\n";
			$css .= '#wpadminbar #wp-toolbar #wp-admin-bar-expand-toggle {display: none;} }';
			$css .= '#wp-admin-bar-expand-toggle .ab-icon:before {';
				$css .= ' transform: rotate(90deg); -webkit-transform: rotate(90deg); top: 1px;';
    				$css .= ' content: "\f228"; display: inline-block; float: left; font: 400 40px/45px dashicons;';
			   	$css .= ' vertical-align: middle; outline: 0; margin: 0; -webkit-font-smoothing: antialiased;';
    				$css .= ' -moz-osx-font-smoothing: grayscale; height: 44px; width: 50px; padding: 0; border: none;';
    				$css .= 'text-align: center; text-decoration: none; box-sizing: border-box;}' . "\n";
    		// 1.0.0: added missing media query close bracket
    		$css .= '}' . "\n";

		// - page menu div -
		// (copied from wpbody-content)
		// TODO: check float rule for RTL languages ?
		$css .= '#adminsanity-menu-page {padding-bottom: 65px; float: left; width: 100%; overflow: visible !important;}' . "\n";

		// - page menu lists -
		$css .= '.adminsanity-menu-wrapper {display:inline-block; vertical-align: top; margin-right: 20px; margin-top: 20px; width: 350px;}' . "\n";
		$css .= '.adminsanity-menu-wrapper.last {margin-right: 0px;}' . "\n";

		// - menu headings -
		// 0.9.9 copy other color scheme rules explicitly via javascript
		if ( 'fresh' == $scheme ) {
			// 1.0.3: set background color explicitly for non-current menu heading
			$css .= '.adminsanity-menu-heading {background-color: #1d2327; color: #FFF;}' . "\n";
			$css .= '.adminsanity-menu-wrapper.current-meta .adminsanity-menu-heading {background-color: #0073aa !important;}' . "\n";
		}
		$css .= '.adminsanity-menu-heading {';
			$css .= 'text-align: center; font-size: 1em; line-height: 2em; width: 160px; padding: 7px 0; ';
			$css .= 'font-weight: bold; text-transform: uppercase; letter-spacing: 2px; display: inline-block; vertical-align:top;}' . "\n";
		$css .= '.adminsanity-menu .wp-menu-name {position: relative !important; left: 0px !important;}' . "\n";

		// - menu dropdown arrows =
		$css .= '.adminsanity-menu-dropdown {';
			$css .= 'display: inline-block; vertical-align: top;';
			$css .= 'font-size: 3em; line-height: 34px; width: 34px; height: 34px;}' . "\n";

		// - submenu dropdown -
		// TODO: check possible need for further z-index rules ?
		$css .= '.adminsanity-menu .wp-menu-open .wp-submenu {';
			$css .= 'position: relative !important; float: left !important;';
			$css .= 'top: 0px !important; right: 0px !important; bottom: 0px !important; left: 0px !important;}'  . "\n";
		$css .= '.adminsanity-menu li.wp-has-submenu.wp-not-current-submenu {height: auto !important;}' . "\n";
		$css .= '.adminsanity-menu li.wp-has-submenu.wp-not-current-submenu ul.wp-submenu {display: none !important; height: 0 !important;}'  . "\n";
		$css .= '.adminsanity-menu li.menu-top {clear: both !important; width: 160px !important;}' . "\n";
		$css .= '.adminsanity-menu li.wp-has-current-submenu {height: 35px !important;}' . "\n";

		// - submenu dropdown arrows -
		$css .= '.submenu-dropdown-arrow, .submenu-dropdown-arrow-alt {float:right; text-align:right;';
			$css .= 'margin-right: -20px; font-size: 2.5em; line-height: 34px; width: 34px; height: 34px;}' . "\n";
		$css .= '.submenu-dropdown-arrow-alt {display: none;}' . "\n";

		// - float expanded submenus -
		$css .= '.adminsanity-menu li.wp-menu-open ul.wp-submenu {';
			$css .= 'border-top: 1px solid #FFF !important;';
			$css .= 'margin-left: 185px !important; margin-top: -35px !important;}' . "\n";

		// - unfloat for medium screens -
		$css .= '@media only screen and (max-width: 1149px) {' . "\n";
			$css .= '.adminsanity-menu-wrapper {width: 200px; margin-right: 10px;}' . "\n";
			$css .= '.adminsanity-menu li.wp-menu-open ul.wp-submenu {margin-left: 0px !important; margin-top: 0px !important; border-top: none !important;}' . "\n";
			$css .= '.adminsanity-menu li.wp-has-current-submenu {height: auto !important;}'  . "\n";
			$css .= '.submenu-dropdown-arrow {display: none;}' . "\n";
			$css .= '.submenu-dropdown-arrow-alt {display: block;}' . "\n";
		$css .= '}';

		// - fixes for smaller screens -
		$css .= '@media only screen and (max-width: 599px) {';
			$css .= ' .adminsanity-menu-wrapper {margin-right: 0px;} ';
		$css .= '}' . "\n";

	}

	// --- filter and output ---
	$css = apply_filters( 'adminsanity_menu_styles', $css );
	echo '<style>' . $css . '</style>';
}

// ------------------
// Admin Menu Scripts
// ------------------
// 0.9.6: add meta menu creation script
// 0.9.9: add full page expanded menu creation script
function adminsanity_menu_scripts() {

	global $adminsanity;

	// --- check add meta menu setting ---
	$meta_menus = true;
	if ( defined( 'ADMINSANITY_MENU_METAS' ) ) {
		$meta_menus = (bool) ADMINSANITY_MENU_METAS;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$meta_menus = (bool) adminsanity_get_setting( 'menu_metas' );
	} else {
		$meta_menus = (bool) apply_filters( 'adminsanity_menu_metas', $meta_menus );
	}

	// --- check add menu page setting ---
	// 0.9.9: added for expanded full page menu
	$expander = true;
	if ( defined( 'ADMINSANITY_MENU_EXPANDER' ) ) {
		$expander = (bool) ADMINSANITY_MENU_EXPANDER;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$expander = (bool) adminsanity_get_setting( 'menu_expander' );
	} else {
		$expander = (bool) apply_filters( 'adminsanity_menu_expander', $expander );
	}

	// --- bug out if both features are disabled ---
	if ( !$meta_menus && !$expander ) {
		return;
	}

	if ( $meta_menus ) {

		// --- set menu labels ---
		// 0.9.9: added menu label filtering
		$labels = array(
			'content'    => __( 'Content', 'adminsanity' ),
			'manage'     => __( 'Manage', 'adminsanity' ),
			'extensions' => __( 'Extensions', 'adminsanity' ),
		);
		// (note: you can change labels but do not change keys)
		$labels = apply_filters( 'adminsanity_menu_meta_labels', $labels );

		// --- filter menu order ---
		$meta_menu_order = array( 'a', 'b', 'c' );
		$meta_menu_order = apply_filters( 'adminsanity_menu_meta_order', $meta_menu_order );
		if ( !is_array( $meta_menu_order ) || ( count( $meta_menu_order ) < 1 ) ) {
			return;
		}

		// --- maybe reorder section labels ---
		$ordered_labels = array();
		foreach ( $meta_menu_order as $key ) {
			if ( 'a' == $key ) {
				$ordered_labels['content'] = $labels['content'];
			} elseif ( 'b' == $key ) {
				$ordered_labels['manage'] = $labels['manage'];
			} elseif ( 'c' == $key ) {
				$ordered_labels['extensions'] = $labels['extensions'];
			}
		}
		$labels = $ordered_labels;
	}

	// --- set empty style rules strings ---
	$js = "as_rules = colorrules = hoverrules = ''; ";

	// 0.9.9: set menu debug mode
	$valid = false;
	if ( isset( $_GET['as-debug'] ) ) {
		// 1.0.0: use sanitize_title on GET value
		$debug = sanitize_title( $_GET['as-debug'] );
		if ( in_array( $debug, array( 'all', 'menu' ) ) ) {
			$js .= "as_menu_debug = true; ";
			$valid = true;
		}
	}
	if ( !$valid ) {
		$js .= "as_menu_debug = false; ";
	}

	// --- add get CSS function helper ---
	// (used to copy hover style rules)
	// ref: https://stackoverflow.com/a/23041055/5240159
	// 0.9.9: use try/catch block to prevent uncaught CORS errors
	$js .= "function adminsanity_get_style(rule, prop, scheme) {
		hostname = '" . esc_js( $_SERVER['HTTP_HOST'] ) . "';
		style = ''; sheets = document.styleSheets;
		for (var i = 0; i < sheets.length; i++) {
			if ( (sheets[i].href == null) || (sheets[i].href.indexOf(hostname) > -1) ) {
				try {rules = sheets[i].cssRules || sheets[i].rules;} catch(e) {rules = false;}
				if (rules) {
					for (var j = 0; j < rules.length; j++) {
						if (rules[j].selectorText && rules[j].style[prop]) {
							exact = false;
							if (rule.indexOf('##') > -1) {rule = rule.replace('##','#'); exact = true;}
							if ( (exact && (rules[j].selectorText == rule)) || (!exact && (rules[j].selectorText.indexOf(rule) > -1)) ) {
								if ( (style == '') || (scheme && sheets[i].href.indexOf('css/colors') > -1) ) {
									style = rules[j].style[prop];
									if (as_menu_debug) {console.log(sheets[i].href+' : '+rule+' : '+rules[j].selectorText+' : '+style);}
								}
							}
						}
					}
				}
			}
		}
		return style;
	}" . "\n";

	// --- add menu label arrays ---
	$i = 0;
	$js .= "var as_meta_keys = new Array(); var as_meta_labels = new Array();";
	foreach ( $labels as $key => $label ) {
		$js .= "as_meta_keys[" . $i . "] = '" . esc_js( $key ) . "'; ";
		$js .= "as_meta_labels[" . $i . "] = '" . esc_js( $label ) . "'; ";
		$i++;
	}
	$js .= "\n";
	
	// --- add meta menus toggle script ---
	if ( $meta_menus ) {

		$js .= "function adminsanity_menu_meta(id) {
			if (jQuery('#'+id+'-meta-menu').hasClass('meta-menu-open')) {
				jQuery('#adminmenu li.'+id+'-menu-item').hide(); /* animate({height:'0px'}, 500); */
				jQuery('#'+id+'-meta-menu').removeClass('meta-menu-open').addClass('meta-menu-closed');
				if (jQuery('#'+id+'-meta-menu').hasClass('current-meta')) {
					if (id == 'content') {jQuery('#adminmenu .separator2').addClass('bgfix');}
					else if (id == 'manage') {jQuery('#adminmenu .separator-last').addClass('bgfix');}
				}
			} else {
				jQuery('#adminmenu li.'+id+'-menu-item').show(); /* animate({height:'auto'}, 500); */
				jQuery('#'+id+'-meta-menu').removeClass('meta-menu-closed').addClass('meta-menu-open');
				if (jQuery('#'+id+'-meta-menu').hasClass('current-meta')) {
					if (id == 'content') {jQuery('#adminmenu .separator2').removeClass('bgfix');}
					else if (id == 'manage') {jQuery('#adminmenu .separator-last').removeClass('bgfix');}
				}
			}
		}" . "\n";

	}

	// --- menu page toggle script ---
	// 0.9.9: added for expanded full page menu
	if ( $expander ) {

		// --- localize translation for expand/collapse ---
		$js .= "var as_expand_menu = '" . esc_js( __( 'Expand Menu', 'adminsanity' ) ) . "'; ";
		$js .= "var as_collapse_menu = '" . esc_js( __( 'Collapse Menu', 'adminsanity' ) ) . "'; ";
		$js .= "var as_menu_added = false; var as_menu_adding = false; var as_delayed_menu = false;" . "\n";

		// --- add expand menu item to admin menu ---
		// TODO: optimize element adding using jQuery ?
		$js .= "expand = document.createElement('li');
		expand.setAttribute('id', 'adminsanity-menu-toggle');
		expand.setAttribute('onclick', 'adminsanity_menu_toggle();');
		el = document.getElementById('adminmenu');
		el.insertBefore(expand, el.childNodes[0] || null);" . "\n";

			// --- create button to expand menu page ---
			$js .= "button = document.createElement('button');
			button.setAttribute('id', 'expand-button');
			button.setAttribute('aria-label', as_expand_menu);
			button.setAttribute('aria-expanded', 'false');" . "\n";

			// --- button icon ---
			// <span class="collapse-button-icon" aria-hidden="true">
			$js .= "span = document.createElement('span');
			span.setAttribute('class', 'expand-button-icon');
			button.appendChild(span);" . "\n";

			// --- button label ---
			// <span class="collapse-button-label">Collapse menu</span>
			$js .= "span = document.createElement('span');
			span.setAttribute('id', 'adminsanity-toggle-label');
			span.setAttribute('class', 'expand-button-label');
			span.innerHTML = as_expand_menu; /* 'Expand Menu' */
			button.appendChild(span);" . "\n";

		// --- add button ---
		$js .= "document.getElementById('adminsanity-menu-toggle').appendChild(button);" . "\n";

		// --- add expanded toggle function ---
		$js .= "function adminsanity_menu_toggle() {
			if (!as_menu_added) {
				if (as_menu_adding) {var as_delayed_menu = setInterval(adminsanity_menu_delayed_toggle, 500); return;}
				else {adminsanity_menu_load();}
			}
			if (document.getElementById('adminsanity-menu-page').style.display == 'none') {
				document.getElementById('wpbody-content').style.display = 'none';
				document.getElementById('adminsanity-menu-page').style.display = '';
				document.getElementById('adminsanity-toggle-label').innerHTML = as_collapse_menu; /* 'Collapse Menu' */
				document.getElementById('expand-button').setAttribute('aria-expanded','true');
				/* if (jQuery('#collapse-menu').attr('aria-expanded') == 'true') {jQuery('#collapse-menu').click();} */
				jQuery('body').addClass('folded'); jQuery('#expand-button').addClass('expanded');
				/* jQuery('#adminsanity-notices-clear').prependTo('#adminsanity-menu-page');
				jQuery('#adminsanity-notices-wrap').prependTo('#adminsanity-menu-page');
				jQuery('#adminsanity-notices-menu').prependTo('#adminsanity-menu-page'); */
			} else {
				document.getElementById('adminsanity-menu-page').style.display = 'none';
				document.getElementById('wpbody-content').style.display = '';
				document.getElementById('adminsanity-toggle-label').innerHTML = as_expand_menu; /* 'Expand Menu' */
				/* if (jQuery('#collapse-menu').attr('aria-expanded') == 'false') {jQuery('#collapse-menu').click();} */
				jQuery('body').removeClass('folded'); jQuery('#expand-button').removeClass('expanded');
				/* jQuery('#adminsanity-notices-wrap').insertAfter('#admin-top-general-notices');
				jQuery('#adminsanity-notices-clear').insertAfter('#admin-top-general-notices');
				jQuery('#adminsanity-notices-menu').insertAfter('#admin-top-general-notices'); */
			}
		}" . "\n";

		// --- delayed toggle cycler ---
		// 1.0.3: added to catch delayed menu load
		$js .= "function adminsanity_menu_delayed_toggle() {
			if (as_menu_debug) {console.log('Waiting for expanded menu page to load...');}
			if (as_menu_added) {clearInterval(as_delayed_menu); adminsanity_menu_toggle();}
		}" . "\n";

		// --- toggle menu state ---
		$js .= "function adminsanity_toggle_section(id) {
			if (jQuery('#adminsanity-'+id+'-menu-dropdown').hasClass('expanded')) {
				jQuery('#adminsanity-'+id+'-menu-dropdown').removeClass('expanded');
				jQuery('#adminsanity-'+id+'-menu').children().each(function() {
					if (jQuery(this).hasClass('wp-has-submenu')) {
						jQuery(this).removeClass('wp-has-current-submenu wp-menu-open').addClass('wp-not-current-submenu');
						jQuery(this).find('.submenu-dropdown-arrow').html('&#9662;');
						jQuery(this).find('.submenu-dropdown-arrow-alt').html('&#9662;');
					}
				});
			} else {
				jQuery('#adminsanity-'+id+'-menu-dropdown').addClass('expanded');
				jQuery('#adminsanity-'+id+'-menu').children().each(function() {
					if (jQuery(this).hasClass('wp-has-submenu')) {
						jQuery(this).removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
						jQuery(this).find('.submenu-dropdown-arrow').html('&#9656;');
						jQuery(this).find('.submenu-dropdown-arrow-alt').html('&#9652;');
					}
				});
			}
		}" . "\n";

		// --- toggle submenu state ---
		// TODO: maybe set toggle menu cookie / user option ?
		// $js .= "value = ''"; // ? get opened submenu values ?
		// $js .= "date = new Date(); date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000)); ";
		// $js .= "document.cookie = 'adminsanity_first_load=' + value + '; expires=' + date.toUTCString() + '; path=/'; ";
		$js .= "function adminsanity_toggle_submenu(id) {
			if (jQuery('#'+id).hasClass('wp-menu-open')) {
				jQuery('#'+id).removeClass('wp-has-current-submenu wp-menu-open').addClass('wp-not-current-submenu');
				jQuery('#'+id+' .submenu-dropdown-arrow').html('&#9662;');
				jQuery('#'+id+' .submenu-dropdown-arrow-alt').html('&#9662;');
			} else {
				jQuery('#'+id).removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
				jQuery('#'+id+' .submenu-dropdown-arrow').html('&#9656;');
				jQuery('#'+id+' .submenu-dropdown-arrow-alt').html('&#9652;');
			}
		}" . "\n";

		// --- add clone styles script ---
		$js .= adminsanity_menu_clone_styles_script();

		// --- dynamic creation of menu ---
		$js .= "function adminsanity_menu_load() {" . "\n";

			// 1.0.3: added check of menu added/adding flags
			$js .= "if (as_menu_added || as_menu_adding) {return;}" . "\n";
			$js .= "as_menu_adding = true;" . "\n";

			// --- set menu added flag ---
			$js .= "as_menu_background = jQuery('#adminmenuwrap').css('background-color'); " . "\n";

			// --- create new page div ---
			// 1.0.3: optimized page adding using jQuery
			$js .= "menupage = jQuery('<div>').attr('id', 'adminsanity-menu-page').css('display','none');
			menupage.prependTo('#wpbody');" . "\n";
			// jQuery('#wpbody').append(menupage);" . "\n";
			/* $js .= "pagediv = document.createElement('div');
			pagediv.setAttribute('id', 'adminsanity-menu-page');
			pagediv.setAttribute('style', 'display:none;');
			el = document.getElementById('wpbody');
			el.insertBefore(pagediv, el.childNodes[0] || null);" . "\n"; */

			// --- dynamic creation of menu wrappers ---
			// TODO: optimize menu creation using jQuery ?
			// 0.9.9: loop using key/label javascript array instead of PHP
			// $i = 0;
			// foreach ( $labels as $key => $label ) {
			$js .= "for (i = 0; i < as_meta_keys.length; i++) {" . "\n";
				// $i++;
				$js .= "key = as_meta_keys[i]; label = as_meta_labels[i];" . "\n";

				$js .= "div = document.createElement('div');
				div.setAttribute('id', 'adminsanity-'+key+'-menu-wrapper');
				if (i == as_meta_keys.length) {div.setAttribute('class', 'adminsanity-menu-wrapper last');}
				else {div.setAttribute('class', 'adminsanity-menu-wrapper');}
				document.getElementById('adminsanity-menu-page').appendChild(div);" . "\n";

				$js .= "div = document.createElement('div');
				div.setAttribute('id', 'adminsanity-'+key+'-menu-heading');
				div.setAttribute('class', 'adminsanity-menu-heading');
				div.setAttribute('onclick', 'adminsanity_toggle_section(\"'+key+'\");');
				div.innerHTML = label;
				document.getElementById('adminsanity-'+key+'-menu-wrapper').appendChild(div);" . "\n";
				// $js .= "jQuery('#adminsanity-'+key+'-menu-heading').css('background-color', as_menu_background);"

				$js .= "div = document.createElement('div');
				div.setAttribute('id', 'adminsanity-'+key+'-menu-dropdown');
				div.setAttribute('class', 'adminsanity-menu-dropdown');
				div.setAttribute('onclick', 'adminsanity_toggle_section(\"'+key+'\");');
				div.innerHTML = '&#9662;';
				document.getElementById('adminsanity-'+key+'-menu-wrapper').appendChild(div);" . "\n";

				$js .= "ul = document.createElement('ul');
				ul.setAttribute('id', 'adminsanity-'+key+'-menu');
				ul.setAttribute('class', 'adminsanity-menu');
				document.getElementById('adminsanity-'+key+'-menu-wrapper').appendChild(ul);" . "\n";

			$js .= "}" . "\n";

			// --- clone admin menu ---
			// (note: uses cloneWithCSS jQuery plugin)
			$js .= "if (jQuery('body').hasClass('folded')) {refold = true;} else {refold = false;}
			if (jQuery('#wpwrap').hasClass('wp-responsive-open')) {reopen = true;} else {reopen = false;}
			jQuery('body').removeClass('folded'); jQuery('#wpwrap').removeClass('wp-responsive-open');
			jQuery('#adminmenu .wp-submenu').css({'display':'block','top':'0px','left':'0px'});
			jQuery('#adminmenu').cloneWithCSS().attr('id','adminsanity-adminmenu').appendTo('#adminsanity-menu-page');
			jQuery('#adminmenu .wp-submenu').css({'display':'','top':'','left':''});
			if (reopen) {jQuery('#wpwrap').addClass('wp-responsive-open');}
			if (refold) {jQuery('body').addClass('folded');}" . "\n";

			// --- loop main menu items ---
			$js .= "jQuery('#adminsanity-adminmenu').children().each(function() {" . "\n";

				// --- deduplicate admin menu item IDs ---
				$js .= "jQuery(this).attr('id', 'as_'+jQuery(this).attr('id')); ";
				$js .= "jQuery(this).find('[id]').attr('id', 'as_'+jQuery(this).attr('id'));" . "\n";

				// --- menu item background color fix ---
				$js .= "jQuery(this).css('background-color', as_menu_background);" . "\n";

				// --- move menu items to separate lists ---
				// 0.9.9: loop using key/label javascript array instead of PHP
				// foreach ( $labels as $key => $label ) {
				$js .= "for (i = 0; i < as_meta_keys.length; i++) {";
					$js .= "key = as_meta_keys[i];";
					$js .= "if (jQuery(this).hasClass(key+'-menu-item')) {jQuery(this).appendTo('#adminsanity-'+key+'-menu');}";
				$js .= "}" . "\n";
				// }

			$js .= "});
			jQuery('#adminsanity-adminmenu').remove();" . "\n";

			// 0.9.9: remove colors (for hover color fix)
			$js .= "jQuery('.adminsanity-menu-wrapper .wp-menu-name, .adminsanity-menu-wrapper .wp-menu-image')
				.css({'color':'','text-fill-color':'','-webkit-text-fill-color':''});" . "\n";

			// --- add submenu dropdowns for menu items ---
			// TODO: optimize element adding using jQuery ?
			$js .= "jQuery('.adminsanity-menu li.wp-has-submenu').each(function() {
				id = jQuery(this).attr('id');
				el = document.getElementById(id);
				arrow = document.createElement('div');
				arrow.setAttribute('class', 'submenu-dropdown-arrow');
				arrow.setAttribute('onclick', 'adminsanity_toggle_submenu(\"'+id+'\");');
				if (jQuery(this).hasClass('wp-menu-open')) {arrow.innerHTML = '&#9656;';}
				else {arrow.innerHTML = '&#9662;';}
				el.insertBefore(arrow, el.childNodes[0] || null);
				arrow = document.createElement('div');
				arrow.setAttribute('class', 'submenu-dropdown-arrow-alt');
				arrow.setAttribute('onclick', 'adminsanity_toggle_submenu(\"'+id+'\");');
				if (jQuery(this).hasClass('wp-menu-open')) {arrow.innerHTML = '&#9652;';}
				else {arrow.innerHTML = '&#9662;';}
				el.insertBefore(arrow, el.childNodes[0] || null);
			});" . "\n";

			// --- highlight currently active meta menu ---
			$js .= "jQuery('.adminsanity-menu li').each(function() {
				if (jQuery(this).hasClass('wp-has-current-submenu') || jQuery(this).hasClass('current')) {
					currentmeta = '';
					if (jQuery(this).hasClass('content-menu-item')) {currentmeta = 'content';}
					else if (jQuery(this).hasClass('manage-menu-item')) {currentmeta = 'manage';}
					else if (jQuery(this).hasClass('extensions-menu-item')) {currentmeta = 'extensions';}
					if (currentmeta != '') {jQuery('#adminsanity-'+currentmeta+'-menu-wrapper').addClass('current-meta');}
				}
			});" . "\n";

			// --- fix hover styles ---
			// (color, background-color)
			// note: ## is used for exact selector match
			$selectors = array(
				'##adminmenu li>a.menu-top:focus',
				'##adminmenu .wp-submenu a:focus',
				'##adminmenu .wp-submenu a:hover',
				'##adminmenu a:hover',
				'##adminmenu li.menu-top:hover',
				'##adminmenu li.opensub>a.menu-top',
				'##adminmenu li.menu-top>a:focus',
				'#adminmenu a:hover, #adminmenu li.menu-top:hover, #adminmenu li.opensub>a.menu-top, #adminmenu li>a.menu-top:focus',
			);
			$js .= 'menuselectors = new Array();';
			foreach ( $selectors as $i => $selector ) {
				// note: do not escape selector
				$js .= "menuselectors[" . esc_js( $i ) . "] = '" . $selector . "'; ";
			}

			// --- loop to add hover events ---
			$js .= "
			for (i in menuselectors) {
				color = adminsanity_get_style(menuselectors[i], 'color', true);
				bgcolor = adminsanity_get_style(menuselectors[i], 'background-color', true);
				if (color || bgcolor) {
					target = menuselectors[i].replace('##','#');
					target = target.replace('#adminmenu', '.adminsanity-menu');
					hoverrules += target + ' {';
					if (color) {hoverrules += 'color:'+color+' !important; -text-fill-color:'+color+' !important; -webkit-text-fill-color:'+color+' !important;';}
					if (bgcolor) {hoverrules += 'background-color:'+bgcolor+' !important;';  }
					hoverrules += '} ';
					if (as_menu_debug) {console.log(color+' on '+bgcolor+' for '+target);}
				}
			}
			as_rules = document.getElementById('adminsanity-extra-styles').innerHTML;
			document.getElementById('adminsanity-extra-styles').innerHTML = as_rules + hoverrules;" . "\n";

			// --- open all menu sections ---
			// TODO: maybe check cookie/user value of first toggle ?
			// if ( !isset( $_COOKIE['adminsanity_first_toggle'] ) || ( '' == $_COOKIE['adminsanity_first_toggle'] ) ) {
				// 0.9.9: loop key/label javascript array instead of PHP
				// foreach ( $labels as $key => $label ) {
				$js .= "for (i = 0; i < as_meta_keys.length; i++) {";
					$js .= "adminsanity_toggle_section(key); " . "\n";
				$js .= "}";
				// }
			// }

			// 1.0.3: move flag set to end of function
			$js .= "as_menu_added = true; as_menu_adding = false;" . "\n";

		// --- close load menu function ---
		$js .= '}' . "\n";

	}

	// Document Ready Script
	// ---------------------
	
	// --- open document ready function ---
	$readyjs = "jQuery(document).ready(function() {" . "\n";

	if ( $meta_menus ) {

		// --- dynamic creation of meta menu ---
		// 0.9.9: loop key/label javascript array instead of PHP
		// foreach ( $labels as $key => $label ) {
		$readyjs .= "for (i = 0; i < as_meta_keys.length; i++) {" . "\n";

			$readyjs .= "key = as_meta_keys[i]; label = as_meta_labels[i];
			listitem = document.createElement('li');
			listitem.setAttribute('id', key+'-meta-menu');
			listitem.setAttribute('class', 'meta-menu meta-menu-open');
			listitem.setAttribute('onclick', 'adminsanity_menu_meta(\"'+key+'\");');
			listitem.setAttribute('style', 'display:none;');
			listitem.innerHTML = label;
			document.getElementById('adminmenu').appendChild(listitem);" . "\n";

			$readyjs .= "if ('content' == key) {
				firstitem = jQuery('#adminmenu .content-menu-item').first();
				jQuery('#adminmenu #content-meta-menu').insertBefore(firstitem).show();
			} else if ('manage' == key) {
				firstmanage = jQuery('#adminmenu .manage-menu-item').first();
				jQuery('#adminmenu #manage-meta-menu').insertBefore(firstmanage).show();
			} else if ( 'extensions' == key) {
				firstextension = jQuery('#adminmenu .extensions-menu-item').first();
				jQuery('#adminmenu #extensions-meta-menu').insertBefore(firstextension).show();
			}" . "\n";

		$readyjs .= "}" . "\n";

		// --- highlight currently active meta menu ---
		$readyjs .= "jQuery('#adminmenu li').each(function() {
			if (jQuery(this).hasClass('wp-has-current-submenu') || jQuery(this).hasClass('current')) {
				currentmeta = '';
				if (jQuery(this).hasClass('content-menu-item')) {
					currentmeta = 'content';
				} else if (jQuery(this).hasClass('manage-menu-item')) {
					currentmeta = 'manage';
					jQuery('#adminmenu li.separator2').addClass('current-meta');
				} else if (jQuery(this).hasClass('extensions-menu-item')) {
					currentmeta = 'extensions';
					jQuery('#adminmenu li.separator-last').addClass('current-meta');
				}
				if ('' != currentmeta) {jQuery('#'+currentmeta+'-meta-menu').addClass('current-meta');}
			}
		});" . "\n";
	}
		
	// --- expand menu button ---
	// 0.9.9: added for expanded full page menu
	if ( $expander ) {

		// --- delayed auto-load of menu ---
		// 1.0.3: remove autoload of expanded menu for speed (loads fine on click)
		// $readyjs .= "setTimeout(adminsanity_menu_load, 1000);" . "\n";

		// --- clone admin menu item  ---
		$readyjs .= "jQuery('#wp-admin-bar-menu-toggle').clone()" . "\n";
		$readyjs .= ".attr('id','wp-admin-bar-expand-toggle').attr('class','admin-bar-extra-menu')" . "\n";
		$readyjs .= ".attr('onclick','adminsanity_menu_toggle();').insertAfter('#wp-admin-bar-menu-toggle');" . "\n";
		$readyjs .= "jQuery('#expand-button').on('mouseover', function() {adminsanity_menu_load();});" . "\n";

		// 1.0.3: test collapse button remains working?
		$readyjs .= "jQuery('#collapse-button').on('click',function(e) {
			console.log(e);
			/* e.preventDefault();
			setTimeout(function() {
				if (jQuery('body').hasClass('folded')) {
					jQuery('body').removeClass('folded');
					jQuery('#collapse-button').attr('aria-expanded','true');
				} else {
					jQuery('body').addClass('folded');
					jQuery('#collapse-button').attr('aria-expanded','false');
				}
			}, 500); */
		});" . "\n";

	}

	// --- close document ready functions ---
	$readyjs .= "});" . "\n";


	// Style Rule Fixes
	// ----------------

	// --- get user colour scheme ---
	// 0.9.9: added get user colour scheme
	$current_user = wp_get_current_user();
	$scheme = $current_user->admin_color;

	// --- for non-default colour schemes ---
	if ( 'fresh' != $scheme ) {

		// arrays of (source, destination)
		$selectors = array();

		// --- meta menu color mapping ---
		// note: ## specifies exact rule match
		if ( $meta_menus ) {
			$selectors[] = array( '##adminmenu a', '#adminmenu .meta-menu' );
			$selectors[] = array(
				// '#adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head, #adminmenu .wp-menu-arrow, #adminmenu .wp-menu-arrow div, #adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, .folded #adminmenu li.current.menu-top, .folded #adminmenu li.wp-has-current-submenu',
				'#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu',
				'#adminmenu .meta-menu.current-meta'
			);
			$selectors[] = array(
				// '#adminmenu, #adminmenu .wp-submenu, #adminmenuback, #adminmenuwrap',
				'#adminmenu, #adminmenuback, #adminmenuwrap', '#adminmenu .separator2.bgfix, #adminmenu .separator-last.bgfix' );
		}

		// --- expander scheme styles ---
		if ( $expander ) {
			// --- expand button ---
			$selectors[] = array( '#collapse-button', '#adminsanity-menu-toggle #expand-button' );
			$selectors[] = array( '#collapse-button:hover', '#adminsanity-menu-toggle:hover span' );
			// --- menu headings ---
			$selectors[] = array(
				// '#adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head, #adminmenu .wp-menu-arrow, #adminmenu .wp-menu-arrow div, #adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, .folded #adminmenu li.current.menu-top, .folded #adminmenu li.wp-has-current-submenu',
				'#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu',
				'.adminsanity-menu-wrapper.current-meta .adminsanity-menu-heading'
			);
			$selectors[] = array( '##adminmenu a', '.adminsanity-menu-heading' );
			$selectors[] = array( '#adminmenu, #adminmenuback, #adminmenuwrap', '.adminsanity-menu-heading' );
		}

		// --- loop selectors ----
		$js .= 'src = new Array(); dest = new Array();';
		foreach ( $selectors as $i => $selector ) {
			// note: do not escape selector names
			$js .= "src[" . esc_js( $i ) . "] = '" . $selector[0] . "'; ";
			$js .= "dest[" . esc_js( $i ) . "] = '" . $selector[1] . "'; ";
		}
		$js .= "for (i = 0; i < src.length; i++) {
			color = adminsanity_get_style(src[i], 'color', true);
			bg = adminsanity_get_style(src[i], 'background', true);
			bgcolor = adminsanity_get_style(src[i], 'background-color', true);
			if (color) {colorrules += dest[i] + ' {color: '+color+';} ';}
			if (bg) {colorrules += dest[i] + ' {background: '+bg+';} ';}
			if (bgcolor) {colorrules += dest[i] + ' {background-color: '+bgcolor+';} ';}
			if (as_menu_debug) {console.log(color+' on '+bgcolor+' ('+bg+') for '+src[i]);}
		}
		as_rules += colorrules; " . "\n";

	}

	// --- insert style rules in sheet ---
	$js .= "document.getElementById('adminsanity-extra-styles').innerHTML = as_rules;" . "\n";

	// --- output extra rules style tag ---
	echo '<style id="adminsanity-extra-styles"></style>';

	// --- filter and output scripts ---
	$js = apply_filters( 'adminsanity_menu_script', $js );
	$readyjs = apply_filters( 'adminsanity_menu_ready_script', $readyjs );
	echo "<script>" . $js . $readyjs . "</script>";
}

// -------------------
// Clone Styles Script
// -------------------
function adminsanity_menu_clone_styles_script() {

/* START getStyleObject Plugin */

/*
* getStyleObject Plugin for jQuery JavaScript Library
* From: http://upshots.org/?p=112
*
* Copyright: Unknown, see source link
* Plugin version by Dakota Schneider (http://hackthetruth.org)
*/

$js = "(function($){
    \$.fn.getStyleObject = function(){
        var dom = this.get(0);
        var style;
        var returns = {};
        if(window.getComputedStyle){
            var camelize = function(a,b){
                return b.toUpperCase();
            }
            style = window.getComputedStyle(dom, null);
            for(var i=0;i<style.length;i++){
                var prop = style[i];
                var camel = prop.replace(/\-([a-z])/g, camelize);
                var val = style.getPropertyValue(prop);
                returns[camel] = val;
            }
            return returns;
        }
        if(dom.currentStyle){
            style = dom.currentStyle;
            for(var prop in style){
                returns[prop] = style[prop];
            }
            return returns;
        }
        return this.css();
    }

    \$.fn.cloneWithCSS = function() {
        styles = {};

        \$this = \$(this);
        \$clone = \$this.clone();
        \$clone.css( \$this.getStyleObject() );

        children = \$this.children().toArray();
        var i = 0;
        while( children.length ) {
            \$child = \$( children.pop() );
            styles[i++] = \$child.getStyleObject();
            \$child.children().each(function(i, el) {
                children.push(el);
            })
        }

        cloneChildren = \$clone.children().toArray()
        var i = 0;
        while( cloneChildren.length ) {
            \$child = \$( cloneChildren.pop() );
            \$child.css( styles[i++] );
            \$child.children().each(function(i, el) {
                cloneChildren.push(el);
            })
        }

        return \$clone;
    }
})(jQuery);";

	/* END getStyleObject jQuery Plugin */

	return $js;
}

// --------------------------------
// Plugin Settings Current Menu Fix
// --------------------------------
// 0.9.9: function prefix name fix
function adminsanity_menu_settings_fix() {

	if ( !isset( $_REQUEST['page'] ) ) {
		return;
	}
	// 1.0.0: use sanitize_title on REQUEST value
	$page = sanitize_title( $_REQUEST['page'] );

	// --- add current menu class to plugin settings menu ---
	// 0.9.9: fix to check selector length
	echo "<script>if (!jQuery('#adminmenu li.current').length) {
		jQuery('#menu-extra-settings ul li').each(function() {
			href = jQuery(this).find('a').attr('href');
			if ( (href != undefined) && (href.indexOf('page=" . esc_js( $page ) . "') > -1) ) {
				jQuery('#menu-settings, #menu-settings a').addClass('wp-not-current-submenu').removeClass('wp-has-current-submenu wp-menu-open');
				jQuery('#menu-extra-settings, #menu-extra-settings a').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
				jQuery('#menu-extra-settings ul li a').removeClass('wp-has-current-submenu wp-menu-open');
				jQuery(this).addClass('current').find('a').addClass('current').attr('aria-current', 'page');
			}
		});
	}</script>";
}

// -------------------------------
// Example User Menu Order (Admin)
// -------------------------------
// 0.9.9: function reprefix and add early priority
// 0.9.9: disable filter (provided for example only)
// add_filter( 'adminsanity_menu_meta_order', 'adminsanity_menu_admin_reorder', 5 );
function adminsanity_menu_admin_reorder( $order ) {

	// --- reorder menu for administrators ---
	$current_user = wp_get_current_user();
	if ( in_array( 'administrator', $current_user->roles ) ) {
		$order = array( 'b', 'a', 'c' );
	}

	return $order;
}

// -------------------------
// Top Menu Positions Filter
// -------------------------
// (array of menu item names to move above megamenus automatically)
add_filter( 'adminsanity_menu_top_positions', 'adminsanity_menu_top_positions_test', 9 );
function adminsanity_menu_top_positions_test( $top ) {

	// 1.0.3: merge in top level items for WordPress.Com
	return array_merge( $top, array( 'https://wordpress.com/sites', site_url() ) );
}

// ---------------------------
// Test Keep Menu Order Filter
// ---------------------------
// (array of menu item names to not to reprder/move automatically)
add_filter( 'adminsanity_menu_keep_positions', 'adminsanity_menu_keep_position_test', 9 );
function adminsanity_menu_keep_position_test( $keep ) {
	
	// note: prototasq slug is used as an example here to maintain first position (for project management)
	return array_merge( $keep, array( 'prototasq' ) );

}

// --- end load wrapper ---
}
