=== ForceField ===
Contributors: majick
Donate link: http://wordquest.org/donate/?plugin=forcefield
Tags: login protect, bot protect, api access, admin protect
Author URI: http://dreamjester.net
Plugin URI: http://wordquest.net/plugins/forcefield/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.0.0
Tested up to: 4.9.5
Stable tag: trunk

Flexible Protection for Login, Registration, Commenting, REST API and XML RPC.

== Description ==

Adds several layers of security to restrict access to common hacking attack vectors.

**Token Protection**

Reduce Brute Force Password attacks, SPAM Comments, Fake User Registrations and Sploggers.
Adds a dynamic Javascript Token field to all common user action forms:
Login, Registration, Blog Signup (Multisite), Lost Password and Commenting
Since the majority of bots do not have the capacity to recognize or handle javascript fields,
their attempts at access via these actions are blocked, with repeat offender getting IP banned.

**API Protection**

Adds several ways to restrict access to XML RPC and REST API features. While these *can* be disabled,
there are several other options provided to severely limit bot and other unauthorized access while still
being able to use these features as intended. Part of the aim of this plugin is to make these available 
for everyone without needed to know how to code them: Multiple request slowdown, disable XML RPC logins, 
logged in access only, restrict access to specified user roles, require secure connection.

**Admin Protection**

A last line of defense against hackers who have managed to create their own administrator account!
Automatically block, notify by email and/or *auto-delete* an account when an "administrator" logs in 
who is not in an explicit whitelist of verified admin usernames. Goodbye escalated privelage attack.

**Behavioural Protection**

Records access to user actions missing referer headers, missing or bad tokens, and other bad behaviours.
Reaching transgression limits for any specific action results in an IP ban. Transgression occurences are
reduced via cooldown over time, with old records expired and later deleted (all intervals adjustable.)
This process keeps protection high for fresh attacks while keeping the database free of record bloating.
Also option to output a form to banned IPs to unblock themselves manually in case of false positives.

**Update Protection**

Out-dated plugins and themes are a security attack vector, so of course it's good to keep them updated. 
But did you know inactive plugins are still a vulnerability? Don't get hacked because of something you are
not even using right now! ForceField allows you to auto-update inactive plugins and themes. Sorted.

[ForceField Home](http://wordquest.org/plugins/forcefield/)
[Support Forum](http://wordquest.org/support/forcefield/)

== Installation ==

1. Upload `forcefield.zip` via the Wordpress plugin installer.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the Plugin Settings via the WordQuest -> ForceField menu

== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

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
