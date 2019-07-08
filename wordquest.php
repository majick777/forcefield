<?php

// =======================
// WORDQUEST HELPER PLUGIN
// =======================

$wordquestversion = '1.6.9';

// Requires PHP 5.3 (for anonymous function usage)
// (otherwise helper library loads nothing)

// Usage Note: To Adjust WordQuest Admin Menu Position
// ---------------------------------------------------
// (example user override for use in Child Theme functions.php or /wp-content/mu-plugins/)
// note: this filter is called in wordquest plugins - not in this helper
// if (!has_filter('wordquest_menu_position', 'custom_wordquest_menu_position')) {
//	add_filter('wordquest_menu_position', 'custom_wordquest_menu_position');
// }
// if (!function_exists('custom_wordquest_menu_position')) {
//  function custom_wordquest_menu_position() {
//		return '10'; // numeric menu priority, defaults to 3
//	}
// }

// TODO: collapse/expand buttons for righthand sidebar?

// ================
// HELPER CHANGELOG
// ================

// -- 1.6.9 --
// - replaced all translation wrappers
// - default translation to bioship theme text domain

// -- 1.6.7 --
// - updated wordpress.org review links
// - added some missing translation wrappers

// -- 1.6.6 --
// - check wordpress.org only installs/availability
// - sanitize posted wqhv version value
// - add permission check to debug switch

// -- 1.6.5 --
// - added stickykit to replace float script
// - added basic string translation wrappers
// - added debug output switch
// - split released / upcoming plugin boxes
// - fix to latest / next release box
// - fix to sidebar options saving call
// - fix to admin notice boxer

// -- 1.6.0 --
// - use variable function names
// - change function prefix to wqhelper
// - text link forms for donations

// -- 1.5.0 --
// - added version checking/loading
// - added global admin page
// - added admin styles/scripts
// - added subscriber levels
// - further wordquest conversions
// - added freemius submenu styling
// - split feed load metaxboxes
// - added feed transient storage
// - added admin notice boxer
// - sidebar options to single array
// - AJAXify some helper actions

// -- 1.4.0 --
// - change to wordquest.org
// - updated donation amounts

// -- 1.3.0 --
// - added recurring donations
// - user email populate bonus form

// -- 1.2.0 --
// - added bonus report


// START CODE - PHP 5.3 MINIMUM REQUIRED FOR ANONYMOUS FUNCTIONS
// -------------------------------------------------------------
if (version_compare(PHP_VERSION, '5.3.0') >= 0) {

// Set this Wordquest Helper Plugin version
// ----------------------------------------
// 1.6.0: wqv to wqhv for new variable functions
// 1.6.6: move wordquestversion to top for easy changing
$wqhv = str_replace('.','',$wordquestversion);

// set global site URLs
// --------------------
// 1.6.5: for clearer/cleaner usage
global $wqurls;
$wqurls = array(
	'wp' => 'http://wordpress.org',
	'wq' => 'http://wordquest.org',
	'prn' => 'http://pluginreview.net',
	'bio' => 'http://bioship.space'
);

// set debug switch default
// ----------------------
// 1.6.6: set debug switch to off to recheck later
global $wqdebug; $wqdebug = false;

// =================================
// Version Handling Loader Functions
// =================================
// ...future proofing helper update library...

// Add to global array of Wordquest versions
// -----------------------------------------
// 1.6.0: change globals to use new variable functions (as not backcompatible!)
global $wordquesthelpers, $wqfunctions;
if (!is_array($wordquesthelpers)) {$wordquesthelpers = array($wqhv);}
elseif (!in_array($wqhv, $wordquesthelpers)) {$wordquesthelpers[] = $wqhv;}

// Set Latest Wordquest Version on Admin Load
// ------------------------------------------
// 1.5.0: use admin_init not plugins_loaded so as to be usable by themes
if (!has_action('admin_init', 'wqhelper_admin_loader', 1)) {
	add_action('admin_init', 'wqhelper_admin_loader', 1);
}

if (!function_exists('wqhelper_admin_loader')) {
 function wqhelper_admin_loader() {
 	global $wqdebug;

	// 1.6.6: check debug switch here so we can check permissions
	if (current_user_can('manage_options')) {
		if ( (isset($_REQUEST['wqdebug'])) && ($_REQUEST['wqdebug'] == 'yes') ) {$wqdebug = true;}
	}

 	// 1.6.0: maybe remove the pre 1.6.0 loader action
 	if (has_action('admin_init', 'wordquest_admin_load')) {
 		remove_action('admin_init', 'wordquest_admin_load');
 	}

 	// 1.6.0: new globals used for new method
 	global $wordquesthelper, $wordquesthelpers;
 	$wordquesthelper = max($wordquesthelpers);
 	if ($wqdebug) {echo "<!-- WHQV: ".$wordquesthelper." -->";}

	// 1.6.0: set the function caller helper
	global $wqcaller, $wqfunctions;
	$vfunctionname = 'wqhelper_caller_';
	$vfunc = $vfunctionname.$wordquesthelper;

	if (is_callable($wqfunctions[$vfunc])) {
		$wqfunctions[$vfunc]($vfunctionname); // $wqcaller = $wqfunctions[$vfunctionname];
	} elseif (function_exists($vfunc)) {call_user_func($vfunc, $vfunctionname);}
	if ($wqdebug) {echo "<!-- WQ CALLER: "; print_r($wqcaller); echo " -->";}

 	// 1.5.0: set up any admin notices via helper version
 	// 1.6.0: ...use caller function directly for this
 	$vadminnotices = 'wqhelper_admin_notices';
 	if (is_callable($wqcaller)) {$wqcaller($vadminnotices);}
 	elseif (function_exists($vadminnotices)) {call_user_func($vadminnotices);}

 }
}

// Function to Define Function Caller
// ----------------------------------
// 1.6.0: some lovely double abstraction here!
$vfuncname = 'wqhelper_caller_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function($vfunc) {
		global $wqfunctions, $wqcaller;
		if (!is_callable($wqcaller)) {
			$wqcaller = function($vfunction, $vargs = null) {
				global $wordquesthelper, $wqfunctions;
				$vfunc = $vfunction.'_'.$wordquesthelper;
				if (is_callable($wqfunctions[$vfunc])) {return $wqfunctions[$vfunc]($vargs);}
				elseif (function_exists($vfunc)) {return call_user_func($vfunc, $vargs);}
			};
		}
	};
}


// Versioned Admin Page Caller Functions
// -------------------------------------
// wqhelper_admin_page
// wqhelper_admin_notice_boxer
// wqhelper_get_plugin_info
// wqhelper_admin_plugins_column
// wqhelper_admin_feeds_column
// wqhelper_install_plugin
// wqhelper_reminder_notice
// wqhelper_translate
if (!function_exists('wqhelper_admin_page')) {
 function wqhelper_admin_page($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
// admin notice boxer
if (!function_exists('wqhelper_admin_notice_boxer')) {
 function wqhelper_admin_notice_boxer($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
// get plugins info
if (!function_exists('wqhelper_get_plugin_info')) {
 function wqhelper_get_plugin_info($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__, $vargs);
 }
}
// admin page plugins column
if (!function_exists('wqhelper_admin_plugins_column')) {
 function wqhelper_admin_plugins_column($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
// admin page feeds column
if (!function_exists('wqhelper_admin_feeds_column')) {
 function wqhelper_admin_feeds_column($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}

// 1.6.5: WordQuest plugin install
if (!function_exists('wqhelper_install_plugin')) {
 function wqhelper_install_plugin($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
// 1.6.5: reminder notice message
if (!function_exists('wqhelper_reminder_notice')) {
 function wqhelper_reminder_notice($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
// 1.6.9: translation wrapper
if (!function_exists('wqhelper_translate')) {
 function wqhelper_translate($vstring) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vstring);
 }
}


// Sidebar Floatbox Caller Functions
// ---------------------------------
// wqhelper_sidebar_floatbox
// wqhelper_sidebar_paypal_donations
// wqhelper_sidebar_testimonial_box
// wqhelper_sidebar_floatmenuscript
// wqhelper_sidebar_stickykitscript

if (!function_exists('wqhelper_sidebar_floatbox')) {
 function wqhelper_sidebar_floatbox($vargs = null) {
	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_sidebar_paypal_donations')) {
 function wqhelper_sidebar_paypal_donations($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_sidebar_testimonial_box')) {
 function wqhelper_sidebar_testimonial_box($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_sidebar_floatmenuscript')) {
 function wqhelper_sidebar_floatmenuscript($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_sidebar_stickykitscript')) {
 function wqhelper_sidebar_stickykitscript($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}


// Dashboard Feed Caller Functions
// -------------------------------
// wqhelper_add_dashboard_feed_widget
// wqhelper_dashboard_feed_widget
// wqhelper_process_rss_feed
// wqhelper_load_category_feed

if (!function_exists('wqhelper_add_dashboard_feed_widget')) {
 function wqhelper_add_dashboard_feed_widget($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_dashboard_feed_javascript')) {
 function wqhelper_dashboard_feed_javascript($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_dashboard_feed_widget')) {
 function wqhelper_dashboard_feed_widget($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_pluginreview_feed_widget')) {
 function wqhelper_pluginreview_feed_widget($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
if (!function_exists('wqhelper_process_rss_feed')) {
 function wqhelper_process_rss_feed($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}
// if (!function_exists('wqhelper_load_category_feed')) {
//  function wqhelper_load_category_feed($vargs = null) {
// 	if (!is_admin()) {return;} global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
//  }
// }


// ------------------
// Styles and Scripts
// ------------------

// Add Wordquest Styles to Admin Footer
// ------------------------------------
if (!has_action('admin_footer', 'wqhelper_admin_styles')) {
	add_action('admin_footer', 'wqhelper_admin_styles');
}
if (!function_exists('wqhelper_admin_styles')) {
 function wqhelper_admin_styles($vargs = null) {
	remove_action('admin_footer', 'wordquest_admin_styles');
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}

// Add Wordquest Scripts to Admin Footer
// -------------------------------------
if (!has_action('admin_footer', 'wqhelper_admin_scripts')) {
	add_action('admin_footer', 'wqhelper_admin_scripts');
}
if (!function_exists('wqhelper_admin_scripts')) {
 function wqhelper_admin_scripts($vargs = null) {
	remove_action('admin_footer', 'wordquest_admin_scripts');
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}

// --------------
// AJAX Functions
// --------------

// AJAX for reminder dismissal
// ---------------------------
// 1.6.5: added this AJAX function
if (!has_action('wp_ajax_wqhelper_reminder_dismiss', 'wqhelper_reminder_dismiss')) {
	add_action('wp_ajax_wqhelper_reminder_dismiss', 'wqhelper_reminder_dismiss');
}
if (!function_exists('wqhelper_reminder_dismiss')) {
 function wqhelper_reminder_dismiss($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}

// AJAX Load Category Feed
// -----------------------
if (!has_action('wp_ajax_wqhelper_load_feed_cat', 'wqhelper_load_feed_category')) {
	add_action('wp_ajax_wqhelper_load_feed_cat', 'wqhelper_load_feed_category');
}
if (!function_exists('wqhelper_load_feed_category')) {
 function wqhelper_load_feed_category($vargs = null) {
 	global $wqcaller; return $wqcaller(__FUNCTION__,$vargs);
 }
}

// Update Sidebar Options
// ----------------------
// 1.6.0: ! caller exception ! use matching form version function here just in case...
if (!has_action('wp_ajax_wqhelper_update_sidebar_boxes', 'wqhelper_update_sidebar_boxes')) {
 	add_action('wp_ajax_wqhelper_update_sidebar_boxes', 'wqhelper_update_sidebar_boxes');
}

if (!function_exists('wqhelper_update_sidebar_boxes')) {
 function wqhelper_update_sidebar_boxes() {
 	if (!isset($_POST['wqhv'])) {return;} else {$wqhv = $_POST['wqhv'];}
 	// 1.6.6: sanitize version value
 	if ( (!is_numeric($wqhv)) || (strlen($wqhv) !== 3) ) {return;}

 	$vfunc = 'wqhelper_update_sidebar_options_'.$wqhv;

 	// 1.6.5: fix to function call method
 	global $wqfunctions;
 	if (is_callable($wqfunctions[$vfunc])) {$wqfunctions[$vfunc]();}
 	elseif (function_exists($vfunc)) {call_user_func($vfunc);}
 }
}

// ==========================
// Version Specific Functions
// ==========================
// (functions below this point must be suffixed with _{VERSION} to work
// and update with each plugin helper version regardless of change state)

// Translation Wrapper
// -------------------
// 1.6.9: check translated labels global
$vfuncname = 'wqhelper_translate_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function($vstring) {
		global $wqlabels;
		if (isset($wqlabels[$vstring])) {return $wqlabels[$vstring];}
		// 1.6.9: fallback translation for bioship theme
		if (function_exists('bioship_translate')) {return bioship_translate($vstring);}
		if (function_exists('translate')) {return translate($vstring, 'default');}
		return $vstring;
	};
}

// Admin Notice Boxer
// ------------------
// (for settings pages)
$vfuncname = 'wqhelper_admin_notice_boxer_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function() {

	// count admin notices
	// global $wp_filter; $vnotices = 0; // print_r($wp_filter);
	// if (isset($wp_filter['admin_notices'])) {$vadminnotices = $vnotices = count($wp_filter['admin_notices']);}
	// if (is_network_admin()) {if (isset($wp_filter['network_admin_notices'])) {$vnetworknotices = count($wp_filter['network_admin_notices']); $vnotices = $vnotices + $vnetworknotices;} }
	// if (is_user_admin()) {if (isset($wp_filter['user_admin_notices'])) {$vusernotices = count($wp_filter['user_admin_notices']); $vnotices = $vnotices + $vusernotices;} }
	// if (isset($wp_filter['all_admin_notices'])) {$valladminnotices = count($wp_filter['all_admin_notices']); $vnotices = $vnotices + $valladminnotices;}
	// if ($vnotices == 0) {return;}

	// print_r($wp_filter['admin_notices']); print_r($wp_filter['all_admin_notices']);
	// echo "<!-- Notices: ".$vadminnotices." - ".$vnetworknotices." - ".$vuseradminnotices." - ".$valladminnotices." -->";

	echo "<script>function togglenoticebox() {divid = 'adminnoticewrap';
	if (document.getElementById(divid).style.display == '') {
		document.getElementById(divid).style.display = 'none'; document.getElementById('adminnoticearrow').innerHTML = '&#9662;';}
	else {document.getElementById(divid).style.display = ''; document.getElementById('adminnoticearrow').innerHTML= '&#9656;';} } ";
	// straight from /wp-admin/js/common.js... to move the notices if common.js is not loaded...
	echo "jQuery(document).ready(function() {jQuery( 'div.updated, div.error, div.notice' ).not( '.inline, .below-h2' ).insertAfter( jQuery( '.wrap h1, .wrap h2' ).first() ); });";
	echo "</script>";

	$vadminnotices = ''; // $vadminnotices = '('.$vnotices.')';
	echo '<div style="width:680px" id="adminnoticebox" class="postbox">';
	echo '<h3 class="hndle" style="margin:7px 14px;font-size:12pt;" onclick="togglenoticebox();">';
	echo '<span id="adminnoticearrow">&#9662;</span> &nbsp; ';
	echo wqhelper_translate('Admin Notices');
	echo $vadminnotices.'</span></h3>';
	echo '<div id="adminnoticewrap" style="display:none";><h2></h2></div></div>';

	// echo '<div style="width:75%" id="adminnoticebox" class="postbox">';
	// echo '<h3 class="hndle" style="margin-left:20px;" onclick="togglenoticebox();"><span>&#9660; ';
	// echo wqhelper_translate('Admin Notices');
	// echo ' ('.$vadminnotices.')</span></h3>';
	// echo '<div id="adminnoticewrap" style="display:none";><h2></h2></div></div>';
 };
}

// Usage Reminder Notice Check
// ---------------------------
// 1.5.0: added reminder prototype that does nothing yet
// 1.6.5: completed usage reminder notices
$vfuncname = 'wqhelper_admin_notices_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function() {
 	global $wordquestplugins;
 	foreach ($wordquestplugins as $vpluginslug => $wqplugin) {
 		// 1.6.8: move here to fix undefined index warning
 		$vpre = $wqplugin['settings'];

 		// 1.6.7: maybe set first install version for plugin
 		if (!isset($vsidebaroptions['installversion'])) {
			$vsidebaroptions['installversion'] = $wqplugin['version'];
			update_option($vpre.'_sidebar_options', $vsidebaroptions);
 		}
 		// 1.6.5: no reminders needed if pro version
 		if ($wqplugin['plan'] == 'premium') {return;}

 		$vsidebaroptions = get_option($vpre.'_sidebar_options');
 		// 1.6.5: no reminders if donation box has been turned off
 		// 1.6.7: revert that as so many other ways to still contribute
 		// if ( (isset($vsidebaroptions['donationboxoff']))
 		//   && ($vsidebaroptions['donationboxoff'] == 'checked') ) {return;}

 		if (isset($vsidebaroptions['installdate'])) {
 			$vreminder = false;
 			$vinstalltime = @strtotime($vsidebaroptions['installdate']);
 			$vtimesince = time() - $vinstalltime;
 			$vdayssince = floor($vtimesince / (24*60*60));

 			// 30 days, 90 days and 1 years notices
 			if ($vdayssince > 365) {
 				if (!isset($vsidebaroptions['365days'])) {$vreminder = '365';}
 			} elseif ($vdayssince > 90) { // 90 day notice
 				if (!isset($vsidebaroptions['90days'])) {$vreminder = '90';}
 			} elseif ($vdayssince > 30) { // 30 day notice
 				if (!isset($vsidebaroptions['30days'])) {$vreminder = '30';}
 			}

 			if ($vreminder) {
				// add an admin reminder notice
				global $wqreminder; $wqreminder[$vpluginslug] = $wqplugin;
				$wqreminder[$vpluginslug]['days'] = $vdayssince;
				$wqreminder[$vpluginslug]['notice'] = $vreminder;
				add_action('admin_notices', 'wqhelper_reminder_notice');
 			}
 		} else {
 			$vsidebaroptions['installdate'] = date('Y-m-d');
 			update_option($vpre.'_sidebar_options', $vsidebaroptions);
 		}
  	}
 };
}

// Usage Reminder Notice
// ---------------------
// 1.6.5: added reminder notice text
$vfuncname = 'wqhelper_reminder_notice_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function() {
		global $wqreminder, $wqurls;

		foreach ($wqreminder as $vpluginslug => $vreminder) {
			echo "<div class='updated notice is-dismissable' id='".$vpluginslug."-reminder-notice' style='line-height:20px;margin:0;'>";
			echo wqhelper_translate("You've been enjoying")." ";
			echo $wqreminder[$vpluginslug]['title']." ".wqhelper_translate("for")." ";
			echo $wqreminder[$vpluginslug]['days']." ".wqhelper_translate("days").". ";
			echo wqhelper_translate("If you like it, here's some ways you can help make it better").":<br>";

			// Links: Supporter / Donate / Rate / Testimonial / Feedback / Development / Go Pro
			// 1.6.7: extended link anchor text for clarity
			echo "<table cellpadding='0' cellspacing='0' style='width:100%;'><tr><td>";
			echo "<ul style='list-style:none;padding:0;margin:0;'>";
			echo "<li style='display:inline-block;'><a href='".$wqurls['wq']."/contribute/?tab=supporterlevels' target=_blank>&rarr; ".wqhelper_translate('Become a Supporter')."</a></li>";
			echo "<li style='display:inline-block;margin-left:15px;'><a href='".$wqurls['wq']."/contribute/?plugin=".$vpluginslug."' target=_blank>&rarr; ".wqhelper_translate('Make a Donation')."</a></li>";
			if (isset($wqreminder['wporgslug'])) {
				echo "<li style='display:inline-block;margin-left:15px;'>";
				// 1.6.7: different rating action for theme
				if ($vpluginslug == 'bioship') {
					echo "<a href='".$wqurls['wp']."/support/theme/".$vpluginslug."/reviews/?rate=5#new-post' target=_blank>&rarr; ".wqhelper_translate('Rate Theme')."</a></li>";
				} else {echo "<a href='".$wqurls['wp']."/support/plugin/".$vpluginslug."/reviews/?rate=5#new-post' target=_blank>&rarr; ".wqhelper_translate('Rate Plugin')."</a></li>";}
			}
			echo "<li style='display:inline-block;margin-left:15px;'><a href='".$wqurls['wq']."/contribute/?tab=testimonial' target=_blank>&rarr; ".wqhelper_translate('Send a Testimonial')."</a></li>";
			echo "<li style='display:inline-block;margin-left:15px;'><a href='".$wqurls['wq']."/support/".$vpluginslug."' target=_blank>&rarr; ".wqhelper_translate('Give Feedback')."</a></li>";
			echo "<li style='display:inline-block;margin-left:15px;'><a href='".$wqurls['wq']."/contribute/?tab=development' target=_blank>&rarr; ".wqhelper_translate('Contribute to Development')."</a></li>";
			// Pro Version plan link
			if ( (isset($wqreminder['hasplans'])) && ($wqreminder['hasplans']) ) {
				$vupgradeurl = admin_url('admin.php').'?page='.$wqreminder['slug'].'-pricing';
				echo "<li style='display:inline-block;margin-left:15px;'><a href='".$vupgradeurl."'><b>&rarr; ".wqhelper_translate('Go PRO')."</b></a></li>";
			}
			echo "</ul></td><td style='text-align:right;'>";
			// make notice dismissable link
			$vdismisslink = admin_url('admin-ajax.php').'?action=wqhelper_reminder_dismiss&slug='.$vpluginslug.'&notice='.$wqreminder[$vpluginslug]['notice'];
			echo "<a href='".$vdismisslink."' target='wqdismissframe' style='text-decoration:none;' title='".wqhelper_translate('Dismiss this Notice')."'>";
			echo "<div class='dashicons dashicons-dismiss' style='font-size:16px;'></div></a>";
			echo "</td></tr></table></div>";
		}
		echo "<iframe style='display:none;' src='javascript:void(0);' name='wqdismissframe' id='wqdimissframe'></iframe>";
	};
}

// Reminder Dismisser
// ------------------
$vfuncname = 'wqhelper_reminder_dismiss_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function() {
		if (!current_user_can('manage_options')) {return;}
		$vpluginslug = $_REQUEST['slug']; $vnotice = $_REQUEST['notice'];
		if ( ($vnotice != '30') && ($vnotice != '90') && ($vnotice != '365') ) {return;}
		global $wordquestplugins; $vpre = $wordquestplugins[$vpluginslug]['settings'];
		$vsidebaroptions = get_option($vpre.'_sidebar_options');
		if ( (isset($vsidebaroptions[$vnotice.'days'])) && ($vsidebaroptions[$vnotice.'days'] == 'dismissed') ) {$vsidebaroptions[$vnotice.'days'] = '';}
		else {$vsidebaroptions[$vnotice.'days'] = 'dismissed';}
		update_option($vpre.'_sidebar_options', $vsidebaroptions);
		echo "<script>parent.document.getElementById('".$vpluginslug."-reminder-notice').style.display = 'none';</script>";
		exit;
	};
}


// Get WordQuest Plugins Info
// --------------------------
$vfuncname = 'wqhelper_get_plugin_info_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function() {
		global $wqurls, $wqdebug;
		// 1.5.0: get plugin info (maximum twice daily)
		$vplugininfo = get_transient('wordquest_plugin_info');
		if ($wqdebug) {$vplugininfo = '';} // clear transient for debugging
		if ( (!$vplugininfo) || ($vplugininfo == '') || (!is_array($vplugininfo)) ) {
			$vpluginsurl = $wqurls['wq'].'/?get_plugins_info=yes';
			$vargs = array('timeout' => 15);
			$vplugininfo = wp_remote_get($vpluginsurl, $vargs);
			if (!is_wp_error($vplugininfo)) {
				$vplugininfo = $vplugininfo['body'];
				$vdataend = "*****END DATA*****";
				if (strstr($vplugininfo, $vdataend)) {
					$vpos = strpos($vplugininfo, $vdataend);
					$vplugininfo = substr($vplugininfo, 0, $vpos);
					$vplugininfo = json_decode($vplugininfo, true);
					set_transient('wordquest_plugin_info', $vplugininfo, (12*60*60));
				} else {$vplugininfo = '';}
			} else {$vplugininfo = '';}
		}
		if ($wqdebug) {echo "<!-- Plugin Info: "; print_r($vplugininfo); echo " -->";}
		return $vplugininfo;
	};
}

// Version Specific Admin Page
// ---------------------------
$vfuncname = 'wqhelper_admin_page_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function() {

	global $wordquesthelper, $wordquestplugins, $wqurls;

	echo '<div id="pagewrap" class="wrap">';

	// Call Admin Notice Boxer
	wqhelper_admin_notice_boxer();

	echo "<script>function togglemetabox(divid) {
		var divid = divid+'-inside';
		if (document.getElementById(divid).style.display == '') {
			document.getElementById(divid).style.display = 'none';
		} else {document.getElementById(divid).style.display = '';}
	}</script>";

	echo '<style>#plugincolumn, #feedcolumn {display: inline-block; float:left; margin: 0 5px;}
	#plugincolumn .postbox {max-width:330px;} #feedcolumn .postbox {max-width:330px;}
	#plugincolumn .postbox h2, #feedcolumn .postbox h2 {font-size: 16px; margin-top: 0; background-color: #E0E0EE; padding: 5px;}
	#page-title a {text-decoration:none;} #page-title h2 {color: #3568A9;}
	</style>';

	// Floating Sidebar
	// ----------------
	// set dummy "plugin" values for sidebar
	global $wordquestplugins, $wordquesthelper;
	$wordquestplugins['wordquest']['version'] = $wordquesthelper;
	$wordquestplugins['wordquest']['title'] = 'WordQuest Alliance';
	$wordquestplugins['wordquest']['namespace'] = 'wordquest';
	$wordquestplugins['wordquest']['settings'] = 'wq';
	$wordquestplugins['wordquest']['plan'] = 'free';
	$wordquestplugins['wordquest']['wporg'] = false;
	$wordquestplugins['wordquest']['wporgslug'] = false;
	$vargs = array('wordquest','special');
	wqhelper_sidebar_floatbox($vargs);

	// 1.6.5: replace floatmenu with stickykit
	echo wqhelper_sidebar_stickykitscript();
	echo '<style>#floatdiv {float:right;} #wpcontent, #wpfooter {margin-left:150px !important;}</style>';
	echo '<script>jQuery("#floatdiv").stick_in_parent();</script>';
	unset($wordquestplugins['wordquest']);

	// echo wqhelper_sidebar_floatmenuscript();
	// echo '<script language="javascript" type="text/javascript">
	// floatingMenu.add("floatdiv", {targetRight: 10, targetTop: 20, centerX: false, centerY: false});
	// function move_upper_right() {
	//	floatingArray[0].targetTop=20;
	//	floatingArray[0].targetBottom=undefined;
	//	floatingArray[0].targetLeft=undefined;
	//	floatingArray[0].targetRight=10;
	//	floatingArray[0].centerX=undefined;
	//	floatingArray[0].centerY=undefined;
	// }
	// move_upper_right();
	// </script>

	// Admin Page Title
	// ----------------
	$vwordquesticon = plugins_url('images/wordquest.png', __FILE__);
	echo '<style>.wqlink {text-decoration:none;} .wqlink:hover {text-decoration:underline;}</style>';
	echo '<table><tr><td width="20"></td><td><img src="'.$vwordquesticon.'"></td><td width="20"></td>';
	echo '<td><div id="page-title"><a href="'.$wqurls['wq'].'" target=_blank><h2>WordQuest Alliance</h2></a></div></td>';
	echo '<td width="30"></td><td><h3>&rarr; <a href="'.$wqurls['wq'].'/register/" class="wqlink" target=_blank>'.wqhelper_translate('Join').'</a></h3></td>';
	echo '<td> / </td><td><h3><a href="'.$wqurls['wq'].'/login/"  class="wqlink" target=_blank>'.wqhelper_translate('Login').'</a></h3></td>';
	echo '<td width="20"></td><td><h3>&rarr; <a href="'.$wqurls['wq'].'/solutions/"  class="wqlink" target=_blank>'.wqhelper_translate('Solutions').'</a></h3></td>';
	echo '<td width="20"></td><td><h3>&rarr; <a href="'.$wqurls['wq'].'/contribute/"  class="wqlink" target=_blank>'.wqhelper_translate('Contribute').'</a></h3></td>';
	echo '</tr></table>';

	// Output Plugins Column
	// ---------------------
	wqhelper_admin_plugins_column(null);

	// Output Feeds Column
	// -------------------
	wqhelper_admin_feeds_column(null);

	// Wordquest sidebar 'plugin' box
	// ------------------------------
	function wq_sidebar_plugin_footer() {
		global $wqurls;
		$viconurl = plugins_url('images/wordquest.png', __FILE__);
		echo '<div id="pluginfooter"><div class="stuffbox" style="width:250px;background-color:#ffffff;"><h3>Source Info</h3><div class="inside">';
		echo "<center><table><tr>";
		echo "<td><a href='".$wqurls['wq']."' target='_blank'><img src='".$viconurl."' border=0></a></td></td>";
		echo "<td width='14'></td>";
		echo "<td><a href='".$wqurls['wq']."' target='_blank'>WordQuest Alliance</a><br>";
		echo "<a href='".$wqurls['wq']."/plugins/' target='_blank'><b>&rarr; WordQuest Plugins</b></a><br>";
		echo "<a href='".$wqurls['prn']."/directory/' target='_blank'>&rarr; Plugin Directory</a></td>";
		echo "</tr></table></center>";
		echo '</div></div></div>';
	}

	echo '</div>';

	// hidden iframe for plugin actions
	echo '<iframe id="pluginactionframe" src="javascript:void(0);" style="display:none;"></iframe>';

	echo '</div>';
 };
}

// Version Specific Plugins Column
// -------------------------------
$vfuncname = 'wqhelper_admin_plugins_column_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
	$wqfunctions[$vfuncname] = function($vargs) {

	global $wordquesthelper, $wordquestplugins, $wqurls, $wqdebug;

	// check if WordPress.Org Plugins only
	// -----------------------------------
	// 1.6.6: check if current WQ plugins are all installed via WordPress.Org
	// (if so, only provide option to install other WQ plugins in repository)
	global $wordpressorgonly; $wordpressorgonly = true;
	foreach ($wordquestplugins as $pluginslug => $plugin) {
		// if this is false, it was from wordquest not wordpress
		if (!$plugin['wporg']) {$wordpressorgonly = false;}
	}

	// Plugin Action Select Javascript
	// -------------------------------
	// TODO: test all options here more thoroughly...
	echo "<script>
	function dopluginaction(pluginslug) {
		var selectelement = document.getElementById(pluginslug+'-action');
		var actionvalue = selectelement.options[selectelement.selectedIndex].value;
		var linkel = document.getElementById(pluginslug+'-link');
		var adminpageurl = '".admin_url('admin.php')."';
		if (actionvalue == 'settings') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug;}
		if (actionvalue == 'update') {linkel.target = '_self'; linkel.href = document.getElementById(pluginslug+'-update-link').value;}
		if (actionvalue == 'activate') {linkel.target = '_self'; linkel.href = document.getElementById(pluginslug+'-activate-link').value;}
		if (actionvalue == 'install') {linkel.target = '_self';	linkel.href = document.getElementById(pluginslug+'-install-link').value;}
		if (actionvalue == 'support') {linkel.target = '_blank'; linkel.href = adminpageurl+'?page='+pluginslug+'-wp-support-forum';}
		if (actionvalue == 'donate') {linkel.target = '_blank';	linkel.href = '".$wqurls['wq']."/contribute/?plugin='+pluginslug;}
		if (actionvalue == 'testimonial') {linkel.target = '_blank'; linkel.href = '".$wqurls['wq']."/contribute/?tab=testimonial';}
		if (actionvalue == 'rate') {linkel = '_blank'; linkel.href = '".$wqurls['wp']."/support/plugin/'+pluginslug+'/reviews/?rate=5#postform';}
		if (actionvalue == 'development') {linkel.target = '_blank'; linkel.href= '".$wqurls['wq']."/contribute/?tab=development';}
		if (actionvalue == 'contact') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-contact';}
		if (actionvalue == 'home') {linkel.target = '_blank'; linkel.href = '".$wqurls['wq']."/plugins/'+pluginslug+'/';}
		if (actionvalue == 'upgrade') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-pricing';}
		if (actionvalue == 'account') {linkel.target = '_self'; linkel.href = adminpageurl+'?page='+pluginslug+'-account';}
	}</script>";

	echo "<style>.pluginlink {text-decoration:none;} .pluginlink:hover {text-decoration:underline;}</style>";

	// Get Installed and Active Plugin Slugs
	// -------------------------------------
	$vi = 0; foreach ($wordquestplugins as $vpluginslug => $vvalues) {$vpluginslugs[$vi] = $vpluginslug; $vi++;}
	// if ($wqdebug) {echo "<!-- Active Wordquest Plugins: "; print_r($vpluginslugs); echo " -->";}

	// Get All Installed Plugins Info
	// ------------------------------
	$vi = 0; $vinstalledplugins = get_plugins();
	foreach ($vinstalledplugins as $vpluginfile => $vvalues) {$vinstalledslugs[$vi] = sanitize_title($vvalues['Name']); $vi++;}
	// if ($wqdebug) {echo "<!-- Installed Plugins: "; print_r($vinstalledplugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Installed Plugin Slugs: "; print_r($vinstalledslugs); echo " -->";}

	// Get Plugin Update Info
	// ----------------------
	// 1.6.6: define empty pluginupdates array
	$vi = 0; $vupdateplugins = get_site_transient('update_plugins'); $vpluginupdates = array();
	foreach ($vupdateplugins->response as $vpluginfile => $vvalues) {$vpluginupdates[$vi] = $vvalues->slug; $vi++;}
	// if ($wqdebug) {echo "<!-- Plugin Updates: "; print_r($vupdateplugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Plugin Update Slugs: "; print_r($vpluginupdates); echo " -->";}

	// Get Available Plugins from WordQuest.org
	// ----------------------------------------
	$vplugininfo = wqhelper_get_plugin_info();

	// process plugin info
	$vi = 0; $vwqplugins = array(); $vwqpluginslugs = array();
	if (is_array($vplugininfo)) {
		foreach ($vplugininfo as $vplugin) {
			// print_r($vplugin); // debug point
			if (isset($vplugin['slug'])) {
				$vwqpluginslugs[$vi] = $vpluginslug = $vplugin['slug']; $vi++;
				if (isset($vplugin['title'])) {$vwqplugins[$vpluginslug]['title'] = $vplugin['title'];}
				if (isset($vplugin['home'])) {$vwqplugins[$vpluginslug]['home'] = $vplugin['home'];}
				if (isset($vplugin['description'])) {$vwqplugins[$vpluginslug]['description'] = $vplugin['description'];}
				if (isset($vplugin['icon'])) {$vwqplugins[$vpluginslug]['icon'] = $vplugin['icon'];}
				if (isset($vplugin['paidplans'])) {$vwqplugins[$vpluginslug]['paidplans'] = $vplugin['paidplans'];}
				if (isset($vplugin['package'])) {$vwqplugins[$vpluginslug]['package'] = $vplugin['package'];}

				if (isset($vplugin['tags'])) {$vwqplugins[$vpluginslug]['tags'] = $vplugin['tags'];}
				if (isset($vplugin['cats'])) {$vwqplugins[$vpluginslug]['cats'] = $vplugin['cats'];}

				// 1.6.5: check release date and status
				if (isset($vplugin['releasedate'])) {$vwqplugins[$vpluginslug]['releasedate'] = $vplugin['releasedate'];}
				if (isset($vplugin['releasestatus'])) {$vwqplugins[$vpluginslug]['releasestatus'] = $vplugin['releasestatus'];}
				else {$vwqplugins[$vpluginslug]['releasestatus'] = 'Upcoming';}

				// 1.6.6: check for wordpress.org slug
				if (isset($vplugin['wporgslug'])) {$vwqplugins[$vpluginslug]['wporgslug'] = $vplugin['wporgslug'];}
				else {$vwpplugins[$vpluginslug]['wporgslug'] = false;}

				if (in_array($vpluginslug,$vinstalledslugs)) {$vwqplugins[$vpluginslug]['installed'] = 'yes';}
				else {$vwqplugins[$vpluginslug]['installed'] = 'no';}

				// check for latest release plugin
				if ( (isset($vplugin['latestrelease'])) && ($vplugin['latestrelease'] == 'yes') ) {
					$vwqplugins[$vpluginslug]['latestrelease'] = 'yes';
					$vlatestrelease = $vwqplugins[$vpluginslug];
					$vlatestrelease['slug'] = $vpluginslug;
				}
				// 1.6.5: check for next plugin release
				if ( (isset($vplugin['nextrelease'])) && ($vplugin['nextrelease'] == 'yes') ) {
					$vwqplugins[$vpluginslug]['nextrelease'] = 'yes';
					$vnextrelease = $vwqplugins[$vpluginslug];
					$vnextrelease['slug'] = $vpluginslug;
				}
			}
		}
	}
	// if ($wqdebug) {echo "<!-- WQ Plugin Slugs: "; print_r($vwqpluginslugs); echo " -->";}
	if ($wqdebug) {echo "<!-- WQ Plugins: "; print_r($vwqplugins); echo " -->";}

	// maybe set Plugin Release Info
	// -----------------------------
	global $wqreleases;
	if (isset($vlatestrelease)) {$wqreleases['latest'] = $vlatestrelease;}
	if (isset($vnextrelease)) {$wqreleases['next'] = $vnextrelease;}

	// get Installed Wordquest Plugin Data
	// -----------------------------------
	$vplugins = array(); $vinactiveplugins = array();
	$vi = 0; $vj = 0;
	foreach ($vinstalledplugins as $vpluginfile => $vvalues) {
		$vpluginslug = sanitize_title($vvalues['Name']);
		$vpluginfiles[$vpluginslug] = $vpluginfile;
		// echo '***'.$vpluginslug.'***'; // debug point
		if ( (in_array($vpluginslug, $vwqpluginslugs)) || (in_array($vpluginslug, $vpluginslugs)) ) {
			$vplugins[$vi]['slug'] = $vpluginslug;
			$vplugins[$vi]['name'] = $vvalues['Name'];
			$vplugins[$vi]['filename'] = $vpluginfile;
			$vplugins[$vi]['version'] = $vvalues['Version'];
			$vplugins[$vi]['description'] = $vvalues['Description'];

			// check for matching plugin update
			if (in_array($vpluginslug,$vpluginupdates)) {$vplugins[$vi]['update'] = 'yes';}
			else {$vplugins[$vi]['update'] = 'no';}

			// filter out to get inactive plugins
			if (!in_array($vpluginslug,$vpluginslugs)) {
				$vinactiveplugins[$vj] = $vpluginslug; $vj++;
				$vinactiveversions[$vpluginslug] = $vvalues['Version'];
			}
			$vi++;
		}
	}
	// if ($wqdebug) {echo "<!-- Plugin Data: "; print_r($vplugins); echo " -->";}
	// if ($wqdebug) {echo "<!-- Inactive Plugins: "; print_r($vinactiveplugins); echo " -->";}

	// check if BioShip Theme installed
	// --------------------------------
	$vthemes = wp_get_themes(); $vbioshipinstalled = false;
	foreach ($vthemes as $vtheme) {if ($vtheme->stylesheet == 'bioship') {$vbioshipinstalled = true;} }

	echo '<div id="plugincolumn">';

		// Active Plugin Panel
		// -------------------
		$boxid = 'wordquestactive'; $boxtitle = wqhelper_translate('Active WordQuest Plugins');
		echo '<div id="'.$boxid.'" class="postbox">';
		echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
		echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
		foreach ($wordquestplugins as $vpluginslug => $vplugin) {
			if ($vpluginslug != 'bioship') { // filter out theme here
				if (in_array($vpluginslug,$vpluginupdates)) {
					$vupdatelink = wp_nonce_url(admin_url('update.php').'?action=upgrade-plugin&plugin='.$vpluginfiles[$vpluginslug],'upgrade-plugin_'.$vpluginfiles[$vpluginslug]);
					echo "<input type='hidden' id='".$vpluginslug."-update-link' value='".$vupdatelink."'>";
				}
				echo "<tr><td><a href='".$wqurls['wq']."/plugins/".$vpluginslug."' class='pluginlink' target=_blank>";
				echo $vplugin['title']."</a></td><td width='20'></td>";
				echo "<td>".$vplugin['version']."</td><td width='20'></td>";

				echo "<td><select name='".$vpluginslug."-action' id='".$vpluginslug."-action' style='font-size:8pt;'>";
				if (in_array($vpluginslug,$vpluginupdates)) {
					echo "<option value='update' selected='selected'>".wqhelper_translate('Update')."</option>";
					echo "<option value='settings'>".wqhelper_translate('Settings')."</option>";
				} else {echo "<option value='settings' selected='selected'>".wqhelper_translate('Settings')."</option>";}

				echo "<option value='donate'>".wqhelper_translate('Donate')."</option>";
				echo "<option value='testimonial'>".wqhelper_translate('Testimonial')."</option>";
				echo "<option value='support'>".wqhelper_translate('Support')."</option>";
				echo "<option value='development'>".wqhelper_translate('Development')."</option>";
				if (isset($vplugin['wporgslug'])) {echo "<option value='Rate'>".wqhelper_translate('Rate')."</option>";}

				// check for Pro Plan availability
				// if ($vplugin['plan'] == 'premium') {echo "<option value='contact'>Contact</option>";}
				if ( (isset($wordquestplugins[$vpluginslug]['hasplans'])) && ($wordquestplugins[$vpluginslug]['hasplans']) ) {
					if ($vplugin['plan'] != 'premium') {echo "<option style='font-weight:bold;' value='upgrade'>Go PRO!</option>";}
					else {echo "<option value='account'>Account</option>";}
				}

				echo "</select></td><td width='20'></td>";
				echo "<td><a href='javascript:void(0);' target=_blank id='".$vpluginslug."-link' onclick='dopluginaction(\"".$vpluginslug."\");'>";
				echo "<input class='button-secondary' type='button' value='".wqhelper_translate('Go')."'></a></td></tr>";
			}
		}
		echo '</table></div></div>';

		// Inactive Plugin Panel
		// ---------------------
		if (count($vinactiveplugins) > 0) {
			$boxid = 'wordquestinactive'; $boxtitle = wqhelper_translate('Inactive WordQuest Plugins');
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
			foreach ($vinactiveplugins as $vinactiveplugin) {
				$vactivatelink = admin_url('plugins.php').'?action=activate&plugin='.$vpluginfiles[$vinactiveplugin];
				$vactivatelink = wp_nonce_url($vactivatelink, 'activate-plugin_'.$vpluginfiles[$vinactiveplugin]);
				echo "<input type='hidden' id='".$vinactiveplugin."-activate-link' value='".$vactivatelink."'>";
				if (in_array($vinactiveplugin,$vpluginupdates)) {
					$vupdatelink = admin_url('update.php').'?action=upgrade-plugin&plugin='.$vpluginfiles[$vinactiveplugin];
					$vupdatelink = wp_nonce_url($vupdatelink, 'upgrade-plugin_'.$vpluginfiles[$vinactiveplugins]);
					echo "<input type='hidden' id='".$vinactiveplugin."-update-link' value='".$vupdatelink."'>";
				}
				echo "<tr><td><a href='".$vwqplugins[$vinactiveplugin]['home']."' class='pluginlink' target=_blank>";
				echo $vwqplugins[$vinactiveplugin]['title']."</a></td><td width='20'></td>";
				echo "<td>".$vinactiveversions[$vinactiveplugin]."</td><td width='20'></td>";
				echo "<td><select name='".$vinactiveplugin."-action' id='".$vinactiveplugin."-action' style='font-size:8pt;'>";
				if (in_array($vinactiveplugin,$vpluginupdates)) {
					echo "<option value='update' selected='selected'>".wqhelper_translate('Update')."</option>";
					echo "<option value='activate'>".wqhelper_translate('Activate')."</option>";
				} else {echo "<option value='activate' selected='selected'>".wqhelper_translate('Activate')."</option>";}
				echo "</select></td><td width='20'></td>";
				echo "<td><a href='javascript:void(0);' target=_blank id='".$vinactiveplugin."-link' onclick='dopluginaction(\"".$vinactiveplugin."\");'>";
				echo "<input class='button-secondary' type='button' value='".wqhelper_translate('Go')."'></a></td>";
				echo "</tr>";
			}
			echo '</table></div></div>';
		}

		$vreleasedplugins = array(); $vunreleasedplugins = array();
		if ( count($vwqplugins) > count($wordquestplugins) ) {
			foreach ($vwqplugins as $vpluginslug => $vwqplugin) {
				if ( (!in_array($vpluginslug, $vinstalledslugs)) && (!in_array($vpluginslug, $vinactiveplugins)) ) {
					if ($vwqplugin['releasestatus'] == 'Released') {$vreleasedplugins[$vpluginslug] = $vwqplugin;}
					else {
						$vreleasetime = strtotime($vwqplugin['releasedate']);
						$vwqplugin['slug'] = $vpluginslug;
						$vunreleasedplugins[$vreleasetime] = $vwqplugin;
					}
				}
			}
		}

		// Available Plugin Panel
		// ----------------------
		if (count($vreleasedplugins) > 0) {
			if ($wqdebug) {echo "<!-- Released Plugins: "; print_r($vreleasedplugins); echo " -->";}
			$boxid = 'wordquestavailable'; $boxtitle = wqhelper_translate('Available WordQuest Plugins');
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
			foreach ($vreleasedplugins as $vpluginslug => $vwqplugin) {

				// 1.6.5: add separate install link URL for each plugin for nonce checking
				// 1.6.6: use wordpress.org link if all plugins are from wordpress.org
				if ( ($wordpressorgonly) && ($vwqplugin['wporgslug']) ) {
					$vinstalllink = self_admin_url('update.php')."?action=install-plugin&plugin=".$vwqplugin['wporgslug'];
					$vinstalllink = wp_nonce_url($vinstalllink, 'install-plugin_'.$vwqplugin['wporgslug']);
					echo "<input type='hidden' name='".$vpluginslug."-install-link' value='".$vinstalllink."'>";
				} elseif ( (!$wordpressorgonly) && (is_array($vwqplugin['package'])) ) {
					$vinstalllink = admin_url('update.php')."?action=wordquest_plugin_install&plugin=".$vpluginslug;
					$vinstalllink = wp_nonce_url($vinstalllink, 'plugin-upload');
					echo "<input type='hidden' name='".$vpluginslug."-install-link' value='".$vinstalllink."'>";
				}

				echo "<tr><td><a href='".$vwqplugin['home']."' class='pluginlink' target=_blank>";
				echo $vwqplugin['title']."</a></td><td width='20'></td>";
				// echo "<td>".$vwqplugin['version']."</td><td width='20'></td>";

				echo "<td><select name='".$vpluginslug."-action' id='".$vpluginslug."-action' style='font-size:8pt;'>";

				// 1.6.6: check if only wp.org plugins installable
				if ( ($wordpressorgonly) && ($vwqplugin['wporgslug']) ) {
					// has a wordpress.org slug so installable from repository
					echo "<option value='install' selected='selected'>".wqhelper_translate('Install Now')."</option>";
					echo "<option value='home'>".wqhelper_translate('Plugin Home')."</option>";
				} elseif ( (!$wordpressorgonly) && (is_array($vwqplugin['package'])) ) {
					// not all plugins are from wordpress.org, use the install package
					echo "<option value='install' selected='selected'>".wqhelper_translate('Install Now')."</option>";
					echo "<option value='home'>".wqhelper_translate('Plugin Home')."</option>";
				} else {
					// oops, installation package currently unavailable (404)
					echo "<option value='home' selected='selected'>".wqhelper_translate('Plugin Home')."</option>";
				}
				echo "</select></td><td width='20'></td>";
				echo "<td><a href='javascript:void(0);' target=_blank id='".$vpluginslug."-link' onclick='dopluginaction(\"".$vpluginslug."\");'>";
				echo "<input class='button-secondary' type='button' value='".wqhelper_translate('Go')."'></a></td></tr>";
			}
			echo "</table></div></div>";
		}

		// Upcoming Plugin Panel
		// ---------------------
		if (count($vunreleasedplugins) > 0) {
			ksort($vunreleasedplugins);
			if ($wqdebug) {echo "<!-- Unreleased Plugins: "; print_r($vunreleasedplugins); echo " -->";}
			$boxid = 'wordquestupcoming'; $boxtitle = wqhelper_translate('Upcoming WordQuest Plugins');
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
			foreach ($vunreleasedplugins as $vreleasetime => $vwqplugin) {
				// $vpluginslug = $vwqplugin['slug'];
				echo "<tr><td><a href='".$vwqplugin['home']."' class='pluginlink' target=_blank>";
				echo $vwqplugin['title']."</a></td>";
				echo "<td><span style='font-size:9pt;'>";
				echo wqhelper_translate('Expected').': '.date('jS F Y', $vreleasetime);
				echo "</span></td></tr>";
			}
			echo "</table></div></div>";
		}

		// BioShip Theme
		// -------------
		$boxid = 'bioship'; $boxtitle = wqhelper_translate('BioShip Theme Framework');
		echo '<div id="'.$boxid.'" class="postbox">';
		echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
		echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table><tr><td><center>';

		if ($vbioshipinstalled) {
			// check if BioShip Theme is active...
			$vtheme = wp_get_theme();
			if ($vtheme->stylesheet == 'bioship') {
				echo wqhelper_translate('Sweet! You are using').' <b>';
				echo wqhelper_translate('BioShip Theme Framework').'</b>.<br>';
				echo wqhelper_translate('Great choice!').' ';
				// 1.6.7: add BioShip Theme Options link here
				if (THEMETITAN) {$voptionsurl = admin_url('admin.php').'?page=bioship-options';}
				elseif (THEMEOPT) {$voptionsurl = admin_url('admin.php').'?page=options-framework';}
				else {$voptionsurl = admin_url('customize.php');}
				echo '<a href="'.$voptionsurl.'">'.wqhelper_translate('Theme Options').'</a>';
			} elseif ( (is_child_theme()) && ($vtheme->template == 'bioship') ) {
				echo wqhelper_translate('Groovy. You are using').' <b>';
				echo wqhelper_translate('BioShip Framework').'</b>!<br>';
				echo wqhelper_translate('Your Child Theme is').' <b>'.$vtheme->Name.'</b><br><br>';
				// 1.6.7: add Child Theme Options link here
				if (THEMETITAN) {$voptionsurl = admin_url('admin.php').'?page=bioship-options';}
				elseif (THEMEOPT) {$voptionsurl = admin_url('admin.php').'?page=options-framework';}
				else {$voptionsurl = admin_url('customize.php');}
				echo '<a href="'.$voptionsurl.'">'.wqhelper_translate('Theme Options').'</a>';
			} else {
				echo wqhelper_translate('Looks like you have BioShip installed!').'<br>';
				echo '...'.wqhelper_translate('but it is not yet your active theme.').'<br><br>';

				// BioShip Theme activation link...
				$vactivatelink = admin_url('themes.php').'?action=activate&stylesheet=bioship';
				$vactivatelink = wp_nonce_url($vactivatelink, 'switch-theme_bioship');
				echo '<a href="'.$vactivatelink.'">'.wqhelper_translate('Click here to activate it now').'</a>.<br><br>';

				// Check for Theme Test Drive
				echo "<div id='testdriveoptions'>";
				if (function_exists('themedrive_determine_theme')) {
					// TODO: a better check here, this actually makes no sense
					if (class_exists('TitanFramework')) {
						$vtestdrivelink = admin_url('admin.php').'?page=bioship-options&theme=bioship';
					} elseif (function_exists('OptionsFramework_Init')) {
						$vtestdrivelink = admin_url('themes.php').'?page=options-framework&theme=bioship';
					} else {$vtestdrivelink = admin_url('customize.php').'?theme=bioship';}
					echo wqhelper_translate('or').', <a href="'.$vtestdrivelink.'">';
					echo wqhelper_translate('take it for a Theme Test Drive').'</a>.';
				} elseif (in_array('theme-test-drive',$vinstalledplugins)) {
					// Theme Test Drive plugin activation link
					$vactivatelink = admin_url('plugins.php').'?action=activate&plugin='.urlencode('theme-test-drive/themedrive.php');
					$vactivatelink = wp_nonce_url($vactivatelink,'activate-plugin_theme-test-drive/themedrive.php');
					echo wqhelper_translate('or').', <a href="'.$vactivatelink.'">';
					echo wqhelper_translate('activate Theme Test Drive plugin').'</a><br>';
					echo wqhelper_translate('to test BioShip without affecting your current site.');
				} else {
					// Theme Test Drive plugin installation link
					$vinstalllink = admin_url('update.php').'?action=install-plugin&plugin=theme-test-drive';
					$vinstalllink = wp_nonce_url($vinstalllink, 'install-plugin');
				 	echo wqhelper_translate('or').', <a href="'.$vinstalllink.'">';
				 	echo wqhelper_translate('install Theme Test Drive plugin').'</a><br>';
				 	echo wqhelper_translate('to test BioShip without affecting your current site.');
				}
				echo "</div>";
			}
 		} else {
			echo wqhelper_translate('Also from').' <b>WordQuest Alliance</b>, '.wqhelper_translate('check out the').'<br>';
			echo "<a href='".$wqurls['bio']."' target=_blank><b>BioShip Theme Framework</b></a><br>";
			echo wqhelper_translate('A highly flexible and responsive starter theme').'<br>'.wqhelper_translate('for users, designers and developers.');
		}

		if ( ($vtheme->template == 'bioship') || ($vtheme->stylesheet == 'bioship') ) {
			if (function_exists('admin_theme_updates_available')) {
				$vthemeupdates = admin_theme_updates_available();
				if ($vthemeupdates != '') {
					echo '<div class="update-nag" style="padding:3px 10px;margin:0 0 10px 0;text-align:center;">'.$vthemeupdates.'</div></font><br>';
				}
			}

			// TODO: future link for rating BioShip on wordpress.org theme repository ?
			// $vratelink = 'https://wordpress.org/support/theme/bioship/reviews/?rate=5#new-post';
			// echo '<br><a href="'.$vratelink.'" target=_blank>'.wqhelper_translate('Rate BioShip on WordPress.Org').'</a><br>';
		}

		// BioShip Feed
		// ------------
		// (only displays if Bioship theme is active)
		if (function_exists('muscle_bioship_dashboard_feed_widget')) {
			// $boxid = 'bioshipfeed'; $boxtitle = wphelper_translate('BioShip News');
			// echo '<div id="'.$boxid.'" class="postbox">';
			// echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			// echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;">';
				muscle_bioship_dashboard_feed_widget(false);
			// echo '</div></div>';
		}

		echo '</center></td></tr></table>';
		echo '</div></div>';

	// end column
	echo '</div>';
 };
}

// Version Specific Feed Column
// ----------------------------
$vfuncname = 'wqhelper_admin_feeds_column_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function($vargs) {

	echo '<div id="feedcolumn">';

		// Latest / Next Release
		// ---------------------
		global $wqreleases; $vlatestrelease = ''; $vnextrelease = '';
		if (isset($wqreleases['latest'])) {$vlatestrelease = $wqreleases['latest'];}
		if (isset($wqreleases['next'])) {$vnextrelease = $wqreleases['next'];}

		if ( (isset($vlatestrelease)) && (is_array($vlatestrelease)) ) {
			if ($vlatestrelease['installed'] == 'no') {
				$vrelease = $vlatestrelease; $boxid = 'wordquestlatest'; $boxtitle = wqhelper_translate('Latest Release');
			} else {$vrelease = $vnextrelease; $boxid = 'wordquestupcoming'; $boxtitle = wqhelper_translate('Upcoming Release');}
		} elseif ( (isset($vnextrelease)) && (is_array($vnextrelease)) ) {
			$vrelease = $vnextrelease; $boxid = 'wordquestupcoming'; $boxtitle = wqhelper_translate('Upcoming Release');
		}

		if ( (isset($vrelease)) && (is_array($vrelease)) ) {
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside"><table>';
			echo "<table><tr><td align='center'><img src='".$vrelease['icon']."' width='100' height='100'><br>";
			echo "<a href='".$vlatestrelease['home']."' target=_blank><b>".$vrelease['title']."</b></a></td><td width='10'></td>";
			echo "<td><span style='font-size:9pt;'>".$vrelease['description']."</span><br><br>";
			if ( (isset($vrelease['package'])) && (is_array($vrelease['package'])) ) {
				// 1.6.6: check for wordpress.org only installs
				global $wordpressorgonly; $vinstalllink = false;
				if ( ($wordpressorgonly) && ($vrelease['wporgslug']) ) {
					$vinstalllink = self_admin_url('update.php')."?action=install-plugin&plugin=".$vrelease['wporgslug'];
					$vinstalllink = wp_nonce_url($vinstalllink, 'install-plugin_'.$vrelease['wporgslug']);
				} else {
					$vinstalllink = admin_url('update.php')."?action=wordquest_plugin_install&plugin=".$vrelease['slug'];
					$vinstalllink = wp_nonce_url($vinstalllink, 'plugin-upload');
				}

				if ($vinstalllink) {
					echo "<input type='hidden' name='".$vrelease['slug']."-install-link' value='".$vinstalllink."'>";
					echo "<center><a href='".$vinstalllink."' class='button-primary'>".wqhelper_translate('Install Now')."</a></center>";
				} else {
					$vpluginlink = $wqurls['wq'].'/plugins/'.$vrelease['slug'];
					echo "<center><a href='".$vpluginlink."' class='button-primary' target=_blank>&rarr; ".wqhelper_translate('Plugin Home')."</a></center>";
				}
			} else {echo "<center>".wqhelper_translate('Expected').": ".date('jS F Y',strtotime($vrelease['releasedate']));}
			echo "</td></tr></table>";
			echo '</table></div></div>';
		}

		// WordQuest Feed
		// --------------
		$boxid = 'wordquestfeed'; $boxtitle = wqhelper_translate('WordQuest News');
		if (function_exists('wqhelper_dashboard_feed_widget')) {
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;">';
				wqhelper_dashboard_feed_widget();
			echo '</div></div>';
		}

		// Editors Picks
		// -------------
		$boxid = 'recommendations'; $boxtitle = wqhelper_translate('Editor Picks');
		// TODO: Recommended Plugins via Plugin Review?
		// echo '<div id="'.$boxid.'" class="postbox">';
		// echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
		// echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;"><table>';
		// 	echo "Recommended Plugins...";
		//	print_r($vrecommended);
		// echo '</table></div></div>';

		// PluginReview Feed
		// -----------------
		$boxid = 'pluginreviewfeed'; $boxtitle = wqhelper_translate('Plugin Reviews');
		if (function_exists('wqhelper_pluginreview_feed_widget')) {
			echo '<div id="'.$boxid.'" class="postbox">';
			echo '<h2 class="hndle" onclick="togglemetabox(\''.$boxid.'\');"><span>'.$boxtitle.'</span></h2>';
			echo '<div class="inside" id="'.$boxid.'-inside" style="margin-bottom:0;">';
				wqhelper_pluginreview_feed_widget();
			echo '</div></div>';
		}

	// end column
	echo "</div>";

	// queue feed javascript
	if (!has_action('admin_footer', 'wqhelper_dashboard_feed_javascript')) {
		add_action('admin_footer', 'wqhelper_dashboard_feed_javascript');
	}

 };
}

// Version Specific Admin Styles
// -----------------------------
$vfuncname = 'wqhelper_admin_styles_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {
	// Hide Wordquest plugin freemius submenu items if top level admin menu not open
	echo "<style>#toplevel_page_wordquest a.wp-first-item:after {content: ' Alliance';}
	#toplevel_page_wordquest.wp-not-current-submenu .fs-submenu-item
		{display: none; line-height: 0px; height: 0px;}
    #toplevel_page_wordquest li.wp-first-item {margin-bottom: 5px; margin-left: -10px;}
    span.fs-submenu-item.fs-sub {display: none;}
	.current span.fs-submenu-item.fs-sub {display: block;}
	#wpfooter {display:none !important;}
    </style>";
 };
}

// Version Specific Admin Script
// -----------------------------
$vfuncname = 'wqhelper_admin_scripts_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {
 	// wordquest admin submenu icon and styling fixes
	echo "<script>function wordquestsubmenufix(slug,iconurl,current) {
	jQuery('li a').each(function() {
		position = this.href.indexOf('admin.php?page='+slug);
		if (position > -1) {
			linkref = this.href.substr(position);
			jQuery(this).css('margin-left','10px');
			if (linkref == 'admin.php?page='+slug) {
				jQuery('<img src=\"'+iconurl+'\" style=\"float:left;\">').insertBefore(this);
				jQuery(this).css('margin-top','-3px');
			} else {if (current == 1) {
				if (linkref == 'admin.php?page='+slug+'-account') {jQuery(this).addClass('current');}
				if (linkref == 'admin.php?page='+slug+'-pricing') {jQuery(this).addClass('current');}
				if (linkref == 'admin.php?page='+slug+'-contact') {jQuery(this).addClass('current');}
				if (linkref == 'admin.php?page='+slug+'-wp-support-forum') {jQuery(this).addClass('current');}
				jQuery(this).css('margin-top','-3px');
			} else {jQuery(this).css('margin-top','-10px');} }
		}
	});
	}</script>";
 };
}

// Install a WordQuest Plugin
// --------------------------
// 1.6.5: hook to update.php update-custom_{ACTION} where ACTION = 'wordquest_plugin_install'
add_action('update-custom_wordquest_plugin_install','wqhelper_install_plugin');

$vfuncname = 'wqhelper_install_plugin_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {

	global $wqurls;

	// check permissions and nonce
	if (!current_user_can('upload_plugins')) {
		wp_die( wqhelper_translate('Sorry, you are not allowed to install plugins on this site.') );
	}
	check_admin_referer('plugin-upload');

	// get the package info from download server
	if (!isset($_REQUEST['plugin'])) {wp_die( wqhelper_translate('Error: No Plugin specified.') );}
	$vpluginslug = $_REQUEST['plugin'];
	// 1.5.9: sanitize plugin slug
	$vpluginslug = sanitize_title($vpluginslug);
	if ($vpluginslug == '') {wp_dir( wqhelper_translate('Error: Invalid Plugin slug specified.') );}

	$vurl = $wqurls['wq'].'/downloads/?action=get_metadata&slug='.$vpluginslug;
	$vresponse = wp_remote_get($vurl,array('timeout' => 30));
	if (!is_wp_error($vresponse)) {
		if ($vresponse['response']['code'] == '404') {
			// try to get package info from stored transient data
			$vplugininfo = get_transient('wordquest_plugin_info');
			if (is_array($vpluginfo)) {
				foreach ($vplugininfo as $vplugin) {
					if ($vplugin['slug'] == $vpluginslug) {$vpluginpackage = $vplugin['package'];}
				}
			}
		} else {$vpluginpackage = json_decode($vresponse['body'],true);}
	}

	if (!isset($vpluginpackage)) {
		if (is_ssl()) {$vtryagainurl = 'https://';} else {$vtryagainurl = 'http://';}
		$vtryagainurl .= $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
		wp_die( wqhelper_translate('Failed to retrieve download package information.').' <a href="'.$vtryagainurl.'">'.wqhelper_translate('Try again?').'</a>' );
	}

	// 1.6.5: pass the package download URL to Wordpress to do the rest

	// set the Plugin_Installer_Skin arguments
	// ---------------------------------------
	$url = $vpluginpackage['download_url'];
	$title = sprintf( wqhelper_translate('Installing Plugin from URL: %s'), esc_html($url) );
	$nonce = 'plugin-upload';
	$type = 'web';
	$args = compact('type', 'title', 'nonce', 'url');

	// custom Plugin_Upgrader (via /wp-admin/upgrade.php)
	// --------------------------------------------------

	$title = wqhelper_translage('Upload Plugin');
	$parent_file = 'plugins.php'; $submenu_file = 'plugin-install.php';
	require_once(ABSPATH . 'wp-admin/admin-header.php');

	$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( $args ) );
	$result = $upgrader->install( $url );

	include(ABSPATH . 'wp-admin/admin-footer.php');

 };
}


// ------------------------
// === Sidebar FloatBox ===
// ------------------------

// Main Floatbox Function
// ----------------------
$vfuncname = 'wqhelper_sidebar_floatbox_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function($vargs) {

	global $wqdebug, $wqurls;
	if ($wqdebug) {echo "<!-- Sidebar Args: "; print_r($vargs); echo " -->";}

	if (count($vargs) == 7) {
		// the old way, sending all args individually
		$vpre = $vargs[0]; $vpluginslug = $vargs[1]; $vfreepremium = $vargs[2];
		$vwporgslug = $vargs[3]; $vsavebutton = $vargs[4];
		$vplugintitle = $vargs[5]; $vpluginversion = $vargs[6];
	} else {
		// the new way, just sending two args
		$vpluginslug = $vslug = $vargs[0];
		$vsavebutton = $vargs[1];

		// get the args using the slug and global array
		global $wordquestplugins;
		$vpluginversion = $wordquestplugins[$vslug]['version'];
		$vplugintitle = $wordquestplugins[$vslug]['title'];
		$vpre = $wordquestplugins[$vslug]['settings'];
		$vfreepremium = $wordquestplugins[$vslug]['plan'];
		$vwporg = $wordquestplugins[$vslug]['wporg'];

		if (isset($wordquestplugins[$vslug]['wporgslug'])) {
			$vwporgslug = $wordquestplugins[$vslug]['wporgslug'];
		} else {$vwporgslug = '';}

		if ($wqdebug) {echo "<!-- Sidebar Plugin Info: "; print_r($wordquestplugins[$vslug]); echo "-->";}
	}

	// 1.5.0: get/convert to single array of plugin sidebar options
	// 1.6.0: fix to sidebar options variable
	$sidebaroptions = get_option($vpre.'_sidebar_options');
	if ( ($sidebaroptions == '') || (!is_array($sidebaroptions)) ) {
		$sidebaroptions['installdate'] = date('Y-m-d');
		$sidebaroptions['adsboxoff'] = get_option($vpre.'_ads_box_off');
		$sidebaroptions['donationboxoff'] = get_option($vpre.'_donation_box_off');
		$sidebaroptions['reportboxoff'] = get_option($vpre.'_report_box_off');
		delete_option($vpre.'_ads_box_off'); delete_option($vpre.'_donation_box_off'); delete_option($vpre.'_report_box_off');
		add_option($vpre.'_sidebar_options', $sidebaroptions);
	}
	// 1.6.9: fix to possible undefined keys
	if (!isset($sidebaroptions['installdate'])) {$sidebaroptions['installdate'] = date('Y-m-d');}
	if (!isset($sidebaroptions['adsboxoff'])) {$sidebaroptions['adsboxoff'] = '';}
	if (!isset($sidebaroptions['donationboxoff'])) {$sidebaroptions['donationboxoff'] = '';}
	if (!isset($sidebaroptions['reportboxoff'])) {$sidebaroptions['reportboxoff'] = '';}

	echo "<script language='javascript' type='text/javascript'>
	function hidesidebarsaved() {document.getElementById('sidebarsaved').style.display = 'none';}
	function doshowhidediv(divname) {
		if (document.getElementById(divname).style.display == 'none') {document.getElementById(divname).style.display = '';}
		else {document.getElementById(divname).style.display = 'none';}
		if (typeof sticky_in_parent === 'function') {jQuery(document.body).trigger('sticky_kit:recalc');}
	}</script>";

	// Floatbox Styles
	echo '<style>#floatdiv {margin-top:20px;} .inside {font-size:9pt; line-height:1.6em; padding:0px;}
	#floatdiv a {text-decoration:none;} #floatdiv a:hover {text-decoration:underline;}
	#floatdiv .stuffbox {background-color:#FFFFFF; margin-bottom:10px; padding-bottom:10px; text-align:center; width:25%;}
	#floatdiv .stuffbox .inside {padding:0 3px;} .stuffbox h3 {margin:10px 0; background-color:#FAFAFA; font-size:12pt;}
	</style>';

	echo '<div id="floatdiv" class="floatbox">';
	if ($wqdebug) {echo '<!-- WQ Helper Loaded From: '.dirname(__FILE__).' -->';}

	// Call (optional) Plugin Sidebar Header
	$vfuncname = $vpre.'_sidebar_plugin_header';
	if (function_exists($vfuncname)) {call_user_func($vfuncname);}

	// Save Settings Button
	// --------------------
	if ($vsavebutton != 'replace') {

		echo '<div id="savechanges"><div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		echo '<h3>'.wqhelper_translate('Update Settings').'</h3><div class="inside"><center>';

		if ($vsavebutton == 'yes') {
			$vbuttonoutput = "<script>function sidebarsavepluginsettings() {jQuery('#plugin-settings-save').trigger('click');}</script>";
			$vbuttonoutput .= "<table><tr>";
			$vbuttonoutput .= "<td align='center'><input id='sidebarsavebutton' onclick='sidebarsavepluginsettings();' type='button' class='button-primary' value='Save Settings'></td>";
			$vbuttonoutput .= "<td width='30'></td>";
			$vbuttonoutput .= "<td><div style='line-height:1em;'><font style='font-size:8pt;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='doshowhidediv(\"sidebarsettings\");hidesidebarsaved();'>".wqhelper_translate('Sidebar')."<br>";
			$vbuttonoutput .= wqhelper_translate('Options')."</a></font></div></td>";
			$vbuttonoutput .= "</tr></table>";
			$vbuttonoutput = apply_filters('wordquest_sidebar_save_button', $vbuttonoutput);
			echo $vbuttonoutput;
		}
		elseif ($vsavebutton == 'no') {echo "";}
		else {echo "<div style='line-height:1em;text-align:center;'><font style='font-size:8pt;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='doshowhidediv(\"sidebarsettings\");hidesidebarsaved();'>".wqhelper_translate('Sidebar Options')."</a></font></div>";}

		echo "<div id='sidebarsettings' style='display:none;'><br>";

			global $wordquesthelper;
			echo "<form action='".admin_url('admin-ajax.php')."' target='savesidebar' method='post'>";
			// 1.6.5: added nonce field
			wp_nonce_field($vpre.'_sidebar');
			echo "<input type='hidden' name='action' value='wqhelper_update_sidebar_boxes'>";
			// 1.6.0: added version matching form field
			echo "<input type='hidden' name='wqhv' value='".$wordquesthelper."'>";
			echo "<input type='hidden' name='sidebarprefix' value='".$vpre."'>";
			echo "<table><tr><td align='center'>";
			echo "<b>".wqhelper_translate('I rock! I have made a donation.')."</b><br>(".wqhelper_translate('hides donation box').")</td><td width='10'></td>";
			echo "<td align='center'><input type='checkbox' name='".$vpre."_donation_box_off' value='checked'";
			if ($sidebaroptions['donationboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
			echo "</td></tr>";

			echo "<tr><td align='center'>";
			echo "<b>".wqhelper_translate("I've got your report, you")."<br>".wqhelper_translate('can stop bugging me now.')." :-)</b><br>(".wqhelper_translate('hides report box').")</td><td width='10'></td>";
			echo "<td align='center'><input type='checkbox' name='".$vpre."_report_box_off' value='checked'";
			if ($sidebaroptions['reportboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
			echo "</td></tr>";

			echo "<tr><td align='center'>";
			echo "<b>".wqhelper_translate('My site is so awesome it')."<br>"._("doesn't need any more quality")."<br>".wqhelper_translate('plugin recommendations').".</b><br>(".wqhelper_translate('hides sidebar ads.').")</td><td width='10'></td>";
			echo "<td align='center'><input type='checkbox' name='".$vpre."_ads_box_off' value='checked'";
			// 1.6.5: fix to undefined index warning
			if ($sidebaroptions['adsboxoff'] == 'checked') {echo " checked>";} else {echo ">";}
			echo "</td></tr></table><br>";

			echo "<center><input type='submit' class='button-secondary' value='".wqhelper_translate('Save Sidebar Options')."'></center></form><br>";
			echo "<iframe src='javascript:void(0);' name='savesidebar' id='savesidebar' width='200' height='200' style='display:none;'></iframe>";

			echo "<div id='sidebarsaved' style='display:none;'>";
			echo "<table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
			echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>";
			echo wqhelper_translate('Sidebar Options Saved.')."</font></div></td></tr></table></div>";

		echo "</div></center>";

		echo '</div></div></div>';
	}

	// Donation Box
	// ------------
	// for Free Version? Or Upgrade Link?
	$vargs = array($vpre, $vpluginslug);
	echo '<div id="donate"';
	if ($sidebaroptions['donationboxoff'] == 'checked') {echo " style='display:none;'>";} else {echo ">";}
	if ($vfreepremium == 'free') {
		echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		echo '<h3>'.wqhelper_translate('Gifts of Appreciation').'</h3><div class="inside">';
		wqhelper_sidebar_paypal_donations($vargs);
		wqhelper_sidebar_testimonial_box($vargs);
		if ($vwporgslug != '') {
			echo "<a href='".$wqurls['wp']."/support/plugin/'".$vwporgslug."'/reviews/?rate=5#postform' target='_blank'>";
			echo "&#9733; ".wqhelper_translate('Rate this Plugin on Wordpress.Org')."</a></center>";
		}
		// elseif ($vpluginslug == 'bioship') {
			// 1.5.0: add star rating for theme (when in repository)
			// echo "<a href='https://wordpress.org/support/view/theme-reviews/bioship#postform' target='_blank'>";
			// echo "&#9733; ".wqhelper_translate('Rate this Theme on Wordpress.Org')."</a></center>";
		// }
		echo '</div></div>';
	} elseif ($vfreepremium == 'premium') {
		echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
		echo '<h3>'.wqhelper_translate('Testimonials').'</h3><div class="inside">';
			wqhelper_sidebar_testimonial_box($vargs);
		echo '</div></div>';
	}
	echo '</div>';

	// Bonus Subscription Form
	// -----------------------
	// ...populated for current user...
	global $current_user; $current_user = wp_get_current_user();
	$vuseremail = $current_user->user_email; if (strstr($vuseremail, '@localhost')) {$vuseremail = '';}
	$vuserid = $current_user->ID; $vuserdata = get_userdata($vuserid);
	$vusername = $vuserdata->first_name; $vlastname = $vuserdata->last_name;
	if ($vlastname != '') {$vusername .= ' '.$vlastname;}

	if ($vpluginslug == 'bioship') {$vreportimage = get_template_directory_uri()."/images/rv-report.jpg";}
	else {$vreportimage = plugins_url('images/rv-report.jpg', __FILE__);}
	echo '<div id="bonusoffer"';
	if (get_option($vpre.'_report_box_off') == 'checked') {echo " style='display:none;'>";} else {echo ">";}
	echo '<div class="stuffbox" style="width:250px;background-color:#ffffff;">';
	echo '<h3>'.wqhelper_translate('Bonus Offer').'</h3><div class="inside">';
	echo "<center><table cellpadding='0' cellspacing='0'><tr><td align='center'><img src='".$vreportimage."' width='60' height='80'><br>";
	echo "<font style='font-size:6pt;'><a href='".$wqurls['prn']."/return-visitors-report/' target=_blank>".wqhelper_translate('learn more')."...</a></font></td><td width='7'></td>";
	echo "<td align='center'><b><font style='color:#ee0000;font-size:9pt;'>Maximize Sales Conversions:</font><br><font style='color:#0000ee;font-size:10pt;'>The Return Visitors Report</font></b><br>";
	echo "<form style='margin-top:7px;' action='".$wqurls['prn']."/?visitorfunnel=join' target='_blank' method='post'>";
	echo "<input type='hidden' name='source' value='".$vpluginslug."-sidebar'>";
	echo "<input placeholder='".wqhelper_translate('Your Email')."...' type='text' style='width:150px;font-size:9pt;' name='subemail' value='".$vuseremail."'><br>";
	echo "<table><tr><td><input placeholder='".wqhelper_translate('Your Name')."...' type='text' style='width:90px;font-size:9pt;' name='subname' value='".$vusername."'></td>";
	echo "<td><input type='submit' class='button-secondary' value='".wqhelper_translate('Get it!')."'></td></tr></table>";
	echo "</td></tr></table></form></center>";
	echo '</div></div></div>';

	// PluginReview.Net Plugin Ad
	// --------------------------
	if ($sidebaroptions['adsboxoff'] != 'checked') {
		echo '<div id="pluginads">';
		echo '<div class="stuffbox" style="width:250px;">';
		echo '<h3>'.wqhelper_translate('Recommended').'</h3><div class="inside">';
		echo "<script language='javascript' src='".$wqurls['prn']."/recommends/?s=yes&a=majick&c=".$vpluginslug."&t=sidebar'></script>";
		echo '</div></div></div>';
	}

	// Call Plugin Footer Function
	// ---------------------------
	$vfuncname = $vpre.'_sidebar_plugin_footer';
	if (function_exists($vfuncname)) {call_user_func($vfuncname);}
	else {
		// Default Sidebar Plugin Footer
		// -----------------------------
		// also allow for theme not plugin...
		if ($vpluginslug == 'bioship') {
			$viconurl = get_template_directory_uri().'/images/wordquest.png';
			$vpluginurl = $wqurls['bio'];
			$vpluginfootertitle = wqhelper_translate('Theme Info');
		} else {
			$viconurl = plugins_url("images/wordquest.png", __FILE__);
			$vpluginurl = $wqurls['wq']."/plugins/".$vpluginslug."/";
			$vpluginfootertitle = wqhelper_translate('Plugin Info');
		}
		echo '<div id="pluginfooter"><div class="stuffbox" style="width:250px;background-color:#ffffff;"><h3>'.$vpluginfootertitle.'</h3><div class="inside">';
		echo "<center><table><tr>";
		echo "<td><a href='".$wqurls['wq']."/' target='_blank'><img src='".$viconurl."' border=0></a></td></td>";
		echo "<td width='14'></td>";
		echo "<td><a href='".$vpluginurl."' target='_blank'>".$vplugintitle."</a> <i>v".$vpluginversion."</i><br>";
		echo "by <a href='".$wqurls['wq']."/' target='_blank'>WordQuest Alliance</a><br>";
		echo "<a href='".$wqurls['wq']."/plugins/' target='_blank'><b>&rarr; ".wqhelper_translate('More Cool Plugins')."</b></a><br>";
		echo "<a href='".$wqurls['prn']."/directory/' target='_blank'>&rarr; ".wqhelper_translate('Plugin Directory')."</a></td>";
		echo "</tr></table></center>";
		echo '</div></div></div>';
	}

	echo '</div>';

	// echo '</div>';
 };
}

// ----------------
// Paypal Donations
// ----------------
$vfuncname = 'wqhelper_sidebar_paypal_donations_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function($vargs) {

	global $wqurls;

	$vpre = $vargs[0]; $vpluginslug = $vargs[1];
	if (function_exists($vpre.'_donations_special_top')) {
		$vfuncname = $vpre.'_donations_special_top';
		call_user_func($vfuncname);
	}

	// make display name from the plugin slug
	if (strstr($vpluginslug, '-')) {
		$vparts = explode('-', $vpluginslug);
		$vi = 0;
		foreach ($vparts as $vpart) {
			if ($vpart == 'wp') {$vparts[$vi] = 'WP';}
			else {$vparts[$vi] = strtoupper(substr($vpart, 0, 1)).substr($vpart, 1, (strlen($vpart)-1));}
			$vi++;
		}
		$vpluginname = implode(' ', $vparts);
	}
	else {
		$vpluginname = strtoupper(substr($vpluginslug, 0, 1)).substr($vpluginslug, 1, (strlen($vpluginslug)-1));
	}


	echo "<script language='javascript' type='text/javascript'>
	function showrecurringform() {
		document.getElementById('recurradio').checked = true;
		document.getElementById('onetimedonation').style.display = 'none';
		document.getElementById('recurringdonation').style.display = '';
	}
	function showonetimeform() {
		document.getElementById('onetimeradio').checked = true;
		document.getElementById('recurringdonation').style.display = 'none';
		document.getElementById('onetimedonation').style.display = '';
	}
	function switchperiodoptions() {
		var selectelement = document.getElementById('recurperiod');
		var recurperiod = selectelement.options[selectelement.selectedIndex].value;
		if ( (recurperiod == 'Weekly') || (recurperiod == 'W') ) {
			document.getElementById('periodoptions').innerHTML = document.getElementById('weeklyamounts').innerHTML;
			var monthlyselected = document.getElementById('monthlyselected').value;
			var weeklyselected = monthlyselected++;
			var selectelement = document.getElementById('periodoptions');
			selectelement.selectedIndex = weeklyselected;
		}
		if ( (recurperiod == 'Monthly') || (recurperiod == 'M') ) {
			document.getElementById('periodoptions').innerHTML = document.getElementById('monthlyamounts').innerHTML;
			var weeklyselected = document.getElementById('weeklyselected').value;
			var monthlyselected = weeklyselected--;
			var selectelement = document.getElementById('periodoptions')
			selectelement.selectedIndex = monthlyselected;
		}
	}
	function storeamount() {
		var selectelement = document.getElementById('recurperiod');
		var recurperiod = selectelement.options[selectelement.selectedIndex].value;
		var selectelement = document.getElementById('periodoptions');
		var selected = selectelement.selectedIndex;
		if ( (recurperiod == 'Weekly') || (recurperiod == 'W') ) {
			document.getElementById('weeklyselected').value = selected;
		}
		if ( (recurperiod == 'Monthly') || (recurperiod == 'M') ) {
			document.getElementById('monthlyselected').value = selected;
		}
	}
	</script>";

	$vnotifyurl = $wqurls['wq'].'/?estore_pp_ipn=process';
	$vsandbox = ''; // $vsandbox = 'sandbox.';

	// recurring / one-time switcher
	echo "<center><table cellpadding='0' cellspacing='0'><tr><td>";
	echo "<input name='donatetype' id='recurradio' type='radio' onclick='showrecurringform();' checked> <a href='javascript:void(0);' onclick='showrecurringform();' style='text-decoration:none;'>".wqhelper_translate('Supporter')."</a> ";
	echo "</td><td width='10'></td><td>";
	echo "<input name='donatetype' id='onetimeradio' type='radio' onclick='showonetimeform();'> <a href-'javascript:void(0);' onclick='showonetimeform();' style='text-decoration:none;'>".wqhelper_translate('One Time')."</a>";
	echo "</td></tr></table></center>";

	// 1.5.0: weekly amounts
	echo '<div style="display:none;"><input type="hidden" id="weeklyselected" value="3">
	<select name="wp_eStore_subscribe" id="weeklyamounts" style="font-size:8pt;" size="1">
	<optgroup label="'.wqhelper_translate('Supporter Amount').'">
	<option value="1">'.wqhelper_translate('Copper').': $1 </option>
	<option value="3">'.wqhelper_translate('Bronze').': $2</option>
	<option value="5">'.wqhelper_translate('Silver').': $4</option>
	<option value="7">'.wqhelper_translate('Gold').': $5</option>
	<option value="9">'.wqhelper_translate('Platinum').': $7.50</option>
	<option value="11">'.wqhelper_translate('Titanium').': $10</option>
	<option value="13">'.wqhelper_translate('Star Ruby').': $12.50</option>
	<option value="15">'.wqhelper_translate('Star Topaz').': $15</option>
	<option value="17">'.wqhelper_translate('Star Emerald').': $17.50</option>
	<option value="19">'.wqhelper_translate('Star Sapphire').': $20</option>
	<option value="21">'.wqhelper_translate('Star Diamond').': $25</option>
	</select></div>';

	// 1.5.0: monthly amounts
	echo '<div style="display:none;"><input type="hidden" id="monthlyselected" value="3">
	<select name="wp_eStore_subscribe" id="monthlyamounts" style="font-size:8pt;" size="1">
	<optgroup label="'.wqhelper_translate('Supporter Amount').'">
	<option value="2">'.wqhelper_translate('Copper').': $5</option>
	<option value="4">'.wqhelper_translate('Bronze').': $10</option>
	<option value="6">'.wqhelper_translate('Silver').': $15</option>
	<option value="9" selected="selected">'.wqhelper_translate('Gold').': $20</option>
	<option value="10">'.wqhelper_translate('Platinum').': $30</option>
	<option value="12">'.wqhelper_translate('Titanium').': $40</option>
	<option value="14">'.wqhelper_translate('Star Ruby').': $50</option>
	<option value="16">'.wqhelper_translate('Star Topaz').': $60</option>
	<option value="18">'.wqhelper_translate('Star Emerald').': $70</option>
	<option value="20">'.wqhelper_translate('Star Sapphire').': $80</option>
	<option value="22">'.wqhelper_translate('Star Diamond').': $100</option>
	</select></div>';

	// recurring form
	// $wqurls['wq'].'/?wp_eStore_subscribe=LEVEL&c_input='.$vpluginslug;

	if ($vpluginslug == 'bioship') {$vdonateimage = get_template_directory_uri()."/images/pp-donate.jpg";}
	else {$vdonateimage = plugins_url("/images/pp-donate.jpg", __FILE__);}
	echo '
		<center><form id="recurringdonation" method="GET" action="'.$wqurls['wq'].'" target="_blank">
		<input type="hidden" name="c_input" value="'.$vpluginslug.'">
		<select name="wp_eStore_subscribe" style="font-size:10pt;" size="1" id="periodoptions" onchange="storeamount();">
		<optgroup label="'.wqhelper_translate('Supporter Amount').'">
		<option value="1">'.wqhelper_translate('Copper').': $1 </option>
		<option value="3">'.wqhelper_translate('Bronze').': $2</option>
		<option value="5">'.wqhelper_translate('Silver').': $4</option>
		<option value="7" selected="selected">'.wqhelper_translate('Gold').': $5</option>
		<option value="9">'.wqhelper_translate('Platinum').': $7.50</option>
		<option value="11">'.wqhelper_translate('Titanium').': $10</option>
		<option value="13">'.wqhelper_translate('Ruby').': $12.50</option>
		<option value="15">'.wqhelper_translate('Topaz').': $15</option>
		<option value="17">'.wqhelper_translate('Emerald').': $17.50</option>
		<option value="19">'.wqhelper_translate('Sapphire').': $20</option>
		<option value="21">'.wqhelper_translate('Diamond').': $25</option>
		</select>
		</td><td width="5"></td><td>
		<select name="t3" style="font-size:10pt;" id="recurperiod" onchange="switchperiodoptions()">
		<option selected="selected" value="W">'.wqhelper_translate('Weekly').'</option>
		<option value-"M">'.wqhelper_translate('Monthly').'</option>
		</select></tr></table>
		<input type="image" src="'.$vdonateimage.'" border="0" name="I1">
		</center></form>';

	// $wqurls['wq'].'/?wp_eStore_donation=23&var1_price=AMOUNT&c_input='.$vpluginslug;
	echo '
	<center><form id="onetimedonation" style="display:none;" method="GET" action="'.$wqurls['wq'].'" target="_blank">
		<input type="hidden" name="wp_eStore_donation" value="23">
		<input type="hidden" name="c_input" value="'.$vpluginslug.'">
		<select name="var1_price" style="font-size:10pt;" size="1">
		<option selected value="">'.wqhelper_translate('Select Gift Amount').'</option>
		<option value="5">$5 - '.wqhelper_translate('Buy me a Cuppa').'</option>
		<option value="10">$10 - '.wqhelper_translate('Log a Feature Request').'</option>
		<option value="20">$20 - '.wqhelper_translate('Support a Minor Bugfix').'</option>
		<option value="50">$50 - '.wqhelper_translate('Support a Minor Update').'</option>
		<option value="100">$100 - '.wqhelper_translate('Support a Major Bugfix/Update').'</option>
		<option value="250">$250 - '.wqhelper_translate('Support a Minor Feature').'</option>
		<option value="500">$500 - '.wqhelper_translate('Support a Major Feature').'</option>
		<option value="1000">$1000 - '.wqhelper_translate('Support a New Plugin').'</option>
		<option value="">'.wqhelper_translate('Be Unique: Enter Custom Amount').'</option>
		</select>
		<input type="image" src="'.$vdonateimage.'" border="0" name="I1">
		</center></form>
	';

	if (function_exists($vpre.'_donations_special_bottom')) {
		$vfuncname = $vpre.'_donations_special_bottom';
		call_user_func($vfuncname);
	}
 };
}

// ---------------
// Testimonial Box
// ---------------
$vfuncname = 'wqhelper_sidebar_testimonial_box_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function($vargs) {

	global $wqurls, $current_user; $current_user = wp_get_current_user();
	$vuseremail = $current_user->user_email; $vuserid = $current_user->ID;
	$vuserdata = get_userdata($vuserid);
	$vusername = $vuserdata->first_name;
	$vlastname = $vuserdata->last_name;
	if ($vlastname != '') {$vusername .= ' '.$vlastname;}

	$vpre = $vargs[0]; $vpluginslug = $vargs[1];
	$vpluginslug = str_replace('-', '', $vpluginslug);
	echo "<script language='javascript' type='text/javascript'>
	function showhidetestimonialbox() {
		if (document.getElementById('sendtestimonial').style.display == '') {
			document.getElementById('sendtestimonial').style.display = 'none';
		}
		else {
			document.getElementById('sendtestimonial').style.display = '';
			document.getElementById('testimonialbox').style.display = 'none';
		}
	}
	function submittestimonial() {
		document.getElementById('testimonialbox').style.display='';
		document.getElementById('sendtestimonial').style.display='none';
	}</script>";

	echo "<center><a href='javascript:void(0);' onclick='showhidetestimonialbox();'>".wqhelper_translate('Send me a thank you or testimonial.')."</a><br>";
	echo "<div id='sendtestimonial' style='display:none;' align='center'>";
	echo "<center><form action='".$wqurls['wq']."' method='post' target='testimonialbox' onsubmit='submittestimonial();'>";
	echo "<b>".wqhelper_translate('Your Testimonial').":</b><br>";
	echo "<textarea rows='5' cols='25' name='message'></textarea><br>";
	echo "<label for='testimonial_sender'>".wqhelper_translate('Your Name').":</label> ";
	echo "<input type='text' placeholder='".wqhelper_translate('Your Name')."... (".wqhelper_translate('optional').")' style='width:200px;' name='testimonial_sender' value='".$vusername."'><br>";
	echo "<input type='text' placeholder='".wqhelper_translate('Your Website')."... (".wqhelper_translate('optional').")' style='width:200px;' name='testimonial_website' value=''><br>";
	echo "<input type='hidden' name='sending_plugin_testimonial' value='yes'>";
	echo "<input type='hidden' name='for_plugin' value='".$vpluginslug."'>";
	echo "<input type='submit' class='button-secondary' value='".wqhelper_translate('Send Testimonial')."'>";
	echo "</form>";
	echo "</div>";
	echo "<iframe name='testimonialbox' id='testimonialbox' frameborder='0' src='javascript:void(0);' style='display:none;' width='250' height='50' scrolling='no'></iframe>";
 };
}

// ---------------------
// Save Sidebar Settings
// ---------------------
// !! caller exception !! uses form matching version function
$vfuncname = 'wqhelper_update_sidebar_options_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {
	$vpre = $_REQUEST['sidebarprefix'];
	if (current_user_can('manage_options')) {

		// 1.6.5: check nonce field
		check_admin_referer($vpre.'_sidebar');

		// 1.5.0: convert to single array of plugin sidebar options
		$vsidebaroptions = get_option($vpre.'_sidebar_options');
		if (!$vsidebaroptions) {$vsidebaroptions = array('installdate' => date('Y-m-d'));}
		$vsidebaroptions['adsboxoff'] = '';	$vsidebaroptions['donationboxoff'] = ''; $vsidebaroptions['reportboxoff'] = '';
		if ( (isset($_POST[$vpre.'_ads_box_off'])) && ($_POST[$vpre.'_ads_box_off'] == 'checked') ) {$vsidebaroptions['adsboxoff'] = 'checked';}
		if ( (isset($_POST[$vpre.'_donation_box_off'])) && ($_POST[$vpre.'_donation_box_off'] == 'checked') ) {$vsidebaroptions['donationboxoff'] = 'checked';}
		if ( (isset($_POST[$vpre.'_report_box_off'])) && ($_POST[$vpre.'_report_box_off'] == 'checked') ) {$vsidebaroptions['reportboxoff'] = 'checked';}
		update_option($vpre.'_sidebar_options', $vsidebaroptions);
		// print_r($vsidebaroptions); // debug point

		// Javascript Show/Hide Callbacks
		echo "<script language='javascript' type='text/javascript'>";
		echo PHP_EOL."if (parent.document.getElementById('donate')) {";
		if ($vsidebaroptions['donationboxoff'] == 'checked') {echo "parent.document.getElementById('donate').style.display = 'none';}";}
		else {echo "parent.document.getElementById('donate').style.display = '';}";}
		echo PHP_EOL."if (parent.document.getElementById('bonusoffer')) {";
		if ($vsidebaroptions['reportboxoff'] == 'checked') {echo "parent.document.getElementById('bonusoffer').style.display = 'none';}";}
		else {echo "parent.document.getElementById('bonusoffer').style.display = '';}";}
		echo PHP_EOL."if (parent.document.getElementById('pluginads')) {";
		if ($vsidebaroptions['adsboxoff'] == 'checked') {echo "parent.document.getElementById('pluginads').style.display = 'none';}";}
		else {echo "parent.document.getElementById('pluginads').style.display = '';}";}
		echo PHP_EOL."parent.document.getElementById('sidebarsaved').style.display = ''; ";
		echo PHP_EOL."parent.document.getElementById('sidebarsettings').style.display = 'none'; ";
		echo "</script>";

		// maybe call Special Update Options
		$vfuncname = $vpre.'_update_sidebar_options_special';
		if (function_exists($vfuncname)) {call_user_func($vfuncname);}
	}
	exit;
 };
}

// ---------------------
// Sticky Kit Javascript
// ---------------------
$vfuncname = 'wqhelper_sidebar_stickykitscript_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {
return '<script>/* Sticky-kit v1.1.2 | WTFPL | Leaf Corcoran 2015 | http://leafo.net */
(function(){var b,f;b=this.jQuery||window.jQuery;f=b(window);b.fn.stick_in_parent=function(d){var A,w,J,n,B,K,p,q,k,E,t;null==d&&(d={});t=d.sticky_class;B=d.inner_scrolling;E=d.recalc_every;k=d.parent;q=d.offset_top;p=d.spacer;w=d.bottoming;null==q&&(q=0);null==k&&(k=void 0);null==B&&(B=!0);null==t&&(t="is_stuck");A=b(document);null==w&&(w=!0);J=function(a,d,n,C,F,u,r,G){var v,H,m,D,I,c,g,x,y,z,h,l;if(!a.data("sticky_kit")){a.data("sticky_kit",!0);I=A.height();g=a.parent();null!=k&&(g=g.closest(k));
if(!g.length)throw"failed to find stick parent";v=m=!1;(h=null!=p?p&&a.closest(p):b("<div />"))&&h.css("position",a.css("position"));x=function(){var c,f,e;if(!G&&(I=A.height(),c=parseInt(g.css("border-top-width"),10),f=parseInt(g.css("padding-top"),10),d=parseInt(g.css("padding-bottom"),10),n=g.offset().top+c+f,C=g.height(),m&&(v=m=!1,null==p&&(a.insertAfter(h),h.detach()),a.css({position:"",top:"",width:"",bottom:""}).removeClass(t),e=!0),F=a.offset().top-(parseInt(a.css("margin-top"),10)||0)-q,
u=a.outerHeight(!0),r=a.css("float"),h&&h.css({width:a.outerWidth(!0),height:u,display:a.css("display"),"vertical-align":a.css("vertical-align"),"float":r}),e))return l()};x();if(u!==C)return D=void 0,c=q,z=E,l=function(){var b,l,e,k;if(!G&&(e=!1,null!=z&&(--z,0>=z&&(z=E,x(),e=!0)),e||A.height()===I||x(),e=f.scrollTop(),null!=D&&(l=e-D),D=e,m?(w&&(k=e+u+c>C+n,v&&!k&&(v=!1,a.css({position:"fixed",bottom:"",top:c}).trigger("sticky_kit:unbottom"))),e<F&&(m=!1,c=q,null==p&&("left"!==r&&"right"!==r||a.insertAfter(h),
h.detach()),b={position:"",width:"",top:""},a.css(b).removeClass(t).trigger("sticky_kit:unstick")),B&&(b=f.height(),u+q>b&&!v&&(c-=l,c=Math.max(b-u,c),c=Math.min(q,c),m&&a.css({top:c+"px"})))):e>F&&(m=!0,b={position:"fixed",top:c},b.width="border-box"===a.css("box-sizing")?a.outerWidth()+"px":a.width()+"px",a.css(b).addClass(t),null==p&&(a.after(h),"left"!==r&&"right"!==r||h.append(a)),a.trigger("sticky_kit:stick")),m&&w&&(null==k&&(k=e+u+c>C+n),!v&&k)))return v=!0,"static"===g.css("position")&&g.css({position:"relative"}),
a.css({position:"absolute",bottom:d,top:"auto"}).trigger("sticky_kit:bottom")},y=function(){x();return l()},H=function(){G=!0;f.off("touchmove",l);f.off("scroll",l);f.off("resize",y);b(document.body).off("sticky_kit:recalc",y);a.off("sticky_kit:detach",H);a.removeData("sticky_kit");a.css({position:"",bottom:"",top:"",width:""});g.position("position","");if(m)return null==p&&("left"!==r&&"right"!==r||a.insertAfter(h),h.remove()),a.removeClass(t)},f.on("touchmove",l),f.on("scroll",l),f.on("resize",
y),b(document.body).on("sticky_kit:recalc",y),a.on("sticky_kit:detach",H),setTimeout(l,0)}};n=0;for(K=this.length;n<K;n++)d=this[n],J(b(d));return this}}).call(this);</script>';
 };
} // '

// ---------------------
// Float Menu Javascript
// ---------------------
$vfuncname = 'wqhelper_sidebar_floatmenuscript_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {

	return "
	<style>.floatbox {position:absolute;width:250px;top:30px;right:15px;z-index:100;}</style>

	<script language='javascript' type='text/javascript'>
	/* Script by: www.jtricks.com
	 * Version: 1.8 (20111103)
	 * Latest version: www.jtricks.com/javascript/navigation/floating.html
	 *
	 * License:
	 * GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
	 */
	var floatingMenu =
	{
		hasInner: typeof(window.innerWidth) == 'number',
		hasElement: typeof(document.documentElement) == 'object'
		&& typeof(document.documentElement.clientWidth) == 'number'
	};

	var floatingArray =
	[
	];

	floatingMenu.add = function(obj, options)
	{
		var name;  var menu;
		if (typeof(obj) === 'string') name = obj; else menu = obj;
		if (options == undefined) {
		floatingArray.push( {id: name, menu: menu, targetLeft: 0, targetTop: 0, distance: .07, snap: true});
		}
		else  {
		floatingArray.push(
			{id: name, menu: menu, targetLeft: options.targetLeft, targetRight: options.targetRight,
			targetTop: options.targetTop, targetBottom: options.targetBottom, centerX: options.centerX,
			centerY: options.centerY, prohibitXMovement: options.prohibitXMovement,
			prohibitYMovement: options.prohibitYMovement, distance: options.distance != undefined ? options.distance : .07,
			snap: options.snap, ignoreParentDimensions: options.ignoreParentDimensions, scrollContainer: options.scrollContainer,
			scrollContainerId: options.scrollContainerId
			});
		}
	};

	floatingMenu.findSingle = function(item) {
		if (item.id) item.menu = document.getElementById(item.id);
		if (item.scrollContainerId) item.scrollContainer = document.getElementById(item.scrollContainerId);
	};

	floatingMenu.move = function (item) {
		if (!item.prohibitXMovement) {item.menu.style.left = item.nextX + 'px'; item.menu.style.right = '';}
		if (!item.prohibitYMovement) {item.menu.style.top = item.nextY + 'px'; item.menu.style.bottom = '';}
	};

	floatingMenu.scrollLeft = function(item) {
		// If floating within scrollable container use it's scrollLeft
		if (item.scrollContainer) return item.scrollContainer.scrollLeft;
		var w = window.top; return this.hasInner ? w.pageXOffset : this.hasElement
		  ? w.document.documentElement.scrollLeft : w.document.body.scrollLeft;
	};
	floatingMenu.scrollTop = function(item) {
		// If floating within scrollable container use it's scrollTop
		if (item.scrollContainer)
		return item.scrollContainer.scrollTop;
		var w = window.top; return this.hasInner ? w.pageYOffset : this.hasElement
		  ? w.document.documentElement.scrollTop : w.document.body.scrollTop;
	};
	floatingMenu.windowWidth = function() {
		return this.hasElement ? document.documentElement.clientWidth : document.body.clientWidth;
	};
	floatingMenu.windowHeight = function() {
		if (floatingMenu.hasElement && floatingMenu.hasInner) {
		// Handle Opera 8 problems
		return document.documentElement.clientHeight > window.innerHeight
			? window.innerHeight : document.documentElement.clientHeight
		}
		else {
		return floatingMenu.hasElement ? document.documentElement.clientHeight : document.body.clientHeight;
		}
	};
	floatingMenu.documentHeight = function() {
		var innerHeight = this.hasInner ? window.innerHeight : 0;
		var body = document.body, html = document.documentElement;
		return Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight,
		html.scrollHeight, html.offsetHeight, innerHeight);
	};
	floatingMenu.documentWidth = function() {
		var innerWidth = this.hasInner ? window.innerWidth : 0;
		var body = document.body, html = document.documentElement;
		return Math.max(body.scrollWidth, body.offsetWidth, html.clientWidth, html.scrollWidth, html.offsetWidth,
		innerWidth);
	};
	floatingMenu.calculateCornerX = function(item) {
		var offsetWidth = item.menu.offsetWidth;
		if (item.centerX)
		return this.scrollLeft(item) + (this.windowWidth() - offsetWidth)/2;
		var result = this.scrollLeft(item) - item.parentLeft;
		if (item.targetLeft == undefined) {result += this.windowWidth() - item.targetRight - offsetWidth;}
		else {result += item.targetLeft;}
		if (document.body != item.menu.parentNode && result + offsetWidth >= item.confinedWidthReserve)
		{result = item.confinedWidthReserve - offsetWidth;}
		if (result < 0) result = 0;
		return result;
	};
	floatingMenu.calculateCornerY = function(item) {
		var offsetHeight = item.menu.offsetHeight;
		if (item.centerY) return this.scrollTop(item) + (this.windowHeight() - offsetHeight)/2;
		var result = this.scrollTop(item) - item.parentTop;
		if (item.targetTop === undefined) {result += this.windowHeight() - item.targetBottom - offsetHeight;}
		else {result += item.targetTop;}

		if (document.body != item.menu.parentNode && result + offsetHeight >= item.confinedHeightReserve) {
		result = item.confinedHeightReserve - offsetHeight;
		}

		if (result < 0) result = 0;
		return result;
	};
	floatingMenu.computeParent = function(item) {
		if (item.ignoreParentDimensions) {
		item.confinedHeightReserve = this.documentHeight(); item.confinedWidthReserver = this.documentWidth();
		item.parentLeft = 0; item.parentTop = 0; return;
		}
		var parentNode = item.menu.parentNode; var parentOffsets = this.offsets(parentNode, item);
		item.parentLeft = parentOffsets.left; item.parentTop = parentOffsets.top;
		item.confinedWidthReserve = parentNode.clientWidth;

		// We could have absolutely-positioned DIV wrapped
		// inside relatively-positioned. Then parent might not
		// have any height. Try to find parent that has
		// and try to find whats left of its height for us.
		var obj = parentNode; var objOffsets = this.offsets(obj, item);
		while (obj.clientHeight + objOffsets.top < item.menu.offsetHeight + parentOffsets.top) {
		obj = obj.parentNode; objOffsets = this.offsets(obj, item);
		}
		item.confinedHeightReserve = obj.clientHeight - (parentOffsets.top - objOffsets.top);
	};
	floatingMenu.offsets = function(obj, item)
	{
		var result = {left: 0, top: 0};
		if (obj === item.scrollContainer) return;
		while (obj.offsetParent && obj.offsetParent != item.scrollContainer) {
		result.left += obj.offsetLeft; result.top += obj.offsetTop; obj = obj.offsetParent;
		}
		if (window == window.top) return result;

		// we are IFRAMEd
		var iframes = window.top.document.body.getElementsByTagName('IFRAME');
		for (var i = 0; i < iframes.length; i++)
		{
		if (iframes[i].contentWindow != window) continue;
		obj = iframes[i];
		while (obj.offsetParent) {
			result.left += obj.offsetLeft; result.top += obj.offsetTop; obj = obj.offsetParent;
		}
		}
		return result;
	};
	floatingMenu.doFloatSingle = function(item) {
		this.findSingle(item); var stepX, stepY; this.computeParent(item);
		var cornerX = this.calculateCornerX(item); var stepX = (cornerX - item.nextX) * item.distance;
		if (Math.abs(stepX) < .5 && item.snap || Math.abs(cornerX - item.nextX) == 1) {
		stepX = cornerX - item.nextX;
		}
		var cornerY = this.calculateCornerY(item);
		var stepY = (cornerY - item.nextY) * item.distance;
		if (Math.abs(stepY) < .5 && item.snap || Math.abs(cornerY - item.nextY) == 1) {
		stepY = cornerY - item.nextY;
		}
		if (Math.abs(stepX) > 0 || Math.abs(stepY) > 0) {
		item.nextX += stepX; item.nextY += stepY; this.move(item);
		}
	};
	floatingMenu.fixTargets = function() {};
	floatingMenu.fixTarget = function(item) {};
	floatingMenu.doFloat = function() {
		this.fixTargets();
		for (var i=0; i < floatingArray.length; i++) {
		this.fixTarget(floatingArray[i]); this.doFloatSingle(floatingArray[i]);
		}
		setTimeout('floatingMenu.doFloat()', 20);
	};
	floatingMenu.insertEvent = function(element, event, handler) {
		// W3C
		if (element.addEventListener != undefined) {
		element.addEventListener(event, handler, false); return;
		}
		var listener = 'on' + event;
		// MS
		if (element.attachEvent != undefined) {
		element.attachEvent(listener, handler);
		return;
		}
		// Fallback
		var oldHandler = element[listener];
		element[listener] = function (e) {
			e = (e) ? e : window.event;
			var result = handler(e);
			return (oldHandler != undefined)
			&& (oldHandler(e) == true)
			&& (result == true);
		};
	};

	floatingMenu.init = function() {
		floatingMenu.fixTargets();
		for (var i=0; i < floatingArray.length; i++) {
		floatingMenu.initSingleMenu(floatingArray[i]);
		}
		setTimeout('floatingMenu.doFloat()', 100);
	};
	// Some browsers init scrollbars only after
	// full document load.
	floatingMenu.initSingleMenu = function(item) {
		this.findSingle(item); this.computeParent(item); this.fixTarget(item); item.nextX = this.calculateCornerX(item);
		item.nextY = this.calculateCornerY(item); this.move(item);
	};
	floatingMenu.insertEvent(window, 'load', floatingMenu.init);

	// Register ourselves as jQuery plugin if jQuery is present
	if (typeof(jQuery) !== 'undefined') {
		(function ($) {
		$.fn.addFloating = function(options) {
			return this.each(function() {
			floatingMenu.add(this, options);
			});
		};
		}) (jQuery);
	}
	</script>";
 };
}


// =====================
// Dashboard Feed Widget
// =====================

// Add the Dashboard Feed Widget
// -----------------------------
$vrequesturi = $_SERVER['REQUEST_URI'];
if ( (preg_match('|index.php|i', $vrequesturi))
  || (substr($vrequesturi, -(strlen('/wp-admin/'))) == '/wp-admin/')
  || (substr($vrequesturi, -(strlen('/wp-admin/network'))) == '/wp-admin/network/') ) {
	if (!has_action('wp_dashboard_setup', 'wqhelper_add_dashboard_feed_widget')) {
		add_action('wp_dashboard_setup', 'wqhelper_add_dashboard_feed_widget');
	}
}

// Load the Dashboard Feeds
// ------------------------
$vfuncname = 'wqhelper_add_dashboard_feed_widget_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {
	global $wp_meta_boxes, $current_user;
	if ( (current_user_can('manage_options')) || (current_user_can('install_plugins')) ) {
		// 1.6.1: fix to undefined index warning
		$vwordquestloaded = false; $vpluginreviewloaded = false;
		foreach (array_keys($wp_meta_boxes['dashboard']['normal']['core']) as $vname) {
			if ($vname == 'wordquest') {$vwordquestloaded = true;}
			if ($vname == 'pluginreview') {$vpluginreviewloaded = true;}
		}
		if (!$vwordquestloaded) {
			wp_add_dashboard_widget('wordquest', 'WordQuest Alliance', 'wqhelper_dashboard_feed_widget');
		}
		if (!$vpluginreviewloaded) {
			wp_add_dashboard_widget('pluginreview', 'Plugin Review Network', 'wqhelper_pluginreview_feed_widget');
		}

		// add the dashboard feed javascript (once only)
		if (!has_action('admin_footer', 'wqhelper_dashboard_feed_javascript')) {
			add_action('admin_footer', 'wqhelper_dashboard_feed_javascript');
		}
	}
 };
}

// WordQuest Dashboard Feed Javascript
// -----------------------------------
$vfuncname = 'wqhelper_dashboard_feed_javascript_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {
	echo "<script language='javascript' type='text/javascript'>
	function doloadfeedcat(namespace,siteurl) {
		var selectelement = document.getElementById(namespace+'catselector');
		var catslug = selectelement.options[selectelement.selectedIndex].value;
		var siteurl = encodeURIComponent(siteurl);
		document.getElementById('feedcatloader').src='admin-ajax.php?action=wqhelper_load_feed_category&category='+catslug+'&namespace='+namespace+'&siteurl='+siteurl;
	}</script>";
	echo "<iframe src='javascript:void(0);' id='feedcatloader' style='display:none;'></iframe>";
 };
}

// WordQuest Dashboard Feed Widget
// -------------------------------
$vfuncname = 'wqhelper_dashboard_feed_widget_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {

	// maybe Get Latest Release info
	// -----------------------------
	global $wqdebug, $wqreleases, $wqurls;
	$vlatestrelease = ''; $vnextrelease = '';
	if (isset($wqreleases)) {
		if (isset($wqreleases['latest'])) {$vlatestrelease = $wqreleases['latest'];}
		if (isset($wqreleases['next'])) {$vnextrelease = $wqreleases['next'];}
	} else {
		$vpluginsinfo = wqhelper_get_plugin_info();
		if (is_array($vpluginsinfo)) {
			foreach ($vpluginsinfo as $vplugin) {
				if (isset($vplugin['slug'])) {
					if ( ( (isset($vplugin['latestrelease'])) && ($vplugin['latestrelease'] == 'yes') )
					    || ( (isset($vplugin['nextrelease'])) && ($vplugin['nextrelease'] == 'yes') ) ) {
						$vplugininfo = $vplugin; $vplugins = get_plugins(); $vplugininfo['installed'] = 'no';
						foreach ($vplugins as $vpluginfile => $vvalues) {
							if ($vplugininfo['slug'] == sanitize_title($vvalues['Name'])) {$vplugininfo['installed'] = 'yes';}
						}
					}
					if ( (isset($vplugin['latestrelease'])) && ($vplugin['latestrelease'] == 'yes') ) {$vlatestrelease = $vplugininfo;}
					if ( (isset($vplugin['nextrelease'])) && ($vplugin['nextrelease'] == 'yes') ) {$vnextrelease = $vplugininfo;}
				}
			}
		}
	}
	// echo "<!-- Latest Release: "; print_r($vlatestrelease); echo " -->";
 	// echo "<!-- Next Release: "; print_r($vnextrelease); echo " -->";


	// maybe Display Latest Release Info
	// ---------------------------------
	if ( (isset($_REQUEST['page'])) && ($_REQUEST['page'] == 'wordquest') ) {
		// do not duplicate here as already output for wordquest page
	} elseif ( (isset($vlatestrelease)) && (is_array($vlatestrelease)) && ($vlatestrelease['installed'] == 'no') ) {
		echo "<b>".wqhelper_translate('Latest Plugin Release')."</b><br>";
		echo "<table><tr><td align='center'><img src='".$vlatestrelease['icon']."' width='75' height='75'><br>";
		echo "<a href='".$vlatestrelease['home']."' target=_blank><b>".$vlatestrelease['title']."</b></a></td>";
		echo "<td width='10'></td><td><span style='font-size:9pt;'>".$vlatestrelease['description']."</span><br><br>";
		if ( (isset($vlatestrelease['package'])) && (is_array($vlatestrelease['package'])) ) {
			// 1.6.6: check for wordpress.org only installs
			global $wordpressorgonly; $vinstalllink = false;
			if ( ($wordpressorgonly) && ($vwqplugin['wporgslug']) ) {
				$vinstalllink = self_admin_url('update.php')."?action=install-plugin&plugin=".$vlatestrelease['wporgslug'];
				$vinstalllink = wp_nonce_url($vinstalllink, 'install-plugin_'.$vlatestrelease['wporgslug']);
			} else {
				admin_url('update.php').'?action=wordquest_plugin_install&plugin='.$vlatestrelease['slug'];
				$vinstalllink = wp_nonce_url($vinstalllink, 'plugin-upload');
			}
			if ($vinstalllink) {
				echo "<input type='hidden' name='".$vlatestrelease['slug']."-install-link' value='".$vinstalllink."'>";
				echo "<center><a href='".$vinstalllink."' class='button-primary'>".wqhelper_translate('Install Now')."</a></center>";
			} else {
				$vpluginlink = $wqurls['wq'].'/plugins/'.$vlatestrelease['slug'];
				echo "<center><a href='".$vpluginlink."' class='button-primary' target=_blank>&rarr; ".wqhelper_translate('Plugin Home')."</a></center>";
			}
		}
		echo "</td></tr></table><br>";
	} elseif ( (isset($vnextrelease)) && (is_array($vnextrelease)) ) {
		echo "<b>".wqhelper_translate('Upcoming Plugin Release')."</b><br>";
		echo "<table><tr><td align='center'><img src='".$vnextrelease['icon']."' width='75' height='75'><br>";
		echo "<a href='".$vnextrelease['home']."' target=_blank><b>".$vnextrelease['title']."</b></a></td>";
		echo "<td width='10'></td><td><span style='font-size:9pt;'>".$vnextrelease['description']."</span><br><br>";
		$vreleasetime = strtotime($vnextrelease['releasedate']);
		echo "<center><span style='font-size:9pt;'>".wqhelper_translate('Expected').": ".date('jS F Y', $vreleasetime)."</span></center>";
		echo "</td></tr></table><br>";
	}

	echo "<style>.feedlink {text-decoration:none;} .feedlink:hover {text-decoration:underline;}</style>";

	// WordQuest Posts Feed
	// --------------------
	$vrssurl = $wqurls['wq']."/category/guides/feed/";
	if ($wqdebug) {$vfeed = ''; delete_transient('wordquest_guides_feed');}
	else {$vfeed = trim(get_transient('wordquest_guides_feed'));}

	if ( (!$vfeed) || ($vfeed == '') ) {
		$vrssfeed = fetch_feed($vrssurl); $vfeeditems = 4;
		$vargs = array($vrssfeed, $vfeeditems);
		$vfeed = wqhelper_process_rss_feed($vargs);
		if ($vfeed != '') {set_transient('wordquest_guides_feed', $vfeed, (24*60*60));}
	}

	echo "<div id='wordquestguides'>";
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['wq']."/category/guides/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	echo "<b><a href='".$wqurls['wq']."/category/guides/' class='feedlink' target=_blank>".wqhelper_translate('Latest WordQuest Guides')."</a></b><br>";
	if ($vfeed != '') {echo $vfeed;} else {echo wqhelper_translate('Feed Currently Unavailable.'); delete_transient('wordquest_guides_feed');}
	echo "</div>";

	// WordQuest Solutions Feed
	// ------------------------
	$vrssurl = $wqurls['wq']."/quest/feed/";
	if ($wqdebug) {$vfeed = ''; delete_transient('wordquest_quest_feed');}
	else {$vfeed = trim(get_transient('wordquest_quest_feed'));}

	if ( (!$vfeed) || ($vfeed == '') ) {
		$vrssfeed = fetch_feed($vrssurl); $vfeeditems = 4;
		$vargs = array($vrssfeed, $vfeeditems);
		$vfeed = wqhelper_process_rss_feed($vargs);
		if ($vfeed != '') {set_transient('wordquest_quest_feed', $vfeed, (24*60*60));}
	}

	echo "<div id='wordquestsolutions'>";
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['wq']."/solutions/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	echo "<b><a href='".$wqurls['wq']."/solutions/' class='feedlink' target=_blank>".wqhelper_translate('Latest Solution Quests')."</a></b>";
	if ($vfeed != '') {echo $vfeed;} else {echo wqhelper_translate('Feed Currently Unavailable.'); delete_transient('wordquest_quest_feed');}
	echo "</div>";

	return;

	// --------------------------
	// currently not implented...

	// Category Feed Selection
	// -----------------------
	$vpluginsurl = $wqurls['wq']."/?get_post_categories=yes";

	if ($wqdebug) {$vcategorylist = ''; delete_transient('wordquest_feed_cats');}
	else {$vcategorylist = trim(get_transient('wordquest_feed_cats'));}

	if ( (!$vcategorylist) || ($vcategorylist == '') ) {
		$vargs = array('timeout' => 10);
		$vgetcategorylist = wp_remote_get($vpluginsurl, $vargs);
		if (!is_wp_error($vgetcategorylist)) {
			$vcategorylist = $vgetcategorylist['body'];
			if ($vcategorylist) {set_transient('wordquest_feed_cats', $vcategorylist, (24*60*60));}
		}
	}

	if (strstr($vcategorylist, "::::")) {
		$vcategories = explode("::::", $vcategorylist);
		if (count($vcategories) > 0) {
			$vi = 0;
			foreach ($vcategories as $vcategory) {
				$vcatinfo = explode("::", $vcategory);
				$vcats[$vi]['name'] = $vcatinfo[0];
				$vcats[$vi]['slug'] = $vcatinfo[1];
				$vcats[$vi]['count'] = $vcatinfo[2];
				$vi++;
			}

			if (count($vcats) > 0) {
				echo "<table><tr><td><b>".wqhelper_translate('Category').":</b></td>";
				echo "<td width='7'></td>";
				echo "<td><select id='wqcatselector' onchange='doloadfeedcat(\"wq\",\"".$wqurls['wq']."\");'>";
				// echo "<option value='news' selected='selected'>WordQuest News</option>";
				foreach ($vcats as $vcat) {
					echo "<option value='".$vcat['slug']."'";
					if ($vcat['slug'] == 'news') {echo " selected='selected'";}
					echo ">".$vcat['name']." (".$vcat['count'].")</option>";
				}
				echo "</select></td></tr></table>";
				echo "<div id='wqfeeddisplay'></div>";
			}
		}
	}
 };
}

// Plugin Review Network Feed Widget
// ---------------------------------
$vfuncname = 'wqhelper_pluginreview_feed_widget_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {

	echo "<style>.feedlink {text-decoration:none;} .feedlink:hover {text-decoration:underline;}</style>";

	// Latest Plugins Feed
	// -------------------
	global $wqdebug, $wqurls;
	$vrssurl = $wqurls['prn']."/feed/";
	if ($wqdebug) {$vfeed = ''; delete_transient('pluginreview_newest_feed');}
	else {$vfeed = trim(get_transient('pluginreview_newest_feed'));}

	if ( (!$vfeed) || ($vfeed == '') ) {
		$vrssfeed = fetch_feed($vrssurl); $vfeeditems = 4;
		$vargs = array($vrssfeed, $vfeeditems);
		$vfeed = wqhelper_process_rss_feed($vargs);
		if ($vfeed != '') {set_transient('pluginreview_newest_feed', $vfeed, (24*60*60));}
	}

	echo "<center><b><a href='".$wqurls['prn']."/directory/' class='feedlink' style='font-size:11pt;' target=_blank>";
	echo wqhelper_translate('NEW', 'bioship').' '.wqhelper_translate('Plugin Directory')." - ".wqhelper_translate('by Category')."!</a></b></center><br>";

	echo "<div id='pluginslatest'>";
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['prn']."/directory/latest/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	if ($vfeed != '') {echo "<b>".wqhelper_translate('Latest Plugin Releases')."</b><br>".$vfeed;}
	else {echo wqhelper_translate('Feed Currently Unavailable'); delete_transient('prn_feed');}
	echo "</div>";

	// return; // temp

	// Recently Updated Feed
	// ---------------------
	$vrssurl = $wqurls['prn']."/feed/?orderby=modified";
	if ($wqdebug) {$vfeed = ''; delete_transient('pluginreview_updated_feed');}
	else {$vfeed = trim(get_transient('pluginreview_updated_feed'));}

	if ( (!$vfeed) || ($vfeed == '') ) {
		$vrssfeed = fetch_feed($vrssurl); $vfeeditems = 4;
		$vargs = array($vrssfeed, $vfeeditems);
		$vfeed = wqhelper_process_rss_feed($vargs);
		if ($vfeed != '') {set_transient('pluginreview_updated_feed', $vfeed, (24*60*60));}
	}

	echo "<div id='pluginsupdated'>";
	echo "<div style='float:right;'>&rarr;<a href='".$wqurls['prn']."/directory/updated/' class='feedlink' target=_blank> ".wqhelper_translate('More')."...</a></div>";
	if ($vfeed != '') {echo "<b>".wqhelper_translate('Recently Updated Plugins')."</b><br>".$vfeed;}
	else {echo wqhelper_translate('Feed Currently Unavailable'); delete_transient('prn_feed');}
	echo "</div>";

	return;

	// --------------------------
	// currently not implented...

	// Category Feed Selection
	// -----------------------
	$vcategoryurl = $wqurls['prn']."/?get_review_categories=yes";

	// refresh once a day only to limit downloads
	if ($wqdebug) {$vcategorylist = ''; delete_transient('prn_feed_cats');}
	else {$vcategorylist = trim(get_transient('prn_feed_cats'));}

	if ( (!$vcategorylist) || ($vcategorylist == '') ) {
		$vargs = array('timeout' => 10);
		$vgetcategorylist = wp_remote_get($vcategoryurl, $vargs);
		if (!is_wp_error($vgetcategorylist)) {
			$vcategorylist = $vgetcategorylist['body'];
			if ($vcategorylist) {set_transient('prn_feed_cats', $vcategorylist, (24*60*60));}
		}
	}

	if (strstr($vcategorylist, "::::")) {
		$vcategories = explode("::::", $vcategorylist);
		if (count($vcategories) > 0) {
			$vi = 0;
			foreach ($vcategories as $vcategory) {
				$vcatinfo = explode("::", $vcategory);
				$vcats[$vi]['name'] = $vcatinfo[0];
				$vcats[$vi]['slug'] = $vcatinfo[1];
				$vcats[$vi]['count'] = $vcatinfo[2];
				$vi++;
			}

			if (count($vcats) > 0) {
				echo "<table><tr><td><b>".wqhelper_translate('Category').":</b></td>";
				echo "<td width='7'></td>";
				echo "<td><select id='prncatselector' onchange='doloadfeedcat(\"prn\",\"".$wqurls['prn']."\");'>";
				// echo "<option value='reviews' selected='selected'>".wqhelper_translate('Plugin Reviews')."</option>";
				foreach ($vcats as $vcat) {
					echo "<option value='".$vcat['slug']."'";
					if ($vcat['slug'] == 'reviews') {echo " selected='selected'";}
					echo ">".$vcat['name']." (".$vcat['count'].")</option>";
				}
				echo "</select></td></tr></table>";
				echo "<div id='prnfeeddisplay'></div>";
			}
		}
	}
 };
}

// Load a Category Feed
// --------------------
$vfuncname = 'wqhelper_load_feed_category_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function() {

	$vnamespace = $_GET['namespace'];
	$vbaseurl = $_GET['siteurl'];
	$vcatslug = $_GET['category'];

	$vcategoryurl = $vbaseurl."/category/".$vcatslug."/feed/";
	$vmorelink = "<div align='right'>&rarr; <a href='".$vbaseurl."/category/".$vcatslug."/' style='feedlink' target=_blank> More...</a></div>";

	$vcategoryrss = @fetch_feed($vcategoryurl); $vfeeditems = 10;

	// Process the Feed
	// ----------------
	$vargs = array($vcategoryrss, $vfeeditems);
	$vcategoryfeed = wqhelper_process_rss_feed($vargs);
	if ($vcategoryfeed != '') {$vcategoryfeed .= $vmorelink;}

	echo '<script language="javascript" type="text/javascript">
	var categoryfeed = "'.$vcategoryfeed.'";
	parent.document.getElementById("'.$vnamespace.'feeddisplay").innerHTML = categoryfeed;
	</script>';

	exit;
 };
}

// Process RSS Feed
// ----------------
$vfuncname = 'wqhelper_process_rss_feed_'.$wqhv;
if ( (!isset($wqfunctions[$vfuncname])) || (!is_callable($wqfunctions[$vfuncname])) ) {
 $wqfunctions[$vfuncname] = function($vargs) {

	$vrss = $vargs[0]; $vfeeditems = $vargs[1]; $vprocessed = '';
	if (is_wp_error($vrss)) {return '';}

	$vmaxitems = $vrss->get_item_quantity($vfeeditems);
	$vrssitems = $vrss->get_items(0, $vmaxitems);

	if ($vmaxitems == 0) {$vprocessed = "";}
	else {
		$vprocessed = "<ul style='list-style:none;margin:0;text-align:left;'>";
		foreach ($vrssitems as $vitem) {
			$vprocessed .= "<li>&rarr; <a href='".esc_url($vitem->get_permalink())."' class='feedlink' target='_blank' ";
			$vprocessed .= "title='Posted ".$vitem->get_date('j F Y | g:i a')."'>";
			$vprocessed .= esc_html($vitem->get_title())."</a></li>";
		}
		$vprocessed .= "</ul>";
	}
	return $vprocessed;
 };
}


// CLOSE VERSION COMPARE WRAPPER FOR PHP 5.3 REQUIRED
// --------------------------------------------------
}

// debug point
// print_r($wqfunctions);

?>