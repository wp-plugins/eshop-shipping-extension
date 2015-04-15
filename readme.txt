=== Plugin Name ===
Contributors: useStrict
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VLQU2MMXKB6S2
Tags: eShop, Canada Post, USPS, United States Postal Service, UPS, United Parcel Service, Federal Express, Fedex, Correios, Shipping Extension, Third Party Shipping, Shipping Quotes
Requires at least: 3.0
Tested up to: 4.1.1
Stable tag: 2.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Replace eShop's default shipping methods with live calls to Canada Post, Correios, UPS, Fedex, and USPS.

== Description ==

eShop Shipping Extension framework overrides eShop's default shipping methods, interacting directly with Canada Post, UPS, USPS, Fedex, and Correios for real-time shipping rates and services.

This framework includes the Canada Post module for free. 

= Related Modules (click to buy): = 

* [USPS extension](http://usestrict.net/2012/07/usps-module-for-wordpress-eshop-shipping-extension/),
* [UPS extension](http://usestrict.net/2012/07/ups-module-for-wordpress-eshop-shipping-extension/),
* [Fedex extension](http://usestrict.net/2012/10/fedex-module-for-eshop-shipping-extension/),
* [Correios (Brazil) extension](http://usestrict.net/2012/08/modulo-correios-brasil-para-o-eshop-shipping-extension/)
* [Bulk Package Class Helper](http://usestrict.net/2012/10/bulk-package-class-helper-for-eshop-shipping-extension/)
* [Handling Fee Add-on](http://usestrict.net/2012/12/handling-fee-add-on-for-eshop-shipping-extension)
* [Free Shipping Locations Lite (free)](http://usestrict.net/2013/01/eshop-free-shipping-locations-lite/)
* [Per Product Stock Control](http://usestrict.net/2013/03/eshop-per-product-stock-control/)

Other modules will be made available soon at [UseStrict Consulting](http://usestrict.net)

== Installation ==

1. Upload eshop-shipping-extension.zip to your blog's wp-content/plugins directory;
1. Activate the plugin in your Plugin Admin interface;
1. Set eShop shipping preferences to Mode 4 (by Weight & Zone);
1. Go to Settings -> eShop Shipping Extension to activate your preferred third-party interface;
1. Follow the instructions on how to obtain your third-party API credentials, and save your preferences.

== Frequently Asked Questions ==

= How can I enable API calls for services like United States Postal Service (USPS), UPS, Correios, Fedex, etc.? =

* USPS module is available for purchase [here](http://usestrict.net/2012/07/usps-module-for-wordpress-eshop-shipping-extension/).
* UPS module is available for purchase [here](http://usestrict.net/2012/07/ups-module-for-wordpress-eshop-shipping-extension/).
* Correios module is available for purchase [here](http://usestrict.net/2012/08/modulo-correios-brasil-para-o-eshop-shipping-extension/).
* Fedex module is available for purchase [here](http://usestrict.net/2012/10/fedex-module-for-eshop-shipping-extension/).
* Other modules will be available soon at [UseStrict Consulting](http://usestrict.net)

= How can I set Package Classes to all my items in one sweep? =

* We offer a helper plugin that allows the user to manage Package Class to Product association in bulk. 
Find out more [here](http://usestrict.net/2012/10/bulk-package-class-helper-for-eshop-shipping-extension).


= Is it possible to add a Handling Fee to my shipments? =

Yes! By using the Handling Fee Add-on. You can get it [here](http://usestrict.net/2012/12/handling-fee-add-on-for-eshop-shipping-extension/).

= I want to offer free Domestic shipping for purchases over a certain amount. How do I do that? =

eShop supports free shipping for purchases of a given total price under Settings->eShop->Discounts. However, you cannot specify the location without installing another of UseStrict's plugins:
[eshop-free-shipping-locations-lite](http://wordpress.org/extend/plugins/eshop-free-shipping-locations-lite/) or eshop-free-shipping-locations-pro (coming soon). The Lite version checks the client's state against the state the admin specified as being eligible for free shipping - regardless of the amount spent.
The Pro version gives you more flexibility: 

* Select multiple countries/states
* Specify whether the locations override the price or if both price and location are taken into consideration
* And more to come

  

== Screenshots ==

1. A few easy settings get you up and running in minutes.
1. The shipping options is moved to the bottom of the order form and will display Service Name, Price, and a couple of service descriptions received from Canada Post API.
1. The shipping mode selected is displayed next to the Shipping item in the order form, so you know what kind of service your client selected. 

== Changelog ==
= 2.3.2 = 
* Remove schema from ajaxurl. In an edge case, somehting was returning the wrong-schema, so use schema-less for all.

= 2.3.1 =
* Fix premature loading of pluggable.php, which was breaking User Switching functionality offered by the plugin with the same name.

= 2.3 =
* Allow passing custom fields in JS for more flexibility. 

= 2.2 =
* Added option to display shipping services as radio buttons instead of dropdown. Thanks to Russel Consulting, Inc. for sponsoring this addition. 

= 2.1.17 =
* Added: filter usc_ese_filter_services_array to manipulate services (sort, change names, whatever you want to do).

= 2.1.16 =
* Fixed: using delegation for jQuery.on().

= 2.1.15 =
* Dropped version checks as it wasn't working with jQuery 1.10.x, so just check if jQuery.fn.on exists before reverting to jQuery.fn.live

= 2.1.14 =
* Fixed jQuery version check

= 2.1.13 =
* Added the ability to blacklist a service

= 2.1.12 =
* convert_currency() fixes: 1) replaced file_get_contents() with wp_remote_get(); 2) fixed case where value was truncated due to bad characters in Google's response. 

= 2.1.11 =
* Added get_options filter.

= 2.1.10 =
* Showing shipping fieldset even if shipping fields were hidden.

= 2.1.9 =
* Added filter for Canada Post to work with Custom Handling fee add-on v2.0. 

= 2.1.8 = 
* Changed logic to identify if eShop is installed. Thanks Nicolaus Sommer.

= 2.1.7 =
* Added support for [Free Shipping Locations modules](http://usestrict.net/2013/01/eshop-free-shipping-locations-lite).

= 2.1.6 =
* Merging errors in 2.1.5 removed the duplicate admin notices fix.

= 2.1.5 =
* Fixed duplicate admin notices with WP 3.5.
* Updated screenshot-1
* Updated css to force float:left and clear:both on View/Update Shipping Options text

= 2.1.4 =
* Added support for the Handling Fee add-on.
* A few minor html fixes in the admin screen. 
* Number formatting for Canada Post weight, maximum 3 decimals.

= 2.1.3 =
* Fixed a bug with the free shipping option.

= 2.1.2 =
* Fixed a case where saving a product could not work depending on the package class selection.

= 2.1.1 =
* Added option for callback in JS call_get_rates();

= 2.1 =
* Handling eShop "free shipping over value" option.
* Playing nicely with new Bulk Package Class management.

= 2.0.12 =
* Fixed a bug where the shipping would sometimes not be carried over into the checkout overview form.
* Fixed the shipping option field rendering when the form fails with an error - maintaing state.
* Added version string next to module names in Admin.

= 2.0.11 = 
* Small glitch (PHP Warning) when adding a new product after renaming package classes.
* Improved package bundling logic.
* Fixed WP update issue - deactivate/reactivate no longer required for people who purchased UPS/USPS/Correios

= 2.0.10 =
* Quoting class attribute in javascript so it won't break IE < 9. 

= 2.0.9 =
* No longer calling get_rates() when change or blur of address fields. This was causing problems with too many hits to Google currency exchange.

= 2.0.8 =
* Fixed an issue with In-store pickup option.

= 2.0.7 =
* Fixed a bug which could potentially break currency conversion

= 2.0.6 =
* Removed extra JS logging
* Separated multiple JS error messages with "; "

= 2.0.5 =
* Improved Shipping Service drop-down with multiple carriers.

= 2.0.4 =
* Fixed "Unsupported Operand Types" error.

= 2.0.3 =
* Fixed "Cannot re-assign auto-global variable" error for PHP 5.4.

= 2.0.2 =
* Fixed "Call-time pass-by-reference has been removed" error for PHP 5.4.

= 2.0.1 =
* Re-added "None" option as a radio button.
* Updated Admin screenshot.

= 2.0 =
* Allowing multiple vendors to be used at the same time. Currencies are converted into the currency selected in eShop if required.

= 1.5.1 =
* Small typo in previous commit which made much of eSE Admin's text bold.

= 1.5 -
* Added in-store pickup option.

= 1.4.7 =
* Fixed the date shown on the admin order page.

= 1.4.6 =
* Fixed an error when adding more than 10 package classes in the admin.

= 1.4.5 =
* Added link in readme.txt to Correios module

= 1.4.4 =
* Changed SimpleXMLElement->count() to core count() for people using PHP < 5.3

= 1.4.3 =
* Covered another scenario for bug found in 1.4.2

= 1.4.2 =
* Fixed minor bug that appeared when no package classes had been created and users went into the post editor.

= 1.4.1 =
* Fixed a bug where Global Package Options still considered Package Class mandatory in the Product Entry form.

= 1.4 =
* NEW: Advanced packaging options (added product, and product-option levels)
* Minor data massaging bug
* Replace hardcoded XML with SimpleXML
* Fixed total weight bug - jQuery did not always pass the correct weight to the rating API

= 1.3.2 =
* Fixed "ZipCode Required" error for UPS users

= 1.3.1 =
* Fixed bad call to jQuery.live() for users with jQuery version < 1.7

= 1.3 =
* A few changes to support UPS module

= 1.2.6 =
* Certificate file support in the installer

= 1.2.5 =
* Fixed issue where order field showed "Extra: ()" when no extra was selected

= 1.2.4 =
* Fixed a bug where the reloaded user details form showed undefined as shipping prices and did not reselect the appropriate shipping option

= 1.2.3 =
* Added option for Commercial or Counter rates in Canada Post

= 1.2.2 =
* Adjusted Canada Post prices to not apply Automation Discount of 3%
* Removed CustomerNumber from Canada Post debug XML


= 1.2.1 = 
* Added debugging options to assist in support requests

= 1.2 =
* Modifications to work with USPS module. Absolutely required for USPS to work.

= 1.1.6 =
* Added package dimension options for Canada Post users. This will address most cases of Volumetric Weight vs. Actual Weight. 

= 1.1.5 =
* Fixed ajax bug with non-logged-in users.

= 1.1.4 =
* Forcing uppercase on zipcodes.

= 1.1.3 =
* Added "grams" support to the weight converter.
* Fixed a bug when checking for eShop's weight measurement value

= 1.1.2 = 
* Fixed Admin CSS placement bug.

= 1.1.1 =
* Fixed localization bugs in Canada Post module

= 1.1 = 
* Added install logic for additional third-party modules such as USPS

= 1.0 =
* Initial release

== Upgrade Notice ==
= 2.3.1 =
Fixed a conflict with User Switching plugin. Please upgrade if you plan on using both plugins at the same time.

= 2.1.16 =
Shipping details display has been fixed on newer versions of jQuery.
