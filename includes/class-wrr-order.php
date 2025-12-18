<?php

/**
 * Order Handler Class
 *
 * @package WRR
 */

defined('ABSPATH') || exit;

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
	public static function instance()
    {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
    {
		add_action('woocommerce_order_status_completed', array( $this, 'save_order_data' ), 10, 1);
		add_action('woocommerce_thankyou', array( $this, 'display_reminder_selector' ), 20);
		add_action('wp_ajax_wrr_save_reminder_days', array( $this, 'save_reminder_days_ajax' ));
		add_action('wp_ajax_nopriv_wrr_save_reminder_days', array( $this, 'save_reminder_days_ajax' ));
	}

	/**
	 * Save order data when order is completed
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_order_data($order_id)
    {
		$order = wc_get_order($order_id);
		if (! $order) {
			return;
		}

		// Check if reminders are enabled globally
		if ('yes' !== get_option('wrr_enable_reminder', 'yes')) {
			return;
		}

		$email = $order->get_billing_email();
		if (! $email) {
			return;
		}

		// Save order data for each product
		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			if (! $product_id) {
				continue;
			}

			// Check if reminder is enabled for this product
			$product_enabled = get_post_meta($product_id, '_wrr_enable', true);
			if ('no' === $product_enabled) {
				continue;
			}

			// Store order completion timestamp for this product
			update_post_meta($order_id, '_wrr_pending_' . $product_id, time());
			update_post_meta($order_id, '_wrr_product_' . $product_id, $product_id);
			update_post_meta($order_id, '_wrr_email_' . $product_id, $email);

			// Log pending reminder
			WRR_Logger::log($order_id, $product_id, $email, 'pending');
		}
	}

	/**
	 * Display reminder day selector on thank you page
	 *
	 * @param int $order_id Order ID.
	 */
	public function display_reminder_selector($order_id)
    {
		$order = wc_get_order($order_id);
		if (! $order) {
			return;
		}

		// Do not show for failed orders
		if ($order->has_status('failed')) {
			return;
		}

		wc_get_template(
			'thankyou-reminder-selector.php',
			array( 'order' => $order ),
			'',
			WRR_PATH . 'templates/'
		);
	}

	/**
	 * Save reminder days via AJAX
	 */
	public function save_reminder_days_ajax()
    {
		// Verify nonce
		$order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
		$nonce    = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

		if (! wp_verify_nonce($nonce, 'wrr_save_reminder_days_' . $order_id)) {
			wp_send_json_error(__('Invalid security token.', 'easy-reorder-reminder'));
		}

		$order = wc_get_order($order_id);
		if (! $order) {
			wp_send_json_error(__('Order not found.', 'easy-reorder-reminder'));
		}

		// Verify user has access to this order
		// Check if user is logged in and owns the order
		$has_access = false;
		if (is_user_logged_in() && get_current_user_id() === $order->get_user_id()) {
			$has_access = true;
		} else {
			// Check order key from referrer or session
			$order_key = isset($_REQUEST['key']) ? wc_clean(wp_unslash($_REQUEST['key'])) : '';
			if ($order_key && $order->get_order_key() === $order_key) {
				$has_access = true;
			}
		}

		if (! $has_access) {
			wp_send_json_error(__('Permission denied.', 'easy-reorder-reminder'));
		}

		$reminder_days = isset($_POST['reminder_days']) ? absint($_POST['reminder_days']) : 0;

		if ($reminder_days < 1) {
			wp_send_json_error(__('Invalid reminder days.', 'easy-reorder-reminder'));
		}

		// Save customer preference
		update_post_meta($order_id, '_wrr_customer_reminder_days', $reminder_days);

		wp_send_json_success(__('Reminder preference saved successfully.', 'easy-reorder-reminder'));
	}
}
