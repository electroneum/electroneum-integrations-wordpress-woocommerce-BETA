=== Electroneum WooCommerce Extension ===
Contributors: serhack
Modification : Nirvana Labs to support with Electroneum
Website link: http://nirvanalabs.co
Tags: electroneum, woocommerce, integration, payment, merchant, cryptocurrency, accept electroneum, electroneum woocommerce
Requires at least: 4.0
Tested up to: 4.8
Stable tag: BETA master
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

IMPORTANT: THIS IS CURRENTLY IN BETA - DO NOT USE IN A PRODUCTION ENVIRONMENT.
We are porting this serhack's code to work with Electroneum and we'd made several improvements. It will be ready for release when we move from a cookie based system to a database system, which is in development.
This code is to demonstrate how easy it is for vendors to confirm ETN payments without running a full node, using the Electroneum block explorer (http://blockexplorer.electroneum.com)


Electroneum WooCommerce Extension is a Wordpress plugin that allows to accept electroneum (ETN) at WooCommerce-powered online stores.

== Description ==

An extension to WooCommerce for accepting Electroneum as payment in your store.

= Benefits =

* Accept payment directly into your personal Electroneum wallet.
* Accept payment in electroneum for physical and digital downloadable products.
* Add electroneum payments option to your existing online store with alternative main currency.
* Flexible exchange rate calculations fully managed via administrative settings.
* Zero fees and no commissions for electroneum payments processing from any third party.
* Automatic conversion to Electroneum via realtime exchange rate feed and calculations.
* Ability to set exchange rate calculation multiplier to compensate for any possible losses due to bank conversions and funds transfer fees.

== Installation ==

1. Install "Electroneum WooCommerce extension" wordpress plugin just like any other Wordpress plugin.
2. Activate
3. Setup your electroneum-wallet-rpc and create offline / CLI wallet with view key
4. Add your electroneum-wallet-rpc host address and Electroneum address in the settings panel
5. Click “Enable this payment gateway”
6. Enjoy it!

== Remove plugin ==

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Electroneum Payment Box
2. Electroneum Options

== Changelog ==

= 1.0 =
* First version ! Yay!

= 2.0 =
* Bug fixes

== Upgrade Notice ==

soon

== Frequently Asked Questions ==

* What is Electroneum ?
Electroneum is completely private, cryptographically secure, digital payment solution designed for mass adoption. See https://electroneum.com for more information

* What is a Electroneum wallet?
A Electroneum wallet is a piece of software that allows you to store your funds and interact with the Electroneum network. You can get a Electroneum wallet from https://downloads.electroneum.com/

* What is electroneum-wallet-rpc ?
The electroneum-wallet-rpc is an RPC server that will allow this plugin to communicate with the Electroneum network. You can download it from https://github.com/electroneum/electroneum-pool#1-downloading--installing with the command-line tools.

* Why do I see `[ERROR] Failed to connect to electroneum-wallet-rpc at localhost port 26969
Syntax error: Invalid response data structure: Request id: 1 is different from Response id: ` ?
This is most likely because this plugin can not reach your electroneum-wallet-rpc. Make sure that you have supplied the correct host IP and port to the plugin in their fields. If your electroneum-wallet-rpc is on a different server than your wordpress site, make sure that the appropriate port is open with port forwarding enabled.
