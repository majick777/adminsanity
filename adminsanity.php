<?php

/*
Plugin Name: AdminSanity
Plugin URI: https://wpmedic.tech/adminsanity/
Description: Add Sanity back to your WordPress Admin Area.
Version: 1.0.4
Author: Tony Hayes
Author URI: https://wpmedic.tech
GitHub Plugin URI: majick777/adminsanity
*/


// --- define plugin constants ---
// 1.0.1: added extra plugin constants
define( 'ADMINSANITY_DIR', __DIR__ );
define( 'ADMINSANITY_FILE', __FILE__ );
define( 'ADMINSANITY_URL', plugins_url( '', __FILE__ ) );
define( 'ADMINSANITY_BASENAME', plugin_basename( __FILE__ ) );
define( 'ADMINSANITY_HOME_URL', 'https://wpmedic.tech/adminsanity/' );

global $adminsanity, $adminsanity_data;

// --- set plugin options ---
// 1.0.1: added plugin options for settings loader
$options = array(

	// --- notices module ---
	'load_notices' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Admin Notices Module', 'adminsanity' ),
		'helper'  => __( 'Load AdminSanity Notices module to reduce your Admin Notices clutter.', 'adminsanity' ),
		'section' => 'modules',
	),	

	// --- menu module ---
	'load_menu' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Admin Menu Module', 'adminsanity' ),
		'helper'  => __( 'Load AdminSanity Menu module to sort and improve your Admin Menu.', 'adminsanity' ),
		'section' => 'modules',
	),

	// --- bar module ---
	'load_bar' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Admin Bar Module', 'adminsanity' ),
		'helper'  => __( 'Load AdminSanity Bar module to improve your Admin Bar useability.', 'adminsanity' ),
		'section' => 'modules',
	),

	// === Menu Options ===

	// --- meta menus ---
	'menu_metas' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Add Meta Menus', 'adminsanity' ),
		'helper'  => __( 'Enable the addition of meta menu headings to the Admin Menu.', 'adminsanity' ),
		'section' => 'menu',
	),
	
	// --- plugins submenu ---
	'menu_plugins' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Plugin Settings Menu', 'adminsanity' ),
		'helper'  => __( 'Group non-core settings in new Plugin Settings menu item.', 'adminsanity' ),
		'section' => 'menu',
	),
	
	// --- menu expander ---
	'menu_expander' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Menu Expander Icon', 'adminsanity' ),
		'helper'  => __( 'Enable menu expander icon to display fully expanded menus and submenus.', 'adminsanity' ),
		'section' => 'menu',
	),

	// === Bar Options ===

	// --- bar frontend ---
	// 1.0.4: fix to bar frontend settings key
	'bar_frontend' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Bar Frontend', 'adminsanity' ),
		'helper'  => __( 'Enable load of Admin Bar module on frontend pages not just in admin area.', 'adminsanity' ),
		'section' => 'bar',
	),
	
	// --- bar cycler ---
	'bar_cycler' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Bar Cycler Icon', 'adminsanity' ),
		'helper'  => __( 'Enable bar cycler icon to cycle display of core and plugin Admin Bar items.', 'adminsanity' ),
		'section' => 'bar',
	),

	// --- bar dropdown ---
	'bar_dropdown' => array(
		'type'    => 'checkbox',
		'default' => 'yes',
		'value'   => 'yes',
		'label'   => __( 'Bar Dropdown Icon', 'adminsanity' ),
		'helper'  => __( 'Enable bar dropdown icon to display fully expanded admin bar items.', 'adminsanity' ),
		'section' => 'bar',
	),

	// --- section titles ---
	'sections' => array(
		'modules' => __( 'Modules', 'adminsanity' ),
		'menu'    => __( 'Admin Menu', 'adminsanity' ),
		'bar'     => __( 'Admin Bar', 'adminsanity' ),
	),

);
$adminsanity_data['options'] = $options;

// --- plugin loader settings ---
// 1.0.1: added plugin loader settings
$settings = array(

	// --- Plugin Info ---
	'slug'         => 'adminsanity',
	'file'         => __FILE__,
	'version'      => '0.0.1',

	// --- Menus and Links ---
	'title'        => 'AdminSanity',
	// 'parentmenu'   => 'adminsanity',
	'home'         => ADMINSANITY_HOME_URL,
	'docs'         => ADMINSANITY_HOME_URL,
	'support'      => 'https://github.com/majick777/adminsanity/issues/',
	'ratetext'     => __( 'Rate on WordPress.org', 'adminsanity' ),
	'share'        => ADMINSANITY_HOME_URL . '#share',
	'sharetext'    => __( 'Share the Plugin Love', 'adminsanity' ),
	'donate'       => 'https://patreon.com/wpmedic',
	'donatetext'   => __( 'Support this Plugin', 'adminsanity' ),
	'readme'       => false,
	'author'       => 'WP Medic',
	'author_url'   => 'https://wpmedic.tech/',

	// --- Options ---
	'namespace'    => 'adminsanity',
	'settings'     => 'as',
	'option'       => 'adminsanity',
	'options'      => $options,

	// --- WordPress.Org ---
	'wporgslug'    => 'adminsanity',
	'wporg'        => true,
	'textdomain'   => 'adminsanity',


);
$adminsanity_data['settings'] = $settings;

// --- include and init loader ---
// 1.0.1: added plugin panel loader class
$loader = ADMINSANITY_DIR . '/loader.php';
if ( file_exists( $loader ) ) {
	include $loader;
	$instance = new adminsanity_loader( $settings );
}

// --- load plugin modules ---
add_action( 'plugins_loaded', 'adminsanity_load_modules' );
function adminsanity_load_modules() {
	
	global $adminsanity_data;

	// --- set module load states ---
	// 0.9.8: added load state checking via constants and filters
	// 0.9.9: standardize loader constant names
	$load = array( 'bar' => true, 'menu' => true, 'notices' => true );

	// --- bar module setting ---
	if ( defined( 'ADMINSANITY_LOAD_BAR' ) ) {
		$load['bar'] = ADMINSANITY_LOAD_BAR;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$load['bar'] = adminsanity_get_setting( 'load_bar' );
	} else {
		$load['bar'] = apply_filters( 'adminsanity_load_bar', $load['bar'] );
	}

	// --- menu module setting ---
	if ( defined( 'ADMINSANITY_LOAD_MENU' ) ) {
		$load['menu'] = ADMINSANITY_LOAD_MENU;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$load['menu'] = adminsanity_get_setting( 'load_menu' );
	} else {
		$load['menu'] = apply_filters( 'adminsanity_load_menu', $load['menu'] );
	}

	// --- notices module setting ---
	if ( defined( 'ADMINSANITY_LOAD_NOTICES' ) ) {
		$load['notices'] = ADMINSANITY_LOAD_NOTICES;
	} elseif ( function_exists( 'adminsanity_get_setting' ) ) {
		$load['notices'] = adminsanity_get_setting( 'load_notices' );
	} else {
		$load['notices'] = apply_filters( 'adminsanity_load_notices', $load['notices'] );
	}
	$adminsanity_data = array( 'load' => $load );
	
	// --- include loaded module files ---
	$includes = array( 'bar', 'menu', 'notices' );
	foreach ( $includes as $include ) {
		if ( $load[$include] ) {
			$filepath = ADMINSANITY_DIR . '/adminsanity/adminsanity-' . $include . '.php';
			if ( file_exists( $filepath ) ) {
				include $filepath;
			}
		}
	}
}

