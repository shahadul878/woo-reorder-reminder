<?php
/**
 * Main Plugin Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

/**
 * WRR_Plugin Class
 */
class WRR_Plugin {

	/**
	 * Plugin instance
	 *
	 * @var WRR_Plugin
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return WRR_Plugin
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
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once WRR_PATH . 'includes/class-wrr-cron.php';
		require_once WRR_PATH . 'includes/class-wrr-order.php';
		require_once WRR_PATH . 'includes/class-wrr-email.php';
		require_once WRR_PATH . 'includes/class-wrr-settings.php';
		require_once WRR_PATH . 'includes/class-wrr-logger.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		register_activation_hook( WRR_FILE, array( 'WRR_Cron', 'activate' ) );
		register_deactivation_hook( WRR_FILE, array( 'WRR_Cron', 'deactivate' ) );

		// Load text domain
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Initialize components
		add_action( 'init', array( $this, 'init_components' ) );

		// Register email class with WooCommerce (after WooCommerce emails are loaded)
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_class' ) );
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'woo-reorder-reminder', false, dirname( WRR_BASENAME ) . '/languages' );
	}

	/**
	 * Initialize plugin components
	 */
	public function init_components() {
		WRR_Cron::instance();
		WRR_Order::instance();
		WRR_Settings::instance();
		WRR_Logger::instance();
	}

	/**
	 * Add email class to WooCommerce emails
	 *
	 * @param array $emails Email classes.
	 * @return array
	 */
	public function add_email_class( $emails ) {
		$emails['WRR_Email'] = WRR_Email::instance();
		return $emails;
	}
}

