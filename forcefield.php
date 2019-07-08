<?php

/*
Plugin Name: ForceField
Plugin URI: http://wordquest.org/plugins/forcefield/
Author: Tony Hayes
Description: Flexible Protection for Login, Registration, Commenting, REST API and XML RPC.
Version: 0.9.5
Author URI: http://wordquest.org/
GitHub Plugin URI: majick777/forcefield
@fs_premium_only forcefield-pro.php
*/

// ==================
// === FORCEFIELD ===
// ==================
//
// === Plugin Setup ===
// - Set Plugin Values
// - Check for Update Checker
// - Load WordQuest/Pro Functions
// - Load Freemius SDK
// - Custom Freemius Connect Message
// === Plugin Settings ===
// - Get Plugin Settings
// - Get Default Settings
// - Add Defaults on Activation
// - Update Plugin Settings
// - Add Admin Options Page
// - Admin Options Page Loader
// === Helper Functions ===
// - Simple Alert Message
// - Get Remote IP Address
// - Get IP Address keys
// - Get Server IP Address
// - Get IP Address Type
// - Check if IP is in IP Range
// - 403 Forbidden and Exit
// - Filter WP Errors
// - Filter Login Error Messages (Hints)
// - Block Unwhitelisted Administrators
// - Email Alerts From Name
// - Get Transient Timeout
// === XML RPC ===
// - maybe Disable XML RPC Entirely
// - maybe Disable XML RPC Authenticated Methods
// - maybe Slowdown XML RPC Calls
// - maybe Remove XML RPC Link (RSD)
// - maybe Disable XML RPC Methods
// - maybe Disable Self Pings
// - maybe disable Anonymous Commenting
// === REST API ===
// - maybe Disable/Restrict REST API
// - maybe Slowdown REST API Calls
// - maybe Disable REST JSONP
// - maybe Remove REST API Info
// - maybe Change REST API Prefix
// - maybe Disable User Enumeration Endpoint
// - maybe disable REST API Anonymous Commenting
// === Authentication ===
// - XML RPC Authentication
// - XML RPC Error Message (Banned)
// - XML RPC Error Message (Blocked)
// - XML RPC requires SSL Message
// - Login Token Authentication
// - Registration Token Authentication
// - Blog Signup Authenticate
// - Lost Password Token Authentication
// - Commenting Authenticate
// - BuddyPress Registration Authenticate
// === IP Blocklist ===
// - Blocklist Contexts
// - IP Whitelist Check
// - IP Blacklist Check
// - Check IP Blocklist
// - Create IP Blocklist Table
// - set Blocklist Table Variables
// - Check Blocklist Table Exists
// - Clear IP Blocklist Table
// - check IP in Blocklist
// - get IP Blocklist Records
// - Add/Update an IP Address Record
// - Check Transgressions against Limit
// - Get Default Transgression Limits
// - Get Translated Block Reasons
// - Blocklist Transgression Cooldown
// - Blocklist Expire Old Rows
// - Blocklist Delete Old Rows
// - Blocklist Delete Record
// - AJAX Blocklist Delete Record
// - AJAX Blocklist Clear Table
// - Manual Unblock Form Output
// - AJAX Unblock Action
// - Blocklist Table Cleanup
// - WP CRON Schedule Table Cleanup
// === Data Lists ===
// - CRON Intervals
// - Expiry Times


// Development TODOs
// -----------------
// - maybe modulize tokens/blocklist/update functions ?
// - turn XML RPC method disable settings into on/off switches ?
// - handle IPv6 blocklist range checking ?
// - demote admin role option

// [DEVELOPMENT ONLY] you can uncomment this to bypass all REST API Nonce Checks
// (this can be helpful to eliminate REST nonces as a cause of endpoint failure)
// define('REST_NONCE_BYPASS', true);


// --------------------
// === Plugin Setup ===
// --------------------

// set Plugin Values
// -----------------
global $wordquestplugins;
$vslug = $vforcefieldslug = 'forcefield';
$wordquestplugins[$vslug]['version'] = $vforcefieldversion = '0.9.5';
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
$vprofunctions = dirname(__FILE__).'/'.$vslug.'-pro.php';
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

		/* BuddyPress Registration */
		// 0.9.5: added BuddyPress token field
		'buddypress_token' => 'yes',
		'buddypress_notokenban'	=> 'yes',
		'buddypress_norefblock'	=> 'yes',
		'buddypress_requiressl'	=> 'no',

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

		/* Auto Updates */
		// 'autoupdate_self' => 'no',
		// 'autoupdate_inactive_plugins' => 'no',
		// 'autoupdate_inactive_themes' => 'no',

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

		/* BuddyPress Registration */
		'buddypress_token' => 'checkbox',
		'buddypress_notokenban' => 'checkbox',
		'buddypress_norefblock' => 'checkbox',
		'buddypress_requiressl' => 'checkbox',

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

		/* Auto Updates */
		// 'autoupdate_self' => 'checkbox',
		// 'autoupdate_inactive_plugins' => 'checkbox',
		// 'autoupdate_inactive_themes' => 'checkbox',

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
			$vicon = plugins_url('images/wordquest-icon.png', __FILE__); $vposition = apply_filters('wordquest_menu_position','3');
			add_menu_page('WordQuest Alliance', 'WordQuest', 'manage_options', 'wordquest', 'wqhelper_admin_page', $vicon, $vposition);
		}
		// ...and plugin settings submenu and style fixes
		// 0.9.4: capital F dangit!
		add_submenu_page('wordquest', 'ForceField', 'ForceField', 'manage_options', 'forcefield', 'forcefield_options_page');
		add_action('admin_footer', 'forcefield_admin_javascript');
	} else {
		// otherwise just add a standard options page
		// 0.9.4: capital F dangit!
		add_options_page('ForceField', 'ForceField', 'manage_options', 'forcefield', 'forcefield_options_page');
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

// Load Plugin Admin Page
// ----------------------
function forcefield_options_page() {
	include(dirname(__FILE__).'/forcefield-admin.php');
	forcefield_options_admin_page();
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

			if ($vip != $vserverip) {
				if ($vdebug) {echo "<!-- \$_SERVER[".$vipkey."] : ".$vip." -->";}
				$viptype = forcefield_get_ip_type($_SERVER[$vipkey]);
				// 0.9.4: allow 127.0.0.1 and localhost as valid IPs
				if ($viptype == 'localhost') {
					// note: currently we use this to help distinguish actual IP
					// a different check is needed here to be truly accurate
					$vlocal = true;
				} elseif ($viptype) {return $_SERVER[$vipkey];}
			}
		}
	}

	// 0.9.4: maybe return for localhost IP
	if ($vlocal) {return 'localhost';}

	return false;
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
	// 0.9.4: allow for 127.0.0.1 and localhost
	if ( ($vip == '127.0.0.1') || ($vip == 'localhost') ) {return 'localhost';}
	elseif (filter_var($vip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {return 'ip4';}
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

// Filter WP Errors
// ----------------
// 0.9.1: added abstract error wrapper
function forcefield_filtered_error($verror, $verrormessage, $vstatus=false, $verrors=false) {
	if (!$vstatus) {$vstatus = 403;}
	$verrormessage = apply_filters('forcefield_error_message_'.$verror, $verrormessage);
	if ( $verrors && (is_wp_error($verrors)) ) {
		$verrors->add($verror, $verrormessage, array('status' => $vstatus));
		return $verrors;
	} else {return new WP_Error($verror, $verrormessage, array('status' => $vstatus));}
}

// maybe Filter Login Error Messages (Hints)
// -----------------------------------------
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
			// 1.5.0: shift get setting up so available in email body
			$vautodelete = forcefield_get_setting('admin_autodelete');

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

// Get Transient Timeout
// ---------------------
function forcefield_get_transient_timeout($transient) {
	global $wpdb;
	$query = "SELECT option_value FROM ".$wpdb->options." WHERE option_name LIKE '%_transient_timeout_".$transient."%'";
	$timeout = $wpdb->get_var($query);
	return $timeout;
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

// maybe Slowdown XML RPC Calls
// ----------------------------
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
// -------------------------------
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

// maybe Slowdown REST API Calls
// -----------------------------
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

// maybe Disable REST JSONP
// ------------------------
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

// maybe Disable User Enumeration Endpoint
// ---------------------------------------
add_filter('rest_endpoints', 'forcefield_endpoint_restriction', 99);
function forcefield_endpoint_restriction($endpoints) {
	if (forcefield_get_setting('restapi_nouserlist') == 'yes') {
		if (isset($endpoints['/wp/v2/users'])) {unset($endpoints['/wp/v2/users']);}
		if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);}
 	}
    return $endpoints;
}

// maybe disable REST API Anonymous Commenting
// -------------------------------------------
add_filter('rest_allow_anonymous_comments', 'forcefield_restapi_anonymous_comments');
function forcefield_restapi_anonymous_comments($allow) {
	$vallowanon = forcefield_get_setting('restapi_anoncomments');
	if ($vallowanon != 'yes') {$allow = false;}
	return $allow;
}

// maybe Remove All REST API Endpoints
// -----------------------------------
// add_action( 'plugins_loaded', 'forcefield_endpoints_remove', 0);
function forcefield_endpoints_remove() {
	remove_filter('rest_api_init', 'create_initial_rest_routes');
}

// REST Nonce Bypass
// -----------------
// 0.9.2: [DEV USE ONLY!] REST API Nonce Check Bypass
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
function forcefield_check_token($vcontext, $vgetexpiry=false) {

	global $vforcefield;

	$vdebug = true;
	// $vdebug = false;

	// validate IP address to IP key
	$viptype = forcefield_get_ip_type($vforcefield['ip']);
	// 0.9.4: allow for localhost IP type
	if ($viptype == 'localhost') {$vipaddress = str_replace('.', '-', $vforcefield['ip']);}
	elseif ($viptype == 'ip4') {$vipaddress = str_replace('.', '-', $vforcefield['ip']);}
	elseif ($viptype == 'ip6') {$vipaddress = str_replace(':', '--', $vforcefield['ip']);}
	else {return false;}

	// get transient token value by token key
	$vtoken = array();
	$vtransientid = $vcontext.'_token_'.$vipaddress;
	if ($vdebug) {echo "Transient ID: ".$vtransientid.PHP_EOL;}
	$vtokenvalue = get_transient($vtransientid);
	if ($vtokenvalue) {$vtoken['value'] = $vtokenvalue;}

	// 0.9.5: maybe return transient expiry time also for consistency
	if ($vgetexpiry) {
		$vtimeout = forcefield_get_transient_timeout($vtransientid);
		if ($vtimeout) {
			$vtime = time();
			if ($vdebug) {
				echo "Current Time: ".$vtime.PHP_EOL;
				echo "Expiry Time: ".$vtimeout.PHP_EOL;
			}
			$vexpiry = $vtimeout - $vtime;
			$vtoken['expiry'] = $vexpiry;
		}
	}
	if ($vdebug) {echo print_r($vtoken, true);}
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

// 0.9.5: add token field to BuddyPress registration form
add_action('bp_after_account_details_fields', 'forcefield_buddypress_field');
function forcefield_buddypress_field() {forcefield_add_field('buddypress'); do_action('bp_auth_token_errors');}


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

// 0.9.5: add token field to BuddyPress registration form
add_action('wp_ajax_nopriv_forcefield_buddypress', 'forcefield_buddypress_token');
function forcefield_buddypress_token() {forcefield_output_token('buddypress');}

// Token Output Abstract
// ---------------------
function forcefield_output_token($vcontext) {

	$vtoken = forcefield_create_token($vcontext);
	// echo $vcontext.PHP_EOL;
	// var_dump($vtoken); echo PHP_EOL;

	if ($vtoken) {
		// 0.9.3: some extra javascript obfuscation of token value
		$vtokenchars = str_split($vtoken['value'], 1);
		echo "<script>var bits = new Array(); ";
		foreach ($vtokenchars as $vi => $vchar) {echo "bits[".$vi."] = '".$vchar."'; ";}
		echo "bytes = bits.join('');
		parent.document.getElementById('auth_token_".$vcontext."').value = bytes;</script>".PHP_EOL;

		// 0.9.4: add a timer to auto-refresh expired token values
		if (isset($vtoken['expiry'])) {
			$vcycle = $vtoken['expiry'] * 1000;
			// TODO: use timer cycler rather than single timeout
			echo "<script>setTimeout(function() {window.location.reload();}, ".$vcycle.");</script>";
		}
	} else {echo __('Error. No Token was generated.', 'forcefield');}
	exit;
}

// Create a Token
// --------------
function forcefield_create_token($vcontext) {
	global $vforcefield;

	$vdebug = true;
	// $vdebug = false;

	$vtokenize = forcefield_get_setting($vcontext.'_token');
	if ($vdebug) {echo "Tokenize? ".$vtokenize.PHP_EOL;}
	if ($vtokenize != 'yes') {
		if ($vdebug) {echo "Tokens off for '".$vcontext."'";}
		return false;
	}

	// maybe return existing token
	// 0.9.5: also check and return token expiry if found
	$vtoken = forcefield_check_token($vcontext, true);
	if (isset($vtoken['value'])) {
		if ($vdebug) {echo "Existing Token: ".print_r($vtoken,true).PHP_EOL;}
		return $vtoken;
	}

	// validate IP address and make IP key
	$viptype = forcefield_get_ip_type($vforcefield['ip']);
	// 0.9.4: allow for localhost IP type
	if ($viptype == 'localhost') {$vip = str_replace('.', '-', $vforcefield['ip']);}
	elseif ($viptype == 'ip4') {$vip = str_replace('.', '-', $vforcefield['ip']);}
	elseif ($viptype == 'ip6') {$vip = str_replace(':', '--', $vforcefield['ip']);}
	else {
		if ($vdebug) {echo "No token generated for IP type '".$viptype."'";}
		return false;
	}
	if ($vdebug) {echo "IP: ".$vip.PHP_EOL;}

	// get and set token expiry length
	$vexpirytime = forcefield_get_setting('blocklist_tokenexpiry');
	// 0.9.4: added context-specific expiry time filtering
	$vexpirytime = apply_filters('blocklist_tokenexpiry_'.$vcontext, $vtokenexpiry);
	$vexpirytime = absint($vexpirytime);
	if (!is_numeric($vexpirytime)) {$vexpirytime = 300;}
	// 0.9.4: set a bare minimum token usage time
	if ($vexpirytime < 30) {$vexpirytime = 30;}
	if ( ($vcontext == 'comment') && ($vexpirytime < 300) ) {$vexpirytime = 300;}

	// create token and set transient
	$vtransientid = $vcontext.'_token_'.$vip;
	$vtokenvalue = wp_generate_password(12, false, false);
	set_transient($vtransientid, $vtokenvalue, $vexpirytime);
	// 0.9.4: return expiry time value also, for auto-refresh
	$vtoken = array('value' => $vtokenvalue, 'expiry' => $vexpirytime);
	$vtoken = apply_filters('forcefield_token', $vtoken, $vcontext);
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

		// 0.9.5: check now returns an array so we check 'value' key
		if (!$vchecktoken) {
			// probably the token transient has expired
			do_action('forcefield_login_oldtoken');
			do_action('forcefield_login_failed');
			$vstatus = 403; // HTTP 403: Forbidden
			return forcefield_filtered_error('login_token_expired', $verrormessage, $vstatus);
		} elseif ($vauthtoken != $vchecktoken['value']) {
			// fail, token is a mismatch
			// 0.9.1: record general token usage failure
			$vrecordbadtokens = forcefield_get_setting('blocklist_badtoken');
			if ($vrecordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}
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
			// 0.9.5: fix to undefined error string
			return forcefield_filtered_error('register_no_referer', $verrormessage, $vstatus);
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
		// 0.9.5: fix to undefined error string
		return forcefield_filtered_error('register_token_missing', $verrormessage, $vstatus, $errors);
	} else {
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_register_token');
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('register');

	// 0.9.5: check now returns an array so we check 'value' key
	if (!$vchecktoken) {
		// probably the register token transient has expired
		do_action('forcefield_register_oldtoken');
		do_action('forcefield_register_failed');
		$vstatus = 403; // HTTP 403: Forbidden
		return forcefield_filtered_error('register_token_expired', $verrormessage, $vstatus, $errors);
	} elseif ($vauthtoken != $vchecktoken['value']) {
		// fail, token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($vrecordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}
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

	// 0.9.5: check now returns an array so we check 'value' key
	if (!$vchecktoken) {
		// probably the register token transient has expired
		do_action('forcefield_signup_oldtoken');
		do_action('forcefield_signup_failed');
		$results['errors'] = forcefield_filtered_error('signup_token_expired', $verrormessage, false, $errors);
		return $results;
	} elseif ($vauthtoken != $vchecktoken['value']) {
		// fail, register token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtoken');
		if ($vrecordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}
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

	// 0.9.5: check now returns an array so we check 'value' key
	if (!$vchecktoken) {
		// probably the lost password token transient has expired
		do_action('forcefield_lostpass_oldtoken');
		do_action('forcefield_lostpass_failed');
		return forcefield_filtered_error('lostpass_token_expired', $verrormessage);
	} elseif ($vauthtoken != $vchecktoken['value']) {
		// fail, lost password token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($vrecordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}
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
		if ($vrecordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}
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

	// 0.9.5: check now returns an array so we check 'value' key
	if (!$vchecktoken) {
		// probably the comment token transient has expired
		do_action('forcefield_comment_oldtoken');
		do_action('forcefield_comment_failed');
		return forcefield_filtered_error('comment_token_expired', $verrormessage);
	} elseif ($vauthtoken != $vchecktoken['value']) {
		// fail, comment token is a mismatch
		// 0.9.1: record general token usage failure
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($vrecordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}
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

// BuddyPress Registration Trigger
// -------------------------------
// 0.9.5: add token field to BuddyPress registration
// ref: https://samelh.com/blog/2017/10/26/add-fields-buddypress-registration-form-profile/
add_action('bp_signup_validate', 'forcefield_buddypress_registration_authenticate');
function buddypress_registration_authenticate() {
	$error = forcefield_buddypress_authenticate();
	if ($error) {
		global $bp;
		if (!isset($bp->signup->errors)) {$bp->signup->errors = array();}
		$bp->signup->errors['auth_token'] = $error;
	}
}

// BuddyPress Registration Authenticate
// ------------------------------------
function forcefield_buddypress_authenticate() {

	// make sure we are on the BuddyPress registration page
    if (!function_exists('bp_is_current_component') || !bp_is_current_component('register')) {return;}

	// filtered general error message
	$verrormessage = forcefield_get_setting('error_message');
	$verrormessage = apply_filters('forcefield_error_message_buddypress', $verrormessage);

	// check IP whitelist
	if (forcefield_whitelist_check('actions')) {return false;}
	// 0.9.2: check IP blacklist
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// maybe require SSL connection for registration
	$vrequiressl = forcefield_get_setting('buddypress_requiressl');
	if ( ($vrequiressl == 'yes') && !is_ssl()) {
		// the instant version of the auth_redirect function
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// check for empty referer field
	if ($_SERVER['HTTP_REFERER'] == '') {
		do_action('forcefield_buddypress_noreferer');
		do_action('forcefield_no_referer');

		$vnorefban = forcefield_get_setting('blocklist_norefban');
		if ($vnorefban == 'yes') {
			$vtransgressions = forcefield_blocklist_record_ip('no_referer');
			$vblocked = forcefield_blocklist_check_transgressions('no_referer', $vtransgressions);
			if ($vblocked) {$vblock = true;}
		}
		$vnorefblock = forcefield_get_setting('buddypress_norefblock');
		if ($vnorefblock == 'yes') {$vblock = true;}

		if (isset($vblock) && $vblock) {
			do_action('forcefield_buddypress_failed');
			$vstatus = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('buddypress_no_referer', $verrormessage, $vstatus);
		}
	}

	// check tokenizer setting
    $vtokenize = forcefield_get_setting('buddypress_token');
	if ($vtokenize != 'yes') {return $errors;}

	// maybe ban the IP if missing the token form field
	if (!isset($_POST['auth_token'])) {
		$vrecordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($vrecordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}
		$vinstaban = forcefield_get_setting('buddypress_notokenban');
		if ($vinstaban == 'yes') {forcefield_blocklist_record_ip('no_buddypress_token');}
		do_action('forcefield_buddypress_notoken');
		do_action('forcefield_buddypress_failed');
		$vstatus = 400; // HTTP 400: Bad Request
		return forcefield_filtered_error('buddypress_token_missing', $verrormessage, $vstatus, $errors);
	} else {
		// maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_buddypress_token');
	}

	$vauthtoken = $_POST['auth_token'];
	$vchecktoken = forcefield_check_token('buddypress');

	// 0.9.5: check now returns an array so we check 'value' key
	if (!$vchecktoken) {
		// probably the register token transient has expired
		do_action('forcefield_buddypress_oldtoken');
		do_action('forcefield_buddypress_failed');
		$vstatus = 403; // HTTP 403: Forbidden
		return forcefield_filtered_error('buddypress_token_expired', $verrormessage, $vstatus, $errors);
	} elseif ($vauthtoken != $vchecktoken['value']) {
		// fail, token is a mismatch
		$vrecordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($vrecordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}
		do_action('forcefield_buddypress_mismatch');
		do_action('forcefield_buddypress_failed');
		$vstatus = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('buddypress_token_mismatch', $verrormessage, $vstatus, $errors);
	} else {
		// clear any bad token records
		forcefield_blocklist_delete_record(false, 'bad_token');
		// remove used register token
		forcefield_delete_token('buddypress');
		do_action('forcefield_buddypress_success');
	}

    return $errors;
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
			// 0.9.5: added buddypress registration token to conditions
			if ( ( ($vreason == 'admin_bad') && (forcefield_get_setting('admin_block') != 'yes') )
			  || ( ($vreason == 'xmlrpc_login') && (forcefield_get_setting('xmlrpc_authban') != 'yes') )
			  || ( ($vreason == 'no_login_token') && (forcefield_get_setting('login_token') != 'yes') )
			  || ( ($vreason == 'no_register_token') && (forcefield_get_setting('register_token') != 'yes') )
			  || ( ($vreason == 'no_signup_token') && (forcefield_get_setting('signup_token') != 'yes') )
			  || ( ($vreason == 'no_lostpass_token') && (forcefield_get_setting('lostpass_token') != 'yes') )
			  || ( ($vreason == 'no_comment_token') && (forcefield_get_setting('comment_token') != 'yes') )
			  || ( ($vreason == 'no_buddypress_token') && (forcefield_get_setting('buddypress_token') != 'yes') ) ) {
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
	if ($vreason) {
		// 0.9.5: fix to handle reason without specific IP address
		if ($vwhere == '') {$vwhere .= $wpdb->prepare(" WHERE `label` = %s", $vreason);}
		else {$vwhere .= $wpdb->prepare(" AND `label` = %s", $vreason);}
	}

	$vquery = "SELECT $vcolumnquery FROM ".$vforcefield['table']." ".$vwhere;
	$vresult = $wpdb->get_results($vquery, ARRAY_A);
	if ( (is_array($vresult)) && (isset($vresult[0])) ) {return $vresult;}
	return array();
}

// Add/Update an IP Address Record
// -------------------------------
// 0.9.1: use blocklist table
function forcefield_blocklist_record_ip($vreason, $vip=false) {
	// 0.9.6: fix to incorrect global declaration
	global $vforcefield;
	if (!isset($vip)) {$vip = $vforcefield['ip'];}
	global $wpdb; $vtime = time();

	// check for existing trangression of this type
	$vcolumns = array('id', 'label', 'transgressions', 'deleted_at');
	$vrecords = forcefield_blocklist_get_records($vcolumns, $vip, $vreason, false);
	if ($vrecords) {
		foreach ($vrecords as $vrecord) {
			if ($vrecord['label'] == $vreason) {
				// increment transgression count
				// 0.9.6: fix to (multiple!) incorrect variable names
				$vtransgressions = $vrecord['transgressions'];
				$vrecord['transgressions'] = $vtransgressions++;
				$vrecord['last_access_at'] = $vtime;
				$vrecord['deleted_at'] = 0;
				$vwhere = array('id' => $vrecord['id']);
				$vupdate = $wpdb->update($vforcefield['table'], $vrecord, $vwhere);
				return $vupdate;
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

	// 0.9.5: check now returns an array so we check 'value' key
	if (!$vchecktoken) {
		$vmessage = __('Time Limit Expired. Refresh the Page and Try Again.','forcefield');
	} elseif ($vauthtoken != $vchecktoken['value']) {
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

	// [PRO] trigger cleanup of any Pro block records
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
