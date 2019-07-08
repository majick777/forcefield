<?php

/*
Plugin Name: ForceField
Plugin URI: http://wordquest.org/plugins/forcefield/
Author: Tony Hayes
Description: Flexible Protection for Login, Registration, Commenting, REST API and XML RPC.
Version: 0.9.3
Author URI: http://wordquest.org/
GitHub Plugin URI: majick777/forcefield
@fs_premium_only pro-functions.php
*/

// [FOR TESTING ONLY] uncomment to bypass REST API Nonce Check
// define('REST_NONCE_BYPASS', true);


// ==================
// === FORCEFIELD ===
// ==================

// set Plugin Values
// -----------------
global $wordquestplugins;
$vslug = $vforcefieldslug = 'forcefield';
$wordquestplugins[$vslug]['version'] = $vforcefieldversion = '0.9.3';
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
                'parent'		=> array('slug'=>'wordquest'),
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


// ===============
// PLUGIN SETTINGS
// ===============

// get Plugin Settings
// -------------------
global $vforcefield;
$vforcefield['settings'] = get_option('forcefield');
// 0.9.2: initiate table variables right away
$vforcefield = forcefield_blocklist_table_init();
// 0.9.2: get remote IP right away
$vforcefield['ip'] = forcefield_get_remote_ip();

// get Default Settings
// --------------------
function forcefield_get_default_settings() {

	$vsettings = array(

		/* Administrator */
		'admin_block' => 'yes',
		'admin_autodelete' => 'no',
		'admin_whitelist' => '',
		'admin_alert' => 'yes',
		// 'admin_email' => '',

		/* IP Blocklist */
		'blocklist_tokenexpiry' => '300',
		'blocklist_noreferer' => 'yes',
		'blocklist_notoken' => 'yes',
		'blocklist_badtoken' => 'yes',
		'blocklist_unblocking' => 'yes',
		'blocklist_whitelist' => '',
		'blocklist_blacklist' => '',
		'blocklist_cooldown' => '10minutes',
		'blocklist_expiry' => 'hourly',
		'blocklist_delete' => 'daily',
		'blocklist_cleanups' => 'twicedaily',

		/* Login */
		'login_token' => 'yes',
		'login_notokenban' => 'yes',
		'login_norefblock' => 'yes',
		'login_requiressl' => 'no',
		'login_nohints' => 'no',

		/* Registration */
		'register_token' => 'yes',
		'register_notokenban' => 'yes',
		'register_norefblock' => 'yes',
		'register_requiressl' => 'no',

		/* Blog Signup (Multisite) */
		'signup_token' => 'yes',
		'signup_notokenban' => 'yes',
		'signup_norefblock' => 'yes',
		'signup_requiressl' => 'no',

		/* Lost Password */
		'lostpass_token' => 'yes',
		'lostpass_notokenban' => 'no',
		'lostpass_norefblock' => 'yes',
		'lostpass_requiressl' => 'no',

		/* Comments */
		'comment_token' => 'yes',
		'comment_notokenban' => 'yes',
		'comment_norefblock' => 'yes',
		'comment_requiressl' => 'no',

		/* XML RPC */
		'xmlrpc_disable' => 'no',
		'xmlrpc_noauth' => 'yes',
		'xmlrpc_authblock' => 'yes',
		'xmlrpc_authban' => 'yes',
		'xmlrpc_requiressl' => 'no',
		'xmlrpc_slowdown' => 'yes',
		'xmlrpc_anoncomments' => 'yes',
		'xmlrpc_restricted' => 'no',
		'xmlrpc_roles' => array(),
		'xmlrpc_nopingbacks' => 'no',
		'xmlrpc_noselfpings' => 'yes',

		/* REST API */
		'restapi_disable' => 'no',
		'restapi_requiressl' => 'no',
		'restapi_slowdown' => 'yes',
		'restapi_anoncomments' => 'no',
		'restapi_restricted' => 'no',
		'restapi_roles' => array(),
		'restapi_nouserlist' => 'yes',
		'restapi_nojsonp' => 'no',
		'restapi_nolinks' => 'no',
		'restapi_prefix' => '',

		/* Generic Error Message */
		'error_message' => __('Request Failed. Authentication Error.','forcefield'),

		/* Admin UI */
		'current_tab' => 'general'
	);

	// set administrator alert email to current user
	$current_user = wp_get_current_user();
	$vuseremail = $current_user->user_email;
	if (strstr($vuseremail, '@localhost')) {$vuseremail = '';}
	$vsettings['admin_email'] = $vuseremail;

	// 0.9.1: add transgression limit defaults
	$vlimits = forcefield_blocklist_get_default_limits();
	foreach ($vlimits as $vkey => $vlimit) {$vsettings['limit_'.$vkey] = $vlimit;}

	return $vsettings;
}


// add Defaults on Activation
// --------------------------
register_activation_hook(__FILE__, 'forcefield_add_settings');
function forcefield_add_settings() {
	$vforcefield['defaults'] = forcefield_get_default_settings();
	add_option('forcefield', $vforcefield['defaults']);
}

// get a Forcefield Setting
// ------------------------
function forcefield_get_setting($vkey, $vfilter=false) {
	global $vforcefield;
	if (!isset($vforcefield['defaults'])) {$vforcefield['defaults'] = forcefield_get_default_settings();}

	if (isset($vforcefield['settings'][$vkey])) {$vvalue = $vforcefield['settings'][$vkey];}
	elseif (isset($vforcefield['defaults'][$vkey])) {$vvalue = $vforcefield['defaults'][$vkey];}
	else {$vvalue = null;}

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
	$vprevious = $vforcefield['settings'];
	$vdefaults = forcefield_get_default_settings();
	$vprefix = $vforcefield['settings']['restapi_prefix'];
	$vroles = wp_roles()->get_names();
	$vintervals = forcefield_get_intervals();

	// 0.9.1: handle settings reset
	if ( (isset($_POST['forcefield_reset_settings'])) && ($_POST['forcefield_reset_settings'] == 'yes') ) {
		$vforcefield['settings'] = $vdefaults; update_option('forcefield', $vforcefield['settings']);

		// [PRO] maybe reset any pro settings also
		if (function_exists('forcefield_pro_reset_settings')) {forcefield_pro_reset_settings();}
		return;
	}

	// set plugin option keys and option types
	$voptionkeys = array(

		/* Administrator */
		'admin_block' => 'checkbox',
		'admin_whitelist' => 'usernames',
		'admin_autodelete' => 'checkbox',
		'admin_alert' => 'checkbox',
		'admin_email' => 'email',

		/* IP Blocklist */
		'blocklist_tokenexpiry' => 'numeric',
		'blocklist_noreferer' => 'checkbox',
		'blocklist_notoken' => 'checkbox',
		'blocklist_badtoken' => 'checkbox',
		'blocklist_unblocking' => 'checkbox',
		'blocklist_whitelist' => 'iptextarea',
		'blocklist_blacklist' => 'iptextarea',
		'blocklist_cooldown' => 'frequency',
		'blocklist_expiry' => 'frequency',
		'blocklist_delete' => 'frequency',
		'blocklist_cleanups' => 'frequency',

		/* Login */
		'login_token' => 'checkbox',
		'login_notokenban' => 'checkbox',
		'login_norefban' => 'checkbox',
		'login_requiressl' => 'checkbox',
		'login_nohints' => 'checkbox',

		/* Register */
		'register_token' => 'checkbox',
		'register_notokenban' => 'checkbox',
		'register_norefblock' => 'checkbox',
		'register_requiressl' => 'checkbox',

		/* Blog Signup (Multisite) */
		'signup_token' => 'checkbox',
		'signup_notokenban' => 'checkbox',
		'signup_norefblock' => 'checkbox',
		'signup_requiressl' => 'checkbox',

		/* Lost Password */
		'lostpass_token' => 'checkbox',
		'lostpass_notokenban' => 'checkbox',
		'lostpass_norefblock' => 'checkbox',
		'lostpass_requiressl' => 'checkbox',

		/* Comments */
		'comment_token' => 'checkbox',
		'comment_notokenban' => 'checkbox',
		'comment_norefblock' => 'checkbox',
		'comment_requiressl' => 'checkbox',

		/* XML RPC */
		'xmlrpc_disable' => 'checkbox',
		'xmlrpc_noauth' => 'checkbox',
		'xmlrpc_authblock' => 'checkbox',
		'xmlrpc_authban' => 'checkbox',
		'xmlrpc_requiressl' => 'checkbox',
		'xmlrpc_slowdown' => 'checkbox',
		'xmlrpc_anoncomments' => 'checkbox',
		'xmlrpc_restricted' => 'checkbox',
		// 'xmlrpc_roles' => array(); // handled below
		'xmlrpc_nopingbacks' => 'checkbox',
		'xmlrpc_noselfpings' => 'checkbox',

		/* REST API */
		'restapi_disable' => 'checkbox',
		'restapi_requiressl' => 'checkbox',
		'restapi_slowdown' => 'checkbox',
		'restapi_anoncomments' => 'checkbox',
		'restapi_restricted' => 'checkbox',
		// 'restapi_roles' => 'roles', // handled below
		'restapi_nouserlist' => 'checkbox',
		'restapi_nolinks' => 'checkbox',
		'restapi_nojsonp' => 'checkbox',
		'restapi_prefix' => 'specialtext',

		/* Admin UI */
		'current_tab' => 'general/user-actions/xml-rpc/rest-api/ip-blocklist',
	);

	foreach ($voptionkeys as $vkey => $vtype) {
		$vpostkey = 'ff_'.$vkey;
		if (isset($_POST[$vpostkey])) {$vposted = $_POST[$vpostkey];} else {$vposted = '';}

		if ($vtype == 'checkbox') {
			if ($vposted == '') {$vforcefield['settings'][$vkey] = 'no';}
			elseif ($vposted == 'yes') {$vforcefield['settings'][$vkey] = 'yes';}
		} elseif (strstr($vtype, '/')) {
			$vvalid = explode('/', $vtype);
			if (in_array($vposted, $vvalid)) {$vforcefield['settings'][$vkey] = $vposted;}
			elseif (in_array($vprevious[$vkey], $vvalid)) {$vforcefield['settings'][$vkey] = $vprevious[$vkey];}
			elseif (in_array($vdefaults[$vkey], $vvalid)) {$vforcefield['settings'][$vkey] = $vdefaults[$vkey];}
			else {$vforcefield['settings'][$vkey] = $vvalid[0];}
		} elseif ($vtype == 'numeric') {
			$vposted = absint($vposted);
			if (is_numeric($vposted)) {$vforcefield['settings'][$vkey] = $vposted;}
			elseif (is_numeric($vprevious[$vkey])) {$vforcefield['settings'][$vkey] = $vprevious[$vkey];}
			elseif (is_numeric($vdefaults[$vkey])) {$vforcefield['settings'][$vkey] = $vdefaults[$vkey];}
		} elseif ($vtype == 'email') {
			$vposted = sanitize_email($vposted);
			if ($vposted) {$vforcefield['settings'][$vkey] = $vposted;}
			else {$vforcefield['settings'][$vkey] = '';}
		} elseif ($vtype == 'usernames') {
			if (strstr($vposted, ',')) {
				$vusernames = explode(',', $vposted);
				foreach ($vusernames as $vi => $vusername) {
					$vusername = trim($vusername);
					$vuser = get_user_by('login', $vusername);
					if (!$vuser) {unset($vusername[$vi]);}
				}
				if (count($vusernames) > 0) {$vforcefield['settings'][$vkey] = implode(',', $vusernames);}
				else {$vforcefield['settings'][$vkey] = '';}
			} else {
				$vposted = trim($vposted);
				$vuser = get_user_by('login', $vposted);
				if ($vuser) {$vforcefield['settings'][$vkey] = $vposted;}
			}
		} elseif ($vtype == 'specialtext') {
			$vtest = str_replace('/', '', $vposted);
			$vcheckposted = preg_match('/^[a-zA-Z0-9_\-]+$/', $vtest);
			if ($vcheckposted) {$vforcefield['settings'][$vkey] = $vposted;}
			else {$vforcefield['settings'][$vkey] = '';}
		} elseif ($vtype == 'iptextarea') {
			// 0.9.1: added for IP list textareas
			if (trim($vposted) == '') {$vforcefield['settings'][$vkey] = '';}
			else {
				// validate textarea IP lines
				$vvalidips = array(); $vposted = stripslashes($vposted);
				if (strstr($vposted, "\n")) {$viprows = explode("\n", $vposted);}
				else {$viprows[0] = $vposted;}
				foreach ($viprows as $vi => $viprow) {
					// allowing for comma separated lines
					$viprow = trim($viprow);
					if (strstr($viprow, ",")) {
						$vips = explode(",", $viprow);
						foreach ($vips as $vip) {
							$vip = trim($vip);
							$vcheckip = forcefield_get_ip_type($vip);
							if ($vcheckip) {$vvalidips[] = $vip;}
						}
					} else {
						$vcheckip = forcefield_get_ip_type($viprow);
						if ($vcheckip) {$vvalidips[] = $viprow;}
					}
				}
				$vforcefield['settings'][$vkey] = $vvalidips;
			}
		} elseif ($vtype == 'frequency') {
			if ($vposted == '') {$vforcefield['settings'][$vkey] = $vdefaults[$vkey];}
			if (array_key_exists($vposted, $vintervals)) {$vforcefield['settings'][$vkey] = $vposted;}
		}
	}

	// handle XML RPC and REST API role restrictions
	$vforcefield['settings']['xmlrpc_roles'] = array();
	$vforcefield['settings']['restapi_roles'] = array();
	foreach ($vroles as $vrole => $vlabel) {
		$vxmlrpckey = 'ff_xmlrpc_role_'.$vrole;
		if ( (isset($_POST[$vxmlrpckey])) && ($_POST[$vxmlrpckey] == 'yes') ) {
			$vforcefield['settings']['xmlrpc_roles'][] = $vrole;
		}
		$vrestkey = 'ff_restapi_role_'.$vrole;
		if ( (isset($_POST[$vrestkey])) && ($_POST[$vrestkey] == 'yes') ) {
			$vforcefield['settings']['restapi_roles'][] = $vrole;
		}
	}

	// 0.9.1: handle transgression limit updates
	$vlimits = forcefield_blocklist_get_default_limits();
	foreach ($vlimits as $vkey => $vlimit) {
		if (isset($_POST['ff_limit_'.$vkey])) {
			$vposted = absint($_POST['ff_limit_'.$vkey]);
			if ($vposted < 0) {$vposted = 0;}
			$vforcefield['settings']['limit_'.$vkey] = $vposted;
		} else {$vforcefield['settings']['limit_'.$vkey] = $vlimit;}
	}

	// update the plugin options
	update_option('forcefield', $vforcefield['settings']);

	// maybe flush rewrite rules if REST prefix was changed
	if ($vprefix != $vforcefield['settings']['restapi_prefix']) {flush_rewrite_rules();}

	// [PRO] maybe update any pro settings
	if (function_exists('forcefield_pro_update_settings')) {forcefield_pro_update_settings();}

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

	// debug point
	// echo "ForceField Settings: <br>".PHP_EOL;
	// print_r($vforcefield['settings']);

	// get current setting tab
	$vcurrenttab = $vforcefield['settings']['current_tab'];
	if ($vcurrenttab == '') {$vcurrenttab = 'general';}

	// get all user roles
	$vroles = wp_roles()->get_names();

	// get default limits
	$vlimits = forcefield_blocklist_get_default_limits();

	// get intervals array
	$vintervals = forcefield_get_intervals();

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

	// Plugin Page Title Box
	// ---------------------
	$viconurl = plugins_url("images/forcefield.png", __FILE__);
	echo "<table><tr><td><img src='".$viconurl."'></td>".PHP_EOL;
	echo "<td width='20'></td><td>".PHP_EOL;
		echo "<table><tr><td><h2>".__('ForceField','forcefield')."</h2></td>".PHP_EOL;
		echo "<td width='20'></td>".PHP_EOL;
		echo "<td><h3>v".$vforcefieldversion."</h3></td></tr>".PHP_EOL;
		echo "<tr><td colspan='3' align='center'>".__('by','forcefield');
		echo " <a href='http://wordquest.org/' class='pagelink' target=_blank><b>WordQuest Alliance</b></a>";
		echo "</td></tr></table>".PHP_EOL;
	echo "</td><td width='50'></td>".PHP_EOL;

	// welcome / update / reset messages
	if ( (isset($_REQUEST['welcome'])) && ($_REQUEST['welcome'] == 'true') ) {
		$vupdatemessage = __('Welcome! For usage see','forcefield')." <i>readme.txt</i> FAQ";
	}
	if (isset($_REQUEST['updated'])) {
		if ($_REQUEST['updated'] == 'yes') {$vupdatemessage = __('Settings Updated.','forcefield');}
		if ($_REQUEST['updated'] == 'reset') {$vupdatemessage == __('Settings Reset.','forcefield');}
	}
	if (isset($vupdatemessage)) {
		echo "<td><table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>".PHP_EOL;
		echo "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>".PHP_EOL;
		echo $vupdatemessage."</font></div></td></tr></table></td>".PHP_EOL;
	}
	echo "</tr></table><br>".PHP_EOL;

	// admin page styles
	echo "<style>.pagelink {text-decoration:none;} .pagelink:hover {text-decoration:underline;}
	.checkbox-cell {max-width:40px !important;}
	.valigntop {vertical-align:top;}
	.role-box {display:inline-block; float:left; margin-right:20px;}
	.role-cell {max-width:350px;}
	.tab-button {width:100px; height:30px; background-color:#DDDDDD;
		border:1px solid #000; text-align:center;}
	.tab-button:hover {background-color:#EEEEEE; font-weight:bold;}</style>";

	// admin page scripts
	$vadminajax = admin_url('admin-ajax.php');
	$vconfirmreset = __('Are you sure you want to Reset to Default Settings?','forcefield');
	$vconfirmclear = __('Are you sure you want to Clear the Entire IP Blocklist?','forcefield');
	$vunblocknonce = wp_create_nonce('forcefield_unblock');
	echo "<script>function showtab(tab) {
		document.getElementById('current-tab').value = tab;
		console.log(document.getElementById('current-tab').value);
		document.getElementById('general').style.display = 'none';
		document.getElementById('user-actions').style.display = 'none';
		document.getElementById('xml-rpc').style.display = 'none';
		document.getElementById('rest-api').style.display = 'none';
		document.getElementById('ip-blocklist').style.display = 'none';
		document.getElementById(tab).style.display = '';
		document.getElementById('general-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('user-actions-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('xml-rpc-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('rest-api-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('ip-blocklist-button').style.backgroundColor = '#DDDDDD';
		document.getElementById(tab+'-button').style.backgroundColor = '#F0F0F0';
		if (tab == 'ip-blocklist') {
			document.getElementById('update-buttons').style.display = 'none';
		} else {document.getElementById('update-buttons').style.display = '';}
	}
	function confirmreset() {
		var ask = '".$vconfirmreset."'; agree = confirm(ask); if (!agree) {return false;}
	}
	function confirmblocklistclear() {
		var ask = '".$vconfirmclear."'; agree = confirm(ask); if (!agree) {return false;}
	}
	function unblockip(ip,label) {
		ip = encodeURIComponent(ip);
		unblockurl = '".$vadminajax."?action=forcefield_unblock_ip&_wpnonce=".$vunblocknonce."&ip='+ip;
		if (label != '') {unblockurl += label;}
		document.getElementById('blocklist-action-frame').src = unblockurl;
	}</script>";

	// 0.9.3: show current IP addresses
	$vserverip = forcefield_get_server_ip(); $vclientip = forcefield_get_remote_ip(true);
	echo '<table width="100%"><tr><td width="50%"><b>'.__('Server IP','forcefield').'</b>: '.$vserverip.'</td>';
	echo '<td width="50%"><b>'.__('Client IP (You)','forcefield').'</b>: '.$vclientip.'</td></tr></table>';

	// settings tab selector buttons
	echo '<ul style="list-style:none;">'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'general\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="general-button" class="tab-button"';
			if ($vcurrenttab == 'general') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('General','forcefield').'</div></a></li>'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'user-actions\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="user-actions-button" class="tab-button"';
			if ($vcurrenttab == 'user-actions') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('User Actions','forcefield').'</div></a></li>'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'xml-rpc\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="xml-rpc-button" class="tab-button"';
			if ($vcurrenttab == 'xml-rpc') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('XML RPC','forcefield').'</div></a></li>'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'rest-api\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="rest-api-button" class="tab-button"';
			if ($vcurrenttab == 'rest-api') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('REST API','forcefield').'</div></a></li>'.PHP_EOL;
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'ip-blocklist\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="ip-blocklist-button" class="tab-button"';
			if ($vcurrenttab == 'ip-blocklist') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('IP Blocklist','forcefield').'</div></a></li>'.PHP_EOL;
	echo '</ul>';

	// start update form
	echo '<form action="admin.php?page=forcefield&updated=yes" method="post">'.PHP_EOL;
	echo '<input type="hidden" name="forcefield_update_settings" value="yes">'.PHP_EOL;
	echo '<input type="hidden" id="current-tab" name="ff_current_tab" value="'.$vcurrenttab.'">'.PHP_EOL;
		wp_nonce_field('forcefield_update');

		// =======
		// General
		// =======
		echo '<div id="general"';
			if ($vcurrenttab != 'general') {echo ' style="display:none;"';}
		echo '><table>';

		// ----------------
		// Admin Protection
		// ----------------
		echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Admin Protection','forcefield').'</h3></td></tr>';

		// admin login fail limit (admin_fail)
		echo '<tr><td><b>'.__('Failed Admin Login Limit','forcefield').'</b></td><td width="20"></td>';
			$vlimit = forcefield_get_setting('limit_admin_fail', false);
		echo '<td><input style="width:40px;" type="number" name="ff_limit_admin_fail" value="'.$vlimit.'"></td>';
		echo '<td width="10"></td>';
		echo '<td>'.__('Admin Login Failures before IP Address is Banned.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('default','forcefield').': '.$vlimits['admin_fail'].')</td></tr>';

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


		// -----------------
		// Blocklist Options
		// -----------------
		echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Blocklist Options','forcefield').'</h3></td></tr>';

		// token expiry time
		echo '<tr><td><b>'.__('Action Token Expiry','forcefield').'</b></td><td width="20"></td>';
			$vexpiry = forcefield_get_setting('blocklist_tokenexpiry', false);
		echo '<td><input style="width:50px;" type="number" name="ff_blocklist_tokenexpiry" min="1" value="'.$vexpiry.'"></td>';
		echo '<td width="10"></td>';
		echo '<td>'.__('Length of time that action tokens are valid.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('default','forcefield').': 300)</td></tr>';

		// missing token records
		echo '<tr><td><b>'.__('Record Missing Tokens?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_notoken" value="yes"';
		 	if (forcefield_get_setting('blocklist_notoken', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Record IP of User Actions Missing Tokens.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// missing token limit (no_token)
		echo '<tr><td><b>'.__('Missing Token Limit?','forcefield').'</b></td><td width="20"></td>';
			$vlimit = forcefield_get_setting('limit_no_token', false);
		echo '<td><input style="width:40px;" type="number" name="ff_limit_no_token" value="'.$vlimit.'"></td>';
		echo '<td width="10"></td>';
		echo '<td>'.__('No Referer Transgressions before IP Address is Banned.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('default','forcefield').': '.$vlimits['no_token'].')</td></tr>';

		// missing token records
		echo '<tr><td><b>'.__('Record Bad Tokens?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_badtoken" value="yes"';
		 	if (forcefield_get_setting('blocklist_badtoken', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Record IP of User Actions with Bad Tokens.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// bad token limit (bad_token)
		echo '<tr><td><b>'.__('Bad Token Limit?','forcefield').'</b></td><td width="20"></td>';
			$vlimit = forcefield_get_setting('limit_bad_token', false);
		echo '<td><input style="width:40px;" type="number" name="ff_limit_bad_token" value="'.$vlimit.'"></td>';
		echo '<td width="10"></td>';
		echo '<td>'.__('Bad Token Transgressions before IP Address is Banned.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('default','forcefield').': '.$vlimits['bad_token'].')</td></tr>';

		// missing referer records
		echo '<tr><td><b>'.__('Record Missing Referer?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_noreferer" value="yes"';
			if (forcefield_get_setting('blocklist_noreferer', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Record IP of User Actions Missing Referer Header.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// missing referrer limit (no_referer)
		echo '<tr><td><b>'.__('Missing Referer Limit?','forcefield').'</b></td><td width="20"></td>';
			$vlimit = forcefield_get_setting('limit_no_referer', false);
		echo '<td><input style="width:40px;" type="number" name="ff_limit_no_referer" value="'.$vlimit.'"></td>';
		echo '<td width="10"></td>';
		echo '<td>'.__('No Referer Transgressions before IP Address is Banned.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('default','forcefield').': '.$vlimits['no_referer'].')</td></tr>';

		// ...other transgression limits..?

		// allow user unblocking (via form)
		echo '<tr><td><b>'.__('Manual User Unblocking?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_unblocking" value="yes"';
			if (forcefield_get_setting('blocklist_unblocking', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Allows visitors to unblock their IP manually via a form.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// IP Whitelist (textarea)
		$vipwhitelist = forcefield_get_setting('blocklist_whitelist', false);
		if (is_array($vipwhitelist)) {$vipwhitelist = implode("\n", $vipwhitelist);} else {$vipwhitelist = '';}
		echo '<tr><td class="valigntop"><b>'.__('Manual IP Whitelist','forcefield').'</b></td><td width="20"></td>';
		echo '<td colspan="3"><textarea class="ip-textarea" rows="3" name="ff_blocklist_whitelist">'.$vipwhitelist.'</textarea></td></tr>';

		// IP Blacklist (textarea)
		$vipblacklist = forcefield_get_setting('blocklist_blacklist', false);
		if (is_array($vipblacklist)) {$vipblacklist = implode("\n", $vipblacklist);} else {$vipblacklist = '';}
		echo '<tr><td class="valigntop"><b>'.__('Manual IP Blacklist','forcefield').'</b></td><td width="20"></td>';
		echo '<td colspan="3"><textarea class="ip-textarea" rows="3" name="ff_blocklist_blacklist">'.$vipblacklist.'</textarea></td></tr>';

		// blocklist expiry time (blocklist_cooldown)
		echo '<tr><td class="valigntop"><b>'.__('Block Cooldown Time','forcefield').'</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_cooldown">';
		echo '<option value="none">'.__('None','forcefield').'</option>';
		$vselected = forcefield_get_setting('blocklist_cooldown', false);
		foreach ($vintervals as $vkey => $vinterval) {
			echo '<option value="'.$vkey.'"';
			if ($vselected == $vkey) {echo ' selected="selected"';}
			echo '>'.$vinterval['display'].'</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo __('How often trangressions are reduced over time.','forcefield').'</div></td></tr>';

		// blocklist expiry time (blocklist_expiry)
		echo '<tr><td class="valigntop"><b>'.__('Block Expiry Time','forcefield').'</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_expiry">';
		echo '<option value="none">'.__('None','forcefield').'</option>';
		$vselected = forcefield_get_setting('blocklist_expiry', false);
		foreach ($vintervals as $vkey => $vinterval) {
			echo '<option value="'.$vkey.'"';
			if ($vselected == $vkey) {echo ' selected="selected"';}
			echo '>'.$vinterval['display'].'</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo __('How long before an IP block expires.','forcefield').'</div></td></tr>';

		// blocklist delete time (blocklist_delete)
		echo '<tr><td class="valigntop"><b>'.__('Block Delete Time','forcefield').'</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_delete">';
		echo '<option value="none">'.__('None','forcefield').'</option>';
		$vselected = forcefield_get_setting('blocklist_delete', false);
		foreach ($vintervals as $vkey => $vinterval) {
			echo '<option value="'.$vkey.'"';
			if ($vselected == $vkey) {echo ' selected="selected"';}
			echo '>'.$vinterval['display'].'</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo __('How long before an IP record is deleted.','forcefield').'</div></td></tr>';

		// blocklist cleanup frequency (blocklist_cleanups)
		echo '<tr><td class="valigntop"><b>'.__('CRON Cleanups','forcefield').'</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_cleanups">';
		echo '<option value="none">'.__('None','forcefield').'</option>';
		$vselected = forcefield_get_setting('blocklist_cleanups', false);
		foreach ($vintervals as $vkey => $vinterval) {
			echo '<option value="'.$vkey.'"';
			if ($vselected == $vkey) {echo ' selected="selected"';}
			echo '>'.$vinterval['display'].'</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo __('How often blocklist cleanups are scheduled.','forcefield').'</div></td></tr>';

		echo '</table></div>'; // close user access tab


		// ============
		// User Actions
		// ============
		echo '<div id="user-actions"';
			if ($vcurrenttab != 'user-actions') {echo ' style="display:none;"';}
		echo '><table>';

		// -----
		// Login
		// -----
		echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Login','forcefield').'</h3></td></tr>';

		// login referer check
		echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_norefblock" value="yes"';
			if (forcefield_get_setting('login_norefblock', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Block Login if missing HTTP Referer Header.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// login token
		echo '<tr><td><b>'.__('Require Login Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_token" value="yes"';
			if (forcefield_get_setting('login_token', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Require Automatic Token for Login.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// login instant ban
		echo '<tr><td><b>'.__('InstaBan if Missing Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_notokenban" value="yes"';
			if (forcefield_get_setting('login_notokenban', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Instantly Ban IP Address if missing Login Token.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// require SSL for login
		echo '<tr><td><b>'.__('Require SSL for Login?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_requiressl" value="yes"';
			if (forcefield_get_setting('login_requiressl', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Require Secure Connection for User Login.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// disable login hints
		echo '<tr><td><b>'.__('Disable Login Hints (Errors)?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_nohints" value="yes"';
			if (forcefield_get_setting('login_nohints', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Disables Login Error Output Hints on Login Screen.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// ------------
		// Registration
		// ------------
		echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Registration','forcefield').'</h3></td></tr>';

		// registration referer check
		echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_norefblock" value="yes"';
			if (forcefield_get_setting('register_norefblock', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Block Registration if missing HTTP Referer Header.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// registration token
		echo '<tr><td><b>'.__('Require Registration Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_token" value="yes"';
			if (forcefield_get_setting('register_token', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Require Automatic Token for Registration.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// missing registration token auth ban
		echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_notokenban" value="yes"';
			if (forcefield_get_setting('register_notokenban', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Instantly Ban IP Address if missing Registration Token.','forcefield').'</td>';
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
		echo '<td> </td><td> </td><td> </td><td><b>'.__('Affects Multisite Only','forcefield').'</b></td></tr>';

		// signup referer check
		echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_norefblock" value="yes"';
			if (forcefield_get_setting('signup_norefblock', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Block Blog Signup if missing HTTP Referer.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// signup token
		echo '<tr><td><b>'.__('Require Blog Signup Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_token" value="yes"';
			if (forcefield_get_setting('signup_token', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Require Automatic Token for Blog Signup.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// missing signup token auth ban
		echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_notokenban" value="yes"';
			if (forcefield_get_setting('signup_notokenban', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Instantly Ban IP Address if missing Blog Signup Token.','forcefield').'</td>';
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
		echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_norefblock" value="yes"';
			if (forcefield_get_setting('comment_norefblock', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Block Comment if missing HTTP Referer Header.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// comment token
		echo '<tr><td><b>'.__('Require Comment Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td><input type="checkbox" name="ff_comment_token" value="yes"';
			if (forcefield_get_setting('comment_token', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Require Automatic Token for Commenting.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// comment auth ban
		echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_notokenban" value="yes"';
			if (forcefield_get_setting('comment_notokenban', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Instantly Ban IP Address if missing Comment Token.','forcefield').'</td>';
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
		echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_norefblock" value="yes"';
			if (forcefield_get_setting('lostpass_norefblock', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Block Lost Password request is missing HTTP Referer.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// lost password token
		echo '<tr><td><b>'.__('Require Lost Password Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_token" value="yes"';
			if (forcefield_get_setting('lostpass_token', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Require Automatic Token for Lost Password form.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// lost password auth ban
		echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_notokenban" value="yes"';
			if (forcefield_get_setting('lostpass_notokenban', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Instantly Ban IP Address if missing Lost Password Token.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// require SSL for lost password
		echo '<tr><td><b>'.__('Require SSL for Lost Password?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_requiressl" value="yes"';
			if (forcefield_get_setting('lostpass_requiressl', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Require Secure Connection for Lost Password form.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		echo '</table></div>'; // close user action tab


		// =======
		// XML RPC
		// =======
		echo '<div id="xml-rpc"';
			if ($vcurrenttab != 'xml-rpc') {echo ' style="display:none;"';}
		echo '><table>';

		echo '<tr><td><h3 style="margin-bottom:10px;">XML RPC</h3></td></tr>';

		// disable XML RPC
		echo '<tr><td><b>'.__('Disable XML RPC?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_disable" value="yes"';
			if (forcefield_get_setting('xmlrpc_disable', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Disable XML RPC Entirely.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('not recommended','forcefield').')</td></tr>';

		// disable XML RPC auth attempts
		echo '<tr><td><b>'.__('Disable XML RPC Logins?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authblock" value="yes"';
			if (forcefield_get_setting('xmlrpc_authblock', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('All Login attempts via XML RPC will be blocked.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// ban XML RPC auth attempts
		echo '<tr><td><b>'.__('AutoBan IP for XML RPC Auth Attempts?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authban" value="yes"';
			if (forcefield_get_setting('xmlrpc_authban', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('ANY login attempts via XML RPC will result in an IP ban.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// failed XML RPC login limit (xmlrpc_authfail)
		echo '<tr><td><b>'.__('Failed XML RPC Login Limit','forcefield').'</b></td><td width="20"></td>';
			$vlimit = forcefield_get_setting('limit_xmlrpc_authfail', false);
		echo '<td><input style="width:40px;" type="number" name="ff_limit_xmlrpc_authfail" value="'.$vlimit.'"></td>';
		echo '<td width="10"></td>';
		echo '<td>'.__('XML RPC Login Failures before IP Address is Banned.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('default','forcefield').': '.$vlimits['xmlrpc_authfail'].')</td></tr>';

		// require SSL
		echo '<tr><td><b>'.__('Require SSL for XML RPC?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_requiressl" value="yes"';
			if (forcefield_get_setting('xmlrpc_requiressl', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Only allow XML RPC access via SSL.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		echo '<tr><td><b>'.__('Rate Limit XML RPC?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_slowdown" value="yes"';
			if (forcefield_get_setting('xmlrpc_slowdown', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Slowdown via a Rate Limiting Delay.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		echo '<tr><td><b>'.__('Disable XML RPC Anonymous Comments?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_anoncomments" value="yes"';
			if (forcefield_get_setting('xmlrpc_anoncomments', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Disable Anonymous Commenting via XML RPC.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// role restrict XML RPC access
		echo '<tr><td class="valigntop"><b>'.__('Restrict XML RPC Access?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_xmlrpc_restricted" value="yes"';
			if (forcefield_get_setting('xmlrpc_restricted', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td class="valigntop">'.__('Restrict XML RPC Access to Selected Roles.','forcefield').'<br>';
		echo __('Note: Enforces Logged In Only Access','forcefield').'</td>';
		echo '<td width="10"></td><td class="valigntop">('.__('optional','forcefield').')</td></tr>';

		// roles for XML RPC role restriction
		$vxmlrpcroles = forcefield_get_setting('xmlrpc_roles', false);
		if (!is_array($vxmlrpcroles)) {$vxmlrpcroles = array();}
		echo '<tr><td class="valigntop"><b>'.__('Restrict to Selected Roles','forcefield').'</b></td>';
		echo '<td width="20"></td><td></td><td></td>';
		echo '<td class="valigntop" colspan="3">';
		foreach ($vroles as $vslug => $vlabel) {
			echo '<div class="role-box">';
			echo '<input type="checkbox" name="ff_xmlrpc_role_'.$vslug.'" value="yes"';
				if (in_array($vslug, $vxmlrpcroles)) {echo ' checked';}
			echo '> '.$vlabel.'</div>';
		}
		echo '</td></tr>';

		// disable pingbacks
		echo '<tr><td><b>'.__('Disable Pingback Processing?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_nopingbacks" value="yes"';
		if (forcefield_get_setting('xmlrpc_nopingbacks', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Disable XML RPC Pingback processing.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('not recommended','forcefield').')</td></tr>';

		// disable self pings
		echo '<tr><td><b>'.__('Disable Self Pings?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_noselfpings" value="yes"';
			if (forcefield_get_setting('xmlrpc_noselfpings', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Disable Pingbacks from this site to itself.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		echo '</table>'; // close XML RPC option table

		// 0.9.1: [PRO] XML RPC Method Restriction Options
		if (function_exists('forcefield_pro_method_options')) {forcefield_pro_method_options();}

		echo '</div>'; // close XML RPC tab


		// ========
		// REST API
		// ========

		echo '<div id="rest-api"';
			if ($vcurrenttab != 'rest-api') {echo ' style="display:none;"';}
		echo '><table>';

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
		echo '<td>'.__('REST API access for authenticated users only.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

		// require SSL
		echo '<tr><td><b>'.__('Require SSL for REST API?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_requiressl" value="yes"';
			if (forcefield_get_setting('restapi_requiressl', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Only allow REST API access via SSL.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// rate limiting for REST
		echo '<tr><td class="valigntop"><b>'.__('Rate Limit REST API?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_restapi_slowdown" value="yes"';
			if (forcefield_get_setting('restapi_slowdown', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td class="valigntop">'.__('Slowdown via a Rate Limiting Delay.','forcefield').'</td>';
		echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// role restrict REST API access
		echo '<tr><td class="valigntop"><b>'.__('Restrict REST API Access?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_restapi_restricted" value="yes"';
			if (forcefield_get_setting('restapi_restricted', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td class="valigntop">'.__('Restrict REST API Access to Selected Roles.','forcefield').'<br>';
		echo __('Note: Enforces Logged In Only Access','forcefield').'</td>';
		echo '<td width="10"></td><td class="valigntop">('.__('optional','forcefield').')</td></tr>';

		// roles for REST API role restriction
		$vrestroles = forcefield_get_setting('restapi_roles', false);
		if (!is_array($vrestroles)) {$vrestroles = array();}
		echo '<tr><td class="valigntop"><b>'.__('Restrict to Selected Roles','forcefield').'</b></td>';
		echo '<td width="20"></td><td></td><td></td>';
		echo '<td class="valigntop" colspan="3">';
		foreach ($vroles as $vslug => $vlabel) {
			echo '<div class="role-box">';
			echo '<input type="checkbox" name="ff_restapi_role_'.$vslug.'" value="yes"';
				if (in_array($vslug, $vrestroles)) {echo ' checked';}
			echo '> '.$vlabel.'</div>';
		}
		echo '</td></tr>';

		// disable User List Endpoint
		echo '<tr><td><b>'.__('Disable Userlist Endpoint?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nouserlist" value="yes"';
			if (forcefield_get_setting('restapi_nouserlist', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('Disable the REST API User Enumeration Endpoint.','forcefield').'</td></tr>';

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

		// REST API prefix (default: "wp-json")
		echo '<tr><td style="vertical-align:top;"><b>'.__('Prefix for REST API Access?','forcefield').'</b></td><td colspan="3"></td>';
		echo '<td colspan="5"><input type="text" name="ff_restapi_prefix" value="';
			echo forcefield_get_setting('restapi_prefix', false);
		echo '"><br>'.__('Leave blank for no change. Default','forcefield').': "wp-json"';
		echo '<br>'.__('Note: you may need to resave permalinks to effect.','forcefield');
		echo '</td></tr>';

		echo '</table>'; // close REST API option table

		// 0.9.1: [PRO] Endpoint Restriction Options
		if (function_exists('forcefield_pro_endpoint_options')) {forcefield_pro_endpoint_options();}

		echo '</div>'; // close REST API tab

	// Reset and Update Buttons
	// ------------------------
	echo '<div id="update-buttons"><br><table style="width:100%;">'.PHP_EOL;
		// settings update Submit Button
		echo '<tr><td width="33%"></td><td width="33%" style="text-align:center;">'.PHP_EOL;
		echo '<input type="submit" class="button-primary" value="'.__('Update Settings','forcefield').'">'.PHP_EOL;
		echo '</td></form>'; // close settings form
		// settings reset button
		echo '<td width="33%" style="text-align:right;">'.PHP_EOL;
		echo '<form action="admin.php?page=forcefield&updated=reset" method="post">'.PHP_EOL;
		echo '<input type="hidden" name="forcefield_update_settings" value="yes">'.PHP_EOL;
		echo '<input type="hidden" name="forcefield_reset_settings" value="yes">'.PHP_EOL;
			wp_nonce_field('forcefield_update');
		echo '<input type="submit" value="'.__('Reset to Defaults','forcefield').'" class="button-secondary" onclick="return confirmreset();" style="margin-right:20px;">'.PHP_EOL;
		echo '</form></td></tr>'.PHP_EOL;
	echo '</table><br></div>'.PHP_EOL; // close buttons div


	// ============
	// IP Blocklist
	// ============

	echo '<div id="ip-blocklist" style="min-height:500px;';
		if ($vcurrenttab != 'ip-blocklist') {echo ' display:none;';}
	echo '">';

	// 0.9.2: [PRO] Manual IP Whitelist / Blacklist (with context / expiry options)
	if (function_exists('forcefield_pro_lists_interface')) {forcefield_pro_lists_interface();}

	echo '<h3 style="margin-bottom:10px;">'.__('IP Blocklist','forcefield').'</h3>';

	// 0.9.1: IP Blocklist with removal buttons
	$vreasons = forcefield_blocklist_get_reasons();
	$vcolumns = array('ip', 'label', 'transgressions', 'last_access_at', 'created_at');
	// note other columns: 'id', 'list', 'ip6', 'is_range', 'deleted_at'
	$vblocklist = forcefield_blocklist_get_records(false, false, $vcolumns, true);

	if ($vblocklist && (count($vblocklist) > 0) ) {

		// clear entire blocklist button
		echo '<div style="width:100%;text-align:center;">';
		echo '<form action="'.$vadminajax.'" target="blocklist-action-frame">';
		echo '<input type="hidden" name="action" value="forcefield_blocklist_clear">';
			wp_nonce_field('forcefield_clear');
		echo '<input type="submit" class="button-secondary" value="'.__('Clear Entire IP Blocklist','forcefield').'" onclick="return confirmblocklistclear();">';
		echo '</form></div><br>'.PHP_EOL;

		// TODO: add sortable columns and/or pagination
		// - group records by IP address to show activity?
		// - group records by activity to show patterns?

		echo '<div id="blocklist-table"><table><tr>';
		echo '<td>'.__('IP Address','forcefield').'</td><td width="10"></td>';
		echo '<td>'.__('Block Reason','forcefield').'</td><td width="10"></td>';
		echo '<td>'.__('Transgressions','forcefield').'</td><td width="10"></td>';
		echo '<td>'.__('Blocked?','forcefield').'</td><td width="10"></td>';
		echo '<td>'.__('First Access','forcefield').'</td><td width="10"></td>';
		echo '<td>'.__('Last Access','forcefield').'</td><td width="10"></td>';
		echo '<td></td></tr>'.PHP_EOL;


		foreach ($vblocklist as $vrow) {
			echo '<tr><td>'.$vrow['ip'].'</td><td></td>';
			echo '<td><div title="'.$vreasons[$vrow['label']].'">'.$vrow['label'].'</div></td><td></td>';
			echo '<td>'.$vrow['transgressions'].'</td><td></td>';
			echo '<td>';
				// red X indicates blocked IP
				$vlimit = forcefield_get_setting('limit_'.$vrow['label'], false);
				if ($vrow['transgressions'] > $vlimit) {echo '<font color="#E00;">X</font>';}
			echo '</td>';
			echo '<td>'.date('H:i:s d-m-Y', $vrow['created_at']).'</td><td></td>';
			echo '<td>'.date('H:i:s d-m-Y', $vrow['last_access_at']).'</td><td></td>';
			//  record row removal button
			echo '<td><input type="button" value="'.__('Delete','forcefield').'" onclick="unblockip(\''.$vrow['ip'].'\',\''.$vrow['label'].'\');"></td>';
			// full IP unblock button
			echo '<td><input type="button" value="'.__('Unblock IP','forcefield').'" onclick="unblockip(\''.$vrow['ip'].'\',\'\');"></td>';
			echo '</tr>'.PHP_EOL;
		}

		echo '</table></div>'.PHP_EOL; // close IP blocklist table

	} else {echo '<b>'.__('No IP Transgressions Recorded Yet.','forcefield').'</b><br>'.PHP_EOL;}

	// blocklist action iframe
	echo '<iframe style="display:none;" id="blocklist-action-frame" name="blocklist-action-frame" src="javascript:void(0);" frameborder=0></iframe>'.PHP_EOL;

	echo '</div>'.PHP_EOL; // close IP blocklist div

	echo '</div></div>'.PHP_EOL; // close #wrapbox

	echo '</div>'.PHP_EOL; // close #pagewrap

}


// =================
// GENERAL FUNCTIONS
// =================

// Simple Alert Message
// --------------------
function forcefield_alert_message($vmessage) {echo "<script>alert('".$vmessage."');</script>";}

// get Remote IP Address
// ---------------------
function forcefield_get_remote_ip($vdebug=false) {

	// 0.9.3: get server IP to match against
	$vserverip = forcefield_get_server_ip($vdebug);

	// 0.9.3: check remote address keys, filtering out server IP
	$vipkeys = forcefield_get_remote_ip_keys();
	foreach ($vipkeys as $vipkey) {
		if ( (isset($_SERVER[$vipkey])) && (!empty($_SERVER[$vipkey])) ) {
			$vip = $_SERVER[$vipkey];
			if ( ($vip != $vserverip) && ($vip != '127.0.0.1') && ($vip != 'localhost') ) {
				$viptype = forcefield_get_ip_type($_SERVER[$vipkey]);
				if ($vdebug) {echo "<!-- \$_SERVER[".$vipkey."] : ".$vip." -->";}
				if ($viptype) {return $_SERVER[$vipkey];}
			}
		}
	}
	return false;

	// 0.9.3: [deprecated] old method, somewhat less reliable
	// if (!empty($_SERVER['HTTP_CLIENT_IP'])) {$vip = $_SERVER['HTTP_CLIENT_IP'];}
	// elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) && (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {$vip = $_SERVER['HTTP_X_FORWARDED_FOR'];}
	// else {$vip = $_SERVER['REMOTE_ADDR'];}
	// return $vip;
}

// get IP Address keys
// -------------------
// 0.9.3: set possible $_SERVER keys for IP
function forcefield_get_remote_ip_keys() {
	$vipkeys = array(
		'REMOTE_ADDR',
		'HTTP_CF_CONNECTING_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_REAL_IP',
		'HTTP_X_SUCURI_CLIENTIP',
		'HTTP_INCAP_CLIENT_IP',
		'HTTP_FORWARDED',
		'HTTP_CLIENT_IP'
	);
	$vipkeys = apply_filters('forcefield_remote_ip_keys', $vipkeys);
	return $vipkeys;
}

// get Server IP Address
// ---------------------
function forcefield_get_server_ip($vdebug=false) {
	$vserverip = get_transient('forcefield_server_ip');
	if ($vserverip) {return $vserverip;}

	if (function_exists('gethostbyname')) {
		// use DNS lookup of the server host name
		$vhostname = $_SERVER['HTTP_HOST'];
		if ($vdebug) {echo "<!-- Host Name: ".$vhostname." -->";}
		$vserverip = gethostbyname($vhostname);
	} else {
		// ping IP server to reliably get server IP
		$vurl = 'http://api.ipify.org/';
		$vresponse = wp_remote_request($vurl, array('method' => 'GET'));
		if (is_wp_error($vresponse)) {return false;}
		if ( (!isset($vresponse['response']['code'])) || ($vresponse['response']['code'] != 200) ) {return false;}
		$vserverip = $vresponse['body'];
	}
	if (!forcefield_get_ip_type($vserverip)) {return false;}
	if ($vdebug) {echo "<!-- Server IP: ".$vserverip." -->";}

	// store IP response so retrieved once daily max
	set_transient('forcefield_server_ip', $vserverip, (24*60*60));
	return $vserverip;
}

// get IP Address Type
// -------------------
// 0.9.1: use helper to determine IP address type
function forcefield_get_ip_type($vip) {
	// ref: https://www.mikemackintosh.com/5-tips-for-working-with-ipv6-in-php/
	if (filter_var($vip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {return 'ip4';}
	elseif (filter_var($vip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {return 'ip6';}
	else {return false;}
}

// check if IP is in IP Range
// --------------------------
// 0.9.2: check IP is in a provided IP range (without using ip2long)
function forcefield_is_ip_in_range($vip, $viprange) {
	$viptype = forcefield_get_ip_type($vip);
	if ($viptype == 'ip4') {
		$vipparts = explode('.', $vip);
		$vrangeparts = explode('.', $viprange);
		if ($vipparts[0] != $vrangeparts[0]) {return false;}
		for ($vi = 1; $vi < 4; $vi++) {
			$vmatch = false;
			if ($vrangeparts[$vi] == '*') {$vmatch = true;}
			elseif ( ($vipparts[$vi] == $vrangeparts[$vi]) ) {$vmatch = true;}
			elseif (strstr($vrangeparts[$vi], '-')) {
				$vmaxmin = explode('-', $vrangeparts);
				if ( ($vipparts[$vi] >= $vmaxmin[0])
				  && ($vipparts[$vi] <= $vmaxmin[1]) ) {$vmatch = true;}
			}
			if (!$vmatch) {return false;}
		}
	} elseif ($viptype == 'ip6') {
		// TODO: handle IP6 ranges
		return false; // TEMP
	}
	return true;
}

// 403 Forbidden and Exit
// ----------------------
function forcefield_forbidden_exit() {
	// status_header('403', 'HTTP/1.1 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	header('Status: 403 Forbidden');
	header('Connection: Close'); exit;
}

// Filtered WP Error
// -----------------
// 0.9.1: added abstract error wrapper
function forcefield_filtered_error($verror, $verrormessage, $vstatus=false, $verrors=false) {
	if (!$vstatus) {$vstatus = 403;}
	$verrormessage = apply_filters('forcefield_error_message_'.$verror, $verrormessage);
	if ( $verrors && (is_wp_error($verrors)) ) {
		$verrors->add($verror, $verrormessage, array('status' => $vstatus));
		return $verrors;
	} else {return new WP_Error($verror, $verrormessage, array('status' => $vstatus));}
}

// maybe filter the Login Error Messages (Hints)
// -------------------------------------
add_filter('login_errors', 'forcefield_login_error_message');
function forcefield_login_error_message($vmessage) {
	$vremovehints = forcefield_get_setting('login_nohints');
	if ($vremovehints == 'yes') {return '';}
	return $vmessage;
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
			$vadminemail = forcefield_get_setting('admin_email');
			$valertemail = forcefield_get_setting('admin_alert');

			if ( ($valertemail == 'yes') && ($vadminemail != '') ) {
				// set mail from name
				add_filter('wp_mail_from_name', 'forcefield_email_from_name');

				// set email subject and body
				$vblogname = get_bloginfo('name');
				$vsubject = '[ForceField] Warning: Unwhitelisted Administrator Login!';
				$vbody = 'ForceField plugin has blocked an unwhitelisted administrator login'."\n";
				$vbody .= 'to WordPress site '.$vblogname.' ('.home_url().')'."\n\n";
				$vbody .= 'Username Blocked: "'.$user->data->user_login.'"'."\n\n";
				$vbody .= 'If this username is familiar, add it to your whitelist to stop further alerts.'."\n";
				$vbody .= 'But if it is unfamiliar, your site security may be compromised.'."\n\n";
				if ($vautodelete) {
					$vbody .= 'Additionally, according to your ForceField plugin settings,'."\n";
					$vbody .= 'the user "'.$user->data->user_login.'" was automatically deleted.'."\n\n";
				}
				$vbody .= 'Below is a dump of the user object for "'.$user->data->user_login.'"'."\n";
				$vbody .= '----------'."\n";
				$vbody .= print_r($user, true);
				wp_mail($vadminemail, $vsubject, $vmessage);
			}

			// add IP address to blocklist
			forcefield_blocklist_record_ip('admin_bad');

			// maybe autodelete unwhitelisted admin!
			$vautodelete = forcefield_get_setting('admin_autodelete');
			if ($vautodelete) {
				if (!function_exists('wp_delete_user')) {include(ABSPATH.WPINC.'/user.php');}
				wp_delete_user($user->data->ID);
				wp_cache_delete($user->data->ID, 'users');
				wp_cache_delete($user->data->user_login, 'userlogins');
			}
			wp_logout(); exit;
		}
	}
}

// Email Alerts From Name
// ----------------------
function forcefield_email_from_name() {
	// 0.9.1: forcefield-specific filter for the email from name
	return apply_filters('forcefield_emails_from_name', get_bloginfo('name'));
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
add_filter('xmlrpc_methods', 'forcefield_xmlrpc_disable');
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
add_filter('xmlrpc_methods', 'forcefield_xmlrpc_methods', 9);
function forcefield_xmlrpc_methods($methods) {
	// maybe disable pingbacks
	$vdisable = forcefield_get_setting('xmlrpc_nopingbacks');
	if ($vdisable == 'yes') {
		if (isset($methods['pingback.ping'])) {unset($methods['pingback.ping']);}
		if (isset($methods['pingback.extensions.getPingbacks'])) {
			unset($methods['pingback.extensions.getPingbacks']);
		}
	}
	return $methods;
}

// maybe Remove XML RPC Pingback Header
// ------------------------------------
add_filter('wp_headers', 'forcefield_remove_pingback_header');
function forcefield_remove_pingback_header($headers) {
	$vdisable = forcefield_get_setting('xmlrpc_nopingbacks');
	if ($vdisable == 'yes') {unset($headers['X-Pingback']);}
	return $headers;
}

// maybe Disable Self Pings
// ------------------------
add_action('pre_ping', 'forcefield_disable_self_pings');
function forcefield_disable_self_pings($vlinks) {
	$vdisable = forcefield_get_setting('xmlrpc_noselfpings');
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
add_filter('rest_authentication_errors', 'forcefield_restapi_access', 11);
function forcefield_restapi_access($access) {

	// 0.9.2: check whitelist and blacklist
	if (forcefield_whitelist_check('apis')) {return $access;}
	if (forcefield_blacklist_check('apis')) {forcefield_forbidden_exit();}

	// maybe disabled REST API
	$vrestapidisable = forcefield_get_setting('restapi_disable');
	if ($vrestapidisable == 'yes') {
		$verrormessage = __('The REST API is disabled.','forcefield');
		$vstatus = 405; // HTTP 405: Method Not Allowed
		return forcefield_filtered_error('rest_disabled', $verrormessage, 405);
	}

	// maybe SSL connection required
	$vrequiressl = forcefield_get_setting('restapi_requiressl');
	if ( ($vrequiressl == 'yes') && !is_ssl()) {
		$verrormessage = __('SSL connection is required to access the REST API.','forcefield');
		$vstatus = 403; // HTTP 403: Forbidden
		return forcefield_filtered_error('rest_ssl_required', $verrormessage, $vstatus);
	}

	// maybe authenticated (logged in) users only
	$vrequireauth == forcefield_get_setting('restapi_authonly');
    if ( ($vrequireauth == 'yes') && !is_user_logged_in()) {
    	$vstatus = rest_authorization_required_code();
    	$verrormessage = __('You need to be logged in to access the REST API.','forcefield');
		return forcefield_filtered_error('rest_not_logged_in', $verrormessage, $vstatus);
    }

	// 0.9.1: role restricted REST API access
	$vrestricted = forcefield_get_setting('restapi_restricted');
	if ($vrestricted == 'yes') {
		if (!is_user_logged_in()) {
			// (enforced) logged in only message
			$vstatus = rest_authorization_required_code();
			$verrormessage = __('You need to be logged in to access the REST API.','forcefield');
			return forcefield_filtered_error('rest_not_logged_in', $verrormessage,  $vstatus);
    	} else {
    		// 0.9.1: check multiple roles to maybe allow access
    		$vallowedroles = forcefield_get_setting('restapi_roles');
    		if (!is_array($vallowedroles)) {$vallowedroles = array();}
    		$user = wp_get_current_user(); $userroles = $user->roles;
    		$vblock = true;
    		if (count($userroles) > 0) {
	    		foreach ($userroles as $role) {
	    			if (in_array($role, $vallowedroles)) {$vblock = false;}
	    		}
	    	}
			if (isset($vblock) && $vblock) {
				$vstatus = rest_authorization_required_code();
				$verrormessage = __('Access to the REST API is restricted.','forcefield');
				return forcefield_filtered_error('rest_restricted', $verrormessage, $vstatus);
			}
    	}
    }

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
function forcefield_jsonp_disable($venabled) {
	$vnojsonp = forcefield_get_setting('restapi_nojsonp');
	if ($vnojsonp == 'yes') {return false;}
	return $venabled;
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

// maybe Change REST API Prefix
// ----------------------------
// note: default is "wp-json"
add_filter('rest_url_prefix', 'forcefield_restapi_prefix', 100);
function forcefield_restapi_prefix($vprefix) {
	$vcustomprefix = trim(forcefield_get_setting('restapi_prefix'));
	if ($vcustomprefix != '') {$vprefix = $vcustomprefix;}
	return $vprefix;
}

// maybe Disable REST API User Enumeration Endpoint
// ------------------------------------------------
add_filter('rest_endpoints', 'forcefield_endpoint_restriction', 99);
function forcefield_endpoint_restriction($endpoints) {
	if (forcefield_get_setting('restapi_nouserlist') == 'yes') {
		if (isset($endpoints['/wp/v2/users'])) {unset($endpoints['/wp/v2/users']);}
		if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);}
 	}
    return $endpoints;
}

// maybe Remove All REST API Endpoints
// -----------------------------------
// add_action( 'plugins_loaded', 'forcefield_endpoints_remove', 0);
function forcefield_endpoints_remove() {
	remove_filter('rest_api_init', 'create_initial_rest_routes');
}

// REST Nonce Bypass
// -----------------
// 0.9.2: [TEST USE ONLY!] REST API Nonce Check Bypass
add_filter('rest_authentication_errors', 'forcefield_rest_nonce_bypass', 99);
function forcefield_rest_nonce_bypass($access) {
	if (defined('REST_NONCE_BYPASS') && REST_NONCE_BYPASS) {
		global $wp_rest_auth_cookie; $wp_rest_auth_cookie = false;
	}
	return $access;
}


// ================
// ACTION TOKENIZER
// ================

// Check for Existing Token
// ------------------------
function forcefield_check_token($vcontext) {
	global $vforcefield;

	// validate IP address to IP key
	$viptype = forcefield_get_ip_type($vforcefield['ip']);
	if ($viptype == 'ip4') {$vipaddress = str_replace('.', '-', $vforcefield['ip']);}
	elseif ($viptype == 'ip6') {$vipaddress = str_replace(':', '--', $vforcefield['ip']);}
	else {return false;}

	// return transient token value
	$vtransientid = $vcontext.'_token_'.$vipaddress;
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
		// 0.9.3: add input via dynamic javascript instead of hardcoding
		// echo '<input type="hidden" id="auth_token" name="auth_token_'.$vcontext.'" value="" />';
		echo '<span id="dynamic-tokenizer"></span>';
		echo "<script>var tokeninput = document.createElement('input');
		tokeninput.setAttribute('type', 'hidden'); tokeninput.setAttribute('value', '');
		tokeninput.setAttribute('id', 'auth_token_".$vcontext."');
		tokeninput.setAttribute('name', 'auth_token_".$vcontext."');
		document.getElementById('dynamic-tokenizer').appendChild(tokeninput);</script>";

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
add_action('wp_ajax_forcefield_comment', 'forcefield_comment_token');
function forcefield_login_token() {forcefield_output_token('login');}
function forcefield_register_token() {forcefield_output_token('register');}
function forcefield_signup_token() {forcefield_output_token('signup');}
function forcefield_lostpass_token() {forcefield_output_token('lostpass');}
function forcefield_comment_token() {forcefield_output_token('comment');}

// Token Output Abstract
// ---------------------
function forcefield_output_token($vcontext) {
	$vtoken = forcefield_create_token($vcontext);
	// 0.9.3: some extra javascript obfuscation of token value
	$vsplitstring = str_split($vtoken, 1);
	echo "<script>var bits = new Array();";
	foreach ($vsplitstring as $vi => $vchar) {echo "bits[".$vi."] = '".$vchar."'; ";}
	echo "bytes = bits.join('');
	parent.document.getElementById('auth_token_".$vcontext."').value = bytes;</script>";
	exit;
}

// Create a Token
// --------------
function forcefield_create_token($vcontext) {
	global $vforcefield;

	$vtokenize = forcefield_get_setting($vcontext.'_token');
	if ($vtokenize != 'yes') {return false;}

	// maybe return existing token
	$vtoken = forcefield_check_token($vcontext);
	if ($vtoken) {return $vtoken;}

	// validate IP address and make IP key
	$viptype = forcefield_get_ip_type($vforcefield['ip']);
	if ($viptype == 'ip4') {$vip = str_replace('.', '-', $vforcefield['ip']);}
	elseif ($viptype == 'ip6') {$vip = str_replace(':', '--', $vforcefield['ip']);}
	else {return false;}

	// create token and set transient
	$vtransientid = $vcontext.'_token_'.$vip;
	$vtoken = wp_generate_password(12, false, false);
	$vexpires = forcefield_get_setting('blocklist_tokenexpiry');
	if (!is_numeric(absint($vexpires))) {$vexpires = 300;}
	set_transient($vtransientid, $vtoken, $vexpires);
	return $vtoken;
}

// Delete a Token
// --------------
function forcefield_delete_token($vcontext) {
	global $vforcefield;

	// validate IP address and make IP key
	if (filter_var($vforcefield['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$vipaddress = str_replace('.', '-', $vforcefield['ip']);
	} elseif (filter_var($vforcefield['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$vipaddress = str_replace(':', '--', $vforcefield['ip']);
	}

	// delete token transient
	$vtransientid = $vcontext.'_token_'.$vipaddress;
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
	if (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) {return $user;}

	// filter general error message
	$verrormessage = forcefield_get_setting('error_message');
	$verrormessage = apply_filters('forcefield_error_message_xmlrpc', $verrormessage);

	// 0.9.1: check IP whitelist
	if (forcefield_whitelist_check('apis')) {return $user;}
	// 0.9.2: check IP blacklist
	if (forcefield_blacklist_check('apis')) {forcefield_forbidden_exit();}

	// check for authentication block
	$vauthblock = forcefield_get_setting('xmlrpc_authblock');
	$vauthban = forcefield_get_setting('xmlrpc_authban');

	if ($vauthban == 'yes') {
		// ban this IP for XML RPC authentication violation
		forcefield_blocklist_record_ip('xmlrpc_login');
		add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_error_message_banned');
		do_action('xmlrpc_login_banned');
		return forcefield_filtered_error('xmlrpc_ban', $verrormessage);
	} elseif ($vauthblock == 'yes') {
		// 0.9.1: record anyway so a changed ban setting can take effect
		forcefield_blocklist_record_ip('xmlrpc_login');
		add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_error_message_blocked');
		do_action('xmlrpc_login_blocked');
		return forcefield_filtered_error('xmlrpc_block', $verrormessage);
	} elseif (is_wp_error($user)) {
		$vtransgressions = forcefield_blocklist_record_ip('xmlrpc_authfail');
		// 0.9.1: check against XML RPC authentication attempts limit
		$vblocked = forcefield_blocklist_check_transgressions('xmlrpc_authfail', $vtransgressions);
		if ($vblocked) {
			do_action('xmlrpc_login_toomany');
			$vstatus = 429; // 'too many requests'
			return forcefield_filtered_error('xmlrpc_toomany', $verrormessage, $vstatus);
		}
	}

	// check for SSL connection requirement
	$vrequiressl = forcefield_get_setting('xmlrpc_requiressl');
	if ( ($vrequiressl == 'yes') && !is_user_logged_in() && !is_ssl() ) {
		add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_require_ssl_message');
		// note: we need to return an error here so that the xmlrpc_login_error filter is called
		$verrormessage = __('XML RPC requires SSL Connection.','forcefield');
		return forcefield_filtered_error('xmlrpc_ssl_required', $verrormessage);
	}

	return $user;
}

// XML RPC Error Message (Banned)
// ------------------------------
function forcefield_xmlrpc_error_message_banned($error) {
	$verrormessage = __('Access denied. XML RPC authentication is disabled.','forcefield');
	$verrormessage = apply_filters('forcefield_error_message_xmlrpc_banned', $verrormessage);
	$vstatus = 405; // HTTP 405: Method Not Allowed
	return new IXR_Error($vstatus, $verrormessage);
}

// XML RPC Error Message (Blocked)
// ------------------------------
function forcefield_xmlrpc_error_message_blocked($error) {
	$verrormessage = __('Access denied. XML RPC authentication is disabled.','forcefield');
	$verrormessage = apply_filters('forcefield_error_message_xmlrpc_blocked', $verrormessage);
	$vstatus = 405; // HTTP 405: Method Not Allowed
	return new IXR_Error($vstatus, $verrormessage);
}

// XML RPC requires SSL Message
// ----------------------------
function forcefield_xmlrpc_require_ssl_message() {
	$verrormessage = __('XML RPC authentication requires an SSL Connection.','forcefield');
	$verrormessage = apply_filters('forcefield_error_message_xmlrpc_requiressl', $verrormessage);
	$vstatus = 426; // HTTP 426: Upgrade Required
	return new IXR_Error($vstatus, $verrormessage);
}

// Login Token Authentication
// --------------------------
add_filter('authenticate', 'forcefield_login_validate', 10, 3);
function forcefield_login_validate($user, $username, $password) {

	// filter general error message
	$verrormessage = forcefield_get_setting('error_message');
	$verrormessage = apply_filters('forcefield_error_message_login', $verrormessage);

	// 0.9.1: check IP whitelist
	if (forcefield_whitelist_check('actions')) {return $user;}
	// 0.9.2: check IP blacklist
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// 0.9.1: for a failed login, check if an admin account
	if ( $username && $password && is_wp_error($user) ) {
		$vcheckuser = get_user_by('login', $username);
		if (in_array('administrator', (array)$user->roles)) {
			// add a record of failed admin login attempt
			$vtransgressions = forcefield_blocklist_record_ip('admin_fail');
			$vblocked = forcefield_blocklist_check_transgressions('admin_fail', $vtransgressions);
			if ($vblocked) {
				do_action('forcefield_login_admin_toomany');
				$vstatus = 429; // HTTP 429: Too Many Requests
				return forcefield_filtered_error('login_admin_toomany', $verrormessage, $vstatus);
			}
		}
	}

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
		do_action('forcefield_login_noreferer');
		do_action('forcefield_no_referer');

		// 0.9.1: separate general no referer recording
		$vnorefban = forcefield_get_setting('blocklist_norefban');
		if ($vnorefban == 'yes') {
			$vtransgressions = forcefield_blocklist_record_ip('no_referer');
			$vblocked = forcefield_blocklist_check_transgressions('no_referer', $vtransgressions);
			if ($vblocked) {$vblock = true;}
		}
		$vnorefblock = forcefield_get_setting('login_norefblock');
		if ($vnorefblock == 'yes') {$vblock = true;}

		if (isset($vblock) && $vblock) {
			do_action('forcefield_login_failed');
			$vstatus = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('login_no_referer', $verrormessage, $vstatus);
		}
	}

	// login form field to check token
	if ( (isset($POST['log'])) && (isset($_POST['pwd'])) ) {

		$vtokenize = forcefield_get_setting('login_token');
		if ($vtokenize != 'yes') {return $user;}

		// maybe record the IP if missing the token form field
		if (!isset($_POST['auth_token'])) {
			// 0.9.1: separate instaban and no token recording
			$vrecordnotoken = forcefield_get_setting('blocklist_notoken');
			if ($vrecordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}
			$vinstaban = forcefield_get_setting('login_notokenban');
			if ($vinstaban == 'yes') {forcefield_blocklist_record_ip('no_login_token');}
			do_action('forcefield_login_notoken');
			do_action('forcefield_login_failed');
			$vstatus = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('login_token_missing', $verrormessage, $vstatus);
		} else {
			// 0.9.1: maybe clear no token records
			forcefield_delete_record(false, 'no_token');
			forcefield_delete_record(false, 'no_login_token');
		}

		$vauthtoken = $_POST['auth_token'];
		$vchecktoken = forcefield_check_token('login');

		if (!$vchecktoken) {
			// probably the token transient has expired
			do_action('forcefield_login_oldtoken');
			do_action('forcefield_login_failed');
			$vstatus = 403; // HTTP 403: Forbidden
			return forcefield_filtered_error('login_token_expired', $verrormessage, $vstatus);
		} elseif ($vauthtoken != $vchecktoken) {
			// fail, token is a mismatch
			// 0.9.1: record general token usage failure
			$vrecordbadtokens = forcefield_get_setting('blocklist_badtoken');
			if ($vrecordbadtokens == 'yes') {forcefield_record_ip('bad_token');}
			do_action('forcefield_login_mismatch');
			do_action('forcefield_login_failed');
			$vstatus = 401; // HTTP 401: Unauthorized
			return forcefield_filtered_error('login_token_mismatch', $verrormessage, $vstatus);
		} else {
			// success, allow user to login
			// 0.9.1: clear any bad token records
			forcefield_blocklist_delete_record(false, 'bad_token');
			// remove used login token
			forcefield_delete_token('login');
			do_action('forcefield_login_success');
		}
	}

	// 0.9.1: clear possible admin_fail records on a successful login
	if (!is_wp_error($user)) {
		if (in_array('administrator', (array)$user->roles)) {
			forcefield_blocklist_delete_record(false, 'admin_fail');
		}
	}

	return $user;
}

// Registration Token Authentication
// ---------------------------------
add_filter('register_post', 'forcefield_registration_authenticate', 9, 3);
function forcefield_registration_authenticate($errors, $sanitized_user_login, $user_email) {

	// filtered general error message
	$verrormessage = forcefield_get_setting('error_message');
	$verrormessage = apply_filters('forcefield_error_message_register', $verrormessage);

	// check IP whitelist
	if (forcefield_whitelist_check('actions')) {return $errors;}
	// 0.9.2: check IP blacklist
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

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
		do_action('forcefield_register_noreferer');
		do_action('forcefield_no_referer');

		// 0.9.1: separate general no referer recording
		$vnorefban = forcefield_get_setting('blocklist_norefban');
		if ($vnorefban == 'yes') {
			$vtransgressions = forcefield_blocklist_record_ip('no_referer');
			$vblocked = forcefield_blocklist_check_transgressions('no_referer', $vtransgressions);
			if ($vblocked) {$vblock = true;}
		}
		$vnorefblock = forcefield_get_setting('register_norefblock');
		if ($vnorefblock == 'yes') {$vblock = true;}

		if (isset($vblock) && $vblock) {
			do_action('forcefield_register_failed');
			$vstatus = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error($verror, $verrormessage, $vstatus);
		}
	}

	// check tokenizer setting
    $vtokenize = forcefield_get_setting('register_token');
	if ($vtokenize != 'yes') {return $errors;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		// 0.9.1: separate token and register token recording
		$vrecordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($vrecordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}
		$vinstaban = forcefield_get_setting('register_notokenban');
		if ($vinstaban == 'yes') {forcefield_blocklist_record_ip('no_register_token');}
		do_action('forcefield_register_notoken');
		do_action('forcefield_register_failed');
		$vstatus = 400; // HTTP 400: Bad Request
		return forcefield_filtered_error($verror, $verrormessage, $vstatus, $errors);
	} else {
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_register_token');
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('register');

	if (!$vchecktoken) {
		// probably the register token transient has expired
		do_action('forcefield_register_oldtoken');
		do_action('forcefield_register_failed');
		$vstatus = 403; // HTTP 403: Forbidden
		return forcefield_filtered_error('register_token_expired', $verrormessage, $vstatus, $errors);
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($vrecordbadtokens == 'yes') {forcefield_record_ip('bad_token');}
		do_action('forcefield_register_mismatch');
		do_action('forcefield_register_failed');
		$vstatus = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('register_token_mismatch', $verrormessage, $vstatus, $errors);
	} else {
		// 0.9.1: clear any bad token records
		forcefield_blocklist_delete_record(false, 'bad_token');
		// remove used register token
		forcefield_delete_token('register');
		do_action('forcefield_register_success');
	}

    return $errors;
}

// Blog Signup Authenticate
// ------------------------
add_filter('wpmu_validate_user_signup', 'forcefield_signup_authenticate');
function forcefield_signup_authenticate($results) {

	// set any existing errors
	$errors = $results['errors'];

	// filtered general error message
	$verrormessage = forcefield_get_setting('error_message');
	$verrormessage = apply_filters('forcefield_error_message_signup', $verrormessage);

	// check IP whitelist
	if (forcefield_whitelist_check('actions')) {return $results;}
	// 0.9.2: check IP blacklist
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// ? maybe allow signup for already logged in users ?
	// if ( is_user_logged_in() && is_admin() && !defined('DOING_AJAX') ) {return $results;}

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
		do_action('forcefield_signup_noreferer');
		do_action('forcefield_no_referer');

		// 0.9.1: separate general no referer recording
		$vnorefban = forcefield_get_setting('blocklist_norefban');
		if ($vnorefban == 'yes') {
			$vtransgressions = forcefield_blocklist_record_ip('no_referer');
			$vblocked = forcefield_blocklist_check_transgressions('no_referer', $vtransgressions);
			if ($vblocked) {$vblock = true;}
		}
		$vnorefblock = forcefield_get_setting('signup_norefblock');
		if ($vnorefblock == 'yes') {$vblock = true;}

		if (isset($vblock) && $vblock) {
			do_action('forcefield_signup_failed');
			$vstatus = 400; // HTTP 400: Bad Request
			$results['errors'] = forcefield_filtered_error('signup_no_referer', $verrormessage, $vstatus, $errors);
			return $results;
		}
	}

	// check tokenizer setting
	$vtokenize = forcefield_get_setting('signup_token');
	if ($vtokenize != 'yes') {return $results;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vrecordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($vrecordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}
		$vinstaban = forcefield_get_setting('signup_notokenban');
		if ($vinstaban == 'yes') {forcefield_blocklist_record_ip('no_signup_token');}
		do_action('forcefield_signup_notoken');
		do_action('forcefield_signup_failed');
		$vstatus = 400; // HTTP 400: Bad Request
		$results['errors'] = forcefield_filtered_error('signup_token_missing', $verrormessage, $vstatus, $errors);
		return $results;
	} else {
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_signup_token');
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('signup');

	if (!$vchecktoken) {
		// probably the register token transient has expired
		do_action('forcefield_signup_oldtoken');
		do_action('forcefield_signup_failed');
		$results['errors'] = forcefield_filtered_error('signup_token_expired', $verrormessage, false, $errors);
		return $results;
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, register token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtoken');
		if ($vrecordbadtokens == 'yes') {forcefield_record_ip('bad_token');}
		do_action('forcefield_signup_mismatch');
		do_action('forcefield_signup_failed');
		$vstatus = 401; // HTTP 401: Unauthorized
		$results['errors'] = forcefield_filtered_error('signup_token_mismatch', $verrormessage, $vstatus, $errors);
		return $results;
	} else {
		// success, allow the user to signup
		// 0.9.1: clear any bad token records
		forcefield_blocklist_delete_record(false, 'bad_token');
		// remove used signup token
		forcefield_delete_token('signup');
		do_action('forcefield_signup_success');
	}

	return $results;
}


// Lost Password Token Authentication
// ----------------------------------
add_action('allow_password_reset', 'forcefield_lost_password_authenticate', 21, 1);
function forcefield_lost_password_authenticate($allow) {

	// filter general error message
	$verrormessage = forcefield_get_setting('error_message');
	$verrormessage = apply_filters('forcefield_error_message_lostpassword', $verrormessage);

	// 0.9.1: check IP whitelist
	if (forcefield_whitelist_check('actions')) {return $allow;}
	// 0.9.2: check IP blacklist
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

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
		do_action('forcefield_lostpass_noreferer');
		do_action('forcefield_no_referer');

		// 0.9.1: separate general no referer recording
		$vnorefban = forcefield_get_setting('blocklist_norefban');
		if ($vnorefban == 'yes') {
			$vtransgressions = forcefield_blocklist_record_ip('no_referer');
			$vblocked = forcefield_blocklist_check_transgressions('no_referer', $vtransgressions);
			if ($vblocked) {$vblock = true;}
		}
		$vnorefblock = forcefield_get_setting('lostpass_norefblock');
		if ($vnorefblock == 'yes') {$vblock = true;}

		if (isset($vblock) && $vblock) {
			do_action('forcefield_lostpass_failed');
			$vstatus = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('lostpass_no_referer', $verrormessage, $vstatus);
		}
	}

	// check tokenizer setting
	$vtokenize = forcefield_get_setting('lostpass_token');
	if ($vtokenize != 'yes') {return $allow;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vrecordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($vrecordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}
		$vinstaban = forcefield_get_setting('lostpass_notokenban');
		if ($vinstaban == 'yes') {forcefield_blocklist_record_ip('no_lostpass_token');}
		do_action('forcefield_lostpass_notoken');
		do_action('forcefield_lostpass_failed');
		$vstatus = 400; // HTTP 400: Bad Request
		return forcefield_filtered_error('lostpass_token_missing', $verrormessage, $vstatus);
	} else {
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_lostpass_token');
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('lostpass');

	if (!$vchecktoken) {
		// probably the lost password token transient has expired
		do_action('forcefield_lostpass_oldtoken');
		do_action('forcefield_lostpass_failed');
		return forcefield_filtered_error('lostpass_token_expired', $verrormessage);
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, lost password token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($vrecordbadtokens == 'yes') {forcefield_record_ip('bad_token');}
		do_action('forcefield_lostpass_mismatch');
		do_action('forcefield_lostpass_failed');
		$vstatus = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('lostpass_token_mismatch', $verrormessage, $vstatus);
	} else {
		// success, allow the user to send reset email
		// 0.9.1: clear any bad token records
		blocklist_delete_record(false, 'bad_token');
		// remove used lost password token
		forcefield_delete_token('lostpass');
		do_action('forcefield_lostpass_success');
	}

	return $allow;
}

// Commenting Authenticate
// -----------------------
add_filter('preprocess_comment', 'forcefield_preprocess_comment');
function forcefield_preprocess_comment($comment) {

	// filter general error message
	$verrormessage = forcefield_get_setting('error_message');
	$verrormessage = apply_filters('forcefield_error_message_comment', $verrormessage);

	// skip checks for those with comment moderation permission
	if (current_user_can('moderate_comments')) {return $comment;}

	// 0.9.1: checks IP whitelist
	if (forcefield_whitelist_check('actions')) {return $comment;}
	// 0.9.2: check IP blacklist
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

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
		do_action('forcefield_comment_noreferer');
		do_action('forcefield_no_referer');

		// 0.9.1: separate general no referer recording
		$vnorefban = forcefield_get_setting('blocklist_norefban');
		if ($vnorefban == 'yes') {
			$vtransgressions = forcefield_blocklist_record_ip('no_referer');
			$vblocked = forcefield_blocklist_check_transgressions('no_referer', $vtransgressions);
			if ($vblocked) {$vblock = true;}
		}
		$vnorefblock = forcefield_get_setting('comment_norefblock');
		if ($vnorefblock == 'yes') {$vblock = true;}

		if (isset($vblock) && $vblock) {
			do_action('forcefield_comment_failed');
			$vstatus = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('comment_no_referer', $verrormessage, $vstatus);
		}
	}

	// check tokenizer setting
	$vtokenize = forcefield_get_setting('comment_token');
	if ($vtokenize != 'yes') {return $comment;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vrecordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($vrecordnotoken == 'yes') {forcefield_record_ip('no_token');}
		$vinstaban = forcefield_get_setting('comment_notokenban');
		if ($vinstaban == 'yes') {forcefield_blocklist_record_ip('no_comment_token');}
		do_action('forcefield_comment_notoken');
		do_action('forcefield_comment_failed');
		$vstatus = 400; // HTTP 400: Bad Request
		return forcefield_filtered_error('comment_token_missing', $verrormessage, $vstatus);
	} else {
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_comment_token');
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('lostpass');

	if (!$vchecktoken) {
		// probably the comment token transient has expired
		do_action('forcefield_comment_oldtoken');
		do_action('forcefield_comment_failed');
		return forcefield_filtered_error('comment_token_expired', $verrormessage);
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, comment token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($vrecordbadtokens == 'yes') {forcefield_record_ip('bad_token');}
		do_action('forcefield_comment_mismatch');
		do_action('forcefield_comment_failed');
		$vstatus = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('comment_token_mismatch', $verrormessage, $vstatus);
	} else {
		// success, allow the user to comment
		// 0.9.1: clear any bad token records
		blocklist_delete_record(false, 'bad_token');
		// remove used comment token
		forcefield_delete_token('comment');
		do_action('forcefield_comment_success');
	}

	return $comment;
}


// ============
// IP BLOCKLIST
// ============

// Blocklist Contexts
// ------------------
function forcefield_blocklist_get_contexts() {
	$vcontexts = array(
		'site' 		=> __('Entire Site','forcefield'),
		'actions' 	=> __('User Actions','forcefield'),
		'apis' 		=> __('API Access','forcefield'),
		'both' 		=> __('Actions and APIs','forcefield'),
		'custom' 	=> __('Custom','forcefield')
	);
	return $vcontexts;
}

// IP Whitelist Check
// ------------------
function forcefield_whitelist_check($vcontext, $vip=false) {
	global $wpdb, $vforcefield;
	if (!$vip) {$vip = $vforcefield['ip'];}

	// check permanent whitelist (no context)
	$vwhitelist = forcefield_get_setting('blocklist_whitelist');
	if (is_array($vwhitelist)) {
		if (in_array($vip, $vwhitelist)) {return true;}
		if (count($vwhitelist) > 0) {
			foreach ($vwhitelist as $vipaddress) {
				if ( (strstr($vipaddress, '*')) || (strstr($vipaddress, '-')) ) {
					if (forcefield_is_ip_in_range($vip, $vipaddress)) {return true;}
				}
			}
		}
	}

	// 0.9.2: [PRO] maybe check for manual whitelist table records
	if (function_exists('forcefield_pro_whitelist_check')) {
		if (forcefield_pro_whitelist_check($vcontext, $vip)) {return true;}
	}
	if (function_exists('forcefield_pro_whitelist_check_range')) {
		if (forcefield_pro_whitelist_check_range($vcontext, $vip)) {return true;}
	}

	return false;
}

// IP Blacklist Check
// ------------------
function forcefield_blacklist_check($vcontext, $vip=false) {
	global $wpdb, $vforcefield;
	if (!$vip) {$vip = $vforcefield['ip'];}

	// permanent blacklist check (no context)
	if (!isset($vforcefield['blacklistchecked'])) {
		$vblacklist = forcefield_get_setting('blocklist_blacklist');
		if (is_array($vblacklist)) {
			if (in_array($vip, $vblacklist)) {return true;}
			if (count($vblacklist) > 0) {
				foreach ($vblacklist as $vipaddress) {
					if ( (strstr($vipaddress, '*')) || (strstr($vipaddress, '-')) ) {
						if (forcefield_is_ip_in_range($vip, $vipaddress)) {return true;}
					}
				}
			}
		}
		$vforcefield['blacklistchecked'] = true;
	}

	// 0.9.2: [PRO] maybe check for manual whitelist records
	if (function_exists('forcefield_pro_blacklist_check')) {
		if (forcefield_pro_blacklist_check($vcontext, $vip)) {return true;}
	}
	if (function_exists('forcefield_pro_blacklist_check_range')) {
		if (forcefield_pro_blacklist_check_range($vcontext, $vip)) {return true;}
	}
	return false;
}

// Check IP Blocklist
// ------------------
add_action('plugins_loaded', 'forcefield_blocklist_check', 0);
function forcefield_blocklist_check() {
	global $vforcefield;

	// check if IP is in manual whitelist (exact or range)
	if (forcefield_whitelist_check('site', $vforcefield['ip'])) {return;}

	// check if IP is in manual blacklist (exact or range)
	if (forcefield_blacklist_check('site', $vforcefield['ip'])) {forcefield_forbidden_exit();}

	// maybe auto delete old blocklist records for this IP
	forcefield_blocklist_table_cleanup(false, $vforcefield['ip']);

	// check for remaining blocklist records for this IP
	$vrecords = forcefield_blocklist_check_ip($vforcefield['ip']);
	if ($vrecords && (is_array($vrecords)) && (count($vrecords) > 0) ) {
		foreach ($vrecords as $vrecord) {

			// 0.9.1: check the cooldown period for this block
			if (forcefield_get_setting('blocklist_cooldown') == 'yes') {
				$vrecord = forcefield_blocklist_cooldown($vrecord);
			}

			// check the block reason is still enforced
			$vreason = $vrecord['label']; $venforced = true;
			if ( ( ($vreason == 'admin_bad') && (forcefield_get_setting('admin_block') != 'yes') )
			  || ( ($vreason == 'xmlrpc_login') && (forcefield_get_setting('xmlrpc_authban') != 'yes') )
			  || ( ($vreason == 'no_login_token') && (forcefield_get_setting('login_token') != 'yes') )
			  || ( ($vreason == 'no_register_token') && (forcefield_get_setting('register_token') != 'yes') )
			  || ( ($vreason == 'no_signup_token') && (forcefield_get_setting('signup_token') != 'yes') )
			  || ( ($vreason == 'no_lostpass_token') && (forcefield_get_setting('lostpass_token') != 'yes') )
			  || ( ($vreason == 'no_comment_token') && (forcefield_get_setting('comment_token') != 'yes') ) ) {
				$venforced = false;
			}
			// note exception: for "no_referer" just check transgression limit

			if ($venforced) {
				$vblocked = forcefield_blocklist_check_transgressions($vreason, $vtransgressions);
				if ($vblocked) {
					// transgressions are over limit
					// status_header('403', 'HTTP/1.1 403 Forbidden');
					header('HTTP/1.1 403 Forbidden');
					header('Status: 403 Forbidden');

					if (forcefield_get_setting('blocklist_unblocking') == 'yes') {
						// 0.9.1: output manual unblocking request form
						forcefield_blocklist_unblock_form_output();
					} else {header('Connection: Close');}
					exit;
				}
			}
		}
	}
	return;

	// 0.9.1: [DEPRECATED] single array IP blocklist check
	global $vffblocklist;
	$vffblocklist = get_option('forcefield_blocklist');
	if (is_array($vffblocklist)) {

		// note: defaults to not expire any IP blocks at all
		// can be filtered to an expiry time difference in seconds (eg. 1 day = 24*60*60)
		$vexpireblock = absint(forcefield_get_setting('blocklist_expiry'));
		$vexpireblock = 120; // TEMP

		if ($vexpireblock && ($vexpireblock > 0) ) {
			if (count($vffblocklist) > 0) {
				$vfoundexpired = true;
				foreach ($vffblocklist as $vblockedip) {
					$vinfo = $vffblocklist[$vforcefieldip];
					$vdata = explode(':', $vinfo);
					$vdiff = time() - $vdata[1];
					if ($vdiff > $vexpireblock) {
						unset($vffblocklist[$vforcefieldip]);
						$vfoundexpired = true;
					}
				}
				if ($vfoundexpired) {update_option('forcefield_blocklist', $vffblocklist);}
			}
		}

		if (array_key_exists($vforcefieldip, $vffblocklist)) {
			$vblockrequest = true;
			if ($vblockrequest) {
				header("HTTP/1.1 403 Forbidden");
				header('Status: 403 Forbidden');
				header('Connection: Close'); exit;
			}
		}
	}
}

// Create IP Blocklist Table on Activation
// ---------------------------------------
// 0.9.1: create an IP blocklist table
register_activation_hook(__FILE__, 'forcefield_blocklist_table_create');
function forcefield_blocklist_table_create() {

	global $wpdb, $vforcefield;
	$vtablename = $vforcefield['table']; $vcharset = $vforcefield['charset'];

	// Note: IP Transgression Table Structure based on Shield Plugin (wp-simple-firewall)
	// Ref: https://www.icontrolwp.com/blog/wordpress-security-plugin-update-automatically-block-malicious-visitors/
	if ($wpdb->get_var("SHOW TABLES LIKE '".$vtablename."'") != $vtablename) {
		$vquery = "CREATE TABLE %s (
				id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				ip varchar(40) NOT NULL DEFAULT '',
				label varchar(255) NOT NULL DEFAULT '',
				list varchar(4) NOT NULL DEFAULT '',
				ip6 tinyint(1) NOT NULL DEFAULT 0,
				is_range tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
				transgressions tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
				last_access_at int(15) UNSIGNED NOT NULL DEFAULT 0,
				created_at int(15) UNSIGNED NOT NULL DEFAULT 0,
				deleted_at int(15) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
			) %s;";
		$vquery = sprintf($vquery, $vtablename, $vcharset);

		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($vquery);
	}
}

// set Blocklist Table Variables
// -----------------------------
// 0.9.1: set blocklist table variables
function forcefield_blocklist_table_init() {
	global $wpdb, $vforcefield;
	$vforcefield['table'] = $wpdb->prefix.'forcefield_ips';
	$vforcefield['charset'] = $wpdb->get_charset_collate();
	$vcheck = forcefield_blocklist_check_table();
	if (!$vcheck) {forcefield_blocklist_table_create();}
	return $vforcefield;
}

// Check Blocklist Table Exists
// ----------------------------
// 0.9.1: check blocklist table exists
function forcefield_blocklist_check_table() {
	global $wpdb, $vforcefield;
	$vtablequery = "SHOW TABLES LIKE '".$vforcefield['table']."'";
	$vchecktable = $wpdb->get_var($vtablequery);
	if ($vchecktable != $vforcefield['table']) {return false;}
	return true;
}

// Clear IP Blocklist Table
// ------------------------
// 0.9.1: to clear entire blocklist table
function forcefield_blocklist_clear_table() {
	global $wpdb, $vforcefield;
	$vquery = "DELETE FROM ".$vforcefield['table']." WHERE `list` = 'AB'";
	$vdelete = $wpdb->query($vquery);
	return $vdelete;
}

// check IP in Blocklist
// ---------------------
// 0.9.1: check if IP is in blocklist table
function forcefield_blocklist_check_ip($vip) {
	$vcolumns = array('label', 'transgressions', 'last_access_at');
	return forcefield_blocklist_get_records($vcolumns, $vip);
}

// get IP Blocklist Records
// ------------------------
// 0.9.1: use blocklist table
function forcefield_blocklist_get_records($vcolumns=array(), $vip=false, $vreason=false, $vnoexpired=true) {
	global $wpdb, $vforcefield;

	$vwhere = '';
	if (empty($vcolumns)) {$vcolumnquery = '*';} else {$vcolumnquery = implode(',', $vcolumns);}
	if ($vnoexpired) {$vwhere = "WHERE `deleted_at` = 0";}
	if ($vip) {
		if ($vwhere == '') {$vwhere = $wpdb->prepare("WHERE `ip` = %s", $vip);}
		else {$vwhere .= $wpdb->prepare(" AND `ip` = %s", $vip);}
	}
	if ($vreason) {$vwhere .= $wpdb->prepare(" AND `label` = %s", $vreason);}

	$vquery = "SELECT $vcolumnquery FROM ".$vforcefield['table']." ".$vwhere;
	$vresult = $wpdb->get_results($vquery, ARRAY_A);
	if ( (is_array($vresult)) && (isset($vresult[0])) ) {return $vresult;}
	return array();
}

// Add/Update an IP Address Record
// -------------------------------
// 0.9.1: use blocklist table
function forcefield_blocklist_record_ip($vreason, $vip=false) {
	if (!isset($vip)) {global $vforcefield; $vip = $vforcefield['ip'];}
	global $wpdb; $vtime = time();

	// check for existing trangression of this type
	$vcolumns = array('id', 'label', 'transgressions', 'deleted_at');
	$vrecords = forcefield_blocklist_get_records($vcolumns, $vip, $vreason, false);
	if ($vrecords) {
		foreach ($vrecords as $vrecord) {
			if ($vrecord['label'] == $vreason) {
				// increment transgression count
				$vtransgressions = $vrecord['transgressions'];
				$vrecords['transgressions'] = $vtransgressions++;
				$vrecords['last_access_at'] = $vtime;
				$vrecords['deleted_at'] = 0;
				$vwhere = array('id' => $vrecord['id']);
				$vupdate = $wpdb->update($vforcefield['table'], $vdata, $vwhere);
				return $vdata;
			}
		}
	}

	// add new IP address record to Blocklist Table
	if (forcefield_get_ip_type($vip) == 'ip6') {$vip6 = 1;} else {$vip6 = 0;}
	$vrecord = array(
		'ip' 				=> $vip,
		'label'				=> $vreason,
		'list' 				=> 'AB',
		'ip6' 				=> $vip6,
		'transgressions' 	=> 1,
		'is_range' 			=> 0,
		'last_access_at' 	=> $vtime,
		'created_at' 		=> $vtime,
		'deleted_at' 		=> 0
	);
	$vinsert = $wpdb->insert($vforcefield['table'], $vrecord);
	return $vrecord;
}

// 0.9.1: [DEPRECATED] Ban IP Address (old single option array)
function forcefield_ban_ip_address($vcontext) {
	global $vffblocklist, $vforcefieldip;
	if ( (!is_array($vffblocklist)) || (!array_key_exists($vforcefieldip, $vffblocklist)) ) {
		$vffbannedips[$vip] = $vcontext.':'.time();
	}
}

// Check Transgressions against Limit
// ----------------------------------
// 0.9.1: check transgression limit for different block reasons
function forcefield_blocklist_check_transgressions($vreason, $vtransgressions) {
	// return true; // TEST TEMP: 1 attempt = auto block

	// get limit for this block reason
	$vlimit = forcefield_get_setting('limit_'.$vreason);

	// for instant ban set limit to 1
	if ( ( ($vreason == 'no_login_token') && (forcefield_get_setting('login_notokenban') == 'yes') )
	  || ( ($vreason == 'no_register_token') && (forcefield_get_setting('register_notokenban') == 'yes') )
	  || ( ($vreason == 'no_signup_token') && (forcefield_get_setting('signup_notokenban') == 'yes') )
	  || ( ($vreason == 'no_lostpass_token') && (forcefield_get_setting('lostpass_notokenban') == 'yes') )
	  || ( ($vreason == 'no_comment_token') && (forcefield_get_setting('comment_notokenban') == 'yes') ) ) {
	    $vlimit = 1;
	}

	// filter and return result
	$vlimit = absint(apply_filters('forcefield_limit_'.$vreason, $vlimit));
	if ($vtransgressions > ($vlimit - 1)) {return true;}
	return false;
}

// Get Default Transgression Limits
// --------------------------------
function forcefield_blocklist_get_default_limits() {
	$vlimits = array(
		'admin_bad' 		=> 1, 	// really really bad
		'admin_fail' 		=> 10,	// likely brute force attempts
		'xmlrpc_login' 		=> 2,	// blocks only when disallowed
		'xmlrpc_authfail' 	=> 10,	// likely brute force attempts
		'no_referer' 		=> 10,	// probably a silly bot
		'no_token'			=> 10,	// probably a bot
		'bad_token'			=> 5,	// probably a bot
		// initial benefit of the doubt leeway for tokens
		'no_login_token' 	=> 3,
		'no_register_token' => 3,
		'no_signup_token' 	=> 3,
		'no_lostpass_token' => 3,
		'no_comment_token' 	=> 3,
	);
	return $vlimits;
}

// Get Translated Block Reasons
// ----------------------------
// 0.9.1: translated block reasons
function forcefield_blocklist_get_reasons() {
	// IP Block Reasons
	$vreasons = array(
		'admin_bad' 		=> __('Unwhitelisted Admin Login','forcefield'),
		'admin_fail' 		=> __('Administrator Login Fail','forcefield'),
		'xmlrpc_login' 		=> __('Disallowed XML RPC Login Attempt','forcefield'),
		'xmlrpc_authfail' 	=> __('Failed XML RPC Login Attempt','forcefield'),
		'no_referer' 		=> __('Missing Referrer Header','forcefield'),
		'no_token'			=> __('Missing Action Token','forcefield'),
		'bad_token'			=> __('Action Token Mismatch','forcefield'),
		'no_login_token' 	=> __('Missing Login Token','forcefield'),
		'no_register_token' => __('Missing Registration Token','forcefield'),
		'no_signup_token' 	=> __('Missing Blog Signup Token','forcefield'),
		'no_lostpass_token' => __('Missing Lost Password Token','forcefield'),
		'no_comment_token' 	=> __('Missing Comment Token','forcefield'),
	);
	return $vreasons;
}

// Blocklist Transgression Cooldown
// --------------------------------
// 0.9.1: check cooldown period
function forcefield_blocklist_cooldown($vrecord) {
	global $wpdb, $vforcefield;

	$vcooldown = forcefield_get_setting('blocklist_cooldown');
	$vdiff = time() - $vrecord['last_access_at'];
	if ($vdiff > $vcooldown) {
		if ($vrecord['transgressions'] > 0) {
			$vreduce = floor($vdiff / $vcooldown); // ** check floor/ceil usage? **
			$vrecord['trangressions'] = $vrecord['transgressions'] - $vreduce;
			if ($vrecord['transgressions'] < 1) {
				$vrecord['transgressions'] = 0;
				$vrecord['deleted_at'] = time();
			}
			$vrecord['last_access_at'] = time(); // ** note: not strictly accurate **
			$vwhere = array('id' => $vrecord['id']);
			$wpdb->update($vforcefield['table'], $vrecord, $vwhere);
		}
	}
	return $vrecord;
}

// Blocklist Expire Old Rows
// -------------------------
function forcefield_blocklist_expire_old_rows($vtimestamp, $vreason=false, $vip=false) {
	global $wpdb, $vforcefield;
	$vquery = "UPDATE '".$vforcefield['table']."' SET `deleted_at` = %s WHERE `last_access_at` < %s"; // "
	$vquery = $wpdb->prepare($vquery, array(time(), $vtimestamp));
	if ($vreason) {
		if ($vreason == 'admin_bad') {return false;} // never auto-expire
		$vquery .= $wpdb->prepare(" AND `label` = %s", $vreason);
	}
	if ($vip) {$vquery .= $wpdb->prepare(" AND `ip` = %s", $vip);}
	return $wpdb->query($vquery);
}

// Blocklist Delete Old Rows
// -------------------------
function forcefield_blocklist_delete_old_rows($vtimestamp, $vreason=false, $vip=false) {
	global $wpdb, $vforcefield;
	$vquery = "DELETE FROM ".$vforcefield['table']." WHERE `last_access_at` < %s"; // "
	$vquery = $wpdb->prepare($vquery, $vtimestamp);
	if ($vreason) {
		if ($vreason == 'admin_bad') {return false;} // never auto-delete
		$vquery .= $wpdb->prepare(" AND `label` = %s", $vreason);
	}
	if ($vip) {$vquery .= $wpdb->prepare(" AND `ip` = %s", $vip);}
	return $wpdb->query($vquery);
}

// Blocklist Delete Record
// -----------------------
function forcefield_blocklist_delete_record($vip=false, $vreason=false) {
	global $wpdb, $vforcefield;
	if (!$vip) {$vip = $vforcefield['ip'];}
	$vquery = "DELETE FROM ".$vforcefield['table']." WHERE `ip` = %s"; // "
	$vquery = $wpdb->prepare($vquery, $vip);
	if ($vreason) {$vquery .= $wpdb->prepare(" AND `label` = %s", $vreason);}
	return $wpdb->query($vquery);
}

// AJAX Blocklist Delete Record
// ----------------------------
add_action('wp_ajax_forcefield_unblock_ip', 'forcefield_blocklist_remove_record');
function forcefield_blocklist_remove_record() {
	if (!current_user_can('manage_options')) {exit;}
	check_admin_referrer('forcefield_unblock');

	$vip = $_REQUEST['ip'];
	$viptype = forcefield_get_ip_type($vip);
	if (!$viptype) {return false;}
	$vmessage = __('IP Address Block Removed.','forcefield');

	if (isset($_REQUEST['label'])) {
		$vreason = $_REQUEST['label'];
		$vreasons = forcefield_blocklist_get_reasons();
		if (!array_key_exists($vreason, $vreasons)) {return false;}
		$vmessage = __('Transgression Record Removed.','forcefield');
	} else {$vreason = false;}

	$vresult = forcefield_blocklist_delete_record($vip, $vreason);
	forcefield_alert_message($vmessage); exit;
}

// AJAX Blocklist Clear Table
// --------------------------
add_action('wp_ajax_forcefield_blocklist_clear', 'forcefield_blocklist_clear');
function forcefield_blocklist_clear() {
	if (!current_user_can('manage_options')) {exit;}
	check_admin_referrer('forcefield_clear');

	$vcheck = forcefield_blocklist_check_table();
	if ($vcheck) {
		$vclear = forcefield_blocklist_clear_table();
		$vmessage = __('IP Blocklist has been cleared.','forcefield');
	} else {$vmessage = __('Error. Blocklist table does not exist.','forcefield');}

	echo "<script>alert('".$vmessage."');
	parent.document.getElementById('blocklist-table').innerHTML = '';
	</script>"; exit;

}

// Manual Unblock Form Output
// --------------------------
// 0.9.1: manual unblock form output
function forcefield_blocklist_unblock_form_output() {
	// form title
	echo "<br><table><tr><td align='center'><h3>".__('403 Forbidden','forcefield')."</h3></td></tr>".PHP_EOL;
	echo "<tr height='20'><td> </td></tr>";
	// user message
	echo "<tr><td>";
	echo __('Access denied. Your IP Address has been blocked!','forcefield')."<br>".PHP_EOL;
	echo __('If you are a real person click the button below.','forcefield').PHP_EOL;
	echo "</td></tr>";
	echo "<tr height='20'><td> </td></tr>";
	echo "<tr><td align='center'>";
		// unblock form
		$vadminajax = admin_url('admin-ajax.php');
		echo "<form action='".$vadminajax."' target='unblock-frame'>";
		echo "<input type='hidden' name='action' value='forcefield_unblock'>";
		// add an unblock token field
		forcefield_add_field('unblock');
		echo "<input type='submit' value='".__('Unblock My IP','forcefield')."'>";
		echo "</form>";
	echo "</td></tr></table>";
}

// AJAX Unblock Action
// -------------------
// 0.9.1: check for manual unblock request
add_action('wp_ajax_forcefield_unblock', 'forcefield_blocklist_unblock_check');
add_action('wp_ajax_nopriv_forcefield_unblock', 'forcefield_blocklist_unblock_check');
function forcefield_blocklist_unblock_check() {

	// fail on empty referer field
	if ($_SERVER['HTTP_REFERER'] == '') {exit;}

	// check token field
	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('unblock');

	if (!$vchecktoken) {
		$vmessage = __('Time Limit Expired. Refresh the Page and Try Again.','forcefield');
	} elseif ($vauthtoken != $vchecktoken) {
		// fail, token is a mismatch
		$vmessage = __('Invalid Request. Unblock Failed.','forcefield');
	} else {
		forcefield_blocklist_delete_record();
		forcefield_delete_token('unblock');
	}
	forcefield_alert_message($vmessage); exit;
}

// Blocklist Table Cleanup
// -----------------------
function forcefield_blocklist_table_cleanup($vreason=false, $vip=false) {

	if (!forcefield_blocklist_check_table()) {return false;}

	// expire old IP blocks
	$vexpireperiod = forcefield_get_setting('blocklist_expiry');
	if ($vreason) {$vexpireperiod = apply_filters('blocklist_expiry_'.$vreason, $vexpireperiod);}
	$vexpireperiod = absint($vexpireperiod);
	if ($vexpireperiod > 0) {
		$vtimestamp = time() - $vexpireperiod;
		forcefield_blocklist_expire_old_rows($vtimestamp, $vreason, $vip);
	}

	// delete old IP block records
	$vexpireperiod = forcefield_get_setting('blocklist_delete');
	if ($vreason) {$vexpireperiod = apply_filters('blocklist_delete_'.$vreason, $vexpireperiod);}
	$vexpireperiod = absint($vexpireperiod);
	if ($vexpireperiod > 0) {
		$vtimestamp = time() - $vexpireperiod;
		forcefield_blocklist_delete_old_rows($vtimestamp, $vreason, $vip);
	}

	// [PRO] cleanup any Pro records
	if (function_exists('forcefield_pro_cleanup_records')) {forcefield_pro_cleanup_records();}
}

// WP CRON Schedule Table Cleanup
// ------------------------------
add_action('init', 'forcefield_blocklist_schedule_cleanup');
function forcefield_blocklist_schedule_cleanup() {
	if (!wp_next_scheduled('forcefield_blocklist_table_cleanup')) {
		$vfrequency = forcefield_get_setting('blocklist_cleanups');
		wp_schedule_event(time(), $vfrequency, 'forcefield_blocklist_table_cleanup');
	}
}


// ==========
// DATA LISTS
// ==========

// Get CRON Intervals
// ------------------
// 0.9.1: get cron intervals, doubles as cron schedule filter
add_filter('cron_schedules', 'forcefield_get_intervals');
function forcefield_get_intervals($schedule=array()) {
	$vintervals['5minutes'] = array('interval' => 300, 'display' => __('Every 5 Minutes','forcefield'));
	$vintervals['10minutes'] = array('interval' => 600, 'display' => __('Every 10 Minutes','forcefield'));
	$vintervals['15minutes'] = array('interval' => 900, 'display' => __('Every 15 Minutes','forcefield'));
	$vintervals['20minutes'] = array('interval' => 1200, 'display' => __('Every 20 Minutes','forcefield'));
	$vintervals['30minutes'] = array('interval' => 1800, 'display' => __('Every 30 Minutes','forcefield'));
	$vintervals['hourly'] = array('interval' => 3600, 'display' => __('Every Hour','forcefield'));
	$vintervals['2hours'] = array('interval' => 7200, 'display' => __('Every 2 Hours','forcefield'));
	$vintervals['3hours'] = array('interval' => 10800, 'display' => __('Every 3 Hours','forcefield'));
	$vintervals['6hours'] = array('interval' => 21600, 'display' => __('Every 6 Hours','forcefield'));
	$vintervals['twicedaily'] = array('interval' => 43200, 'display' => __('Twice Daily','forcefield'));
	$vintervals['daily'] = array('interval' => 86400, 'display' => __('Daily','forcefield'));

	foreach ($vintervals as $vkey => $vinterval) {
		if (!isset($schedule[$vkey])) {$schedule[$vkey] = $vinterval;}
	}
   	return $schedule;
}

// Expiry Times
// ------------
function forcefield_get_expiries() {
	$vexpiries['none'] = array('interval' => 0, 'display' => __('No Expiry','forcefield'));
	$vexpiries['1hour'] = array('interval' => 3600, 'display' => __('1 Hour','forcefield'));
	$vexpiries['3hours'] = array('interval' => 10800, 'display' => __('3 Hours','forcefield'));
	$vexpiries['6hours'] = array('interval' => 21600, 'display' => __('6 Hours','forcefield'));
	$vexpiries['12hours'] = array('interval' => 43200, 'display' => __('12 Hours','forcefield'));
	$vexpiries['1day'] = array('interval' => 86400, 'display' => __('1 Day','forcefield'));
	$vexpiries['2days'] = array('interval' => 86400*2, 'display' => __('2 Days','forcefield'));
	$vexpiries['3days'] = array('interval' => 86400*3, 'display' => __('3 Days','forcefield'));
	$vexpiries['1week'] = array('interval' => 86400*7, 'display' => __('1 Week','forcefield'));
	$vexpiries['2weeks'] = array('interval' => 86400*14, 'display' => __('2 Weeks','forcefield'));
	$vexpiries['1month'] = array('interval' => 86400*30, 'display' => __('1 Month','forcefield'));
	$vexpiries['2months'] = array('interval' => 86400*60, 'display' => __('2 Months','forcefield'));
	$vexpiries['3months'] = array('interval' => 86400*90, 'display' => __('3 Months','forcefield'));
	$vexpiries['6months'] = array('interval' => 86400*180, 'display' => __('6 Months','forcefield'));
	$vexpiries['1year'] = array('interval' => 86400*365, 'display' => __('1 year','forcefield'));
	return $vexpiries;
}
