<?php

// -------------------------------
// === AdminSanity Notices Box ===
// -------------------------------

// ------------------------------
// Buffer All Admin Notice Output
// ------------------------------
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
add_action( 'network_admin_notices', 'adminsanity_network_notices_end', 999 );
function adminsanity_network_notices_end() {
	global $adminsanity;
	$adminsanity['network_notices'] = ob_get_contents();
	ob_end_clean();
	echo '<div id="admin-top-network-notices"></div>';
}
add_action( 'user_admin_notices', 'adminsanity_user_notices_end', 999 );
function adminsanity_user_notices_end() {
	global $adminsanity;
	$adminsanity['user_notices'] = ob_get_contents();
	ob_end_clean();
	echo '<div id="admin-top-user-notices"></div>';
}

add_action( 'admin_notices', 'adminsanity_admin_notices_end', 999 );
function adminsanity_admin_notices_end() {
	global $adminsanity;
	$adminsanity['admin_notices'] = ob_get_contents();
	ob_end_clean();
	echo '<div id="admin-top-admin-notices"></div>';
}

// ----------------
// Admin Notice Box
// ----------------
add_action( 'all_admin_notices', 'adminsanity_all_notices_end', 999 );
function adminsanity_all_notices_end() {

	global $adminsanity;
	$adminsanity['general_notices'] = ob_get_contents();
	ob_end_clean();

	echo '<div id="admin-top-general-notices"></div>';

	// --- output notice box ---
	echo '<div style="width:98%" id="adminsanity-notices-box" class="postbox">';
	echo '<h3 class="adminsanity-notices-title" onclick="adminsanity_notices_toggle();">';
	echo '<span id="adminsanity-notices-arrow">&#9656;</span>';
	echo ' &nbsp; ' . esc_html( __( 'Notices' ) ) . ' &nbsp; ';
	echo '<span id="adminsanity-all-notices-count" class="adminsanity-notices-count"></span>';
	echo '</h3>';

	// --- notice type menu ---
	$types = array(
		'all'		=> __( 'All' ),
		'general'	=> __( 'General' ),
		'network'	=> __( 'Network' ),
		'admin'		=> __( 'Admin' ),
		'user'		=> __( 'User' ),
	);
	foreach ( $types as $type => $label ) {
		echo '<div id="adminsanity-' . esc_attr( $type ) . '-notices-menu" class="adminsanity-notices-menu" style="display:none;">';
			echo '<a href-"javascript:void(0);" onclick="adminsanity_show_notices(\'' . esc_attr( $type ) . '\');">' . esc_html( $label ) . '</a>';
			if ( 'all' != $type ) {
				echo ' (<span id="adminsanity-' . esc_attr( $type ) . '-notices-count" class="adminsanity-notices-count"></span>)';
			}
		echo '</div>';
	}
	
	// --- menu spacer ---
	echo '<div class="adminsanity-notices-menu-spacer">';
	echo ' &nbsp; </div>';
	
	// --- message types ---
	$mtypes = array(
		'error'				=> __( 'Errors' ),
		'update-nag'		=> __( 'Updates' ),
		'notice-warning'	=> __( 'Warnings' ),
		'updated'			=> __( 'Messages' ),
		'notice-info'		=> __( 'Notices' ),
	);	
	foreach ( $mtypes as $mtype => $label ) {
		echo '<div id="adminsanity-' . esc_attr( $mtype ) . '-notices-menu" class="adminsanity-notices-menu">'; // style="display:none;"
			echo '<a href-"javascript:void(0);" onclick="adminsanity_show_types(\'' . esc_attr( $mtype ) . '\');">' . esc_html( $label ) . '</a>';
			echo ' (<span id="adminsanity-' . esc_attr( $mtype ) . '-notices-count" class="adminsanity-notices-count"></span>)';
		echo '</div>';
	}
	
	// --- notices wrap ---
	echo '<div id="adminsanity-notices-wrap" style="display:none";>';
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

	echo '</div>'; // close notices wrap
	echo '</div>'; // close notices box

	// --- admin notices box styles ---
	echo "<style>#adminsanity-notices-arrow {font-size: 24px; vertical-align: -3px;}
	.adminsanity-notices-title {cursor:pointer; margin: 7px 14px; font-size: 16px; display: inline-block; vertical-align:top;}
	.adminsanity-notices-menu {display: inline-block; margin: 6px; padding: 6px; border-radius: 0 0 3px 3px;}
	.adminsanity-notices-menu.active {background-color: #F7F7F7;}
	.adminsanity-notices-menu:hover {background-color: #FAFAFA;}
	.adminsanity-notices-menu a {cursor: pointer;}
	.adminsanity-notices-menu-spacer {display: inline-block; width: 50px;}
	#adminsanity-error-notices-menu {border-top: 3px solid #DC3232;}
	#adminsanity-update-nag-notices-menu {border-top: 3px solid #FFBA00;}
	#adminsanity-notice-warning-notices-menu {border-top: 3px solid #FFE900;}
	#adminsanity-updated-notices-menu {border-top: 3px solid #46B450;}
	#adminsanity-notice-info-notices-menu {border-top: 3px solid #00A0D2;}
	#adminsanity-notices-wrap {border-top: 1px solid #DDDDDD;}
	#adminsanity-notices-wrap div.update-nag {margin-top: 10px;}
	#adminsanity-notices-wrap div.notice-warning {border-left: 4px solid #FFE900;}
	#adminsanity-notices-wrap .adminsanity-notices > div {display: block; margin: 10px 0 10px 0; outline: 1px solid #EEEEEE;}
	</style>";

	// --- admin notices box script ---
	$selectors = array();
	foreach ( $mtypes as $type => $label ) {$selectors[] = "#adminsanity-notices-wrap div." . $type;}
	echo "<script>var adminnoticesselector = '" . implode( ',', $selectors ) . "';
	noticescount = noticetypecount = 0;
	var adminnoticetypes = new Array();
	adminnoticetypes[0] = 'general'; adminnoticetypes[1] = 'network';
	adminnoticetypes[2] = 'user'; adminnoticetypes[3] = 'admin';
	var adminmessagetypes = new Array(); adminmessagetypes[0] = 'update-nag'; 
	adminmessagetypes[1] = 'updated'; adminmessagetypes[2] = 'error'; 
	adminmessagetypes[3] = 'notice-info'; adminmessagetypes[4] = 'notice-warning';"; 

	// --- move out naughty non-notices printed in admin notices ---
	foreach ( $types as $type => $label ) {
		if ( 'all' != $type ) {
			// echo "console.log(jQuery('#adminsanity-" . esc_attr( $type ) . "-notices').children()";
			// echo ".not('div.update-nag, div.updated, div.error, div.notice-info'));";
			echo "jQuery('#adminsanity-" . esc_attr( $type ) . "-notices').children()";
			echo ".not('div.update-nag, div.updated, div.error, div.notice-info, div.notice-warning')";
			echo ".insertAfter(jQuery('#admin-top-" . esc_attr( $type ) . "-notices'));";
		}
	}

	echo "for (i in adminnoticetypes) {
		count = jQuery('#adminsanity-'+adminnoticetypes[i]+'-notices').children('div').length;
		if (count > 0) {
			jQuery('#adminsanity-'+adminnoticetypes[i]+'-notices-count').html(count);
			noticetypecount++; noticescount += count;
		} else {jQuery('#adminsanity-'+adminnoticetypes[i]+'-notices-menu').remove();}
	}
	if (noticescount == 0) {jQuery('#adminsanity-notices-box').remove();}
	else {
		jQuery('#adminsanity-all-notices-count').html('('+noticescount+')');
		if (noticetypecount == 1) {jQuery('#adminsanity-all-notices-menu').remove();}
		for (i in adminmessagetypes) {
			count = jQuery('#adminsanity-notices-wrap div.'+adminmessagetypes[i]).length;
			/* console.log(adminmessagetypes[i]+' - '+count); */
			if (count > 0) {
				jQuery('#adminsanity-'+adminmessagetypes[i]+'-notices-count').html(count);
			} else {jQuery('#adminsanity-'+adminmessagetypes[i]+'-notices-menu').remove();}
		}
	}
	
	function adminsanity_notices_toggle() {
		if (document.getElementById('adminsanity-notices-wrap').style.display == '') {
			document.getElementById('adminsanity-notices-wrap').style.display = 'none';
			document.getElementById('adminsanity-notices-arrow').innerHTML = '&#9656;';
			jQuery('.adminsanity-notices-menu').hide();
		} else {
			document.getElementById('adminsanity-notices-wrap').style.display = '';
			document.getElementById('adminsanity-notices-arrow').innerHTML= '&#9662;';
			jQuery('.adminsanity-notices-menu').show();
		}
	}
	function admin_notices_show() {
		document.getElementById('adminsanity-notices-wrap').style.display = '';
		document.getElementById('adminsanity-notices-arrow').innerHTML= '&#9662;';
	}
	function adminsanity_show_notices(noticetype) {
		if (jQuery('#adminsanity-'+noticetype+'-notices-menu').hasClass('active')) {noticetype = 'none';}
		jQuery('.adminsanity-notices-menu').removeClass('active');
		jQuery(adminnoticesselector).show();
		for (i in adminnoticetypes) {
			if (jQuery('#adminsanity-'+adminnoticetypes[i]+'-notices')) {
				if (noticetype == 'all') {jQuery('#adminsanity-'+adminnoticetypes[i]+'-notices').show();}
				else {jQuery('#adminsanity-'+adminnoticetypes[i]+'-notices').hide();}
			}
		}
		if (noticetype == 'all') {jQuery('#adminsanity-all-notices-menu').addClass('active');}
		else if (noticetype != 'none' ) {
			jQuery('#adminsanity-'+noticetype+'-notices').show();
			jQuery('#adminsanity-'+noticetype+'-notices-menu').addClass('active');
		}
	}
	function adminsanity_show_types(messagetype) {
		if (jQuery('#adminsanity-'+messagetype+'-notices-menu').hasClass('active')) {
			adminsanity_show_notices('none'); return;
		}
		admin_notices_show(); adminsanity_show_notices('all');
		jQuery(adminnoticesselector).hide();
		jQuery('#adminsanity-notices-wrap div.'+messagetype).show();
		jQuery('.adminsanity-notices-menu').removeClass('active');
		jQuery('#adminsanity-'+messagetype+'-notices-menu').addClass('active');
	}";
	
	// note: this is from /wp-admin/js/common.js... to move the notices to below first h1/h2
	// echo " jQuery( 'div.updated, div.error, div.notice' ).not( '.inline, .below-h2' ).insertAfter( jQuery( '.wrap h1, .wrap h2' ).first() ); }";

	// --- prevent notices from being moved by common.js ---
	echo "jQuery(adminnoticesselector).not('.inline').addClass('below-h2').addClass('noticetemp');";

	// --- remove the below-h2 class after document loaded ---		
	echo "jQuery(document).ready(function() {";
		echo "setTimeout(function() {";
			echo "jQuery('div.update-nag.noticetemp, div.updated.noticetemp, div.error.noticetemp, div.notice-info.noticetemp, div.notice-warning.noticetemp')";
			echo ".removeClass('below-h2').removeClass('noticetemp');";
		echo "}, 1000);";
	echo "});";
	
	echo "</script>";

}

// --------------------
// Trigger Test Notices
// --------------------
add_action( 'admin_init', 'adminsanity_test_notices' );
function adminsanity_test_notices() {
	if ( !isset( $_GET['test'] ) || ( 'admin-notices' != $_GET['test'] ) ) {
		return;
	}
	add_action( 'network_admin_notices', 'adminsanity_test_notice' );
	add_action( 'user_admin_notices', 'adminsanity_test_notice' );
	add_action( 'admin_notices', 'adminsanity_test_notice' );
	add_action( 'admin_notices', 'adminsanity_test_notice', 11 );
	add_action( 'admin_notices', 'adminsanity_test_notice', 12 );
	add_action( 'all_admin_notices', 'adminsanity_test_notice' );
	add_action( 'all_admin_notices', 'adminsanity_test_notice', 11 );
}

// ------------
// Test Notices
// ------------
function adminsanity_test_notice() {
	global $adminsanity;
	if ( !isset( $adminsanity['test-count'] ) ) {$adminsanity['test-count'] = 0;}
	$adminsanity['test-count']++;
	
	if ( $adminsanity['test-count'] == 1 ) {$class = 'error';}
	elseif ( $adminsanity['test-count'] == 2 ) {$class = 'update-nag';}
	elseif ( $adminsanity['test-count'] == 3 ) {$class = 'notice notice-warning';}
	elseif ( $adminsanity['test-count'] == 4 ) {$class = 'updated';}
	elseif ( $adminsanity['test-count'] == 5 ) {$class = 'notice notice-info';}
	
	echo '<div class="' . esc_attr( $class ) . '">';
	echo 'This is a Test Notice with class "' . esc_attr( $class ) . '".';
	echo '</div>';
}
