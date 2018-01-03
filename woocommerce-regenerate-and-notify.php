<?php

/**
 *
 * @link              https://bdaley.com
 * @since             1.0.0
 * @package           Wc_Regenerate_And_Notify
 *
 * @wordpress-plugin
 * Plugin Name:       Woocommerce Regenerate & Notify
 * Plugin URI:        https://bdaley.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Brian Daley
 * Author URI:        https://bdaley.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-regenerate-and-notify
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


add_filter('woocommerce_order_actions', 'wc_regenerate_and_notify_custom_order_action');
function wc_regenerate_and_notify_custom_order_action( $actions ) {

	// Remove the woocommerce option to regenerate to avoid confusion
	unset($actions['regenerate_download_permissions']);

	// Add our new action (executed below)
	$actions['wc_regenerate_and_notify'] = __('Regenerate permissions & send link to customer', 'wc-regenerate-and-notify');
	return $actions;
}

/**
 * We need to do 4 things:
 * 1) Reset the download counter
 * 2) Reset the download expiration date
 * 3) Send order confirmation (again) with the download links
 * 4) Add a note for recording purposes. *
 */
add_action('woocommerce_order_action_wc_regenerate_and_notify', function($order){

	// 1)  Reset the download counter
	$data_store = WC_Data_Store::load( 'customer-download' );
	$data_store->delete_by_order_id( $order->ID );
	wc_downloadable_product_permissions( $order->ID, true );

	// 2) Retrieve Download and remove the access expiration. (Our poor customer has had enough trouble)
	// *Could optionally reset to product default (x days or whatever)
	$downloads = $data_store->get_downloads(array('order_id' => $order->ID) );
	if(is_array($downloads)){
		foreach($downloads as $download){
			$download->set_access_expires(null);
			$download->save();
		}
	}

	// 3 & 4) Yay! Send an updated invoice to the customer with this message:
	$order->add_order_note( __( "We've reset your download permissions. Please try downloading again.", 'woocommerce' ), true, true );
});
