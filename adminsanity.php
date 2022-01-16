<?php

/*
Plugin Name: AdminSanity
Plugin URI: https://wpmedic.tech/adminsanity/
Description: Add Sanity back to your WordPress Admin Area.
Version: 1.0.0
Author: Tony Hayes
Author URI: https://wpmedic.tech
GitHub Plugin URI: majick777/adminsanity
*/

// Development TODOs: add plugin settings loader

// --- define plugin constants ---
define( 'ADMINSANITY_DIR', dirname( __FILE__ ) );
define( 'ADMINSANITY_URL', plugins_url( '', __FILE__ ) );

global $adminsanity;

// --- set module load states ---
// 0.9.8: added load state checking via constants and filters
// 0.9.9: standardize loader constant names
$load = array( 'bar' => true, 'menu' => true, 'notices' => true );

// - bar module setting -
if ( defined( 'ADMINSANITY_LOAD_BAR' ) ) {
	$load['bar'] = ADMINSANITY_LOAD_BAR;
} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
	$load['bar'] = adminsanity_get_setting( 'load_bar' );
} else {
	$load['bar'] = apply_filters( 'adminsanity_load_bar', $load['bar'] );
}

// - menu module setting -
if ( defined( 'ADMINSANITY_LOAD_MENU' ) ) {
	$load['menu'] = ADMINSANITY_LOAD_MENU;
} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
	$load['menu'] = adminsanity_get_setting( 'load_menu' );
} else {
	$load['menu'] = apply_filters( 'adminsanity_load_menu', $load['menu'] );
}

// - notices module setting -
if ( defined( 'ADMINSANITY_LOAD_NOTICES' ) ) {
	$load['notices'] = ADMINSANITY_LOAD_NOTICES;
} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
	$load['notices'] = adminsanity_get_setting( 'load_notices' );
} else {
	$load['notices'] = apply_filters( 'adminsanity_load_notices', $load['notices'] );
}
$adminsanity = array( 'load' => $load );

// --- load plugin modules ---
$modules_loaded = false;
$includes = array( 'bar', 'menu', 'notices' );
foreach ( $includes as $include ) {
	if ( $load[$include] ) {
		$filepath = ADMINSANITY_DIR . '/adminsanity/adminsanity-' . $include . '.php';
		if ( file_exists( $filepath ) ) {
			include $filepath;
		}
	}
}
