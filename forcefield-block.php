<?php

// ===================================
// === ForceField Blocklist Module ===
// ===================================

// === IP Blocklist ===
// - IP Whitelist Check
// - IP Blacklist Check
// - Check IP Blocklist
// - Create IP Blocklist Table
// - Set Blocklist Table Variables
// - Check Blocklist Table Exists
// - Clear IP Blocklist Table
// - Check IP in Blocklist
// - Get IP Blocklist Records
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


// --------------------
// === IP Blocklist ===
// --------------------

// ------------------
// IP Whitelist Check
// ------------------
function forcefield_whitelist_check( $context, $ip = false ) {

	global $forcefield;
	if ( !$ip ) {
		if ( !isset( $forcefield['ip'] ) ) {
			$forcefield['ip'] = forcefield_get_remote_ip();
		}
		$ip = $forcefield['ip'];
	}

	// --- check permanent whitelist (no context) ---
	$whitelist = forcefield_get_setting( 'blocklist_whitelist' );
	if ( is_array( $whitelist ) ) {
		if ( in_array( $ip, $whitelist ) ) {
			return true;
		}
		if ( count( $whitelist ) > 0 ) {
			foreach ( $whitelist as $ipaddress ) {
				if ( ( strstr( $ipaddress, '*' ) ) || ( strstr( $ipaddress, '-' ) ) ) {
					if ( forcefield_is_ip_in_range( $ip, $ipaddress ) ) {
						return true;
					}
				}
			}
		}
	}

	// --- maybe check Pro whitelist ---
	// 0.9.2: [PRO] maybe check for manual whitelist table records
	// 0.9.8: use apply_filters instead of function_exists
	$whitelisted = apply_filters( 'forcefield_whitelist_check', false, $context, $ip );
	return $whitelisted;
}

// ------------------
// IP Blacklist Check
// ------------------
// 0.9.7: fix to variable typo (ontext?!)
function forcefield_blacklist_check( $context, $ip = false ) {

	global $forcefield;
	if ( !$ip ) {
		if ( !isset( $forcefield['ip'] ) ) {
			$forcefield['ip'] = forcefield_get_remote_ip();
		}
		$ip = $forcefield['ip'];
	}

	// --- permanent blacklist check (no context) ---
	if ( !isset( $forcefield['blacklistchecked'] ) ) {
		$blacklist = forcefield_get_setting( 'blocklist_blacklist' );
		if ( is_array( $blacklist ) ) {
			if ( in_array( $ip, $blacklist ) ) {
				return true;
			}
			if ( count( $blacklist ) > 0 ) {
				foreach ( $blacklist as $ipaddress ) {
					if ( strstr( $ipaddress, '*' ) || strstr( $ipaddress, '-' ) ) {
						if ( forcefield_is_ip_in_range( $ip, $ipaddress ) ) {
							return true;
						}
					}
				}
			}
		}
		$forcefield['blacklistchecked'] = true;
	}

	// --- maybe check Pro whitelist records ---
	// 0.9.2: [PRO] maybe check for manual whitelist records
	// 0.9.8: use apply_filters instead of function_exists
	$blacklisted = apply_filters( 'forcefield_blacklist_check', false, $context, $ip );
	return $blacklisted;
}

// ------------------
// Check IP Blocklist
// ------------------
// 0.9.6: change to priority of 1 to allow for other actions
add_action( 'plugins_loaded', 'forcefield_blocklist_check', 1 );
function forcefield_blocklist_check() {

	global $forcefield;

	// --- get custom table values ---
	// 0.9.2: initiate table variables right away
	// 0.9.6: moved here from plugin setup section
	$forcefield = forcefield_blocklist_table_init();

	// --- get remote IP ---
	// 0.9.2: get current remote IP right away
	// 0.9.6: moved here from plugin setup section
	$forcefield['ip'] = forcefield_get_remote_ip();

	// --- check if IP is in manual whitelist (exact or range) ---
	if ( forcefield_whitelist_check( 'site', $forcefield['ip'] ) ) {
		return;
	}

	// ---- check if IP is in manual blacklist (exact or range) ---
	if ( forcefield_blacklist_check( 'site', $forcefield['ip'] ) ) {
		forcefield_forbidden_exit();
	}

	// --- maybe auto delete old blocklist records for this IP ---
	forcefield_blocklist_table_cleanup( false, $forcefield['ip'] );

	// --- check for remaining blocklist records for this IP ---
	$records = forcefield_blocklist_check_ip( $forcefield['ip'] );
	if ( $records && is_array( $records ) && ( count( $records ) > 0 ) ) {
		foreach ( $records as $record ) {

			// --- check block cool down ---
			// 0.9.1: check the cooldown period for this block
			$cooldown = forcefield_get_setting( 'blocklist_cooldown' );
			if ( 'yes' === (string) $cooldown ) {
				$record = forcefield_blocklist_cooldown( $record );
			}

			// --- check the block reason is still enforced ---
			$enforced = true;
			$reason = $record['label'];
			// 0.9.5: added buddypress registration token to conditions
			if ( ( ( 'admin_bad' == $reason ) && ( 'yes' != forcefield_get_setting( 'admin_block' ) ) )
			  || ( ( 'xmlrpc_login' == $reason ) && ( 'yes' != forcefield_get_setting( 'xmlrpc_authban' ) ) )
			  || ( ( 'no_login_token' == $reason ) && ( 'yes' != forcefield_get_setting( 'login_token' ) ) )
			  || ( ( 'no_register_token' == $reason ) && ( 'yes' != forcefield_get_setting( 'register_token' ) ) )
			  || ( ( 'no_signup_token' == $reason ) && ( 'yes' != forcefield_get_setting( 'signup_token' ) ) )
			  || ( ( 'no_lostpass_token' == $reason ) && ( 'yes' != forcefield_get_setting( 'lostpass_token' ) ) )
			  || ( ( 'no_comment_token' == $reason ) && ( 'yes' != forcefield_get_setting( 'comment_token' ) ) )
			  || ( ( 'no_buddypress_token' == $reason ) && ( 'yes' != forcefield_get_setting( 'buddypress_token' ) ) ) ) {
				$enforced = false;
			}
			// note exception: for "no_referer" just check transgression limit regardless

			if ( $enforced ) {

				// --- check transgressions ---
				// 0.9.7: fix for undefined transgression count
				$transgressions = $record['transgressions'];
				$blocked = forcefield_blocklist_check_transgressions( $reason, $transgressions );
				if ( $blocked ) {

					// --- set page to not be cached ---
					// 1.0.0: added no page cache constant
					if ( defined( 'DONOTCACHEPAGE' ) ) {
						define( 'DONOTCACHEPAGE', true );
					}

					// --- transgressions are over limit! ---
					// status_header('403', 'HTTP/1.1 403 Forbidden');
					// 1.0.0: added no cache headers
					header( "HTTP/1.1 403 Forbidden" );
					header( "Status: 403 Forbidden" );
					header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
					header( "Cache-Control: post-check=0, pre-check=0", false );
					header( "Pragma: no-cache" );

					// --- maybe allow manual unblocking ---
					$unblocking = forcefield_get_setting( 'blocklist_unblocking' );
					if ( 'yes' === (string) $unblocking ) {
						// 0.9.1: output manual unblocking request form
						forcefield_blocklist_unblock_form_output();
					} else {
						header( "Connection: Close" );
					}
					exit;
				}
			}
		}
	}
}

// ---------------------------------------
// Create IP Blocklist Table on Activation
// ---------------------------------------
// 0.9.1: create an IP blocklist table
register_activation_hook( __FILE__, 'forcefield_blocklist_table_create' );
function forcefield_blocklist_table_create() {

	global $wpdb, $forcefield;

	// Note: IP Transgression Table Structure based on Shield Plugin (wp-simple-firewall)
	// Ref: https://www.icontrolwp.com/blog/wordpress-security-plugin-update-automatically-block-malicious-visitors/

	// 0.9.7: fix for missed plugin activation check
	if ( !isset( $forcefield['table'] ) ) {
		$forcefield['table'] = $wpdb->prefix . 'forcefield_ips';
	}
	if ( !isset( $forcefield['charset'] ) ) {
		$forcefield['charset'] = $wpdb->get_charset_collate();
	}

	// --- create table query ---
	// 0.9.7: fix for mismatch table name key on new installs
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$checktable = $wpdb->get_var( "SHOW TABLES LIKE '" . $forcefield['table'] . "'" );
	if ( $checktable != $forcefield['table'] ) {

		// --- load dbDelta function ---
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// --- set create table query ---
		$query = "CREATE TABLE " . $forcefield['table'] . " (
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
		) " . $forcefield['charset'] . ";";
		// 0.9.7: use neither sprintf or wpdb->prepare method (as adding single quotes breaking query!)
		// $query = $wpdb->prepare($query, array($forcefield['table'], $forcefield['charset']));

		// --- execute create table query ---
		dbDelta( $query );
	}
}

// -----------------------------
// Set Blocklist Table Variables
// -----------------------------
// 0.9.1: set blocklist table variables
function forcefield_blocklist_table_init() {

	global $wpdb, $forcefield;
	$forcefield['table'] = $wpdb->prefix . 'forcefield_ips';
	$forcefield['charset'] = $wpdb->get_charset_collate();

	// --- check table and maybe auto-create ---
	$check = forcefield_blocklist_check_table();
	if ( !$check ) {
		forcefield_blocklist_table_create();
	}

	return $forcefield;
}

// ----------------------------
// Check Blocklist Table Exists
// ----------------------------
// 0.9.1: check blocklist table exists
function forcefield_blocklist_check_table() {
	global $wpdb, $forcefield;
	$tablequery = "SHOW TABLES LIKE '" . $forcefield['table'] . "'";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$checktable = $wpdb->get_var( $tablequery );
	if ( $checktable != $forcefield['table'] ) {
		return false;
	}
	return true;
}

// ------------------------
// Clear IP Blocklist Table
// ------------------------
// 0.9.1: to clear the entire blocklist table
function forcefield_blocklist_clear_table() {
	global $wpdb, $forcefield;
	$query = "DELETE FROM " . $forcefield['table'] . " WHERE `list` = 'AB'";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$delete = $wpdb->query( $query );
	return $delete;
}

// ---------------------
// Check IP in Blocklist
// ---------------------
// 0.9.1: check if IP is in blocklist table
function forcefield_blocklist_check_ip( $ip ) {
	$columns = array( 'label', 'transgressions', 'last_access_at' );
	return forcefield_blocklist_get_records( $columns, $ip );
}

// ------------------------
// Get IP Blocklist Records
// ------------------------
// 0.9.1: use blocklist table
function forcefield_blocklist_get_records( $columns = array(), $ip = false, $reason = false, $noexpired = true ) {

	global $wpdb, $forcefield;

	$where = '';
	if ( empty( $columns ) ) {
		$columnquery = '*';
	} else {
		$columnquery = implode( ',', $columns );
	}
	if ( $noexpired ) {
		$where = "WHERE `deleted_at` = 0";
	}
	if ( $ip ) {
		// 1.0.4: removed single quotes from %s placeholders
		if ( '' == $where ) {
			$where = $wpdb->prepare( "WHERE `ip` = %s", $ip );
		} else {
			$where .= $wpdb->prepare( " AND `ip` = %s", $ip );
		}
	}
	if ( $reason ) {
		// 0.9.5: fix to handle reason without specific IP address
		// 1.0.4: removed single quotes from %s placeholders
		if ( '' == $where ) {
			$where .= $wpdb->prepare( " WHERE `label` = %s", $reason );
		} else {
			$where .= $wpdb->prepare( " AND `label` = %s", $reason );
		}
	}
	$query = "SELECT " . $columnquery . " FROM " . $forcefield['table'] . " " . $where;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $forcefield['debug'] ) && $forcefield['debug'] ) {
		echo "<!-- Blocklist Query: " . esc_html( $query ) . " -->" . PHP_EOL;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( $query, ARRAY_A );
	if ( is_array( $results ) && ( count( $results ) > 0 ) ) {
		return $results;
	}
	return array();
}

// -------------------------------
// Add/Update an IP Address Record
// -------------------------------
// 0.9.1: use blocklist table
function forcefield_blocklist_record_ip( $reason, $ip = false ) {

	// 0.9.6: fix to incorrect global declaration
	// 0.9.6: check for IP and bug out if not valid
	global $wpdb, $forcefield;
	if ( !$ip && isset( $forcefield['ip'] ) ) {
		$ip = $forcefield['ip'];
	} else {
		$ip = forcefield_get_remote_ip();
	}
	if ( !$ip ) {
		return false;
	}
	$time = time();

	// --- check for existing trangression of this type ---
	$columns = array( 'id', 'label', 'transgressions', 'deleted_at' );
	$records = forcefield_blocklist_get_records( $columns, $ip, $reason, false );
	if ( $records ) {
		foreach ( $records as $record ) {
			if ( $record['label'] == $reason ) {
				// --- increment transgression count ---
				// 0.9.6: fix to (multiple!) incorrect variable names
				$transgressions = $record['transgressions'];
				$record['transgressions'] = $transgressions++;
				$record['last_access_at'] = $time;
				$record['deleted_at'] = 0;
				$where = array( 'id' => $record['id'] );
				$update = $wpdb->update( $forcefield['table'], $record, $where );
				return $update;
			}
		}
	}

	// --- add new IP address record to Blocklist Table ---
	// 1.0.4: simplified logic to single line
	$ip6 = ( 'ip6' == forcefield_get_ip_type( $ip ) ) ? 1 : 0;
	$record = array(
		'ip' 				=> $ip,
		'label'				=> $reason,
		'list' 				=> 'AB',
		'ip6' 				=> $ip6,
		'transgressions' 	=> 1,
		'is_range' 			=> 0,
		'last_access_at' 	=> $time,
		'created_at' 		=> $time,
		'deleted_at' 		=> 0,
	);
	$insert = $wpdb->insert( $forcefield['table'], $record );
	return $record;
}

// ----------------------------------
// Check Transgressions against Limit
// ----------------------------------
// 0.9.1: check transgression limit for different block reasons
function forcefield_blocklist_check_transgressions( $reason, $transgressions ) {

	// return true; // TEST TEMP: 1 attempt = auto block

	// --- get limit for this block reason ---
	$limit = forcefield_get_setting( 'limit_' . $reason );

	// --- for instant ban set limit to 1 ---
	if ( ( ( 'no_login_token' == $reason ) && ( 'yes' == forcefield_get_setting( 'login_notokenban' ) ) )
	  || ( ( 'no_register_token' == $reason ) && ( 'yes' == forcefield_get_setting( 'register_notokenban' ) ) )
	  || ( ( 'no_signup_token' == $reason ) && ( 'yes' == forcefield_get_setting( 'signup_notokenban' ) ) )
	  || ( ( 'no_lostpass_token' == $reason ) && ( 'yes' == forcefield_get_setting( 'lostpass_notokenban' ) ) )
	  || ( ( 'no_comment_token' == $reason ) && ( 'yes' == forcefield_get_setting( 'comment_notokenban' ) ) ) ) {
	  $limit = 1;
	}

	// --- filter and return result ---
	$limit = absint( apply_filters( 'forcefield_limit_' . $reason, $limit ) );
	// 0.9.6: auto-pass if limit is below 0, but auto-fail if limit is 0
	if ( $limit < 0 ) {
		return false;
	} elseif ( 0 == $limit ) {
		return true;
	}
	if ( $transgressions > ( $limit - 1 ) ) {
		return true;
	}
	return false;
}

// --------------------------------
// Get Default Transgression Limits
// --------------------------------
function forcefield_blocklist_get_default_limits() {
	// 0.9.6: added limits for super_fail and no_buddypress_token
	$limits = array(
		'admin_bad' 			=> 1, 	// really really bad
		'super_fail'			=> 5,	// likely brute force attempts
		'admin_fail' 			=> 10,	// likely brute force attempts
		'xmlrpc_login' 			=> 2,	// blocks only when disallowed
		'xmlrpc_authfail' 		=> 10,	// likely brute force attempts
		'no_referer' 			=> 10,	// probably a silly bot
		'no_token'				=> 10,	// probably a bot
		'bad_token'				=> 5,	// probably a bot
		// note: initial benefit of the doubt leeway for tokens
		'no_login_token' 		=> 3,
		'no_register_token'		=> 3,
		'no_signup_token'		=> 3,
		'no_lostpass_token'		=> 3,
		'no_comment_token'		=> 3,
		'no_buddypress_token'	=> 3,
	);
	return $limits;
}

// ----------------------------
// Get Translated Block Reasons
// ----------------------------
// 0.9.1: translated block reasons
function forcefield_blocklist_get_reasons() {

	// --- IP Block Reasons ---
	$reasons = array(
		'admin_bad' 			=> __( 'Unwhitelisted Admin Login', 'forcefield' ),
		'admin_fail' 			=> __( 'Administrator Login Fail', 'forcefield' ),
		'xmlrpc_login' 			=> __( 'Disallowed XML RPC Login Attempt', 'forcefield' ),
		'xmlrpc_authfail' 		=> __( 'Failed XML RPC Login Attempt', 'forcefield' ),
		'no_referer' 			=> __( 'Missing Referrer Header', 'forcefield' ),
		'no_token'				=> __( 'Missing Action Token', 'forcefield' ),
		'bad_token'				=> __( 'Action Token Mismatch', 'forcefield' ),
		'no_login_token' 		=> __( 'Missing Login Token', 'forcefield' ),
		'no_register_token' 	=> __( 'Missing Registration Token', 'forcefield' ),
		'no_signup_token' 		=> __( 'Missing Blog Signup Token', 'forcefield' ),
		'no_lostpass_token' 	=> __( 'Missing Lost Password Token', 'forcefield' ),
		'no_comment_token' 		=> __( 'Missing Comment Token', 'forcefield' ),
		'no_buddypress_token'	=> __( 'Missing BuddyPress Token', 'forcefield' ),
	);
	return $reasons;
}

// --------------------------------
// Blocklist Transgression Cooldown
// --------------------------------
// 0.9.1: check cooldown period
function forcefield_blocklist_cooldown( $record ) {
	global $wpdb, $forcefield;

	$cooldown = forcefield_get_setting( 'blocklist_cooldown' );
	$diff = time() - $record['last_access_at'];
	if ( $diff > $cooldown ) {
		if ( $record['transgressions'] > 0 ) {
			// TODO: recheck floor/ceil usage here?
			$reduce = floor( $diff / $cooldown );
			$record['trangressions'] = $record['transgressions'] - $reduce;
			if ( $record['transgressions'] < 1 ) {
				$record['transgressions'] = 0;
				$record['deleted_at'] = time();
			}
			// note: not strictly accurate
			$record['last_access_at'] = time();
			$where = array( 'id' => $record['id'] );
			$wpdb->update( $forcefield['table'], $record, $where );
		}
	}
	return $record;
}

// -------------------------
// Blocklist Expire Old Rows
// -------------------------
function forcefield_blocklist_expire_old_rows( $timestamp, $reason = false, $ip = false ) {

	global $wpdb, $forcefield;
	$query = "UPDATE " . $forcefield['table'] . " SET `deleted_at` = %s WHERE `last_access_at` < %s";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$query = $wpdb->prepare( $query, array( time(), $timestamp ) );
	if ( $reason ) {
		if ( 'admin_bad' == $reason ) {
			// never auto-expire
			return false;
		}
		$query .= $wpdb->prepare( " AND `label` = %s", $reason );
	}
	if ( $ip ) {
		$query .= $wpdb->prepare( " AND `ip` = %s", $ip );
	}

	// 1.0.5: use sanitize_title on request variable
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['ff-cleanup'] ) && ( '1' == sanitize_title( $_REQUEST['ff-cleanup'] ) ) ) {
		echo esc_html( $query ) . '<br>' . PHP_EOL;
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $query );
}

// -------------------------
// Blocklist Delete Old Rows
// -------------------------
function forcefield_blocklist_delete_old_rows( $timestamp, $reason = false, $ip = false ) {

	global $wpdb, $forcefield;

	// 0.9.6: add auto-delete of bad records (with empty IP)
	$query = "DELETE FROM " . $forcefield['table'] . " WHERE ip = ''";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$clear = $wpdb->query( $query );

	$query = "DELETE FROM " . $forcefield['table'] . " WHERE `last_access_at` < %s";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$query = $wpdb->prepare( $query, $timestamp );
	if ( $reason ) {
		if ( 'admin_bad' == $reason ) {
			// never auto-delete
			return false;
		}
		$query .= $wpdb->prepare( " AND `label` = %s", $reason );
	}
	if ( $ip ) {
		$query .= $wpdb->prepare( " AND `ip` = %s", $ip );
	}

	// 1.0.5: use sanitize_title on request variable
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['ff-cleanup'] ) && ( '1' == sanitize_title( $_REQUEST['ff-cleanup'] ) ) ) {
		echo esc_html( $query ) . '<br>' . PHP_EOL;
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $query );
}

// -----------------------
// Blocklist Delete Record
// -----------------------
function forcefield_blocklist_delete_record( $ip = false, $reason = false ) {

	global $wpdb, $forcefield;
	if ( !$ip ) {
		$ip = $forcefield['ip'];
	}
	$query = "DELETE FROM " . $forcefield['table'] . " WHERE `ip` = %s";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$query = $wpdb->prepare( $query, $ip );
	if ( $reason ) {
		$query .= $wpdb->prepare( " AND `label` = %s", $reason );
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->query( $query );
}

// ----------------------------
// Admin AJAX Blocklist Unblock
// ----------------------------
add_action( 'wp_ajax_forcefield_unblock_ip', 'forcefield_blocklist_remove_record' );
function forcefield_blocklist_remove_record() {

	// --- check admin permissions ---
	if ( !current_user_can( 'manage_options' ) ) {
		exit;
	}

	// --- check admin referer ---
	// 0.9.6: fix to referrer typo
	// 0.9.6: change to -delete action suffix
	check_admin_referer( 'forcefield-delete' );

	// --- get IP to unblock ---
	// 1.0.5: use sanitize_text_field on request variable
	$ip = sanitize_text_field( $_REQUEST['ip'] );
	$iptype = forcefield_get_ip_type( $ip );
	if ( !$iptype ) {
		return false;
	}
	$message = __( 'IP Address Block Removed.', 'forcefield' );

	if ( isset( $_REQUEST['label'] ) ) {
		// 1.0.5: use sanitize_text_field on request variable
		$reason = sanitize_text_field( $_REQUEST['label'] );
		$reasons = forcefield_blocklist_get_reasons();
		if ( !array_key_exists( $reason, $reasons ) ) {
			return false;
		}
		$message = __( 'Transgression Record Removed.', 'forcefield' );
	} else {
		$reason = false;
	}

	// --- delete block record
	$delete = forcefield_blocklist_delete_record( $ip, $reason );

	// --- remove row(s) from IP blocklist display table ---
	// 1.0.0: added row removal javascript
	// 1.0.5: output directly instead of storing javascript
	echo "<script>";
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['row'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$row = absint( $_REQUEST['row'] );
		echo "parent.document.getElementById('blocklist-row-" . esc_js( $row ) . "').style.display = 'none'; ";
	} else {
		$ipclass = str_replace( '.', '-', $ip );
		echo "els = parent.document.getElementsByClassName('ip-" . esc_js( $ipclass ) . "'); ";
		echo "for (i = 0; i < els.length; i++) {els[i].style.display = 'none';} ";
	}

	// --- refresh unblock action nonce ---
	// 1.0.0: added nonce refresh
	$unblocknonce = wp_create_nonce( 'forcefield-delete' );
	echo "parent.document.getElementById('unblock-nonce').value = '" . esc_js( $unblocknonce ) . "'; ";

	// --- close javascript ---
	echo "</script>";

	// --- alert and exit ---
	forcefield_alert_message( $message );
	exit;
}

// --------------------------------
// Admin AJAX Blocklist Clear Table
// --------------------------------
add_action( 'wp_ajax_forcefield_blocklist_clear', 'forcefield_blocklist_clear' );
function forcefield_blocklist_clear() {

	// --- check admin permissions ---
	if ( !current_user_can( 'manage_options' ) ) {
		exit;
	}

	// --- check admin referer ---
	// 0.9.6: fix to referrer typo
	// 1.0.2: alert with nonce expired message
	// 1.0.5: use sanitize_text_field on request variable
	// check_admin_referer( 'forcefield-clear' );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$nonce = sanitize_text_field( $_REQUEST['nonce'] );
	$checknonce = wp_verify_nonce( $nonce, 'forcefield-clear' );
	if ( !$checknonce ) {
		$message = __( 'Nonce expired. Please reload the page and try again.', 'forcefield' );
		forcefield_alert_message( $message );
		exit;
	}

	// --- check blocklist table ---
	$check = forcefield_blocklist_check_table();
	if ( $check ) {
		$clear = forcefield_blocklist_clear_table();
		$message = __( 'IP Blocklist has been cleared.', 'forcefield' );
	} else {
		$message = __( 'Error. Blocklist table does not exist.', 'forcefield' );
	}
	echo "<script>parent.document.getElementById('blocklist-table').innerHTML = '';</script>";

	forcefield_alert_message( $message );
	exit;
}

// --------------------------
// Manual Unblock Form Output
// --------------------------
// 0.9.1: manual unblock form output
function forcefield_blocklist_unblock_form_output() {

	global $forcefield;

	// --- form title ---
	echo '<br><table><tr><td align="center">' . PHP_EOL;
		// 1.0.4: added missing esc_html wrapper
		echo '<h3>' . esc_html( __( '403 Forbidden', 'forcefield' ) ) . '</h3>' . PHP_EOL;
	echo '</td></tr>' . PHP_EOL;
	echo '<tr height="20"><td> </td></tr>' . PHP_EOL;

	// --- user message ---
	// 1.0.4: added missing esc_html wrappers
	echo '<tr><td>' . PHP_EOL;
		echo esc_html( __( 'Access Denied. Your IP Address has been blocked!', 'forcefield' ) ) . '<br>' . PHP_EOL;
		echo esc_html( __( 'Your IP Address is: ', 'forcefield' ) ) . esc_html( $forcefield['ip'] ) . '<br>' . PHP_EOL;
		echo esc_html( __( 'If you are a real person click the button below.', 'forcefield' ) ) . PHP_EOL;
	echo '</td></tr>' . PHP_EOL;

	echo '<tr height="20"><td> </td></tr>' . PHP_EOL;
	echo '<tr><td align="center">';

		// --- unblock form ---
		// 0.9.6: added missing nonce unblock field
		// 1.0.0: remove unused form target attribute
		// 1.0.0: add redirect field for unblock success
		// 1.0.0: set form method to POST for unblock token check
		// 1.0.3: simplify protocol logic
		// 1.0.4: added missing esc_url and esc_url_raw wrappers
		$adminajax = admin_url( 'admin-ajax.php' );
		$protocol = is_ssl() ? 'https://' : 'http://';
		$redirect = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		echo '<form action="' . esc_url( $adminajax ) . '" method="post">' . PHP_EOL;
		echo '<input type="hidden" name="action" value="forcefield_unblock">' . PHP_EOL;
		echo '<input type="hidden" name="redirect" value="' . esc_url_raw( $redirect ) . '">' . PHP_EOL;
		wp_nonce_field( 'forcefield-unblock' );

		// --- add an unblock token field ---
		forcefield_add_field( 'unblock' );

		// --- submit button ---
		// 1.0.4: added missing esc_html wrapper
		echo '<input type="submit" value="' . esc_html( __( 'Unblock My IP', 'forcefield' ) ) . '">' . PHP_EOL;
		echo '</form>' . PHP_EOL;

	echo '</td></tr></table>' . PHP_EOL;
	echo '</html></body>';
}

// -------------------
// AJAX Unblock Action
// -------------------
// 0.9.1: check for manual unblock request
add_action( 'wp_ajax_forcefield_unblock', 'forcefield_blocklist_unblock_check' );
add_action( 'wp_ajax_nopriv_forcefield_unblock', 'forcefield_blocklist_unblock_check' );
function forcefield_blocklist_unblock_check() {

	// --- fail on empty referer field ---
	// 0.9.7: added isset check as may not be set if empty
	if ( !isset( $_SERVER['HTTP_REFERER'] ) || ( '' == $_SERVER['HTTP_REFERER'] ) ) {
		exit;
	}

	// --- check nonce field ---
	// 0.9.6: added user nonce field for unblock IP request
	// $checknonce = wp_verify_nonce('forcefield-unblock');
	check_admin_referer( 'forcefield-unblock' );

	// --- check for unblock token ---
	// 0.9.7: added check if unblock token set
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_POST['auth_token_unblock'] ) ) {

		// --- get sanitized post value ---
		// 0.9.9: strip non alphanumeric characters
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$authtoken = sanitize_text_field( $_POST['auth_token_unblock'] );
		// 1.0.3: fix to mismatched variable name (posted)
		$checkposted = preg_match( '/^[a-zA-Z0-9]+$/', $authtoken );

		// --- check token exists for IP ---
		$checktoken = forcefield_check_token( 'unblock' );

		// 0.9.5: check now returns an array so we check 'value' key
		if ( !$checktoken ) {

			// --- unblock token expired ---
			$message = __( 'Time limit expired. Refresh the page and try again.', 'forcefield' );

		} elseif ( !$checkposted || ( 12 != strlen( $checkposted ) ) ) {

			// --- fail, invalid token ---
			// 0.9.9: added check for alphanumeric and token length
			$message = __( 'Invalid unblock token. IP Unblock Failed.', 'forcefield' );

		} elseif ( $authtoken != $checktoken['value'] ) {

			// --- fail, token is a mismatch ---
			$message = __( 'Invalid Request. IP Unblock Failed.', 'forcefield' );

		} else {

			// --- success, delete block record ---
			// 0.9.6: added missing success message
			forcefield_blocklist_delete_record();
			forcefield_delete_token( 'unblock' );

			// --- output unblock success message ---
			// 1.0.4: added missing esc_html wrapper
			echo '<p>' . esc_html( __( 'Success! Your IP has been unblocked.', 'forcefield' ) ) . '</p>';

			// --- maybe automatically redirect to last URL ---
			// 1.0.0: added automatic redirect
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['redirect'] ) ) {

				// --- output redirection message ---
				// 1.0.1: fix to incorrect text domain
				// 1.0.4: added missing esc_html wrappers
				// 1.0.5: use sanitize_url on request variable
				$redirect = trim( esc_url_raw( $_REQUEST['redirect'] ) );
				if ( '' != $redirect ) {
					echo '<p>' . esc_html( __( 'You will be automatically redirected to your last requested address.', 'forcefield' ) ) . '</p>';
					echo '<p><a href="' . esc_url( $redirect ) . '">' . esc_html( __( 'Click here to continue to this address manually.', 'forcefield' ) ) . '</a></p>';

					// --- script for automatic redirection ---
					echo "<script>setTimeout(function() {document.location = '" . esc_url( $redirect ) . "';}, 5000);</script>";
				}
			}

			exit;
		}
	} else {
		// --- missing unblock token ---
		// 0.9.7: added message for missing unblock token
		$message = __( 'Error! Unblock authentication failed.', 'forcefield' );
	}

	// --- javascript alert message and exit ---
	forcefield_alert_message( $message );
	exit;
}

// -----------------------
// Blocklist Table Cleanup
// -----------------------
function forcefield_blocklist_table_cleanup( $reason = false, $ip = false ) {

	if ( !forcefield_blocklist_check_table() ) {
		return false;
	}

	$intervals = forcefield_get_intervals();

	// --- expire old block records ---
	// 1.0.1: fix to get expire period interval
	$expireperiod = forcefield_get_setting( 'blocklist_expiry' );
	$expireperiod = $intervals[$expireperiod]['interval'];
	if ( $reason ) {
		$expireperiod = apply_filters( 'blocklist_expiry_' . $reason, $expireperiod );
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['ff-cleanup'] ) && ( '1' == sanitize_title( $_REQUEST['ff-cleanup'] ) ) ) {
		// 1.0.4: added missing esc_html wrapper
		echo 'Expire Period: ' . esc_html( $expireperiod ) . '<br>' . PHP_EOL;
	}
	if ( $expireperiod > 0 ) {
		// --- expire old rows ---
		$timestamp = time() - $expireperiod;
		forcefield_blocklist_expire_old_rows( $timestamp, $reason, $ip );
	}

	// --- delete older block records ---
	// 1.0.1: fix to get delete period interval
	$deleteperiod = forcefield_get_setting( 'blocklist_delete' );
	$deleteperiod = $intervals[$deleteperiod]['interval'];
	if ( $reason ) {
		$deleteperiod = apply_filters( 'blocklist_delete_' . $reason, $deleteperiod );
	}
	// $expireperiod = absint( $expireperiod );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['ff-cleanup'] ) && ( '1' == sanitize_title( $_REQUEST['ff-cleanup'] ) ) ) {
		// 1.0.4: added missing esc_html wrapper
		echo 'Delete Period: ' . esc_html( $deleteperiod ) . '<br>' . PHP_EOL;
	}
	if ( $deleteperiod > 0 ) {
		// --- delete old rows ---
		$timestamp = time() - $deleteperiod;
		forcefield_blocklist_delete_old_rows( $timestamp, $reason, $ip );
	}

	// --- trigger cleanup records action ---
	do_action( 'forcefield_cleanup_records' );
}

// ------------------------------
// WP CRON Schedule Table Cleanup
// ------------------------------
add_action( 'init', 'forcefield_blocklist_schedule_cleanup' );
function forcefield_blocklist_schedule_cleanup() {
	if ( !wp_next_scheduled( 'forcefield_blocklist_table_cleanup' ) ) {
		$frequency = forcefield_get_setting( 'blocklist_cleanups' );
		wp_schedule_event( time(), $frequency, 'forcefield_blocklist_table_cleanup' );
	}
	if ( current_user_can( 'manage_options' ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['ff-cleanup'] ) && ( '1' == sanitize_title( $_REQUEST['ff-cleanup'] ) ) ) {
			forcefield_blocklist_table_cleanup();
			exit;
		}
	}
}

