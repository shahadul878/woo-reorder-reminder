<?php

/**
 * Logger Class
 *
 * @package WRR
 */

defined('ABSPATH') || exit;

/**
 * WRR_Logger Class
 */
class WRR_Logger {

	/**
	 * Instance
	 *
	 * @var WRR_Logger
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WRR_Logger
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
		// Table creation is handled by plugin activation hook
		// Also ensure table exists when logger is first used
		add_action('init', array( __CLASS__, 'maybe_create_table' ), 5);
	}

	/**
	 * Maybe create table if it doesn't exist
	 */
	public static function maybe_create_table()
    {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wrr_logs';

		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
			self::create_log_table();
		}
	}

	/**
	 * Create log table
	 */
	public static function create_log_table()
    {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wrr_logs';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) NOT NULL,
			product_id bigint(20) NOT NULL,
			email varchar(255) NOT NULL,
			sent_at datetime DEFAULT CURRENT_TIMESTAMP,
			status varchar(20) NOT NULL,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY product_id (product_id),
			KEY email (email),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	/**
	 * Log reminder
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $product_id Product ID.
	 * @param string $email Email address.
	 * @param string $status Status (pending, sent, failed).
	 * @return int|false Log ID or false on failure
	 */
	public static function log($order_id, $product_id, $email, $status = 'pending')
    {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wrr_logs';

		// Ensure table exists
		self::create_log_table();

		$result = $wpdb->insert(
			$table_name,
			array(
				'order_id'   => absint($order_id),
				'product_id' => absint($product_id),
				'email'      => sanitize_email($email),
				'status'     => sanitize_text_field($status),
				'sent_at'    => current_time('mysql'),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		if ($result) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get logs
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_logs($args = array())
    {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wrr_logs';

		// Ensure table exists before querying
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
			self::create_log_table();
		}

		$defaults = array(
			'limit'  => 50,
			'offset' => 0,
			'status' => '',
			'order'  => 'DESC',
		);

		$args = wp_parse_args($args, $defaults);

		$where = '1=1';

		if (! empty($args['status'])) {
			$where .= $wpdb->prepare(' AND status = %s', $args['status']);
		}

		$limit  = absint($args['limit']);
		$offset = absint($args['offset']);
		$order  = 'DESC' === strtoupper($args['order']) ? 'DESC' : 'ASC';

		$query = "SELECT * FROM $table_name WHERE $where ORDER BY sent_at $order LIMIT $limit OFFSET $offset";

		return $wpdb->get_results($query, ARRAY_A);
	}

	/**
	 * Get log count
	 *
	 * @param string $status Status filter.
	 * @return int
	 */
	public static function get_log_count($status = '')
    {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wrr_logs';

		// Ensure table exists before querying
		if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
			self::create_log_table();
		}

		$where = '1=1';

		if (! empty($status)) {
			$where .= $wpdb->prepare(' AND status = %s', $status);
		}

		$query = "SELECT COUNT(*) FROM $table_name WHERE $where";

		return (int) $wpdb->get_var($query);
	}

	/**
	 * Update log status
	 *
	 * @param int    $log_id Log ID.
	 * @param string $status New status.
	 * @return bool
	 */
	public static function update_status($log_id, $status)
    {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wrr_logs';

		$result = $wpdb->update(
			$table_name,
			array( 'status' => sanitize_text_field($status) ),
			array( 'id' => absint($log_id) ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}
}
