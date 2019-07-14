=== ForceField ===
Contributors: majick
Donate link: http://wordquest.org/donate/?plugin=forcefield
Tags: login protect, bot protect, api access, admin protect, xml rpc, rest api, security
Author URI: http://wordquest.org
Plugin URI: http://wordquest.org/plugins/forcefield/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.0.0
Tested up to: 4.9.10
Stable tag: trunk

Strong and Flexible Access, User Action, API and Role Protection

== Description ==

Adds several layers of security to restrict access to common hacking attack vectors.

**Tokenized  Protection**

Easily reduce Brute Force Password attacks, SPAM Comments, Fake User Registrations and Sploggers! Adds a dynamic Javascript Token field to all common user action forms: Login, Registration (plus BuddyPress Registration), Blog Signup (Multisite only), Lost Password and Commenting. 

Since the majority of bots do not have the capacity to recognize or handle javascript fields, their attempts at access via these actions are instantly blocked - with repeat offender getting IP banned from further attempts. This gives seamless and invisible protection (without an annoying ReCaptcha field.)

**Login Role Protection**

A last line of defense against hackers who have managed to "somehow" create their own administrator account! Automatically block, notify by email, revoke role and/or demote to subscriber any "administrator" account that logs in who is not in an *explicit whitelist* of verified administrator usernames. Goodbye escalated privelage attack! (The same settings are also available for the Super Admin role for Multisite installs.)

**API Protection**

Adds several ways to restrict access to XML RPC and REST API features. While these *can* be disabled, there are several other options provided to severely limit bot and other unauthorized access while still being able to use these features as intended! Part of the aim of this plugin is to make these options available for everyone without needing to code them: Multiple request slowdown, disable XML RPC logins, logged in access only, restrict access to specified user roles, and require secure connection.

**Behavioural Protection**

ForceField also records access to user actions missing referer headers, missing or bad tokens, and other bad behaviours in a custom table. Reaching transgression limits for any specific action results in an IP ban. Transgression occurences are reduced via cooldown over time, with old records expired and later deleted (with intervals adjustable.) This process keeps protection high for fresh attacks while keeping the database free of old record bloat. Also gives the option to output a form to banned IPs so users can unblock themselves manually in case of false positives (and so you don't lock yourself out of your site!)

**Vulnerability Check**

Checks your installed core, plugins and themes for known vulnerabilities and sends email alerts on new vulnerabilities found.


[ForceField Home](http://wordquest.org/plugins/forcefield/)
[Support Forum](http://wordquest.org/support/forcefield/)


== Installation ==

1. Upload `forcefield.zip` via the Wordpress plugin installer.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the Plugin Settings via the WordQuest -> ForceField menu


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.0.0 =
* remove Curl fallback for Vulnerability Checker API
* updated to WordQuest Helper 1.7.5 (button fix)
* fix to loop multiple vulnerability item in admin notice
* fix to current installed version vulnerability check 
* fix to set new vulnerabilities array empty subarrays
* fix to multi-update form on vulnerabilities list
* fix to remove missing resources from vulnerability list
* added clearing of theme and plugin cache for checker

= 0.9.9 =
* updated Freemius SDK to 2.3.0
* sanitize and validate all posted action token values
* vulnerability API check default to use wp_remote_get
* fix to vulnerability checker already updated check
* fix to vulnarability alert emails for plugins/themes
* allow listing multiple vulnerabilities per resource
* added vulnerability notice dismissal button / AJAX
* added vulnerability checker API overload Detection
* added vulnerability checker auto-resume at position
* improved admin notice vulnerability display table
* improved background trigger for vulnerability check
* improved error checking for vulnerability checker

= 0.9.8 =
* updated WordQuest Helper library to 1.7.4
* added vulnerability checker for core, plugins and themes!
* use do_actions to allow adding extra admin interfaces
* use apply_filters for filtering whitelist / blacklist
* honour FORCEFIELD_REQUIRE_SSL constant override
* fix to reset input type to button so enter submits

= 0.9.7 =
* fix to transgression record check
* fix for possible empty referrer global
* fix to default limit settings
* fix to function names for signup and lostpass token fields
* fix to remove unchecked API role restrictions
* update plugin loader class 1.0.6 for multiple alert emails
* change single alert email setting type to allow multiple
* removed REST API prefix change option (better hard-coded)

= 0.9.6 =
* Updated to use Plugin Loader Class
* Disabled Plugin/Theme Updates sections
* maybe transfer old Settings from settings key
* moved Role Protect options to separate tab
* allow for auto-pass/auto-fail limit values
* added filters for contexts/intervals/expiries
* added nonce field to user IP unblock form
* fix to IP blocklist display query arguments

= 0.9.5 =
* Added BuddyPress registration token field 
* Fix multiple undefined function forcefield_record_ip
* Fix record retrieval by reason without specific IP
* Removed auto-update features for retesting

= 0.9.4 =
* Better localhost Detection for Local Usage
* Auto-Refresh Tokens according to Expiry Time
* Added Context-specific Token Expiry Time Filter
* Added Option to Auto-update Inactive Plugins
* Added Option to Auto-update Inactive Themes
* Added Option to Auto-update this Plugin

= 0.9.3 = 
* Dynamically add token input field to forms
* Added obfuscation of javascript token value

= 0.9.2 = 
* Single Global Plugin Settings Array
* Improved Blacklist / Whitelist Checking
* IP 4 Range Support for Blacklist / Whitelist
* Added calls for possible Pro features

= 0.9.1 =
* Added IP Table Recording with Transgression Limits
* Added Restrict API Access by Role Option
* Added Simple IP Blacklist / Whitelist
* Added Settings Reset to Default Button
* Standardized User Action Tokenizer Logic

= 0.9.0 =
* Development Version
* Javascript Tokenizer Engine
* Plugin Admin Options


== Other Notes ==

[ForceField Home](http://wordquest.org/plugins/forcefield/)

Like this plugin? Check out more of our free plugins here: 
[WordQuest](http://wordquest.org/plugins/ "WordQuest Plugins")

Looking for an awesome theme? Check out my child theme framework:
[BioShip Child Theme Framework](http://bioship.space "BioShip Child Theme Framework")

= Support =
For support or if you have an idea to improve this plugin:
[ForceField Support Quests](http://wordquest.org/support/forcefield/ "ForceField Support")

= Contribute = 
Help support improvements and log priority feature requests by a gift of appreciation:
[Contribute to ForceField](http://wordquest.org/contribute/?plugin=forcefield)

= Development =
To aid directly in development, please fork on Github and do a pull request:
[ForceField on Github](http://github.com/majick777/forcefield/)

= Limitations = 

= Planned Updates/Features =
