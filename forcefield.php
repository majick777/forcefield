<?php

/*
Plugin Name: ForceField
Plugin URI: http://wordquest.org/plugins/forcefield/
Author: Tony Hayes
Description: Strong and Flexible Access, User Action, API and Role Protection
Version: 0.9.7
Author URI: http://wordquest.org/
GitHub Plugin URI: majick777/forcefield
@fs_premium_only forcefield-pro.php
*/

if (!function_exists('add_action')) {exit;}

// ==================
// === FORCEFIELD ===
// ==================
//
// === Plugin Setup ===
// - Set Plugin Options
// - Set Plugin Settings
// - Load Plugin Loader Class
// === Load Plugin Modules ===
// -- WordPress APIs Module
// --- XML RPC Filters
// --- REST API Filters
// -- Authentication Module
// --- Action Tokenizer
// --- Authentication Filters
// -- Blocklist Module
// === Plugin Settings ===
// - Transfer Old Settings
// - Process Special Settings
// - Admin Options Page Loader
// === Login Role Protection ===
// - Block Unwhitelisted Logins
// - Protect Default User Role
// === Helper Functions ===
// - Simple Alert Message
// - Get General Error Message
// - Get Remote IP Address
// - Get IP Address keys
// - Get Server IP Address
// - Get IP Address Type
// - Check if IP is in IP Range
// - 403 Forbidden and Exit
// - Filter WP Errors
// - Filter Login Error Messages (Hints)
// - Set Email Alerts From Name
// - Get Transient Timeout
// - Get CRON Intervals
// - Get Expiry Times


// Development TODOs
// -----------------
// - Idea: single device sign-ons (force logout other sessions)
// - Idea: track changes to options table values
// -- check home and siteurl options against WP_HOME and WP_SITEURL constants ?
// -- check / protect wp_user_roles, membership and default_role option for changes ?
// + add WP CLI commands for clearing IP blocklists
// ? turn XML RPC method disable settings into on/off switches ?
// - handle IPv6 blocklist range checking ?


// --------------------
// === Plugin Setup ===
// --------------------

// --------------
// Plugin Options
// --------------

// --- set administrator alert email to current user ---
// 0.9.6: added property_exists check for global context
global $current_user; $adminemail = '';
if (is_object($current_user) && property_exists($current_user, 'user_email')) {
	$adminemail = $current_user->user_email;
	if (strstr($adminemail, '@localhost')) {$adminemail = '';}
}

// 0.9.6: converted to options for plugin loader class
// 0.9.7: use new emails setting option type to allow multiple emails
$options = array(

	// --- Administrator Logins ---
	'admin_block' 			=> array('type' => 'checkbox', 'default' => 'yes'),
	'admin_blockaction'		=> array('type' => '/delete/revoke/demote', 'default' => ''),
	'admin_whitelist'		=> array('type' => 'csv', 'default' => ''),
	'admin_alert'			=> array('type' => 'checkbox', 'default' => 'yes'),
	'admin_email'			=> array('type' => 'emails', 'default' => $adminemail),

	// --- Super Admin Logins ---
	'super_block' 			=> array('type' => 'checkbox', 'default' => 'yes'),
	'super_blockaction'		=> array('type' => '/delete/revoke/demote', 'default' => 'revoke'),
	'super_whitelist'		=> array('type' => 'csv', 'default' => ''),
	'super_alert'			=> array('type' => 'checkbox', 'default' => 'yes'),
	'super_email'			=> array('type' => 'emails', 'default' => $adminemail),

	// --- IP Blocklist ---
	'blocklist_tokenexpiry'	=> array('type' => 'numeric', 'default' => '300'),
	'blocklist_noreferer'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'blocklist_notoken'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'blocklist_badtoken'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'blocklist_unblocking'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'blocklist_whitelist'	=> array('type' => 'special', 'default' => ''),
	'blocklist_blacklist'	=> array('type' => 'special', 'default' => ''),
	'blocklist_cooldown'	=> array('type' => 'frequency', 'default' => '10minutes'),
	'blocklist_expiry'		=> array('type' => 'frequency', 'default' => 'hourly'),
	'blocklist_delete'		=> array('type' => 'frequency', 'default' => 'daily'),
	'blocklist_cleanups'	=> array('type' => 'frequency', 'default' => 'twicedaily'),

	// --- Login ---
	'login_token'			=> array('type' => 'checkbox', 'default' => 'yes'),
	'login_notokenban'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'login_norefblock'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'login_requiressl'		=> array('type' => 'checkbox', 'default' => 'no'),
	'login_nohints'			=> array('type' => 'checkbox', 'default' => 'no'),

	// --- Registration ---
	'register_token'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'register_notokenban'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'register_norefblock'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'register_requiressl'	=> array('type' => 'checkbox', 'default' => 'no'),

	// --- BuddyPress Registration ---
	// 0.9.5: added BuddyPress token field
	'buddypress_token'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'buddypress_notokenban'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'buddypress_norefblock'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'buddypress_requiressl'	=> array('type' => 'checkbox', 'default' => 'no'),

	// --- Blog Signup (Multisite) ---
	'signup_token'			=> array('type' => 'checkbox', 'default' => 'yes'),
	'signup_notokenban'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'signup_norefblock'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'signup_requiressl'		=> array('type' => 'checkbox', 'default' => 'no'),

	// --- Lost Password ---
	'lostpass_token'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'lostpass_notokenban'	=> array('type' => 'checkbox', 'default' => 'no'),
	'lostpass_norefblock'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'lostpass_requiressl'	=> array('type' => 'checkbox', 'default' => 'no'),

	// --- Comments ---
	'comment_token'			=> array('type' => 'checkbox', 'default' => 'yes'),
	'comment_notokenban'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'comment_norefblock'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'comment_requiressl'	=> array('type' => 'checkbox', 'default' => 'no'),

	// --- XML RPC ---
	'xmlrpc_disable'		=> array('type' => 'checkbox', 'default' => 'no'),
	'xmlrpc_noauth'			=> array('type' => 'checkbox', 'default' => 'yes'),
	'xmlrpc_authblock'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'xmlrpc_authban'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'xmlrpc_requiressl'		=> array('type' => 'checkbox', 'default' => 'no'),
	'xmlrpc_slowdown'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'xmlrpc_anoncomments'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'xmlrpc_restricted'		=> array('type' => 'checkbox', 'default' => 'no'),
	'xmlrpc_roles'			=> array('type' => 'special', 'default' => array()),
	'xmlrpc_nopingbacks'	=> array('type' => 'checkbox', 'default' => 'no'),
	'xmlrpc_noselfpings'	=> array('type' => 'checkbox', 'default' => 'yes'),

	// --- REST API ---
	'restapi_disable'		=> array('type' => 'checkbox', 'default' => 'no'),
	'restapi_requiressl'	=> array('type' => 'checkbox', 'default' => 'no'),
	'restapi_slowdown'		=> array('type' => 'checkbox', 'default' => 'yes'),
	'restapi_anoncomments'	=> array('type' => 'checkbox', 'default' => 'no'),
	'restapi_restricted'	=> array('type' => 'checkbox', 'default' => 'no'),
	'restapi_roles'			=> array('type' => 'special', 'default' => array()),
	'restapi_nouserlist'	=> array('type' => 'checkbox', 'default' => 'yes'),
	'restapi_nolinks'		=> array('type' => 'checkbox', 'default' => 'no'),
	'restapi_nojsonp'		=> array('type' => 'checkbox', 'default' => 'no'),
	// 0.9.7: removed REST API prefix changes (this filter is better hardcoded in mu-plugins)
	// 'restapi_prefix'		=> array('type' => 'alphanumeric', 'default' => ''),

	// TODO: options backup protection
	// 'options_alert_email'	=> array('type' => 'emails', 'default' => $adminemail),
	// 'options_backup_checks'	=> array('type' => 'checkbox', 'default' => 'yes'),
	// 'options_backup_actions'	=> array('type' => 'restore/alert/restorealert', 'default' => 'alert'),

	// --- Auto Updates ---
	// 0.9.6: disabled auto update options
	// 'autoupdate_self' => 'no',
	// 'autoupdate_inactive_plugins' => 'no',
	// 'autoupdate_inactive_themes' => 'no',

	// --- Admin Page Interface ---
	'current_tab'			=> array('type' => 'general/role-protect/user-actions/api-access/ip-blocklist',
									'default' => 'general'),

);

// ----------------------
// Special Options Filter
// ----------------------
// 0.9.7: added special options filter
add_filter('forcefield_options', 'forcefield_special_settings', 0);
function forcefield_special_settings($options) {

	// 0.9.1: add transgression limit defaults
	$limits = forcefield_blocklist_get_default_limits();
	foreach ($limits as $key => $limit) {
		$options['limit_'.$key] = array('type' => 'numeric', 'default' => $limit);
	}
	return $options;
}

// ---------------
// Plugin Settings
// ---------------
// 0.9.6: updated settings to use plugin loader class
$slug = 'forcefield';
$args = array(
	// --- Plugin Info ---
	'slug'			=> $slug,
	'file'			=> __FILE__,
	'version'		=> '0.0.1',

	// --- Menus and Links ---
	'title'			=> 'ForceField',
	'parentmenu'	=> 'wordquest',
	'home'			=> 'http://wordquest.org/plugins/forcefield/',
	'support'		=> 'http://wordquest.org/quest-category/'.$slug.'/',
	'share'			=> 'http://wordquest.org/plugins/forcefield/#share',
	'donate'		=> 'http://wordquest.org/contribute/?plugin=forcefield',
	'donatetext'	=> __('Support ForceField'),
	'welcome'		=> '',	// TODO

	// --- Options ---
	'namespace'		=> 'forcefield',
	'option'		=> 'forcefield',
	'options'		=> $options,
	'settings'		=> 'ff',

	// --- WordPress.Org ---
	// 'wporgslug'	=> 'forcefield',
	'textdomain'	=> 'forcefield',
	// 'wporg'		=> false,

	// --- Freemius ---
	'freemius_id'	=> '1555',
	'freemius_key'	=> 'pk_8c058d54aa8e43dbb8fd1259992ab',
	'hasplans'		=> false,
	'hasaddons'		=> false,
	'plan'			=> 'free',
);

// ----------------------------
// Load Plugin Module Functions
// ----------------------------
// 0.9.7: moved above plugin loader for function accessibility
$forcefielddir = dirname(__FILE__).DIRECTORY_SEPARATOR;

// --- WordPress APIs Module ---
include_once($forcefielddir.'forcefield-apis.php');

// --- Authentication Module ---
include_once($forcefielddir.'forcefield-auth.php');

// --- Blocklist Module ---
include_once($forcefielddir.'forcefield-block.php');

// ----------------------------
// Start Plugin Loader Instance
// ----------------------------
require(dirname(__FILE__).DIRECTORY_SEPARATOR.'loader.php');
$instance = new forcefield_loader($args);

// ----------------------------
// Check/Create Debug Directory
// ----------------------------
umask(0000);
$debugdir = dirname(__FILE__).'/debug';
if (!is_dir($debugdir)) {wp_mkdir_p($debugdir);}
if (is_dir($debugdir)) {

	$debughtaccess = $debugdir."/.htaccess";
	$htaccess = "deny from all";

	// --- check for existing htaccess file and content match ---
	$writehtaccess = false;
	if (!file_exists($debughtaccess)) {$writehtaccess = true;}
	elseif (file_get_contents($debughtaccess) != $htaccess) {$writehtaccess = true;}

	if ($writehtaccess) {

		// --- check direct writing method before writing ---
		if (!function_exists('get_filesystem_method')) {require_once(ABSPATH.'/wp-admin/includes/file.php');}
		$checkmethod = get_filesystem_method(array(), $debugdir, false);

		if ($checkmethod == 'direct') {
			// --- write directly ---
			$fh = fopen($debughtaccess, 'w');
			@fwrite($fh, $htaccess); fclose($fh);
			@chmod($debughtaccess, 0644);
		} else {
			// --- write using WP Filesystem ---
			global $wp_filesystem;
			if (empty($wp_filesystem)) {WP_Filesystem();}
			$wp_filesystem->put_contents($debughtaccess, $htaccess, FS_CHMOD_FILE);
		}

		// 1.9.7: recheck for written .htaccess file
		if (!file_exists($debughtaccess) || (file_get_contents($debughtaccess) != $htaccess)) {
			add_action('admin_notices', 'forcefield_debug_htaccess_warning');
		}
	}
} else {add_action('admin_notices', 'forcefield_debug_directory_warning');}

// -------------------------------------
// Debug Directory not Writeable Warning
// -------------------------------------
function forcefield_debug_directory_warning() {
	global $forcefield;
	$message = __('Warning','forcefield').": ".$forcefield['title']." ";
	$message .= __('Debug Log Directory NOT writeable!','forcefield');
	return $message;
}

// ------------------------------------
// Debug Htaccess Write Failure Warning
// ------------------------------------
// 0.9.7: added this warning
function forcefield_debug_htaccess_warning() {
	global $forcefield;
	$message = __('Warning','forcefield').": ".$forcefield['title']." ";
	$message .= __('Debug Log Directory .htaccess write failure.','forcefield')."<br>";
	$message .= __('It is recommended that you fix this problem manually.','forcefield');
	return $message;
}

// -----------------------
// === Plugin Settings ===
// -----------------------

// ------------------------
// Plugin Admin Page Loader
// ------------------------
function forcefield_settings_page() {
	include(dirname(__FILE__).'/forcefield-admin.php');
	forcefield_admin_page();
}

// ---------------------------
// maybe Transfer Old Settings
// ---------------------------
// 0.9.6: maybe transfer from 'settings' array key
function forcefield_transfer_settings() {
	global $forcefield;
	$current = get_option('forcefield');
	if ($current && isset($current['settings'])) {
		foreach ($current['settings'] as $key => $value) {$forcefield[$key] = $value;}
		if (isset($forcefield['settings'])) {unset($forcefield['settings']);}
		update_option('forcefield', $forcefield);
	}
}

// ------------------------
// Process Special Settings
// ------------------------
// 0.9.6: process special settings updates
// 0.9.7: removed restapi_prefix option saving
function forcefield_process_special($settings) {

	// --- get needed data values ---
	// $prefix = $settings['restapi_prefix'];
	$roles = wp_roles()->get_names();
	$intervals = forcefield_get_intervals();

	// --- set special option keys and types ---
	$optionkeys = array(
		'blocklist_whitelist'	=> 'iptextarea',
		'blocklist_blacklist'	=> 'iptextarea',
		'blocklist_cooldown'	=> 'frequency',
		'blocklist_expiry'		=> 'frequency',
		'blocklist_delete'		=> 'frequency',
		'blocklist_cleanups'	=> 'frequency',
		// 0.9.7: removed restapi_prefix option
		// 'restapi_prefix'		=> 'specialtext',
	);

	// --- loop to update special options ---
	foreach ($optionkeys as $key => $type) {
		$postkey = 'ff_'.$key;
		if (isset($_POST[$postkey])) {$posted = $_POST[$postkey];} else {$posted = '';}

		if ($type == 'specialtext') {

			$test = str_replace('/', '', $posted);
			$checkposted = preg_match('/^[a-zA-Z0-9_\-]+$/', $test);
			if ($checkposted) {$settings[$key] = $posted;}
			else {$settings[$key] = '';}

		} elseif ($type == 'iptextarea') {

			// 0.9.1: added for IP list textareas
			if (trim($posted) == '') {$settings[$key] = '';}
			else {
				// --- validate textarea IP lines ---
				$posted = stripslashes($posted);
				$validips = $iprows = array();
				if (strstr($posted, "\n")) {$iprows = explode("\n", $posted);} else {$iprows = array($posted);}
				foreach ($iprows as $i => $iprow) {
					// note: allowing for comma separated lines ---
					$iprow = trim($iprow); $ips = array();
					if (strstr($iprow, ",")) {$ips = explode(",", $iprow);} else {$ips = array(trim($iprow));}
					foreach ($ips as $ip) {
						$ip = trim($ip);
						$checkip = forcefield_get_ip_type($ip);
						if ($checkip) {$validips[] = $ip;}
					}
				}
				$settings[$key] = $validips;
			}

		} elseif ($type == 'frequency') {
			if (array_key_exists($posted, $intervals)) {$settings[$key] = $posted;}
			else {$settings[$key] = '';}
		}
	}

	// ---- maybe flush rewrite rules if REST prefix was changed ---
	if ($prefix != $settings['restapi_prefix']) {flush_rewrite_rules();}

	// --- handle XML RPC and REST API role restrictions ---
	// 0.9.7: fix to remove newly unchecked API role restrictions
	$settings['xmlrpc_roles'] = $settings['restapi_roles'] = array();
	foreach ($roles as $role => $label) {
		$xmlrpckey = 'ff_xmlrpc_role_'.$role;
		if (isset($_POST[$xmlrpckey])) {
			if ($_POST[$xmlrpckey] == 'yes') {$settings['xmlrpc_roles'][] = $role;}
			elseif (in_array($role, $settings['xmlrpc_roles'])) {
				foreach ($settings['xmlrpc_roles'] as $i => $value) {
					if ($value == $role) {unset($settings['xmlrpc_roles'][$i]);}
				}
			}
		}
		$restkey = 'ff_restapi_role_'.$role;
		if (isset($_POST[$restkey])) {
			if ($_POST[$restkey] == 'yes') {$settings['restapi_roles'][] = $role;}
			elseif (in_array($role, $settings['restapi_roles'])) {
				foreach ($settings['restapi_roles'] as $i => $value) {
					if ($value == $role) {unset($settings['restapi_roles'][$i]);}
				}
			}
		}
	}

	// --- update transgression limits ---
	// 0.9.1: handle transgression limit updates
	$limits = forcefield_blocklist_get_default_limits();
	foreach ($limits as $key => $limit) {
		if (isset($_POST['ff_limit_'.$key])) {
			$posted = absint($_POST['ff_limit_'.$key]);
			// 0.9.6: allow for -1 = auto-pass and 0 = auto-fail
			if ($posted < -1) {$posted = -1;} elseif ($posted < 1) {$posted = 0;}
			$settings['limit_'.$key] = $posted;
		} else {$settings['limit_'.$key] = $limit;}
	}

	return $settings;
}


// -----------------------------
// === Login Role Protection ===
// -----------------------------

// --------------------------
// Block Unwhitelisted Logins
// --------------------------
add_action('init', 'forcefield_administrator_validation', 0);
function forcefield_administrator_validation() {

	// --- bug out if not logged in ---
	if (!is_user_logged_in()) {return;}

	// --- get current user ---
	$user = wp_get_current_user();
	$userid = $user->data->ID;
	$userlogin = $user->data->user_login;

	// --- get login settings ---
	if (is_multisite()) {$superadmins = get_super_admins();} else {$superadmins = array();}
	$blocksuper = forcefield_get_setting('super_block');
	$blockadmins = forcefield_get_setting('admin_block');

	// Super Admin Login Check
	// -----------------------
	// 0.9.6: added whitelist check for super admin role (multisite only)
	if (is_multisite()) {
		if ( ($blocksuper == 'yes') && (count($superadmins) > 0) && is_super_admin($userid) ) {

			// --- get whitelist ---
			$whitelisted = array();
			$whitelist = forcefield_get_setting('super_whitelist');
			if (strstr($whitelist, ',')) {
				$whitelisted = explode(',', $whitelist);
				foreach ($whitelisted as $i => $whitelisted) {$whitelisted[$i] = trim($whitelisted);}
			} elseif (trim($whitelist) != '') {$whitelisted = array(trim($whitelisted));}

			// --- check if in whitelist ---
			if ( (count($whitelisted) > 0) && !in_array($userlogin, $whitelisted)) {

				// --- maybe send admin alert email ---
				$adminemail = forcefield_get_setting('super_email');
				$alertemail = forcefield_get_setting('super_alert');
				$blockaction = forcefield_get_setting('super_blockaction');

				// ---- handle block action ---
				if ($blockaction == 'delete') {

					// --- delete the user completely ---
					if (!function_exists('wp_delete_user')) {include(ABSPATH.WPINC.'/user.php');}
					wp_delete_user($userid);

				} elseif ($blockaction == 'revoke') {

					// --- remove administrator role ---
					revoke_super_admin($userid);

				} elseif ($blockaction == 'demote') {

					// --- remove all roles and add subscriber only ---
					revoke_super_admin($userid);
					foreach ($user->roles as $role) {$user->remove_role($role);}
					$user->add_role('subscriber');

				}

				// --- maybe send alert email ---
				if ( ($alertemail == 'yes') && ($adminemail != '') ) {

					// --- set mail from name ---
					add_filter('wp_mail_from_name', 'forcefield_email_from_name');

					// --- set email subject and body ---
					$blogname = get_bloginfo('name');
					$subject = '[ForceField] Warning: Unwhitelisted Super-Admin Login!';
					$body = 'ForceField plugin has blocked an unwhitelisted super-admin login'."\n";
					$body .= 'to WordPress site '.$blogname.' ('.home_url().')'."\n\n";
					$body .= 'Username Blocked: "'.$userlogin.'"'."\n\n";
					$body .= 'If this username is familiar, add it to your whitelist to stop further alerts.'."\n";
					$body .= 'But if it is unfamiliar, your site security may be compromised.'."\n\n";

					// --- maybe add block action info ---
					if ($blockaction == 'delete') {
						$body .= 'Additionally, according to your ForceField plugin settings,'."\n";
						$body .= 'the user "'.$userlogin.'" was automatically deleted.'."\n\n";
					} elseif ($blockaction == 'revoke') {
						$body .= 'Additionally, according to your ForceField plugin settings,'."\n";
						$body .= 'the super-admin role has been revoked from user "'.$userlogin.'.'."\n\n";
					} elseif ($blockaction == 'demote') {
						$body .= 'Additionally, according to your ForceField plugin settings,'."\n";
						$body .= 'the user "'.$userlogin.' has been demoted to a subscriber.'."\n\n";
					}

					// --- add dump user object ---
					$body .= 'Below is a dump of the user object for "'.$userlogin.'"'."\n";
					$body .= '----------'."\n";
					$body .= print_r($user, true);

					// --- send the alert email now ---
					// 0.9.7: allow for multiple alert emails
					if (strstr($adminemail, ',')) {$emails = explode(',', $adminemail);}
					else {$emails = array(trim($adminemail));}
					foreach ($emails as $email) {wp_mail($email, $subject, $body);}
				}

				// --- add IP address to blocklist ---
				forcefield_blocklist_record_ip('admin_bad');

				// --- clear the login cache ---
				wp_cache_delete($userid, 'users');
				wp_cache_delete($userlogin, 'userlogins');
				wp_logout(); exit;
			}
		}
	}

	// Administrator Login Check
	// -------------------------
	if ( ($blockadmins == 'yes') && in_array('administrator', (array)$user->roles) ) {

		// --- get whitelist ---
		$whitelisted = array();
		$whitelist = forcefield_get_setting('admin_whitelist');
		if (strstr($whitelist, ',')) {
			$whitelisted = explode(',', $whitelist);
			foreach ($whitelisted as $i => $admin) {$whitelisted[$i] = trim($admin);}
		} elseif (trim($whitelist) != '') {$whitelisted = array(trim($whitelist));}

		// --- check if not in whitelist ---
		if ( (count($whitelisted) > 0) && !in_array($userlogin, $whitelisted)) {

			// --- maybe send admin alert email ---
			// 0.9.6: shift get setting up so available in email body
			// 0.9.6: change admin_autodelete to admin_blockaction setting
			$adminemail = forcefield_get_setting('admin_email');
			$alertemail = forcefield_get_setting('admin_alert');
			// $autodelete = forcefield_get_setting('admin_autodelete');
			$blockaction = forcefield_get_setting('admin_blockaction');

			// ---- handle block action ---
			if ($blockaction == 'delete') {

				// --- delete the user completely ---
				if (!function_exists('wp_delete_user')) {include(ABSPATH.WPINC.'/user.php');}
				wp_delete_user($userid);

			} elseif ($blockaction == 'revoke') {

				// --- remove administrator role ---
				$user->remove_role('administrator');

			} elseif ($blockaction == 'demote') {

				// --- remove all roles and add subscriber only ---
				foreach ($user->roles as $role) {$user->remove_role($role);}
				$user->add_role('subscriber');

			}

			// --- maybe send alert email ---
			if ( ($alertemail == 'yes') && ($adminemail != '') ) {

				// --- set mail from name ---
				add_filter('wp_mail_from_name', 'forcefield_email_from_name');

				// --- set email subject and body ---
				$blogname = get_bloginfo('name');
				$subject = '[ForceField] Warning: Unwhitelisted Administrator Login!';
				$body = 'ForceField plugin has blocked an unwhitelisted administrator login'."\n";
				$body .= 'to WordPress site '.$blogname.' ('.home_url().')'."\n\n";
				$body .= 'Username Blocked: "'.$userlogin.'"'."\n\n";
				$body .= 'If this username is familiar, add it to your whitelist to stop further alerts.'."\n";
				$body .= 'But if it is unfamiliar, your site security may be compromised.'."\n\n";

				// --- maybe add block action info ---
				if ($blockaction == 'delete') {
					$body .= 'Additionally, according to your ForceField plugin settings,'."\n";
					$body .= 'the user "'.$userlogin.'" was automatically deleted.'."\n\n";
				} elseif ($blockaction == 'revoke') {
					$body .= 'Additionally, according to your ForceField plugin settings,'."\n";
					$body .= 'the administrator role has been revoked from user "'.$userlogin.'.'."\n\n";
				} elseif ($blockaction == 'demote') {
					$body .= 'Additionally, according to your ForceField plugin settings,'."\n";
					$body .= 'the user "'.$userlogin.' has been demoted to a subscriber.'."\n\n";
				}

				// --- add dump user object ---
				$body .= 'Below is a dump of the user object for "'.$useruser_login.'"'."\n";
				$body .= '----------'."\n";
				$body .= print_r($user, true);

				// --- send the alert email now ---
				// 0.9.6: fix to incorrect message variable typo
				// 0.9.7: allow for multiple alert emails
				if (strstr($adminemail, ',')) {$emails = explode(',', $adminemail);}
				else {$emails = array(trim($adminemail));}
				foreach ($emails as $email) {wp_mail($email, $subject, $body);}
			}

			// --- add IP address to blocklist ---
			forcefield_blocklist_record_ip('super_bad');

			// --- clear the login cache ---
			wp_cache_delete($userid, 'users');
			wp_cache_delete($userlogin, 'userlogins');
			wp_logout(); exit;
		}
	}
}


// ------------------------
// === Helper Functions ===
// ------------------------

// ------------------------
// Javascript Alert Message
// ------------------------
function forcefield_alert_message($message) {
	echo "<script>alert('".$message."');</script>";
}

// -------------------------
// Get General Error Message
// -------------------------
function forcefield_get_error_message() {
	$message = __('Request Failed. Authentication Error.','forcefield');
	$message = apply_filters('forcefield_error_message', $message);
	return $message;
}

// ---------------------
// Get Remote IP Address
// ---------------------
function forcefield_get_remote_ip($debug=false) {

	// --- get server IP address ---
	// 0.9.3: get server IP to match against
	$serverip = forcefield_get_server_ip($debug);

	// --- get possible remote address keys ---
	// 0.9.3: get remote address keys
	$ipkeys = forcefield_get_remote_ip_keys();

	// 0.9.7: fix for undefined local variable warning
	$local = false;
	foreach ($ipkeys as $ipkey) {
		if (isset($_SERVER[$ipkey]) && !empty($_SERVER[$ipkey])) {
			$ip = $_SERVER[$ipkey];

			// --- filter out server IP match ---
			// 0.9.3: check remote IP against server IP
			if ($ip != $serverip) {
				if ($debug) {echo "<!-- \$_SERVER[".$ipkey."] : ".$ip." -->";}
				$iptype = forcefield_get_ip_type($_SERVER[$ipkey]);

				// 0.9.4: allow 127.0.0.1 and localhost as valid IPs
				if ($iptype == 'localhost') {
					// note: currently we use this to help distinguish actual IP
					// a different check is needed here to be truly accurate
					$local = true;
				} elseif ($iptype) {return $_SERVER[$ipkey];}
			}
		}
	}

	// 0.9.4: maybe return for localhost IP
	if ($local) {return 'localhost';}

	return false;
}

// -------------------
// Get IP Address keys
// -------------------
// 0.9.3: set possible $_SERVER keys for IP
function forcefield_get_remote_ip_keys() {
	$ipkeys = array(
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
	$ipkeys = apply_filters('forcefield_remote_ip_keys', $ipkeys);
	return $ipkeys;
}

// ---------------------
// Get Server IP Address
// ---------------------
function forcefield_get_server_ip($debug=false) {

	// --- check cached server IP ---
	$serverip = get_transient('forcefield_server_ip');
	if ($serverip) {return $serverip;}

	if (function_exists('gethostbyname')) {

		// --- use DNS lookup of the server host name ---
		$hostname = $_SERVER['HTTP_HOST'];
		if ($debug) {echo "<!-- Host Name: ".$hostname." -->";}
		$serverip = gethostbyname($hostname);

	} else {

		// --- ping an IP server to reliably get server IP ---
		$url = 'http://api.ipify.org/';
		$response = wp_remote_request($url, array('method' => 'GET'));

		if (is_wp_error($response)) {return false;}
		if ( (!isset($response['response']['code'])) || ($response['response']['code'] != 200) ) {return false;}
		$serverip = $response['body'];
	}
	if (!forcefield_get_ip_type($serverip)) {return false;}
	if ($debug) {echo "<!-- Server IP: ".$serverip." -->";}

	// --- cache server IP  ---
	set_transient('forcefield_server_ip', $serverip, (24*60*60));

	return $serverip;
}

// -------------------
// Get IP Address Type
// -------------------
// 0.9.1: use helper to determine IP address type
function forcefield_get_ip_type($ip) {
	// ref: https://www.mikemackintosh.com/5-tips-for-working-with-ipv6-in-php/
	// 0.9.4: allow for 127.0.0.1 and localhost
	if ( ($ip == '127.0.0.1') || ($ip == 'localhost') ) {return 'localhost';}
	elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {return 'ip4';}
	elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {return 'ip6';}
	else {return false;}
}

// --------------------------
// Check if IP is in IP Range
// --------------------------
// 0.9.2: check IP is in a provided IP range (without using ip2long)
function forcefield_is_ip_in_range($ip, $iprange) {

	$iptype = forcefield_get_ip_type($ip);
	if ($iptype == 'ip4') {

		// --- handle IP4 ranges ---
		$ipparts = explode('.', $ip);
		$rangeparts = explode('.', $iprange);
		if ($ipparts[0] != $rangeparts[0]) {return false;}

		for ($i = 1; $i < 4; $i++) {
			$match = false;
			if ($rangeparts[$i] == '*') {$match = true;}
			elseif ( ($ipparts[$i] == $rangeparts[$i]) ) {$match = true;}
			elseif (strstr($rangeparts[$i], '-')) {
				$maxmin = explode('-', $rangeparts);
				if ( ($ipparts[$i] >= $maxmin[0]) && ($ipparts[$i] <= $maxmin[1]) ) {$match = true;}
			}
			if (!$match) {return false;}
		}

	} elseif ($iptype == 'ip6') {

		// TODO: handle IP6 ranges
		return false; // TEMP

	}
	return true;
}

// ----------------------
// 403 Forbidden and Exit
// ----------------------
function forcefield_forbidden_exit() {
	// status_header('403', 'HTTP/1.1 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	header('Status: 403 Forbidden');
	header('Connection: Close'); exit;
}

// ----------------
// Filter WP Errors
// ----------------
// 0.9.1: added abstract error wrapper
function forcefield_filtered_error($error, $errormessage, $status=false, $errors=false) {

	global $forcefield;

	if (!$status) {$status = 403;}
	$errormessage = apply_filters('forcefield_error_message_'.$error, $errormessage);

	// --- log errors to debug file ---
	// 0.9.7: added authentication error logging
	$datetime = date('Y-m-d H:i:s', time());
	$ip = forcefield_get_remote_ip();
	$debugline = '['.$datetime.'] '.$ip.': '.$error.' - '.$errormessage.' ('.$status.')'.PHP_EOL;
	error_log($debugline, 3, dirname(__FILE__).'/debug/auth-errors.log');

	// --- return errors ---
	if ($errors && (is_wp_error($errors)) ) {
		$errors->add($error, $errormessage, array('status' => $status));
		return $errors;
	} else {return new WP_Error($error, $errormessage, array('status' => $status));}
}

// -----------------------------------------
// Maybe Remove Login Error Messages (Hints)
// -----------------------------------------
add_filter('login_errors', 'forcefield_login_error_message');
function forcefield_login_error_message($message) {
	$removehints = forcefield_get_setting('login_nohints');
	if ($removehints == 'yes') {return '';}
	return $message;
}

// --------------------------
// Set Email Alerts From Name
// --------------------------
function forcefield_email_from_name() {
	// 0.9.1: forcefield-specific filter for the email from name
	return apply_filters('forcefield_emails_from_name', get_bloginfo('name'));
}

// ---------------------
// Get Transient Timeout
// ---------------------
function forcefield_get_transient_timeout($transient) {
	global $wpdb;
	// TODO: use wpdb->prepare on timeout query value ?
	$query = "SELECT option_value FROM ".$wpdb->options." WHERE option_name LIKE '%_transient_timeout_".$transient."%'";
	// $query = "SELECT option_value FROM ".$wpdb->options." WHERE option_name LIKE '%_transient_timeout_%s%'";
	// $query = $wpdb->prepare($query, $transient);
	$timeout = $wpdb->get_var($query);
	return $timeout;
}

// ------------------
// Get CRON Intervals
// ------------------
// 0.9.1: get cron intervals, doubles as cron schedule filter
add_filter('cron_schedules', 'forcefield_get_intervals');
function forcefield_get_intervals($schedule=array()) {

	// --- set cron intervals to add ---
	// 0.9.6: simplify interval list
	$intervals = array(
		'5minutes'		=> array('interval' => 300, 'display' => __('Every 5 Minutes','forcefield')),
		'10minutes'		=> array('interval' => 600, 'display' => __('Every 10 Minutes','forcefield')),
		'15minutes'		=> array('interval' => 900, 'display' => __('Every 15 Minutes','forcefield')),
		'20minutes'		=> array('interval' => 1200, 'display' => __('Every 20 Minutes','forcefield')),
		'30minutes'		=> array('interval' => 1800, 'display' => __('Every 30 Minutes','forcefield')),
		'hourly'		=> array('interval' => 3600, 'display' => __('Every Hour','forcefield')),
		'2hours'		=> array('interval' => 7200, 'display' => __('Every 2 Hours','forcefield')),
		'3hours'		=> array('interval' => 10800, 'display' => __('Every 3 Hours','forcefield')),
		'6hours'		=> array('interval' => 21600, 'display' => __('Every 6 Hours','forcefield')),
		'twicedaily'	=> array('interval' => 43200, 'display' => __('Twice Daily','forcefield')),
		'daily'			=> array('interval' => 86400, 'display' => __('Daily','forcefield')),
	);

	// --- filter cron intervals ----
	// 0.9.6: added cron interval filter
	$intervals = apply_filters('forcefield_cron_invervals', $intervals);

	// --- add to current schedules ---
	foreach ($intervals as $key => $interval) {
		if (!isset($schedule[$key])) {$schedule[$key] = $interval;}
	}
   	return $schedule;
}

// --------------------------
// Get Transient Expiry Times
// --------------------------
function forcefield_get_expiries() {

	// --- set transient expiry list ---
	// 0.9.6: simplify expiry array declaration
	$expiries = array(
		'none'		=> array('interval' => 0, 'display' => __('No Expiry','forcefield')),
		'1hour'		=> array('interval' => 3600, 'display' => __('1 Hour','forcefield')),
		'3hours'	=> array('interval' => 10800, 'display' => __('3 Hours','forcefield')),
		'6hours'	=> array('interval' => 21600, 'display' => __('6 Hours','forcefield')),
		'12hours'	=> array('interval' => 43200, 'display' => __('12 Hours','forcefield')),
		'1day'		=> array('interval' => 86400, 'display' => __('1 Day','forcefield')),
		'2days'		=> array('interval' => 86400*2, 'display' => __('2 Days','forcefield')),
		'3days'		=> array('interval' => 86400*3, 'display' => __('3 Days','forcefield')),
		'1week'		=> array('interval' => 86400*7, 'display' => __('1 Week','forcefield')),
		'2weeks'	=> array('interval' => 86400*14, 'display' => __('2 Weeks','forcefield')),
		'1month'	=> array('interval' => 86400*30, 'display' => __('1 Month','forcefield')),
		'2months'	=> array('interval' => 86400*60, 'display' => __('2 Months','forcefield')),
		'3months'	=> array('interval' => 86400*90, 'display' => __('3 Months','forcefield')),
		'6months'	=> array('interval' => 86400*180, 'display' => __('6 Months','forcefield')),
		'1year'		=> array('interval' => 86400*365, 'display' => __('1 year','forcefield')),
	);

	// --- filter and return ---
	// 0.9.6: added expiries filter
	$expiries = apply_filters('forcefield_transient_expiries', $expiries);
	return $expiries;
}

