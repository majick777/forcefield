<?php

/*
Plugin Name: ForceField
Plugin URI: http://wordquest.org/plugins/forcefield/
Author: Tony Hayes
Description: Brute Force Protection for Login, Registration, Signup, Commenting, REST API and XML RPC.
Version: 0.9.0
Author URI: http://wordquest.org/
GitHub Plugin URI: majick777/forcefield
@fs_premium_only pro-functions.php
*/

// Development TODO List
// ---------------------
// - Update: create a MySQL table for IP banlist
// - Option: role restricted REST API access
// - Option: role restricted XML RPC access ?
// - Idea: REST API: test something for route permission_callback ?
// - Idea: Better HTTP Response Codes? https://tools.ietf.org/html/rfc6585
// - Feature: admin blocklist table with unblock options ?
// - Feature: user IP unblock via ReCaptcha form ?
// - Feature: option to port banlist to server fail2ban ?
// - Feature: REST API endpoint control?
// - Feature: Woocommerce Action Tokens? (cart / checkout)?


// ==================
// === FORCEFIELD ===
// ==================

// set Plugin Values
// -----------------
global $wordquestplugins;
$vslug = $vforcefieldslug = 'forcefield';
$wordquestplugins[$vslug]['version'] = $vforcefieldversion = '0.9.0';
$wordquestplugins[$vslug]['title'] = 'ForceField';
$wordquestplugins[$vslug]['namespace'] = 'forcefield';
$wordquestplugins[$vslug]['settings'] = $vpre = 'ff';
$wordquestplugins[$vslug]['hasplans'] = false;
// $wordquestplugins[$vslug]['wporgslug'] = 'forcefield';

// Check for Update Checker
// ------------------------
// note: lack of updatechecker.php file indicates WordPress.Org SVN version
// presence of updatechecker.php indicates site download or GitHub version
$vfile = __FILE__; $vupdatechecker = dirname($vfile).'/updatechecker.php';
if (!file_exists($vupdatechecker)) {$wordquestplugins[$vslug]['wporg'] = true;}
else {include($vupdatechecker); $wordquestplugins[$vslug]['wporg'] = false;}

// Load WordQuest Helper/Pro Functions
// -----------------------------------
$wordquest = dirname(__FILE__).'/wordquest.php';
if ( (is_admin()) && (file_exists($wordquest)) ) {include($wordquest);}
$vprofunctions = dirname(__FILE__).'/pro-functions.php';
if (file_exists($vprofunctions)) {include($vprofunctions); $wordquestplugins[$vslug]['plan'] = 'premium';}
else {$wordquestplugins[$vslug]['plan'] = 'free';}

// Load Freemius
// -------------
function forcefield_freemius($vslug) {
    global $wordquestplugins, $forcefield_freemius;
    $vwporg = $wordquestplugins[$vslug]['wporg'];
	if ($wordquestplugins[$vslug]['plan'] == 'premium') {$vpremium = true;} else {$vpremium = false;}
	$vhasplans = $wordquestplugins[$vslug]['hasplans'];

	// redirect for support forum
	if ( (is_admin()) && (isset($_REQUEST['page'])) ) {
		if ($_REQUEST['page'] == $vslug.'-wp-support-forum') {
			if (!function_exists('wp_redirect')) {include(ABSPATH.WPINC.'/pluggable.php');}
			wp_redirect('http://wordquest.org/quest/quest-category/plugin-support/'.$vslug.'/'); exit;
		}
	}

    if (!isset($forcefield_freemius)) {

        // start the Freemius SDK
        if (!class_exists('Freemius')) {
        	$vfreemiuspath = dirname(__FILE__).'/freemius/start.php';
        	if (!file_exists($vfreemiuspath)) {return;}
        	require_once($vfreemiuspath);
        }

		$forcefield_settings = array(
            'id'                => '1555',
            'slug'              => $vslug,
            'type'				=> 'plugin',
            'public_key'        => 'pk_8c058d54aa8e43dbb8fd1259992ab',
            'is_premium'        => $vpremium,
            'has_addons'        => false,
            'has_paid_plans'    => $vhasplans,
            'is_org_compliant'  => $vwporg,
            'menu'              => array(
                'slug'       	=> $vslug,
                'first-path' 	=> 'admin.php?page='.$vslug.'&welcome=true',
                'parent'		=> array('slug' => 'wordquest'),
                'contact'		=> $vpremium,
                // 'support'    	=> false,
                // 'account'    	=> false,
            )
        );
        $forcefield_freemius = fs_dynamic_init($forcefield_settings);
    }
    return $forcefield_freemius;
}
// initialize Freemius
$forcefield_freemius = forcefield_freemius($vslug);

// Custom Freemius Connect Message
// -------------------------------
function forcefield_freemius_connect($message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link) {
	return sprintf(
		__fs('hey-x').'<br>'.
		__("If you want to more easily provide feedback for this plugins features and functionality, %s can connect your user, %s at %s, to %s",'forcefield'),
		$user_first_name, '<b>'.$plugin_title.'</b>', '<b>'.$user_login.'</b>', $site_link, $freemius_link
	);
}
if ( (is_object($forcefield_freemius)) && (method_exists($forcefield_freemius, 'add_filter')) ) {
	$forcefield_freemius->add_filter('connect_message', 'forcefield_freemius_connect', WP_FS__DEFAULT_PRIORITY, 6);
}


// ------------------
// Check IP Blocklist
// ------------------

global $vffblocklist, $vffipaddress;
$vffblocklist = get_option('forcefield_blocklist');
$vffipaddress = forcefield_get_remote_ip();

if (is_array($vffblocklist)) {

	// note: defaults to not expire any IP blocks at all
	// can be filtered to an expiry time difference in seconds (eg. 1 day = 24*60*60)
	$vexpireblock = absint(apply_filters('forcefield_block_expiration', false));

	if ($vexpireblock && ($vexpireblock > 0) ) {
		if (count($vffblocklist) > 0) {
			$vfoundexpired = true;
			foreach ($vffblocklist as $vblockedip) {
				$vinfo = $vffblocklist[$vffipaddress];
				$vdata = explode(':', $vinfo);
				$vdiff = time() - $vdata[1];
				if ($vdiff > $vexpireblock) {
					unset($vffblocklist[$vffipaddress]);
					$vfoundexpired = true;
				}
			}
			if ($vfoundexpired) {update_option('forcefield_blocklist', $vffblocklist);}
		}
	}

	if (array_key_exists($vffipaddress, $vffblocklist)) {

		// TODO: check for unblock request via reCaptcha form here?

		$vblockrequest = true;

		if ($vblockrequest) {

			// TODO: maybe allow for a manual IP unblocking request via reCaptcha form here?

			header("HTTP/1.1 403 Forbidden");
			header('Status: 403 Forbidden');
			header('Connection: Close');

			exit;
		}
	}
}

// ===============
// PLUGIN SETTINGS
// ===============

// get Plugin Settings
// -------------------
global $vforcefield; $vforcefield = get_option('forcefield');

// TEMP: use default settings
$vforcefield = forcefield_get_default_settings();

// get Default Settings
// --------------------
function forcefield_get_default_settings() {

	return array(

		/* Login */
		'login_token' => 'yes',
		'login_authban' => 'yes',
		'login_norefban' => 'yes',
		'login_requiressl' => 'no',
		'login_nohints' => 'no',

		/* Administrator */
		'admin_block' => 'yes',
		'admin_autodelete' => 'no',
		'admin_whitelist' => '',
		'admin_alert' => 'yes',
		'admin_email' => '',

		/* Registration */
		'register_token' => 'yes',
		'register_authban' => 'yes',
		'register_norefban' => 'yes',
		'register_requiressl' => 'no',

		/* Blog Signup (Multisite) */
		'signup_token' => 'yes',
		'signup_authban' => 'yes',
		'signup_norefban' => 'yes',
		'signup_requiressl' => 'no',

		/* Lost Password */
		'lostpass_token' => 'yes',
		'lostpass_authban' => 'yes',
		'lostpass_norefban' => 'yes',
		'lostpass_requiressl' => 'no',

		/* Comments */
		'comment_token' => 'yes',
		'comment_authban' => 'yes',
		'comment_norefban' => 'yes',
		'comment_requiressl' => 'no',

		/* XML RPC */
		'xmlrpc_disable' => 'no',
		'xmlrpc_noauth' => 'yes',
		'xmlrpc_authblock' => 'yes',
		'xmlrpc_authban' => 'yes',
		'xmlrpc_requiressl' => 'no',
		'xmlrpc_slowdown' => 'yes',
		'xmlrpc_anoncomments' => 'no',
		// 'xmlrpc_restricted' => 'no',
		// 'xmlrpc_roles' => array();
		'pingbacks_disable' => 'no',
		'selfpings_disable' => 'yes',

		/* REST API */
		'restapi_disable' => 'no',
		// 'restapi_authblock' => 'yes',
		// 'restapi_authban' => 'yes',
		'restapi_requiressl' => 'no',
		'restapi_slowdown' => 'yes',
		'restapi_anoncomments' => 'no',
		// 'restapi_restricted' => 'no',
		// 'restapi_roles' => array(),
		'restapi_nojsonp' => 'no',
		'restapi_nolinks' => 'no',
		'restapi_prefix' => '',

		/* Generic Error Message */
		'error_message' => __('Authentication Error. Please try again.','forcefield'),

		/* Admin UI */
		'current_tab' => 'user-access'
	);
}

// add Defaults on Activation
// --------------------------
register_activation_hook(__FILE__, 'forcefield_add_settings');
function forcefield_add_settings() {
	global $vforcefield;
	$vforcefield = forcefield_get_default_settings();

	// set administrator alert email to current user
	$current_user = wp_get_current_user();
	$vuseremail = $current_user->user_email;
	if (strstr($vuseremail, '@localhost')) {$vuseremail = '';}
	$vforcefield['admin_email'] = $vuseremail;

	add_option('forcefield', $vforcefield);
}

// get a Forcefield Setting
// ------------------------
function forcefield_get_setting($vkey, $vfilter=false) {
	global $vforcefield;
	if (isset($vforcefield[$vkey])) {$vvalue = $vforcefield[$vkey];}
	else {
		if (!isset($vforcefielddefaults)) {
			$vforcefielddefaults = forcefield_get_default_settings();
		}
		if (isset($vforcefielddefaults[$vkey])) {$vvalue = $vforcefielddefault[$vkey];}
		else {$vvalue = null;}
	}
	$vvalue = apply_filters('forcefield_'.$vkey, $vvalue);
	return $vvalue;
}

// update Forcefield Settings
// --------------------------
add_action('init', 'forcefield_update_settings');
function forcefield_update_settings() {

	if (!isset($_POST['forcefield_update_settings'])) {return;}
	if ($_POST['forcefield_update_settings'] != 'yes') {return;}
	if (!current_user_can('manage_options')) {return;}
	check_admin_referer('forcefield_update');

	global $vforcefield;
	$vprevious = $vforcefield;
	$vdefaults = forcefield_get_default_settings();

	$voptionkeys = array(

		/* Login */
		'login_token' => 'checkbox',
		'login_authban' => 'checkbox',
		'login_norefban' => 'checkbox',
		'login_requiressl' => 'checkbox',
		'login_nohints' => 'checkbox',

		/* Register */
		'register_token' => 'checkbox',
		'register_authban' => 'checkbox',
		'register_norefban' => 'checkbox',
		'register_requiressl' => 'checkbox',

		/* Blog Signup (Multisite) */
		'signup_token' => 'checkbox',
		'signup_authban' => 'checkbox',
		'signup_norefban' => 'checkbox',
		'signup_requiressl' => 'checkbox',

		/* Lost Password */
		'lostpass_token' => 'checkbox',
		'lostpass_authban' => 'checkbox',
		'lostpass_norefban' => 'checkbox',
		'lostpass_requiressl' => 'checkbox',

		/* Comments */
		'comment_token' => 'checkbox',
		'comment_authban' => 'checkbox',
		'comment_norefban' => 'checkbox',
		'comment_requiressl' => 'checkbox',

		/* Administrator */
		'admin_block' => 'checkbox',
		'admin_whitelist' => 'usernames',
		'admin_autodelete' => 'checkbox',
		'admin_alert' => 'checkbox',
		'admin_email' => 'email',

		/* XML RPC */
		'xmlrpc_disable' => 'checkbox',
		'xmlrpc_noauth' => 'checkbox',
		'xmlrpc_authblock' => 'checkbox',
		'xmlrpc_authban' => 'checkbox',
		'xmlrpc_requiressl' => 'checkbox',
		'xmlrpc_slowdown' => 'checkbox',
		'xmlrpc_anoncomments' => 'checkbox',
		'pingbacks_disable' => 'checkbox',
		'selfpings_disable' => 'checkbox',

		/* REST API */
		'restapi_disable' => 'checkbox',
		// 'restapi_authblock' => 'checkbox',
		// 'restapi_authban' => 'checkbox',
		'restapi_requiressl' => 'checkbox',
		'restapi_slowdown' => 'checkbox',
		'restapi_anoncomments' => 'checkbox',
		// 'restapi_restricted' => 'checkbox',
		// 'restapi_roles' => 'roles',
		'restapi_nolinks' => 'checkbox',
		'restapi_nojsonp' => 'checkbox',
		'restapi_prefix' => 'specialtext',

		/* Admin UI */
		'current_tab' => 'user-access/api-access/ip-blocklist',
	);

	foreach ($voptionkeys as $vkey => $vtype) {
		$vpostkey = 'ff_'.$vkey;
		if (isset($_POST[$vpostkey])) {$vposted = $_POST[$vpostkey];} else {$vposted = '';}

		if ($vtype == 'checkbox') {
			if ($vposted == '') {$vforcefield[$vkey] = 'no';}
			elseif ($vposted == 'yes') {$vforcefield[$vkey] = 'yes';}
		} elseif (strstr($vtype, '/')) {
			$vvalid = explode('/',$vtype);
			if (in_array($vposted, $vvalid)) {$vforcefield[$vkey] = $vposted;}
			elseif (in_array($vprevious[$vkey], $vvalid)) {$vforcefield[$vkey] = $vprevious[$vkey];}
			elseif (in_array($vdefaults[$vkey], $vvalid)) {$vforcefield[$vkey] = $vdefaults[$vkey];}
			else {$vforcefield[$vkey] = $vvalid[0];}
		} elseif ($vtype == 'numeric') {
			$vposted = absint($vposted);
			if (is_numeric($vposted)) {$vforcefield[$vkey] = $vposted;}
			elseif (is_numeric($vprevious[$vkey])) {$vforcefield[$vkey] = $vprevious[$vkey];}
			elseif (is_numeric($vdefaults[$vkey])) {$vforcefield[$vkey] = $vdefaults[$vkey];}
		} elseif ($vtype == 'email') {
			$vposted = sanitize_email($vposted);
			if ($vposted) {$vforcefield[$vkey] = $vposted;}
			else {$vforcefield[$vkey] = '';}
		} elseif ($vtype == 'usernames') {
			if (strstr($vposted, ',')) {
				$vusernames = explode(',', $vposted);
				foreach ($vusernames as $vi => $vusername) {
					$vusername = trim($vusername);
					$vuser = get_user_by('login', $vusername);
					if (!$vuser) {unset($vusername[$vi]);}
				}
				if (count($vusernames) > 0) {$vforcefield[$vkey] = implode(',', $vusernames);}
				else {$vforcefield[$vkey] = '';}
			} else {
				$vposted = trim($vposted);
				$vuser = get_user_by('login', $vposted);
				if ($vuser) {$vforcefield[$vkey] = $vposted;}
			}
		} elseif ($vtype == 'specialtext') {
			$vtest = str_replace('/', '', $vposted);
			$vcheckposted = preg_match('/^[a-zA-Z0-9_]+$/', $vtest);
			if ($vcheckposted) {$vforcefield[$vkey] = $vposted;}
			else {$vforcefield[$vkey] = '';}
		}
		$vforcefield[$vkey] = $vposted;
	}

	update_option('forcefield', $vforcefield);
}

// Add Admin Settings to Menu
// --------------------------
add_action('admin_menu', 'forcefield_add_settings_menu', 1);
function forcefield_add_settings_menu() {

	// maybe add Wordquest top level menu
	if (function_exists('wqhelper_admin_page')) {
		if (empty($GLOBALS['admin_page_hooks']['wordquest'])) {
			$vicon = plugins_url('images/wordquest-icon.png',__FILE__); $vposition = apply_filters('wordquest_menu_position','3');
			add_menu_page('WordQuest Alliance', 'WordQuest', 'manage_options', 'wordquest', 'wqhelper_admin_page', $vicon, $vposition);
		}
		// ...and plugin settings submenu and style fixes
		add_submenu_page('wordquest', 'Forcefield', 'Forcefield', 'manage_options', 'forcefield', 'forcefield_options_page');
		add_action('admin_footer', 'forcefield_admin_javascript');
	} else {
		// otherwise just add a standard options page
		add_options_page('Forcefield', 'Forcefield', 'manage_options', 'forcefield', 'forcefield_options_page');
	}

	// for adding icons and styling to the plugin submenu
	function forcefield_admin_javascript() {
		global $vforcefieldslug; $vslug = $vforcefieldslug; $vcurrent = '0';
		$vicon = plugins_url('images/icon.png', __FILE__);
		if ( (isset($_REQUEST['page'])) && ($_REQUEST['page'] == $vslug) ) {$vcurrent = '1';}
		echo "<script>jQuery(document).ready(function() {if (typeof wordquestsubmenufix == 'function') {
		wordquestsubmenufix('".$vslug."','".$vicon."','".$vcurrent."');} });</script>";
	}

	// add Plugin Settings link to plugin page
	add_filter('plugin_action_links', 'forcefield_register_plugin_links', 10, 2);
	function forcefield_register_plugin_links($vlinks, $vfile) {
		global $vforcefieldslug;
		$vthisplugin = plugin_basename(__FILE__);
		if ($vfile == $vthisplugin) {
			$vsettingslink = "<a href='".admin_url('admin.php')."?page=".$vforcefieldslug."'>".__('Settings','forcefield')."</a>";
			array_unshift($vlinks, $vsettingslink);
		}
		return $vlinks;
	}

}


// Admin Settings Page
// -------------------
function forcefield_options_page() {

	global $vforcefield, $vforcefieldslug, $vforcefieldversion, $wordquestplugins;

	$vcurrenttab = forcefield_get_setting('current_tab', false);

	// start page wrap
	echo '<div id="pagewrap" class="wrap" style="width:100%;margin-right:0px !important;">'.PHP_EOL;

	// Call Plugin Sidebar
	// -------------------
	$vargs = array('forcefield', 'yes');
	if (function_exists('wqhelper_sidebar_floatbox')) {
		wqhelper_sidebar_floatbox($vargs);
		echo wqhelper_sidebar_stickykitscript();
		echo '<style>#floatdiv {float:right;}</style>';
		echo '<script>jQuery("#floatdiv").stick_in_parent();
		wrapwidth = jQuery("#pagewrap").width();
		sidebarwidth = jQuery("#floatdiv").width();
		newwidth = wrapwidth - sidebarwidth;
		jQuery("#wrapbox").css("width",newwidth+"px");
		jQuery("#adminnoticebox").css("width",newwidth+"px");
		</script>';
	}

	// Admin Notices Boxer
	// -------------------
	if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();} else {echo "<h2> </h2>";}

	echo "<div id='wrapbox' class='postbox' style='width:680px;line-height:2em;'>".PHP_EOL;
	echo "<div class='inner' style='padding-left:20px;'>".PHP_EOL;

	// Plugin Page Title
	// -----------------
	$viconurl = plugins_url("images/forcefield.png", __FILE__);
	echo "<table><tr><td><img src='".$viconurl."'></td>".PHP_EOL;
	echo "<td width='20'></td><td>".PHP_EOL;
		echo "<table><tr><td><h2>".__('ForceField','forcefield')."</h2></td>".PHP_EOL;
		echo "<td width='20'></td>".PHP_EOL;
		echo "<td><h3>v".$vforcefieldversion."</h3></td></tr>".PHP_EOL;
		echo "<tr><td colspan='3' align='center'>".__('by','forcefield');
		echo " <a href='http://wordquest.org/' style='text-decoration:none;' target=_blank><b>WordQuest Alliance</b></a>";
		echo "</td></tr></table>".PHP_EOL;
	echo "</td><td width='50'></td>".PHP_EOL;
	if ( (isset($_REQUEST['welcome'])) && ($_REQUEST['welcome'] == 'true') ) {
		echo "<td><table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>".PHP_EOL;
		echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>".PHP_EOL;
		echo __('Welcome! For usage see','forcefield')." <i>readme.txt</i> FAQ</font></div></td></tr></table></td>".PHP_EOL;
	}
	if ( (isset($_REQUEST['updated'])) && ($_REQUEST['updated'] == 'yes') ) {
		echo "<td><table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>".PHP_EOL;
		echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>".PHP_EOL;
		echo __('Settings Updated.','forcefield')."</font></div></td></tr></table></td>".PHP_EOL;
	}
	echo "</tr></table><br>".PHP_EOL;

	// admin styles
	echo "<style>.checkbox-cell {max-width:40px !important;}
	.tab-button {width:100px; height:30px; background-color:#DDDDDD;
		border:1px solid #000; text-align:center;}
	.tab-button:hover {background-color:#EEEEEE; font-weight:bold;}</style>";

	// tabbing script
	echo "<script>function showtab(tab) {
		document.getElementById('user-access').style.display = 'none';
		document.getElementById('user-actions').style.display = 'none';
		document.getElementById('api-access').style.display = 'none';
		document.getElementById('ip-blocklist').style.display = 'none';
		document.getElementById(tab).style.display = '';
		document.getElementById('user-access-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('user-actions-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('api-access-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('ip-blocklist-button').style.backgroundColor = '#DDDDDD';
		document.getElementById(tab+'-button').style.backgroundColor = '#F0F0F0';
	}</script>";

	// settings tab selector buttons
	echo '<ul style="list-style:none;">'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'user-access\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="user-access-button" class="tab-button"';
			if ($vcurrenttab == 'user-access') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('User Access','forcefield').'</div></a></li>'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'user-actions\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="user-actions-button" class="tab-button"';
			if ($vcurrenttab == 'user-actions') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('User Actions','forcefield').'</div></a></li>'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'api-access\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="api-access-button" class="tab-button"';
			if ($vcurrenttab == 'api-access') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('API Access','forcefield').'</div></a></li>'.PHP_EOL;
		// TEMP: disabled
		echo '<li style="display:none;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'ip-blocklist\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="ip-blocklist-button" class="tab-button"';
			if ($vcurrenttab == 'ip-blocklist') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('IP Blocklist','forcefield').'</div></a></li>'.PHP_EOL;
	echo '</ul>';

	// start update form
	echo '<form action="admin.php?page=forcefield&updated=yes" method="post">'.PHP_EOL;
	echo '<input type="hidden" name="forcefield_update_settings" value="yes">'.PHP_EOL;
	echo '<input type="hidden" name="current_tab" value="'.$vcurrenttab.'">'.PHP_EOL;
	wp_nonce_field('forcefield');

	// ===========
	// User Access
	// ===========
	echo '<div id="user-access"';
		if ($vcurrenttab != 'user-access') {echo ' style="display:none;"';}
	echo '><table>';

	// -----
	// Login
	// -----
	echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Login','forcefield').'</h3></td></tr>';

	// login referer check
	echo '<tr><td><b>'.__('AutoBan IP if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_norefban" value="yes"';
	if (forcefield_get_setting('login_norefban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if Login is missing HTTP Referer.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// login token
	echo '<tr><td><b>'.__('Require Login Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_token" value="yes"';
	if (forcefield_get_setting('login_token', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Automatic Token for Login.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// login auth ban
	echo '<tr><td><b>'.__('AutoBan IP if Missing Login Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_authban" value="yes"';
	if (forcefield_get_setting('login_authban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if missing Login Token.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// require SSL for login
	echo '<tr><td><b>'.__('Require SSL for Login?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_requiressl" value="yes"';
	if (forcefield_get_setting('login_requiressl', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Secure Connection for User Login.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// remove login hints
	echo '<tr><td><b>'.__('Remove Login Error Hints?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_nohints" value="yes"';
	if (forcefield_get_setting('login_nohints', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Removes Hints (Error Output) from Login Screen.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// ----------------
	// Admin Protection
	// ----------------
	echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Admin Protection','forcefield').'</h3></td></tr>';

	// list of whitelisted admin usernames
	echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>'.__('Whitelisted Admins','forcefield').'</b></td><td width="20"></td>';
	$vwhitelist = forcefield_get_setting('admin_whitelist', false);
	echo '<td colspan="5" style="vertical-align:top">';
	echo '<input type="text" name="ff_admin_whitelist" value="'.$vwhitelist.'" style="width:100%;margin-top:10px;">';
	echo '<br>'.__('Comma separated list of Whitelisted Administrator Accounts.','forcefield');
	echo '</td></tr>';

	// get all current administrator logins
	$query = new WP_User_Query(array('role' => 'Administrator', 'count_total' => false));
	$users = $query->results; $vadminlogins = array();
	foreach ($users as $user) {$vadminlogins[] = $user->data->user_login;}
	$vadminusernames = implode(', ', $vadminlogins);

	echo '<tr><td><b>'.__('Current Admin Usernames','forcefield').':</b></td><td width="20"></td>';
	echo '<td colspan="5">'.$vadminusernames.'</td></tr>';

	// block unwhitelisted Administrators
	echo '<tr><td><b>'.__('Block Unwhitelisted Admins?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_admin_block" value="yes"';
	if (forcefield_get_setting('admin_block', false) == 'yes') {echo ' checked';}
	echo '></td><td width="20"></td>';
	echo '<td>'.__('Block Administrator Logins not in Whitelist.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// send admin alert for administrator accounts
	echo '<tr><td><b>'.__('Send Admin Alert Emails?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_admin_alert" value="yes"';
	if (forcefield_get_setting('admin_alert', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td colspan="3">'.__('Send Email Alert when Unwhitelisted Admin Logs in.','forcefield').'</td></tr>';

	// administrator alert email address
	echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>'.__('Admin Alert Email','forcefield').'</b></td><td width="20"></td>';
	$vadminemail = forcefield_get_setting('admin_email', false);
	echo '<td colspan="3"><input type="text" name="ff_admin_email" value="'.$vadminemail.'" style="width:100%;margin-top:10px;"></td>';
	echo '</tr>';

	// delete unwhitelisted Administrators
	echo '<tr><td><b>'.__('AutoDelete Unwhitelisted Admins?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell" style="vertical-align:top;">';
	echo '<input type="checkbox" name="ff_admin_autodelete" value="yes"';
	if (forcefield_get_setting('admin_autodelete', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td colspan="3">'.__('AutoDelete Admin Accounts not in Whitelist.','forcefield').'<br>';
	echo __('Experimental hacker protection. Use this feature with caution.','forcefield').'<br>';
	echo __('Ensure new admin accounts are whitelisted before they login.','forcefield').'</td></tr>';

	// settings update Submit Button
	echo '<tr height="20"><td> </td></tr>';
	echo '<tr><td> </td><td width="20"></td>';
	echo '<td colspan="3" align="left">';
	echo '<input type="submit" class="button-primary" value="'.__('Update Settings','forcefield').'"></td></tr>';

	echo '</table></div>'; // close user access tab

	// ============
	// User Actions
	// ============
	echo '<div id="user-actions"';
		if ($vcurrenttab != 'user-actions') {echo ' style="display:none;"';}
	echo '><table>';

	// ------------
	// Registration
	// ------------
	echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Registration','forcefield').'</h3></td></tr>';

	// registration referer check
	echo '<tr><td><b>'.__('AutoBan IP if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_norefban" value="yes"';
	if (forcefield_get_setting('register_norefban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if Registration is missing HTTP Referer.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// registration token
	echo '<tr><td><b>'.__('Require Registration Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_token" value="yes"';
	if (forcefield_get_setting('register_token', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Automatic Token for Registration.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// missing registration token auth ban
	echo '<tr><td><b>'.__('AutoBan IP if Missing Registration Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_authban" value="yes"';
	if (forcefield_get_setting('register_authban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if missing Registration Token.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// require SSL for registration
	echo '<tr><td><b>'.__('Require SSL for Registration?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_requiressl" value="yes"';
	if (forcefield_get_setting('register_requiressl', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Secure Connection for User Registration.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// -----------------------
	// Blog Signup (Multisite)
	// -----------------------
	echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Blog Signup','forcefield').'</h3></td>';
	echo '<td> </td><td> </td><td> </td><td>'.__('Note: Affects Multisite Only','forcefield').'</td></tr>';

	// signup referer check
	echo '<tr><td><b>'.__('AutoBan IP if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_norefban" value="yes"';
	if (forcefield_get_setting('signup_norefban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if Blog Signup is missing HTTP Referer.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// signup token
	echo '<tr><td><b>'.__('Require Blog Signup Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_token" value="yes"';
	if (forcefield_get_setting('signup_token', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Automatic Token for Blog Signup.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// missing signup token auth ban
	echo '<tr><td><b>'.__('AutoBan IP if Missing Registration Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_authban" value="yes"';
	if (forcefield_get_setting('signup_authban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if missing Blog Signup Token.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// require SSL for blog signup
	echo '<tr><td><b>'.__('Require SSL for Blog Signup?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_requiressl" value="yes"';
	if (forcefield_get_setting('signup_requiressl', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Secure Connection for Blog Signup.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// ----------
	// Commenting
	// ----------
	echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Comments','forcefield').'</h3></td></tr>';

	// comment referer check
	echo '<tr><td><b>'.__('AutoBan IP if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_norefban" value="yes"';
	if (forcefield_get_setting('comment_norefban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if login is missing HTTP Referer.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// comment token
	echo '<tr><td><b>'.__('Require Comment Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td><input type="checkbox" name="ff_comment_token" value="yes"';
	if (forcefield_get_setting('comment_token', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Automatic Token for Commenting.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// comment auth ban
	echo '<tr><td><b>'.__('AutoBan IP if Missing Comment Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_authban" value="yes"';
	if (forcefield_get_setting('comment_authban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if missing Comment Token.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// require SSL for commenting
	echo '<tr><td><b>'.__('Require SSL for Commenting?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_requiressl" value="yes"';
	if (forcefield_get_setting('comment_requiressl', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require SSL for Commenting.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// -------------
	// Lost Password
	// -------------
	echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Lost Password','forcefield').'</h3></td></tr>';

	// lost password referer check
	echo '<tr><td><b>'.__('AutoBan IP if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_norefban" value="yes"';
	if (forcefield_get_setting('lostpass_norefban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if login is missing HTTP Referer.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// lost password token
	echo '<tr><td><b>'.__('Require Lost Password Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_token" value="yes"';
	if (forcefield_get_setting('lostpass_token', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Automatic Token for Lost Password form.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// lost password auth ban
	echo '<tr><td><b>'.__('AutoBan IP if Missing Lost Password Token?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_authban" value="yes"';
	if (forcefield_get_setting('lostpass_authban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Ban IP Address if missing Lost Password Token.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// require SSL for lost password
	echo '<tr><td><b>'.__('Require SSL for Lost Password?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_requiressl" value="yes"';
	if (forcefield_get_setting('lostpass_requiressl', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Require Secure Connection for Lost Password form.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// settings update Submit Button
	echo '<tr height="20"><td> </td></tr>';
	echo '<tr><td> </td><td width="20"></td>';
	echo '<td colspan="3" align="left">';
	echo '<input type="submit" class="button-primary" value="'.__('Update Settings','forcefield').'"></td></tr>';

	echo '</table></div>'; // close user action tab


	// ==========
	// API Access
	// ==========
	echo '<div id="api-access"';
		if ($vcurrenttable != 'api-access') {echo ' style="display:none;"';}
	echo '><table>';

	// -------
	// XML RPC
	// -------
	echo '<tr><td><h3 style="margin-bottom:10px;">XML RPC</h3></td></tr>';

	// disable XML RPC
	echo '<tr><td>'.__('Disable XML RPC?','forcefield').'</td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_disable" value="yes"';
	if (forcefield_get_setting('xmlrpc_disable', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Disable XML RPC Entirely.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// disable XML RPC auth attempts
	echo '<tr><td>'.__('Disable XML RPC Auth Attempts?','forcefield').'</td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authblock" value="yes"';
	if (forcefield_get_setting('xmlrpc_authblock', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Login attempts via XML RPC will be blocked.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// ban XML RPC auth attempts
	echo '<tr><td>'.__('AutoBan IP for XML RPC Auth Attempts?','forcefield').'</td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authban" value="yes"';
	if (forcefield_get_setting('xmlrpc_authban', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('ANY login attempts via XML RPC will result in an IP ban.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// require SSL
	echo '<tr><td>'.__('Require SSL for XML RPC?','forcefield').'</td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_requiressl" value="yes"';
	if (forcefield_get_setting('xmlrpc_requiressl', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Only allow XML RPC access via SSL.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	echo '<tr><td>'.__('Rate Limit XML RPC?','forcefield').'</td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_slowdown" value="yes"';
	if (forcefield_get_setting('xmlrpc_slowdown', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Slowdown XML RPC access via a Rate Limiting Delay.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// disable pingbacks
	echo '<tr><td>'.__('Disable Pingback Processing?','forcefield').'</td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_pingbacks_disable" value="yes"';
	if (forcefield_get_setting('pingbacks_disable', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Disable XML RPC Pingback processing.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('not recommended','forcefield').')</td></tr>';

	// disable self pings
	echo '<tr><td>'.__('Disable Self Pings?','forcefield').'</td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_selfpings_disable" value="yes"';
	if (forcefield_get_setting('selfpings_disable', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Disable Pingbacks from this site to itself.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// --------
	// REST API
	// --------
	echo '<tr><td><h3 style="margin-bottom:10px;">REST API</h3></td></tr>';

	// disable REST API
	echo '<tr><td><b>'.__('Disable REST API?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_disable" value="yes"';
	if (forcefield_get_setting('restapi_disable', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Disable REST API entirely.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('not recommended','forcefield').')</td></tr>';

	// logged in users only
	echo '<tr><td><b>'.__('Logged In Users Only?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="restapi_authonly" value="yes"';
	if (forcefield_get_setting('restapi_authonly', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Access to REST API for authenticated users only.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// disable REST API auth atttempts (oops! there are no such attempts)
	// echo '<tr><td><b>'.__('Disable REST API Auth Attempts?','forcefield').'</b></td><td width="20"></td>';
	// echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_authblock" value="yes"';
	// if (forcefield_get_setting('restapi_authblock', false) == 'yes') {echo ' checked';}
	// echo '></td><td width="10"></td>';
	// echo '<td>'.__('Login attempts via REST API will be blocked.','forcefield').'</td>';
	// echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// ban REST API auth attempts (oops! there are no such attempts)
	// echo '<tr><td><b>'.__('AutoBan IP for REST API Auth Attempts?','forcefield').'</b></td><td width="20"></td>';
	// echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_authban" value="yes"';
	// if (forcefield_get_setting('restapi_authban', false) == 'yes') {echo ' checked';}
	// echo '></td><td width="10"></td>';
	// echo '<td>'.__('ANY login attempts via REST API will result in an IP ban.','forcefield').'</td>';
	// echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

	// require SSL
	echo '<tr><td><b>'.__('Require SSL for REST API?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_requiressl" value="yes"';
	if (forcefield_get_setting('restapi_requiressl', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Only allow REST API access via SSL.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// rate limiting for REST
	echo '<tr><td><b>'.__('Rate Limit REST API?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_slowdown" value="yes"';
	if (forcefield_get_setting('restapi_slowdown', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Slowdown REST API access via a Rate Limiting Delay.','forcefield').'</td>';
	echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// role restrict REST API access
	// TODO: not implemented yet
	// echo '<tr><td><b>'.__('Restrict REST API Access via Roles?','forcefield').'</b></td><td width="20"></td>';
	// echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_restricted" value="yes"';
	// if (forcefield_get_setting('restapi_restricted', false) == 'yes') {echo ' checked';}
	// echo '></td><td width="10"></td>';
	// echo '<td>'.__('Restrict REST API Access to Selected Roles.','forcefield').'<br>';
	// echo __('Note: Enforces Logged In Only Access','forcefield').'</td>';
	// echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

	// roles for REST API role restriction
	// TODO: not implemented yet
	// $vrestapiroles = forcefield_get_setting('restapi_restricted', false);
	if (!is_array($vrestapiroles)) {$vrestapiroles = false;}
	// $vroles = get_roles();
	// echo '<tr><td><b>'.__('Allowed Roles for REST API Restriction','forcefield').'</b></td><td width="20"></td>';
	// echo '<td colspan="3"><ul style="list-style:none;">';
	// foreach ($vroles as $vrole) {
	// 	$vroleslug = $vrole->slug;
	//	echo "<li style="display:inline-block; margin-right:10px;">";
	//	echo "<input type='checkbox' name='restapi-role-".$vroleslug."' value='yes'";
	//		if ($vrestrictrole[$vroleslug] == 'yes') {echo " checked";}
	//	echo "> ".$vrole->label;
	// }
	// echo .'</td></tr>';

	// disable REST API Links
	echo '<tr><td><b>'.__('Disable REST API Links?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nolinks" value="yes"';
	if (forcefield_get_setting('restapi_nolinks', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Remove output of REST API Links in Page Head.','forcefield').'</td></tr>';

	// disable JSONP for REST API
	echo '<tr><td><b>'.__('Disable JSONP for REST API?','forcefield').'</b></td><td width="20"></td>';
	echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nojsonp" value="yes"';
	if (forcefield_get_setting('restapi_nojsonp', false) == 'yes') {echo ' checked';}
	echo '></td><td width="10"></td>';
	echo '<td>'.__('Disables JSONP Output for the REST API.','forcefield').'</td></tr>';

	// REST API prefix
	// default: 'json'
	echo '<tr><td><b>'.__('Prefix for REST API Access?','forcefield').'</b></td><td colspan="3"></td>';
	echo '<td colspan="5"><input type="text" name="ff_restapi_prefix" value="';
		echo forcefield_get_setting('restapi_prefix', false);
	echo '"><br>'.__('Leave blank for no change. Default is "json".','forcefield').'</td></tr>';

	// settings update Submit Button
	echo '<tr height="20"><td> </td></tr>';
	echo '<tr><td> </td><td width="20"></td>';
	echo '<td colspan="3" align="left">';
	echo '<input type="submit" class="button-primary" value="'.__('Update Settings','forcefield').'"></td></tr>';

	echo '</table></div>'; // close API access tab

	echo '</form><br>'; // close settings form


	// ============
	// IP Blocklist
	// ============
	// TODO: output blocklist with removal options

	global $vffblocklist;
	echo '<div id="ip-blocklist"';
		// if ($vcurrenttab != 'ip-blocklist') {echo ' style="display:none;"';
	echo '><table>';

	// TODO: IP Whitelist Field?

	// TODO: list IPs and why they were blocked


	echo '</table></div>'; // close IP blocklist table



	echo '</div></div>'; // close #wrapbox

	echo '</div>'; // close #pagewrap

}

// =================
// GENERAL FUNCTIONS
// =================

// get Remote IP Address
// ---------------------
function forcefield_get_remote_ip() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {$vip = $_SERVER['HTTP_CLIENT_IP'];}
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {$vip = $_SERVER['HTTP_X_FORWARDED_FOR'];}
	else {$vip = $_SERVER['REMOTE_ADDR'];}
	return $vip;
}

// Ban an IP Address
// -----------------
function forcefield_ban_ip_address($vcontext) {
	global $vffblocklist, $vffipaddress;
	if ( (!is_array($vffblocklist)) || (!array_key_exists($vffipaddress, $vffblocklist)) ) {
		$vffbannedips[$vip] = $vcontext.':'.time();
	}
}

// maybe filter the Login Error Messages (Hints)
// -------------------------------------
add_filter('login_errors', 'forcefield_login_error_message', 11);
function forcefield_login_error_message($message) {
	$vremovehints = forcefield_get_setting('login_nohints');
	if ($vremovehints == 'yes') {return '';}
	return $message;
}

// Block Unwhitelisted Administrators
// ----------------------------------
add_action('init', 'forcefield_administrator_validation', 0);
function forcefield_administrator_validation() {
	if (!is_user_logged_in()) {return;}
	$vblockadmins = forcefield_get_setting('admin_block');
	if ($vblockadmins != 'yes') {return;}
	$user = wp_get_current_user();
	if (in_array('administrator', (array)$user->roles)) {
		$vwhitelist = forcefield_get_setting('admin_whitelist');
		if (strstr($vwhitelist, ',')) {
			$vadmins = explode(',', $vwhitelist);
			foreach ($vadmins as $vi => $vadmin) {$vadmins[$vi] = trim($vadmin);}
		} elseif (trim($vwhitelist) != '') {$vadmins[0] = trim($vwhitelist);}
		else {return;}

		if (!in_array($user->data->user_login, $vadmins)) {
			// maybe send admin alert email
			$valertemail = forcefield_get_setting('admin_alert');
			$vadminemail = forcefield_get_setting('admin_email');
			$vautodelete = forcefield_get_setting('admin_autodelete');

			if ( ($valertemail == 'yes') && ($vadminemail != '') ) {
				// set mail from name
				add_filter('wp_mail_from_name', 'forcefield_set_from_name');
				// set email body
				$vblogname = get_bloginfo('name');
				$vsubject = '[ForceField] Warning: Unwhitelisted Administrator Login!';
				$vbody = 'ForceField plugin has blocked an unwhitelisted administrator login'."\n";
				$vbody .= 'to WordPress site '.$vblogname.' ('.home_url().')'."\n\n";
				$vbody .= 'Username Blocked: "'.$user->login.'"'."\n\n";
				$vbody .= 'If this username is familiar, add it to your whitelist to stop further alerts.'."\n";
				$vbody .= 'But if it is unfamiliar, your site security may be compromised.'."\n\n";
				if ($vautodelete) {
					$vbody .= 'Additionally, according to your ForceField plugin settings,'."\n";
					$vbody .= 'the user "'.$user->user_login.'" was automatically deleted.'."\n\n";
				}
				ob_start(); print_r($user); $vprintuser = ob_get_contents(); ob_end_clean();
				$vbody .= 'Below is a dump of the user object for "'.$user->user_login.'"'."\n";
				$vbody .= '----------'."\n";
				$vbody .= $vprintuser;
				wp_mail($vadminemail, $vsubject, $vmessage);
			}

			// maybe autodelete unwhitelisted admins
			if ($vautodelete) {
				if (!function_exists('wp_delete_user')) {include(ABSPATH.WPINC.'/user.php');}
				wp_delete_user($user->data->ID);
				forcefield_ban_ip_address('administrator');
			} else {wp_logout();}
			wp_die(false);
		}
	}
}

// to filter email from name for forcefield email alerts only
function forcefield_set_from_name($vname) {
	return apply_filters('forcefield_email_from_name', $vname);
}


// =======
// XML RPC
// =======

// xmlrpc.php sequence
// - new wp_xmlrpc_server (/wp-includes/class-wp-xmlrpc-server.php)
// -> serve_request -> IXR_Server($methods)
// -> method (authenticated) -> login
// -> method (unauthenticated) -> (no login)

// maybe Disable XML RPC Entirely
// ------------------------------
add_filter('xmlrpc_methods', 'forcefield_xmlrpc_disable', 99);
function forcefield_xmlrpc_disable($methods) {
	$vdisable = forcefield_get_setting('xmlrpc_disable');
	if ($vdisable == 'yes') {$methods = array();}
	return $methods;
}

// maybe Disable XML RPC Authenticated Methods
// -------------------------------------------
// note: this enable filter is for authenticated methods *only*
// as it is triggered by the login method of XML RPC server
add_filter('xmlrpc_enabled', 'forcefield_xmlrpc_disable_auth');
function forcefield_xmlrpc_disable_auth($venabled) {
	$vdisable = forcefield_get_setting('xmlrpc_noauth');
	if ($vdisable == 'yes') {$venabled = false;}
	return $venabled;
}

// maybe Slowdown XML RPC
// ----------------------
add_filter('xmlrpc_enabled', 'forcefield_xmlrpc_slowdown');
add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_slowdown');
function forcefield_xmlrpc_slowdown($arg) {
	$vslowdown = forcefield_get_setting('xmlrpc_slowdown');
	if ($vslowdown == 'yes') {
		static $xmlrpc_calls = 0; $xmlrpc_calls++;
		$rand = function_exists('mt_rand') ? 'mt_rand' : 'rand';
		usleep(call_user_func($rand, 500000 * $xmlrpc_calls, 2000000 * $xmlrpc_calls));
	}
	return $arg;
}

// maybe Remove XML RPC Link (RSD)
// -------------------------
add_action('plugins_loaded', 'forcefield_remove_rsd_link');
function forcefield_remove_rsd_link() {
	if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
		$vdisable = forcefield_get_setting('xmlrpc_disable');
		if ($vdisable == 'yes') {remove_action('wp_head', 'rsd_link');}
	}
}

// maybe Disable XML RPC Methods
// -----------------------------
add_filter('xmlrpc_methods', 'forcefield_xmlrpc_methods');
function forcefield_xmlrpc_methods($methods) {
	// maybe disable pingbacks
	$vdisable = forcefield_get_setting('pingbacks_disable');
	if ($vdisable == 'yes') {
		unset($methods['pingback.ping']);
		unset($methods['pingback.extensions.getPingbacks']);
	}

	// TODO: enable/disable more XML RPC methods (endpoints)?
	// if ( (isset($_GET['ffdebug'])) && ($_GET['ffdebug'] == '1') ) {
	//	echo "<!-- XML RPC Methods: "; print_r($methods); echo " -->"; exit;
	// }

	return $methods;
}

// maybe Remove XML RPC Pingback Header
// ------------------------------------
add_filter('wp_headers', 'forcefield_remove_pingback_header');
function forcefield_remove_pingback_header($headers) {
	$vdisable = forcefield_get_setting('pingbacks_disable');
	if ($vdisable == 'yes') {unset($headers['X-Pingback']);}
	return $headers;
}

// maybe Disable Self Pings
// ------------------------
add_action('pre_ping', 'forcefield_disable_self_pings');
function forcefield_disable_self_pings($vlinks) {
	$vdisable = forcefield_get_setting('selfpings_disable');
	if ($vdisable == 'yes') {
		$vhome = home_url();
		foreach ($vlinks as $vi => $vlink) {
			if (0 === strpos($vlink, $vhome)) {unset($vlinks[$vi]);}
		}
	}
	return $vlinks;
}

// maybe disable Anonymous Commenting via XML RPC
// ----------------------------------------------
add_filter('xmlrpc_allow_anonymous_comments', 'forcefield_xmlrpc_anonymous_comments');
function forcefield_xmlrpc_anonymous_comments($allow) {
	$vallowanon = forcefield_get_setting('xmlrpc_anoncomments');
	if ($vallowanon != 'yes') {$allow = false;}
	return $allow;
}

// maybe disable Anonymous Commenting via REST API
// -----------------------------------------------
add_filter('rest_allow_anonymous_comments', 'forcefield_restapi_anonymous_comments');
function forcefield_restapi_anonymous_comments($allow) {
	$vallowanon = forcefield_get_setting('restapi_anoncomments');
	if ($vallowanon != 'yes') {$allow = false;}
	return $allow;
}

// ========
// REST API
// ========

// rest_api_loaded()
// -> rest_get_server (new WP_REST_Server)
// -> serve_request
// note: since check_authentication is always called
// rest_authentication_errors filter is always applied

// maybe Disable/Restrict REST API
// -------------------------------
add_filter('rest_authentication_errors', 'forcefield_restapi_access');
function forcefield_restapi_access($access) {

	// maybe disabled REST API
	$vrestapidisable = forcefield_get_setting('restapi_disable');
	if ($vrestapidisable) {
		return new WP_Error(
			'rest_disabled',
			__('The REST API is disabled.','forcefield'),
			array('status' => '405')
		);
	}

	// maybe SSL connection required
	$vrequiressl = forcefield_get_setting('restapi_requiressl');
	if ( ($vrequiressl == 'yes') && !is_ssl()) {
		return new WP_Error(
			'rest_ssl_required',
			__('SSL connection is required to access the REST API.','forcefield'),
			array('status' => 403)
		);
	}

	// maybe authenticated (logged in) users only
	$vrequireauth == forcefield_get_setting('restapi_authonly');
    if ( ($vrequireauth == 'yes') && !is_user_logged_in()) {
        return new WP_Error(
        	'rest_not_logged_in',
        	__('You need to be logged in to access the REST API.','forcefield'),
        	array('status' => rest_authorization_required_code())
        );
    }

	// TODO: role restricted REST API access
	// $vrestricted = forcefield_get_setting('restapi_restricted');
	// if ( ($vrestricted == 'no') || ($vrestricted == '') ) {return $access;}
	// elseif (!is_user_logged_in()) {
	//	// (enforced) logged in only message
    //	return new WP_Error(
    //    	'rest_not_logged_in',
    //    	__('You need to be logged in to access the REST API.','forcefield'),
    //    	array('status' => rest_authorization_required_code())
    //	);
    // } else {
    //	$user = wp_get_current_user(); $roles = $user->roles;
    //	if ( ( ($vrestricted == 'administrator') && (!in_array('administrator', $roles)) )
	//	  || ( ($vrestricted == 'editor') && (!in_array('editor', $roles)) )
	//	  || ( ($vrestricted == 'author') && (!in_array('author', $roles)) )
	//	  || ( ($vrestricted == 'contributor') && (!in_array('contributor', $roles)) ) {
	//		return new WP_Error(
	//			'rest_restricted',
	//			__('Access to the REST API is restricted.','forcefield'),
	//			array('status' => rest_authorization_required_code())
	//		);
	//	}
    // }

    return $access;
}

// maybe Slowdown REST API
// -----------------------
add_filter('rest_jsonp_enabled', 'forcefield_restapi_slowdown');
add_filter('rest_authentication_errors', 'forcefield_restapi_slowdown');
function forcefield_restapi_slowdown($arg) {
	$vslowdown = forcefield_get_setting('restapi_slowdown');
	if ($vslowdown == 'yes') {
		static $restapi_calls = 0; $restapi_calls++;
		$rand = function_exists('mt_rand') ? 'mt_rand' : 'rand';
		usleep(call_user_func($rand, 500000 * $restapi_calls, 2000000 * $restapi_calls));
	}
	return $arg;
}

// maybe Disable JSONP (REST API)
// -------------------
add_filter('rest_jsonp_enabled', 'forcefield_jsonp_disable');
function forcefield_jsonp_disable($enabled) {
	$vjsonp = forcefield_get_setting('restapi_jsonp');
	if ($vjsonp == 'no') {return false;}
	return $enabled;
}

// maybe Remove REST API Info
// --------------------------
add_action('plugins_loaded', 'forcefield_remove_restapi_info');
function forcefield_remove_restapi_info() {
	$vnolinks = forcefield_get_setting('restapi_nolinks');
	if ($vnolinks == 'yes') {
		remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
		remove_action('wp_head', 'rest_output_link_wp_head', 10);
		remove_action('template_redirect', 'rest_output_link_header', 11);
	}
}

// REST API Response Pre-Dispatch Filter
// -------------------------------------
// add_filter('rest_pre_dispatch', 'forcefield_restapi_pre_dispatch');
// function forcefield_restapi_pre_dispatch($response, $server, $request) {
//	// TODO: maybe pre-check permission_callbacks?
//	return $response;
// }

// REST API Response Dispatch Filter
// ---------------------------------
// add_filter('rest_dispatch_request', 'forcefield_dispatch_request_check');
// function forcefield_dispatch_request_check($dispatch_result, $request, $route, $handler) {
//	return $dispatch_result;
// }

// maybe Change REST API Prefix
// ----------------------------
// note: default is "wp-json"
add_filter('rest_url_prefix', 'forcefield_restapi_prefix', 100);
function forcefield_restapi_prefix($prefix) {
	$vcustomprefix = forcefield_get_setting('restapi_prefix');
	if ($vcustomprefix != '') {$prefix = $vcustomprefix;}
	return $prefix;
}


// =========
// TOKENIZER
// =========

// Check for Existing Token
// ------------------------
function forcefield_check_token($vcontext) {
	global $vffipaddress;

	// validate IP address to IP key
	if (filter_var($vffipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$vipaddy = str_replace('.', '-', $vffipaddress);
	} elseif (filter_var($vffipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$vipaddy = str_replace(':', '--', $vffipdaddress);
	} else {return false;}

	// return transient token value
	$vtransientid = $vcontext.'_token_'.$vipaddy;
	$vtoken = get_transient($vtransientid);
	return $vtoken;
}

// Add Token Fields to Forms
// -------------------------
add_action('login_form', 'forcefield_login_field');
add_action('register_form', 'forcefield_register_field');
add_action('signup_extra_fields', 'forcefield_signup_token');
add_action('signup_blogform', 'forcefield_signup_token');
add_action('lostpassword_form', 'forcefield_lostpass_token');
add_action('comment_form', 'forcefield_comment_field');
function forcefield_login_field() {forcefield_add_field('login');}
function forcefield_register_field() {forcefield_add_field('register');}
function forcefield_signup_field() {forcefield_add_field('signup');}
function forcefield_lostpass_field() {forcefield_add_field('lostpass');}
function forcefield_comment_field() {forcefield_add_field('comment');}

// Add Form Field Abstract
// -----------------------
function forcefield_add_field($vcontext) {
	$vtokenize = forcefield_get_setting($vcontext.'_token');
	if ($vtokenize == 'yes') {
		echo '<input type="hidden" id="auth_token" name="auth_token" value="" />';
		$vframesrc = admin_url('admin-ajax.php')."?action=forcefield_".$vcontext;
		echo '<iframe style="display:none;" name="auth_frame" id="auth_frame" src="'.$vframesrc.'"></iframe>';
	}
}

// AJAX to Create and Return Token
// -------------------------------
add_action('wp_ajax_nopriv_forcefield_login', 'forcefield_login_token');
add_action('wp_ajax_nopriv_forcefield_register', 'forcefield_register_token');
add_action('wp_ajax_nopriv_forcefield_signup', 'forcefield_signup_token');
add_action('wp_ajax_nopriv_forcefield_lostpass', 'forcefield_lostpass_token');
add_action('wp_ajax_nopriv_forcefield_comment', 'forcefield_comment_token');
function forcefield_login_token() {forcefield_output_token('login');}
function forcefield_register_token() {forcefield_output_token('register');}
function forcefield_signup_token() {forcefield_output_token('signup');}
function forcefield_lostpass_token() {forcefield_output_token('lostpass');}
function forcefield_comment_token() {forcefield_output_token('comment');}

// Token Output Abstract
// ---------------------
function forcefield_output_token($vcontext) {
	$vtoken = forcefield_create_token($vcontext);
	echo "<script>parent.document.getElementById('auth_token').value = '".$vtoken."';</script>";
	exit;
}

// Create a Token
// --------------
function forcefield_create_token($vcontext) {

	global $vffipaddress;

	$vtokenize = forcefield_get_setting($vcontext.'_token');
	if ($vtokenize != 'yes') {return false;}

	// maybe return existing token
	$vtoken = forcefield_check_token($vcontext);
	if ($vtoken) {return $vtoken;}

	// validate IP address and make IP key
	// ref: https://www.mikemackintosh.com/5-tips-for-working-with-ipv6-in-php/
	if (filter_var($vffipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$vipaddy = str_replace('.', '-', $vffipaddress);
	} elseif (filter_var($vffipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$vipaddy = str_replace(':', '--', $vffipdaddress);
	} else {return false;}

	// create token and set transient
	$vtransientid = $vcontext.'_token_'.$vipaddy;
	$vtoken = wp_generate_password(12, false, false);
	$vexpires = apply_filters('forcefield_token_expiration', 300);
	if (!is_numeric(absint($vexpires))) {$vexpires = 300;}
	set_transient($vtransientid, $vtoken, $vexpires);
	return $vtoken;
}

// Delete a Token
// --------------
function forcefield_delete_token($vcontext) {

	global $vffipaddress;

	// validate IP address and make IP key
	if (filter_var($vffipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$vipaddy = str_replace('.', '-', $vffipaddress);
	} elseif (filter_var($vffipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$vipaddy = str_replace(':', '--', $vffipdaddress);
	}

	// delete token transient
	$vtransientid = $vcontext.'_token_'.$vipaddy;
	delete_transient($vtransientid);
}


// ==============
// AUTHENTICATION
// ==============

// XML RPC Authentication
// ----------------------
add_filter('authenticate', 'forcefield_xmlrpc_authentication', 9, 3);
function forcefield_xmlrpc_authentication($user, $username, $password) {

	// note: XMLRPC_REQUEST is defined in xmlrpc.php
	if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {

		// check for SSL connection requirement
		$vrequiressl = forcefield_get_setting('xmlrpc_requiressl');
		if ( ($vrequiressl == 'yes') && !is_user_logged_in() && !is_ssl() ) {
			add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_require_ssl_message');
			// note: we need to return an error here so that the xmlrpc_login_error filter is called
			return new WP_Error('xmlrpc_ssl_required', __('XML RPC requires SSL Connection.','forcefield'));
		}

		// check for authentication block
		$vauthblock = forcefield_get_setting('xmlrpc_authblock');
		$vauthban = forcefield_get_setting('xmlrpc_authban');
		$verrormessage = forcefield_get_setting('error_message');

		if ($vauthban == 'yes') {
			// ban this IP for XML RPC authentication violation
			forcefield_ban_ip_address('xmlrpc');
			add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_error_message');
			return new WP_Error('xmlrpc_ban', $verrormessage);
		} elseif ($vauthblock == 'yes') {
			add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_error_message');
			return new WP_Error('xmlrpc_block', $verrormessage);
		} elseif (is_wp_error($user)) {
			// TODO: maybe check against XML RPC authentication attempts limit?
			// $vattempts = forcefield_get_setting('xmlrpc_attempts');
		}
	}
	return $user;
}

// XML RPC Error Message
// ---------------------
function forcefield_xmlrpc_error_message($error) {
	return new IXR_Error(403, __('Authentication Error. Operation not permitted.','forcefield'));
}

// XML RPC requires SSL Message
// ----------------------------
function forcefield_xmlrpc_require_ssl_message() {
	return new IXR_Error(403, __('XML RPC requires SSL Connection.','forcefield'));
}

// REST API Authentication
// -----------------------
// whoooooops... ummmmmm... this is not ever even called
// ...because REST API does not "do" authentication...
// add_filter('authenticate', 'forcefield_restapi_authentication', 9, 3);
// function forcefield_restapi_authentication($user, $username, $password) {
// 	note: REST_REQUEST is defined in function rest_api_loaded
//	if (defined('REST_REQUEST') && REST_REQUEST) {
//		$vauthblock = forcefield_get_setting('restapi_authblock');
//		$vauthban = forcefield_get_setting('restapi_authban');
//		$verrormessage = __('Authentication Error. Operation not permitted.','forcefield');
//		if ($vauthban == 'yes') {
//			// ban this IP as authed REST API operations not permitted
//			forcefield_ban_ip_address('restapi');
//			return new WP_Error('rest_ban', $verrormessage);
//		} elseif ($vauthblock == 'yes') {
//			return new WP_Error('rest_block', $verrormessage);
//		} elseif (is_wp_error($user)) {
//			// TODO: maybe check against REST API authentication attempts limit?
//			// $vattempts = forcefield_get_setting('restapi_attempts');
//		}
//	}
//	return $user;
// }


// Login Token Authentication
// --------------------------
add_filter('authenticate', 'forcefield_login_validate', 10, 3);
function forcefield_login_validate($user, $username, $password) {

	$verrormessage = forcefield_get_setting('error_message');

	// maybe return on existing errors
    if ( !$username || !$password || is_wp_error($user) ) {return $user;}

	// maybe require SSL connection to login
	$vrequiressl = forcefield_get_setting('login_requiressl');
	if ( ($vrequiressl == 'yes') && !is_user_logged_in() && !is_ssl()) {
		add_filter('secure_auth_redirect', '__return_true');
		auth_redirect(); exit;
	}

	// check for empty referer field
	if ($_SERVER['HTTP_REFERER'] == '') {
		$vnorefban = forcefield_get_setting('login_norefban');
		if ($vnorefban) {forcefield_ban_ip_address('noref');}
		do_action('forcefield_login_noreferer');
		do_action('forcefield_login_failed');
		return new WP_Error('login_no_referer', $verrormessage);
	}

	// login form field to check token
	if ( (isset($POST['log'])) && (isset($_POST['pwd'])) ) {

		$vtokenize = forcefield_get_setting('login_token');
		if ($vtokenize != 'yes') {return $user;}

		// maybe ban the IP if missing the token form field
		if (!isset($_POST['auth_token'])) {
			$vauthban = forcefield_get_settings('login_authban');
			if ($vauthban == 'yes') {forcefield_ban_ip_address('login');}
			do_action('forcefield_login_nofield');
			do_action('forcefield_login_failed');
			return new WP_Error('login_token_missing', $verrormessage);
		}

		$vauthtoken = $_POST['auth_token'];
		$vchecktoken = forcefield_check_token('login');

		if (!$vchecktoken) {
			// probably the token transient has expired
			do_action('forcefield_login_notoken');
			do_action('forcefield_login_failed');
			return new WP_Error('login_token_expired', $verrormessage);
		} elseif ($vauthtoken != $vchecktoken) {
			// fail, token is a mismatch
			do_action('forcefield_login_mismatch');
			do_action('forcefield_login_failed');
			return new WP_Error('login_token_mismatch', $verrormessage);
		} else {
			do_action('forcefield_login_success');
			forcefield_delete_token('login');
		}
	}

	return $user;
}

// Registration Token Authentication
// ---------------------------------
add_filter('register_post', 'forcefield_registration_authenticate', 9, 3);
function forcefield_registration_authenticate($errors, $sanitized_user_login, $user_email) {

	$verrormessage = forcefield_get_setting('error_message');

	// maybe require SSL connection for registration
	$vrequiressl = forcefield_get_setting('register_requiressl');
	if ( ($vrequiressl == 'yes') && !is_ssl()) {
		// the instant version of the auth_redirect function
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// check for empty referer field
	if ($_SERVER['HTTP_REFERER'] == '') {
		$vnorefban = forcefield_get_setting('register_norefban');
		if ($vnorefban) {forcefield_ban_ip_address('noref');}
		do_action('forcefield_register_noreferer');
		do_action('forcefield_register_failed');
		return new WP_Error('register_no_referer', $verrormessage);
	}

	// check tokenizer setting
    $vtokenize = forcefield_get_setting('register_token');
	if ($vtokenize != 'yes') {return $errors;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vauthban = forcefield_get_settings('register_authban');
		if ($vauthban == 'yes') {forcefield_ban_ip_address('register');}
		do_action('forcefield_register_nofield');
		do_action('forcefield_register_failed');
		if (is_wp_error($errors)) {$errors->add('register_token_missing', $verrormessage);}
		else {return new WP_Error('register_token_missing', $verrormessage);}
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('register');

	if (!$vchecktoken) {
		// probably the token transient has expired
		do_action('forcefield_register_notoken');
		do_action('forcefield_register_failed');
		if (is_wp_error($errors)) {$errors->add('register_token_expired', $verrormessage);}
		else {return new WP_Error('register_token_expired', $verrormessage);}
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, token is a mismatch
		do_action('forcefield_register_mismatch');
		do_action('forcefield_register_failed');
		if (is_wp_error($errors)) {$errors->add('register_token_mismatch', $verrormessage);}
		else {return new WP_Error('register_token_mismatch', $verrormessage);}
	} else {
		do_action('forcefield_register_success');
		forcefield_delete_token('register');
	}

    return $errors;
}

// Blog Signup Authenticate
// ------------------------
add_filter('wpmu_validate_user_signup', 'forcefield_signup_authenticate');
function forcefield_signup_authenticate($results) {

	$verrormessage = forcefield_get_setting('error_message');

	// maybe allow signup for already logged in users
	if ( is_user_logged_in() && is_admin() && !defined('DOING_AJAX') ) {return $results;}

	// maybe require SSL connection for blog signup
	$vrequiressl = forcefield_get_setting('signup_requiressl');
	if ( ($vrequiressl == 'yes') && !is_ssl()) {
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// check for empty referer field
	if ($_SERVER['HTTP_REFERER'] == '') {
		$vnorefban = forcefield_get_setting('signup_norefban');
		if ($vnorefban) {forcefield_ban_ip_address('noref');}
		do_action('forcefield_signup_noreferer');
		do_action('forcefield_signup_failed');
		return new WP_Error('signup_no_referer', $verrormessage);
	}

	// check tokenizer setting
	$vtokenize = forcefield_get_setting('signup_token');
	if ($vtokenize != 'yes') {return $results;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vauthban = forcefield_get_settings('signup_authban');
		if ($vauthban == 'yes') {forcefield_ban_ip_address('signup');}
		do_action('forcefield_signup_nofield');
		do_action('forcefield_signup_failed');
		$errors = $results['errors'];
		$errors->add('signup_token_missing', $verrormessage);
		$results['errors'] = $errors;
		return $results;
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('signup');

	if (!$vchecktoken) {
		// probably the lost password token transient has expired
		do_action('forcefield_signup_notoken');
		do_action('forcefield_signup_failed');
		$errors = $results['errors'];
		$errors->add('signup_token_expires', $verrormessage);
		$results['errors'] = $errors;
		return $results;
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, lost password token is a mismatch
		do_action('forcefield_signup_mismatch');
		do_action('forcefield_signup_failed');
		$errors = $results['errors'];
		$errors->add('signup_token_mismatch', $verrormessage);
		$results['errors'] = $errors;
		return $results;
	} else {
		// success, allow the user to signup
		do_action('forcefield_signup_success');
		forcefield_delete_token('signup');
	}

	return $results;
}


// Lost Password Token Authentication
// ----------------------------------
add_action('allow_password_reset', 'forcefield_lost_password_authenticate', 21, 1);
function forcefield_lost_password_authenticate($allow) {

	$verrormessage = forcefield_get_setting('error_message');

	// maybe require SSL connection for lost password
	$vrequiressl = forcefield_get_setting('lostpass_requiressl');
	if ( ($vrequiressl == 'yes') && !is_ssl()) {
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// check for empty referer field
	if ($_SERVER['HTTP_REFERER'] == '') {
		$vnorefban = forcefield_get_setting('lostpass_norefban');
		if ($vnorefban) {forcefield_ban_ip_address('noref');}
		do_action('forcefield_lostpass_noreferer');
		do_action('forcefield_lostpass_failed');
		return new WP_Error('lostpass_no_referer', $verrormessage);
	}

	// check tokenizer setting
	$vtokenize = forcefield_get_setting('lostpass_token');
	if ($vtokenize != 'yes') {return $allow;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vauthban = forcefield_get_settings('lostpass_authban');
		if ($vauthban == 'yes') {forcefield_ban_ip_address('lostpass');}
		do_action('forcefield_lostpass_nofield');
		do_action('forcefield_lostpass_failed');
		return new WP_Error('lostpass_token_missing', $verrormessage);
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('lostpass');

	if (!$vchecktoken) {
		// probably the lost password token transient has expired
		do_action('forcefield_lostpass_notoken');
		do_action('forcefield_lostpass_failed');
		return new WP_Error('lostpass_token_expired', $verrormessage);
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, lost password token is a mismatch
		do_action('forcefield_lostpass_mismatch');
		do_action('forcefield_lostpass_failed');
		return new WP_Error('lostpass_token_mismatch', $verrormessage);
	} else {
		// success, allow the user to send reset email
		do_action('forcefield_lostpass_success');
		forcefield_delete_token('lostpass');
	}

	return $allow;
}

// Commenting Authenticate
// -----------------------
add_filter('preprocess_comment', 'forcefield_preprocess_comment');
function forcefield_preprocess_comment($comment) {

	$verrormessage = forcefield_get_setting('error_message');

	// skip checks for those with comment moderation permission
	if (current_user_can('moderate_comments')) {return $comment;}

	// maybe require SSL connection for commenting
	$vrequiressl = forcefield_get_setting('comment_requiressl');
	if ( ($vrequiressl == 'yes') && !is_ssl()) {
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// check for empty referer field
	if ($_SERVER['HTTP_REFERER'] == '') {
		$vnorefban = forcefield_get_setting('comment_norefban');
		if ($vnorefban) {forcefield_ban_ip_address('noref');}
		do_action('forcefield_comment_noreferer');
		do_action('forcefield_comment_failed');
		return new WP_Error('comment_no_referer', $verrormessage);
	}

	// check tokenizer setting
	$vtokenize = forcefield_get_setting('comment_token');
	if ($vtokenize != 'yes') {return $comment;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vauthban = forcefield_get_settings('comment_authban');
		if ($vauthban == 'yes') {forcefield_ban_ip_address('comment');}
		do_action('forcefield_comment_nofield');
		do_action('forcefield_comment_failed');
		return new WP_Error('comment_token_missing', $verrormessage);
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('lostpass');

	if (!$vchecktoken) {
		// probably the comment token transient has expired
		do_action('forcefield_comment_notoken');
		do_action('forcefield_comment_failed');
		return new WP_Error('comment_token_expired', $verrormessage);
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, comment token is a mismatch
		do_action('forcefield_comment_mismatch');
		do_action('forcefield_comment_failed');
		return new WP_Error('comment_token_mismatch', $verrormessage);
	} else {
		// success, allow the user to comment
		do_action('forcefield_comment_success');
		forcefield_delete_token('comment');
	}

	return $comment;
}
