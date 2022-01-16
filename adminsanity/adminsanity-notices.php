<?php

// -------------------------------
// === AdminSanity Notices Box ===
// -------------------------------

// Adds an Admin Notices Box with tabbed message type display.

// === AdminSanity Notices ===
// - Get Message Levels
// - Get Message Types
// - Buffer All Notice Output
// - Capture Buffered Output
// - Admin Notices Box
// - Notices Styles
// - Notices Scripts
// === Test Notices ===
// - Trigger Test Notices
// - Test Notice


// ----------------------
// Module Option Settings
// ----------------------

// Filters
// -------
// Filter Setting | Filter                             | Type   | Default
// Notices Levels | adminsanity_notices_message_levels | array  | message levels
// Notices Types  | adminsanity_notices_levels_levels  | array  | message types 
// Module Styles  | adminsanity_notices_styles         | string | plugin css
// Module Scripts | adminsanity_notices_scripts        | string | plugin js


// -------------------------------
// Abort on Negative Load Constant
// -------------------------------
// 0.9.9: standardize loader constant names
if ( defined( 'ADMINSANITY_LOAD_NOTICES' ) && !ADMINSANITY_LOAD_NOTICES ) {
	return;
}

// --- allow for use as an mu plugin ---
// 0.9.9: attempt to prevent double load conflicts
// 1.0.1: use return instead of function wrapper
if ( !function_exists( 'adminsanity_notices_message_levels' ) ) {


// ------------------
// Get Message Levels
// ------------------
function adminsanity_notices_message_levels() {
	$types = array(
		'all'		=> __( 'All', 'adminsanity' ),
		'general'	=> __( 'General', 'adminsanity' ),
		'network'	=> __( 'Network', 'adminsanity' ),
		'admin'		=> __( 'Admin', 'adminsanity' ),
		'user'		=> __( 'User', 'adminsanity' ),
	);
	$types = apply_filters( 'adminsanity_notices_message_levels', $types );
	return $types;
}

// -----------------
// Get Message Types
// -----------------
function adminsanity_notices_message_types() {

	// 0.9.8: add class for notice-success = updated
	// 0.9.8: add classes subarray values
	// 0.9.9: added notice-error message class
	// 0.9.9: added woocmmerce message type
	$mtypes = array(
		'error'			=> array( 'classes' => 'notice-error error', 'label' => __( 'Errors', 'adminsanity' ) ),
		'update-nag'		=> array( 'classes' => 'update-nag', 'label' => __( 'Updates', 'adminsanity' ) ),
		'notice-warning'	=> array( 'classes' => 'notice-warning', 'label' => __( 'Warnings', 'adminsanity' ) ),
		'updated'		=> array( 'classes' => 'notice-success updated', 'label' => __( 'Messages', 'adminsanity' ) ),
		'notice-info'		=> array( 'classes' => 'notice-info', 'label' => __( 'Notices', 'adminsanity' ) ),
		'commerce'		=> array( 'classes' => 'woocommerce-message', 'label' => __( 'Commerce', 'adminsanity') ),
	);
	$mtypes = apply_filters( 'adminsanity_notices_message_types', $mtypes );
	return $mtypes;
}

// ------------------------
// Buffer All Notice Output
// ------------------------
add_action( 'network_admin_notices', 'adminsanity_notices_start', 0 );
add_action( 'user_admin_notices', 'adminsanity_notices_start', 0 );
add_action( 'admin_notices', 'adminsanity_notices_start', 0 );
add_action( 'all_admin_notices', 'adminsanity_notices_start', 0 );
function adminsanity_notices_start() {
	ob_start();
}

// -----------------------
// Capture Buffered Output
// -----------------------
// 0.9.9: standardize function prefixes
add_action( 'network_admin_notices', 'adminsanity_notices_network_end', 999 );
function adminsanity_notices_network_end() {
	global $adminsanity;
	$adminsanity['network_notices'] = ob_get_contents();
	ob_end_clean();
	echo '<div id="admin-top-network-notices"></div>';
}
add_action( 'user_admin_notices', 'adminsanity_notices_user_end', 999 );
function adminsanity_notices_user_end() {
	global $adminsanity;
	$adminsanity['user_notices'] = ob_get_contents();
	ob_end_clean();
	echo '<div id="admin-top-user-notices"></div>';
}

add_action( 'admin_notices', 'adminsanity_notices_admin_end', 999 );
function adminsanity_notices_admin_end() {
	global $adminsanity;
	$adminsanity['admin_notices'] = ob_get_contents();
	ob_end_clean();
	echo '<div id="admin-top-admin-notices"></div>';
}

// -----------------
// Admin Notices Box
// -----------------
add_action( 'all_admin_notices', 'adminsanity_notices_all_end', 999 );
function adminsanity_notices_all_end() {

	global $adminsanity;
	$adminsanity['general_notices'] = ob_get_contents();
	ob_end_clean();

	echo '<div id="admin-top-general-notices"></div>';

	// --- output notice box ---
	// 0.9.8: split menu from notices box (space for Screen Options/Help)
	
	// --- notices menu ---
	// 0.9.9: add initial collapsed class
	echo '<div id="adminsanity-notices-menu" class="postbox collapsed">';

	// --- notices menu title ---
	echo '<h3 class="adminsanity-notices-title" onclick="adminsanity_notices_toggle();">';
		echo '<div id="adminsanity-notices-label">' . esc_html( __( 'Notices', 'adminsanity' ) ) . ' &nbsp;</div>';
		echo '<div id="adminsanity-notices-count" class="adminsanity-notices-count"></div>';
		echo '<div id="adminsanity-notices-arrow">&#9662;</div>';
	echo '</h3>';

	// --- notice type menu ---
	$types = adminsanity_notices_message_levels();	
	foreach ( $types as $type => $label ) {
		echo '<div id="adminsanity-' . esc_attr( $type ) . '-notices-menu" class="adminsanity-notices-menu level" style="display:none;">';
			echo '<a href-"javascript:void(0);" onclick="adminsanity_show_notices(\'' . esc_attr( $type ) . '\',false);">' . esc_html( $label ) . '</a>';
			echo ' <span id="adminsanity-' . esc_attr( $type ) . '-notices-count" class="adminsanity-notices-count"></span>';
		echo '</div>';
	}

	// --- menu spacer ---
	echo '<div class="adminsanity-notices-menu-spacer">';
	echo ' &nbsp; </div>';

	// --- message types ---
	$mtypes = adminsanity_notices_message_types();
	foreach ( $mtypes as $key => $mtype ) {
		echo '<div id="adminsanity-' . esc_attr( $key ) . '-notices-menu" class="adminsanity-notices-menu">';
			echo '<a href-"javascript:void(0);" onclick="adminsanity_show_types(\'' . esc_attr( $key ) . '\');">' . esc_html( $mtype['label'] ) . '</a>';
			echo ' <span id="adminsanity-' . esc_attr( $key ) . '-notices-count" class="adminsanity-notices-count"></span>';
		echo '</div>';
	}
	
	// --- close notices menu ---
	echo '</div>'; 
	
	// 0.9.9: added ID for menu module full page integration
	echo '<div id="adminsanity-notices-clear" class="clear"></div>';

	// --- notices wrap ---
	// 0.9.9: remove postbox class
	echo '<div id="adminsanity-notices-wrap" style="display:none;">';
	echo '<h1 style="display:none;"></h1><h2 style="display:none;"></h2>';

	foreach ( $types as $type => $label ) {
		if ( 'all' != $type ) {
			echo '<div id="adminsanity-' . esc_attr( $type ) . '-notices" class="adminsanity-notices">';
			if ( isset( $adminsanity[$type . '_notices'] ) ) {
				// phpcs:ignore WordPress.Security.OutputNotEscaped
				echo $adminsanity[$type . '_notices'];
			}
			echo '</div>';
		}
	}
	
	// --- close notices wrap ---
	echo '</div>'; 

	// 0.9.9: added extra clearfix for floats
	echo '<div class="adminsanity-notices-clear"></div>';
}

// --------------
// Notices Styles
// --------------
add_action( 'admin_print_styles', 'adminsanity_notices_styles' );
function adminsanity_notices_styles() {

	// TODO: get count bubble styles for colour scheme ?

	// --- admin notices box styles ---
	// 0.9.8: add style class for expanded/collapsed 
	// 0.9.9: style admin notices counts and alignment
	// 0.9.9: improve specificity for h3 adminsanity-notices-title
	// 0.9.9: added commerce message type tab styles
	// 0.9.9: style/float notices box like screen options/help
	$css = "#adminsanity-notices-menu {float: left; margin-bottom: 0; min-width: auto; border-radius: 0 0 4px 4px;}
	#wpbody h3.adminsanity-notices-title {
	    cursor:pointer; margin: 0; padding: 2px 14px; display: inline-block; vertical-align:top;
	    color: #72777c; font-size: 14px; font-weight: normal;}
	#wpbody h3.adminsanity-notices-title:hover {color: #32373c;}
	#adminsanity-notices-label, #adminsanity-notices-count, #adminsanity-notices-arrow {display: inline-block; vertical-align: middle;}
	#adminsanity-notices-arrow {font-size: 24px; line-height: 28px; color: #72777c; margin-top: -3px;}
	#adminsanity-notices-menu.expanded #adminsanity-notices-arrow {margin-bottom: 3px;}
	.adminsanity-notices-menu {display: none; margin: 2px 7px 0px 7px; padding: 0 1px 4px 1px; min-width: 65px; 
	    text-align: center; font-size: 12px; line-height: 16px; border-left: 1px solid transparent; border-right: 1px solid transparent;}
	.adminsanity-notices-menu.level {min-width: 45px; margin-top: 0px; padding-top: 4px;}
	.adminsanity-notices-menu.active {background-color: #F7F7F7; border-left: 1px solid #ccc; border-right: 1px solid #ccc;}
	.adminsanity-notices-menu.level.active {border-top: 1px solid #ccc;}
	.adminsanity-notices-menu:hover {background-color: #FAFAFA;}
	.adminsanity-notices-menu a {cursor: pointer; vertical-align: middle;}
	.adminsanity-notices-menu-spacer {display: none; width: 10px;}
	#adminsanity-error-notices-menu {border-top: 3px solid #DC3232;}
	#adminsanity-update-nag-notices-menu {border-top: 3px solid #FFBA00;}
	#adminsanity-notice-warning-notices-menu {border-top: 3px solid #FFE900;}
	#adminsanity-updated-notices-menu {border-top: 3px solid #46B450;}
	#adminsanity-notice-info-notices-menu {border-top: 3px solid #00A0D2;}
	#adminsanity-commerce-notices-menu {border-top: 3px solid #CC99C2;}
	#adminsanity-notices-wrap {width: 98%; overflow: hidden; clear: both;}
	#adminsanity-notices-wrap div.update-nag {margin-top: 10px;}
	#adminsanity-notices-wrap div.notice-warning {border-left: 4px solid #FFE900;}
	#adminsanity-notices-wrap .adminsanity-notices > div {display: block; margin: 10px 0 10px 0; outline: 1px solid #EEEEEE;}
	.adminsanity-notices-count {
	    display: none; vertical-align: top; margin: 1px 0 0 2px; padding: 0 5px;
	    min-width: 7px; height: 17px; border-radius: 11px; background-color: #ca4a1f;
	    color: #fff; font-size: 9px; line-height: 17px; text-align: center; z-index: 26;}
	#adminsanity-notices-label, #adminsanity-notices-count {vertical-align: middle;}
	#adminsanity-notices-count {font-size: 12px; line-height: 20px; height: 20px; padding: 0 7px;}
	adminsanity-notices-clear {float: left; width: 100%; clear: both; display: block;}";

	// --- filter and output ---
	$css = apply_filters( 'adminsanity_notices_styles', $css );
	echo "<style>" . $css . "</style>";
}

// ---------------
// Notices Scripts
// ---------------
add_action( 'admin_footer', 'adminsanity_notices_scripts' );
function adminsanity_notices_scripts() {

	$js = '';

	// 0.9.9: set notices debug mode
	/* $valid = false;
	if ( isset( $_GET['as-debug'] ) ) {
		$debug = sanitize_title( $_GET['as-debug'] );
		if ( in_array( $debug, array( 'all', 'notices' ) ) ) {
			$js .= "as_notices_debug = true; ";
			$valid = true;
		}
	}
	if ( !$valid ) {
		$js .= "as_notices_debug = false; ";
	} */
	
	// --- get notice levels and types ---
	$types = adminsanity_notices_message_levels();
	$mtypes = adminsanity_notices_message_types();

	// --- admin notices box script ---
	// 0.9.8: allow multiple class selectors instead of just key
	$selectors = $unselectors = array();
	foreach ( $mtypes as $key => $mtype ) {		
		$classes = explode( " ", $mtype['classes'] );
		foreach ( $classes as $class ) {
			$class = trim( $class );
			if ( '' != $class ) {
				$selectors[] = "#adminsanity-notices-wrap div." . $class;
				$unselectors[] = "div." . $class;
			}
		}
	}
	
	// 0.9.9: prefix admin notice variables
	$js .= "var as_notices_selector = '" . implode( ',', $selectors ) . "';
	as_notices_count = 0; as_notice_type_count = 0; as_notices_height = 0;" . PHP_EOL;
	$i = 0; $js .= "var as_notice_levels = new Array(); ";
	foreach ( $types as $key => $type ) {
		$js .= "as_notice_levels[" . $i . "] = '" . $key . "'; "; $i++;
	}	
	// 0.9.9: set class lists by key
	$js .= "var as_notice_types = new Array(); as_notice_classes = new Array();";
	$i = 0;
	foreach ( $mtypes as $key => $type ) {
		$js .= "as_notice_types['" . $i . "'] = '" . $key . "'; ";
		$js .= "as_notice_classes['" . $i . "'] = '" . $type['classes'] . "'; ";
		$i++;
	}
	$js .= PHP_EOL;

	// --- move out naughty non-notices printed in admin notices ---
	foreach ( $types as $type => $label ) {
		if ( 'all' != $type ) {		
			// 0.9.9: use unselector class list
			$js .= "jQuery('#adminsanity-" . esc_attr( $type ) . "-notices').children()";
			// $js .= ".not('div.update-nag, div.updated, div.error, div.notice-info, div.notice-warning, div.notice-success, div.woocommerce-message')";
			$js .= ".not('" . implode(',', $unselectors ) . "')";
			$js .= ".insertAfter(jQuery('#admin-top-" . esc_attr( $type ) . "-notices'));" . PHP_EOL;
		}
	}

	// 0.9.8: fix for extra notice-success class
	// 0.9.9: fix to handle multiple classes dynamically
	$js .= "for (i = 0; i < as_notice_levels.length; i++) {
		if (as_notice_levels[i] != 'all') {
			count = jQuery('#adminsanity-'+as_notice_levels[i]+'-notices').children('div').length;
			if (count > 0) {
				jQuery('#adminsanity-'+as_notice_levels[i]+'-notices-count').html(count).css('display','inline-block');
				as_notice_type_count++; as_notices_count = as_notices_count + count;
			} else {jQuery('#adminsanity-'+as_notice_levels[i]+'-notices-menu').remove();}
		}
	}
	if (as_notices_count == 0) {jQuery('#adminsanity-notices-box').remove();}
	else {
		jQuery('#adminsanity-notices-count').html(as_notices_count).css('display','inline-block');
		jQuery('.adminsanity-notices-menu-spacer').css('display','inline-block');
		if (as_notice_type_count < 2) {jQuery('#adminsanity-all-notices-menu').remove();}
		else {jQuery('#adminsanity-all-notices-count').html(as_notices_count).css('display','inline-block');}
		for (i = 0; i < as_notice_types.length; i++) {
			if (as_notice_classes[i].indexOf(' ') > -1) {classes = as_notice_classes[i].split(' ');}
			else {classes = new Array; classes[0] = as_notice_types[i];}
			count = 0;
			for (j = 0; j < classes.length; j++) {
				count = count + jQuery('#adminsanity-notices-wrap div.'+classes[j]).length;
			}
			if (count > 0) {
				jQuery('#adminsanity-'+as_notice_types[i]+'-notices-menu').css('display','inline-block');
				jQuery('#adminsanity-'+as_notice_types[i]+'-notices-count').html(count).css('display','inline-block');
			} else {jQuery('#adminsanity-'+as_notice_types[i]+'-notices-menu').remove();}
		}
	}
	function adminsanity_notices_toggle() {
		if (jQuery('#adminsanity-notices-menu').hasClass('expanded')) {
			jQuery('#adminsanity-notices-menu').removeClass('expanded').addClass('collapsed');
			jQuery('#adminsanity-notices-menu').css('float','right');
			jQuery('#adminsanity-notices-count').show();
			jQuery('#adminsanity-notices-wrap').animate({'height':'0px'},750);
			as_notices_height = 0;
			jQuery('#adminsanity-notices-arrow').html('&#9662;');
			jQuery('.adminsanity-notices-menu, .adminsanity-notices-menu-spacer').hide();
		} else {
			jQuery('#adminsanity-notices-menu').removeClass('collapsed').addClass('expanded');
			jQuery('#adminsanity-notices-menu').css('float','left');
			jQuery('#adminsanity-notices-count').hide();
			adminsanity_notices_height(1500);
			jQuery('#adminsanity-notices-arrow').html('&#9652;');
			jQuery('.adminsanity-notices-menu, .adminsanity-notices-menu-spacer').css('display','inline-block');
		}
	}
	function adminsanity_notices_height(speed) {
		height = jQuery('#adminsanity-notices-wrap').show().css('height','auto').height();
		jQuery('#adminsanity-notices-wrap').css('height',as_notices_height);
		jQuery('#adminsanity-notices-wrap').animate({'height':height},speed);
		as_notices_height = height;
	}
	function adminsanity_notices_show() {
		jQuery('#adminsanity-notices-wrap').show();
		jQuery('#adminsanity-notices-arrow').html('&#9652;');
	}
	function adminsanity_show_notices(level,reset) {
		if (jQuery('#adminsanity-'+level+'-notices-menu').hasClass('active')) {
			jQuery('#adminsanity-notices-arrow').html('&#9662;'); level = 'none';
		}
		jQuery('.adminsanity-notices-menu').removeClass('active');
		jQuery(as_notices_selector).show();
		for (i = 0; i < as_notice_levels.length; i++) {
			if (jQuery('#adminsanity-'+as_notice_levels[i]+'-notices')) {
				if (level == 'all') {jQuery('#adminsanity-'+as_notice_levels[i]+'-notices').show();}
				else {jQuery('#adminsanity-'+as_notice_levels[i]+'-notices').hide();}
			}
		}
		if (level == 'all') {jQuery('#adminsanity-all-notices-menu').addClass('active');}
		else if (level != 'none' ) {
			jQuery('#adminsanity-'+level+'-notices').show();
			jQuery('#adminsanity-'+level+'-notices-menu').addClass('active');
		}
		if (!reset) {adminsanity_notices_height(750);}
	}
	function adminsanity_show_types(type) {
		if (jQuery('#adminsanity-'+type+'-notices-menu').hasClass('active')) {
			jQuery('#adminsanity-notices-arrow').html('&#9662;');
			adminsanity_show_notices('none'); return;
		}
		adminsanity_notices_show(); adminsanity_show_notices('all',true); jQuery(as_notices_selector).hide();
		for (i = 0; i < as_notice_types.length; i++) {
			if (as_notice_types[i] == type) {
				if (as_notice_classes[i].indexOf(' ') > -1) {classes = as_notice_classes[i].split(' ');}
				else {classes = new Array(); classes[0] = as_notice_classes[i];}
			}
		}
		for (i = 0; i < classes.length; i++) {jQuery('#adminsanity-notices-wrap div.'+classes[i]).show();}
		jQuery('.adminsanity-notices-menu').removeClass('active');
		jQuery('#adminsanity-'+type+'-notices-menu').addClass('active');
		adminsanity_notices_height(750);
	}" . PHP_EOL;
	

	// note: this is from /wp-admin/js/common.js... to move the notices to below first h1/h2
	// jQuery( 'div.updated, div.error, div.notice' ).not( '.inline, .below-h2' ).insertAfter( jQuery( '.wrap h1, .wrap h2' ).first() ); }

	// --- prevent notices from being moved by common.js ---
	$js .= "jQuery(as_notices_selector).not('.inline').addClass('below-h2').addClass('noticetemp');";

	// --- remove the below-h2 class after document loaded ---		
	$js .= "jQuery(document).ready(function() { setTimeout(function() {		
		jQuery('div.update-nag, div.updated, div.error, div.notice-success, div.notice-info, div.notice-warning').each(function() {
			if (jQuery(this).hasClass('noticetemp')) {jQuery(this).removeClass('below-h2').removeClass('noticetemp');}
		});		
	}, 1000); });";

	// --- filter and output ---
	$js = apply_filters( 'adminsanity_notices_scripts', $js );	
	echo "<script>" . $js . "</script>";
}


// --------------------
// === Test Notices ===
// --------------------

// --------------------
// Trigger Test Notices
// --------------------
add_action( 'admin_init', 'adminsanity_notices_test' );
function adminsanity_notices_test() {
	if ( !isset( $_GET['test'] ) || ( 'as-notices' != $_GET['test'] ) ) {
		return;
	}
	add_action( 'network_admin_notices', 'adminsanity_notice_test' );
	add_action( 'user_admin_notices', 'adminsanity_notice_test' );
	add_action( 'user_admin_notices', 'adminsanity_notice_test', 11 );
	add_action( 'admin_notices', 'adminsanity_notice_test' );
	add_action( 'admin_notices', 'adminsanity_notice_test', 11 );
	add_action( 'admin_notices', 'adminsanity_notice_test', 12 );
	add_action( 'all_admin_notices', 'adminsanity_notice_test' );
	add_action( 'all_admin_notices', 'adminsanity_notice_test', 11 );
	add_action( 'all_admin_notices', 'adminsanity_notice_test', 12 );
}

// -----------
// Test Notice
// -----------
function adminsanity_notice_test() {
	global $adminsanity;
	if ( !isset( $adminsanity['test-count'] ) ) {$adminsanity['test-count'] = 0;}
	$adminsanity['test-count']++;
	
	if ( $adminsanity['test-count'] == 1 ) {$class = 'error';}
	elseif ( $adminsanity['test-count'] == 2 ) {$class = 'notice-error';}
	elseif ( $adminsanity['test-count'] == 3 ) {$class = 'update-nag';}
	elseif ( $adminsanity['test-count'] == 4 ) {$class = 'notice notice-warning';}
	elseif ( $adminsanity['test-count'] == 5 ) {$class = 'updated';}
	elseif ( $adminsanity['test-count'] == 5 ) {$class = 'notice notice-info';}
	elseif ( $adminsanity['test-count'] == 7 ) {$class = 'notice notice-success';}
	
	echo '<div class="' . esc_attr( $class ) . '">';
	echo 'This is a Test Notice with class "' . esc_attr( $class ) . '".';
	echo '</div>';
}

// --- end function load wrapper ---
}

