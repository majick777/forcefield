<?php

// ========================================
// === ForceField Vulnerability Checker ===
// ========================================

if (!function_exists('add_action')) {exit;}

// - Schedule Vulnerability Check
// - Trigger Vulnerability Checks
// - AJAX Run Check Types
// - Force Connection Closed
// - Check Vulnerability Callbacks
// - Store Pre-Installation Data
// - Vulnerability Check New Installs
// - Perform Vulnerabilities Check
// - Check Vulnerability for Version
// - Check Vulnerability is New
// - Get API Response Data
// - Create Vulnerabilities List
// - Admin Vulnerabilities Notice
// - AJAX Dismiss Vulnerability Notice
// - Plugin Table List Alerts
// - Send Vulnerability Alert Emails
// - Vulnerability Alert Email Test

// Development TODOs
// -----------------
// - test plugin / theme vulnerability notice with multi-select updates
// ? trigger/queue vulnerability rechecks on plugin or theme rollbacks ?
// ? add support for ClassicPress user agent string


// ----------------------------
// Schedule Vulnerability Check
// ----------------------------
add_action( 'plugins_loaded', 'forcefield_vulnerabilities_check_schedule' );
function forcefield_vulnerabilities_check_schedule() {

    global $forcefield;

	// --- maybe reset vulnerability check ---
	// 1.0.0: prefixed reset check querystring trigger
	if ( isset($_GET['ff-reset-check'] ) ) {
		if ( ( 'all' == $_GET['ff-reset-check'] ) && current_user_can( 'manage_options' ) ) {
			delete_option( 'forcefield_vulnerability_checks' );
		} else {
			$reset = $_GET['ff-reset-check'];
			if ( in_array( 'reset', array( 'core', 'plugins', 'themes' ) ) ) {
				$checktimes = get_option( 'forcefield_vulnerability_checks' );
				if ( isset( $checktimes[$reset] ) ) {
					$doreset = false;
					if ( ( 'core' == $reset ) && ( current_user_can('manage_options') || current_user_can( 'update_core' ) ) ) {
						$doreset = true;
					} elseif ( ( 'plugins' == $reset ) && current_user_can( 'update_plugins' ) ) {
						$doreset = true;
					} elseif ( ( 'themes' == $reset ) && current_user_can( 'update_themes' ) ) {
						$doreset = true;
					}

					if ( $doreset ) {
						unset( $checktimes[$reset] );
						if ( count( $checktimes) > 0 ) {
							update_option( 'forcefield_vulnerability_checks', $checktimes );
						} else {
							$checktimes = false;
							delete_option( 'forcefield_vulnerability_checks' );
						}
					}
				}
			}
		}
	}

	// --- check last checked times ---
	if ( !isset($checktimes ) ) {
		$checktimes = get_option( 'forcefield_vulnerability_checks' );
	}
	if ( !$checktimes ) {
		$checktimes = array();
	}
	if ( !isset( $checktimes['core'] ) ) {
		$checktimes['core'] = '';
	}
	if ( !isset( $checktimes['plugins'] ) ) {
		$checktimes['plugins'] = '';
	}
	if ( !isset( $checktimes['themes'] ) ) {
		$checktimes['themes'] = '';
	}

	$intervals = forcefield_get_intervals();

	// --- loop last checked types ---
	foreach ( $checktimes as $type => $lastchecked ) {

	// --- get check frequenecy for type ---
		$frequency = forcefield_get_setting( 'vuln_check_' . $type );
		if ( $frequency && ( '' != $frequency ) && ( 'none' != $frequency ) ) {

			// --- convert schedule frequency to interval ---
			foreach ( $intervals as $key => $value ) {
				if ( $frequency == $key ) {
					$interval = $value['interval'];
				}
			}

			// --- check whether we next check is scheduled ---
			$docheck = false;
			if ( '' == $lastchecked ) {
				$docheck = true;
			} elseif ( isset( $interval ) ) {
				$nextrun = $lastchecked + $interval;
				if ( time() >= $nextrun ) {
					$docheck = true;
				}
			}

			// --- trigger vulnerability checks via iframe ---
			if ( $docheck ) {
				// 0.9.9: do checks in background not on shutdown
				if ( !isset( $forcefield['vulnerability-checks'] ) ) {
					$forcefield['vulnerability-checks'] = array();
				}
				$forcefield['vulnerability-checks'][] = $type;
				if ( !has_action( 'wp_footer', 'forcefield_trigger_vulnerability_check' ) ) {
					add_action( 'wp_footer', 'forcefield_trigger_vulnerability_check', 99 );
				}
			}
		}
	}
}

// ----------------------------
// Trigger Vulnerability Checks
// ----------------------------
// 0.9.9: trigger AJAX to run checks in background
function forcefield_trigger_vulnerability_check() {

	global $forcefield;
	$checks = $forcefield['vulnerability-checks'];
	// 1.0.0: fix to missing array index for singular check
	if ( 1 == count( $checks ) ) {
		$checkstring = $checks[0];
	} else {
		$checkstring = implode( ',', $checks );
	}
	$adminajax = admin_url( 'admin-ajax.php' );
	// $checkurl = add_query_arg( 'action', 'ff_resource_check', $adminajax );
	// $checkurl = add_query_arg( 'types', $checkstring, $checkurl );

	// --- load check after document ready ---
	// 1.0.0: add delayed check for visitors
	// 1.0.1: escape URL separately from arguments
	echo "<script>jQuery(document).ready(function() {setTimeout(function() {";
	echo "checkurl = '" . esc_url( $adminajax ) . "?action=ff_resource_check&types=" . esc_js( $checkstring ) . "'; ";
	echo "jQuery('#resource-check').attr('src', checkurl);";
	echo "}, 5000);});</script>";

	// --- iframe to trigger checks ---
	// 1.0.1: use about:blank for src instead of javascript:void(0)
	echo '<iframe id="resource-check" name="resource-check" src="about:blank" style="display:none;"></iframe>';
}

// --------------------
// AJAX Run Check Types
// --------------------
// 0.9.9: added to run checks in background
add_action( 'wp_ajax_ff_resource_check', 'forcefield_vulnerability_check_ajax' );
add_action( 'wp_ajax_nopriv_ff_resource_check', 'forcefield_vulnerability_check_ajax' );
function forcefield_vulnerability_check_ajax() {
	if ( !current_user_can( 'manage_options' ) ) {
		forcefield_force_connection_closed();
	}
	$types = $_REQUEST['types'];
	if ( strstr( $types, ',' ) ) {
		$checks = explode( ',', $types );
	} else {
		$checks = array( $types );
	}
	foreach ( $checks as $type ) {
		$type = trim( $type );
		echo "Doing Check: " . $type . PHP_EOL;
		if ( function_exists( 'forcefield_vulnerabilities_check_' . $type ) ) {
			call_user_func( 'forcefield_vulnerabilities_check_' . $type );
		} else {
			echo "Error: " . 'forcefield_vulnerabilities_check_' . $type;
		}
	}
	exit;
}

// -----------------------
// Force Connection Closed
// -----------------------
// (flush data so visitors are not waiting on background checks)
// ref: https://gist.github.com/bubba-h57/32593b2b970366d24be7
// 0.9.9: do complete connection close not just flush
function forcefield_force_connection_closed() {
    set_time_limit( 0 );
    ignore_user_abort( true );
    @ob_end_clean();
    ob_start();
    echo '0';
    $size = ob_get_length();
    header( "Connection: close\r\n" );
    header( "Content-Encoding: none\r\n" );
    header( "Content-Length: " . $size );
    if ( function_exists( 'http_response_code')) {
    	http_response_code( 200 );
    }
    ob_end_flush();
    @ob_flush();
    flush();
}

// -----------------------------
// Check Vulnerability Callbacks
// -----------------------------
function forcefield_vulnerabilities_check_all() {
	forcefield_vulnerabilities_check( 'all' );
}
function forcefield_vulnerabilities_check_core() {
	forcefield_vulnerabilities_check( 'core' );
}
function forcefield_vulnerabilities_check_plugins() {
	forcefield_vulnerabilities_check( 'plugins' );
}
function forcefield_vulnerabilities_check_themes() {
	forcefield_vulnerabilities_check( 'themes' );
}

// ---------------------------
// Store Pre-Installation Data
// ---------------------------
add_action( 'upgrader_pre_install', 'forcefield_vulnerability_store_current', 10, 2 );
function forcefield_vulnerability_store_current( $response, $hook_extra ) {

    global $forcefield;

    if ( isset( $hook_extra['action'] ) && ( 'install' == $hook_extra['action'] ) ) {
        if ( isset( $hook_extra['type'] ) ) {
            $type = $hook_extra['type'];
            if ( 'plugin' == $type ) {
                if ( !function_exists( 'get_plugins' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $forcefield['installed_plugins'] = array_keys( get_plugins() );
            }
            if ( 'theme' == $type ) {
                $forcefield['installed_themes'] = array_keys( wp_get_themes() );
            }
        }
    }
    // 1.0.0: fix to variable typo (reponse)
    return $response;
}

// --------------------------------
// Vulnerability Check New Installs
// --------------------------------
add_action( 'upgrader_post_install', 'forcefield_vulnerability_check_new', 10, 3 );
function forcefield_vulnerability_check_new( $response, $hook_extra, $result ) {

    global $forcefield;

    if ( isset( $hook_extra['action'] ) && ( 'install' == $hook_extra['action'] ) ) {
        if ( isset( $hook_extra['type'] ) ) {
            $type = $hook_extra['type'];
            if ( 'plugin' == $type ) {
                if ( !function_exists( 'get_plugins' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $plugins = get_plugins();
                foreach ( $plugin as $name => $plugin ) {
                    if ( !in_array( $name, $forcefield['installed_plugins'] ) ) {
                    	$new = $name;
                    }
                }
            }
            if ($type == 'theme') {
                $themes = wp_get_themes();
                foreach ( $themes as $name => $theme ) {
                    if ( !in_array( $name, $forcefield['installed_themes'] ) ) {
                    	$new = $name;
                    }
                }
            }
            if ( isset( $new ) ) {
            	forcefield_vulnerabilities_check( $type . 's', $new );
            }
        }
    }
    return $response;
}

// -----------------------------
// Perform Vulnerabilities Check
// -----------------------------
function forcefield_vulnerabilities_check( $type = 'all', $checkname = false ) {

    // --- dev debug switch ---
    $debug = false; // $debug = true;

    // -- check if API already overloaded ---
    if ( isset( $forcefield['api_overload'] ) && $forcefield['api_overload'] ) {
    	return;
    }

    // --- only allow name matches for plugins or themes ---
    if ( ( 'all' == $type ) || ( 'core' == $type ) ) {
    	$checkname = false;
    }

    // --- get cached report ---
    // 1.0.0: fix to new array (empty array values not empty strings)
    $report = $lastcheck = array();
    $new = array( 'core' => array(), 'plugins' => array(), 'themes' => array() );
    $cachedreport = get_option( 'forcefield_vulnerability_report' );
    if ( $cachedreport ) {
    	$report = $cachedreport;
    }

    // --- set default remote get args ---
    // TODO: add support for ClassicPress user agent string ?
    $home_url = home_url();
    global $wp_version, $cp_version;
    // if ( isset( $cp_version ) ) {
    //	$user_agent = 'ClassicPress/' . $cp_version . '; ' . $home_url;
    // } else {
    	$user_agent = 'WordPress/' . $wp_version . '; ' . $home_url;
    // }
    $args = array(
        'timeout'     => 10,
        'user-agent'  => $user_agent,
    );

    // --- filter for sslverify ---
    // 0.9.9: added to help some users bypass SSL connection errors
    // 1.0.0: fix to undefined variable warning and force boolean
    $sslverify = apply_filters( 'forcefield_vuln_ssl_verify', true );
    $args['sslverify'] = (bool)$sslverify;

    // --- check for saved WP VulnDB Token ---
    $token = forcefield_get_setting( 'vuln_api_token' );
    $verified = get_option( 'forcefield_wbvulndb_verified' );
    if ( $token && $verified ) {
        // --- set WP VulnDB API v3 ---
        $apiurl = 'https://wpvulndb.com/api/v3';
        // --- add header if token is set ---
        $args['headers'] = array( 'Authorization: Token token=' . $token );
    } else {
        // --- just use WP VulnDB API v2 ---
        // 1.0.1: deprecate API v2 (token required for v3)
        // $apiurl = 'https://wpvulndb.com/api/v2';
        return;
    }

    // --- filter remote get args ---
    $args = apply_filters( 'forcefield_vulnerablity_check_args', $args );

	// --- get specific last check times ---
    $check_times = get_option( 'forcefield_vulnerability_check_times' );

    // Check Core
    // ----------
    if ( ( 'core' == $type ) || ( 'all' == $type ) ) {

        // --- get existing vulnerabilities ---
        if ( isset( $report['core'] ) ) {
        	$existing = $report['core'];
        } else {
        	$existing = array();
        }

        // --- set version and URL ---
        // TODO: add support for ClassicPress versions ?
        $version = $wp_version;
        // if ( isset( $cp_version ) ) {
        //	$version = $cp_version;
        // }
        $version = apply_filters( 'wordpress_vulnerabilies_core_check', $version );
        if ( $version ) {

			// --- skip recent core specific checks ---
			// 1.0.1: added to reduce duplicate checks
			$frequency = forcefield_get_setting( 'vuln_check_core' );
			if ( $frequency && ( '' != $frequency ) && ( 'none' != $frequency ) ) {

				// --- get check interval ---
				foreach ( $intervals as $key => $value ) {
					if ( $frequency == $key ) {$interval = $value['interval'];}
				}

				// --- check specific last version check time ---
				if ( isset( $check_times['core'][$version] ) ) {
					$checked = $check_times['core'][$version];
					if ( ( $checked + $interval ) < time() ) {$version = false;}
				}
			}

			if ( $version ) {
				$url = $apiurl . '/wordpresses/' . str_replace( '.', '', $version );
				if ( $debug ) {
					echo "<!-- Core Check URL: " . $url . " -->" . PHP_EOL;
				}

				// --- get API response data ---
				$check = false;
				$response = forcefield_get_response_data( $url, $args );
				if ( $response ) {
					if ( $debug ) {
						echo "<!-- Response: " . print_r( $response, true ) . " -->" . PHP_EOL;
					}
					// 1.0.1: fix to mismatched core report key (wordpress)
					$check = forcefield_vulnerability_check( $response, $version );
					if ( $check ) {
						$newitems = forcefield_vulnerability_is_new( $check, $existing );
						if ( $newitems ) {
							$new['core'] = array_merge( $new['core'], $newitems );
						}
						$report['core'] = $check;
					} elseif ( isset( $report['core'] ) ) {
						unset( $report['core'] );
					}
				}

				// 1.0.1: abort on overload or invalid API token
				if ( ( isset( $forcefield['api_overload'] ) && $forcefield['api_overload'] )
				  || ( isset( $forcefield['invalid_token'] ) && $forcefield['invalid_token'] ) ) {
					return;
				} elseif ( $response ) {
					// 1.0.1: record version-specific check time
					forcefield_vulnerability_update_check_time( 'core', false, $version, $check );
				}
			}
		}

		if ( $version ) {
	        $lastcheck['core'] = time();
	    }
    }

    // Check Plugins
    // -------------
    if ( ( 'plugins' == $type ) || ( 'all' == $type ) ) {

        // --- get all plugins ---
        // 1.0.0: clear plugin cache to ensure accurate matching
        // 1.0.1: add filter for plugins to check
        wp_clean_plugins_cache( false );
        if ( !function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $plugins = apply_filters( 'forcefield_vulnerability_check_plugins', $plugins, $checkname );

        if ( count( $plugins > 0 ) ) {

			// --- skip recent plugin specific checks ---
			// 1.0.1: added to reduce duplicate checks
			$frequency = forcefield_get_setting( 'vuln_check_plugins' );
			if ( $frequency && ( '' != $frequency ) && ( 'none' != $frequency ) ) {

				// --- get check interval ---
				foreach ( $intervals as $key => $value ) {
					if ( $frequency == $key ) {$interval = $value['interval'];}
				}

				// --- check specific last version check time ---
				foreach ( $plugins as $name => $details ) {
					$version = $details['Version'];
					if ( strstr( $name, '/' ) ) {
						$parts = explode( '/', $name );
						$slug = $parts[0];
					} else {
						$slug = str_replace( '.php', '', $name );
					}
					if ( isset( $check_times['plugins'][$slug][$version] ) ) {
						$checked = $check_times['plugins'][$slug][$version];
						if ( ( $checked + $interval ) < time() ) {unset( $plugins[$name] );}
					}
				}
			}

	        // --- loop plugins ---
	        if ( count( $plugins > 0 ) ) {
				$resumeat = get_option( 'forcefield_check_plugin' );
				foreach ( $plugins as $name => $details ) {


					// --- maybe check if we have a current position ---
					// 0.9.9: added check resuming
					$docheck = true;
					if ( $resumeat ) {
						if ($name != $resumeat) {
							$docheck = false;
						} else {
							delete_option( 'forcefield_check_plugin' );
							$resumeat = false;
						}
					}

					// --- check if API overloaded ---
					// 0.9.9: added overload check
					// 1.0.1: also abort if invalid API token
					if ( ( isset( $forcefield['api_overload'] ) && $forcefield['api_overload'] )
					  || ( isset( $forcefield['invalid_token'] ) && $forcefield['invalid_token'] ) ) {

						$abortpluginchecks = true;

					} elseif ( $docheck ) {

						// --- check for name match (if specified) ---
						if ( !$checkname || ( $checkname && ( $checkname == $name ) ) ) {

							// --- get existing vulnerabilities ---
							$existing = array();
							if ( isset( $report['plugins'] ) ) {
								$existing = $report['plugins'];
							}

							// --- set plugin slug and URL ---
							// $slug = sanitize_title($details['Name']);
							// 0.9.9: get plugin slug from plugin subdirectory
							if ( strstr( $name, '/' ) ) {
								$parts = explode( '/', $name );
								$slug = $parts[0];
							} else {
								$slug = str_replace( '.php', '', $name );
							}

							$url = $apiurl . '/plugins/' . $slug;
							if ( $debug ) {
								echo "<!-- Plugin Check URL: " . $url . " -->" . PHP_EOL;
							}

							// --- get API response data ---
							$check = false;
							$response = forcefield_get_response_data( $url, $args );
							if ( $response ) {
								if ( $debug ) {
									echo "<!-- Response: " . print_r( $response, true ) . " -->" . PHP_EOL;
								}
								$check = forcefield_vulnerability_check( $response, $details['Version'] );
								if ( $check ) {

									$newitems = forcefield_vulnerability_is_new( $check, $existing );
									if ( $newitems ) {
										$new['plugins'] = array_merge( $new['plugins'], $newitems );
									}
									$report['plugins'][$name] = $check;
								} elseif ( isset( $report['plugins'][$name] ) ) {
									unset( $report['plugins'][$name] );
								}
							}
							if ( isset( $forcefield['api_overload'] ) && $forcefield['api_overload'] ) {
								// --- store current plugin for resuming ---
								// 0.9.9: added for when API overloaded
								update_option( 'forcefield_check_plugin', $name );
							} elseif ( $response ) {
								// 1.0.1: record plugin-specific check time
								forcefield_vulnerability_update_check_time( 'plugins', $slug, $version, $check );
							}
						}
					}
				}
			}
        }
        // 1.0.0: record last check time whether aborted or not
        // (so as to prevent continual re-attempts)
        if ( count( $plugins ) > 0 ) {
	        $lastcheck['plugins'] = time();
		}
    }

    // Check Themes
    // ------------
    if ( ( 'themes' == $type ) || ( 'all' == $type ) ) {

        // --- get existing vulnerabilities ---
        $existing = array();
        if ( isset( $report['themes'] ) ) {
        	$existing = $report['themes'];
        }

        // --- get all themes ---
        // 1.0.0: clear theme cache to ensure accurate matching
        wp_clean_themes_cache( false );
        $themes = wp_get_themes();
        $themes = apply_filters( 'forcefield_vulnerability_check_themes', $themes, $checkname );

        if ( count( $themes > 0 ) ) {

			// --- skip recent theme specific checks ---
			// 1.0.1: added to reduce duplicate checks
			$frequency = forcefield_get_setting( 'vuln_check_themes' );
			if ( $frequency && ( '' != $frequency ) && ( 'none' != $frequency ) ) {

				// --- get check interval ---
				foreach ( $intervals as $key => $value ) {
					if ( $frequency == $key ) {$interval = $value['interval'];}
				}

				// --- check specific last version check time ---
				foreach ( $themes as $name => $details ) {
					$version = $details['Version'];
					if ( isset( $check_times['themes'][$name][$version] ) ) {
						$checked = $check_times['themes'][$name][$version];
						if ( ( $checked + $interval ) < time() ) {unset( $themes[$name] );}
					}
				}
			}

	        // --- loop themes ---
	        if ( count( $themes > 0 ) ) {
				$resumeat = get_option( 'forcefield_check_theme' );
				foreach ( $themes as $name => $details ) {

					// --- maybe check if we have a current position ---
					// 0.9.9: added check resuming
					$docheck = true;
					if ( $resumeat ) {
						if ( $name != $resumeat ) {
							$docheck = false;
						} else {
							delete_option( 'forcefield_check_theme' );
							$resumeat = false;
						}
					}

					// --- check if API overloaded ---
					// 0.9.9: added this check
					// 1.0.1: also abort if invalid API token
					if ( ( isset( $forcefield['api_overload'] ) && $forcefield['api_overload'] )
					  || ( isset( $forcefield['invalid_token'] ) && $forcefield['invalid_token'] ) ) {

						$abortthemechecks = true;

					} elseif ( $docheck ) {

						// --- check for name match (if specified) ---
						if ( !$checkname || ( $checkname && ( $checkname == $name ) ) ) {

							// --- set slug and URL ---
							$url = $apiurl . '/themes/' . $name;
							if ( $debug ) {echo "<!-- Theme Check URL: " . $url . " -->" . PHP_EOL;}

							// --- get API response data ---
							$check = false;
							$response = forcefield_get_response_data( $url, $args );
							if ( $response ) {
								if ( $debug ) {
									echo "<!-- Response: " . print_r( $response, true) . " -->" . PHP_EOL;
								}
								$check = forcefield_vulnerability_check( $response, $details['Version'] );
								if ( $check ) {
									$newitems = forcefield_vulnerability_is_new( $check, $existing );
									if ( $newitems ) {
										$new['themes'] = array_merge( $new['themes'], $newitems );
									}
									$report['themes'][$name] = $check;
								} elseif ( isset( $report['themes'][$name] ) ) {
									unset( $report['themes'][$name] );
								}
							}
							if ( isset( $forcefield['api_overload']) && $forcefield['api_overload' ] ) {
								// --- store current theme for resuming ---
								// 0.9.9: added for when API overloaded
								update_option( 'forcefield_check_theme', $name );
							} elseif ( $response ) {
								// 1.0.1: record theme-specific check time
								forcefield_vulnerability_update_check_time( 'themes', $name, $version, $check );
							}
						}
					}
				}
			}
        }
        // 1.0.0: record last check time whether aborted or not
        // (so as to prevent continual re-attempts)
        if ( count( $themes > 0 ) ) {
	        $lastcheck['themes'] = time();
	    }
    }

    // --- maybe update last checked times ---
    if ( isset( $lastcheck['core'] ) || isset( $lastcheck['plugins'] ) || isset( $lastcheck['themes'] ) ) {
        $checktimes = get_option( 'forcefield_vulnerability_checks' );
        if ( !$checktimes ) {$checktimes = array();}
        $types = array( 'core', 'plugins', 'themes' );
        foreach ( $types as $type ) {
            if ( isset( $lastcheck[$type] ) ) {
            	$checktimes[$type] = $lastcheck[$type];
            }
        }
        // 1.0.0: fix to variable used (latcheck)
        update_option( 'forcefield_vulnerability_checks', $checktimes );
    }

    // --- save current report data ---
    if ( count( $report) > 0 ) {
    	// 1.0.1: added filter for further data usage/integration
    	$report = apply_filters( 'forcefield_vulnerability_report', $report, $type, $checkname, $lastcheck, $new );
    	update_option( 'forcefield_vulnerability_report', $report );
    }

    // --- send email alert if new vulnerabilities found ---
    // 1.0.1: added filter for further data usage/integration
    $new = apply_filters( 'forcefield_vulnerability_new', $new, $report );
    if ( count( $new ) > 0 ) {
    	forcefield_vulnerability_alert( $new );
    }

}

// -------------------------------
// Check Vulnerability for Version
// -------------------------------
function forcefield_vulnerability_check( $data, $version ) {
	$debug = false; // $debug = true;
	$list = array();
	$key = key( $data );
	foreach ( $data[$key]['vulnerabilities'] as $item ) {
		if ( $debug) {echo "<!-- " . print_r( $item, true ) . " -->";}
		// 1.0.0: remove v prefix if used for version
		$version = str_replace( 'v', '', $version );
		if ( version_compare( $version, $item['fixed_in'], '<' ) ) {
			if ( $debug ) {echo "<-- *** VULNERABILITY FOUND *** -->";}
			$list[] = $item;
		}
	}
	if ( count( $list ) > 0 ) {
		return $list;
	}
	return false;
}

// ------------------
// Update Check Times
// ------------------
// 1.0.1: added for further check order optimization
function forcefield_vulnerability_update_check_time( $type, $slug, $version, $check ) {
	$check_time = time();
	$check_times = get_option( 'forcefield_vulnerability_check_times' );
	if ( !$check_times ) {$check_times = array();}
	if ( 'core' == $type ) {
		$check_times['core'][$version]['time'] = $check_time;
		$check_times['core'][$version]['flag'] = $check;
	} else {
		$check_times[$type][$slug][$version]['time'] = $check_time;
		$check_times[$type][$slug][$version]['flag'] = $check;
	}
	update_option( 'forcefield_vulnerability_check_times', $check_times );
}

// --------------------------
// Check Vulnerability is New
// --------------------------
function forcefield_vulnerability_is_new( $items, $existing ) {
    $debug = false; // $debug = true;
    if ( $debug ) {
    	echo "<!-- Existing: " . print_r( $existing, true )." -->";
    }
    $newitems = array();
    if ( count( $existing ) > 0 ) {
        // --- loop all items ---
        foreach ( $items as $item ) {
            $found = false;
            // --- loop existing items ---
            foreach ( $existing as $existingitem ) {
                if ( $item['id'] == $existingitem['id'] ) {
                    if ( $debug ) {
                    	echo "<!-- *** FOUND NEW *** -->";
                    }
                    $found = true;
                }
            }
            // --- add to new if not found ---
            if ( !$found ) {
            	$newitems[] = $item;
            }
        }
    } else {
    	$newitems = $items;
    }

    if ( count( $newitems ) > 0 ) {
    	return $newitems;
    }
    return false;
}

// ---------------------
// Get API Response Data
// ---------------------
// 0.9.9: set default method to wp_remote_get (with Curl fallback)
// 1.0.0: removed Curl fallback (handled by HTTP API)
function forcefield_get_response_data( $url, $args, $method = 'get' ) {

    global $forcefield;

    // --- revert back to API v2 if token failed ---
    // 1.0.1: just abort as v2 API deprecated
    if ( isset( $forcefield['invalid_token'] ) && $forcefield['invalid_token'] ) {
    	return false;
        // if (isset($args['headers'])) {unset($args['headers']);}
        // $url = str_replace('/v3/', '/v2/', $url);
    }

    // --- check for ssl verify override ---
    // 1.0.0: added for error fallback handling
    if ( isset( $forcefield['ssl_verify'] ) ) {
    	$args['sslverify'] = $forcefield['ssl_verify'];
    }

    // --- bug out if external requests are blocked ---
    if ( defined( 'WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL ) {
        $allowed = false;
        if ( defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
            // --- or allow if host exception is defined ---
            $hosts = explode( ',', WP_ACCESSIBLE_HOSTS );
            $valid = array( 'www.wpvulndb.com', 'wpvulndb.com' );
            foreach ( $hosts as $host ) {
            	$host = strtolower( trim( $host ) );
                if ( in_array( $host, $valid ) ) {$allowed = true;}
            }
        }
        if ( !$allowed ) {
        	// 1.0.1: add an admin notice regarding block ?
        	add_action( 'admin_notices', 'forcefield_vulnerability_api_block_message' );
        	return false;
        }
    }

    // --- dev debug switch ---
    $debug = false; // $debug = true;

    // --- get URL with wp_remote_get ---
    $response = wp_remote_get( $url, $args );

    // --- check for response errors ---
    if ( is_wp_error( $response ) ) {
        $errors = $response->errors;
        foreach ( $errors as $error ) {
            if ( stristr( $error, 'cURL error 35' ) ) {
                // --- set sslverify to false and try again ---
                $forcefield['ssl_verify'] = $args['sslverify'] = false;
                $response = wp_remote_get( $url, $args );
            }
        }
        if ( is_wp_error($response ) ) {
            if ( $debug ) {
            	echo "<!-- Response Error: " . print_r( $response, true ) . " -->" . PHP_EOL;
            }
            return false;
        }
    }

    if ( $debug ) {
    	echo "<!-- API Response: " . print_r( $response, true ) . " -->" . PHP_EOL;
    }
    if ( !is_wp_error( $response ) ) {
        $body = wp_remote_retrieve_body( $response );
        if ( stristr( $body, 'Retry later' ) ) {
            $forcefield['api_overload'] = true;
            return false;
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( $debug ) {
        	echo "<!-- Body: " . $body . " -->" . PHP_EOL;
        }
        $result = (array) json_decode( $body, true );
    }

    // --- check result ---
    // 0.9.9: moved out
    if ( isset( $result ) && !empty( $result ) && is_array( $result ) ) {
        if ( $debug ) {
        	echo "<!-- Result: " . print_r( $result, true ) . " -->" . PHP_EOL;
        }
        // 0.9.9: added check for API token error
        if ( isset( $result['error'] ) && stristr( $result['error'], 'HTTP Token: Access denied' ) ) {
            $forcefield['invalid_token'] = true;
            // 1.0.1: return false to prevent looping
            return false;
        } elseif ( isset( $result['error'] ) && stristr( $result['error'], 'Not found' ) ) {
            return false;
        } else {
            if ( $debug ) {
            	echo "<!-- Response Code: " . $code . " -->" . PHP_EOL;
            }
            if ( 200 == $code ) {
            	return $result;
            }
        }
    }

    return false;
}

// ---------------------------------
// Vulnerability API Blocked Message
// ---------------------------------
// 1.0.1: added blocking rule detected message
function forcefield_vulnerability_api_block_message() {

	$message = __( 'Warning! ForceField is unable to use the Vulnerability API.', 'forcefield' ) . '<br>';
	$message .= sprintf( __( '%s is defined, preventing access to the API.', 'forcefield' ), 'WP_HTTP_BLOCK_EXTERNAL' ) . '<br>';
	$message .= sprintf( __( 'Please add "%s" to your defined list of %s', 'forcefield' ), 'wpvulndb.com', 'WP_ACCESSIBLE_HOSTS' );

	echo '<div class="notice notice-warning">' . esc_html( $message ) . '</div>';
}

// ---------------------------
// Create Vulnerabilities List
// ---------------------------
function forcefield_vulnerabilities_list( $type, $matchitems = null ) {

    $debug = false; // $debug = true;

    // --- get report ---
    $report = get_option( 'forcefield_vulnerability_report' );
    if ( !isset( $report[$type] ) ) {
    	return false;
    }
    if ( $debug ) {
    	echo "<!-- Report: " . print_r( $report[$type], true ) . " -->";
    }

    // --- get dismissed notices ---
    // 0.9.9: added dismissed item checking
    $dismissed = get_option( 'forefield_vulnerability_dismissed' );
    if ( !$dismissed ) {
    	$dismissed = array();
    }

    // --- clear theme and plugin cache ---
    // 1.0.0: added to ensure accurate matching
    wp_clean_plugins_cache( false );
    wp_clean_themes_cache( false );

    // --- get all resources for matching ---
    global $wp_version;
    if ( !function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    $themes = wp_get_themes();

    // --- loop report items for type ---
    $changed = false;
    $htmllist = $textlist = array();
    $itemcount = count( $report[$type] );
    foreach ( $report[$type] as $itemname => $items ) {

        // 1.0.0: add check if resource still installed
        if ( ( ( 'plugins' == $type ) && array_key_exists( $itemname, $plugins ) )
          || ( ( 'themes' == $type ) && array_key_exists( $itemname, $themes ) )
          || ( 'core' == $type ) ) {

            // 1.0.0: fix to loop multiple items
            foreach ( $items as $i => $item ) {

                // --- check if notice has been dismissed  ---
                // 0.9.9: added notice dismissal check
                if ( !in_array( $item['id'], $dismissed ) ) {

                    // --- recheck vulnerabilities ---
                    // (as updates may have occurred since found)
                    $skip = $updateurl = false;
                    if ( 'core' == $type ) {
                        $version = $wp_version;
                        // 0.9.9: fix to version compare clearing updated items
                        // 1.0.0: fix again for when version equals fixed version
                        if ( $debug ) {
                        	echo "<!-- Core: " . $version . " - Fixed in " . $item['fixed_in'] . " -->";
                        }
                        if ( version_compare( $version, $item['fixed_in'], '>=' ) ) {
                            if ( $debug ) {
                            	echo "<!-- FIXED -->";
                            }
                            // 1.0.0: fix to handle multiple items
                            $changed = true;
                            unset( $report[$type][$itemname][$i] );
                            if ( 0 == count( $report[$type][$itemname] ) ) {
                                unset( $report[$type][$itemname] );
                                $skip = true;
                            }
                        } else {
                            // --- clear dismissed updates and force update check ---
                            delete_site_option( 'dismissed_update_core' );
                            delete_site_transient( 'update_core' );
                            $updates = get_core_updates();
                            if ( !isset( $updates[0]->response ) || ( 'latest' == $updates[0]->response ) ) {
                                // well that is strange, no core update is available yet
                            } else {
                            	$updateurl = admin_url( 'update-core.php' );
                            }
                        }
                    } elseif ( 'plugins' == $type ) {
                        foreach ( $plugins as $name => $details ) {
                            if ( $name == $itemname ) {
                                $displayname = $details['Name'];
                                $version = $details['Version'];
                                // 0.9.9: fix to version compare clearing updated items
                                // 1.0.0: fix again for when version equals fixed version
                                // 1.0.0: maybe remove v prefix (if used)
                                $version = str_replace( 'v', '', $version );
                                if ( $debug ) {
                                	echo "<!-- Plugin: ".$displayname." - " . $version . " - Fixed in " . $item['fixed_in'] . " -->";
                                }
                                if ( version_compare( $version, $item['fixed_in'], '>=' ) ) {
                                    if ( $debug ) {
                                    	echo "<!-- FIXED -->";
                                    }
                                    // 1.0.0: fix to handle multiple items
                                    $changed = true;
                                    unset( $report[$type][$itemname][$i] );
                                    if ( 0 == count( $report[$type][$itemname] ) ) {
                                        unset( $report[$type][$itemname] );
                                        $skip = true;
                                    }
                                } else {
                                    // 0.9.9: removed mismatched hasPackage check
                                    // TODO: add real check if plugin update available ?
                                    $updateurl = add_query_arg( 'action', 'upgrade-plugin', admin_url( 'update.php' ) );
                                    $updateurl = add_query_arg( 'plugin', urlencode( $name ), $updateurl );
                                    $updateurl = wp_nonce_url( $updateurl, 'upgrade-plugin_' . $name);
                                }
                            }
                        }
                    } elseif ( 'themes' == $type ) {
                        foreach ( $themes as $name => $details ) {
                            if ( $name == $itemname ) {
                                $displayname = $details['Name'];
                                $version = $details['Version'];
                                // 0.9.9: fix to version compare clearing updated items
                                // 1.0.0: fix again for when version equals fixed version
                                // 1.0.0: maybe remove v prefix (if used)
                                $version = str_replace( 'v', '', $version );
                                if ( $debug ) {
                                	echo "<!-- Theme: " . $displayname . " - " . $version . " - Fixed in " . $item['fixed_in'] . " -->";
                                }
                                if ( version_compare( $version, $item['fixed_in'], '>=' ) ) {
                                    if ( $debug ) {
                                    	echo "<!-- FIXED -->";
                                    }
                                    // 1.0.0: fix to handle multiple items
                                    $changed = true;
                                    unset( $report[$type][$itemname][$i] );
                                    if ( 0 == count( $report[$type][$itemname] ) ) {
                                        unset( $report[$type][$itemname] );
                                        $skip = true;
                                    }
                                } else {
                                    // 0.9.9: removed mismatched hasPackage check
                                    // TODO: add real check if theme update available ?
                                    $updateurl = add_query_arg( 'action', 'upgrade-theme', admin_url( 'update.php' ) );
                                    $updateurl = add_query_arg( 'theme', urlencode( $name ), $updateurl );
                                    $updateurl = wp_nonce_url( $updateurl, 'upgrade-theme_'.$name );
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // --- remove any missing resource from check list ---
            unset( $report[$type][$itemname] );
            $changed = $skip = true;
        }
        // 1.0.0: fix to set updated items array for current loop
        if ( isset( $report[$type][$itemname] ) ) {
        	$items = $report[$type][$itemname];
        }

        // --- maybe skip if removed ---
        if ( !$skip ) {

            // --- check if items specified for matching ---
            // 1.0.0: fix for matchitems (duplicate variable name items)
            $add = false;
            if ( is_null( $matchitems ) ) {
            	$add = true;
            } else {
                // --- match current item with passed items ---
                foreach ( $matchitems as $matchitem ) {
                    if ( $item['id'] == $matchitem['id'] ) {
                    	$add = true;
                    }
                }
            }

            if ( $add ) {

                if ( $debug ) {
                	echo "<!-- Item: " . print_r( $item, true) . " -->";
                }

                // --- set list item details ---
                // 0.9.9: allow for listing of multiple vulnerabilities
                $links = $fixed = $icons = $sinces = '';
                $textttitles = $texturls = '';
                foreach ( $items as $details ) {

                    // --- linked vulnerability titles ---
                    $linkurl = "https://wpvulndb.com/vulnerabilities/" . $details['id'];
                    $links .= "<a href='" . esc_url( $linkurl ) . "' target='_blank'>";
                    $links .= esc_html( $details['title'] )."</a><br>";
                    $textdetails .= __( 'Vulnerability', 'forcefield' ) . ": " . $details['title'] . ":\n";
                    $textdetails .= $linkurl . "\n";

                    // --- check if fixed yet ---
                    if ( empty( $details['fixed_in'] ) ) {
                        $notfixedtext = __( 'Not Fixed Yet!', 'forcefield' );
                        $fixed .= '<span style="color:#d50000;">' . esc_html( $notfixedtext ) . '</span><br>';
                        $icons .= '<span class="dashicons dashicons-warning" style="color:#d50000;"></span><br>';
                        $textdetails .= "* " . $notfixedtext . "\n";
                    } else {
                        $fixedtext = sprintf( __( 'Fixed in Version %s', 'forcefield' ), $details['fixed_in'] );
                        $fixed .= '<span style="color:#cc7700;">' . esc_html( $fixedtext ) . '</span><br>';
                        $icons .= '<span class="dashicons dashicons-warning" style="color:#cc7700;"></span><br>';
                        $textdetails .= "* " . esc_attr( $fixedtext ) . "\n";
                    }
                }

                // --- set HTML list ---
                // 1.0.0: top align plugin title and add max-widths
                $html = '<tr id="ff-vuln-id-' . esc_attr( $item['id'] ) . '">';
                    $html .= '<td>' . $icons . '</td>';
                    $html .= '<td style="vertical-align:top;max-width:150px;">' . esc_html( $displayname ) . "</td>";
                    $html .= '<td style="max-width:400px;">' . $links . '</td>';
                    $html .= '<td>'.$fixed.'</td>';
                    $html .= '<td><span style="color:#d50000;"> ' . esc_attr( __( 'Installed Version', 'forcefield' ) ) . ': ' . esc_html( $version ) . '</span></td>';
                    if ( $updateurl ) {
                        $html .= "<td><a href='" . esc_url( $updateurl ) . "'>";
                            // 0.9.9: converted update link to button
                            // 1.0.0: use different button class and value for core update
                            if ( 'core' == $type ) {
                            	$buttonclass = 'button-primary';
                            	$update = __( 'Upgrades Page','forcefield' );
                            } else {
                            	$buttonclass = 'button-secondary';
                            	$update = __( 'Update Now','forcefield' );
                            }
                            $html .= '<input type="button" class="' . esc_attr( $buttonclass ) . '" value="' . esc_attr( $update ) . '">';
                        $html .= '</a></td>';
                    } else {
                    	$html .= '<td></td>';
                    }
                    if ( ( 'plugins' == $type ) || ( 'themes' == $type ) ) {
                        // 0.9.9: only add checkbox if more than one vulnerability
                        // 1.0.0: fix to value variable (use itemname instead of name)
                        if ( $itemcount > 1 ) {
                            $html .= '<td><input type="checkbox" name="checked[]" value="' . esc_attr( $itemname ) . '" checked="checked"></td>';
                        } else {
                        	$html .= '<td></td>';
                        }
                    } else {
                    	$html .= '<td></td>';
                    }
                    // 0.9.9: added notice dismissal button
                    $html .= '<td style="vertical-align:bottom;">';
                        $dismissurl = add_query_arg( 'action', 'forcefield_vulnerability_dismiss', admin_url( 'admin-ajax.php' ) );
                        $dismissurl = add_query_arg( 'type', $type, $dismissurl );
                        $dismissurl = add_query_arg( 'id', $item['id'], $dismissurl );
                        // &type='.$type.'&id='.esc_attr($item['id']);
                        $title = __( 'Dismiss this Notice', 'forcefield' );
                        $html .= '<a href="' . esc_url( $dismissurl ) . '" target="forcefield-dismiss-frame" style="text-decoration:none;" title="' . esc_attr( $title ) . '">';
                        $html .= '<div class="dashicons dashicons-dismiss" style="font-size:16px;"></div></a>';
                    $html .= '</td>';

                $html .= '</tr>';
                $htmllist[] = $html;

                // --- set text item list ---
                $text = $displayname . ' - ' . __( 'Installed Version', 'forcefield' ) . ': ' . $version . "\n";
                $text .= $textdetails;
                if ( $updateurl ) {
                	$text .= __( 'Update URL', 'forcefield' ) . ":\n" . $updateurl . "\n";
                }
                $textlist[] = $text;
            }
        }
    }

    // --- maybe update vulnerability report ---
    if ( $changed ) {
    	update_option('forcefield_vulnerability_report', $report );
    }

    // --- output vulnerability list ---
    // 0.9.9: do not wrap HTML with table tag here
    // 0.9.9: fix to check of incorrect variable name (list)
    if ( count( $htmllist ) > 0 ) {
        $data = array(
            'text'  => implode( "\n", $textlist ),
            'html'  => implode( PHP_EOL, $htmllist ),
            'count' => count( $textlist ),
        );
        return $data;
    }
    return false;
}

// ----------------------------
// Admin Vulnerabilitues Notice
// ----------------------------
add_action( 'admin_notices', 'forcefield_vulnerability_notice' );
function forcefield_vulnerability_notice() {

    $debug = false; // $debug = true;
    $output = '';

    // --- Core Vulnerability Notices ---
    if ( current_user_can( 'manage_options') || current_user_can('update_core' ) ) {
        $corelist = forcefield_vulnerabilities_list( 'core' );
        if ( $corelist && ( $corelist['count'] > 0 ) ) {

            // --- core vulnerabilities div ---
            $output .= '<div id="ff-core-vulnerabilities">';

            // --- core vulnerabilities table header ---
            $output .= '<table cellspacing="10">';
            $output .= '<tr>';
                $output .= '<td colspan="8" style="font-size:18px;">';
                $output .= '<b><span style="color:#d50000;">' . esc_html( __( 'Warning!', 'forcefield' ) ) . '</span> ';
                $output .= esc_html( __( 'Core Vulnerabilities Found!', 'forcefield' ) ) . '</b>';
                $output .= '</td>';
            $output .= '</tr>';

            // --- core vulnerabilities rows ---
            $output .= $corelist['html'];

            // --- close table and div ---
            $output .= '</table></div>';
        }
    }

    // --- Plugin Vulnerability Notices ---
    if ( current_user_can('update_plugins' ) ) {
        $pluginlist = forcefield_vulnerabilities_list( 'plugins' );
        if ( $debug ) {
        	echo "<!-- Plugin Vulnerability List: " . print_r( $pluginlist, true ) . " -->";
        }
        if ( $pluginlist && ( $pluginlist['count'] > 0 ) ) {

            // --- plugin vulnerabilities div ---
            $output .= '<div id="ff-plugin-vulnerabilities">';

            // --- update selected themes form ---
            // 1.0.0: change to action URL (frmo update.php), nonce, action and post method
            $nonce = wp_create_nonce( 'upgrade-core' );
            $updgradeurl = admin_url( 'update-core.php?action=do-plugin-upgrade' );
            $output .= '<form id="ff-plugin-form" action="' . esc_url( $upgradeurl ) . '" method="post">';
            $output .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . "'>";

            // --- plugin list table header row ---
            $output .= '<table cellspacing="10">';
            $output .= '<tr>';
                $output .= '<td colspan="5" style="font-size:18px;">';
                    $output .= '<b><span style="color:#d50000;">' . esc_html( __('Warning!', 'forcefield' ) ) . '</span> ';
                    $output .= esc_html( __( 'Forcefield has detected', 'forcefield' ) ) . ' ';
                    if ( $pluginlist['count'] > 1 ) {
                        $output .= esc_html( __( 'Plugin Vulnerabilities', 'forcefield' ) ) . '</b> ';
                        $output .= esc_html( sprintf( __('in %d Plugins', 'forcefield' ), $pluginlist['count'] ) ) . '!</b>';
                    } else {
                    	$output .= esc_html( __( 'a Plugin Vulnerability', 'forcefield' ) ) . '</b>';
                    }
                $output .= '</td><td colspan="3">';
                // 0.9.9: only show button if more than one vulnerability
                if ( $pluginlist['count'] > 1 ) {
                    $output .= '<input class="button-primary" type="submit" value="' . esc_attr( __( 'Update Selected Plugins', 'forcefield' ) ) . '">';
                }
                $output .= '</td>';
            $output .= '</tr>';

            // --- theme list table rows ---
            $output .= $pluginlist['html'];

            // --- close form table and div ---
            $output .= '</table></form></div>';

        }
    }

    // --- Theme Vulnerability Notices ---
    if ( current_user_can( 'update_themes' ) ) {
        $themelist = forcefield_vulnerabilities_list( 'themes' );
        if ( $themelist && ( $themelist['count'] > 0 ) ) {

            // --- theme vulnerabilities div ---
            $output .= '<div id="ff-theme-vulnerabilities">';

            // --- update selected themes form ---
            // 1.0.0: add form method post
            $nonce = wp_create_nonce( 'update-core' );
            $upgradeurl = admin_url( 'update-core.php?action=do-theme-upgrade' );
            $output .= '<form id="ff-theme-form" action="' . esc_url( $upgradeurl ) . '" method="post">';
            $output .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';

            // --- theme list table header row ---
            $output .= '<table cellspacing="10">';
            $output .= '<tr id="vulnerability-' . esc_attr( $item['id'] ) . '">';
                $output .= '<td colspan="5" style="font-size:18px;">';
                    $output .= '<b><span style="color:#d50000;">' . esc_html( __( 'Warning!', 'forcefield' ) ) . '</span> ';
                    $output .= esc_html( __( 'ForceField has detected', 'forcefield' ) ) . ' ';
                    if ( count( $themelist['count'] ) > 1 ) {
                        $output .= esc_html( __( 'Theme Vulnerabilities', 'forcefield' ) ) . '</b> ';
                        $output .= esc_html( sprintf( __('in %d Themes', 'forcefield' ), $themelist['count'] ) ) . '!';
                    } else {
                    	$output .= esc_html( __( 'a Theme Vulnerability', 'forcefield' ) ) . '</b>';
                    }
                $output .= '</td><td colspan="3">';
                // 0.9.9: only show button if more than one vulnerability
                if ( $themelist['count'] > 1 ) {
                    $output .= '<input class="button-primary" type="submit" value="' . esc_attr( __( 'Update Selected Themes', 'forcefield' ) ) . '">';
                }
                $output .= '</td>';
            $output .= '</tr>';

            // --- theme list table rows ---
            $output .= $themelist['html'];

            // --- close form table and div ---
            $output .= '</table></form></div>';
        }
    }

    // --- output vulnerability alert notice ---
    // 0.9.9: added missing vulnerability notices output
    if ( '' != $output ) {

        // --- output notices ---
        // 0.9.9: change to error notice class to display red
        echo '<div class="error notice" id="forcefield-vulnerability-notice" style="font-size:14px; line-height:22px; margin:0;">';
            echo $output;
        echo '</div>';

        // --- notice dismissal iframe ---
        // 1.0.1: use about:blank for src instead of javascript:void(0)
        echo '<iframe src="about:blank" name="forcefield-dismiss-frame" id="forcefield-dismiss-frame" style="display:none;" frameborder="0"></iframe>';
    }
}

// --------------------------------
// AJAX Dimiss Vulnerability Notice
// --------------------------------
// 0.9.9: added AJAX to dismiss vulnerability notices
add_action( 'wp_ajax_forcefield_vulnerability_dismiss', 'forcefield_vulnerability_dismiss' );
function forcefield_vulnerability_dismiss() {

    // --- check and sanitize vulnerability ID ---
    if ( !isset( $_REQUEST['id'] ) ) {
    	exit;
    }
    $id = absint( $_REQUEST['id'] );
    if ( $id < 1 ) {
    	exit;
    }

    // --- get item type for matching ---
    if ( !isset($_REQUEST['type'] ) ) {
    	exit;
    }
    $type = $_REQUEST['type'];
    $valid = array( 'core', 'plugins', 'themes' );
    if ( !in_array( $type, $valid ) ) {
    	exit;
    }
    if ( 'core' == $type ) {
        if ( !current_user_can( 'manage_options' ) && !current_user_can( 'update_core' ) ) {
        	exit;
        }
    } elseif ( ( 'plugins' == $type ) && !current_user_can( 'update_plugins' ) ) {
    	exit;
    } elseif ( ( 'themes' == $type ) && !current_user_can( 'update_themes' ) ) {
    	exit;
    }

    // --- check if already dismissed ---
    $dismissed = get_option( 'forcefield_vulnerability_dismissed' );
    if ( $dismissed && is_array( $dismissed ) && in_array( $id, $dismissed ) ) {
    	exit;
    }

    // --- check vulnerability item ID ---
    $report = get_option( 'forcefield_vulnerability_report' );
    if ( !$report || !isset( $reports[$type] ) || !is_array( $report[$type] ) || ( count( $report[$type] ) < 1 ) ) {
    	exit;
    }
    $found = false;
    foreach ( $report[$type] as $itemname => $item ) {
        if ( $id == $item['id'] ) {
        	$found = true;
        }
    }
    if ( !$found ) {
    	exit;
    }

    // --- save to dismissed vulnerabilities list ---
    if ( !$dismissed ) {
    	$dismissed = array();
    }
    $dismissed[] = $id;
    update_option( 'forcefield_vulnerability_dismissed' );

    // --- remove vulnerability row ---
    // TODO: check and remove table header if only one row ?
    echo "<script>parent.document.getElementById('ff-vuln-id-" . esc_js( $id ) . "').style.display = 'none';</script>";

    exit;
}

// ------------------------
// Plugin Table List Alerts
// ------------------------
// 1.0.1 added missing argument number
add_action( 'after_plugin_row', 'forcefield_plugin_row_alert', 9, 3 );
function forcefield_plugin_row_alert( $plugin_file, $plugin_data, $status ) {

    // --- only show if user can update plugins ---
    if ( !current_user_can( 'update_plugins' ) ) {
    	return;
    }

    // --- get / set plugin vulnerabilities ---
    global $forcefield, $wp_list_table;
    if ( isset( $forcefield['plugin_vulns'] ) ) {
    	$plugins = $forcefield['plugins_vulns'];
    } else {
        $vulns = get_option( 'forcefield_vulnerability_report' );
        if ( $vulns && isset( $vulns['plugins'] ) ) {
            $plugins = $forcefield['plugins_vulns'] = $vulns['plugins'];
        } else {
        	$plugins = $forcefield['plugins_vulns'] = array();
        }
    }

    // --- maybe display plugin list vulnerability row ---
    if ( count($plugins) > 0 ) {
        if ( isset( $plugins[$plugin_file] ) ) {

            // --- plugin list vulnerability row wrapper ---
            $columncount = $wp_list_table->get_column_count();
            echo '<tr class="plugin-update-tr">';
            echo '<td colspan="' . esc_attr( $columncount ) . '" class="plugin-update colspanchange">';
            echo '<div class="notice inline notice-error">';

                // --- vulnerability list table ---
                echo '<table width="100%">';
                foreach ( $plugins[$plugin_file] as $details ) {

                    // --- check if fixed ---
                    if ( empty( $details['fixed_in'] ) ) {
                        $fixedtext = __( 'Not Fixed Yet!', 'forcefield');
                        $fixed .= '<span style="color:#d50000;">' . esc_html( $fixedtext ) . '</span><br>';
                        $icon .= '<span class="dashicons dashicons-warning" style="color:#d50000;"></span><br>';
                    } else {
                        $fixedtext = sprintf( __( 'Fixed in Version %s', 'forcefield' ), $details['fixed_in'] );
                        $fixed .= '<span style="color:#cc7700;">' . esc_html( $fixedtext ) . '</span><br>';
                        $icon .= '<span class="dashicons dashicons-warning" style="color:#cc7700;"></span><br>';
                    }

                    // --- vulnerability item row ---
                    $linkurl = "https://wpvulndb.com/vulnerabilities/" . $details['id'];
                    echo '<tr>';
                        echo '<td>' . $icon . '</td>';
                        echo '<td>' . esc_html( __( 'ForceField detected a vulnerability!' ) ) . '</td>';
                        echo '<td><a href="' . esc_url( $linkurl ) . '" target="_blank">' . esc_html( $details['title'] ) . '</a></td>';
                        echo '<td>'.$fixed.'</td>';
                    echo '</tr>';
                }
                echo '</table>';
            echo '</div></td></tr>';
        }
    }
}

// -------------------------------
// Send Vulnerability Alert Emails
// -------------------------------
function forcefield_vulnerability_alert( $new, $debug=false ) {

    // --- group alert types by email addresses ---
    $addresses = array();
    $types = array( 'core', 'plugin', 'theme' );
    foreach ( $types as $type ) {
        $email = forcefield_get_setting( 'vuln_' . $type . '_emails' );
        // 0.9.9: fix to non-plural settings key mismatch
        if ( in_array( $type, array( 'plugin', 'theme' ) ) ) {$type .= 's';}
        if ( $email && ( '' != trim( $email ) ) ) {
            if ( strstr( $email, ',' ) ) {
                $emails = explode( ',', $email );
                foreach ( $emails as $address ) {
                	$address = trim( $address );
                	$addresses[$address][] = $type;
                }
            } else {
                $email = trim( $email );
                $addresses[$email][] = $type;
            }
        }
    }
    if ( $debug ) {
    	echo "<!-- Email Addresses: " . print_r( $addresses, true ) . " -->" . PHP_EOL;
    }

    // --- bug out if no one to email ---
    if ( 0 == count( $addresses ) ) {
    	return;
    }

    // --- get vulnerability lists ---
    $corelist = forcefield_vulnerabilities_list( 'core', $new['core'] );
    $pluginlist = forcefield_vulnerabilities_list( 'plugins', $new['plugins'] );
    $themelist = forcefield_vulnerabilities_list( 'themes', $new['themes'] );
    $lists = array( 'core' => $corelist, 'plugins' => $pluginlist, 'themes' => $themelist );
    if ( $debug ) {
        echo "Core Check: " . print_r( $corelist, true ) . "<br>";
        echo "Plugin Check: " . print_r( $pluginlist, true ) . "<br>";
        echo "Theme Check: " . print_r( $themelist, true ) . "<br>";
    }

    // --- loop email addresses and send relevent alerts ---
    foreach ( $addresses as $address => $types ) {

        // --- get total vulnerability count ---
        $totalcount = 0;
        if ( $corelist && ( $corelist['count'] > 0 ) && in_array( 'core', $types ) ) {
            $totalcount = $corelist['count'];
        }
        if ( $pluginlist && ( $pluginlist['count'] > 0 ) && in_array( 'plugins', $types ) ) {
            $totalcount = $totalcount + $pluginlist['count'];
        }
        if ( $themelist && ( $themelist['count'] > 0 ) && in_array( 'themes', $types ) ) {
            $totalcount = $totalcount + $themelist['count'];
        }
        if ( 0 == $totalcount ) {
        	break;
        }

        // --- set email subject ---
        $blogname = get_option( 'blogname' );
        $subject = '[ForceField Alert] ' . $blogname;
        if ( $totalcount > 1 ) {
        	$subject = ': ' . $totalcount . ' ' . __( 'New Vulnerabilities Detected!', 'forcefield' );
        } else {
        	$subject .= ': ' . __( 'New Vulnerability Detected!', 'forcefield' );
        }

        // --- set email body ---
        $body = __( 'ForceField has detected vulnerabilities in your installation.', 'forcefield' ) . "\n";
        $body .= __( '(via querying the WPVulnDB API with your relevant version data.)', 'forcefield' ) . "\n";
        $body .= __( 'For improved site security, please update your installed software.', 'forcefield' ) . "\n\n";

        if ( $corelist && ( $corelist['count'] > 0 ) && in_array( 'core', $types ) ) {
            $body .= "\n=== " . __( 'Core Vulnerabilities', 'forcefield');
            $body .= " (" . $corelist['count'] . ") ===\n";
            $body .= $corelist['text'];
        }
        if ( $pluginlist && ( $pluginlist['count'] > 0 ) && in_array( 'plugins', $types ) ) {
            $body .= "\n=== " . __( 'Plugin Vulnerabilities', 'forcefield' );
            $body .= " (" . $pluginlist['count'] . ") ===\n";
            // 0.9.9: fix to plural variable typo
            $body .= $pluginlist['text'];
        }
        if ( $themelist && ( $themelist['count'] > 0 ) && in_array( 'themes', $types ) ) {
            $body .= "\n=== " . __( 'Theme Vulnerabilities', 'forcefield' );
            $body .= " (" . $themelist['count'] . ") ===\n";
            $body .= $themelist['text'];
        }

        $body .= "\n" . __( 'A full list of known vulnerabilities can be seen in your admin notices area.', 'forcefield' );
        $body .= "\n\n".'ForceField';

		// 1.0.1: filter email subject and body
        $subject = apply_filters( 'forcefield_vulnerability_email_subject', $subject, $lists, $totalcount );
        $body = apply_filters( 'forcefield_vulnerabilty_email_message', $body, $lists, $totalcount );

        // --- send the email ---
        $sent = wp_mail( $address, $subject, $body );
        if ( $debug ) {
            echo "Sending alert to: " . $address . "<br>" . PHP_EOL;
            echo "Subject: " . $subject . "<br>" . PHP_EOL;
            echo "Message: " . str_replace( "\n", "<br>", $message ) . "<br>" . PHP_EOL;
        	echo "Send Result: " . $sent . "<br>" . PHP_EOL;
        }

    }
}

// ------------------------------
// Vulnerability Alert Email Test
// ------------------------------
// 0.9.9: added to test resending of all alert emails
add_action( 'init', 'forcefield_vulnerability_alert_test' );
function forcefield_vulnerability_alert_test() {
    if ( !current_user_can( 'manage_options' ) ) {
    	return;
    }
    if ( !isset( $_GET['ff-alert-test'] ) || ( 'yes' != $_GET['ff-alert-test'] ) ) {
    	return;
    }

    // --- send array of null values for all items instead of new ---
    $items = array('core' => null, 'plugins' => null, 'themes' => null);
    forcefield_vulnerability_alert( $items, true );
    exit;
}
