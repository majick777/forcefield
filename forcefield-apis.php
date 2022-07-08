<?php

// ==============================
// === ForceField APIs Module ===
// ==============================

// === Application Passwords ===
// - maybe Disable Application Passwords

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


// -----------------------------
// === Application Passwords ===
// -----------------------------

// -----------------------------------
// maybe Disable Application Passwords
// -----------------------------------
// 1.0.1: added for WP 5.6+ Application Passwords feature
add_filter( 'wp_is_application_passwords_available', 'forcefield_app_passwords_disable' );
function forcefield_app_passwords_disable( $enabled ) {
	// 1.0.3: fix for when called before plugins_loaded hook
	if ( function_exists( 'forcefield_get_setting' ) ) {
		$disabled = forcefield_get_setting( 'app_passwords_disable' );
	} else {
		$settings = apply_filters( 'forcefield_settings', get_option( 'forcefield' ) );
		$disabled = apply_filters( 'forcefield_app_passwords_disable', $settings['app_passwords_disable'] );
	}
	if ( 'yes' == $disabled ) {
		$enabled = false;
	}
	return $enabled;
}


// ---------------
// === XML RPC ===
// ---------------

// Note: xmlrpc.php sequence
// - new wp_xmlrpc_server (/wp-includes/class-wp-xmlrpc-server.php)
// -> serve_request -> IXR_Server($methods)
// -> method (authenticated) -> login
// -> method (unauthenticated) -> (no login)

// ------------------------------
// maybe Disable XML RPC Entirely
// ------------------------------
add_filter( 'xmlrpc_methods', 'forcefield_xmlrpc_disable' );
function forcefield_xmlrpc_disable( $methods ) {
	$disable = forcefield_get_setting( 'xmlrpc_disable' );
	if ( 'yes' === (string) $disable ) {
		$methods = array();
	}
	return $methods;
}

// -------------------------------------------
// maybe Disable XML RPC Authenticated Methods
// -------------------------------------------
// note: this enable filter is for authenticated methods *only*
// as it is triggered by the login method of XML RPC server
add_filter( 'xmlrpc_enabled', 'forcefield_xmlrpc_disable_auth' );
function forcefield_xmlrpc_disable_auth( $enabled ) {
	$disable = forcefield_get_setting( 'xmlrpc_noauth' );
	if ( 'yes' === (string) $disable ) {
		$enabled = false;
	}
	return $enabled;
}

// ----------------------------
// maybe Slowdown XML RPC Calls
// ----------------------------
// add_filter( 'xmlrpc_enabled', 'forcefield_xmlrpc_slowdown' );
add_filter( 'xmlrpc_login_error', 'forcefield_xmlrpc_slowdown' );
function forcefield_xmlrpc_slowdown( $arg ) {
	$slowdown = forcefield_get_setting( 'xmlrpc_slowdown' );
	if ( 'yes' == $slowdown ) {
		global $forcefield_data;
		if ( !isset( $forcefield_data ) ) {
			$forcefield_data = array();
		}
		if ( !isset( $forcefield_data['xmlrpc_calls'] ) ) {
			$forcefield_data['xmlrpc_calls'] = 0;
			return $arg;
		} else {
			$forcefield_data['xmlrpc_calls'] ++;
		}
		$min = $forcefield_data['xmlrpc_calls'] * 500000;
		$max = $forcefield_data['xmlrpc_calls'] * 2000000;
		// 1.0.4: use wp_rand function instead
		if ( function_exists( 'wp_rand' ) ) {
			$sleep = wp_rand( $min, $max );
		} elseif ( function_exists( 'mt_rand' ) ) {
			$sleep = mt_rand( $min, $max );
		} else {
			$sleep = rand( $min, $max );
		}
		usleep( $sleep );
	}
	return $arg;
}

// -------------------------------
// maybe Remove XML RPC Link (RSD)
// -------------------------------
add_action( 'plugins_loaded', 'forcefield_remove_rsd_link' );
function forcefield_remove_rsd_link() {
	// if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		$disable = forcefield_get_setting( 'xmlrpc_disable' );
		if ( 'yes' === (string) $disable ) {
			remove_action( 'wp_head', 'rsd_link' );
		}
	// }
}

// -----------------------------
// maybe Disable XML RPC Methods
// -----------------------------
add_filter( 'xmlrpc_methods', 'forcefield_xmlrpc_methods', 9 );
function forcefield_xmlrpc_methods( $methods ) {
	// --- maybe disable pingbacks ---
	$disable = forcefield_get_setting( 'xmlrpc_nopingbacks' );
	if ( 'yes' === (string) $disable ) {
		if ( isset( $methods['pingback.ping'] ) ) {
			unset( $methods['pingback.ping'] );
		}
		if ( isset( $methods['pingback.extensions.getPingbacks'] ) ) {
			unset( $methods['pingback.extensions.getPingbacks'] );
		}
	}
	return $methods;
}

// ------------------------------------
// maybe Remove XML RPC Pingback Header
// ------------------------------------
add_filter( 'wp_headers', 'forcefield_remove_pingback_header' );
function forcefield_remove_pingback_header( $headers ) {
	$disable = forcefield_get_setting( 'xmlrpc_nopingbacks' );
	if ( 'yes' === (string) $disable ) {
		unset( $headers['X-Pingback'] );
	}
	return $headers;
}

// ------------------------
// maybe Disable Self Pings
// ------------------------
add_action( 'pre_ping', 'forcefield_disable_self_pings' );
function forcefield_disable_self_pings( $links ) {
	$disable = forcefield_get_setting( 'xmlrpc_noselfpings' );
	if ( 'yes' === (string) $disable ) {
		$home = home_url();
		// 1.0.1: added extra check for links array
		if ( is_array( $links ) && ( count( $links ) > 0 ) ) {
			foreach ( $links as $i => $link ) {
				if ( 0 === strpos( $link, $home ) ) {
					unset( $links[$i] );
				}
			}
		}
	}
	return $links;
}

// ----------------------------------------------
// maybe disable Anonymous Commenting via XML RPC
// ----------------------------------------------
add_filter( 'xmlrpc_allow_anonymous_comments', 'forcefield_xmlrpc_anonymous_comments' );
function forcefield_xmlrpc_anonymous_comments( $allow ) {
	$allowanon = forcefield_get_setting( 'xmlrpc_anoncomments' );
	if ( 'yes' !== (string) $allowanon ) {
		$allow = false;
	}
	return $allow;
}


// ----------------
// === REST API ===
// ----------------

// rest_api_loaded()
// -> rest_get_server (new WP_REST_Server)
// -> serve_request
// note: since check_authentication is always called
// rest_authentication_errors filter is always applied

// -------------------------------
// maybe Disable/Restrict REST API
// -------------------------------
add_filter( 'rest_authentication_errors', 'forcefield_restapi_access', 11 );
function forcefield_restapi_access( $access ) {

	// --- check whitelist and blacklist ---
	// 0.9.2: added whitelist and blacklist checks
	if ( forcefield_whitelist_check( 'apis' ) ) {
		return $access;
	}
	if ( forcefield_blacklist_check( 'apis' ) ) {
		forcefield_forbidden_exit();
	}

	// --- maybe disabled REST API ---
	$restapidisable = forcefield_get_setting( 'restapi_disable' );
	if ( 'yes' == $restapidisable ) {
		$errormessage = __( 'The REST API is disabled.', 'forcefield' );
		$status = 405; // HTTP 405: Method Not Allowed
		return forcefield_filtered_error( 'rest_disabled', $errormessage, $status );
	}

	// --- maybe SSL connection required ---
	$requiressl = forcefield_get_setting( 'restapi_requiressl' );
	// 0.9.8: honour require SSL constant override
	if ( defined( 'FORCEFIELD_REQUIRE_SSL' ) ) {
		// 1.0.2: simplified check logic
		$requiressl = ( FORCEFIELD_REQUIRE_SSL ) ? 'yes' : '';
	}
	if ( ( 'yes' == $requiressl ) && !is_ssl() ) {
		$errormessage = __( 'SSL connection is required to access the REST API.', 'forcefield' );
		$status = 403; // HTTP 403: Forbidden
		return forcefield_filtered_error( 'rest_ssl_required', $errormessage, $status );
	}

	// --- maybe authenticated (logged in) users only ---
	// 1.0.3: fix to double equals typo
	$requireauth = forcefield_get_setting( 'restapi_authonly' );
	if ( ( 'yes' === (string) $requireauth ) && !is_user_logged_in() ) {
		$status = rest_authorization_required_code();
		$errormessage = __( 'You need to be logged in to access the REST API.', 'forcefield' );
		return forcefield_filtered_error( 'rest_not_logged_in', $errormessage, $status );
	}

	// --- handle role restrictions ---
	// 0.9.1: add role restricted REST API access
	$restricted = forcefield_get_setting( 'restapi_restricted' );
	if ( 'yes' === (string) $restricted ) {
		if ( !is_user_logged_in() ) {
			// --- (enforced) logged in only message ---
			$status = rest_authorization_required_code();
			$errormessage = __( 'You need to be logged in to access the REST API.', 'forcefield' );
			return forcefield_filtered_error( 'rest_not_logged_in', $errormessage, $status );
		} else {

			// --- check blocked user roles ---
			// 0.9.1: check multiple roles to maybe allow access
			$allowedroles = forcefield_get_setting( 'restapi_roles' );
			if ( !is_array( $allowedroles ) ) {
				$allowedroles = array();
			}
			$user = wp_get_current_user();
			$userroles = $user->roles;
			$block = true;

			if ( count( $userroles ) > 0 ) {
				foreach ( $userroles as $role ) {
					if ( in_array( $role, $allowedroles ) ) {
						$block = false;
					}
				}
			}

			if ( $block ) {
				$status = rest_authorization_required_code();
				$errormessage = __( 'Access to the REST API is restricted.', 'forcefield' );
				return forcefield_filtered_error( 'rest_restricted', $errormessage, $status );
			}
		}
	}

	return $access;
}

// -----------------------------
// maybe Slowdown REST API Calls
// -----------------------------
// add_filter( 'rest_jsonp_enabled', 'forcefield_restapi_slowdown' );
add_filter( 'rest_authentication_errors', 'forcefield_restapi_slowdown' );
function forcefield_restapi_slowdown( $arg ) {
	$slowdown = forcefield_get_setting( 'restapi_slowdown' );
	if ( 'yes' == $slowdown ) {
		global $forcefield_data;
		if ( !isset( $forcefield_data ) ) {
			$forcefield_data = array();
		}
		if ( !isset( $forcefield_data['restapi_calls'] ) ) {
			$forcefield_data['restapi_calls'] = 0;
			return $arg;
		} else {
			$forcefield_data['restapi_calls']++;
		}
		$min = $forcefield_data['restapi_calls'] * 500000;
		$max = $forcefield_data['restapi_calls'] * 2000000;
		// 1.0.4: use wp_rand instead of mt_rand
		if ( function_exists( 'wp_rand' ) ) {
			$sleep = wp_rand( $min, $max );
		} elseif ( function_exists( 'mt_rand' ) ) {
			$sleep = mt_rand( $min, $max );
		} else {
			$sleep = rand( $min, $max );
		}
		usleep( $sleep );
	}
	return $arg;
}

// --------------------------
// maybe Remove REST API Info
// --------------------------
add_action( 'plugins_loaded', 'forcefield_remove_restapi_info' );
function forcefield_remove_restapi_info() {
	$nolinks = forcefield_get_setting( 'restapi_nolinks' );
	if ( 'yes' === (string) $nolinks ) {
		remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	}
}

// ------------------------
// maybe Disable REST JSONP
// ------------------------
add_filter( 'rest_jsonp_enabled', 'forcefield_jsonp_disable' );
function forcefield_jsonp_disable( $enabled ) {
	$nojsonp = forcefield_get_setting( 'restapi_nojsonp' );
	if ( 'yes' === (string) $nojsonp ) {
		return false;
	}
	return $enabled;
}

// ----------------------------
// maybe Change REST API Prefix
// ----------------------------
// [Deprecated] for example reference only
// 0.9.7: removed as this filter is better hard-coded manually
// note: default is "wp-json"
// add_filter('rest_url_prefix', 'forcefield_restapi_prefix', 100);
// function forcefield_restapi_prefix($prefix) {
//	$customprefix = trim(forcefield_get_setting('restapi_prefix'));
//	if ($customprefix != '') {$prefix = $customprefix;}
//	return $prefix;
// }

// ---------------------------------------
// maybe Disable User Enumeration Endpoint
// ---------------------------------------
add_filter( 'rest_endpoints', 'forcefield_endpoint_restriction', 99 );
function forcefield_endpoint_restriction( $endpoints ) {
	if ( 'yes' == forcefield_get_setting( 'restapi_nouserlist' ) ) {
		if ( isset( $endpoints['/wp/v2/users'] ) ) {
			unset( $endpoints['/wp/v2/users'] );
		}
		if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
			unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		}
	}
	return $endpoints;
}

// -------------------------------------------
// maybe disable REST API Anonymous Commenting
// -------------------------------------------
add_filter( 'rest_allow_anonymous_comments', 'forcefield_restapi_anonymous_comments' );
function forcefield_restapi_anonymous_comments( $allow ) {
	$allowanon = forcefield_get_setting( 'restapi_anoncomments' );
	if ( 'yes' !== (string) $allowanon ) {
		$allow = false;
	}
	return $allow;
}

// -----------------------------------
// maybe Remove All REST API Endpoints
// -----------------------------------
// [Unused] for example reference only
// add_action( 'plugins_loaded', 'forcefield_endpoints_remove', 0);
// function forcefield_endpoints_remove() {
//	remove_filter('rest_api_init', 'create_initial_rest_routes');
// }

// -----------------
// REST Nonce Bypass
// -----------------
// [Deprecated] for example reference only
// 0.9.2: [DEV USE ONLY!] REST API Nonce Check Bypass
// 0.9.7: removed this filter as better hard-coded in development environment
// Usage: You can add define a constant in your wp-config.php to bypass all REST API Nonce Checks
// (this can be helpful to eliminate REST nonces as a cause of endpoint failure):
// define('REST_NONCE_BYPASS', true);
//
// add_filter('rest_authentication_errors', 'forcefield_rest_nonce_bypass', 99);
// function forcefield_rest_nonce_bypass($access) {
//	if (defined('REST_NONCE_BYPASS') && REST_NONCE_BYPASS) {
//		global $wp_rest_auth_cookie; $wp_rest_auth_cookie = false;
//	}
//	return $access;
// }

