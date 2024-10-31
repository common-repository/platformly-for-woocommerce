=== Platform.ly for WooCommerce ===

Contributors: platformlycom
Tags: CRM, ecommerce, event tracking, abandoned cart, cart abandonment, sales reporting, platform.ly, platformly
Requires at least: 4.9.13
Tested up to: 6.6
Stable tag: trunk
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily connect WooCommerce to your Platformly CRM, set up abandoned cart campaigns and access detailed customer reporting including lifetime value and more.

== Description ==

<strong>Transactions</strong>

You can select what transactions you want to include in your Platformly sales reports:

* All transactions
* Successful purchases
* Failed purchases
* Refunded orders
* Cancelled orders

<strong>Contacts Settings</strong>

<strong>1. New Users</strong>

Every new user is imported in Platformly immediately after registration. You can choose which segment to add them to and/or apply tags.

<strong>2. GDPR</strong>

If you want to be GDPR-compliant, you can request users to tick a box, to confirm subscription. This is optional.

If you decide to use it, the tick box will be shown on your checkout page.

If the user ticks the box, meaning he or she agrees to receive marketing communications from you, he or she will be added to the segment of your choice.

If the user doesn't tick the box, meaning he or she does not agree to receive marketing communications from you, he or she won't be added to that segment, so you know you cannot send marketing emails to this person.

You may apply tags instead of adding users to different segments.

<strong>3. Purchase Actions</strong>

You can add to a segment or apply a tag when:

* A purchase is made
* An order is refunded
* A transaction fails
* An order is canceled

<strong>Events Settings</strong>

You can track user actions.

Here is a list of events you can track:

* View product: when users visit a product page

* Add to cart: when users add a product to their cart

* View cart: when users move to the cart page

* Checkout: when users move to the checkout page

* Place order: when users place an order

* Abandoned cart: when users add a product to their cart but don't check out and leave the products in their cart for more than X minutes

* Abandoned cart recovered: when users finish checkout after receiving an abandoned cart email.

Event data of registered users will instant sent to Platformly.

For unregistered users, data will be stored in the user's session and will be sent to Platformly after the user registers or makes an order.

<strong>Product Page</strong>

You can also choose to add users to a specific segment, or apply them a specific tag on a product level (for a specific product). To do so, simply open the product page in WordPress, scroll down to the Product Data section and open the Platform.ly tab.

You can add users to a segment, or apply them a tag:

* When the product is added to cart
* When the product is purchased
* When the order is refunded
* When the order has failed
* When the order is cancelled

Please note that you will also be able to insert product information to your email in Plaformly (including the list of products added to cart), as well as set up automations using the events, segments and tags previously created, access detailed sales reports and more.

For more information, as well as updates and references for further reading, please visit the <a target="_blank" href="https://www.platform.ly/blog/" rel="noopener">Platform.ly blog</a>.

== Installation ==

To use the Platform.ly for WooCommerce plugin, please follow these steps:

1. Download the plugin, install it and activate it.
2. Click on 'Platform.ly for WooCommerce' in the left menu. This will open the settings page.
3. Enter your API key. You can create an API key in the 'API Docs & Keys' section in the Platformly members area. Then click on the 'Connect' button.
4. Choose a Project and Payment Processor. You can create a Project and connect to a Payment Processor in the Platformly members area.
5. Choose transactions you want to track. The default value is 'All'. (Optional) 
6. Once everything is ready, tick the 'Enable Platform.ly' checkbox to activate synchronisation.

== Frequently Asked Questions ==

= Where can I find my API key? =

Your API keys are located in the 'API Keys & Docs' section in your Platformly account. Click on your profile picture in the top-right corner to make the drop-down menu appear. In the drop-down menu, click on 'API Docs & Keys'. You can create as many API keys as you want. We offer API key authentication in other services connected to your account as well.

== Screenshots ==

1. The page where you can set up your plugin. Connect it with your Platform.ly account
2. The page where you can manage tags and segments for your new users, GDPR settings, tags and segments for transactions
3. The page where you can manage your event tracking settings
4. The page where you can manage users' tags and segments on a product level

== Changelog ==

= 1.0 =
* Initial release. No previous versions are available.

= 1.1 =
* Updated abandoned cart functionality

= 1.1.1 =
* Minor fix

= 1.1.2 =
* Minor fix

= 1.1.3 =
* Minor fix

= 1.1.5 =
* Improvement: Compatibility with WP 6.6

== Upgrade Notice ==

= 1.0 =
* Initial release. No previous versions are available.

= 1.1 =
* Updated abandoned cart functionality

= 1.1.1 =
* Minor fix

= 1.1.2 =
* Minor fix

= 1.1.3 =
* Minor fix

= 1.1.4 =
* Minor fix

= 1.1.5 =
* Improvement: Compatibility with WP 6.6