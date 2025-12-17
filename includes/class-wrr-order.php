<?php
/**
 * Order Handler Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

/**
 * WRR_Order Class
 */
class WRR_Order {

	/**
	 * Instance
	 *
	 * @var WRR_Order
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WRR_Order
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'save_order_data' ), 10, 1 );
	}

	/**
	 * Save order data when order is completed
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_order_data( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Check if reminders are enabled globally
		if ( 'yes' !== get_option( 'wrr_enable_reminder', 'yes' ) ) {
			return;
		}

		$email = $order->get_billing_email();
		if ( ! $email ) {
			return;
		}

		// Save order data for each product
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( ! $product_id ) {
				continue;
			}

			// Check if reminder is enabled for this product
			$product_enabled = get_post_meta( $product_id, '_wrr_enable', true );
			if ( 'no' === $product_enabled ) {
				continue;
			}

			// Store order completion timestamp for this product
			update_post_meta( $order_id, '_wrr_pending_' . $product_id, time() );
			update_post_meta( $order_id, '_wrr_product_' . $product_id, $product_id );
			update_post_meta( $order_id, '_wrr_email_' . $product_id, $email );

			// Log pending reminder
			WRR_Logger::log( $order_id, $product_id, $email, 'pending' );
		}
	}
}

