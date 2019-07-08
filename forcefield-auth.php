<?php

// ========================================
// === ForceField Authentication Module ===
// ========================================

// === Action Tokenizer ===
// - Check for Existing Token
// - Add Token Fields to Forms
// - Add Form Field Abstract
// - AJAX to Create and Return Token
// - Token Output Abstract
// - Create a Token
// - Delete a Token
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


// ------------------------
// === Action Tokenizer ===
// ------------------------

// ------------------------
// Check for Existing Token
// ------------------------
function forcefield_check_token($context, $getexpiry=false) {

	global $forcefield;

	// $debug = true;
	$debug = false;

	// --- validate IP address to IP key ---
	$iptype = forcefield_get_ip_type($forcefield['ip']);
	// 0.9.4: allow for localhost IP type
	if ($iptype == 'localhost') {$ipaddress = str_replace('.', '-', $forcefield['ip']);}
	elseif ($iptype == 'ip4') {$ipaddress = str_replace('.', '-', $forcefield['ip']);}
	elseif ($iptype == 'ip6') {$ipaddress = str_replace(':', '--', $forcefield['ip']);}
	else {return false;}

	// --- get transient token value by token key ---
	$token = array();
	$transientid = $context.'_token_'.$ipaddress;
	if ($debug) {echo "Transient ID: ".$transientid.PHP_EOL;}
	$tokenvalue = get_transient($transientid);
	if ($tokenvalue) {$token['value'] = $tokenvalue;}

	// 0.9.5: maybe return transient expiry time also for consistency
	if ($getexpiry) {
		$timeout = forcefield_get_transient_timeout($transientid);
		if ($timeout) {
			$time = time();
			if ($debug) {
				echo "Current Time: ".$time.PHP_EOL;
				echo "Expiry Time: ".$timeout.PHP_EOL;
			}
			$expiry = $timeout - $time;
			$token['expiry'] = $expiry;
		}
	}
	if ($debug) {echo print_r($token, true);}
	return $token;
}

// -------------------------
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

// -----------------------
// Add Form Field Abstract
// -----------------------
function forcefield_add_field($context) {

	// --- check setting for context ---
	$tokenize = forcefield_get_setting($context.'_token');
	if ($tokenize != 'yes') {return;}

	// --- output tokenizer javascript ---
	// 0.9.3: add input via dynamic javascript instead of hardcoding
	// echo '<input type="hidden" id="auth_token" name="auth_token_'.$context.'" value="" />';
	echo '<span id="dynamic-tokenizer"></span>';
	echo "<script>var tokeninput = document.createElement('input');
	tokeninput.setAttribute('type', 'hidden'); tokeninput.setAttribute('value', '');
	tokeninput.setAttribute('id', 'auth_token_".$context."');
	tokeninput.setAttribute('name', 'auth_token_".$context."');
	document.getElementById('dynamic-tokenizer').appendChild(tokeninput);</script>";

	// --- output tokenizer iframe ---
	$framesrc = admin_url('admin-ajax.php')."?action=forcefield_".$context;
	echo '<iframe style="display:none;" name="auth_frame" id="auth_frame" src="'.$framesrc.'"></iframe>';

}

// -------------------------------
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

// ---------------------
// Token Output Abstract
// ---------------------
function forcefield_output_token($context) {

	$token = forcefield_create_token($context);
	// echo $context.PHP_EOL;
	// var_dump($token); echo PHP_EOL;

	if ($token) {

		// 0.9.3: added some extra javascript obfuscation of token value
		$tokenchars = str_split($token['value'], 1);

		// --- output token characters via script ---
		echo "<script>var bits = new Array(); ";
		foreach ($tokenchars as $i => $char) {echo "bits[".$i."] = '".$char."'; ";}
		echo "bytes = bits.join('');
		parent.document.getElementById('auth_token_".$context."').value = bytes;</script>".PHP_EOL;

		// --- auto-refresh expired tokens ---
		// 0.9.4: add a timer for token auto-refresh
		if (isset($token['expiry'])) {
			$cycle = $token['expiry'] * 1000;
			// TODO: use interval cycler rather than a single timeout ?
			echo "<script>setTimeout(function() {window.location.reload();}, ".$cycle.");</script>";
		}
	} else {echo __('Error. No Token was generated.', 'forcefield');}
	exit;
}

// --------------
// Create a Token
// --------------
function forcefield_create_token($context) {

	global $forcefield;

	// $debug = true;
	$debug = false;

	// --- check token setting for context ----
	$tokenize = forcefield_get_setting($context.'_token');
	if ($debug) {echo "Tokenize? ".$tokenize.PHP_EOL;}
	if ($tokenize != 'yes') {
		if ($debug) {echo "Tokens off for '".$context."'";}
		return false;
	}

	// --- maybe return existing token ---
	// 0.9.5: also check and return token expiry if found
	$token = forcefield_check_token($context, true);
	if (isset($token['value'])) {
		if ($debug) {echo "Existing Token: ".print_r($token,true).PHP_EOL;}
		return $token;
	}

	// --- validate IP address and make IP key ---
	// 0.9.4: allow for localhost IP type
	$iptype = forcefield_get_ip_type($forcefield['ip']);
	if ($iptype == 'localhost') {$ip = str_replace('.', '-', $forcefield['ip']);}
	elseif ($iptype == 'ip4') {$ip = str_replace('.', '-', $forcefield['ip']);}
	elseif ($iptype == 'ip6') {$ip = str_replace(':', '--', $forcefield['ip']);}
	else {
		if ($debug) {echo "No token generated for IP type '".$iptype."'";}
		return false;
	}
	if ($debug) {echo "IP: ".$ip.PHP_EOL;}

	// --- get and set token expiry length ---
	// 0.9.4: added context-specific expiry time filtering
	// 0.9.4: set a bare minimum token usage time
	// 0.9.6: fix to expirytime filter variable
	$expirytime = forcefield_get_setting('blocklist_tokenexpiry');
	$expirytime = apply_filters('blocklist_tokenexpiry_'.$context, $expirytime);
	$expirytime = absint($expirytime);
	if (!is_numeric($expirytime)) {$expirytime = 300;}
	if ($expirytime < 30) {$expirytime = 30;}
	if ( ($context == 'comment') && ($expirytime < 300) ) {$expirytime = 300;}

	// --- create token and set transient ---
	$transientid = $context.'_token_'.$ip;
	$tokenvalue = wp_generate_password(12, false, false);
	set_transient($transientid, $tokenvalue, $expirytime);

	// 0.9.4: return expiry time value also, for auto-refresh
	$token = array('value' => $tokenvalue, 'expiry' => $expirytime);
	$token = apply_filters('forcefield_token', $token, $context);
	return $token;
}

// --------------
// Delete a Token
// --------------
function forcefield_delete_token($context) {
	global $forcefield;

	// --- validate IP address and make IP key ---
	if (filter_var($forcefield['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$ipaddress = str_replace('.', '-', $forcefield['ip']);
	} elseif (filter_var($forcefield['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$ipaddress = str_replace(':', '--', $forcefield['ip']);
	}

	// --- delete token transient ---
	$transientid = $context.'_token_'.$ipaddress;
	delete_transient($transientid);
}


// ----------------------
// === Authentication ===
// ----------------------

// ----------------------
// XML RPC Authentication
// ----------------------
add_filter('authenticate', 'forcefield_xmlrpc_authentication', 9, 3);
function forcefield_xmlrpc_authentication($user, $username, $password) {

	// note: XMLRPC_REQUEST is defined in xmlrpc.php
	if (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) {return $user;}

	// --- filter general error message ---
	$errormessage = forcefield_get_error_message();
	$errormessage = apply_filters('forcefield_error_message_xmlrpc', $errormessage);

	// --- check IP whitelist and blacklist ---
	// 0.9.1: check IP whitelist
	// 0.9.2: check IP blacklist
	if (forcefield_whitelist_check('apis')) {return $user;}
	if (forcefield_blacklist_check('apis')) {forcefield_forbidden_exit();}

	// --- check for authentication block ---
	$authblock = forcefield_get_setting('xmlrpc_authblock');
	$authban = forcefield_get_setting('xmlrpc_authban');

	if ($authban == 'yes') {

		// --- ban this IP for XML RPC authentication violation ---
		forcefield_blocklist_record_ip('xmlrpc_login');
		add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_error_message_banned');
		do_action('xmlrpc_login_banned');
		return forcefield_filtered_error('xmlrpc_ban', $errormessage);

	} elseif ($authblock == 'yes') {

		// --- block this XML RPC attempt ---
		// 0.9.1: record anyway so a changed ban setting can take effect
		forcefield_blocklist_record_ip('xmlrpc_login');
		add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_error_message_blocked');
		do_action('xmlrpc_login_blocked');
		return forcefield_filtered_error('xmlrpc_block', $errormessage);

	} elseif (is_wp_error($user)) {

		// --- record authentication fail ---
		$transgressions = forcefield_blocklist_record_ip('xmlrpc_authfail');
		// 0.9.1: check against XML RPC authentication attempts limit
		$blocked = forcefield_blocklist_check_transgressions('xmlrpc_authfail', $transgressions);
		if ($blocked) {
			do_action('xmlrpc_login_toomany');
			$status = 429; // 'too many requests'
			return forcefield_filtered_error('xmlrpc_toomany', $errormessage, $status);
		}

	}

	// --- check for SSL connection requirement ---
	$requiressl = forcefield_get_setting('xmlrpc_requiressl');
	if ( ($requiressl == 'yes') && !is_user_logged_in() && !is_ssl() ) {
		add_filter('xmlrpc_login_error', 'forcefield_xmlrpc_require_ssl_message');
		// note: we need to return an error here so that the xmlrpc_login_error filter is called
		$errormessage = __('XML RPC requires SSL Connection.','forcefield');
		return forcefield_filtered_error('xmlrpc_ssl_required', $errormessage);
	}

	return $user;
}

// ------------------------------
// XML RPC Error Message (Banned)
// ------------------------------
function forcefield_xmlrpc_error_message_banned($error) {
	$errormessage = __('Access denied. XML RPC authentication is disabled.','forcefield');
	$errormessage = apply_filters('forcefield_error_message_xmlrpc_banned', $errormessage);
	$status = 405; // HTTP 405: Method Not Allowed
	return new IXR_Error($status, $errormessage);
}

// -------------------------------
// XML RPC Error Message (Blocked)
// ------------------------------
function forcefield_xmlrpc_error_message_blocked($error) {
	$errormessage = __('Access denied. XML RPC authentication is disabled.','forcefield');
	$errormessage = apply_filters('forcefield_error_message_xmlrpc_blocked', $errormessage);
	$status = 405; // HTTP 405: Method Not Allowed
	return new IXR_Error($status, $errormessage);
}

// ----------------------------
// XML RPC requires SSL Message
// ----------------------------
function forcefield_xmlrpc_require_ssl_message() {
	$errormessage = __('XML RPC authentication requires an SSL Connection.','forcefield');
	$errormessage = apply_filters('forcefield_error_message_xmlrpc_requiressl', $errormessage);
	$status = 426; // HTTP 426: Upgrade Required
	return new IXR_Error($status, $errormessage);
}

// --------------------------
// Login Token Authentication
// --------------------------
add_filter('authenticate', 'forcefield_login_validate', 11, 3);
function forcefield_login_validate($user, $username, $password) {

	// --- filter general error message ---
	$errormessage = forcefield_get_error_message();
	$errormessage = apply_filters('forcefield_error_message_login', $errormessage);

	// --- check whitelist and blacklist ---
	// 0.9.1: check IP whitelist
	// 0.9.2: check IP blacklist
	if (forcefield_whitelist_check('actions')) {return $user;}
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// --- recheck failed user ---
	// 0.9.1: for a failed login, check if an admin account
	if ( $username && $password && is_wp_error($user) ) {

		$checkuser = get_user_by('login', $username);

		// --- check for super admin role ---
		// 0.9.6: add check for super admin
		if (is_multisite() && is_super_admin($checkuser->ID)) {

			// --- add a record of failed super admin login attempt ---
			$transgressions = forcefield_blocklist_record_ip('super_fail');
			$blocked = forcefield_blocklist_check_transgressions('super_fail', $transgressions);
			if ($blocked) {
				do_action('forcefield_login_super_toomany');
				$status = 429; // HTTP 429: Too Many Requests
				return forcefield_filtered_error('login_super_toomany', $errormessage, $status);
			}
		}

		// --- check for admin role ---
		if (in_array('administrator', (array)$checkuser->roles)) {

			// --- add a record of failed admin login attempt ---
			$transgressions = forcefield_blocklist_record_ip('admin_fail');
			$blocked = forcefield_blocklist_check_transgressions('admin_fail', $transgressions);
			if ($blocked) {
				do_action('forcefield_login_admin_toomany');
				$status = 429; // HTTP 429: Too Many Requests
				return forcefield_filtered_error('login_admin_toomany', $errormessage, $status);
			}
		}

		// TODO: check other significant roles (editor ?)

	}

	// --- return for existing errors ---
    if ( !$username || !$password || is_wp_error($user) ) {return $user;}

	// --- maybe require SSL connection to login ---
	$requiressl = forcefield_get_setting('login_requiressl');
	if ( ($requiressl == 'yes') && !is_user_logged_in() && !is_ssl()) {
		add_filter('secure_auth_redirect', '__return_true');
		auth_redirect(); exit;
	}

	// --- check for empty referer field ---
	if ($_SERVER['HTTP_REFERER'] == '') {

		do_action('forcefield_login_noreferer');
		do_action('forcefield_no_referer');

		// --- check ban setting ---
		// 0.9.1: separate general no referer recording
		$norefban = forcefield_get_setting('blocklist_norefban');
		if ($norefban == 'yes') {
			$transgressions = forcefield_blocklist_record_ip('no_referer');
			$blocked = forcefield_blocklist_check_transgressions('no_referer', $transgressions);
			if ($blocked) {$block = true;}
		}

		// --- check block setting ---
		$norefblock = forcefield_get_setting('login_norefblock');
		if ($norefblock == 'yes') {$block = true;}

		if (isset($block) && $block) {
			do_action('forcefield_login_failed');
			$status = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('login_no_referer', $errormessage, $status);
		}
	}

	// --- login form field to check token ---
	if (isset($POST['log']) && isset($_POST['pwd'])) {

		$tokenize = forcefield_get_setting('login_token');
		if ($tokenize != 'yes') {return $user;}

		// --- maybe record the IP if missing the token form field ---
		if (!isset($_POST['auth_token'])) {

			// --- record no token ---
			// 0.9.1: separate instaban and no token recording
			$recordnotoken = forcefield_get_setting('blocklist_notoken');
			if ($recordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}

			// --- check ban setting ---
			$instaban = forcefield_get_setting('login_notokenban');
			if ($instaban == 'yes') {forcefield_blocklist_record_ip('no_login_token');}

			do_action('forcefield_login_notoken');
			do_action('forcefield_login_failed');
			$status = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('login_token_missing', $errormessage, $status);

		} else {

			// --- token provided so clear old records ---
			// 0.9.1: maybe clear no token records
			forcefield_delete_record(false, 'no_token');
			forcefield_delete_record(false, 'no_login_token');

		}

		$authtoken = $_POST['auth_token'];
		$checktoken = forcefield_check_token('login');

		// --- check token ---
		// 0.9.5: check now returns an array so we check 'value' key
		if (!$checktoken) {

			// --- token expired ---
			do_action('forcefield_login_oldtoken');
			do_action('forcefield_login_failed');
			$status = 403; // HTTP 403: Forbidden
			return forcefield_filtered_error('login_token_expired', $errormessage, $status);

		} elseif ($authtoken != $checktoken['value']) {

			// --- fail, token is a mismatch ---
			// 0.9.1: record general token usage failure
			$recordbadtokens = forcefield_get_setting('blocklist_badtoken');
			if ($recordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}

			do_action('forcefield_login_mismatch');
			do_action('forcefield_login_failed');
			$status = 401; // HTTP 401: Unauthorized
			return forcefield_filtered_error('login_token_mismatch', $errormessage, $status);

		} else {

			// --- success, allow user to login ---
			// 0.9.1: clear any bad token records
			forcefield_blocklist_delete_record(false, 'bad_token');

			// --- remove used login token ---
			forcefield_delete_token('login');
			do_action('forcefield_login_success');

		}
	}

	// --- clear admin fail records ---
	// 0.9.1: clear possible admin_fail records on successful login
	if (!is_wp_error($user)) {

		// 0.9.6: get user object via username
		$checkuser = get_user_by('login', $username);

		// 0.9.6: also clear super admin fail records
		if (is_multisite() && is_super_admin($checkuser->ID)) {
			forcefield_blocklist_delete_record(false, 'super_fail');
		}
		if (in_array('administrator', (array)$checkuser->roles)) {
			forcefield_blocklist_delete_record(false, 'admin_fail');
		}
		// TODO: do similar for other significant roles (editor?)
	}

	return $user;
}

// ---------------------------------
// Registration Token Authentication
// ---------------------------------
add_filter('register_post', 'forcefield_registration_authenticate', 9, 3);
function forcefield_registration_authenticate($errors, $sanitized_user_login, $user_email) {

	// --- filtered general error message ---
	$errormessage = forcefield_get_error_message();
	$errormessage = apply_filters('forcefield_error_message_register', $errormessage);

	// --- check IP whitelist and blacklist ---
	// 0.9.2: check IP blacklist
	if (forcefield_whitelist_check('actions')) {return $errors;}
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// --- maybe require SSL connection for registration ---
	$requiressl = forcefield_get_setting('register_requiressl');
	if ( ($requiressl == 'yes') && !is_ssl()) {
		// note: compressed version of auth_redirect function
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// --- check for empty referer field ---
	if ($_SERVER['HTTP_REFERER'] == '') {

		do_action('forcefield_register_noreferer');
		do_action('forcefield_no_referer');

		// --- record no referer ---
		// 0.9.1: separate general no referer recording
		$norefban = forcefield_get_setting('blocklist_norefban');
		if ($norefban == 'yes') {
			$transgressions = forcefield_blocklist_record_ip('no_referer');
			$blocked = forcefield_blocklist_check_transgressions('no_referer', $transgressions);
			if ($blocked) {$block = true;}
		}

		// --- check block setting ---
		$norefblock = forcefield_get_setting('register_norefblock');
		if ($norefblock == 'yes') {$block = true;}

		if (isset($block) && $block) {
			do_action('forcefield_register_failed');
			$status = 400; // HTTP 400: Bad Request
			// 0.9.5: fix to undefined error string
			return forcefield_filtered_error('register_no_referer', $errormessage, $status);
		}
	}

	// --- check tokenizer setting ---
    $tokenize = forcefield_get_setting('register_token');
	if ($tokenize != 'yes') {return $errors;}

	// --- check the token field ---
	if (!isset($_POST['auth_token'])) {

		// --- record no token ---
		// 0.9.1: separate token and register token recording
		$recordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($recordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}

		// --- check ban setting ---
		$instaban = forcefield_get_setting('register_notokenban');
		if ($instaban == 'yes') {forcefield_blocklist_record_ip('no_register_token');}

		do_action('forcefield_register_notoken');
		do_action('forcefield_register_failed');
		$status = 400; // HTTP 400: Bad Request
		// 0.9.5: fix to undefined error string
		return forcefield_filtered_error('register_token_missing', $errormessage, $status, $errors);

	} else {
		// --- token present, clear old records ---
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_register_token');
	}

	$authtoken = $_POST['auth_token'];
	$checktoken = forcefield_check_token('register');

	// --- check token ---
	// 0.9.5: check now returns an array so we check 'value' key
	if (!$checktoken) {

		// --- token expired ---
		do_action('forcefield_register_oldtoken');
		do_action('forcefield_register_failed');
		$status = 403; // HTTP 403: Forbidden
		return forcefield_filtered_error('register_token_expired', $errormessage, $status, $errors);

	} elseif ($authtoken != $checktoken['value']) {

		// --- fail, token is a mismatch ---
		// 0.9.1: record general token usage failure
		$recordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($recordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}

		do_action('forcefield_register_mismatch');
		do_action('forcefield_register_failed');
		$status = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('register_token_mismatch', $errormessage, $status, $errors);

	} else {

		// --- clear bad token records ---
		// 0.9.1: clear any bad token records
		forcefield_blocklist_delete_record(false, 'bad_token');

		// --- remove used register token ---
		forcefield_delete_token('register');
		do_action('forcefield_register_success');

	}

    return $errors;
}

// ------------------------
// Blog Signup Authenticate
// ------------------------
add_filter('wpmu_validate_user_signup', 'forcefield_signup_authenticate');
function forcefield_signup_authenticate($results) {

	// --- set any existing errors ---
	$errors = $results['errors'];

	// --- filtered general error message ---
	$errormessage = forcefield_get_error_message();
	$errormessage = apply_filters('forcefield_error_message_signup', $errormessage);

	// --- check IP whitelist and blacklist ---
	// 0.9.2: check IP blacklist
	if (forcefield_whitelist_check('actions')) {return $results;}
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// ? maybe allow signup for already logged in users ?
	// if ( is_user_logged_in() && is_admin() && !defined('DOING_AJAX') ) {return $results;}

	// --- maybe require SSL connection for blog signup ---
	$requiressl = forcefield_get_setting('signup_requiressl');
	if ( ($requiressl == 'yes') && !is_ssl()) {
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// --- check for empty referer field ---
	if ($_SERVER['HTTP_REFERER'] == '') {

		do_action('forcefield_signup_noreferer');
		do_action('forcefield_no_referer');

		// --- check no referer ban ---
		// 0.9.1: separate general no referer recording
		$norefban = forcefield_get_setting('blocklist_norefban');
		if ($norefban == 'yes') {
			$transgressions = forcefield_blocklist_record_ip('no_referer');
			$blocked = forcefield_blocklist_check_transgressions('no_referer', $transgressions);
			if ($blocked) {$block = true;}
		}

		// --- check no referer block ---
		$norefblock = forcefield_get_setting('signup_norefblock');
		if ($norefblock == 'yes') {$block = true;}

		if (isset($block) && $block) {
			do_action('forcefield_signup_failed');
			$status = 400; // HTTP 400: Bad Request
			$results['errors'] = forcefield_filtered_error('signup_no_referer', $errormessage, $status, $errors);
			return $results;
		}
	}

	// --- check tokenizer setting ---
	$tokenize = forcefield_get_setting('signup_token');
	if ($tokenize != 'yes') {return $results;}

	// --- maybe ban the IP if missing the token form field ---
	if (!isset($_POST['auth_token'])) {

		// --- record no token ---
		$recordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($recordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}

		// --- check no token ban ----
		$instaban = forcefield_get_setting('signup_notokenban');
		if ($instaban == 'yes') {forcefield_blocklist_record_ip('no_signup_token');}

		do_action('forcefield_signup_notoken');
		do_action('forcefield_signup_failed');
		$status = 400; // HTTP 400: Bad Request
		$results['errors'] = forcefield_filtered_error('signup_token_missing', $errormessage, $status, $errors);
		return $results;

	} else {

		// --- delete old records ---
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_signup_token');

	}

	$authtoken = $_POST['auth_token'];
	$checktoken = forcefield_check_token('signup');

	// --- check token ---
	// 0.9.5: check now returns an array so we check 'value' key
	if (!$checktoken) {

		// --- token expired ---
		do_action('forcefield_signup_oldtoken');
		do_action('forcefield_signup_failed');
		$results['errors'] = forcefield_filtered_error('signup_token_expired', $errormessage, false, $errors);
		return $results;

	} elseif ($authtoken != $checktoken['value']) {

		// --- fail, register token is a mismatch ---
		// 0.9.1: record general token usage failure
		$recordbadtokens = forcefield_get_setting('blocklist_badtoken');
		if ($recordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}

		do_action('forcefield_signup_mismatch');
		do_action('forcefield_signup_failed');
		$status = 401; // HTTP 401: Unauthorized
		$results['errors'] = forcefield_filtered_error('signup_token_mismatch', $errormessage, $status, $errors);
		return $results;

	} else {

		// --- success, allow the user to signup --
		// 0.9.1: clear any bad token records
		forcefield_blocklist_delete_record(false, 'bad_token');

		// --- remove used signup token ---
		forcefield_delete_token('signup');
		do_action('forcefield_signup_success');
	}

	return $results;
}

// ----------------------------------
// Lost Password Token Authentication
// ----------------------------------
add_action('allow_password_reset', 'forcefield_lost_password_authenticate', 21, 1);
function forcefield_lost_password_authenticate($allow) {

	// --- filter general error message ---
	$errormessage = forcefield_get_error_message();
	$errormessage = apply_filters('forcefield_error_message_lostpassword', $errormessage);

	// --- check IP whitelist and blacklist ---
	// 0.9.1: check IP whitelist
	// 0.9.2: check IP blacklist
	if (forcefield_whitelist_check('actions')) {return $allow;}
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// --- maybe require SSL connection for lost password ---
	$requiressl = forcefield_get_setting('lostpass_requiressl');
	if ( ($requiressl == 'yes') && !is_ssl()) {
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// --- check for empty referer field ---
	if ($_SERVER['HTTP_REFERER'] == '') {

		do_action('forcefield_lostpass_noreferer');
		do_action('forcefield_no_referer');

		// --- no referer ban ---
		// 0.9.1: separate general no referer recording
		$norefban = forcefield_get_setting('blocklist_norefban');
		if ($norefban == 'yes') {
			$transgressions = forcefield_blocklist_record_ip('no_referer');
			$blocked = forcefield_blocklist_check_transgressions('no_referer', $transgressions);
			if ($blocked) {$block = true;}
		}

		// --- no referer block ---
		$norefblock = forcefield_get_setting('lostpass_norefblock');
		if ($norefblock == 'yes') {$block = true;}

		if (isset($block) && $block) {
			do_action('forcefield_lostpass_failed');
			$status = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('lostpass_no_referer', $errormessage, $status);
		}
	}

	// --- check tokenizer setting ---
	$tokenize = forcefield_get_setting('lostpass_token');
	if ($tokenize != 'yes') {return $allow;}

	// --- maybe ban the IP if missing the token form field ---
	if (!isset($_POST['auth_token'])) {

		// --- record no token ---
		$recordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($recordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}

		// --- no token ban ---
		$instaban = forcefield_get_setting('lostpass_notokenban');
		if ($instaban == 'yes') {forcefield_blocklist_record_ip('no_lostpass_token');}

		do_action('forcefield_lostpass_notoken');
		do_action('forcefield_lostpass_failed');
		$status = 400; // HTTP 400: Bad Request
		return forcefield_filtered_error('lostpass_token_missing', $errormessage, $status);

	} else {
		// --- remove old records ---
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_lostpass_token');

	}

	$authtoken = $_POST['auth_token'];
	$checktoken = forcefield_check_token('lostpass');

	// --- check token ---
	// 0.9.5: check now returns an array so we check 'value' key
	if (!$checktoken) {

		// --- token expired ---
		do_action('forcefield_lostpass_oldtoken');
		do_action('forcefield_lostpass_failed');
		return forcefield_filtered_error('lostpass_token_expired', $errormessage);

	} elseif ($authtoken != $checktoken['value']) {

		// --- fail, lost password token is a mismatch ---
		// 0.9.1: record general token usage failure
		$recordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($recordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}

		do_action('forcefield_lostpass_mismatch');
		do_action('forcefield_lostpass_failed');
		$status = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('lostpass_token_mismatch', $errormessage, $status);

	} else {

		// --- success, allow the user to send reset email ---
		// 0.9.1: clear any bad token records
		blocklist_delete_record(false, 'bad_token');
		// remove used lost password token
		forcefield_delete_token('lostpass');
		do_action('forcefield_lostpass_success');

	}

	return $allow;
}

// -----------------------
// Commenting Authenticate
// -----------------------
add_filter('preprocess_comment', 'forcefield_preprocess_comment');
function forcefield_preprocess_comment($comment) {

	// --- filter general error message ---
	$errormessage = forcefield_get_error_message();
	$errormessage = apply_filters('forcefield_error_message_comment', $errormessage);

	// --- skip checks for those with comment moderation permission ---
	if (current_user_can('moderate_comments')) {return $comment;}

	// --- check IP whitelist and blacklist ---
	// 0.9.1: checks IP whitelist
	// 0.9.2: check IP blacklist
	if (forcefield_whitelist_check('actions')) {return $comment;}
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// --- maybe require SSL connection for commenting ---
	$requiressl = forcefield_get_setting('comment_requiressl');
	if ( ($requiressl == 'yes') && !is_ssl()) {
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// --- check for empty referer field ---
	if ($_SERVER['HTTP_REFERER'] == '') {

		do_action('forcefield_comment_noreferer');
		do_action('forcefield_no_referer');

		// --- check no referer ban ---
		// 0.9.1: separate general no referer recording
		$norefban = forcefield_get_setting('blocklist_norefban');
		if ($norefban == 'yes') {
			$transgressions = forcefield_blocklist_record_ip('no_referer');
			$blocked = forcefield_blocklist_check_transgressions('no_referer', $transgressions);
			if ($blocked) {$block = true;}
		}

		// --- check no referer block ---
		$norefblock = forcefield_get_setting('comment_norefblock');
		if ($norefblock == 'yes') {$block = true;}

		if (isset($block) && $block) {
			do_action('forcefield_comment_failed');
			$status = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('comment_no_referer', $errormessage, $status);
		}
	}

	// --- check tokenizer setting ---
	$tokenize = forcefield_get_setting('comment_token');
	if ($tokenize != 'yes') {return $comment;}

	// --- maybe ban the IP if missing the token form field ---
	if (!isset($_POST['auth_token'])) {

		// --- record no token ---
		$recordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($recordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}

		// --- no token ban ---
		$instaban = forcefield_get_setting('comment_notokenban');
		if ($instaban == 'yes') {forcefield_blocklist_record_ip('no_comment_token');}

		do_action('forcefield_comment_notoken');
		do_action('forcefield_comment_failed');
		$status = 400; // HTTP 400: Bad Request
		return forcefield_filtered_error('comment_token_missing', $errormessage, $status);

	} else {

		// --- delete old no token records ---
		// 0.9.1: maybe clear no token records
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_comment_token');
	}

	// --- check token ---
	$authtoken = $_POST['auth_token'];
	$checktoken = forcefield_check_token('lostpass');
	// 0.9.5: check now returns an array so we check 'value' key
	if (!$checktoken) {

		// --- token expired ---
		do_action('forcefield_comment_oldtoken');
		do_action('forcefield_comment_failed');
		return forcefield_filtered_error('comment_token_expired', $errormessage);

	} elseif ($authtoken != $checktoken['value']) {

		// --- fail, comment token is a mismatch ---
		// 0.9.1: record general token usage failure
		$recordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($recordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}

		do_action('forcefield_comment_mismatch');
		do_action('forcefield_comment_failed');
		$status = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('comment_token_mismatch', $errormessage, $status);

	} else {

		// --- success, allow the user to comment ---
		// 0.9.1: clear any bad token records
		blocklist_delete_record(false, 'bad_token');

		// --- remove used comment token ---
		forcefield_delete_token('comment');
		do_action('forcefield_comment_success');
	}

	return $comment;
}

// -------------------------------
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

// ------------------------------------
// BuddyPress Registration Authenticate
// ------------------------------------
function forcefield_buddypress_authenticate() {

	// --- make sure we are on the BuddyPress registration page ---
    if (!function_exists('bp_is_current_component') || !bp_is_current_component('register')) {return;}

	// --- filtered general error message ---
	$errormessage = forcefield_get_error_message();
	$errormessage = apply_filters('forcefield_error_message_buddypress', $errormessage);

	// --- check IP whitelist and blacklist ---
	// 0.9.2: check IP blacklist
	if (forcefield_whitelist_check('actions')) {return false;}
	if (forcefield_blacklist_check('actions')) {forcefield_forbidden_exit();}

	// --- maybe require SSL connection for registration ---
	$requiressl = forcefield_get_setting('buddypress_requiressl');
	if ( ($requiressl == 'yes') && !is_ssl()) {
		if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
			wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
		} else {wp_redirect( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);}
		exit;
	}

	// --- check for empty referer field ---
	if ($_SERVER['HTTP_REFERER'] == '') {

		do_action('forcefield_buddypress_noreferer');
		do_action('forcefield_no_referer');

		// --- check no referer ban ---
		$norefban = forcefield_get_setting('blocklist_norefban');
		if ($norefban == 'yes') {
			$transgressions = forcefield_blocklist_record_ip('no_referer');
			$blocked = forcefield_blocklist_check_transgressions('no_referer', $transgressions);
			if ($blocked) {$block = true;}
		}

		// --- check no referer block ---
		$norefblock = forcefield_get_setting('buddypress_norefblock');
		if ($norefblock == 'yes') {$block = true;}

		if (isset($block) && $block) {
			do_action('forcefield_buddypress_failed');
			$status = 400; // HTTP 400: Bad Request
			return forcefield_filtered_error('buddypress_no_referer', $errormessage, $status);
		}
	}

	// --- check tokenizer setting ---
    $tokenize = forcefield_get_setting('buddypress_token');
	if ($tokenize != 'yes') {return $errors;}

	// --- maybe ban the IP if missing the token form field ---
	if (!isset($_POST['auth_token'])) {

		// --- record no token ---
		$recordnotoken = forcefield_get_setting('blocklist_notoken');
		if ($recordnotoken == 'yes') {forcefield_blocklist_record_ip('no_token');}

		// --- no token ban ----
		$instaban = forcefield_get_setting('buddypress_notokenban');
		if ($instaban == 'yes') {forcefield_blocklist_record_ip('no_buddypress_token');}

		do_action('forcefield_buddypress_notoken');
		do_action('forcefield_buddypress_failed');
		$status = 400; // HTTP 400: Bad Request
		return forcefield_filtered_error('buddypress_token_missing', $errormessage, $status, $errors);

	} else {

		// --- maybe clear no token records ---
		forcefield_delete_record(false, 'no_token');
		forcefield_delete_record(false, 'no_buddypress_token');

	}

	// --- check token ---
	$authtoken = $_POST['auth_token'];
	$checktoken = forcefield_check_token('buddypress');
	// 0.9.5: check now returns an array so we check 'value' key
	if (!$checktoken) {

		// --- token expired ---
		do_action('forcefield_buddypress_oldtoken');
		do_action('forcefield_buddypress_failed');
		$status = 403; // HTTP 403: Forbidden
		return forcefield_filtered_error('buddypress_token_expired', $errormessage, $status, $errors);

	} elseif ($authtoken != $checktoken['value']) {

		// --- fail, token is a mismatch ---
		$recordbadtokens = forcefield_get_setting('blocklist_badtokenban');
		if ($recordbadtokens == 'yes') {forcefield_blocklist_record_ip('bad_token');}
		do_action('forcefield_buddypress_mismatch');
		do_action('forcefield_buddypress_failed');
		$status = 401; // HTTP 401: Unauthorized
		return forcefield_filtered_error('buddypress_token_mismatch', $errormessage, $status, $errors);

	} else {

		// --- clear any bad token records ---
		forcefield_blocklist_delete_record(false, 'bad_token');

		// --- remove used register token ---
		forcefield_delete_token('buddypress');
		do_action('forcefield_buddypress_success');
	}

    return $errors;
}
