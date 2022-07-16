<?php

// ========================
// === ForceField Admin ===
// ========================

// Development TODOs
// -----------------
// + add other role actions / whitelists (super-admin? editor?)

// -------------------
// Admin Settings Page
// -------------------
function forcefield_admin_page() {

	// 1.0.3: declare missing wp_version global
	global $forcefield, $wordquestplugins, $wp_version;
	$settings = $forcefield;

	// --- manual debug for settings ---
	// echo "<!-- ForceField Settings: " . PHP_EOL . print_r( $forcefield, true ) . PHP_EOL . " -->";

	// --- get current setting tab ---
	$currenttab = $forcefield['current_tab'];
	if ( '' == $currenttab ) {
		// 1.0.2: default to user actions tab
		// $currenttab = 'general';
		$currenttab = 'user-actions';
	}

	// --- get all user roles ---
	$roles = wp_roles()->get_names();

	// --- get default limits ----
	$limits = forcefield_blocklist_get_default_limits();

	// --- get intervals ---
	$intervals = forcefield_get_intervals();

	// --- start page wrap ---
	echo '<div id="pagewrap" class="wrap" style="width:100%; margin-right:0px !important;">' . PHP_EOL;

	// --- Call Plugin Sidebar ---
	$args = array( $settings['slug'], 'yes' );
	if ( function_exists( 'wqhelper_sidebar_floatbox' ) ) {
		wqhelper_sidebar_floatbox( $args );
		// 1.0.5: use echo argument for stickykit script
		wqhelper_sidebar_stickykitscript( true );
		echo '<style>#floatdiv {float:right;}</style>';
		echo '<script>jQuery("#floatdiv").stick_in_parent();
		wrapwidth = jQuery("#pagewrap").width();
		sidebarwidth = jQuery("#floatdiv").width();
		newwidth = wrapwidth - sidebarwidth - 20;
		jQuery("#wrapbox").css("width",newwidth+"px");
		jQuery("#adminnoticebox").css("width",newwidth+"px");
		</script>';
	}

	// --- admin notices boxer ---
	// 1.0.1: removed as already added via  plugin loader
	// if ( function_exists( 'wqhelper_admin_notice_boxer' ) ) {
	//	wqhelper_admin_notice_boxer();
	// } else {
	//	echo "<h2> </h2>";
	// }

	// --- Plugin Page Header ---
	// 0.9.6: use standard Plugin Page Header from Loader
	forcefield_settings_header();

	// --- Enqueue Settings Resources ---
	// 1.0.2: added enqueueing of settings resources
	forcefield_settings_resources( false, false );

	// --- admin page styles ---
	// 1.0.1: fix padding on tab buttons
	// 1.0.1: remove focus outline on tab links
	echo "<style>.pagelink {text-decoration:none;} .pagelink:hover {text-decoration:underline;}
	.checkbox-cell {max-width:40px !important;}
	.valigntop {vertical-align:top;}
	.role-box {display:inline-block; float:left; margin-right:20px;}
	.role-cell {max-width:350px;}
	.tab-button {width:100px; height:30px; background-color:#DDDDDD;
		border:1px solid #000; text-align:center; padding-top: 5px;}
	.tab-button:hover {background-color:#EEEEEE; font-weight:bold;}
	.tab-link:focus {outline:none; box-shadow:none;}</style>";

	// --- admin page scripts ---
	// 1.0.2: added nonce for blocklist clear get action
	$clearnonce = wp_create_nonce( 'forcefield-clear' );
	$adminajax = admin_url( 'admin-ajax.php' );
	$confirmreset = __( 'Are you sure you want to Reset to Default Settings?', 'forcefield' );
	$confirmclear = __( 'Are you sure you want to Clear the Entire IP Blocklist?', 'forcefield' );

	// 1.0.1: prefix javascript functions
	// 1.0.2: temporarily remove general tab
	// 1.0.2: use nonce in GET instead of POST on clear action
	echo "<script>function forcefield_showtab(tab) {
		document.getElementById('current-tab').value = tab;
		/* console.log(document.getElementById('current-tab').value); */
		/* document.getElementById('general').style.display = 'none'; */
		document.getElementById('role-protect').style.display = 'none';
		document.getElementById('user-actions').style.display = 'none';
		document.getElementById('api-access').style.display = 'none';";
		// 1.0.1: temporarily disabled if module not present
		if ( file_exists( FORCEFIELD_DIR . '/forcefield-vuln.php' ) ) {
			echo "document.getElementById('vuln-checker').style.display = 'none';";
		}
		echo "/* document.getElementById('auto-updates').style.display = 'none'; */
		document.getElementById('ip-blocklist').style.display = 'none';
		document.getElementById(tab).style.display = '';
		document.getElementById('general-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('role-protect-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('user-actions-button').style.backgroundColor = '#DDDDDD';
		document.getElementById('api-access-button').style.backgroundColor = '#DDDDDD';";
		if ( file_exists( FORCEFIELD_DIR . '/forcefield-vuln.php' ) ) {
			echo "document.getElementById('vuln-checker-button').style.backgroundColor = '#DDDDDD';";
		}
		echo "/* document.getElementById('auto-updates-button').style.backgroundColor = '#DDDDDD'; */
		document.getElementById('ip-blocklist-button').style.backgroundColor = '#DDDDDD';
		document.getElementById(tab+'-button').style.backgroundColor = '#F0F0F0';
		if (tab == 'ip-blocklist') {
			document.getElementById('update-buttons').style.display = 'none';
		} else {document.getElementById('update-buttons').style.display = '';}
	}
	function forcefield_reset() {
		var ask = '" . esc_js( $confirmreset ) . "'; agree = confirm(ask);
		if (!agree) {return false;}
		document.getElementById('forcefield-update-action').value = 'reset';
		document.getElementById('forcefield-update-form').submit();
	}
	function forcefield_blocklist_clear() {
		var ask = '" . esc_js( $confirmclear ) . "'; agree = confirm(ask);
		if (!agree) {return false;}
		url = '" . esc_url( $adminajax ) . "?action=forcefield_blocklist_clear&nonce=" . esc_js( $clearnonce ) . "';
		document.getElementById('blocklist-action-frame').src = url;
	}
	function forcefield_unblock_ip(ip, label, row) {
		ip = encodeURIComponent(ip);
		unblocknonce = document.getElementById('unblock-nonce').value;
		unblockurl = '" . esc_url( $adminajax ) . "?action=forcefield_unblock_ip&_wpnonce='+unblocknonce+'&ip='+ip;
		if (label) {unblockurl += '&label='+label;}
		if (row) {unblockurl += '&row='+row;}
		document.getElementById('blocklist-action-frame').src = unblockurl;
	}</script>";

	// --- hidden input for unblock nonce ---
	// 0.9.6: change nonce to -delete action suffix (-unblock is now for user)
	// 1.0.0: add separate input for nonce value (to allow for refreshing)
	// 1.0.1: added missing hidden type attribute to nonce field
	$unblocknonce = wp_create_nonce( 'forcefield-delete' );
	echo "<input type='hidden' id='unblock-nonce' value='" . esc_attr( $unblocknonce ) . "'>";

	// --- open wrap box ---
	echo "<div id='wrapbox' class='postbox' style='width:680px;line-height:2em;'>" . PHP_EOL;
	echo "<div class='inner' style='padding-left:20px;'>" . PHP_EOL;

	// --- display server and client IP addresses ---
	// 0.9.3: show current IP addresses
	$serverip = forcefield_get_server_ip();
	$clientip = forcefield_get_remote_ip( true );
	echo '<table width="100%"><tr><td width="50%"><b>' . esc_html( __( 'Server IP (Host)', 'forcefield' ) ) . '</b>: ' . esc_attr( $serverip ) . '</td>';
	echo '<td width="50%"><b>' . esc_html( __( 'Client IP (You)', 'forcefield' ) ) . '</b>: ' . esc_attr( $clientip ) . '</td></tr></table>';

	// --- settings tab selector buttons ---
	echo '<ul style="list-style:none;">' . PHP_EOL;

		// --- General ---
		// echo '<li style="display:inline-block;">' . PHP_EOL;
		// echo '<a href="javascript:void(0);" class="tab-link" onclick="forcefield_showtab(\'general\');" style="text-decoration:none;">' . PHP_EOL;
		// echo '<div id="general-button" class="tab-button"';
		// if ( 'general' == $currenttab ) {
		//	echo ' style="background-color:#F0F0F0;"';
		// }
		// echo '>' . esc_html( __( 'General', 'forcefield' ) ) . '</div></a></li>' . PHP_EOL;

		// --- User Actions ---
		echo '<li style="display:inline-block;">' . PHP_EOL;
		echo '<a href="javascript:void(0);" class="tab-link" onclick="forcefield_showtab(\'user-actions\');" style="text-decoration:none;">' . PHP_EOL;
		echo '<div id="user-actions-button" class="tab-button"';
		if ( 'user-actions' == $currenttab ) {
			echo ' style="background-color:#F0F0F0;"';
		}
		echo '>' . esc_html( __( 'User Actions', 'forcefield' ) ) . '</div></a></li>' . PHP_EOL;

		// --- Role Protect ---
		echo '<li style="display:inline-block;">' . PHP_EOL;
		echo '<a href="javascript:void(0);" class="tab-link" onclick="forcefield_showtab(\'role-protect\');" style="text-decoration:none;">' . PHP_EOL;
		echo '<div id="role-protect-button" class="tab-button"';
		if ( 'role-protect' == $currenttab ) {
			echo ' style="background-color:#F0F0F0;"';
		}
		echo '>' . esc_html( __( 'Role Protect', 'forcefield' ) ) . '</div></a></li>' . PHP_EOL;

		// --- API Access ---
		echo '<li style="display:inline-block;">' . PHP_EOL;
		echo '<a href="javascript:void(0);" class="tab-link" onclick="forcefield_showtab(\'api-access\');" style="text-decoration:none;">' . PHP_EOL;
		echo '<div id="api-access-button" class="tab-button"';
		if ( 'api-access' == $currenttab ) {
			echo ' style="background-color:#F0F0F0;"';
		}
		echo '>' . esc_html( __( 'API Access', 'forcefield' ) ) . '</div></a></li>' . PHP_EOL;

		// --- Vulnerabilities ---
		// 0.9.8: added vulnerability checker tab
		// 1.0.1: temporarily disabled unless module present
		if ( file_exists( FORCEFIELD_DIR . '/forcefield-vuln.php' ) ) {
			echo '<li style="display:inline-block;">' . PHP_EOL;
			echo '<a href="javascript:void(0);" class="tab-link" onclick="forcefield_showtab(\'vuln-checker\');" style="text-decoration:none;">' . PHP_EOL;
			echo '<div id="vuln-checker-button" class="tab-button"';
			if ( 'vuln-checker' == $currenttab ) {
				echo ' style="background-color:#F0F0F0;"';
			}
			echo '>' . esc_html( __( 'Vulnerabilities', 'forcefield' ) ) . '</div></a></li>' . PHP_EOL;
		}

		// --- Auto Updates ---
		// 0.9.6: removed auto updates page display
		// echo '<li style="display:inline-block;">'.PHP_EOL;
		// echo '<a href="javascript:void(0);" class="tab-link" onclick="forcefield_showtab(\'auto-updates\');" style="text-decoration:none;">'.PHP_EOL;
		// echo '<div id="auto-updates-button" class="tab-button"';
		//	if ($currenttab == 'auto-updates') {echo ' style="background-color:#F0F0F0;"';}
		// echo '>'.__('Auto Updates','forcefield').'</div></a></li>'.PHP_EOL;

		// --- IP Blocklist ---
		echo '<li style="display:inline-block;">' . PHP_EOL;
		echo '<a href="javascript:void(0);" class="tab-link" onclick="forcefield_showtab(\'ip-blocklist\');" style="text-decoration:none;">' . PHP_EOL;
		echo '<div id="ip-blocklist-button" class="tab-button"';
		if ( 'ip-blocklist' == $currenttab ) {
			echo ' style="background-color:#F0F0F0;"';
		}
		echo '>' . esc_html( __( 'Blocklists', 'forcefield' ) ) . '</div></a></li>' . PHP_EOL;

	echo '</ul>';

	// --- start settings update form ---
	// 0.9.6: added IDs for form and action elements (for settings reset)
	echo '<form method="post" id="forcefield-update-form">' . PHP_EOL;
	echo '<input type="hidden" id="forcefield-update-action" name="' . esc_attr( $settings['namespace'] ) . '_update_settings" value="yes">' . PHP_EOL;
	echo '<input type="hidden" id="current-tab" name="ff_current_tab" value="' . esc_attr( $currenttab ) . '">' . PHP_EOL;

	// --- add nonce field ---
	// 0.9.6: changed nonce ID from forcefield_update (for loader)
	// 1.0.1: append update_setting to slug for new loader
	wp_nonce_field( $settings['slug'] . '_update_settings' );

		// =======
		// General
		// =======
		// 1.0.2: move blocklist options moved to blocklist tab
		// 1.0.2: temporarily remove general tab
		// echo '<div id="general"';
		// if ( 'general' != $currenttab ) {
		//  echo ' style="display:none;"';
		// }
		// echo '><table>' . PHP_EOL;

		// --- close general options tab ---
		// echo '</table></div>' . PHP_EOL;


		// ============
		// User Actions
		// ============
		echo '<div id="user-actions"';
		if ( 'user-actions' != $currenttab ) {
			echo ' style="display:none;"';
		}
		echo '><table>' . PHP_EOL;

			// -------------------
			// User Action Options
			// -------------------
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'User Action Options', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

			// --- missing token records (blocklist_notoken) ---
			echo '<tr><td><b>' . esc_html( __( 'Record Missing Tokens?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_notoken" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'blocklist_notoken', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Record IP of User Actions Missing Tokens.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- missing token limit (no_token) ---
			echo '<tr><td><b>' . esc_html( __( 'Missing Token Limit?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$limit = forcefield_get_setting( 'limit_no_token', false );
			echo '<td><input style="width:40px;" type="number" name="ff_limit_no_token" value="' . esc_attr( $limit ) . '"></td>';
			echo '<td width="10"></td>';
			echo '<td>' . esc_html( __( 'No Referer Transgressions before IP Ban.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'default', 'forcefield' ) ) . ': ' . esc_attr( $limits['no_token'] ) . ')</td></tr>' . PHP_EOL;

			// --- missing token records (blocklist_badtoken) ---
			echo '<tr><td><b>' . esc_html( __( 'Record Bad Tokens?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_badtoken" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'blocklist_badtoken', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Record IP of User Actions with Bad Tokens.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>';

			// --- bad token limit (bad_token) ---
			echo '<tr><td><b>' . esc_html( __( 'Bad Token Limit?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$limit = forcefield_get_setting( 'limit_bad_token', false );
			echo '<td><input style="width:40px;" type="number" name="ff_limit_bad_token" value="' . esc_attr( $limit ) . '"></td>';
			echo '<td width="10"></td>';
			echo '<td>' . esc_html( __( 'Bad Token Transgressions before IP Ban.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'default', 'forcefield' ) ) . ': ' . esc_attr( $limits['bad_token'] ) . ')</td></tr>' . PHP_EOL;

			// --- missing referer records (blocklist_noreferer) ---
			echo '<tr><td><b>' . esc_html( __( 'Record Missing Referer?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_noreferer" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'blocklist_noreferer', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Record IP of User Actions Missing Referer Header.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>';

			// --- missing referrer limit (no_referer) ---
			echo '<tr><td><b>' . esc_html( __( 'Missing Referer Limit?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$limit = forcefield_get_setting( 'limit_no_referer', false );
			echo '<td><input style="width:40px;" type="number" name="ff_limit_no_referer" value="' . esc_attr( $limit ) . '"></td>';
			echo '<td width="10"></td>';
			echo '<td>' . esc_html( __( 'No Referer Transgressions before IP Ban.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'default', 'forcefield' ) ) . ': ' . esc_attr( $limits['no_referer'] ) . ')</td></tr>';

			// --- allow user unblocking via form (blocklist_unblocking) ---
			echo '<tr><td><b>' . esc_html( __( 'Manual User Unblocking?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_blocklist_unblocking" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'blocklist_unblocking', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Allows blocked visitors to unblock their IP manually via a simple form.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>';


			// -----
			// Login
			// -----
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Login', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

			// --- login referer check (login_norefblock) ---
			echo '<tr><td><b>' . esc_html( __( 'Block if Missing HTTP Referer?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_norefblock" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'login_norefblock', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Block Login if missing HTTP Referer Header.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- login token (login_token) ---
			echo '<tr><td><b>' . esc_html( __( 'Require Login Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_token" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'login_token', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Automatic Script Token for Login.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- login instant ban (login_notokenban) ---
			echo '<tr><td><b>' . esc_html( __( 'InstaBan if Missing Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_notokenban" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'login_notokenban', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Instantly Ban IP if missing Login Token.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- require SSL for login (login_requiressl) ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for Login?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'login_requiressl', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Secure Connection for User Login.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- disable login hints (login_nohints) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable Login Hints (Errors)?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_login_nohints" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'login_nohints', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disables Login Error Output Hints on Login Screen.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// ------------
			// Registration
			// ------------
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Registration', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

			// --- registration referer check (register_norefblock) ---
			echo '<tr><td><b>' . esc_html( __( 'Block if Missing HTTP Referer?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_norefblock" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'register_norefblock', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Block Registration if missing HTTP Referer Header.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- registration token (register_token) ---
			echo '<tr><td><b>' . esc_html( __( 'Require Registration Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_token" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'register_token', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Automatic Script Token for Registration.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- missing registration token auth ban (register_notokenban) ---
			echo '<tr><td><b>' . esc_html( __( 'InstaBan IP if Missing Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_notokenban" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'register_notokenban', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Instantly Ban IP if missing Registration Token.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- require SSL for registration (register_requiressl) ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for Registration?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_register_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'register_requiressl', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Secure Connection for User Registration.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// -----------------------
			// BuddyPress Registration
			// -----------------------
			// 0.9.5: added options for BuddyPress registration token
			// 0.9.6: removed unnecesary ff_ prefix on get_settings calls
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'BuddyPress Registration', 'forcefield' ) ) . '</h3></td>';
			echo '<td> </td><td> </td><td> </td><td><b>' . esc_html( __( 'Affects BuddyPress Only', 'forcefield' ) ) . '</b></td></tr>' . PHP_EOL;

			// --- registration referer check ---
			echo '<tr><td><b>' . esc_html( __( 'Block if Missing HTTP Referer?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_norefblock" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'buddypress_norefblock', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Block Registration if missing HTTP Referer Header.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>';

			// --- registration token (buddypress_norefblock) ---
			echo '<tr><td><b>' . esc_html( __( 'Require Registration Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_token" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'buddypress_token', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Automatic Script Token for Registration.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- missing registration token auth ban (buddypress_notokenban) ---
			echo '<tr><td><b>' . esc_html( __( 'InstaBan IP if Missing Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_notokenban" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'buddypress_notokenban', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Instantly Ban IP if missing Registration Token.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- require SSL for registration (buddypress_requiressl) ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for Registration?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_buddypress_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'buddypress_requiressl', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Secure Connection for User Registration.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// -----------------------
			// Blog Signup (Multisite)
			// -----------------------
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Blog Signup', 'forcefield' ) ) . '</h3></td>';
			echo '<td> </td><td> </td><td> </td><td><b>' . esc_html( __( 'Affects Multisite Only', 'forcefield' ) ) . '</b></td></tr>';

			// --- signup referer check (signup_norefblock) ---
			echo '<tr><td><b>' . esc_html( __( 'Block if Missing HTTP Referer?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_norefblock" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'signup_norefblock', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Block Blog Signup if missing HTTP Referer.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>';

			// --- signup token (signup_token) ---
			echo '<tr><td><b>' . esc_html( __( 'Require Blog Signup Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_token" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'signup_token', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Automatic Script Token for Blog Signup.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- missing signup token auth ban (signup_notokenban) ---
			echo '<tr><td><b>' . esc_html( __( 'InstaBan IP if Missing Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_notokenban" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'signup_notokenban', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Instantly Ban IP if missing Blog Signup Token.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>';

			// --- require SSL for blog signup (signup_requiressl) ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for Blog Signup?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_signup_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'signup_requiressl', false ) ) {
				echo ' checked';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Secure Connection for Blog Signup.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// ----------
			// Commenting
			// ----------
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Comments', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

			// --- comment referer check (comment_norefblock) ---
			echo '<tr><td><b>' . esc_html( __( 'Block if Missing HTTP Referer?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_norefblock" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'comment_norefblock', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Block Comment if missing HTTP Referer Header.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- comment token (comment_token) ---
			echo '<tr><td><b>' . esc_html( __( 'Require Comment Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td><input type="checkbox" name="ff_comment_token" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'comment_token', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Automatic Script Token for Commenting.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- comment auth ban (comment_notokenban) ---
			echo '<tr><td><b>' . esc_html( __( 'InstaBan IP if Missing Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_notokenban" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'comment_notokenban', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Instantly Ban IP if missing Comment Token.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- require SSL for commenting (comment_requiressl) ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for Commenting?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_comment_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'comment_requiressl', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require SSL for Commenting.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// -------------
			// Lost Password
			// -------------
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Lost Password', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

			// --- lost password referer check (lostpass_norefblock) ---
			echo '<tr><td><b>' . esc_html( __( 'Block if Missing HTTP Referer?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_norefblock" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'lostpass_norefblock', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Block Lost Password request if missing HTTP Referer.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- lost password token (lostpass_token) ---
			echo '<tr><td><b>' . esc_html( __( 'Require Lost Password Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_token" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'lostpass_token', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Automatic Script Token for Lost Password form.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>';

			// --- lost password auth ban (lostpass_notokenban) ---
			echo '<tr><td><b>' . esc_html( __( 'InstaBan IP if Missing Token?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_notokenban" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'lostpass_notokenban', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Instantly Ban IP if missing Lost Password Token.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- require SSL for lost password (lostpass_requiressl) ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for Lost Password?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_lostpass_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'lostpass_requiressl', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Require Secure Connection for Lost Password form.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

		// --- close user action tab ---
		echo '</table></div>' . PHP_EOL;


		// ---------------------
		// Role Login Protection
		// ---------------------
		// 1.0.1: fix to revoke option key (was remove)
		$blockactions = array(
			''			=> __( 'Just Block Login', 'forcefield' ),
			'revoke'	=> __( 'Revoke Admin Role', 'forcefield' ),
			'demote'	=> __( 'Demote to Subscriber', 'forcefield' ),
			'delete'	=> __( 'Delete User (Caution!)', 'forcefield' ),
		);
		$superblockactions = $blockactions;
		$superblockactions['revoke'] = __( 'Revoke Super Admin Role', 'forcefield' );

		echo '<div id="role-protect"';
		if ( 'role-protect' != $currenttab ) {
			echo ' style="display:none;"';
		}
		echo '><table>';

			// Administrator Login
			// -------------------
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Administrator Protection', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

			// --- admin login fail limit (admin_fail) ---
			echo '<tr><td><b>' . esc_html( __( 'Failed Admin Login Limit', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$limit = forcefield_get_setting( 'limit_admin_fail', false );
			echo '<td><input style="width:40px;" type="number" name="ff_limit_admin_fail" value="' . esc_attr( $limit ) . '"></td>';
			echo '<td width="10"></td>';
			echo '<td>' . esc_html( __( 'Admin Login Failures before IP Ban.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'default', 'forcefield' ) ) . ': ' . esc_attr( $limits['admin_fail'] ) . ')</td></tr>' . PHP_EOL;

			// --- list of whitelisted admin usernames (admin_whitelist) ---
			$whitelist = forcefield_get_setting( 'admin_whitelist', false );
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'Whitelisted Admins', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td colspan="5" style="vertical-align:top">';
			echo '<input type="text" name="ff_admin_whitelist" value="' . esc_attr( $whitelist ) . '" style="width:100%;margin-top:10px;">';
			echo '<br>' . esc_html( __( 'Comma separated list of Whitelisted Administrator Accounts.', 'forcefield' ) );
			echo '</td></tr>';

			// --- get all current administrator logins ---
			$adminwhitelist = explode( ',', $whitelist );
			$query = new WP_User_Query( array( 'role' => 'Administrator', 'count_total' => false ) );
			// 1.0.3: fix to use public get_results method
			$users = $query->get_results();

			// --- display current admin usernames ---
			echo '<tr><td><b>' . esc_html( __( 'Current Admin Usernames', 'forcefield' ) ) . ':</b></td><td width="20"></td>';
			echo '<td colspan="5">';
			foreach ( $users as $i => $user ) {
				$username = $user->data->user_login;
				if ( $i > 0 ) {
					echo ', ';
				}
				if ( is_array( $adminwhitelist ) && ( count( $adminwhitelist ) > 0 ) ) {
					if ( in_array( $username, $adminwhitelist ) ) {
						echo '<b>' . esc_html( $username ) . '</b>';
					} else {
						echo '<span style="color:#777;">' . esc_html( $username ) . '</span>';
					}
				} else {
					echo esc_html( $username );
				}
			}
			echo '</td></tr>' . PHP_EOL;

			// ---- block unwhitelisted Administrators (admin_block) ---
			echo '<tr><td><b>' . esc_html( __( 'Block Unwhitelisted Admins?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_admin_block" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'admin_block', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="20"></td>';
			echo '<td>' . esc_html( __( 'Block Administrator Logins not in Whitelist.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- send admin alert for administrator accounts (admin_alert) ---
			echo '<tr><td><b>' . esc_html( __( 'Send Admin Alert Emails?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_admin_alert" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'admin_alert', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td colspan="3">' . esc_html( __( 'Send Email Alert when Unwhitelisted Admin Logs in.', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

			// --- administrator alert email address (admin_email) ---
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'Admin Alert Email', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$adminemail = forcefield_get_setting( 'admin_email', false );
			// 1.0.4: added missing esc_attr value wrapper
			echo '<td colspan="3"><input type="text" name="ff_admin_email" value="' . esc_attr( $adminemail ) . '" style="width:100%;margin-top:10px;"></td>';
			echo '</tr>';

			// --- unwhitelisted Administrators action (admin_blockaction) ---
			// 0.9.6: change admin_autodelete checkbox to admin_blockaction selection
			$blockaction = forcefield_get_setting( 'admin_blockaction' );
			echo '<tr><td style="vertical-align:top;"><b>' . esc_html( __( 'Unwhitelisted Login Action?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="select-cell" colspan="5" style="vertical-align:top;">';
			echo '<select name="ff_admin_blockaction">';
			foreach ( $blockactions as $option => $label ) {
				echo '<option value="' . esc_attr( $option ) . '"';
				// 1.0.4: simplify selected to direct output
				if ( $option == $blockaction ) {
					echo ' selected="selected"';
				}
				echo '>' . esc_html( $label ) . '</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo esc_html( __( 'Extra Action for Admin Accounts not in Whitelist.', 'forcefield' ) ) . '<br>';
			echo esc_html( __( 'Note: Demote to Subscriber removes all other user roles.', 'forcefield' ) );
			echo '</div></td></tr>' . PHP_EOL;


			// Super Admin Login
			// -----------------
			// 1.0.1: temporarily disabled for further testing
			/*
			echo '<tr height="20"><td> </td></tr>';
			echo '<tr><td><h3 style="margin-bottom:10px;">' . esc_html( __( 'Super Admin Protection', 'forcefield' ) ) . '</h3></td>';
			echo '<td> </td><td> </td><td> </td><td><b>' . esc_html( __( 'Affects Multisite Only', 'forcefield' ) ) . '</b></td></tr>' . PHP_EOL;

			// --- super admin login fail limit (admin_fail) ---
			echo '<tr><td><b>' . esc_html( __( 'Failed Super Admin Login Limit', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$limit = forcefield_get_setting('limit_super_fail', false);
			echo '<td><input style="width:40px;" type="number" name="ff_limit_super_fail" value="' . esc_attr( $limit ) . '"></td>';
			echo '<td width="10"></td>';
			echo '<td>' . esc_html( __( 'Super Admin Login Failures before IP Ban.', 'forcefield') ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'default', 'forcefield' ) ) . ': ' . esc_attr( $limits['super_fail'] ) . ')</td></tr>' . PHP_EOL;

			// --- list of whitelisted super admin usernames (super_whitelist) ---
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'Whitelisted Super Admins', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$whitelist = forcefield_get_setting( 'super_whitelist', false );
			echo '<td colspan="5" style="vertical-align:top">';
			echo '<input type="text" name="ff_super_whitelist" value="' . esc_attr( $whitelist ) . '" style="width:100%;margin-top:10px;">';
			echo '<br>' . esc_html( __( 'Comma separated list of Whitelisted Super Admin Accounts.', 'forcefield' ) );
			echo '</td></tr>' . PHP_EOL;

			// --- get all current administrator logins ---
			if (is_multisite()) {
				$superadmins = get_super_admins();
				$superadminusernames = implode( ', ', $superadmins );
			} else {
				$superadminusernames = __( 'None as not Multisite.', 'forcefield' );
			}

			// --- display current admin usernames ---
			echo '<tr><td><b>' . esc_html( __( 'Current Super Admins', 'forcefield' ) ) . ':</b></td><td width="20"></td>';
			echo '<td colspan="5">' . esc_html( $superadminusernames ) . '</td></tr>' . PHP_EOL;

			// ---- block unwhitelisted Administrators (super_block) ---
			echo '<tr><td><b>' . esc_html( __( 'Block Unwhitelisted Super Admins?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_super_block" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'super_block', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="20"></td>';
			echo '<td>' . esc_html( __( 'Block Super Admin Logins not in Whitelist.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- send admin alert for administrator accounts (super_alert) ---
			echo '<tr><td><b>' . esc_html( __( 'Send Super Admin Alert Emails?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_super_alert" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'super_alert', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td colspan="3">' . esc_html( __( 'Send Email Alert on Unwhitelisted SuperAdmin Login.', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

			// --- administrator alert email address (super_email) ---
			echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'Super Admin Alert Email', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$superadminemail = forcefield_get_setting( 'super_email', false );
			echo '<td colspan="3"><input type="text" name="ff_admin_email" value="' . $superadminemail . '" style="width:100%;margin-top:10px;"></td>';
			echo '</tr>' . PHP_EOL;

			// --- delete unwhitelisted Administrators (super_blockaction) ---
			// 0.9.6: change admin_autodelete to admin_blockaction selection
			$blockaction = forcefield_get_setting( 'super_blockaction' );
			echo '<tr><td style="vertical-align:top;"><b>' . esc_html( __( 'Unwhitelisted Login Action?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="select-cell" colspan="5" style="vertical-align:top;">';
			echo '<select name="ff_super_blockaction">';
			foreach ( $superblockactions as $option => $label ) {
				echo '<option value="' . esc_attr( $option ) . '"';
				if ( $option == $superblockaction ) {
					echo ' selected="selected"';
				}
				echo '>' . esc_html( $label ) . '</option>';
			}
			echo '</select><div style="margin-left:20px; display:inline-block;">';
			echo esc_html( __( 'Extra Action for Admin Accounts not in Whitelist.', 'forcefield' ) ) . "<br>";
			echo esc_html( __( 'Note: Demote to Subscriber removes all other user roles.', 'forcefield' ) );
			echo '</div></td></tr>' . PHP_EOL;
			*/

		// --- close role protect options tab ---
		echo '</table></div>' . PHP_EOL;

		// ==========
		// API Access
		// ==========
		echo '<div id="api-access"';
		if ( 'api-access' != $currenttab ) {
			echo ' style="display:none;"';
		}
		echo '><table>' . PHP_EOL;

			// ---------------------
			// Application Passwords
			// ---------------------
			// 1.0.1: added application passwords setting
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">Application Passwords</h3></td></tr>' . PHP_EOL;
			echo '<tr><td><b>' . esc_html( __( 'Disable Application Passwords?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_app_passwords_disable" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'app_passwords_disable', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disable Application Passwords Feature.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// -------
			// XML RPC
			// -------

			// --- table heading ---
			echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">XML RPC</h3></td></tr>' . PHP_EOL;

			// --- disable XML RPC (xmlrpc_disable) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable XML RPC?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_disable" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_disable', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disable XML RPC Entirely.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'not recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- disable XML RPC auth attempts (xmlrpc_authblock) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable XML RPC Logins?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authblock" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_authblock', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'All Login attempts via XML RPC will be blocked.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- ban XML RPC auth attempts (xmlrpc_authban) ---
			echo '<tr><td><b>' . esc_html( __( 'AutoBan IP for XML RPC Auth Attempts?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_authban" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_authban', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Any login attempt via XML RPC will result in an IP ban.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- failed XML RPC login limit (xmlrpc_authfail) ---
			echo '<tr><td><b>' . esc_html( __( 'Failed XML RPC Login Limit', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			$limit = forcefield_get_setting( 'limit_xmlrpc_authfail', false );
			// 1.0.4: added missing esc_attr wrapper
			echo '<td><input style="width:40px;" type="number" name="ff_limit_xmlrpc_authfail" value="' . esc_attr( $limit ) . '"></td>';
			echo '<td width="10"></td>';
			echo '<td>' . esc_html( __( 'XML RPC Login Failures before IP Ban.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'default', 'forcefield' ) ) . ': ' . esc_attr( $limits['xmlrpc_authfail'] ) . ')</td></tr>' . PHP_EOL;

			// --- require SSL ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for XML RPC?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_requiressl', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Only allow XML RPC access via SSL.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- XML RPC Slowdown (xmlrpc_slowdown) ---
			echo '<tr><td><b>' . esc_html( __( 'Rate Limit XML RPC?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_slowdown" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_slowdown', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Slowdown via a Rate Limiting Delay.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- anonymous comments (xmlrpc_anoncomments) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable XML RPC Anonymous Comments?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_anoncomments" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_anoncomments', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disable Anonymous Commenting via XML RPC.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- role restrict XML RPC access (xmlrpc_resticted) ---
			echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Restrict XML RPC Access?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_xmlrpc_restricted" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_restricted', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td class="valigntop">' . esc_html( __( 'Restrict XML RPC Access to Selected Roles.', 'forcefield' ) ) . '<br>';
			echo esc_html( __( 'Note: Enforces Logged In Only Access', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td class="valigntop">(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- roles for XML RPC role restriction (xmlrpc_roles) ---
			$xmlrpcroles = forcefield_get_setting( 'xmlrpc_roles', false );
			if ( !is_array( $xmlrpcroles ) ) {
				$xmlrpcroles = array();
			}
			echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Restrict to Selected Roles', 'forcefield' ) ) . '</b></td>';
			echo '<td width="20"></td><td></td><td></td>';
			echo '<td class="valigntop" colspan="3">';
			foreach ( $roles as $slug => $label ) {
				echo '<div class="role-box">';
				echo '<input type="checkbox" name="ff_xmlrpc_role_' . esc_attr( $slug ) . '" value="yes"';
					if ( in_array( $slug, $xmlrpcroles ) ) {
						echo ' checked="checked"';
					}
				echo '> ' . esc_html( $label ) . '</div>';
			}
			echo '</td></tr>' . PHP_EOL;

			// --- disable pingbacks (xmlrpc_nopingbacks) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable Pingback Processing?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_nopingbacks" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_nopingbacks', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disable XML RPC Pingback processing.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'not recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- disable self pings (xmlrpc_noselfpings) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable Self Pings?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_xmlrpc_noselfpings" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'xmlrpc_noselfpings', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disable Pingbacks from this site to itself.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- close XML RPC option table ---
			echo '</table>' . PHP_EOL;

			// --- pro method restriction options ---
			// 0.9.1: [PRO] XML RPC Method Restriction Options
			// 0.9.4: use do_action instead of function_exists
			do_action( 'forcefield_method_options' );


			// --------
			// REST API
			// --------

			// --- table heading ---
			echo '<table><tr><td colspan="5"><h3 style="margin-bottom:10px;">REST API</h3></td></tr>' . PHP_EOL;

			// --- disable REST API (restapi_disable) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable REST API?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_disable" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_disable', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disable REST API entirely.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'not recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- logged in users only (restapi_authonly) ---
			echo '<tr><td><b>' . esc_html( __( 'Logged In Users Only?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="restapi_authonly" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_authonly', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'REST API access for authenticated users only.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'recommended', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- require SSL (restapi_requiressl) ---
			echo '<tr><td><b>' . esc_html( __( 'Require SSL for REST API?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_requiressl" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_requiressl', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Only allow REST API access via SSL.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- rate limiting for REST (restapi_slowdown) ---
			echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Rate Limit REST API?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_restapi_slowdown" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_slowdown', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td class="valigntop">' . esc_html( __( 'Slowdown via a Rate Limiting Delay.', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td>(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- role restrict REST API access (restapi_restricted) ---
			echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Restrict REST API Access?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="valigntop checkbox-cell"><input type="checkbox" name="ff_restapi_restricted" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_restricted', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td class="valigntop">' . esc_html( __( 'Restrict REST API Access to Selected Roles.', 'forcefield' ) ) . '<br>';
			echo esc_html( __( 'Note: Enforces Logged In Only Access', 'forcefield' ) ) . '</td>';
			echo '<td width="10"></td><td class="valigntop">(' . esc_html( __( 'optional', 'forcefield' ) ) . ')</td></tr>' . PHP_EOL;

			// --- roles for REST API role restriction (restapi_roles) ---
			$restroles = forcefield_get_setting( 'restapi_roles', false );
			if ( !is_array( $restroles ) ) {
				$restroles = array();
			}
			echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Restrict to Selected Roles', 'forcefield' ) ) . '</b></td>';
			echo '<td width="20"></td><td></td><td></td>';
			echo '<td class="valigntop" colspan="3">';
			foreach ( $roles as $slug => $label ) {
				echo '<div class="role-box">';
				echo '<input type="checkbox" name="ff_restapi_role_' . esc_attr( $slug ) . '" value="yes"';
				if ( in_array( $slug, $restroles ) ) {
					echo ' checked="checked"';
				}
				echo '> ' . esc_html( $label ) . '</div>';
			}
			echo '</td></tr>' . PHP_EOL;

			// --- disable User List Endpoint (restapi_nouserlist) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable Userlist Endpoint?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nouserlist" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_nouserlist', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disable the REST API User Enumeration Endpoint.', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

			// --- disable REST API Links (restapi_nolinks) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable REST API Links?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nolinks" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_nolinks', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Remove REST Discovery Links from Page Head.', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

			// --- disable JSONP for REST API (restapi_nojsonp) ---
			echo '<tr><td><b>' . esc_html( __( 'Disable JSONP for REST API?', 'forcefield' ) ) . '</b></td><td width="20"></td>';
			echo '<td class="checkbox-cell"><input type="checkbox" name="ff_restapi_nojsonp" value="yes"';
			if ( 'yes' == forcefield_get_setting( 'restapi_nojsonp', false ) ) {
				echo ' checked="checked"';
			}
			echo '></td><td width="10"></td>';
			echo '<td>' . esc_html( __( 'Disables JSONP Output for the REST API.', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

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
			echo '</table>' . PHP_EOL;

			// --- pro endpoint restriction options ---
			// 0.9.1: [PRO] Output Endpoint Restriction Options
			// 0.9.4: use do_action instead of function_exists
			do_action( 'forcefield_endpoint_options' );

		// --- close APIs tab ---
		echo '</div>' . PHP_EOL;


		// =====================
		// Vulnerability Checker
		// =====================
		// 0.9.8: added vulnerability checker options
		// 1.0.1: temporarily disabled if module not present
		if ( file_exists( FORCEFIELD_DIR . '/forcefield-vuln.php' ) ) {
			echo '<div id="vuln-checker"';
			if ( 'vuln-checker' != $currenttab ) {
				echo ' style="display:none;"';
			}
			echo '>' . PHP_EOL;

				// --- table heading ---
				echo '<table><tr><td colspan="3"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Vulnerability Checker', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

				// 1.0.1: add required message for API token key (for wpvulndb API v3)
				echo '<tr><td colspan="3">' . esc_html( __( 'An API Key is required to use the Vulnerability Checker.', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

				// --- vulnerability checker API key (vuln_api_token) ---
				echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'WPVulnDB API Key', 'forcefield' ) ) . '</b><br>';

					// --- check API token ---
					// 1.0.5: output verified HTML inline instead of storing
					$token = forcefield_get_setting( 'vuln_api_token', false );
					if ( $token && ( '' != trim( $token ) ) ) {
						$args = array(
							'timeout'		=> 5,
							'user-agent'	=> 'WordPress/' . $wp_version . '; ' . home_url(),
							'headers' 		=> array( 'Authorization: Token token=' . $token ),
						);
						$testurl = 'https://wpvulndb.com/api/v3/wordpresses/4910';
						$response = forcefield_get_response_data( $testurl, $args );
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions
						echo "<!-- API Response: " . esc_html( print_r( $response, true ) ) . " -->" . PHP_EOL;
						if ( !$response || ( isset( $response['error'] ) && strstr( $response['error'], 'HTTP Token: Access denied.' ) ) ) {
							$crossurl = plugins_url( 'images/cross.png', __FILE__ );
							echo "<img style='display:inline;' src='" . esc_url( $crossurl ) . "'>";
							echo " <span style='color:#d50000;'>" . esc_html( __( 'API Key Unverified', 'forcefield' ) ) . "</span>";
							delete_option( 'forcefield_wbvulndb_verified' );
						} else {
							$tickurl = plugins_url( 'images/tick.png', __FILE__ );
							echo "<img style='display:inline;' src='" . esc_url( $tickurl ) . "'>";
							echo " <span style='color:#00d500;'>" . esc_html( __( 'API Key Verified', 'forcefield' ) ) . "</span>";}
							update_option( 'forcefield_wbvulndb_verified', true );
					}
					echo "<!-- Token Verified: " . esc_html( get_option( 'forcefield_wbvulndb_verified' ) ) . " -->";

				echo '</td><td width="20"></td>';
				echo '<td colspan="5" style="vertical-align:top">';
				// 1.0.4: added missing esc_attr wrapper for token
				echo '<input type="text" name="ff_vuln_api_token" value="' . esc_attr( $token ) . '" style="width:100%;margin-top:10px;">';
				// 1.0.1: remove optional message for API token key (no longer optional for API v3)
				echo '<br>' . esc_html( __( 'Key for the WP Vulnerability Database API', 'forcefield' ) );
				echo ' <a href="https://wpvulndb.com/api/" target=_blank>' . esc_html( __( 'Get One', 'forcefield' ) ) . '</a>';
				echo '</td></tr>' . PHP_EOL;

				// --- core vulnerability check frequency (vuln_check_core) ---
				echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Core Checkups', 'forcefield' ) ) . '</b></td>';
				echo '<td width="20"></td><td colspan="5"><select name="ff_vuln_check_core">';
				echo '<option value="off">' . esc_html( __( 'Off', 'forcefield' ) ) . '</option>';
				$frequency = forcefield_get_setting( 'vuln_check_core', false );
				foreach ( $intervals as $key => $interval ) {
					echo '<option value="' . esc_attr( $key ) . '"';
					// 1.0.4: simplify selected to direct output
					if ( $frequency == $key ) {
						echo ' selected="selected"';
					}
					echo '>' . esc_html( $interval['display'] ) . '</option>';
				}
				echo '</select><div style="margin-left:20px; display:inline-block;">';
				echo esc_html( __( 'How often core vulnerabilities are checked.', 'forcefield' ) ) . '</div></td></tr>' . PHP_EOL;

				// --- plugin vulnerability check frequency (vuln_check_plugins) ---
				echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Plugin Checkups', 'forcefield' ) ) . '</b></td>';
				echo '<td width="20"></td><td colspan="5"><select name="ff_vuln_check_plugins">';
				echo '<option value="off">' . esc_html( __( 'Off', 'forcefield' ) ) . '</option>';
				$frequency = forcefield_get_setting( 'vuln_check_plugins', false );
				foreach ( $intervals as $key => $interval ) {
					echo '<option value="' . esc_attr( $key ) . '"';
					// 1.0.4: simplify selected to direct output
					if ( $frequency == $key ) {
						echo ' selected="selected"';
					}
					echo '>' . esc_html( $interval['display'] ) . '</option>';
				}
				echo '</select><div style="margin-left:20px; display:inline-block;">';
				echo esc_html( __( 'How often plugin vulnerabilities are checked.', 'forcefield' ) ) . '</div></td></tr>' . PHP_EOL;

				// --- theme vulnerability check frequency (vuln_check_themes) ---
				echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Theme Checkups', 'forcefield' ) ) . '</b></td>';
				echo '<td width="20"></td><td colspan="5"><select name="ff_vuln_check_themes">';
				echo '<option value="off">' . esc_html( __( 'Off', 'forcefield' ) ) . '</option>';
				$frequency = forcefield_get_setting( 'vuln_check_themes', false );
				foreach ( $intervals as $key => $interval ) {
					echo '<option value="' . esc_attr( $key ) . '"';
					// 1.0.4: simplify selected to direct output
					if ( $frequency == $key ) {
						echo ' selected="selected"';
					}
					echo '>' . esc_html( $interval['display'] ) . '</option>';
				}
				echo '</select><div style="margin-left:20px; display:inline-block;">';
				echo esc_html( __( 'How often theme vulnerabilities are checked.', 'forcefield' ) ) . '</div></td></tr>' . PHP_EOL;

				// --- core vulnerability alert email addresses (vuln_core_emails) ---
				// 1.0.4: added missing esc_attr wrapper
				$coreemails = forcefield_get_setting( 'vuln_core_emails', false );
				echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'Core Alert Emails', 'forcefield' ) ) . '</b></td><td width="20"></td>';
				echo '<td colspan="3"><input type="text" name="ff_vuln_core_emails" value="' . esc_attr( $coreemails ) . '" style="width:100%;margin-top:10px;"></td>';
				echo '</tr>' . PHP_EOL;

				// --- plugin vulnerability alert email addresses (vuln_plugin_emails) ---
				// 1.0.4: added missing esc_attr wrapper
				$pluginemails = forcefield_get_setting( 'vuln_plugin_emails', false );
				echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'Plugin Alert Emails', 'forcefield' ) ) . '</b></td><td width="20"></td>';
				echo '<td colspan="3"><input type="text" name="ff_vuln_plugin_emails" value="' . esc_attr( $pluginemails ) . '" style="width:100%;margin-top:10px;"></td>';
				echo '</tr>' . PHP_EOL;

				// --- core vulnerability alert email addresses (vuln_theme_emails) ---
				// 1.0.4: added missing esc_attr wrapper
				$themeemails = forcefield_get_setting( 'vuln_theme_emails', false );
				echo '<tr><td style="vertical-align:top;padding-top:10px;"><b>' . esc_html( __( 'Theme Alert Emails', 'forcefield' ) ) . '</b></td><td width="20"></td>';
				echo '<td colspan="3"><input type="text" name="ff_vuln_theme_emails" value="' . esc_attr( $themeemails ) . '" style="width:100%;margin-top:10px;"></td>';
				echo '</tr>' . PHP_EOL;

				// --- close vulnerability checker table ---
				echo '</table>' . PHP_EOL;

			// --- close vulnerability checker tab ---
			echo '</div>' . PHP_EOL;
		}

	// 0.9.6: removed auto updates options display

	// ============
	// IP Blocklist
	// ============

	echo '<div id="ip-blocklist" style="min-height:500px;';
	if ( 'ip-blocklist' != $currenttab ) {
		echo ' display:none;';
	}
	echo '">';

	// --- blocklist heading ---
	echo '<h3 style="margin-bottom:10px;">' . esc_html( __( 'Blocklists', 'forcefield' ) ) . '</h3>' . PHP_EOL;

	echo '<table>';

		// --- Blocklist Expiries ---
		echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Blocklist Expiries', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

		// --- token expiry time ---
		echo '<tr><td><b>' . esc_html( __( 'Action Token Expiry', 'forcefield' ) ) . '</b></td><td width="20"></td>';
		$expiry = forcefield_get_setting( 'blocklist_tokenexpiry', false );
		echo '<td><input style="width:50px;" type="number" name="ff_blocklist_tokenexpiry" min="1" value="' . esc_attr( $expiry ) . '"></td>';
		echo '<td width="10"></td>';
		echo '<td>' . esc_html( __( 'Length of time that action tokens are valid.', 'forcefield' ) ) . '</td>';
		echo '<td width="10"></td><td>(' . esc_html( __( 'default', 'forcefield' ) ) . ': 300s)</td></tr>' . PHP_EOL;

		// --- blocklist expiry time (blocklist_cooldown) ---
		echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Block Cooldown Time', 'forcefield' ) ) . '</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_cooldown">';
		echo '<option value="none">' . esc_html( __( 'None', 'forcefield' ) ) . '</option>';
		$cooldown = forcefield_get_setting( 'blocklist_cooldown', false );
		foreach ( $intervals as $key => $interval ) {
			echo '<option value="' . esc_attr( $key ) . '"';
			// 1.0.4: simplify selected to direct output
			if ( $cooldown == $key ) {
				echo ' selected="selected"';
			}
			echo '>' . esc_html( $interval['display'] ) . '</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo esc_html( __( 'How often trangressions are reduced over time.', 'forcefield' ) ) . '</div></td></tr>';

		// --- blocklist expiry time (blocklist_expiry) ---
		echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Block Expiry Time', 'forcefield' ) ) . '</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_expiry">';
		echo '<option value="none">' . esc_html( __( 'None', 'forcefield' ) ) . '</option>';
		$expiry = forcefield_get_setting( 'blocklist_expiry', false );
		foreach ( $intervals as $key => $interval ) {
			echo '<option value="' . esc_attr( $key ) . '"';
			// 1.0.4: simplify selected to direct output
			if ( $expiry == $key ) {
				echo ' selected="selected"';
			}
			echo '>' . esc_html( $interval['display'] ) . '</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo esc_html( __( 'How long before an IP block expires.', 'forcefield' ) ) . '</div></td></tr>' . PHP_EOL;

		// --- blocklist delete time (blocklist_delete) ---
		echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Block Delete Time', 'forcefield' ) ) . '</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_delete">';
		echo '<option value="none">' . esc_html( __( 'None', 'forcefield' ) ) . '</option>';
		$delete = forcefield_get_setting( 'blocklist_delete', false );
		foreach ( $intervals as $key => $interval ) {
			echo '<option value="' . esc_attr( $key ) . '"';
			// 1.0.4: simplify selected to direct output
			if ( $delete == $key ) {
				echo ' selected="selected"';
			}
			echo '>' . esc_html( $interval['display'] ) . '</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo esc_html( __( 'How long before an IP record is deleted.', 'forcefield' ) ) . '</div></td></tr>' . PHP_EOL;

		// --- blocklist cleanup frequency (blocklist_cleanups) ---
		echo '<tr><td class="valigntop"><b>' . esc_html( __( 'CRON Cleanups', 'forcefield' ) ) . '</b></td>';
		echo '<td width="20"></td><td colspan="5"><select name="ff_blocklist_cleanups">';
		echo '<option value="none">' . esc_html( __( 'None', 'forcefield' ) ) . '</option>';
		$cleanup = forcefield_get_setting( 'blocklist_cleanups', false );
		foreach ( $intervals as $key => $interval ) {
			echo '<option value="' . esc_attr( $key ) . '"';
			// 1.0.4: simplify selected to direct output
			if ( $cleanup == $key ) {
				echo ' selected="selected"';
			}
			echo '>' . esc_html( $interval['display'] ) . '</option>';
		}
		echo '</select><div style="margin-left:20px; display:inline-block;">';
		echo esc_html( __( 'How often blocklist cleanups are scheduled.', 'forcefield' ) ) . '</div></td></tr>' . PHP_EOL;

		// --- IP Lists ---
		echo '<tr height="10"><td> </td></tr>';
		echo '<tr><td colspan="5"><h3 style="margin-bottom:10px;">' . esc_html( __( 'Manual IP Lists', 'forcefield' ) ) . '</h3></td></tr>' . PHP_EOL;

		// --- IP Whitelist (textarea) ---
		$ipwhitelist = forcefield_get_setting( 'blocklist_whitelist', false );
		if ( is_array( $ipwhitelist ) ) {
			$ipwhitelist = implode( "\n", $ipwhitelist );
		} else {
			$ipwhitelist = '';
		}
		// 1.0.4: added esc_textarea to whitelist content
		echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Manual IP Whitelist', 'forcefield' ) ) . '</b></td><td width="20"></td>';
		echo '<td colspan="3"><textarea class="ip-textarea" rows="4" name="ff_blocklist_whitelist">' . esc_textarea( $ipwhitelist ) . '</textarea></td>';
		echo '<td></td><td>' . esc_html( __( 'comma and/or line separated', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

		// --- IP Blacklist (textarea) ---
		echo '<tr height="10"><td> </td></tr>';
		$ipblacklist = forcefield_get_setting( 'blocklist_blacklist', false );
		if ( is_array( $ipblacklist ) ) {
			$ipblacklist = implode( "\n", $ipblacklist );
		} else {
			$ipblacklist = '';
		}
		// 1.0.4: use esc_textarea on blacklist setting content
		echo '<tr><td class="valigntop"><b>' . esc_html( __( 'Manual IP Blacklist', 'forcefield' ) ) . '</b></td><td width="20"></td>';
		echo '<td colspan="3"><textarea class="ip-textarea" rows="4" name="ff_blocklist_blacklist">' . esc_textarea( $ipblacklist ) . '</textarea></td>';
		echo '<td></td><td>' . esc_html( __( 'comma and/or line separated', 'forcefield' ) ) . '</td></tr>' . PHP_EOL;

	echo '</table>';

	// --- maybe load Pro whitelist/blocklist interface ---
	// 0.9.2: [PRO] Manual IP List Interface
	// 0.9.4: use do_action instead of function_exists
	do_action( 'forcefield_lists_interface' );

	// --- blocklist heading ---
	echo '<h3 style="margin-bottom:10px;">' . esc_html( __( 'IP Blocklist', 'forcefield' ) ) . '</h3>' . PHP_EOL;

	// --- get blocklist records ---
	$reasons = forcefield_blocklist_get_reasons();
	$columns = array( 'ip', 'label', 'transgressions', 'last_access_at', 'created_at' );
	// note other columns: 'id', 'list', 'ip6', 'is_range', 'deleted_at'
	// 0.9.6: fix to switch of argument 1 and 3 in function
	$blocklist = forcefield_blocklist_get_records( $columns, false, false, true );

	// --- check for blocklist ---
	// 0.9.1: output IP Blocklist with removal buttons
	if ( $blocklist && ( count( $blocklist ) > 0 ) ) {

		// --- clear entire blocklist button ---
		// 1.0.2: change from form to button trigger to prevent form within form
		echo '<div style="width:100%;text-align:center;">';
		// echo '<form action="' . esc_url( $adminajax ) . '" target="blocklist-action-frame">';
		// echo '<input type="hidden" name="action" value="forcefield_blocklist_clear">';
		// wp_nonce_field( 'forcefield-clear' );
		echo '<input type="button" class="button-secondary" value="' . esc_attr( __( 'Clear Entire IP Blocklist', 'forcefield' ) ) . '" onclick="forcefield_blocklist_clear();">';
		// echo '</form>';
		echo '</div><br>' . PHP_EOL;

		// TODO: add sortable columns and/or pagination
		// - group records by IP address to show activity ?
		// - group records by activity to show patterns ?

		echo '<div id="blocklist-table"><table><tr>' . PHP_EOL;
		echo '<td><b>' . esc_html( __( 'IP Address', 'forcefield' ) ) . '</b></td><td width="10"></td>' . PHP_EOL;
		echo '<td><b>' . esc_html( __( 'Block Reason', 'forcefield' ) ) . '</b></td><td width="10"></td>' . PHP_EOL;
		echo '<td><b>#</b></td><td width="10"></td>' . PHP_EOL;
		echo '<td><b>' . esc_html( __( 'Blocked?', 'forcefield' ) ) . '</b></td><td width="10"></td>' . PHP_EOL;
		echo '<td><b>' . esc_html( __( 'First Access', 'forcefield' ) ) . '</b></td><td width="10"></td>' . PHP_EOL;
		echo '<td><b>' . esc_html( __( 'Last Access', 'forcefield' ) ) . '</b></td><td width="10"></td>' . PHP_EOL;
		echo '<td></td></tr>' . PHP_EOL;

		// --- output blocklist rows ---
		foreach ( $blocklist as $i => $row ) {

			// --- check if IP blocked ---
			$limit = forcefield_get_setting( 'limit_' . $row['label'], false );
			$blocked = false;
			if ( $row['transgressions'] > $limit ) {
				$blocked = true;
			}

			// --- blocklist row ---
			// 1.0.0: added row list ID and IP class
			$ipclass = str_replace( '.', '-', $row['ip'] );
			echo '<tr id="blocklist-row-' . esc_attr( $i ) . '" class="ip-' . esc_attr( $ipclass ) . '">' . PHP_EOL;

				// --- IP Address ---
				echo '<td>' . esc_html( $row['ip'] ) . '</td><td></td>' . PHP_EOL;

				// --- record reason ---
				echo '<td><div title="' . esc_attr( $reasons[$row['label']] ) . '">' . esc_html( $row['label'] ) . '</div></td><td></td>' . PHP_EOL;

				// --- number of transgressions ---
				echo '<td>' . esc_html( $row['transgressions'] ) . '</td><td></td>' . PHP_EOL;

				// --- red X indicates blocked IP ---
				echo '<td>';
					if ( $blocked ) {
						echo '<font color="#E00;">' . esc_html( __( 'Blocked', 'forcefield' ) ) . '</font>' . PHP_EOL;
					}
				echo '</td><td></td>' . PHP_EOL;

				// --- record created time date ---
				echo '<td>';
					$display = date( 'd-m-y', $row['created_at'] );
					$title = date( 'H:i:s d-m-Y', $row['created_at'] );
					echo '<div title="' . esc_attr( $title ) . '">' . esc_html( $display ) . '</div>' . PHP_EOL;
				echo '</td><td></td>' . PHP_EOL;

				// --- last access time date ---
				echo '<td>';
					$display = date( 'd-m-y', $row['last_access_at'] );
					$title = date( 'H:i:s d-m-Y', $row['last_access_at'] );
					echo '<div title="' . esc_attr( $title ) . '">' . esc_html( $display ) . '</div>';
				echo '</td><td></td>' . PHP_EOL;

				// --- record row removal button ---
				echo '<td><input type="button" value="X" class="button-secondary" title="' . esc_attr( __( 'Delete Record', 'forcefield' ) ) . '" onclick="forcefield_unblock_ip(\'' . esc_attr( $row['ip'] ) . '\',\'' . esc_attr( $row['label'] ) . '\',\'' . esc_attr( $i ) . '\');"></td>' . PHP_EOL;

				// --- full IP unblock button ---
				echo '<td>';
					if ( $blocked ) {
						echo '<input type="button" class="button-secondary" value="' . esc_attr( __( 'Unblock', 'forcefield' ) ) . '" title="' . esc_attr( __( 'Unblock this IP Address', 'forcefield' ) ) . '" onclick="forcefield_unblock_ip(\'' . esc_attr( $row['ip'] ) . '\',false,false);">';
					}
				echo '</td>' . PHP_EOL;

			echo '</tr>' . PHP_EOL;
		}

		// --- close IP blocklist table ---
		echo '</table></div>' . PHP_EOL;

	} else {
		// 1.0.2: change no transgressions yet message
		echo '<b>' . esc_html( __( 'No current IP transgression records to show.', 'forcefield' ) ) . '</b><br>' . PHP_EOL;
	}

	// --- blocklist action iframe ---
	// 1.0.1: use about:blank instead of javascript:void(0)
	echo '<iframe src="about:blank" id="blocklist-action-frame" name="blocklist-action-frame" style="display:none;" frameborder="0"></iframe>' . PHP_EOL;

	// --- close IP blocklist div ---
	echo '</div>' . PHP_EOL;

	// ------------------------
	// Reset and Update Buttons
	// ------------------------
	echo '<div id="update-buttons"><br><table style="width:100%;">' . PHP_EOL;

		// --- Reset Button ---
		echo '<tr><td width="33%" style="text-align:right;">' . PHP_EOL;
			// 0.9.7: fix to incorrect javascript function (returntodefaults)
			// 0.9.8: change type from submit to button so enter = save
			echo '<input type="button" value="' . esc_attr( __( 'Reset to Defaults', 'forcefield' ) ) . '" class="button-secondary" onclick="return forcefield_reset();" style="margin-right:20px;">' . PHP_EOL;

		echo '</td>' . PHP_EOL;

		// --- middle spacer ---
		echo '<td width="33%"> </td>' . PHP_EOL;

		// --- Update Submit Button ---
		echo '<td width="33%" style="text-align:center;">' . PHP_EOL;
			// 0.9.7: added missing ID tag for sidebar button trigger
			echo '<input id="plugin-settings-save" type="submit" class="button-primary" value="' . esc_attr( __( 'Update Settings', 'forcefield' ) ) . '">' . PHP_EOL;
		echo '</td></tr>' . PHP_EOL;

	// --- close update buttons div ---
	echo '</table><br></div>' . PHP_EOL;

	// --- close main settings form ---
	echo '</form>' . PHP_EOL;

	// --- close #wrapbox ---
	echo '</div></div>' . PHP_EOL;

	// --- close #pagewrap ---
	echo '</div>' . PHP_EOL;

}
