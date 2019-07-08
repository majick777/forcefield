<?php

// ================
// ForceField Admin
// ================

// Admin Options Page
// ------------------
function forcefield_options_admin_page() {

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
		document.getElementById('auto-updates').style.display = 'none';
		document.getElementById('ip-blocklist').style.display = 'none';
		document.getElementById(tab).style.display = '';
		document.getElementById('general-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('user-actions-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('xml-rpc-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('rest-api-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('auto-updates-button').style.backgroundColor = '#DDDDDD';
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
		echo '<a href="javascript:void(0);" onclick="showtab(\'auto-updates\');" style="text-decoration:none;">'.PHP_EOL;
		echo '<div id="auto-updates-button" class="tab-button"';
			if ($vcurrenttab == 'auto-updates') {echo ' style="background-color:#F0F0F0;"';}
		echo '>'.__('Auto Updates','forcefield').'</div></a></li>'.PHP_EOL;

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

		// ============
		// Auto Updates
		// ============

		echo '<div id="auto-updates"';
			if ($vcurrenttab != 'auto-updates') {echo ' style="display:none;"';}
		echo '><table>';

		echo '<tr><td><h3 style="margin-bottom:10px;">'.__('Automatic Updates','forcefield').'</h3></td></tr>';

		// automatically update inactive plugins
		echo '<tr><td><b>'.__('Auto Update Inactive Plugins?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_autoupdate_inactive_plugins" value="yes"';
			if (forcefield_get_setting('autoupdate_inactive_plugins', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('(recommended)','forcefield').'</td></tr>';

		// automatically update inactive themes
		echo '<tr><td><b>'.__('Auto Update Inactive Themes?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_autoupdate_inactive_themes" value="yes"';
			if (forcefield_get_setting('autoupdate_inactive_themes', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('(recommended)','forcefield').'</td></tr>';

		// automatically update this plugin
		echo '<tr><td><b>'.__('Auto Update ForceField?','forcefield').'</b></td><td width="20"></td>';
		echo '<td class="checkbox-cell"><input type="checkbox" name="ff_autoupdate_self" value="yes"';
			if (forcefield_get_setting('autoupdate_self', false) == 'yes') {echo ' checked';}
		echo '></td><td width="10"></td>';
		echo '<td>'.__('(optional)','forcefield').'</td></tr>';

		// TODO: run auto-updates now button?

		echo '</table>'; // close Auto Update option table

		// 0.9.4: [PRO] extra Auto Update Options
		if (function_exists('forcefield_pro_autoupdate_options')) {forcefield_pro_autoupdate_options();}

		echo '</div>'; // close Auto Update Options

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
