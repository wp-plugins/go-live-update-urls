=== Plugin Name ===
Contributors: Mat Lipe
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=paypal%40lipeimagination%2einfo&lc=US&item_name=Go%20Live%20Update%20Urls&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: Go Live, Urls, Domain Changes 
Requires at least: 3.1
Tested up to: 3.4.1
Stable tag: 1.3

== Description ==

Goes through the entire database and replaces all instances of the test domain at Go Live time. Can also be used to replace all instances when changing domains.

Allows table by table selection for any issues with widgets/plugins and such.


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the `go-live-upload-urls` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Where do you use this plugin? =

Under the settings menu in the dashboard there will be a "Go Live" link.

= Why does this one uncheck the wp_options table by default? =

There are a few plugins out there that use certain values in the wp-options table which will break of they are changed manually in the database. Sometimes widgets will disappear. If you have some values that must be changed in the wp_options, I have found that you can prevent the disappearing widget problem by going through all of your widgets and clicking save on the bottom of them after you have changed the domain in general settings. You may then run the Update with the wp_options checked. This method is not fool proof, but it has worked on a few instances I have seen an actual need for updated the wp_options table manually.


== Changelog ==

= 1.3 =
* Added support for adding subdomains like www

= 1.2.1 =
* Added support for other prefixes besides wp_

= 1.2 =
* Added the wp_options to the available tables to be updated and unchecked the table by default.

= 1.1 =
* Removed the wp-options table from the tables to be updated.

== Upgrade Notice ==

= 1.3 =
This Version will allow you to switch to www without having to run it twice

= 1.2.1 =
This Version will uncheck your options table by default for the wp_options as well as other table prefixes.

= 1.2 =
This Version will add the wp_options to the available tables and uncheck the table by default.

= 1.1 =
This version will remove the wp_options from the available tables.

