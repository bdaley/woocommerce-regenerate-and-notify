<?php
/**
 * WooCommerce Regenerate & Notify
 *
 * @package     Woo_Regenerate_And_Notify
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Regenerate & Notify
 * Plugin URI:        https://github.com/bdaley/woocommerce-regenerate-and-notify
 * Description:       A WooCommerce extension that regenerates download permissions (including expiration date) and notifies the customer.
 * Version:           1.0.0
 * Author:            Brian Daley
 * Author URI:        https://bdaley.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       woo-regenerate-and-notify
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Composer dependencies.
require_once dirname( __FILE__ ) . '/vendor/autoload.php';

/**
 * Adds the new action to the order action meta box.
 *
 * @param  array $actions List of actions in the metabox.
 * @return array $actions Amended list of actions.
 */
function woo_regenerate_and_notify_custom_order_action( $actions ) {

	// Remove the woocommerce option to regenerate to avoid confusion.
	unset( $actions['regenerate_download_permissions'] );

	// Add our new action (executed below).
	$actions['woo_regenerate_and_notify'] = __( 'Regenerate permissions & send link to customer', 'woo-regenerate-and-notify' );
	return $actions;
}

// Register the function above.
add_filter( 'woocommerce_order_actions', 'woo_regenerate_and_notify_custom_order_action' );



/**
 * Executes regenerattion, expiry reset, order note, and notification to user.
 *
 * @param  object $order The order WC_Order object.
 * @return void
 */
function woo_regenerate_and_notify( $order ) {

	// First, we reset the download counter to its original value.
	$data_store = WC_Data_Store::load( 'customer-download' );
	$data_store->delete_by_order_id( $order->ID );
	wc_downloadable_product_permissions( $order->ID, true );

	// Retrieve Download(s) and completely remove the access expiration.
	// At some point, we may want to restore the default, but we'll turn off the time limit for now.
	$downloads = $data_store->get_downloads( array( 'order_id' => $order->ID ) );
	if ( is_array( $downloads ) ) {
		foreach ( $downloads as $download ) {
			$download->set_access_expires( null );
			$download->save();
		}
	}

	// All restored. Store note and resend an updated invoice to the customer.
	$order->add_order_note(
		__( "We've reset your download permissions. Please try downloading again.", 'woo-regenerate-and-notify' ),
		true,
		true
	);
}

// Register the function above.
add_action( 'woocommerce_order_action_woo_regenerate_and_notify', 'woo_regenerate_and_notify' );
