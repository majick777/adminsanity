<?php

// ------------------------------
// === AdminSanity Admin Menu ===
// ------------------------------

// Automatically splits WordPress Admin Menus into 3 sections:
// A = Dashboard and Post Type Menus
// B = All Other Default WordPress Menus
// C = Any Other Added Menu Items

// Use adminsanity_menu_keep_positions filter to maintain specific menu positions
// (see example filter usage at end)


// ------------------------
// Store Default Admin Menu
// ------------------------
add_action( '_network_admin_menu', 'adminsanity_menu_store_default', 0 );
add_action( '_user_admin_menu', 'adminsanity_menu_store_default', 0 );
add_action( '_admin_menu', 'adminsanity_menu_store_default', 0 );
function adminsanity_menu_store_default() {
	global $menu, $adminsanity; 
	$adminsanity['default_menu'] = $menu;
}

// ------------------------
// Ordered Split Admin Menu
// ------------------------
add_action( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', 'adminsanity_menu_split_order', 20 );
function adminsanity_menu_split_order( $menu_order ) {

  global $menu, $adminsanity;

	// --- set empty split menu arrays ---
	$menua = $menub = $menuc = array();

	// --- loop the menu items ---
	foreach ( $menu as $i => $item ) {
		$found = false;
		foreach ( $adminsanity['default_menu'] as $j => $menu_item ) {
			if ( $item[2] == $menu_item[2] ) {$found = true;}
		}
		if ( ( $item[2] == 'index.php' )
		  || ( strpos( $item[2], 'edit.php' ) === 0 ) ) {$menua[$i] = $item[2];}
		elseif ( !$found ) {$menuc[$i] = $item[2];}
		elseif ( $item[2] != 'separator1' ) {$menub[$i] = $item[2];}
	}
	$menua[] = 'separator1';

	// --- filter menu items whose position to keep ---
	$keep = apply_filters( 'adminsanity_menu_keep_positions', array() );

	// --- exception - move Settings item to top of section! (after separator 2) ---
	if ( !in_array( 'options-general.php', $keep ) ) {
		$menub2 = array(); $found = false;
		foreach ( $menub as $i => $itemname ) {
			if ( $found ) {$i++;}
			if ( $itemname != 'options-general.php' ) {$menub2[$i] = $itemname;}
			if ( $itemname == 'separator2' ) {
				$menub2[($i+1)] = 'options-general.php'; $found = true;
			}
		}
		$menub = $menub2;
	}

	// --- get the menu items whose positions to keep ---
	if ( count( $keep ) > 0 ) {
		$sep = 0; $menua_keep = $menub_keep = $menuc_keep = array();
		foreach ( $menu as $i => $item ) {
			if ( $item[2] == 'separator1' ) {$sep = 1;}
			elseif ( $item[2] == 'separator2' ) {$sep = 2;}
			elseif ( $item[2] == 'separator-last' ) {break;}
			elseif ( in_array( $item[2], $keep ) ) {
				if ($sep == 0) {
					$menua_keep[$i] = $item[2];
					if ( in_array( $item[2], $menub ) ) {$key = array_search($item[2], $menub); unset($menub[$key]);}
					if ( in_array( $item[2], $menuc ) ) {$key = array_search($item[2], $menuc); unset($menuc[$key]);}
				} elseif ($sep == 1) {
					$menub_keep[$i] = $item[2];
					if ( in_array( $item[2], $menua ) ) {$key = array_search($item[2], $menua); unset($menua[$key]);}
					if ( in_array( $item[2], $menuc ) ) {$key = array_search($item[2], $menuc); unset($menuc[$key]);}
				} elseif ($sep == 2) {
					$menuc_keep[$i] = $item[2];
					if ( in_array( $item[2], $menua ) ) {$key = array_search($item[2], $menua); unset($menua[$key]);}
					if ( in_array( $item[2], $menub ) ) {$key = array_search($item[2], $menub); unset($menub[$key]);}
				}
			}
		}
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

	// --- resort split menus and merge to final menu order ---
	ksort($menua); ksort($menub); ksort($menuc);
	$menu_order = array_merge( $menua, $menub, $menuc );
	return $menu_order;
}

// -------------------------
// Split Menu Separator Line
// -------------------------
add_action( 'admin_print_styles', 'adminsanity_menu_styles');
function adminsanity_menu_styles() {
	echo "<style>#adminmenu {margin-top: 0;}
	#adminmenu li.wp-menu-separator {border-bottom: 1px solid #F1F1F1;}</style>";
}

// ---------------------------
// Test Keep Menu Order Filter
// ---------------------------
// (array of menu item names not to move automatically)
add_filter( 'adminsanity_menu_keep_positions', 'adminsanity_menu_keep_position_test' );
function adminsanity_menu_keep_position_test( $keep ) {
	return array_merge( $keep, array( 'prototasq' ) );
}
