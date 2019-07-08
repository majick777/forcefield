<?php

// =====================================
// === WordQuest Plugin Loader Class ===
// =====================================
//
// --------------
// Version: 1.0.6
// --------------
// * changelog at end of file! *


// Loader Usage:
// =============
// 1. replace all occurrences of PREFIX_ in this file with the plugin namespace eg. myplugin_
// 2. define plugin options, default settings, and setup arguments your main plugin file
// 3. require this file in the main plugin file and instantiate the loader class (see example below)


// ---------------------------
// Plugin Options and Defaults
// ---------------------------
// array of plugin option keys, with input types and defaults
// $options = array(
// 	'optionkey1'	=>	array(
//							'type' 		=> 'checkbox',
//							'default'	=> '1',
//						),
//	'optionkey2'	=>	array(
//							'type' 		=> 'radio',
//							'default'	=> 'on',
//							'options'	=> 'on/off',
//						),
//	'optionkey3'	=> array(
//							'type'		=> 'special',
//						),
// );

// ---------------
// Plugin Settings
// ---------------
// $slug = 'plugin-name';				// plugin slug (usually same as filename)
// $args = array(
//	// --- Plugin Info ---
//	'slug'			=> $slug,			// (uses slug above)
//	'file'			=> __FILE__,		// path to main plugin file (important!)
//	'version'		=> '0.0.1', 		// * rechecked later (from plugin header) *
//
//	// --- Menus and Links ---
//	'title'			=> 'Plugin Name',	// plugin title
//	'parentmenu'	=> 'wordquest',		// parent menu slug
//	'home'			=> 'http://mysite.com/plugins/plugin/',
//	'support'		=> 'http://mysite.com/plugins/plugin/support/',
//	'ratetext'		=> __('Rate on WordPress.org'),		// (overrides default rate text)
//	'share'			=> 'http://mysites.com/plugins/plugin/#share', // (set sharing URL)
//	'sharetext'		=> __('Share the Plugin Love'),		// (overrides default sharing text)
//	'donate'		=> 'https://patreon.com/pagename',	// (overrides plugin Donate URI)
//	'donatetext'	=> __('Support this Plugin'),		// (overrides default donate text)
//
//	// --- Options ---
//	'namespace'		=> 'plugin_name',	// plugin namespace (function prefix)
//	'settings'		=> 'pn',			// sidebar settings prefix
//	'option'		=> 'plugin_key',	// plugin option key
//	'options'		=> $options,		// plugin options array set above
//
//	// --- WordPress.Org ---
//	'wporgslug'		=> 'plugin-slug',	// WordPress.org plugin slug
//	'wporg'			=> false, 			// * rechecked later (via presence of updatechecker.php) *
//	'textdomain'	=> 'text-domain',	// translation text domain (usually same as plugin slug)
//
//	// --- Freemius ---
//	'freemius_id'	=> '',				// Freemius plugin ID
//	'freemius_key'	=> '',				// Freemius public key
//	'hasplans'		=> false,			// has paid plans?
//	'hasaddons'		=> false,			// has add ons?
//	'plan'			=> 'free',	 		// * rechecked later (if premium version found) *
// );

// ----------------------------
// Start Plugin Loader Instance
// ----------------------------
// require(dirname(__FILE__).'/loader.php');				// requires this file!
// $instance = new PREFIX_loader($args);				// instantiates loader class
// (eg. replace 'PREFIX_' with 'my_plugin_' etc.)


// ===========================
// --- Plugin Loader Class ---
// ===========================
// usage: change class prefix to the plugin function prefix
class forcefield_loader {

	public $args = null;
	public $namespace = null;
	public $options = null;
	public $defaults = null;
	public $data = null;

	// -----------------
	// Initialize Loader
	// -----------------
	function __construct($args) {

		// --- set plugin options ---
		// 1.0.6: added options filter
		$args['options'] = apply_filters($args['namespace'].'_options', $args['options']);
		$this->options = $args['options']; unset($args['options']);

		// --- set plugin args and namespace ---
		$this->args = $args; $this->namespace = $args['namespace'];

		// --- setup plugin values ---
		$this->setup_plugin();

		// --- maybe transfer settings ---
		$this->maybe_transfer_settings();

		// --- load settings ---
		$this->load_settings();

		// --- load actions ---
		$this->add_actions();

		// --- load helper libraries ---
		$this->load_helpers();

		// --- autoset class instance global for accessibility ---
		$GLOBALS[$args['namespace'].'_instance'] = $this;
	}

	// ------------
	// Setup Plugin
	// ------------
	function setup_plugin() {
		$args = $this->args; $namespace = $this->namespace;

		// --- Read Plugin Header ---
		if (!isset($args['dir'])) {$args['dir'] = dirname($args['file']);}
		$fh = fopen($args['file'], 'r'); $data = fread($fh, 1024);
		$this->data = str_replace("\r", "\n", $data); fclose($fh);

		// --- Version ---
		$args['version'] = $this->plugin_data('Version:');

		// --- Title ---
		if (!isset($args['title'])) {$args['title'] = $this->plugin_data('Plugin Name:');}

		// --- Plugin Home ---
		if (!isset($args['home'])) {$args['home'] = $this->plugin_data('Plugin URI:');}

		// --- Author ---
		if (!isset($args['author'])) {$args['author'] = $this->plugin_data('Author:');}

		// --- Author URL ---
		if (!isset($args['author_url'])) {$args['author_url'] = $this->plugin_data('Author URI:');}

		// --- Pro Functions ---
		if (!isset($args['proslug'])) {
			$proslug = $this->plugin_data('@fs_premium_only');
			// 1.0.1: if more than one file, extract pro slug based on the first filename
			if (!strstr($proslug, ',')) {$profiles = array($proslug); $proslug = trim($proslug);}
			else {$profiles = explode(',', $proslug); $proslug = trim($profiles[0]);}
			$args['proslug'] = substr($proslug, 0, -4);		// strips .php extension
			$args['profiles'] = $profiles;
		}

		// --- update the loader args ---
		$this->args = $args;
	}

	// ---------------
	// Get Plugin Data
	// ---------------
	function plugin_data($key) {
		$data = $this->data; $value = null;
		$pos = strpos($data, $key);
		if ($pos !== false) {
			$pos = $pos + strlen($key) + 1;
			$tmp = substr($data, $pos);
			$pos = strpos($tmp, "\n");
			$value = trim(substr($tmp, 0, $pos));
		}
		return $value;
	}

	// -----------------
	// Set Pro Namespace
	// -----------------
	function pro_namespace($pronamespace) {
		$this->args['pronamespace'] = $pronamespace;
	}

	// --------------------
	// Get Default Settings
	// --------------------
	function default_settings($dkey = false) {

		// --- return defaults if already set ---
		$defaults = $this->defaults;
		if (!is_null($defaults)) {
			if ($dkey && isset($defaults[$dkey])) {return $defaults[$dkey];}
			return $defaults;
		}

		// --- filter and store the plugin default settings ---
		$options = $this->options; $defaults = array();
		foreach ($options as $key => $values) {$defaults[$key] = $values['default'];}
		$namespace = $this->namespace;
		$defaults = apply_filters($namespace.'_default_settings', $defaults);
		$this->defaults = $defaults;
		if ($dkey && isset($defaults[$dkey])) {return $defaults[$dkey];}
		return $defaults;
	}

	// ------------
	// Add Settings
	// ------------
	function add_settings() {
		// --- add the default plugin settings ---
		$args = $this->args; $defaults = $this->default_settings();
		$added = add_option($args['option'], $defaults);

		// --- if added, make the defaults current settings ---
		if ($added) {
			$namespace = $this->namespace;
			foreach ($defaults as $key => $value) {$GLOBALS[$namespace][$key] = $value;}
		}

		// --- add sidebar settings ---
		if (isset($args['settings'])) {
			if (file_exists($args['dir'].'/updatechecker.php')) {$adsboxoff = '';} else {$adsboxoff = 'checked';}
			$sidebaroptions = array('adsboxoff' => $adsboxoff, 'donationboxoff' => '', 'reportboxoff' => '', 'installdate' => date('Y-m-d'));
			add_option($args['settings'].'_sidebar_options', $sidebaroptions);
		}
	}

	// -----------------------
	// Maybe Transfer Settings
	// -----------------------
	function maybe_transfer_settings() {
		$namespace = $this->namespace; $funcname = $namespace.'_transfer_settings';
		// --- check for either function prefixed or class extended method ---
		if (method_exists($this, 'transfer_settings')) {$this->transfer_settings();}
		elseif (function_exists($funcname)) {call_user_func($funcname);}
	}

	// ----------------
	// Get All Settings
	// ----------------
	function get_settings() {
		$namespace = $this->namespace;
		$settings = $GLOBALS[$namespace];
		$settings = apply_filters($namespace.'_settings', $settings);
		return $settings;
	}

	// ------------------
	// Get Plugin Setting
	// ------------------
	function get_setting($key, $filter=true) {
		$args = $this->args;
		$namespace = $this->namespace; $settings = $GLOBALS[$namespace];
		$settings = apply_filters($namespace.'_settings', $settings);

		// --- maybe strip settings prefix ---
		// 1.0.4: added for backwards compatibility
		if (substr($key, 0, strlen($args['settings'])) == $args['settings']) {
			$key = substr($key, strlen($args['settings']) + 1, strlen($key));
		}

		// --- get plugin setting ---
		if (isset($settings[$key])) {$value = $settings[$key];}
		else {
			$defaults = $this->default_settings();
			if (isset($defaults[$key])) {$value = $defaults[$key];}
			else {$value = null;}
		}
		if ($filter) {$value = apply_filters($namespace.'_'.$key, $value);}
		return $value;
	}

	// ---------------------
	// Reset Plugin Settings
	// ---------------------
	function reset_settings() {
		$args = $this->args; $namespace = $this->namespace;

		// --- check reset triggers ---
		// 1.0.2: fix to namespace key typo in isset check
		// 1.0.3: only use namespace not settings key
		if (!isset($_POST[$args['namespace'].'_update_settings'])) {return;}
		if ($_POST[$args['namespace'].'_update_settings'] != 'reset') {return;}

		// --- check reset permissions ---
		$capability = apply_filters($args['namespace'].'_manage_options_capability', 'manage_options');
		if (!current_user_can($capability)) {return;}
		check_admin_referer($args['slug']);

		// --- reset plugin settings ---
		$defaults = $this->default_settings();
		$defaults['savetime'] = time();
		update_option($args['option'], $defaults);

		// --- loop to remerge with settings global ---
		foreach ($defaults as $key => $value) {$GLOBALS[$namespace][$key] = $value;}

		// --- set settings reset message flag ---
		$_GET['updated'] = 'reset';
	}

	// ----------------------
	// Update Plugin Settings
	// ----------------------
	function update_settings() {
		$args = $this->args; $namespace = $this->namespace;
		$settings = $GLOBALS[$namespace];

		// --- check update triggers ---
		// 1.0.2: fix to namespace key typo in isset check
		// 1.0.3: only use namespace not settings key
		if (!isset($_POST[$args['namespace'].'_update_settings'])) {return;}
		if ($_POST[$args['namespace'].'_update_settings'] != 'yes') {return;}

		// --- check update permissions ---
		$capability = apply_filters($namespace.'_manage_options_capability', 'manage_options');
		if (!current_user_can($capability)) {return;}
		check_admin_referer($args['slug']);

		// --- get plugin options and default settings ---
		$options = $this->options;
		$defaults = $this->default_settings();

		// --- maybe use custom method or function ---
		$funcname = $namespace.'_process_settings';
		if (method_exists($this, 'process_settings')) {

			// --- use class extended method if found ---
			$settings = $this->process_settings();

		} elseif (function_exists($funcname) && is_callable($funcname))  {

			// --- use namespace prefixed function if found ---
			$settings = call_user_func($funcname);

		} else {

			// --- use default loop of plugin options to get new settings ---
			foreach ($options as $key => $values) {

				// --- get option type and options ---
				$type = $values['type']; $valid = array();
				if (isset($values['options'])) {$valid = $values['options'];}

				// --- get posted value ---
				// 1.0.6: set null value for unchecked checkbox fix
				$posted = null; $postkey = $args['settings'].'_'.$key;
				if (isset($_POST[$postkey])) {$posted = $_POST[$postkey];}

				// --- sanitize value according to type ---
				// TODO: add to these sanitization types from/for more plugins ?
				if (strstr($type, '/')) {

					// --- implicit radio / select ---
					$valid = explode('/', $type);
					if (in_array($posted, $valid)) {$settings[$key] = $posted;}

				} elseif ( ($type == 'radio') || ($type == 'select') ) {

					// --- explicit radio or select ---
					if (in_array($posted, $valid)) {$settings[$key] = $posted;}

				} elseif ($type == 'checkbox') {

					// --- checkbox ---
					// 1.0.6: fix to new unchecked checkbox value
					$valid = array('', 'yes', '1', 'checked');
					if (in_array($posted, $valid)) {$settings[$key] = $posted;}
					elseif (is_null($posted)) {$settings[$key] = '';}

				} elseif ($type == 'numeric') {

					// --- number / numeric text ---
					$posted = absint($posted);
					$settings[$key] = $posted;

				} elseif ($type == 'alphanumeric') {

					// --- alphanumeric text only ---
					// TODO: maybe improve on this check ?
					$checkposted = preg_match('/^[a-zA-Z0-9_]+$/', $posted);
					if ($checkposted) {$settings[$key] = $posted;}

				} elseif ($type == 'text') {

					// --- text field (slug) ---
					$posted = sanitize_text_field($posted);
					$settings[$key] = $posted;

				} elseif ($type == 'textarea') {

					// --- text area ---
					$posted = stripslashes($posted);
					$settings[$key] = $posted;

				} elseif ($type == 'email') {

					// --- email address ---
					// 1.0.3: added email option type checking
					$posted = sanitize_email(trim($posted));
					if ($posted) {$settings[$key] = $posted;} else {$settings[$key] = '';}

				} elseif ($type == 'emails') {

					// --- email address list ---
					// 1.0.6: added comma separated email list option type
					if (strstr($posted, ',')) {$emails = explode($posted, ',');}
					else {$emails = array(trim($posted));}
					foreach ($emails as $i => $email) {
						$email = sanitize_email(trim($email));
						if (!empty($email) && $email) {$emails[$i] = $email;} else {unset($emails[$i]);}
					}
					if (count($emails) > 0) {$settings[$key] = implode($emails, ',');}
					else {$settings[$key] = '';}

				} elseif ($type == 'usernames') {

					// --- username list ---
					// 1.0.3: added username option type checking
					$usernames = array();
					if (strstr($posted, ',')) {$usernames = explode(',', $posted);}
					else {$usernames = array(trim($posted));}
					foreach ($usernames as $i => $username) {
						$username = trim($username);
						$user = get_user_by('login', $username);
						if (!$user) {unset($username[$i]);}
					}
					if (count($usernames) > 0) {$settings[$key] = implode(',', $usernames);}
					else {$settings[$key] = '';}

				} elseif ($type == 'url') {
					// 1.0.6: fix to type variable typo (vtype)

					// --- URL address ---
					// 1.0.4: added validated URL option
					// TODO: maybe replace with a regex URL filter ?
					// 1.0.6: fix to posted variable type (vposted)
					$url = filter_var($posted, FILTER_SANITIZE_STRING);
					if ( (substr($url, 0, 4) != 'http') || !filter_var($url, FILTER_VALIDATE_URL)) {$posted = '';}
					$settings[$key] = $posted;

				} elseif ($type == 'csv') {

					// -- comma separated values ---
					// 1.0.4: added comma separated values option
					$values = array();
					if (strstr($posted, ',')) {$values = explode(',', $posted);} else {$values[0] = $posted;}
					foreach ($values as $i => $value) {$values[$i] = trim($value);}
					$valuestring = implode(',', $values);
					$settings[$key] = $valuestring;

				} elseif ($type == 'csvslugs') {

					// -- comma separated slugs ---
					// 1.0.4: added comma separated slugs option
					$values = array();
					if (strstr($posted, ',')) {$values = explode(',', $posted);} else {$values[0] = $posted;}
					foreach ($values as $i => $value) {$values[$i] = sanitize_title(trim($value));}
					$valuestring = implode(',', $values);
					$settings[$key] = $valuestring;

				}

			}
		}

		// --- process special settings ---
		// 1.0.2: added for processing special settings separately
		$funcname = $namespace.'_process_special';
		if (method_exists($this, 'process_special')) {

			// --- use class extended method if found ---
			$settings = $this->process_special($settings);

		} elseif (function_exists($funcname) && is_callable($funcname))  {

			// --- use namespace prefixed function if found ---
			$settings = call_user_func($funcname, $settings);
		}


		if ($settings && is_array($settings)) {

			// --- loop default keys to remove others ---
			$settings_keys = array_keys($defaults);
			foreach ($settings as $key => $value) {
				if (!in_array($key, $settings_keys)) {unset($settings[$key]);}
			}

			// --- update the plugin settings ---
			$settings['savetime'] = time();
			update_option($args['option'], $settings);

			// --- merge with existing settings for pageload ---
			foreach ($settings as $key => $value) {$GLOBALS[$namespace][$key] = $value;}

			// --- set settings update message flag ---
			$_GET['updated'] = 'yes';

		} else {$_GET['updated'] = 'no';}

		// --- maybe update pro settings ---
		if (method_exists($this, 'pro_update_settings')) {$this->pro_update_settings();}
		else {
			if (isset($args['pronamespace'])) {$funcname = $args['pronamespace'].'_update_settings';}
			else {$funcname = $args['namespace'].'_pro_update_settings';}
			if (function_exists($funcname)) {call_user_func($funcname);}
		}

	}

	// ---------------
	// Delete Settings
	// ---------------
	function delete_settings() {
		// TODO: check for settings delete settings switch ?
		// $args = $this->args;
		// delete_option($args['option']);
	}


	// ===============
	// --- Loading ---
	// ===============

	// --------------------
	// Load Plugin Settings
	// --------------------
	function load_settings() {
		$args = $this->args; $namespace = $this->namespace;
		$GLOBALS[$namespace] = $args;
		$settings = get_option($args['option'], false);
		if ($settings && is_array($settings)) {
			foreach ($settings as $key => $value) {$GLOBALS[$namespace][$key] = $value;}
		} else {
			$defaults = $this->default_settings();
			foreach ($defaults as $key => $value) {$GLOBALS[$namespace][$key] = $value;}
		}
	}

	// -----------
	// Add Actions
	// -----------
	function add_actions() {
		$args = $this->args; $namespace = $this->namespace;

		// --- add settings on activation ---
		register_activation_hook($args['file'], array($this, 'add_settings'));

		// --- always check for update and reset of settings ---
		add_action('admin_init', array($this, 'update_settings'));
		add_action('admin_init', array($this, 'reset_settings'));

		// --- add plugin submenu ---
		add_action('admin_menu', array($this, 'settings_menu'), 1);

		// --- add plugin settings page link ---
		add_filter('plugin_action_links', array($this, 'settings_link'), 10, 2);

		// --- delete settings on deactivation ---
		// TODO: check for setting to delete settings
		// register_deactivation_hook($args['file'], array($this, 'delete_settings'));

		// --- maybe load thickbox ---
		add_action('admin_enqueue_scripts', array($this, 'maybe_load_thickbox'));

		// --- AJAX readme viewer ---
		add_action('wp_ajax_'.$namespace.'_readme_viewer', array($this, 'readme_viewer'));
	}

	// ---------------------
	// Load Helper Libraries
	// ---------------------
	function load_helpers() {
		$args = $this->args; $file = $args['file']; $dir = $args['dir'];

		// --- Plugin Slug ---
		if (!isset($args['slug'])) {$args['slug'] = substr($file, 0, -4); $this->args = $args;}

		// --- Pro Functions ---
		$plan = 'free';
		// 1.0.2: auto-load the Pro file(s)
		if (count($args['profiles']) > 0) {
			foreach ($args['profiles'] as $profile) {
				// --- chech for php extension ---
				if (substr($profile, -4, 4) == '.php') {
					$filepath = $dir.'/'.$profile;
					if (file_exists($filepath)) {$plan = 'premium'; include($filepath);}
				}
			}
		}
		$args['plan'] = $plan; $this->args = $args;

		// --- Plugin Update Checker ---
		// note: lack of updatechecker.php file indicates WordPress.Org SVN repo version
		// presence of updatechecker.php indicates direct site download or GitHub version
		$wporg = true; $updatechecker = $dir.'/updatechecker.php';
		if (file_exists($updatechecker)) {
			$wporg = false; $slug = $args['slug'];
			// note: requires $file and $slug to be defined
			include($updatechecker);
		}
		$args['wporg'] = $wporg; $this->args = $args;

		// --- WordQuest Admin ---
		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			global $wordquestplugins; $slug = $args['slug'];
			foreach ($args as $key => $value) {$wordquestplugins[$slug][$key] = $value;}
			$wordquest = $dir.'/wordquest.php';
			if (file_exists($wordquest) && is_admin()) {include($wordquest);}
		}

		// --- Freemius ---
		if (version_compare(PHP_VERSION, '5.4.0') >= 0) {$this->load_freemius();}

	}

	// -------------------
	// Maybe Load Thickbox
	// -------------------
	function maybe_load_thickbox() {
		$args = $this->args;
		if (isset($_REQUEST['page']) && ($_REQUEST['page'] == $args['slug'])) {add_thickbox();}
	}

	// -------------
	// Readme Viewer
	// -------------
	function readme_viewer() {
		$args = $this->args;

		echo "<html><body style='font-family: Consolas, \"Lucida Console\", Monaco, FreeMono, monospace'>";

		$readme = dirname($args['file']).'/readme.txt';
		$contents = file_get_contents($readme);
		$parser = dirname($args['file']).'/readme.php';

		if (file_exists($parser)) {

			// --- include Markdown Readme Parser ---
			include($parser);
			$contents = str_replace('License: GPLv2 or later', '', $contents);
			$contents = str_replace('License URI: http://www.gnu.org/licenses/gpl-2.0.html', '', $contents);

			// --- instantiate Parser class ---
			$readme = new WordPress_Readme_Parser;
			$parsed = $readme->parse_readme_contents($contents);

			// --- output plugin info ---
			echo "<b>Plugin Name</b>: ".$parsed['name']."<br>";
			// echo "<b>Tags</b>: ".implode(', ', $parsed['tags'])."<br>";
			echo "<b>Requires at least</b>: WordPress v".$parsed['requires_at_least']."<br>";
			echo "<b>Tested up to</b>: WordPress v".$parsed['tested_up_to']."<br>";
			if (isset($parsed['stable_tag'])) {echo "<b>Stable Tag</b>: ".$parsed['stable_tag']."<br>";}
			echo "<b>Contributors</b>: ".implode(', ', $parsed['contributors'])."<br>";
			// echo "<b>Donate Link</b>: <a href='".$parsed['donate_link']."' target=_blank>".$parsed['donate_link']."</a><br>";
			echo "<br>".$parsed['short_description']."<br><br>";

			// --- output sections ---
			// possible sections: 'description', 'installation', 'frequently_asked_questions',
			// 'screenshots', 'changelog', 'change_log', 'upgrade_notice'
			$strip = array('installation', 'screenshots');
			foreach ($parsed['sections'] as $key => $section) {
				if (!empty($section) && !in_array($key, $strip)) {
					if (strstr($key, '_')) {$parts = explode('_', $key);} else {$parts = array(); $parts[0] = $key;}
					foreach ($parts as $i => $part) {$parts[$i] = strtoupper(substr($part, 0, 1)).substr($part, 1);}
					$title = implode(' ', $parts);
					echo "<h3>".$title."</h3>";
					echo $section;
				}
			}
			if (isset($parsed['remaining_content']) && !empty($remaining_content)) {
				echo "<h3>Extra Notes</h3>".$parsed['remaining_content'];
			}

		} else {
			// --- fallback text display ---
			$readme = dirname($args['file']).'/readme.txt';
			$contents = str_replace("\n", "<br>", $contents);
			echo $contents;
		}

		echo "</body></html>"; exit;
	}


	// =======================
	// --- Freemius Loader ---
	// =======================
	//
	// required settings keys:
	// -----------------------
	// freemius_id	- plugin ID from Freemius plugin dashboard
	// freemius_key	- public key from Freemius plugin dashboard
	//
	// optional settings keys:
	// -----------------------
	// plan 		- (string) curent plugin plan (value of 'free' or 'premium')
	// hasplans		- (boolean) switch for whether plugin has premium plans
	// hasaddons	- (boolean) switch for whether plugin has premium addons
	// wporg		- (boolean) switch for whether free plugin is WordPress.org compliant
	// contact		- (boolean) submenu switch for plugin Contact (defaults to on for premium only)
	// support		- (boolean) submenu switch for plugin Support (default on)
	// account		- (boolean) submenu switch for plugin Account (default on)
	// parentmenu	- (string) optional slug for plugin parent menu
	//
	// okay lets do this...
	// ====================
	function load_freemius() {

		$args = $this->args; $namespace = $this->namespace;

		// --- check for required keys ---
		if (!isset($args['freemius_id']) || !isset($args['freemius_key'])) {return;}

		// --- check for free / premium plan ---
		// convert plan string value of 'free' or 'premium' to boolean premium switch
		$premium = false; if (isset($args['plan']) && ($args['plan'] == 'premium')) {$premium = true;}

		// --- maybe redirect link to plugin support forum ---
		if (isset($_REQUEST['page']) && ($_REQUEST['page'] == $args['slug'].'-wp-support-forum') && is_admin()) {
			if (!function_exists('wp_redirect')) {include(ABSPATH.WPINC.'/pluggable.php');}
			if (isset($args['support'])) {
				// changes the support forum slug for premium based on the pro plugin file slug
				if ($premium) {$support_url = str_replace($args['slug'], $args['proslug'], $args['support']);}
				$support_url = apply_filters('freemius_plugin_support_url_redirect', $support_url, $args['slug']);
				wp_redirect($support_url); exit;
			}
		}

		// --- do the Freemius Loading boogie ---
		if (!isset($args['freemius'])) {

			// --- start the Freemius SDK ---
			if (!class_exists('Freemius')) {
				$freemiuspath = dirname(__FILE__).'/freemius/start.php';
				if (file_exists($freemiuspath)) {require_once($freemiuspath);} else {return;}
			}

			// --- set defaults for optional key values ---
			if (!isset($args['hasaddons'])) {$args['hasaddons'] = false;}
			if (!isset($args['hasplans'])) {$args['hasplans'] = false;}
			if (!isset($args['wporg'])) {$args['wporg'] = false;}

			// --- set defaults for options submenu key values ---
			// 1.0.2: fix to isset check keys
			// 1.0.5: fix to set args subkeys for support and account
			if (!isset($args['support'])) {$args['support'] = true;}
			if (!isset($args['account'])) {$args['account'] = true;}
			// by default, enable contact submenu item for premium plugins only
			if (!isset($args['contact'])) {$args['contact'] = $premium;}
			if (!isset($args['type'])) {$args['type'] = 'plugin';}

			// --- Freemius settings from plugin settings ---
			$settings = array(
				'type'				=> $args['type'],
				'slug'              => $args['slug'],
				'id'                => $args['freemius_id'],
				'public_key'        => $args['freemius_key'],
				'has_addons'        => $args['hasaddons'],
				'has_paid_plans'    => $args['hasplans'],
				'is_org_compliant'  => $args['wporg'],
				'is_premium'        => $premium,
				'menu'              => array(
					'slug'       	=> $args['slug'],
					'first-path' 	=> 'admin.php?page='.$args['slug'].'&welcome=true',
					'contact'		=> $args['contact'],
					'support'		=> $args['support'],
					'account'		=> $args['account'],
			   )
			);

			// --- maybe add plugin submenu to parent menu ---
			if (isset($args['parentmenu'])) {
				$settings['menu']['parent'] = array('slug' => $args['parentmenu']);
			}

			// --- filter settings before initializing ---
			$settings = apply_filters('freemius_init_settings_'.$args['namespace'], $settings);
			if (!$settings || !is_array($settings)) {return;}

			// --- initialize Freemius now ---
			$freemius = $GLOBALS[$namespace.'_freemius'] = fs_dynamic_init($settings);

			// --- set plugin basename ---
			// 1.0.1: set free / premium plugin basename
			if (method_exists($freemius, 'set_basename')) {
				$freemius->set_basename($premium, $args['file']);
			}

			// --- add Freemius connect message filter ---
			$this->freemius_connect();
		}
	}

	// -----------------------
	// Filter Freemius Connect
	// -----------------------
	function freemius_connect() {
		$namespace = $this->args['namespace']; $freemius = $GLOBALS[$namespace.'_freemius'];
		if (isset($settings['freemius']) && is_object($freemius) && method_exists($freemius, 'add_filter') ) {
			$freemius->add_filter('connect_message', array($this, 'freemius_connect_message'), WP_FS__DEFAULT_PRIORITY, 6);
		}
	}

	// ------------------------
	// Freemius Connect Message
	// ------------------------
	function freemius_connect_message($message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link) {
		// default: 'Never miss an important update - opt-in to our security and feature updates notifications, and non-sensitive diagnostic tracking with %4$s.'
		$message = __fs('hey-x').'<br>';
		$message .= sprintf(
			__("If you want to more easily access support and feedback for this plugins features and functionality, %s can connect your user, %s at %s, to %s"),
			$user_first_name, '<b>'.$plugin_title.'</b>', '<b>'.$user_login.'</b>', $site_link, $freemius_link
		);
		return $message;
	}

	// ----------------------
	// Connect Update Message
	// ----------------------
	// TODO: message for connect updates
	function freemius_update_message($message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link) {
		// default: 'Please help us improve %1$s! If you opt-in, some data about your usage of %1$s will be sent to %4$s. If you skip this, that\'s okay! %1$s will still work just fine.'
		$message = freemius_message($message, $user_first_name, $plugin_title, $user_login, $site_link, $freemius_link);
	}


	// =============
	// --- Admin ---
	// =============

	// -----------------
	// Add Settings Menu
	// -----------------
	function settings_menu() {
		$args = $this->args; $namespace = $this->namespace; $settings = $GLOBALS[$namespace];

		// --- filter

		$args['capability'] = apply_filters($args['namespace'].'_manage_options_capability', 'manage_options');
		if (!isset($args['pagetitle'])) {$args['pagetitle'] = $args['title'];}
		if (!isset($args['menutitle'])) {$args['menutitle'] = $args['title'];}

		// --- check for WordQuest admin page function ---
		if (function_exists('wqhelper_admin_page')) {

			// --- filter menu capability early ---
			$capability = apply_filters('wordquest_menu_capability', 'manage_options');

			// --- maybe add Wordquest top level menu ---
			global $admin_page_hooks;
			if (empty($admin_page_hooks['wordquest'])) {
				$icon = plugins_url('images/wordquest-icon.png', $args['file']);
				$position = apply_filters('wordquest_menu_position', '3');
				add_menu_page('WordQuest Alliance', 'WordQuest', $capability, 'wordquest', 'wqhelper_admin_page', $icon, $position);
			}

			// --- check if using parent menu ---
			// (and parent menu capability)
			if (isset($args['parentmenu']) && ($args['parentmenu'] == 'wordquest') && current_user_can($capability)) {

				// --- add WordQuest Plugin Submenu ---
				$menuadded = add_submenu_page('wordquest', $args['pagetitle'], $args['menutitle'], $args['capability'], $args['slug'], $args['namespace'].'_settings_page');

				// --- add icons and styling fix to the plugin submenu :-) ---
				add_action('admin_footer', array($this, 'submenu_fix'));
			}
		}

		// --- add standalone options page if WordQuest Admin not loaded ---
		if (!isset($menuadded) || !$menuadded) {
			add_options_page($args['pagetitle'], $args['menutitle'], $args['capability'], $args['slug'], $args['namespace'].'_settings_page');
		}
	}

	// ---------------------
	// WordQuest Submenu Fix
	// ---------------------
	function submenu_fix() {
		$args = $this->args; $slug = $args['slug']; $current = '0';
		$icon_url = plugins_url('images/icon.png', $args['file']);
		if (isset($_REQUEST['page']) && ($_REQUEST['page'] == $slug) ) {$current = '1';}
		echo "<script>jQuery(document).ready(function() {if (typeof wordquestsubmenufix == 'function') {
		wordquestsubmenufix('".$slug."','".$icon_url."','".$current."');} });</script>";
	}

	// -------------------------
	// Plugin Page Settings Link
	// -------------------------
	function settings_link($links, $file) {
		$args = $this->args;
		if ($file == plugin_basename($args['file'])) {
			$settingslink = "<a href='".admin_url('admin.php')."?page=".$args['slug']."'>".__('Settings')."</a>";
			array_unshift($links, $settingslink);
		}
		return $links;
	}

	// -----------
	// Message Box
	// -----------
	function message_box($message, $echo) {
		$box = "<table style='background-color: lightYellow; border-style:solid; border-width:1px; border-color: #E6DB55; text-align:center;'>";
		$box .= "<tr><td><div class='message' style='margin:0.25em;'><font style='font-weight:bold;'>";
		$box .= $message."</font></div></td></tr></table>";
		if ($echo) {echo $box;} else {return $box;}
	}

	// ------------------
	// Plugin Page Header
	// ------------------
	function settings_header() {
		$args = $this->args; $namespace = $this->namespace; $settings = $GLOBALS[$namespace];

		// --- check for animated gif icon with fallback to normal icon --
		if (file_exists($this->args['dir'].'/images/'.$args['slug'].'.gif')) {
			$icon_url = plugins_url('images/'.$args['slug'].'.gif', $args['file']);
		} else {$icon_url = plugins_url('images/'.$args['slug'].'.png', $args['file']);}
		$icon_url = apply_filters($namespace.'_plugin_icon_url', $icon_url);

		// --- check for author icon based on provided author name ---
		// 1.0.2: check if author icon file exists and fallback
		$author_slug = strtolower(str_replace(' ', '', $args['author']));
		if (file_exists($this->args['dir'].'/images/'.$author_slug.'.png')) {
			$author_icon_url = plugins_url('images/'.$author_slug.'.png', $args['file']);
		} elseif (file_exists($this->args['dir'].'/images/wordquest.png')) {
			$author_icon_url = plugins_url('images/wordquest.png', $args['file']);
		} else {$author_icon_url = false;}
		$author_icon_url = apply_filters($namespace.'_author_icon_url', $author_icon_url);

		// --- plugin header styles ---
		echo "<style>.pluginlink {text-decoration:none;} .smalllink {font-size:11px;}
		.readme:hover {text-decoration:underline;}</style>";

		// --- plugin icon ---
		echo "<table><tr><td><img src='".$icon_url."' width='128' height='128'></td>";

		echo "<td width='20'></td><td>";

			echo "<table><tr><td>";

				// --- plugin title ---
				echo "<h2 style='font-size:20px;'><a href='".$args['home']."' style='text-decoration:none;'>".$args['title']."</a></h2></a>";

			echo "</td><td width='20'></td>";

			// --- plugin version ---
			echo "<td><h3>v".$args['version']."</h3></td></tr>";

			echo "<tr><td colspan='3' align='center'>";

				// ---- plugin author ---
				echo "<table><tr><td align='center'>";

					echo "<font style='font-size:16px;'>".__('by')."</font> ";
					echo "<a href='".$args['author_url']."' target=_blank style='text-decoration:none;font-size:16px;' target=_blank><b>".$args['author']."</b></a><br><br>";

					// --- readme / docs / support links ---
					$readme_url = add_query_arg('action', $namespace.'_readme_viewer', admin_url('admin-ajax.php'));
					echo "<a href='".$readme_url."' class='pluginlink smalllink thickbox' title='readme.txt'><b>".__('Readme')."</b></a>";
					if (isset($args['docs'])) {echo " | <a href='".$args['docs']."' class='pluginlink smalllink' target=_blank><b>".__('Docs')."</b></a>";}
					if (isset($args['support'])) {echo " | <a href='".$args['support']."' class='pluginlink smalllink' target=_blank><b>".__('Support')."</b></a>";}

				echo "</td><td>";

					// --- author icon ---
					if ($author_icon_url) {
						echo "<a href='".$args['author_url']."' target=_blank><img src='".$author_icon_url."' width='64' height='64' border='0'>";
					}

				echo "</td></tr></table>";

			echo "</td></tr></table>";

		echo "</td><td width='50'></td><td style='vertical-align:top;'>";

			// --- plugin supporter links ---
			// 1.0.1: set rate/share/supporter links and texts
			echo "<br>";

			// --- rate link ---
			if (isset($args['wporgslug'])) {
				if (isset($args['rate'])) {$rate_url = $args['rate'];}
				elseif (isset($args['type']) && ($args['type'] == 'theme')) {
					$rate_url = 'https://wordpress.org/support/theme/'.$args['wporgslug'].'/reviews/#new-post';
				} else {$rate_url = 'https://wordpress.org/plugins/'.$args['wporgslug'].'/reviews/#new-post';}
				if (isset($args['ratetext'])) {$rate_text = $args['ratetext'];}
				else {$rate_text = __('Rate on WordPress.Org');}
				echo "<a href='".$rate_url."' class='pluginlink' target='_blank'>";
				echo "<span style='font-size:24px; color:#FC5; margin-right:10px;' class='dashicons dashicons-star-filled'></span> ";
				echo $rate_text."</a><br><br>";
			}

			// --- share link ---
			if (isset($args['share'])) {
				if (isset($args['sharetext'])) {$share_text = $args['sharetext'];}
				else {$share_text = __('Share the Plugin Love');}
				echo "<a href='".$args['share']."' class='pluginlink' target='_blank'>";
				echo "<span style='font-size:24px; color:#E0E; margin-right:10px;' class='dashicons dashicons-share'></span> ";
				echo $share_text."</a><br><br>";
			}

			// --- donate link ---
			if (isset($args['donate'])) {
				if (isset($args['donatetext'])) {$donate_text = $args['donatetext'];}
				else {$donate_text = __('Support this Plugin');}
				echo "<a href='".$args['donate']."' class='pluginlink' target='_blank'>";
				echo "<span style='font-size:24px; color:#E00; margin-right:10px;' class='dashicons dashicons-heart'></span> ";
				echo "<b>".$donate_text."</b></a><br><br>";
			}
		echo "</td></tr>";

		// --- updated and reset messages ---
		if (isset($_GET['updated'])) {
			if ($_GET['updated'] == 'yes') {$message = $settings['title'].' '.__('Settings Updated.');}
			elseif ($_GET['updated'] == 'no') {$message = __('Error! Settings NOT Updated.');}
			elseif ($_GET['updated'] == 'reset') {$message = $settings['title'].' '.__('Settings Reset!');}
			if (isset($message)) {
				echo "<tr><td></td><td></td><td align='center'>".$this->message_box($message, false)."</td></tr>";
			}
		} else {
			// --- maybe output welcome message ---
			if (isset($_REQUEST['welcome']) && ($_REQUEST['welcome'] == 'true')) {
				if (isset($args['welcome'])) {
					echo "<tr><td colspan='3' align='center'>".$this->message_box($args['welcome'], false)."</td></tr>";
				}
			}
		}

		echo "</table><br>";
	}

	// -------------
	// Settings Page
	// -------------
	function settings_page() {
		// TODO: could create an automatic settings page here
		// based on the passed plugin options and default settings...
		// ...or not...
	}

} // end plugin loader class


// ----------------------------------
// Load Namespaced Prefixed Functions
// ----------------------------------
// [Optional] rename functions prefix to your plugin namespace
// these functions will then be available within your plugin
// to more easily call the matching plugin loader class methods

// 1.0.3: added priority of 0 to prefixed function loading action
add_action('plugins_loaded', 'forcefield_load_prefixed_functions', 0);

function forcefield_load_prefixed_functions() {

	// auto-magic namespacing note
	// ---------------------------
	// all function names suffixes here must be two words for the magic namespace grabber to work
	// ie. _add_settings, because the namespace is taken from before the second-last underscore

	if (!function_exists('forcefield_loader_instance')) {
		function forcefield_loader_instance() {
			$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
			return $GLOBALS[$namespace.'_instance'];
		}
	}

	// ------------
	// Add Settings
	// ------------
	if (!function_exists('forcefield_add_settings')) {
	 function forcefield_add_settings() {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		$instance->add_settings();
	 }
	}

	// ------------
	// Get Defaults
	// ------------
	if (!function_exists('forcefield_default_settings')) {
	 function forcefield_default_settings($key=false) {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		return $instance->default_settings($key);
	 }
	}

	// -----------
	// Get Options
	// -----------
	if (!function_exists('forcefield_get_options')) {
	 function forcefield_get_options() {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		return $instance->options;
	 }
	}

	// -----------
	// Get Setting
	// -----------
	if (!function_exists('forcefield_get_setting')) {
	 function forcefield_get_setting($key, $filter=true) {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		return $instance->get_setting($key, $filter);
	 }
	}

	// --------------
	// Reset Settings
	// --------------
	if (!function_exists('forcefield_reset_settings')) {
	 function forcefield_reset_settings() {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		$instance->reset_settings();
	 }
	}

	// ---------------
	// Update Settings
	// ---------------
	if (!function_exists('forcefield_update_settings')) {
	 function forcefield_update_settings() {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		$instance->update_settings();
	 }
	}

	// ---------------
	// Delete Settings
	// ---------------
	if (!function_exists('forcefield_delete_settings')) {
	 function forcefield_delete_settings() {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		$instance->delete_settings();
	 }
	}

	// -----------------
	// Set Pro Namespace
	// -----------------
	if (!function_exists('forcefield_pro_namespace')) {
	 function forcefield_pro_namespace($pronamespace) {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		$instance->pro_namespace($pronamespace);
	 }
	}

	// ---------------
	// Settings Header
	// ---------------
	if (!function_exists('forcefield_settings_header')) {
	 function forcefield_settings_header() {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		$instance->settings_header();
	 }
	}

	// -------------
	// Settings Page
	// -------------
	if (!function_exists('forcefield_settings_page')) {
	 function forcefield_settings_page() {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		$instance->settings_page();
	 }
	}

	// -----------
	// Message Box
	// -----------
	if (!function_exists('forcefield_message_box')) {
	 function forcefield_message_box($message, $echo=false) {
		$f = __FUNCTION__; $namespace = substr($f,0,strrpos($f,'_',(strrpos($f,'_')-strlen($f)-1)));
		$instance = $GLOBALS[$namespace.'_instance'];
		return $instance->message_box($message, $echo);
	 }
	}
}

// fully loaded
// ------------


// =========
// CHANGELOG
// =========

// == 1.0.6 ==
// - added global options filter
// - added 'emails' option type for multiple email saving
// - fix for new unchecked checkbox value
// - fix for typos in URL option type saving

// == 1.0.5 ==
// - fix for undefined account and support variables

// == 1.0.4 ==
// - added 'url' option type checking and saving
// - added 'csv' option type checking and saving
// - added 'csvslugs' option type checking and saving

// == 1.0.3 ==
// - added priority of 0 to prefixed function loading action
// - only use namespace not settings key for updates
// - added 'email' option type checking and save
// - added 'usernames' option type checking and save

// == 1.0.2 ==
// - fix some Freemius loading argument checks
// - fix namespace typo in update/reset triggers
// - add check for author icon file and fileback
// - add allowance for special settings processing
// - add allowance for auto-load of multiple Pro files

// == 1.0.1 ==
// - set_basename for Freemius free / premium plugins
// - set rate / share / donate links and text anchors
// - check for multiple Pro filenames and use first one

// == 1.0.0 ==
// - Working Release version

// == 0.9.0 ==
// - Development Version
