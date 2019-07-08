<?php

// ========================
// === ForceField Admin ===
// ========================

// Development TODOs
// -----------------
// * change admin_autodelete to admin_blockaction selection!
// + add other role actions / whitelists (super-admin? editor?)
// ? move / remove plugin auto-update options ?

function forcefield_admin_page() {

	global $forcefield, $wordquestplugins;
	$settings = $forcefield;

	// --- manual debug for settings ---
	echo "<!-- ForceField Settings: ".PHP_EOL.print_r($forcefield,true).PHP_EOL." -->";

	// --- get current setting tab ---
	$currenttab = $forcefield['current_tab'];
	if ($currenttab == '') {$currenttab = 'general';}

	// --- get all user roles ---
	$roles = wp_roles()->get_names();

	// --- get default limits ----
	$limits = forcefield_blocklist_get_default_limits();

	// --- get intervals ---
	$intervals = forcefield_get_intervals();

	// --- start page wrap ---
	echo '<div id="pagewrap" class="wrap" style="width:100%;margin-right:0px !important;">'.PHP_EOL;

	// --- Call Plugin Sidebar ---
	$args = array($settings['slug'], 'yes');
	if (function_exists('wqhelper_sidebar_floatbox')) {
		wqhelper_sidebar_floatbox($args);
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

	// --- admin notices boxer ---
	if (function_exists('wqhelper_admin_notice_boxer')) {wqhelper_admin_notice_boxer();} else {echo "<h2> </h2>";}

	// --- Plugin Page Header ---
	// 0.9.6: use standard Plugin Page Header from Loader
	forcefield_settings_header();

	// --- admin page styles ---
	echo "<style>.pagelink {text-decoration:none;} .pagelink:hover {text-decoration:underline;}
	.checkbox-cell {max-width:40px !important;}
	.valigntop {vertical-align:top;}
	.role-box {display:inline-block; float:left; margin-right:20px;}
	.role-cell {max-width:350px;}
	.tab-button {width:100px; height:30px; background-color:#DDDDDD;
		border:1px solid #000; text-align:center;}
	.tab-button:hover {background-color:#EEEEEE; font-weight:bold;}</style>";

	// --- admin page scripts ---
	$adminajax = admin_url('admin-ajax.php');
	$confirmreset = __('Are you sure you want to Reset to Default Settings?','forcefield');
	$confirmclear = __('Are you sure you want to Clear the Entire IP Blocklist?','forcefield');
	// 0.9.6: change to -delete action suffix (-unblock is now for user)
	$unblocknonce = wp_create_nonce('forcefield-delete');

	echo "<script>function showtab(tab) {
		document.getElementById('current-tab').value = tab;
		console.log(document.getElementById('current-tab').value);
		document.getElementById('general').style.display = 'none';
		document.getElementById('role-protect').style.display = 'none';
		document.getElementById('user-actions').style.display = 'none';
		document.getElementById('api-access').style.display = 'none';
		/* document.getElementById('auto-updates').style.display = 'none'; */
		document.getElementById('ip-blocklist').style.display = 'none';
		document.getElementById(tab).style.display = '';
		document.getElementById('general-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('role-protect-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('user-actions-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('api-access-button').style.backgroundColor = '#DDDDDD';
		/* document.getElementById('auto-updates-button').style.backgroundColor = '#DDDDDD'; */
		document.getElementById('ip-blocklist-button').style.backgroundColor = '#DDDDDD';
		document.getElementById(tab+'-button').style.backgroundColor = '#F0F0F0';
		if (tab == 'ip-blocklist') {
			document.getElementById('update-buttons').style.display = 'none';
		} else {document.getElementById('update-buttons').style.display = '';}
	}
	function resettodefaults() {
		var ask = '".$confirmreset."'; agree = confirm(ask); if (!agree) {return false;}
		document.getElementById('forcefield-update-action').value = 'reset';
		document.getElementById('forcefield-update-form').submit();
	}
	function confirmblocklistclear() {
		var ask = '".$confirmclear."'; agree = confirm(ask); if (!agree) {return false;}
	}
	function unblockip(ip,label) {
		ip = encodeURIComponent(ip);
		unblockurl = '".$adminajax."?action=forcefield_unblock_ip&_wpnonce=".$unblocknonce."&ip='+ip;
		if (label != '') {unblockurl += label;}
		document.getElementById('blocklist-action-frame').src = unblockurl;
	}</script>";

	// --- open wrap box ---
	echo "<div id='wrapbox' class='postbox' style='width:680px;line-height:2em;'>".PHP_EOL;
	echo "<div class='inner' style='padding-left:20px;'>".PHP_EOL;

	// --- display server and client IP addresses ---
	// 0.9.3: show current IP addresses
	$serverip = forcefield_get_server_ip(); $clientip = forcefield_get_remote_ip(true);
	echo '<table width="100%"><tr><td width="50%"><b>'.__('Server IP (Host)','forcefield').'</b>: '.$serverip.'</td>';
	echo '<td width="50%"><b>'.__('Client IP (You)','forcefield').'</b>: '.$clientip.'</td></tr></table>';

	// --- settings tab selector buttons ---
	echo '<ul style="list-style:none;">'.PHP_EOL;

		// --- General ---
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'general\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="general-button" class="tab-button"';
			if ($currenttab == 'general') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('General','forcefield').'</div></a></li>'.PHP_EOL;

		// --- User Actions ---
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'user-actions\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="user-actions-button" class="tab-button"';
			if ($currenttab == 'user-actions') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('User Actions','forcefield').'</div></a></li>'.PHP_EOL;

		// --- Role Protect ---
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'role-protect\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="role-protect-button" class="tab-button"';
			if ($currenttab == 'role-protect') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('Role Protect','forcefield').'</div></a></li>'.PHP_EOL;

		// --- API Access ---
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'api-access\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="api-access-button" class="tab-button"';
			if ($currenttab == 'api-access') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('API Access','forcefield').'</div></a></li>'.PHP_EOL;

		// --- Auto Updates ---
		// 0.9.6: removed auto updates page display
		// echo '<li style="display:inline-block;">'.PHP_EOL;
		// echo '<a href="javascript:void(0);" onclick="showtab(\'auto-updates\');" style="text-decoration:none;">'.PHP_EOL;
		// echo '<div id="auto-updates-button" class="tab-button"';
		//	if ($currenttab == 'auto-updates') {echo ' style="background-color:#F0F0F0;"';}
		// echo '>'.__('Auto Updates','forcefield').'</div></a></li>'.PHP_EOL;

		// --- IP Blocklist ---
		echo '<li style="display:inline-block;">'.PHP_EOL;
		echo '<a href="javascript:void(0);" onclick="showtab(\'ip-blocklist\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="ip-blocklist-button" class="tab-button"';
			if ($currenttab == 'ip-blocklist') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('IP Blocklist','forcefield').'</div></a></li>'.PHP_EOL;

	echo '</ul>';

	// --- start settings update form ---
	// 0.9.6: added IDs for form and action elements (for settings reset)
	echo '<form method="post" id="forcefield-update-form">'.PHP_EOL;
	echo '<input type="hidden" id="forcefield-update-action" name="'.$settings['namespace'].'_update_settings" value="yes">'.PHP_EOL;
	echo '<input type="hidden" id="current-tab" name="ff_current_tab" value="'.$currenttab.'">'.PHP_EOL;

	// --- add nonce field ---
	// 0.9.6: changed nonce ID from forcefield_update (for loader)
	wp_nonce_field($settings['slug']);

		// =======
		// General
		// =======
		if ($currenttab != 'general') {$hide = ' style="display:none;"';} else {$hide = '';}
		echo '<div id="general"'.$hide.'><table>';

			// --- IP Lists ---
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">'.__('Manual IP Lists','forcefield').'</h3></td></tr>';

			// --- IP Whitelist (textarea) ---
			$ipwhitelist = forcefield_get_setting('blocklist_whitelist', false);
			if (is_array($ipwhitelist)) {$ipwhitelist = implode("\n", $ipwhitelist);} else {$ipwhitelist = '';}
			echo '<tr><td class="valigntop"><b>'.__('Manual IP Whitelist','forcefield').'</b></td><td width="20"></td>';
			echo '<td colspan="3"><textarea class="ip-textarea" rows="4" name="ff_blocklist_whitelist">'.$ipwhitelist.'</textarea></td>';
			echo '<td></td><td>'.__('comma and/or line separated','forcefield').'</td></tr>';

			// --- IP Blacklist (textarea) ---
			echo '<tr height="10"><td> </td></tr>';
			$ipblacklist = forcefield_get_setting('blocklist_blacklist', false);
			if (is_array($ipblacklist)) {$ipblacklist = implode("\n", $ipblacklist);} else {$ipblacklist = '';}
			echo '<tr><td class="valigntop"><b>'.__('Manual IP Blacklist','forcefield').'</b></td><td width="20"></td>';
			echo '<td colspan="3"><textarea class="ip-textarea" rows="4" name="ff_blocklist_blacklist">'.$ipblacklist.'</textarea></td>';
			echo '<td></td><td>'.__('comma and/or line separated','forcefield').'</td></tr>';

			// --- Blocklist Expiries ---
			echo '<tr height="10"><td> </td></tr>';
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">'.__('Blocklist Expiries','forcefield').'</h3></td></tr>';

			// --- token expiry time ---
			echo '<tr><td><b>'.__('Action Token Expiry','forcefield').'</b></td><td width="20"></td>';
				$expiry = forcefield_get_setting('blocklist_tokenexpiry', false);
			echo '<td><input style="width:50px;" type="number" name="ff_blocklist_tokenexpiry" min="1" value="'.$expiry.'"></td>';
			echo '<td width="10"></td>';
			echo '<td>'.__('Length of time that action tokens are valid.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('default','forcefield').': 300)</td></tr>';

			// --- blocklist expiry time (blocklist_cooldown) ---
			echo '<tr><td class="valigntop"><b>'.__('Block Cooldown Time','forcefield').'</b></td>';
			echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_cooldown">';
			echo '<option value="none">'.__('None','forcefield').'</option>';
			$cooldown = forcefield_get_setting('blocklist_cooldown', false);
			foreach ($intervals as $key => $interval) {
				if ($cooldown == $key) {$selected = ' selected="selected"';} else {$selected = '';}
				echo '<option value="'.$key.'"'.$selected.'>'.$interval['display'].'</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo __('How often trangressions are reduced over time.','forcefield').'</div></td></tr>';

			// --- blocklist expiry time (blocklist_expiry) ---
			echo '<tr><td class="valigntop"><b>'.__('Block Expiry Time','forcefield').'</b></td>';
			echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_expiry">';
			echo '<option value="none">'.__('None','forcefield').'</option>';
			$expiry = forcefield_get_setting('blocklist_expiry', false);
			foreach ($intervals as $key => $interval) {
				if ($expiry == $key) {$selected = ' selected="selected"';} else {$selected = '';}
				echo '<option value="'.$key.'"'.$selected.'>'.$interval['display'].'</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo __('How long before an IP block expires.','forcefield').'</div></td></tr>';

			// --- blocklist delete time (blocklist_delete) ---
			echo '<tr><td class="valigntop"><b>'.__('Block Delete Time','forcefield').'</b></td>';
			echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_delete">';
			echo '<option value="none">'.__('None','forcefield').'</option>';
			$delete = forcefield_get_setting('blocklist_delete', false);
			foreach ($intervals as $key => $interval) {
				if ($delete == $key) {$selected = ' selected="selected"';} else {$selected = '';}
				echo '<option value="'.$key.'"'.$selected.'>'.$interval['display'].'</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo __('How long before an IP record is deleted.','forcefield').'</div></td></tr>';

			// --- blocklist cleanup frequency (blocklist_cleanups) ---
			echo '<tr><td class="valigntop"><b>'.__('CRON Cleanups','forcefield').'</b></td>';
			echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_cleanups">';
			echo '<option value="none">'.__('None','forcefield').'</option>';
			$cleanup = forcefield_get_setting('blocklist_cleanups', false);
			foreach ($intervals as $key => $interval) {
				if ($cleanup == $key) {$selected = ' selected="selected"';} else {$selected = '';}
				echo '<option value="'.$key.'"'.$selected.'>'.$interval['display'].'</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo __('How often blocklist cleanups are scheduled.','forcefield').'</div></td></tr>';

		// --- close general options tab ---
		echo '</table></div>';


		// ============
		// User Actions
		// ============
		if ($currenttab != 'user-actions') {$hide = ' style="display:none;"';} else {$hide = '';}
		echo '<div id="user-actions"'.$hide.'><table>';

			// -------------------
			// User Action Options
			// -------------------
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">'.__('User Action Options','forcefield').'</h3></td></tr>';

			// --- missing token records (blocklist_notoken) ---
			echo '<tr><td><b>'.__('Record Missing Tokens?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_notoken" value="yes"';
				if (forcefield_get_setting('blocklist_notoken', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Record IP of User Actions Missing Tokens.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- missing token limit (no_token) ---
			echo '<tr><td><b>'.__('Missing Token Limit?','forcefield').'</b></td><td width="20"></td>';
				$limit = forcefield_get_setting('limit_no_token', false);
			echo '<td><input style="width:40px;" type="number" name="ff_limit_no_token" value="'.$limit.'"></td>';
			echo '<td width="10"></td>';
			echo '<td>'.__('No Referer Transgressions before IP Ban.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('default','forcefield').': '.$limits['no_token'].')</td></tr>';

			// --- missing token records (blocklist_badtoken) ---
			echo '<tr><td><b>'.__('Record Bad Tokens?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_badtoken" value="yes"';
				if (forcefield_get_setting('blocklist_badtoken', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Record IP of User Actions with Bad Tokens.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- bad token limit (bad_token) ---
			echo '<tr><td><b>'.__('Bad Token Limit?','forcefield').'</b></td><td width="20"></td>';
				$limit = forcefield_get_setting('limit_bad_token', false);
			echo '<td><input style="width:40px;" type="number" name="ff_limit_bad_token" value="'.$limit.'"></td>';
			echo '<td width="10"></td>';
			echo '<td>'.__('Bad Token Transgressions before IP Ban.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('default','forcefield').': '.$limits['bad_token'].')</td></tr>';

			// --- missing referer records (blocklist_noreferer) ---
			echo '<tr><td><b>'.__('Record Missing Referer?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_noreferer" value="yes"';
				if (forcefield_get_setting('blocklist_noreferer', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Record IP of User Actions Missing Referer Header.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- missing referrer limit (no_referer) ---
			echo '<tr><td><b>'.__('Missing Referer Limit?','forcefield').'</b></td><td width="20"></td>';
				$limit = forcefield_get_setting('limit_no_referer', false);
			echo '<td><input style="width:40px;" type="number" name="ff_limit_no_referer" value="'.$limit.'"></td>';
			echo '<td width="10"></td>';
			echo '<td>'.__('No Referer Transgressions before IP Ban.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('default','forcefield').': '.$limits['no_referer'].')</td></tr>';

			// --- allow user unblocking via form (blocklist_unblocking) ---
			echo '<tr><td><b>'.__('Manual User Unblocking?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_unblocking" value="yes"';
				if (forcefield_get_setting('blocklist_unblocking', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Allows blocked visitors to unblock their IP manually via a simple form.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';


			// -----
			// Login
			// -----
			echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Login','forcefield').'</h3></td></tr>';

			// --- login referer check (login_norefblock) ---
			echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_norefblock" value="yes"';
				if (forcefield_get_setting('login_norefblock', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Block Login if missing HTTP Referer Header.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- login token (login_token) ---
			echo '<tr><td><b>'.__('Require Login Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_token" value="yes"';
				if (forcefield_get_setting('login_token', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Automatic Script Token for Login.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- login instant ban (login_notokenban) ---
			echo '<tr><td><b>'.__('InstaBan if Missing Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_notokenban" value="yes"';
				if (forcefield_get_setting('login_notokenban', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Instantly Ban IP if missing Login Token.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- require SSL for login (login_requiressl) ---
			echo '<tr><td><b>'.__('Require SSL for Login?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_requiressl" value="yes"';
				if (forcefield_get_setting('login_requiressl', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Secure Connection for User Login.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// --- disable login hints (login_nohints) ---
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

			// --- registration referer check (register_norefblock) ---
			echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_norefblock" value="yes"';
				if (forcefield_get_setting('register_norefblock', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Block Registration if missing HTTP Referer Header.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- registration token (register_token) ---
			echo '<tr><td><b>'.__('Require Registration Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_token" value="yes"';
				if (forcefield_get_setting('register_token', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Automatic Script Token for Registration.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- missing registration token auth ban (register_notokenban) ---
			echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_notokenban" value="yes"';
				if (forcefield_get_setting('register_notokenban', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Instantly Ban IP if missing Registration Token.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- require SSL for registration (register_requiressl) ---
			echo '<tr><td><b>'.__('Require SSL for Registration?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_requiressl" value="yes"';
				if (forcefield_get_setting('register_requiressl', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Secure Connection for User Registration.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// -----------------------
			// BuddyPress Registration
			// -----------------------
			// 0.9.5: added options for BuddyPress registration token
			// 0.9.6: removed unnecesary ff_ prefix on get_settings calls
			echo '<tr><td><h3 style="margin-bottom:10px;">'.__('BuddyPress Registration','forcefield').'</h3></td>';
			echo '<td> </td><td> </td><td> </td><td><b>'.__('Affects BuddyPress Only','forcefield').'</b></td></tr>';

			// --- registration referer check ---
			echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_norefblock" value="yes"';
				if (forcefield_get_setting('buddypress_norefblock', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Block Registration if missing HTTP Referer Header.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- registration token (buddypress_norefblock) ---
			echo '<tr><td><b>'.__('Require Registration Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_token" value="yes"';
				if (forcefield_get_setting('buddypress_token', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Automatic Script Token for Registration.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- missing registration token auth ban (buddypress_notokenban) ---
			echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_notokenban" value="yes"';
				if (forcefield_get_setting('buddypress_notokenban', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Instantly Ban IP if missing Registration Token.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- require SSL for registration (buddypress_requiressl) ---
			echo '<tr><td><b>'.__('Require SSL for Registration?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_requiressl" value="yes"';
				if (forcefield_get_setting('buddypress_requiressl', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Secure Connection for User Registration.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// -----------------------
			// Blog Signup (Multisite)
			// -----------------------
			echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Blog Signup','forcefield').'</h3></td>';
			echo '<td> </td><td> </td><td> </td><td><b>'.__('Affects Multisite Only','forcefield').'</b></td></tr>';

			// --- signup referer check (signup_norefblock) ---
			echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_norefblock" value="yes"';
				if (forcefield_get_setting('signup_norefblock', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Block Blog Signup if missing HTTP Referer.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- signup token (signup_token) ---
			echo '<tr><td><b>'.__('Require Blog Signup Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_token" value="yes"';
				if (forcefield_get_setting('signup_token', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Automatic Script Token for Blog Signup.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- missing signup token auth ban (signup_notokenban) ---
			echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_notokenban" value="yes"';
				if (forcefield_get_setting('signup_notokenban', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Instantly Ban IP if missing Blog Signup Token.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- require SSL for blog signup (signup_requiressl) ---
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

			// --- comment referer check (comment_norefblock) ---
			echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_norefblock" value="yes"';
				if (forcefield_get_setting('comment_norefblock', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Block Comment if missing HTTP Referer Header.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- comment token (comment_token) ---
			echo '<tr><td><b>'.__('Require Comment Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td><input type="checkbox" name="ff_comment_token" value="yes"';
				if (forcefield_get_setting('comment_token', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Automatic Script Token for Commenting.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- comment auth ban (comment_notokenban) ---
			echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_notokenban" value="yes"';
				if (forcefield_get_setting('comment_notokenban', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Instantly Ban IP if missing Comment Token.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- require SSL for commenting (comment_requiressl) ---
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

			// --- lost password referer check (lostpass_norefblock) ---
			echo '<tr><td><b>'.__('Block if Missing HTTP Referer?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_norefblock" value="yes"';
				if (forcefield_get_setting('lostpass_norefblock', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Block Lost Password request is missing HTTP Referer.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- lost password token (lostpass_token) ---
			echo '<tr><td><b>'.__('Require Lost Password Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_token" value="yes"';
				if (forcefield_get_setting('lostpass_token', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Automatic Script Token for Lost Password form.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- lost password auth ban (lostpass_notokenban) ---
			echo '<tr><td><b>'.__('InstaBan IP if Missing Token?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_notokenban" value="yes"';
				if (forcefield_get_setting('lostpass_notokenban', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Instantly Ban IP if missing Lost Password Token.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// --- require SSL for lost password (lostpass_requiressl) ---
			echo '<tr><td><b>'.__('Require SSL for Lost Password?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_requiressl" value="yes"';
				if (forcefield_get_setting('lostpass_requiressl', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Require Secure Connection for Lost Password form.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

		// --- close user action tab ---
		echo '</table></div>';


		// ---------------------
		// Role Login Protection
		// ---------------------
		$blockactions = array(
			''			=> __('Just Block Login','forcefield'),
			'remove'	=> __('Revoke this Role', 'forcefield'),
			'demote'	=> __('Demote to Subscriber', 'forcefield'),
			'delete'	=> __('Delete User', 'forcefield'),
		);

		if ($currenttab != 'role-protect') {$hide = ' style="display:none;"';} else {$hide = '';}
		echo '<div id="role-protect"'.$hide.'><table>';

			// Administrator Login
			// -------------------
			echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Administrator Protection','forcefield').'</h3></td></tr>';

			// --- admin login fail limit (admin_fail) ---
			echo '<tr><td><b>'.__('Failed Admin Login Limit','forcefield').'</b></td><td width="20"></td>';
				$limit = forcefield_get_setting('limit_admin_fail', false);
			echo '<td><input style="width:40px;" type="number" name="ff_limit_admin_fail" value="'.$limit.'"></td>';
			echo '<td width="10"></td>';
			echo '<td>'.__('Admin Login Failures before IP Ban.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('default','forcefield').': '.$limits['admin_fail'].')</td></tr>';

			// --- list of whitelisted admin usernames (admin_whitelist) ---
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>'.__('Whitelisted Admins','forcefield').'</b></td><td width="20"></td>';
				$whitelist = forcefield_get_setting('admin_whitelist', false);
			echo '<td colspan="5" style="vertical-align:top">';
			echo '<input type="text" name="ff_admin_whitelist" value="'.$whitelist.'" style="width:100%;margin-top:10px;">';
			echo '<br>'.__('Comma separated list of Whitelisted Administrator Accounts.','forcefield');
			echo '</td></tr>';

			// --- get all current administrator logins ---
			$query = new WP_User_Query(array('role' => 'Administrator', 'count_total' => false));
			$users = $query->results; $adminlogins = array();
			foreach ($users as $user) {$adminlogins[] = $user->data->user_login;}
			$adminusernames = implode(', ', $adminlogins);

			// --- display current admin usernames ---
			echo '<tr><td><b>'.__('Current Admin Usernames','forcefield').':</b></td><td width="20"></td>';
			echo '<td colspan="5">'.$adminusernames.'</td></tr>';

			// ---- block unwhitelisted Administrators (admin_block) ---
			echo '<tr><td><b>'.__('Block Unwhitelisted Admins?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_admin_block" value="yes"';
				if (forcefield_get_setting('admin_block', false) == 'yes') {echo ' checked';}
			echo '></td><td width="20"></td>';
			echo '<td>'.__('Block Administrator Logins not in Whitelist.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- send admin alert for administrator accounts (admin_alert) ---
			echo '<tr><td><b>'.__('Send Admin Alert Emails?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_admin_alert" value="yes"';
				if (forcefield_get_setting('admin_alert', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td colspan="3">'.__('Send Email Alert when Unwhitelisted Admin Logs in.','forcefield').'</td></tr>';

			// --- administrator alert email address (admin_email) ---
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>'.__('Admin Alert Email','forcefield').'</b></td><td width="20"></td>';
			$adminemail = forcefield_get_setting('admin_email', false);
			echo '<td colspan="3"><input type="text" name="ff_admin_email" value="'.$adminemail.'" style="width:100%;margin-top:10px;"></td>';
			echo '</tr>';

			// --- unwhitelisted Administrators action (admin_blockaction) ---
			// 0.9.6: change admin_autodelete checkbox to admin_blockaction selection
			$blockaction = forcefield_get_setting('admin_blockaction');
			echo '<tr><td style="vertical-align:top;"><b>'.__('Unwhitelisted Login Action?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="select-cell" colspan="5" style="vertical-align:top;">';
			echo '<select name="ff_admin_blockaction">';
			foreach ($blockactions as $option => $label) {
				if ($option == $blockaction) {$selected = ' selected="selected"';} else {$selected = '';}
				echo '<option value="'.$option.'"'.$selected.'>'.$label.'</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo __('Extra Action for Admin Accounts not in Whitelist.','forcefield')."<br>";
			echo __('Note: Demote to Subscriber removes all other user roles.','forcefield');
			echo '</div></td></tr>';


			// Super Admin Login
			// -----------------
			echo '<tr height="20"><td> </td></tr>';
			echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Super Admin Protection','forcefield').'</h3></td>';
			echo '<td> </td><td> </td><td> </td><td><b>'.__('Affects Multisite Only','forcefield').'</b></td></tr>';

			// --- super admin login fail limit (admin_fail) ---
			echo '<tr><td><b>'.__('Failed Super Admin Login Limit','forcefield').'</b></td><td width="20"></td>';
				$limit = forcefield_get_setting('limit_super_fail', false);
			echo '<td><input style="width:40px;" type="number" name="ff_limit_super_fail" value="'.$limit.'"></td>';
			echo '<td width="10"></td>';
			echo '<td>'.__('Super Admin Login Failures before IP Ban.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('default','forcefield').': '.$limits['super_fail'].')</td></tr>';

			// --- list of whitelisted super admin usernames (super_whitelist) ---
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>'.__('Whitelisted Super Admins','forcefield').'</b></td><td width="20"></td>';
				$whitelist = forcefield_get_setting('super_whitelist', false);
			echo '<td colspan="5" style="vertical-align:top">';
			echo '<input type="text" name="ff_super_whitelist" value="'.$whitelist.'" style="width:100%;margin-top:10px;">';
			echo '<br>'.__('Comma separated list of Whitelisted Super Admin Accounts.','forcefield');
			echo '</td></tr>';

			// --- get all current administrator logins ---
			if (is_multisite()) {
				$superadmins = get_super_admins();
				$superadminusernames = implode(', ', $superadmins);
			} else {$superadminusernames = __('None as not Multisite.','forcefield');}

			// --- display current admin usernames ---
			echo '<tr><td><b>'.__('Current Super Admins','forcefield').':</b></td><td width="20"></td>';
			echo '<td colspan="5">'.$superadminusernames.'</td></tr>';

			// ---- block unwhitelisted Administrators (super_block) ---
			echo '<tr><td><b>'.__('Block Unwhitelisted Super Admins?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_super_block" value="yes"';
				if (forcefield_get_setting('super_block', false) == 'yes') {echo ' checked';}
			echo '></td><td width="20"></td>';
			echo '<td>'.__('Block Super Admin Logins not in Whitelist.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- send admin alert for administrator accounts (super_alert) ---
			echo '<tr><td><b>'.__('Send Super Admin Alert Emails?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_super_alert" value="yes"';
				if (forcefield_get_setting('super_alert', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td colspan="3">'.__('Send Email Alert when Unwhitelisted Admin Logs in.','forcefield').'</td></tr>';

			// --- administrator alert email address (super_email) ---
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>'.__('Super Admin Alert Email','forcefield').'</b></td><td width="20"></td>';
				$superadminemail = forcefield_get_setting('super_email', false);
			echo '<td colspan="3"><input type="text" name="ff_admin_email" value="'.$superadminemail.'" style="width:100%;margin-top:10px;"></td>';
			echo '</tr>';

			// --- delete unwhitelisted Administrators (super_blockaction) ---
			// 0.9.6: change admin_autodelete to admin_blockaction selection
			$blockaction = forcefield_get_setting('super_blockaction');
			echo '<tr><td style="vertical-align:top;"><b>'.__('Unwhitelisted Login Action?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="select-cell" colspan="5" style="vertical-align:top;">';
			echo '<select name="ff_super_blockaction">';
			foreach ($blockactions as $option => $label) {
				if ($option == $blockaction) {$selected = ' selected="selected"';} else {$selected = '';}
				echo '<option value="'.$option.'"'.$selected.'>'.$label.'</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo __('Extra Action for Admin Accounts not in Whitelist.','forcefield')."<br>";
			echo __('Note: Demote to Subscriber removes all other user roles.','forcefield');
			echo '</div></td></tr>';

		// --- close role protect options tab ---
		echo '</table></div>';

		// ==========
		// API Access
		// ==========
		if ($currenttab != 'api-access') {$hide = ' style="display:none;"';} else {$hide = '';}
		echo '<div id="api-access"'.$hide.'><table>';

			// XML RPC
			// -------

			// --- table heading ---
			echo '<tr><td><h3 style="margin-bottom:10px;">XML RPC</h3></td></tr>';

			// --- disable XML RPC (xmlrpc_disable) ---
			echo '<tr><td><b>'.__('Disable XML RPC?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_disable" value="yes"';
				if (forcefield_get_setting('xmlrpc_disable', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Disable XML RPC Entirely.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('not recommended','forcefield').')</td></tr>';

			// --- disable XML RPC auth attempts (xmlrpc_authblock) ---
			echo '<tr><td><b>'.__('Disable XML RPC Logins?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authblock" value="yes"';
				if (forcefield_get_setting('xmlrpc_authblock', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('All Login attempts via XML RPC will be blocked.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- ban XML RPC auth attempts (xmlrpc_authban) ---
			echo '<tr><td><b>'.__('AutoBan IP for XML RPC Auth Attempts?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authban" value="yes"';
				if (forcefield_get_setting('xmlrpc_authban', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('ANY login attempts via XML RPC will result in an IP ban.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// --- failed XML RPC login limit (xmlrpc_authfail) ---
			echo '<tr><td><b>'.__('Failed XML RPC Login Limit','forcefield').'</b></td><td width="20"></td>';
				$limit = forcefield_get_setting('limit_xmlrpc_authfail', false);
			echo '<td><input style="width:40px;" type="number" name="ff_limit_xmlrpc_authfail" value="'.$limit.'"></td>';
			echo '<td width="10"></td>';
			echo '<td>'.__('XML RPC Login Failures before IP Ban.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('default','forcefield').': '.$limits['xmlrpc_authfail'].')</td></tr>';

			// --- require SSL ---
			echo '<tr><td><b>'.__('Require SSL for XML RPC?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_requiressl" value="yes"';
				if (forcefield_get_setting('xmlrpc_requiressl', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Only allow XML RPC access via SSL.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// --- XML RPC Slowdown (xmlrpc_slowdown) ---
			echo '<tr><td><b>'.__('Rate Limit XML RPC?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_slowdown" value="yes"';
				if (forcefield_get_setting('xmlrpc_slowdown', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Slowdown via a Rate Limiting Delay.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// --- anonymous comments (xmlrpc_anoncomments) ---
			echo '<tr><td><b>'.__('Disable XML RPC Anonymous Comments?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_anoncomments" value="yes"';
				if (forcefield_get_setting('xmlrpc_anoncomments', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Disable Anonymous Commenting via XML RPC.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- role restrict XML RPC access (xmlrpc_resticted) ---
			echo '<tr><td class="valigntop"><b>'.__('Restrict XML RPC Access?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_xmlrpc_restricted" value="yes"';
				if (forcefield_get_setting('xmlrpc_restricted', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td class="valigntop">'.__('Restrict XML RPC Access to Selected Roles.','forcefield').'<br>';
			echo __('Note: Enforces Logged In Only Access','forcefield').'</td>';
			echo '<td width="10"></td><td class="valigntop">('.__('optional','forcefield').')</td></tr>';

			// --- roles for XML RPC role restriction (xmlrpc_roles) ---
			$xmlrpcroles = forcefield_get_setting('xmlrpc_roles', false);
			if (!is_array($xmlrpcroles)) {$xmlrpcroles = array();}
			echo '<tr><td class="valigntop"><b>'.__('Restrict to Selected Roles','forcefield').'</b></td>';
			echo '<td width="20"></td><td></td><td></td>';
			echo '<td class="valigntop" colspan="3">';
			foreach ($roles as $slug => $label) {
				echo '<div class="role-box">';
				echo '<input type="checkbox" name="ff_xmlrpc_role_'.$slug.'" value="yes"';
					if (in_array($slug, $xmlrpcroles)) {echo ' checked';}
				echo '> '.$label.'</div>';
			}
			echo '</td></tr>';

			// --- disable pingbacks (xmlrpc_nopingbacks) ---
			echo '<tr><td><b>'.__('Disable Pingback Processing?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_nopingbacks" value="yes"';
			if (forcefield_get_setting('xmlrpc_nopingbacks', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Disable XML RPC Pingback processing.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('not recommended','forcefield').')</td></tr>';

			// --- disable self pings (xmlrpc_noselfpings) ---
			echo '<tr><td><b>'.__('Disable Self Pings?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_noselfpings" value="yes"';
				if (forcefield_get_setting('xmlrpc_noselfpings', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Disable Pingbacks from this site to itself.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- close XML RPC option table ---
			echo '</table>';

			// --- pro method restriction options ---
			// 0.9.1: [PRO] XML RPC Method Restriction Options
			if (function_exists('forcefield_pro_method_options')) {forcefield_pro_method_options();}

			// REST API
			// --------

			// --- table heading ---
			echo '<table><tr><td><h3 style="margin-bottom:10px;">REST API</h3></td></tr>';

			// --- disable REST API (restapi_disable) ---
			echo '<tr><td><b>'.__('Disable REST API?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_disable" value="yes"';
				if (forcefield_get_setting('restapi_disable', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Disable REST API entirely.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('not recommended','forcefield').')</td></tr>';

			// --- logged in users only (restapi_authonly) ---
			echo '<tr><td><b>'.__('Logged In Users Only?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="restapi_authonly" value="yes"';
				if (forcefield_get_setting('restapi_authonly', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('REST API access for authenticated users only.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('recommended','forcefield').')</td></tr>';

			// --- require SSL (restapi_requiressl) ---
			echo '<tr><td><b>'.__('Require SSL for REST API?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_requiressl" value="yes"';
				if (forcefield_get_setting('restapi_requiressl', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Only allow REST API access via SSL.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// --- rate limiting for REST (restapi_slowdown) ---
			echo '<tr><td class="valigntop"><b>'.__('Rate Limit REST API?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_restapi_slowdown" value="yes"';
				if (forcefield_get_setting('restapi_slowdown', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td class="valigntop">'.__('Slowdown via a Rate Limiting Delay.','forcefield').'</td>';
			echo '<td width="10"></td><td>('.__('optional','forcefield').')</td></tr>';

			// --- role restrict REST API access (restapi_restricted) ---
			echo '<tr><td class="valigntop"><b>'.__('Restrict REST API Access?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_restapi_restricted" value="yes"';
				if (forcefield_get_setting('restapi_restricted', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td class="valigntop">'.__('Restrict REST API Access to Selected Roles.','forcefield').'<br>';
			echo __('Note: Enforces Logged In Only Access','forcefield').'</td>';
			echo '<td width="10"></td><td class="valigntop">('.__('optional','forcefield').')</td></tr>';

			// --- roles for REST API role restriction (restapi_roles) ---
			$restroles = forcefield_get_setting('restapi_roles', false);
			if (!is_array($restroles)) {$restroles = array();}
			echo '<tr><td class="valigntop"><b>'.__('Restrict to Selected Roles','forcefield').'</b></td>';
			echo '<td width="20"></td><td></td><td></td>';
			echo '<td class="valigntop" colspan="3">';
			foreach ($roles as $slug => $label) {
				echo '<div class="role-box">';
				echo '<input type="checkbox" name="ff_restapi_role_'.$slug.'" value="yes"';
					if (in_array($slug, $restroles)) {echo ' checked';}
				echo '> '.$label.'</div>';
			}
			echo '</td></tr>';

			// --- disable User List Endpoint (restapi_nouserlist) ---
			echo '<tr><td><b>'.__('Disable Userlist Endpoint?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nouserlist" value="yes"';
				if (forcefield_get_setting('restapi_nouserlist', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Disable the REST API User Enumeration Endpoint.','forcefield').'</td></tr>';

			// --- disable REST API Links (restapi_nolinks) ---
			echo '<tr><td><b>'.__('Disable REST API Links?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nolinks" value="yes"';
				if (forcefield_get_setting('restapi_nolinks', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Remove output of REST API Links in Page Head.','forcefield').'</td></tr>';

			// --- disable JSONP for REST API (restapi_nojsonp) ---
			echo '<tr><td><b>'.__('Disable JSONP for REST API?','forcefield').'</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nojsonp" value="yes"';
				if (forcefield_get_setting('restapi_nojsonp', false) == 'yes') {echo ' checked';}
			echo '></td><td width="10"></td>';
			echo '<td>'.__('Disables JSONP Output for the REST API.','forcefield').'</td></tr>';

			// --- REST API prefix (restapi_prefix) ---
			// 0.9.7: removed this option (this filter is better hardcoded in mu-plugins!)
			// note default: "wp-json"
			// echo '<tr><td style="vertical-align:top;"><b>'.__('Prefix for REST API Access?','forcefield').'</b></td><td colspan="3"></td>';
			// echo '<td colspan="5"><input type="text" name="ff_restapi_prefix" value="';
			//	echo forcefield_get_setting('restapi_prefix', false);
			// echo '"><br>'.__('Leave blank for no change. Default','forcefield').': "wp-json"';
			// echo '<br>'.__('Note: you may need to resave permalinks to effect.','forcefield');
			// echo '</td></tr>';

			// --- close REST API option table ---
			echo '</table>';

			// --- pro endpoint restriction options ---
			// 0.9.1: [PRO] Output Endpoint Restriction Options
			if (function_exists('forcefield_pro_endpoint_options')) {forcefield_pro_endpoint_options();}

		// --- close REST API tab ---
		echo '</div>';

		// 0.9.6: removed auto updates options display

	// Reset and Update Buttons
	// ------------------------
	echo '<div id="update-buttons"><br><table style="width:100%;">'.PHP_EOL;

		// --- Reset Button ---
		echo '<tr><td width="33%" style="text-align:right;">'.PHP_EOL;
			// 0.9.7: fix to incorrect javascript function (returntodefaults)
			echo '<input type="submit" value="'.__('Reset to Defaults','forcefield').'" class="button-secondary" onclick="return resettodefaults();" style="margin-right:20px;">'.PHP_EOL;
		echo '</td>';

		// --- middle spacer ---
		echo '<td width="33%"> </td>'.PHP_EOL;

		// --- Update Submit Button ---
		echo '<td width="33%" style="text-align:center;">'.PHP_EOL;
			// 0.9.7: added missing ID tag for sidebar button trigger
			echo '<input id="plugin-settings-save" type="submit" class="button-primary" value="'.__('Update Settings','forcefield').'">'.PHP_EOL;
		echo '</td></tr>'.PHP_EOL;

	// --- close update buttons div ---
	echo '</table><br></div>'.PHP_EOL;

	// --- close main settings form ---
	echo '</form>'.PHP_EOL;


	// ============
	// IP Blocklist
	// ============

	if ($currenttab != 'ip-blocklist') {$hide = ' display:none;';} else {$hide = '';}
	echo '<div id="ip-blocklist" style="min-height:500px;'.$hide.'">';

	// --- maybe load pro blocklist interface ---
	// 0.9.2: [PRO] Manual IP Whitelist / Blacklist (with context / expiry options)
	if (function_exists('forcefield_pro_lists_interface')) {forcefield_pro_lists_interface();}

	// --- blocklist heading ---
	echo '<h3 style="margin-bottom:10px;">'.__('IP Blocklist','forcefield').'</h3>';

	// --- get blocklist records ---
	$reasons = forcefield_blocklist_get_reasons();
	$columns = array('ip', 'label', 'transgressions', 'last_access_at', 'created_at');
	// note other columns: 'id', 'list', 'ip6', 'is_range', 'deleted_at'
	// 0.9.6: fix to switch of argument 1 and 3 in function
	$blocklist = forcefield_blocklist_get_records($columns, false, false, true);

	// --- check for blocklist ---
	// 0.9.1: output IP Blocklist with removal buttons
	if ($blocklist && (count($blocklist) > 0) ) {

		// --- clear entire blocklist button ---
		echo '<div style="width:100%;text-align:center;">';
		echo '<form action="'.$adminajax.'" target="blocklist-action-frame">';
		echo '<input type="hidden" name="action" value="forcefield_blocklist_clear">';
			wp_nonce_field('forcefield-clear');
		echo '<input type="submit" class="button-secondary" value="'.__('Clear Entire IP Blocklist','forcefield').'" onclick="return confirmblocklistclear();">';
		echo '</form></div><br>'.PHP_EOL;

		// TODO: add sortable columns and/or pagination
		// - group records by IP address to show activity ?
		// - group records by activity to show patterns ?

		echo '<div id="blocklist-table"><table><tr>'.PHP_EOL;
		echo '<td><b>'.__('IP Address','forcefield').'</b></td><td width="10"></td>'.PHP_EOL;
		echo '<td><b>'.__('Block Reason','forcefield').'</b></td><td width="10"></td>'.PHP_EOL;
		echo '<td><b>#</b></td><td width="10"></td>'.PHP_EOL;
		echo '<td><b>'.__('Blocked?','forcefield').'</b></td><td width="10"></td>'.PHP_EOL;
		echo '<td><b>'.__('First Access','forcefield').'</b></td><td width="10"></td>'.PHP_EOL;
		echo '<td><b>'.__('Last Access','forcefield').'</b></td><td width="10"></td>'.PHP_EOL;
		echo '<td></td></tr>'.PHP_EOL;

		// --- output blocklist rows ---
		foreach ($blocklist as $row) {

			// --- check if IP blocked ---
			$limit = forcefield_get_setting('limit_'.$row['label'], false);
			if ($row['transgressions'] > $limit) {$blocked = true;} else {$blocked = false;}

			// --- IP Address ---
			echo '<tr><td>'.$row['ip'].'</td><td></td>'.PHP_EOL;

			// --- record reason ---
			echo '<td><div title="'.$reasons[$row['label']].'">'.$row['label'].'</div></td><td></td>'.PHP_EOL;

			// --- number of transgressions ---
			echo '<td>'.$row['transgressions'].'</td><td></td>'.PHP_EOL;

			// --- red X indicates blocked IP ---
			echo '<td>';
				if ($blocked) {echo '<font color="#E00;">Blocked</font>'.PHP_EOL;}
			echo '</td><td></td>'.PHP_EOL;

			// --- record created time date ---
			echo '<td>';
				$display = date('d-m-y', $row['created_at']);
				$title = date('H:i:s d-m-Y', $row['created_at']);
				echo '<div title="'.$title.'">'.$display.'</div>'.PHP_EOL;
			echo '</td><td></td>'.PHP_EOL;

			// --- last access time date ---
			echo '<td>';
				$display = date('d-m-y', $row['last_access_at']);
				$title = date('H:i:s d-m-Y', $row['last_access_at']);
				echo '<div title="'.$title.'">'.$display.'</div>';
			echo '</td><td></td>'.PHP_EOL;

			// --- record row removal button ---
			echo '<td><input type="button" value="X" class="button-secondary" title="'.__('Delete Record','forcefield').'" onclick="unblockip(\''.$row['ip'].'\',\''.$row['label'].'\');"></td>'.PHP_EOL;

			// --- full IP unblock button ---
			echo '<td>';
				if ($blocked) {echo '<input type="button" class="button-secondary" value="'.__('Unblock','forcefield').'" title="'.__('Unblock this IP','forcefield').'" onclick="unblockip(\''.$row['ip'].'\',\'\');"></td>'.PHP_EOL;}
			echo '</tr>'.PHP_EOL;
		}

		echo '</table></div>'.PHP_EOL; // close IP blocklist table

	} else {echo '<b>'.__('No IP Transgressions Recorded Yet.','forcefield').'</b><br>'.PHP_EOL;}

	// --- blocklist action iframe ---
	echo '<iframe style="display:none;" id="blocklist-action-frame" name="blocklist-action-frame" src="javascript:void(0);" frameborder=0></iframe>'.PHP_EOL;

	// --- close IP blocklist div ---
	echo '</div>'.PHP_EOL;

	// --- close #wrapbox ---
	echo '</div></div>'.PHP_EOL;

	// --- close #pagewrap ---
	echo '</div>'.PHP_EOL;

}
