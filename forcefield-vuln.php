<?php

// ========================================
// === ForceField Vulnerability Checker ===
// ========================================

if (!function_exists('add_action')) {exit;}

// - Schedule Vulnerability Check
// - Check Vulnerability Callbacks
// - Store Pre-Installation Data
// - Vulnerability Check New Installs
// - Perform Vulnerabilities Check
// - Check Vulnerability for Version
// - Check Vulnerability is New
// - Get API Response Data
// - Create Vulnerabilities List
// - Admin Vulnerabilities Notice
// - Send Vulnerability Alert Emails

// Development TODOs
// -----------------
// - test plugin / theme vulnerability notice multi-select updates
// ? trigger vulnerability rechecks on plugin or theme rollback 


// ----------------------------
// Schedule Vulnerability Check
// ----------------------------
add_action('plugins_loaded', 'forcefield_vulnerabilities_check_schedule');
function forcefield_vulnerabilities_check_schedule() {

    $checktimes = get_option('forcefield_vulnerability_checks');
    if (!$checktimes) {add_action('init', 'forcefield_vulnerabilities_check_all');}
    else {
        if (!isset($checkedtimes['core'])) {$checkedtimes['core'] = '';}
        if (!isset($checkedtimes['plugins'])) {$checkedtimes['plugins'] = '';}
        if (!isset($checkedtimes['themes'])) {$checkedtimes['themes'] = '';}

        $intervals = forcefield_get_intervals();

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

                // --- schedule the check on shutdown ---
                if ($docheck) {
                    add_action('shutdown', 'forcefield_force_output_flush', 998);
                    add_action('shutdown', 'forcefield_vulnerabilities_check_'.$type, 999);
                }
            }
        }
    }
}

// ------------------
// Force Output Flush
// ------------------
// (send data so users are not waiting on API checks)
// ref: https://gist.github.com/bubba-h57/32593b2b970366d24be7
// TODO: maybe check WP Cron usage for this ?
function forcefield_force_output_flush() {
    ignore_user_abort(true); flush();
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

    // --- only allow plugin or theme name match ---
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
        foreach ($plugins as $name => $details) {

            // --- check for name match (if specified) ---
            if (!$checkname || ($checkname && ($checkname == $name)) ) {

                // --- get existing vulnerabilities ---
                if (isset($report['plugins'])) {$existing = $report['plugins'];} else {$existing = array();}

                // --- set plugin slug and URL ---
                $slug = sanitize_title($details['Name']);
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
                }
            }
        }
        $lastcheck['plugins'] = time();
    }
    
    // Check Themes
    // ------------
    if ( ($type == 'themes') || ($type == 'all') ) {

        // --- get existing vulnerabilities ---        
        if (isset($report['themes'])) {$existing = $report['themes'];} else {$existing = array();}

        // --- get all themes ---
        $themes = wp_get_themes(); 

        // --- loop themes ---
        foreach ($themes as $name => $details) {

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
                }
            }
        }
        $lastcheck['themes'] = time();
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
    $list = array(); $key = key($data);
    foreach ($data[$key]['vulnerabilities'] as $item) {
        if (version_compare($version, $item['fixed_in'], '<')) {$list[] = $item;}
    }
    if (count($list) > 0) {return $list;} else {return false;}
}

// --------------------------
// Check Vulnerability is New
// --------------------------
function forcefield_vulnerability_is_new($items, $existing) {
    $newitems = array();
    if (count($existing) > 0) {
        // --- loop all items ---
        foreach ($items as $item) {
            $found = false;
            // --- loop existing items ---
            foreach ($existing as $existingitem) {
                if ($item['id'] == $existingitem['id']) {$found = true;}
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
function forcefield_get_response_data($url, $args, $method='curl') {

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

    if ($method == 'curl') {

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
            else {echo "<!-- Raw: ".print_r($data,true)." -->".PHP_EOL;}
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($debug) {echo "<!-- Code: ".$code." -->".PHP_EOL;}
        curl_close($ch);

        if ($code == 200) {
            $result = (array) json_decode($data, true);
            if (!empty($result) && !isset($result['error'])) {return $result;}
        }

    } elseif ($method == 'get') {

        $response = wp_remote_get($url, $args);
        if ($debug) {echo "<!-- Raw: ".print_r($response,true)." -->".PHP_EOL;}
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($debug) {echo "<!-- Code: ".$code." -->".PHP_EOL;}
            if ($code == 200) {
                $body = wp_remote_retrieve_body($response);
                if ($debug) {echo "<!-- Body: ".$body." -->".PHP_EOL;}
                $result = (array) json_decode($body, true);
                if (!empty($result) && !isset($result['error'])) {return $result;}
            }
        }

    }

    return false;
}

// ---------------------------
// Create Vulnerabilities List
// ---------------------------
function forcefield_vulnerabilities_list($type, $items=null) {

    // --- get report ---
    $report = get_option('forcefield_vulnerability_report');
    if (!isset($report[$type])) {return false;}

    // --- get all resources ---
    global $wp_version;
    if (!function_exists('get_plugins')) {
        require_once ABSPATH.'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    $themes = wp_get_themes(); 

    // --- loop report items for type ---
    $htmllist = $textlist = array(); $changed = false;
    foreach ($report[$type] as $itemname => $item) {

        // --- recheck vulnerabilities ---
        // (as updates may have occurred since found)
        $skip = $updateurl = false;
        if ($type == 'core') {
            $version = $wp_version;
            if (!version_compare($version, $item['fixed_in'], '<')) {
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
                    if (!version_compare($version, $item['fixed_in'], '<')) {
                        unset($report[$type][$itemname]); $changed = $skip = true;
                    } elseif (isset($details['hasPackage']) && $details['hasPackage']) {
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
                    if (!version_compare($version, $item['fixed_in'], '<')) {
                        unset($report[$type][$itemname]); $changed = $skip = true;
                    } elseif (isset($details['hasPackage']) && $details['hasPackage']) {
                        $updateurl = add_query_arg('action', 'upgrade-theme', admin_url('update.php'));
                        $updateurl = add_query_arg('theme', urlencode($name), $updateurl);
                        $updateurl = wp_nonce_url($updateurl, 'upgrade-theme_'.$name);    
                    }
                }
            }
        }

        // --- maybe skip if removed ---
        if (!$skip) {

            // --- check if existing items specified ---
            $add = false;
            if (is_null($items)) {$add = true;}
            else {
                // --- match current item with passed items ---
                foreach ($items as $newitem) {
                    if ($item['id'] == $newitem['id']) {$add = true;}
                }
            }

            if ($add) {

                // --- set list item details ---
                $detailsurl = 'https://wpvulndb.com/vulnerabilities/'.$item['id'];
                if (empty($item['fixed_in'])) {
                    $fixed = __('Not Fixed', 'forcefield');
                    $icon = '<span class="dashicons dashicons-warning" style="color:#d50000;"></span>';
                } else {
                    $fixed = sprintf(__('Fixed in version %s', 'forcefield'), $item['fixed_in']);
                    $icon = '<span class="dashicons dashicons-warning" style="color:#ffdd33;"></span>';
                    // $icon = '<span class="dashicons dashicons-yes" style="color:green; display:none;"></span>';
                }

                // --- set HTML list ---
                $html = '<tr>';
                    $html .= '<td>'.$icon.'</td>';
                    $html .= '<td>'.$displayname." v".$version."</td>";
                    $html .= '<td><a href="'.esc_url($detailsurl).'" target="_blank">';
                    $html .= esc_html($item['title'])."</a></td>";
                    $html .= "<td>".esc_attr($fixed)."</td>";
                    if ($updateurl) {$html .= "<td><a href='".esc_url($updateurl)."'>".esc_attr(__('Update Now','forcefield'))."</a></td>";}
                    else {$html .= "<td></td>";}
                    if ( ($type == 'plugins') || ($type == 'themes') ) {
                        $html .= "<td><input type='checkbox' name='checked[]' value='".$name."' checked='checked'></td>";
                    }
                $html .= '</tr>';
                $htmllist[] = $html;

                // --- set text list ---
                $text = $displayname." v".$version."\n";
                $text .= $item['title']." (".$fixed.")\n";
                $text .= "Details: ".$detailsurl."\n";
                if ($updateurl) {$text .= "Update URL: ".$updateurl."\n";}
                $textlist[] = $text;
            }
        }
    }

    // --- maybe update vulnerability report ---
    if ($changed) {update_option('forcefield_vulnerability_report', $report);}

    // --- output vulnerability list ---
    if (!empty($list)) {
        $data = array(
            'text'  => implode("\n", $textlist),
            'html'  => "<table>".implode('', $htmllist)."</table>",
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

    // --- Core Vulnerability Notices ---
    if (current_user_can('manage_options') || current_user_can('update_core')) {
        $corelist = forcefield_vulnerabilities_list('core');
        if ($corelist && ($corelist['count'] > 0)) {
            $output .= "<b>".__('Core Vulnerabilities','forcefield')."</b>";
            $output .= "<br>".$corelist['html'];
        }
    }
    
    // --- Plugin Vulnerability Notices ---
    if (current_user_can('update_plugins')) {
        $pluginlist = forcefield_vulnerabilities_list('plugins');
        if ($pluginlist && ($pluginlist['count'] > 0)) {

            $output .= "<b>".__('Plugin Vulnerabilities','forcefield')."</b><br>";

            // --- update selected plugins form ---
            $output .= "<form action='".admin_url('update.php')."'>";
            $nonce = wp_create_nonce('bulk-update-plugins');
            $output .= "<input type='hidden' name='_wpnonce' value='".$nonce."'>";
            $output .= "<input type='hidden' name='action' value='update-selected'>";
            $output .= "<input type='submit' value='".__('Update Selected Plugins','forcefield')."'>";
            
            // --- plugin list table ---
            $output .= "<br>".$pluginlist['html'];

            // --- close form ---
            $output .= "</form>";
        }
    }

    // --- Theme Vulnerability Notices ---
    if (current_user_can('update_themes')) {
        $themelist = forcefield_vulnerabilities_list('themes');
        if ($themelist && ($themelist['count'] > 0)) {

            $output .= "<b>".__('Theme Vulnerabilities','forcefield')."</b>";

            // --- update selected themes form ---
            $nonce = wp_create_nonce('bulk-update-themes');
            $output .= "<form action='".admin_url('update.php')."'>";
            $output .= "<input type='hidden' name='_wpnonce' value='".$nonce."'>";
            $output .= "<input type='hidden' name='action' value='update-selected-themes'>";
            $output .= "<input type='submit' value='".__('Update Selected Themes','forcefield')."'>";

            // --- theme list table ---
            $output .= "<br>".$themelist['html'];

            // --- close form ---
            $output .= "</form>";
        }
    }

}

// -------------------------------
// Send Vulnerability Alert Emails
// -------------------------------
function forcefield_vulnerability_alert($new) {

    // --- group alert types by email addresses ---
    $addresses = array();
    $types = array('core', 'plugin', 'theme');
    foreach ($types as $type) {
        $email = forcefield_get_setting('vuln_'.$type.'_emails');
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

    // --- bug out if no one to email ---
    if (count($addresses) == 0) {return;}

    // --- get vulnerability lists ---
    $corelist = forcefield_vulnerabilities_list('core', $new['core']);
    $pluginlist = forcefield_vulnerabilities_list('plugins', $new['plugins']);
    $themelist = forcefield_vulnerabilities_list('themes', $new['themes']);

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
            $message .= "\n=== ".__('New Core Vulnerabilities','forcefield')." (".$corelist['count'].") ===\n";
            $message .= $corelist['text'];
        }
        if ($pluginlist && ($pluginlist['count'] > 0) && in_array('plugins', $types)) {
            $message .= "\n=== ".__('New Plugin Vulnerabilities','forcefield')." (".$pluginlist['count'].") ===\n";
            $message .= $pluginslist['text'];
        }
        if ($themelist && ($themelist['count'] > 0) && in_array('themes', $types)) {
            $message .= "\n=== ".__('New Theme Vulnerabilities','forcefield')." (".$themelist['count'].") ===\n";
            $message .= $themelist['text'];
        }

        $message .= "\n".__('A full list of known vulnerabilities can be seen in your admin notices area.','forcefield');
        $message .= "\n\n".'ForceField';

        // --- send the email ---
        $sent = wp_mail($address, $subject, $message);

    }
}
