<?php
/*
Plugin Name: Electroneum - WooCommerce Gateway
Plugin URI: http://electroneum.com
Description: Extends WooCommerce by Adding the Electroneum Gateway
Version: 2.0
Author: SerHack
Author URI: http://monerointegrations.com

Modified March 2018 by NirvanaLabs.co to allow WooCommerce to accept Electroneum.com (ETN) Cryptocurrency
Author URI: http://nirvanalabs.co
*/

// This code isn't for Dark Net Markets, please report them to Authority!
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'electroneum_init', 0);
function electroneum_init()
{
    /* If the class doesn't exist (== WooCommerce isn't installed), return NULL */
    if (!class_exists('WC_Payment_Gateway')) return;


    /* If we made it this far, then include our Gateway Class */
    include_once('include/electroneum_payments.php');
    require_once('library.php');

    // Lets add it too WooCommerce
    add_filter('woocommerce_payment_gateways', 'electroneum_gateway');
    function electroneum_gateway($methods)
    {
        $methods[] = 'Electroneum_Gateway';
        return $methods;
    }
}

/*
 * Add custom link
 * The url will be http://yourworpress/wp-admin/admin.php?=wc-settings&tab=checkout
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'electroneum_payment');
function electroneum_payment($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'electroneum_payment') . '</a>',
    );

    return array_merge($plugin_links, $links);
}

add_action('admin_menu', 'electroneum_create_menu');
function electroneum_create_menu()
{
    add_menu_page(
        __('Electroneum', 'textdomain'),
        'Electroneum',
        'manage_options',
        'admin.php?page=wc-settings&tab=checkout&section=electroneum_gateway',
        '',
        plugins_url('electroneum/assets/electroneum.png'),
        56 // Position on menu, woocommerce has 55.5, products has 55.6

    );
}
