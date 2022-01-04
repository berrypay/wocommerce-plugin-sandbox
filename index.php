<?php
/**
 * Plugin Name: BerryPay
 * Plugin URI: https://berrypay.com
 * Description: Enable online payments using credit or debit cards and online banking.
 * Version: 2.0.1
 * Author: BerryPay
 * Author URI: https://berrypay.com
 * WC requires at least: 2.6.0
 * WC tested up to: 4.4.1
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

# Include BerryPay Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'berrypay_init', 0 );

function berrypay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	include_once( 'src/berrypay.php' );

	add_filter( 'woocommerce_payment_gateways', 'add_berrypay_to_woocommerce' );
	function add_berrypay_to_woocommerce( $methods ) {
		$methods[] = 'BerryPay';

		return $methods;
	}
}

# Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'berrypay_links' );

function berrypay_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=berrypay' ) . '">' . __( 'Settings', 'berrypay' ) . '</a>',
	);

	# Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}

add_action( 'init', 'berrypay_check_response', 15 );

function berrypay_check_response() {
	# If the parent WC_Payment_Gateway class doesn't exist it means WooCommerce is not installed on the site, so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	include_once( 'src/berrypay.php' );

	$berrypay = new berrypay();
	$berrypay->check_berrypay_response();
}

function berrypay_hash_error_msg( $content ) {
	return '<div class="woocommerce-error">The data that we received is invalid. Thank you.</div>' . $content;
}

function berrypay_payment_declined_msg( $content ) {
	return '<div class="woocommerce-error">The payment was declined. Please check with your bank. Thank you.</div>' . $content;
}

function berrypay_success_msg( $content ) {
	return '<div class="woocommerce-info">The payment was successful. Thank you.</div>' . $content;
}
