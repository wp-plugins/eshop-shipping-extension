=== Plugin Name ===
Contributors: useStrict
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VLQU2MMXKB6S2
Tags: eShop, Canada Post, USPS, United States Postal Service, UPS, United Parcel Service, Correios, Shipping Extension, Third Party Shipping, Shipping Quotes
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Replace eShop's default shipping methods with live calls to Canada Post, Correios, UPS, and USPS.

== Description ==

eShop Shipping Extension framework overrides eShop's default shipping methods, interacting directly with Canada Post, UPS, USPS, and Correios for real-time shipping rates and services.

This framework includes the Canada Post module for free. 

= Related Modules (click to buy): = 

* [USPS extension](http://usestrict.net/2012/07/usps-module-for-wordpress-eshop-shipping-extension/),
* [UPS extension](http://usestrict.net/2012/07/ups-module-for-wordpress-eshop-shipping-extension/),
* [Correios (Brazil) extension](http://usestrict.net/2012/08/modulo-correios-brasil-para-o-eshop-shipping-extension/) 

Fedex, and DHL modules will be made available soon at [UseStrict Consulting](http://usestrict.net)

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
* Other modules will be available soon at [UseStrict Consulting](http://usestrict.net)


== Screenshots ==

1. A few easy settings get you up and running in minutes.
1. The shipping options is moved to the bottom of the order form and will display Service Name, Price, and a couple of service descriptions received from Canada Post API.
1. The shipping mode selected is displayed next to the Shipping item in the order form, so you know what kind of service your client selected. 

== Changelog ==
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
No need to upgrade at this time
