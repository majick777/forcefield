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
// - Send Vulnerability Alert Emails
// -Vulnerability Alert Email Test

// Development TODOs
// -----------------
// - test plugin / theme vulnerability notice multi-select updates
// ? trigger vulnerability rechecks on plugin or theme rollback 


// ----------------------------
// Schedule Vulnerability Check
// ----------------------------
add_action('plugins_loaded', 'forcefield_vulnerabilities_check_schedule');
function forcefield_vulnerabilities_check_schedule() {

    global $forcefield;

    // --- maybe reset vulnerability check ---
    if (isset($_GET['resetcheck'])) {
        if ( ($_GET['resetcheck'] == 'all') && current_user_can('manage_options') ) {
            delete_option('forcefield_vulnerability_checks');
        } else {
            $reset = $_GET['resetcheck'];
            if (in_array('reset', array('core', 'plugins', 'themes'))) {
                $checktimes = get_option('forcefield_vulnerability_checks');
                if (isset($checktimes[$reset])) {
                    $doreset = false;
                    if ( ($reset == 'core') && (current_user_can('manage_options') || current_user_can('update_core')) ) {$doreset = true;}
                    elseif ( ($reset == 'plugins') && current_user_can('update_plugins') ) {$doreset = true;}
                    elseif ( ($reset == 'themes') && current_user_can('update_themes') ) {$doreset = true;}
                    if ($doreset) {
                        unset($checktimes[$reset]);
                        if (count($checktimes) > 0) {
                            update_option('forcefield_vulnerability_checks', $checktimes);
                        } else {delete_option('forcefield_vulnerability_checks');}
                    }
                }
            }
        }
    }

    // --- check check times ---
    $checktimes = get_option('forcefield_vulnerability_checks');
    if (!$checktimes) {$checktimes = array();}

    if (!isset($checktimes['core'])) {$checktimes['core'] = '';}
    if (!isset($checktimes['plugins'])) {$checktimes['plugins'] = '';}
    if (!isset($checktimes['themes'])) {$checktimes['themes'] = '';}

    $intervals = forcefield_get_intervals();

    // --- loop check types ---
    foreach ($checktimes as $type => $lastchecked) {

        // --- get check frequenecy for type ---
        $frequency = forcefield_get_setting('vuln_check_'.$type);
        if ($frequency && ($frequency != '') && ($frequency != 'none')) {

            // --- convert schedule frequency to interval ---
            foreach ($intervals as $key => $value) {
                if ($frequency == $key) {$interval = $value['interval'];}
            }

            // --- check whether we next check is scheduled ---
            $docheck = false;
            if ($lastchecked == '') {$docheck = true;}
            elseif (isset($interval)) {
                $nextrun = $lastchecked + $interval;
                if (time() >= $nextrun) {$docheck = true;}
            }

            // --- trigger vulnerability checks via iframe ---
            if ($docheck) {
                // 0.9.9: do checks in background not on shutdown
                if (!isset($forcefield['vulnerability-checks'])) {$forcefield['vulnerability-checks'] = array();}
                $forcefield['vulnerability-checks'][] = $type;
                if (!has_action('wp_footer', 'forcefield_trigger_vulnerability_check')) {
                    add_action('wp_footer', 'forcefield_trigger_vulnerability_check', 99);
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
    if (count($checks) == 1) {$checkstring = $checks;} else {$checkstring = implode(',', $checks);}
    $checkurl = add_query_arg('action', 'resource_check', admin_url('admin-ajax.php'));
    $checkurl = add_query_arg('types', $checkstring, $checkurl);

    // --- load on document ready for admin ---
    if (current_user_can('manage_options')) {
        echo "<script>jQuery(document).ready(function() {jQuery('#resource-check').attr('src', '".$checkurl."');});</script>";
        $checkurl = 'javascript:void(0);';
    }

    // --- iframe to trigger checks ---
    echo "<iframe id='resource-check' src='".$checkurl."' style='display:none;'></iframe>";
}

// --------------------
// AJAX Run Check Types
// --------------------
// 0.9.9: added to run checks in background
add_action('wp_ajax_resource_check', 'forcefield_vulnerability_check_ajax');
add_action('wp_ajax_nopriv_resource_check', 'forcefield_vulnerability_check_ajax');
function forcefield_vulnerability_check_ajax() {
    if (!current_user_can('manage_options')) {forcefield_force_connection_closed();}
    $types = $_REQUEST['types'];
    if (strstr($types, ',')) {$checks = explode(',', $types);} else {$checks = array($types);}
    foreach ($checks as $type) {
        if (current_user_can('manage_options')) {echo "Doing Check ".$type.PHP_EOL;}
        call_user_func('forcefield_vulnerabilities_check_'.$type);
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
    set_time_limit(0);
    ignore_user_abort(true);
    ob_end_clean();
    ob_start();
    echo '0';
    $size = ob_get_length();
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    header("Content-Length: ".$size);
    if (function_exists('http_response_code')) {http_response_code(200);}
    ob_end_flush();
    @ob_flush();
    flush();
}

// -----------------------------
// Check Vulnerability Callbacks
// -----------------------------
function forcefield_vulnerabilities_check_all() {forcefield_vulnerabilities_check('all');}
function forcefield_vulnerabilities_check_core() {forcefield_vulnerabilities_check('core');}
function forcefield_vulnerabilities_check_plugins() {forcefield_vulnerabilities_check('plugins');}
function forcefield_vulnerabilities_check_themes() {forcefield_vulnerabilities_check('themes');}

// ---------------------------
// Store Pre-Installation Data
// ---------------------------
add_action('upgrader_pre_install', 'forcefield_vulnerability_store_current', 10, 2);
function forcefield_vulnerability_store_current($response, $hook_extra) {

    global $forcefield;

    if (isset($hook_extra['action']) && ($hook_extra['action'] == 'install')) {
        if (isset($hook_extra['type'])) {
            $type = $hook_extra['type'];
            if ($type == 'plugin') {
                if (!function_exists('get_plugins')) {
                    require_once ABSPATH.'wp-admin/includes/plugin.php';
                }
                $forcefield['installed_plugins'] = array_keys(get_plugins());
            }
            if ($type == 'theme') {
                $forcefield['installed_themes'] = array_keys(wp_get_themes());
            }
        }
    }
    return $reponse;
}

// --------------------------------
// Vulnerability Check New Installs
// --------------------------------
add_action('upgrader_post_install', 'forcefield_vulnerability_check_new', 10, 3);
function forcefield_vulnerability_check_new($response, $hook_extra, $result) {

    global $forcefield;
    
    if (isset($hook_extra['action']) && ($hook_extra['action'] == 'install')) {
        if (isset($hook_extra['type'])) {
            $type = $hook_extra['type'];
            if ($type == 'plugin') {
                if (!function_exists('get_plugins')) {
                    require_once ABSPATH.'wp-admin/includes/plugin.php';
                }
                $plugins = get_plugins();
                foreach ($plugin as $name => $plugin) {
                    if (!in_array($name, $forcefield['installed_plugins'])) {$new = $name;}
                }
            }
            if ($type == 'theme') {
                $themes = wp_get_themes();
                foreach ($themes as $name => $theme) {
                    if (!in_array($name, $forcefield['installed_themes'])) {$new = $name;}
                }
            }
            if (isset($new)) {forcefield_vulnerabilities_check($type.'s', $new);}
        }
    }
    return $response;
}

// -----------------------------
// Perform Vulnerabilities Check
// -----------------------------
function forcefield_vulnerabilities_check($type='all', $checkname=false) {

    // --- dev debug switch ---
    $debug = false; // $debug = true; 

    // -- check if API already overloaded ---
    if (isset($forcefield['api_overload']) && $forcefield['api_overload']) {return;}

    // --- only allow name matches for plugins or themes ---
    if ( ($type == 'all') || ($type == 'core') ) {$checkname = false;}

    // --- get cached report ---
    $report = $lastcheck = array(); 
    $new = array('core' => '', 'plugins' => '', 'themes' => '');
    $cachedreport = get_option('forcefield_vulnerability_report');
    if ($cachedreport) {$report = $cachedreport;}

    // --- set default remote get args ---
    global $wp_version;
    $args = array(
        'timeout'     => 10,
        'user-agent'  => 'WordPress/'.$wp_version.'; '.home_url(),
    );

    // --- filter for sslverify ---
    // 0.9.9: added to help some users bypass SSL connection errors
    $sslverify = apply_filters('forcefield_vuln_ssl_verify', $sslverify);
    if (!$sslverify) {$args['sslverify'] = false;}
    
    // --- check for saved WP VulnDB Token ---
    $token = forcefield_get_setting('vuln_api_token');
    $verified = get_option('forcefield_wbvulndb_verified');
    if ($token && $verified) {
        // --- se WP VulnDB API v3 ---
        $apiurl = 'https://wpvulndb.com/api/v3';
        // --- add header if token is set ---
        $args['headers'] = array('Authorization: Token token='.$token);
    } else {
        // --- just use WP VulnDB API v2 ---
        $apiurl = 'https://wpvulndb.com/api/v2';
    }

    // --- filter remote get args ---
    $args = apply_filters('forcefield_vulnerablity_check_args', $args);

    // Check Core
    // ----------
    if ( ($type == 'core') || ($type == 'all') ) {

        // --- get existing vulnerabilities ---
        if (isset($report['core'])) {$existing = $report['core'];} else {$existing = array();}
        
        // --- set version and URL ---
        global $wp_version; $version = $wp_version;
        $url = $apiurl.'/wordpresses/'.str_replace('.', '', $version);
        if ($debug) {echo "<!-- Core Check URL: ".$url." -->".PHP_EOL;}

        // --- get API response data ---
        $response = forcefield_get_response_data($url, $args);
        if ($response) {
            if ($debug) {echo "<!-- Response: ".print_r($response,true)." -->".PHP_EOL;}
            $check = forcefield_vulnerability_check($response, $version);
            if ($check) {
                $newitems = forcefield_vulnerability_is_new($check, $existing);
                if ($newitems) {$new['core'] = array_merge($new['core'], $newitems);}
                $report['wordpress'] = $check;
            } elseif (isset($report['wordpress'])) {unset($report['wordpress']);}
        }
        $lastcheck['core'] = time();
    }

    // Check Plugins
    // -------------
    if ( ($type == 'plugins') || ($type == 'all') ) {

        // --- get all plugins ---
        if (!function_exists('get_plugins')) {
            require_once ABSPATH.'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();

        // --- loop all plugins ---
        $resumeat = get_option('forcefield_check_plugin');
        foreach ($plugins as $name => $details) {

            // --- maybe check if we have a current position ---
            // 0.9.9: added check resuming
            $docheck = true;
            if ($resumeat) {
                if ($name != $resumeat) {$docheck = false;}
                else {delete_option('forcefield_check_plugin'); $resumeat = false;}
            }

            // --- check if API overloaded ---
            // 0.9.9: added this check
            if (isset($forcefield['api_overload']) && $forcefield['api_overload']) {
                $abortchecks = true;
            } elseif ($docheck) {

                // --- check for name match (if specified) ---
                if (!$checkname || ($checkname && ($checkname == $name)) ) {

                    // --- get existing vulnerabilities ---
                    if (isset($report['plugins'])) {$existing = $report['plugins'];} else {$existing = array();}

                    // --- set plugin slug and URL ---
                    // $slug = sanitize_title($details['Name']);
                    // 0.9.9: get plugin slug from plugin subdirectory                
                    if (strstr($name, '/')) {$parts = explode('/', $name); $slug = $parts[0];}
                    else {$slug = str_replace('.php', '', $name);}

                    $url = $apiurl.'/plugins/'.$slug;
                    if ($debug) {echo "<!-- Plugin Check URL: ".$url." -->".PHP_EOL;}

                    // --- get API response data ---
                    $response = forcefield_get_response_data($url, $args);
                    if ($response) {
                        if ($debug) {echo "<!-- Response: ".print_r($response,true)." -->".PHP_EOL;}
                        $check = forcefield_vulnerability_check($response, $details['Version']);
                        if ($check) {
                            $newitems = forcefield_vulnerability_is_new($check, $existing);
                            if ($newitems) {$new['plugins'] = array_merge($new['plugins'], $newitems);}
                            $report['plugins'][$name] = $check;
                        } elseif (isset($report['plugins'][$name])) {unset($report['plugins'][$name]);}
                    } elseif (isset($forcefield['api_overload']) && $forcefield['api_overload']) {
                        // --- store current plugin for resuming ---
                        // 0.9.9: added for when API overloaded
                        update_option('forcefield_check_plugin', $name);
                    }
                }
            }
        }
        if (!$abortchecks) {$lastcheck['plugins'] = time();}
    }
    
    // Check Themes
    // ------------
    if ( ($type == 'themes') || ($type == 'all') ) {

        // --- get existing vulnerabilities ---        
        if (isset($report['themes'])) {$existing = $report['themes'];} else {$existing = array();}

        // --- get all themes ---
        $themes = wp_get_themes(); 

        // --- loop themes ---
        $resumeat = get_option('forcefield_check_theme');
        foreach ($themes as $name => $details) {

            // --- maybe check if we have a current position ---
            // 0.9.9: added check resuming
            $docheck = true;
            if ($resumeat) {
                if ($name != $resumeat) {$docheck = false;}
                else {delete_option('forcefield_check_theme'); $resumeat = false;}
            }

            // --- check if API overloaded ---
            // 0.9.9: added this check
            if (isset($forcefield['api_overload']) && $forcefield['api_overload']) {
                $abortchecks = true;
            } elseif ($docheck) {

                // --- check for name match (if specified) ---
                if (!$checkname || ($checkname && ($checkname == $name)) ) {

                    // --- set slug and URL ---            
                    $url = $apiurl.'/themes/'.$name;
                    if ($debug) {echo "<!-- Theme Check URL: ".$url." -->".PHP_EOL;}

                    // --- get API response data ---
                    $response = forcefield_get_response_data($url, $args);
                    if ($response) {
                        if ($debug) {echo "<!-- Response: ".print_r($response,true)." -->".PHP_EOL;}
                        $check = forcefield_vulnerability_check($response, $details['Version']);
                        if ($check) {
                            $newitems = forcefield_vulnerability_is_new($check, $existing);
                            if ($newitems) {$new['themes'] = array_merge($new['themes'], $newitems);}
                            $report['themes'][$name] = $check;
                        } elseif (isset($report['themes'][$name])) {unset($report['themes'][$name]);}
                    } elseif (isset($forcefield['api_overload']) && $forcefield['api_overload']) {
                        // --- store current theme for resuming ---
                        // 0.9.9: added for when API overloaded
                        update_option('forcefield_check_theme', $name);
                    }
                }
            }
        }
        if (!$abortchecks) {$lastcheck['themes'] = time();}
    }

    // --- maybe update last checked times ---
    if (isset($lastcheck['core']) || isset($lastcheck['plugins']) || isset($lastcheck['themes'])) {
        $checktimes = get_option('forcefield_vulnerability_checks');
        if (!$checktimes) {$checktimes = array();}
        $types = array('core', 'plugins', 'themes');
        foreach ($types as $type) {
            if (isset($lastcheck[$type])) {$checktimes[$type] = $lastcheck[$type];}
        }
        update_option('forcefield_vulnerability_checks', $lastcheck);
    }

    // --- save current report data ---
    if (count($report) > 0) {update_option('forcefield_vulnerability_report', $report);}

    // --- send email alert if new vulnerabilities found ---
    if (count($new) > 0) {forcefield_vulnerability_alert($new);}

}

// -------------------------------
// Check Vulnerability for Version
// -------------------------------
function forcefield_vulnerability_check($data, $version) {
    $debug = false; // $debug = true;
    $list = array(); $key = key($data);
    foreach ($data[$key]['vulnerabilities'] as $item) {
        if ($debug) {echo "<!-- ".print_r($item,true)." -->";;}
        if (version_compare($version, $item['fixed_in'], '<')) {
            if ($debug) {echo "<-- *** VULNERABILITY FOUND *** -->";}
            $list[] = $item;
        }
    }
    if (count($list) > 0) {return $list;} else {return false;}
}

// --------------------------
// Check Vulnerability is New
// --------------------------
function forcefield_vulnerability_is_new($items, $existing) {
    $debug = false; // $debug = true;
    if ($debug) {echo "<!-- Existing: ".print_r($existing,true)." -->";}
    $newitems = array();
    if (count($existing) > 0) {
        // --- loop all items ---
        foreach ($items as $item) {
            $found = false;
            // --- loop existing items ---
            foreach ($existing as $existingitem) {
                if ($item['id'] == $existingitem['id']) {
                    if ($debug) {echo "<!-- *** FOUND NEW *** -->";}
                    $found = true;
                }
            }
            // --- add to new if not found ---
            if (!$found) {$newitems[] = $item;}
        }
    } else {$newitems = $items;}

    if (count($newitems) > 0) {return $newitems;} else {return false;}
}

// ---------------------
// Get API Response Data
// ---------------------
// 0.9.9: set default method to wp_remote_get (with Curl fallback)
function forcefield_get_response_data($url, $args, $method='get') {

    global $forcefield;

    // --- revert back to API v2 if token failed ---
    if (isset($forcefield['invalid_token']) && $forcefield['invalid_token']) {
        if (isset($args['headers'])) {unset($args['headers']);}
        $url = str_replace('/v3/', '/v2/', $url);
    }

    // --- allow for response method overrides ---
    if (defined('FORCEFIELD_API_METHOD')) {$method = FORCEFIELD_API_METHOD;}
    elseif (isset($forcefield['api_method'])) {$method = $forcefield['api_method'];}
    if (!in_array($method, array('get', 'curl'))) {$method = 'get';}

    // --- bug out if external requests are blocked ---
    if (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL) {
        $allowed = false; 
        if (defined('WP_ACCESSIBLE_HOSTS')) {
            // --- or allow if host exception is defined ---
            $hosts = explode(',', WP_ACCESSIBLE_HOSTS);
            foreach ($hosts as $host) {
                if (strtolower($host) == 'wpvulndb.com') {$allowed = true;}
            }
        }
        if (!$allowed) {return false;}
    }

    // --- dev debug switch ---
    $debug = false; // $debug = true;

    if ($method == 'get') {

        // --- get URL with wp_remote_get ---
        $response = wp_remote_get($url, $args);
        // if ($debug) {echo "<!-- Raw: ".print_r($response,true)." -->".PHP_EOL;}
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            if (stristr($body, 'Retry later')) {
                $forcefield['api_overload'] = true; return false;
            }
            $code = wp_remote_retrieve_response_code($response);
            if ($debug) {echo "<!-- Body: ".$body." -->".PHP_EOL;}
            $result = (array) json_decode($body, true);
        } else {
            // 0.9.9: fallback to using curl
            $forcefield['api_method'] = $method = 'curl';
        }
    }
        
    if ($method == 'curl') {

        // --- get URL with Curl ---
        // 0.9.9: added check that curl extension is loaded
        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $args['timeout']);
            curl_setopt($ch, CURLOPT_USERAGENT, $args['user-agent']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            if (isset($args['headers'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $args['headers']);
            }
            $data = curl_exec($ch);
            if ($debug) {
                if ($data === false) {echo "<!-- Curl Error: ".curl_error($ch)." -->";}
                else {echo "<!-- Body: ".print_r($data,true)." -->".PHP_EOL;}
            }
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (stristr($data, 'Retry later')) {
                $forcefield['api_overload'] = true; return false;
            }

            // --- decode response ---
            $result = (array) json_decode($data, true);

        }

    }

    // --- check result ---
    // 0.9.9: move out
    if (isset($result) && !empty($result) && is_array($result)) {
        if ($debug) {echo "<!-- Result: ".print_r($result,true)." -->".PHP_EOL;}
        // 0.9.9: added check for API token error
        if (isset($result['error']) && stristr($result['error'], 'HTTP Token: Access denied')) {
            $forcefield['invalid_token'] = true;
            return forcefield_get_response_data($url, $args, $method);
        } elseif (isset($result['error']) && stristr($result['error'], 'Not found')) {
            return false;
        } else {
            if ($debug) {echo "<!-- Code: ".$code." -->".PHP_EOL;}
            if ($code == 200) {return $result;}
        }
    }

    return false;
}

// ---------------------------
// Create Vulnerabilities List
// ---------------------------
function forcefield_vulnerabilities_list($type, $items=null) {

    $debug = false; // $debug = true;

    // --- get report ---
    $report = get_option('forcefield_vulnerability_report');
    if (!isset($report[$type])) {return false;}
    if ($debug) {echo "<!-- Report: ".print_r($report[$type],true)." -->";}

    // --- get dismissed notices ---
    // 0.9.9: added dismissed item checking
    $dismissed = get_option('forefield_vulnerability_dismissed');
    if (!$dismissed) {$dismissed = array();}

    // --- get all resources ---
    global $wp_version;
    if (!function_exists('get_plugins')) {
        require_once ABSPATH.'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    $themes = wp_get_themes(); 

    // --- loop report items for type ---
    $htmllist = $textlist = array(); $changed = false;
    $itemcount = count($report[$type]);
    foreach ($report[$type] as $itemname => $item) {

        // --- check if notice has been dismissed  ---
        // 0.9.9: added notice dismissal check
        if (!in_array($item['id'], $dismissed)) {

            // --- recheck vulnerabilities ---
            // (as updates may have occurred since found)
            $skip = $updateurl = false;
            if ($type == 'core') {
                $version = $wp_version;
                // 0.9.9: fix to version compare clearing updated items
                if (version_compare($version, $item['fixed_in'], '<')) {
                    unset($report[$type][$itemname]); $changed = $skip = true;
                } else {
                    // --- clear dismissed updates and force update check ---
                    delete_site_option('dismissed_update_core');
                    delete_site_transient('update_core');
                    $updates = get_core_updates();
                    if (!isset($updates[0]->response) || 'latest' == $updates[0]->response) {
                        // well that is strange, no core update is available yet
                    } else {$updateurl = admin_url('update-core.php');}
                }
            } elseif ($type == 'plugins') {
                foreach ($plugins as $name => $details) {
                    if ($name == $itemname) {
                        $displayname = $details['Name'];
                        $version = $details['Version'];
                        // 0.9.9: fix to version compare clearing updated items
                        if (version_compare($version, $item['fixed_in'], '<')) {
                            unset($report[$type][$itemname]); $changed = $skip = true;
                        } else {
                            // 0.9.9: removed mismatched hasPackage check
                            // TODO: add real check if plugin update available
                            $updateurl = add_query_arg('action', 'upgrade-plugin', admin_url('update.php'));
                            $updateurl = add_query_arg('plugin', urlencode($name), $updateurl);
                            $updateurl = wp_nonce_url($updateurl, 'upgrade-plugin_'.$name);    
                        }
                    }
                }
            } elseif ($type == 'themes') {
                foreach ($themes as $name => $details) {
                    if ($name == $itemname) {
                        $displayname = $details['Name'];
                        $version = $details['Version'];
                        // 0.9.9: fix to version compare clearing updated items
                        if (version_compare($version, $item['fixed_in'], '<')) {
                            unset($report[$type][$itemname]); $changed = $skip = true;
                        } else {
                            // 0.9.9: removed mismatched hasPackage check
                            // TODO: add real check if theme update available
                            $updateurl = add_query_arg('action', 'upgrade-theme', admin_url('update.php'));
                            $updateurl = add_query_arg('theme', urlencode($name), $updateurl);
                            $updateurl = wp_nonce_url($updateurl, 'upgrade-theme_'.$name);    
                        }
                    }
                }
            }
        } else {$skip = true;}

        // --- maybe skip if removed ---
        if (!$skip) {

            // --- check if items specified for matching ---
            $add = false;
            if (is_null($items)) {$add = true;}
            else {
                // --- match current item with passed items ---
                foreach ($items as $newitem) {
                    if ($item['id'] == $newitem['id']) {$add = true;}
                }
            }

            if ($add) {

                if ($debug) {echo "<!-- Item: ".print_r($item,true)." -->";}

                // --- set list item details ---
                // 0.9.9: allow for listing of multiple vulnerabilities 
                $links = $fixed = $icons = $sinces = '';
                $textttitles = $texturls = '';
                foreach ($item as $details) {

                    // --- linked vulnerability titles ---
                    $linkurl = "https://wpvulndb.com/vulnerabilities/".$details['id'];
                    $links .= "<a href='".$linkurl."' target='_blank'>";
                    $links .= esc_html($details['title'])."</a><br>";
                    $textdetails .= __('Vulnerability','forcefield').": ".$details['title'].":\n";
                    $textdetails .= $linkurl."\n";

                    // --- check if fixed yet ---
                    if (empty($details['fixed_in'])) {
                        $notfixedtext = esc_attr(__('Not Fixed Yet!', 'forcefield'));
                        $fixed .= '<span style="color:#d50000;">'.$notfixedtext.'</span><br>';
                        $icons .= '<span class="dashicons dashicons-warning" style="color:#d50000;"></span><br>';
                        $textdetails .= "* ".$notfixedtext."\n";
                    } else {
                        $fixedtext = esc_attr(sprintf(__('Fixed in Version %s', 'forcefield'), $details['fixed_in']));
                        $fixed .= '<span style="color:#cc7700;">'.$fixedtext.'</span><br>';
                        $icons .= '<span class="dashicons dashicons-warning" style="color:#cc7700;"></span><br>';
                        $textdetails .= "* ".$fixedtext."\n";
                    }
                }

                // --- set HTML list ---
                $html = '<tr id="ff-vuln-id-'.esc_attr($item['id']).'">';
                    $html .= '<td>'.$icons.'</td>';
                    $html .= '<td>'.esc_html($displayname)."</td>";
                    $html .= '<td>'.$links.'</td>';
                    $html .= '<td>'.$fixed.'</td>';
                    $html .= '<td><span style="color:#d50000;">'.esc_attr(__('Installed Version','forcefield')).": ".esc_attr($version)."</span></td>";
                    if ($updateurl) {
                        $html .= "<td><a href='".$updateurl."'>";
                            // 0.9.9: converted link to button
                            $html .= "<input type='button' class='button-secondary' value='".esc_attr(__('Update Now','forcefield'))."'>";
                        $html .= "</a></td>";
                    } else {$html .= "<td></td>";}
                    if ( ($type == 'plugins') || ($type == 'themes') ) {
                        // 0.9.9: only add checkbox if more than one vulnerability
                        if ($itemcount > 1) {
                            $html .= "<td><input type='checkbox' name='checked[]' value='".esc_attr($name)."' checked='checked'></td>";
                        } else {$html .= "<td></td>";}
                    } else {$html .= "<td></td>";}
                    // 0.9.9: added notice dismissal button
                    $html .= '<td style="vertical-align:bottom;">';
                        $dismissurl = admin_url('admin-ajax.php').'?action=forcefield_vulnerability_dismiss&type='.$type.'&id='.esc_attr($item['id']);
                        $html .= "<a href='".$dismissurl."' target='forcefield-dismiss-frame' style='text-decoration:none;' title='".esc_attr(__('Dismiss this Notice','forcefield'))."'>";
                        $html .= "<div class='dashicons dashicons-dismiss' style='font-size:16px;'></div></a>";
                    $html .= '</td>';

                $html .= '</tr>';
                $htmllist[] = $html;

                // --- set text item list ---
                $text = $displayname." - ".__('Installed Version','forcefield').": ".$version."\n";
                $text .= $textdetails;
                if ($updateurl) {$text .= __('Update URL','forcefield').":\n".$updateurl."\n";}
                $textlist[] = $text;
            }
        }
    }

    // --- maybe update vulnerability report ---
    if ($changed) {update_option('forcefield_vulnerability_report', $report);}

    // --- output vulnerability list ---
    // 0.9.9: do not wrap HTML with table tag here
    // 0.9.9: fix to check of incorrect variable name (list)
    if (count($htmllist) > 0) {
        $data = array(
            'text'  => implode("\n", $textlist),
            'html'  => implode(PHP_EOL, $htmllist),
            'count' => count($textlist)
        );
        return $data;
    } else {return false;}

}

// ----------------------------
// Admin Vulnerabilitues Notice
// ----------------------------
add_action('admin_notices', 'forcefield_vulnerability_notice');
function forcefield_vulnerability_notice() {

    $debug = false; // $debug = true;
    $output = '';

    // --- Core Vulnerability Notices ---
    if (current_user_can('manage_options') || current_user_can('update_core')) {
        $corelist = forcefield_vulnerabilities_list('core');
        if ($corelist && ($corelist['count'] > 0)) {

            // --- core vulnerabilities div ---
            $output .= "<div id='ff-core-vulnerabilities'>";

            // --- core vulnerabilities table header ---
            $output .= "<table cellspacing='10'>";
            $output .= "<tr>";
                $output .= "<td colspan='8' style='font-size:18px;'>";
                $output .= "<b><span style='color:#d50000;'>".esc_attr(__('Warning!','forcefield'))."</span> ";
                    $output .= esc_attr(__('Core Vulnerabilities','forcefield'))."</b>";
                $output .= "</td>";
            $output .= "</tr>";

            // --- core vulnerabilities rows ---
            $output .= $corelist['html'];

            // --- close table and div ---
            $output .= "</table></div>";
        }
    }
    
    // --- Plugin Vulnerability Notices ---
    if (current_user_can('update_plugins')) {
        $pluginlist = forcefield_vulnerabilities_list('plugins');
        if ($debug) {echo "<!-- Plugin Vulnerability List: ".print_r($pluginlist,true)." -->";}
        if ($pluginlist && ($pluginlist['count'] > 0)) {

            // --- plugin vulnerabilities div ---
            $output .= "<div id='ff-plugin-vulnerabilities'>";

            // --- update selected themes form ---
            $nonce = wp_create_nonce('bulk-update-plugins');
            $output .= "<form id='ff-plugin-form' action='".esc_url(admin_url('update.php'))."'>";
            $output .= "<input type='hidden' name='_wpnonce' value='".$nonce."'>";
            $output .= "<input type='hidden' id='ff-plugin-action' name='action' value='update-selected'>";
            
            // --- theme list table header row ---
            $output .= "<table cellspacing='10'>";
            $output .= "<tr>";
                $output .= "<td colspan='5' style='font-size:18px;'>";
                    $output .= "<b><span style='color:#d50000;'>".esc_attr(__('Warning!','forcefield'))."</span> ";
                    $output .= "ForceField ".esc_attr(__('has detected','forcefield'))." ";
                    if ($pluginlist['count'] > 1) {
                        $output .= esc_attr(__('Plugin Vulnerabilities','forcefield'))."</b>";
                        $output .= esc_attr(sprintf(__('in %d Plugins','forcefield'), $pluginlist['count']))."!</b>";
                    } else {$output .= esc_attr(__('a Plugin Vulnerability','forcefield'))."</b>";}
                $output .= "</td><td colspan='3'>";
                // 0.9.9: only show button if more than one vulnerability
                if ($pluginlist['count'] > 1) {
                    $output .= "<input class='button-primary' type='submit' value='".esc_attr(__('Update Selected Plugins','forcefield'))."'>";
                }
                $output .= "</td>";
            $output .= "</tr>";

            // --- theme list table rows ---
            $output .= $pluginlist['html'];

            // --- close form table and div ---
            $output .= "</table></form></div>";
            
        }
    }

    // --- Theme Vulnerability Notices ---
    if (current_user_can('update_themes')) {
        $themelist = forcefield_vulnerabilities_list('themes');
        if ($themelist && ($themelist['count'] > 0)) {

            // --- plugin vulnerabilities div ---
            $output .= "<div id='ff-theme-vulnerabilities'>";

            // --- update selected themes form ---
            $nonce = wp_create_nonce('bulk-update-themes');
            $output .= "<form id='ff-theme-form' action='".esc_url(admin_url('update.php'))."'>";
            $output .= "<input type='hidden' name='_wpnonce' value='".$nonce."'>";
            $output .= "<input type='hidden' id='ff-theme-action' name='action' value='update-selected-themes'>";
            
            // --- theme list table header row ---
            $output .= "<table cellspacing='10'>";
            $output .= "<tr id='vulnerability-".esc_attr($item['id'])."'>";
                $output .= "<td colspan='5' style='font-size:18px;'>";
                    $output .= "<b><span style='color:#d50000;'>".esc_attr(__('Warning!','forcefield'))."</span> ";
                    $output .= "ForceField ".esc_attr(__('has detected','forcefield'))." ";
                    if (count($themelist['count']) > 1) {
                        $output .= esc_attr(__('Theme Vulnerabilities','forcefield'));
                        $output .= esc_attr(sprintf(__('in %d Themes','forcefield'), $themelist['count']))."!</b>";
                    } else {$output .= esc_attr(__('a Theme Vulnerability','forcefield'))."</b>";}
                $output .= "</td><td colspan='3'>";
                // 0.9.9: only show button if more than one vulnerability
                if ($themelist['count'] > 1) {
                    $output .= "<input class='button-primary' type='submit' value='".esc_attr(__('Update Selected Themes','forcefield'))."'>";
                }
                $output .= "</td>";
            $output .= "</tr>";

            // --- theme list table rows ---
            $output .= $themelist['html'];

            // --- close form table and div ---
            $output .= "</table></form></div>";
        }
    }

    // --- output vulnerability alert notice ---
    // 0.9.9: added missing vulnerability notices output 
    if ($output != '') {

        // --- output notices ---
        // 0.9.9: change to error notice class to display red 
        echo "<div class='error notice' id='forcefield-vulnerability-notice' style='font-size:14px; line-height:22px; margin:0;'>";
            echo $output;
        echo "</div>";

        // --- notice dismissal iframe ---
        echo "<iframe style='display:none;' src='javascript:void(0);' name='forcefield-dismiss-frame' id='forcefield-dismiss-frame'></iframe>";
    }

}

// --------------------------------
// AJAX Dimiss Vulnerability Notice
// --------------------------------
// 0.9.9: added AJAX to dismiss vulnerability notices
add_action('wp_ajax_forcefield_vulnerability_dismiss', 'forcefield_vulnerability_dismiss');
function forcefield_vulnerability_dismiss() {

    // --- check and sanitize vulnerability ID ---
    if (!isset($_REQUEST['id'])) {exit;}
    $id = absint($_REQUEST['id']);
    if ($id < 1) {exit;}

    // --- get item type for matching ---
    if (!isset($_REQUEST['type'])) {exit;}
    $type = $_REQUEST['type'];
    if (!in_array($type, array('core', 'plugins', 'themes'))) {exit;}
    if ($type == 'core') {
        if (!current_user_can('manage_options') && !current_user_can('update_core')) {exit;}
    } elseif (($type == 'plugins') && !current_user_can('update_plugins')) {exit;}
    elseif (($type == 'themes') && !current_user_can('update_themes')) {exit;}

    // --- check if already dismissed ---
    $dismissed = get_option('forcefield_vulnerability_dismissed');
    if ($dismissed && is_array($dismissed) && in_array($id, $dismissed)) {exit;}
    
    // --- check vulnerability item ID ---
    $report = get_option('forcefield_vulnerability_report');
    if (!$report || !isset($reports[$type]) || !is_array($report[$type]) || (count($report[$type]) < 1)) {exit;}
    $found = false;
    foreach ($report[$type] as $itemname => $item) {
        if ($id == $item['id']) {$found = true;}
    }
    if (!$found) {exit;}

    // --- save to dismissed vulnerabilities list ---
    if (!$dismissed) {$dismissed = array();}
    $dismissed[] = $id;
    update_option('forcefield_vulnerability_dismissed');

    // --- remove vulnerability row ---
    // TODO: check and remove table header if only row ?
    echo "<script>parent.document.getElementById('ff-vuln-id-".$id."').style.display = 'none';</script>";
    
    exit;
}

// -------------------------------
// Send Vulnerability Alert Emails
// -------------------------------
function forcefield_vulnerability_alert($new, $debug=false) {

    // --- group alert types by email addresses ---
    $addresses = array();
    $types = array('core', 'plugin', 'theme');
    foreach ($types as $type) {
        $email = forcefield_get_setting('vuln_'.$type.'_emails');
        // 0.9.9: fix to non-plural settings key mismatch
        if (in_array($type, array('plugin', 'theme'))) {$type .= 's';}
        if ($email && (trim($email) != '')) {
            if (strstr($email, ',')) {
                $emails = explode(',', $email);
                foreach ($emails as $address) {$addresses[$address][] = $type;}
            } else {
                $email = trim($email);
                $addresses[$email][] = $type;
            }
        }
    }
    if ($debug) {echo "Addresses: ".print_r($addresses,true)."<br>";}

    // --- bug out if no one to email ---
    if (count($addresses) == 0) {return;}

    // --- get vulnerability lists ---
    $corelist = forcefield_vulnerabilities_list('core', $new['core']);
    $pluginlist = forcefield_vulnerabilities_list('plugins', $new['plugins']);
    $themelist = forcefield_vulnerabilities_list('themes', $new['themes']);
    if ($debug) {
        echo "Core Check: ".print_r($corelist,true)."<br>";
        echo "Plugin Check: ".print_r($pluginlist,true)."<br>";
        echo "Theme Check: ".print_r($themelist,true)."<br>";
    }

    // --- loop email addresses and send relevent alerts ---
    foreach ($addresses as $address => $types) {

        // --- get total vulnerability count ---
        $totalcount = 0;
        if ($corelist && ($corelist['count'] > 0) && in_array('core', $types)) {
            $totalcount = $corelist['count'];
        }
        if ($pluginlist && ($pluginlist['count'] > 0) && in_array('plugins', $types)) {
            $totalcount = $totalcount + $pluginlist['count'];
        }
        if ($themelist && ($themelist['count'] > 0) && in_array('themes', $types)) {
            $totalcount = $totalcount + $themelist['count'];
        }
        if ($totalcount == 0) {break;}

        // --- set email subject ---
        $subject = "[ForceField Alert] ".get_option('blogname');
        if ($totalcount > 1) {$subject = " - ".$totalcount." ".__('New Vulnerabilities Detected!','forcefield');}
        else {$subject .= " - ".__('New Vulnerability Detected!','forcefield');}

        // --- set email message ---
        $message = __('ForceField has detected vulnerabilities in your installation.','forcefield')."\n";
        $message .= __('(via querying the WPVulnDB API with your relevant version data.)','forcefield')."\n";
        $message .= __('For improved security, please update your installations.','forcefield')."\n\n";

        if ($corelist && ($corelist['count'] > 0) && in_array('core', $types)) {
            $message .= "\n=== ".__('Core Vulnerabilities','forcefield')." (".$corelist['count'].") ===\n";
            $message .= $corelist['text'];
        }
        if ($pluginlist && ($pluginlist['count'] > 0) && in_array('plugins', $types)) {
            $message .= "\n=== ".__('Plugin Vulnerabilities','forcefield')." (".$pluginlist['count'].") ===\n";
            // 0.9.9: fix to plural variable typo
            $message .= $pluginlist['text'];
        }
        if ($themelist && ($themelist['count'] > 0) && in_array('themes', $types)) {
            $message .= "\n=== ".__('Theme Vulnerabilities','forcefield')." (".$themelist['count'].") ===\n";
            $message .= $themelist['text'];
        }

        $message .= "\n".__('A full list of known vulnerabilities can be seen in your admin notices area.','forcefield');
        $message .= "\n\n".'ForceField';

        if ($debug) {
            echo "Sending alert to: ".$address."<br>".PHP_EOL;
            echo "Subject: ".$subject."<br>".PHP_EOL;
            echo "Message: ".str_replace("\n", "<br>", $message)."<br>".PHP_EOL;
        }

        // --- send the email ---
        $sent = wp_mail($address, $subject, $message);
        if ($debug) {echo "Send result: ".$sent;}

    }
}

// ------------------------------
// Vulnerability Alert Email Test
// ------------------------------
// 0.9.9: added to test resending of all alert emails
add_action('init', 'forcefield_vulnerability_alert_test');
function forcefield_vulnerability_alert_test() {
    if (!current_user_can('manage_options')) {return;}
    if (!isset($_GET['ff-alert-test']) || ($_GET['ff-alert-test'] != 'yes')) {return;}

    // --- send array of null values for all items instead of new ---
    $items = array('core' => null, 'plugins' => null, 'themes' => null);
    forcefield_vulnerability_alert($items, true); exit;
}
