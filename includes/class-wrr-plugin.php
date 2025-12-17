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

		// Register email class with WooCommerce after WooCommerce is loaded
		add_action( 'woocommerce_loaded', array( $this, 'register_email_class' ) );
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
	 * Register email class with WooCommerce
	 */
	public function register_email_class() {
		// Ensure WC_Email exists before registering
		if ( ! class_exists( 'WC_Email' ) ) {
			return;
		}

		// Re-include email class if it wasn't loaded (in case WC_Email wasn't available when first included)
		if ( ! class_exists( 'WRR_Email' ) ) {
			require_once WRR_PATH . 'includes/class-wrr-email.php';
		}

		// Now register the filter - hook it directly here
		if ( class_exists( 'WRR_Email' ) ) {
			add_filter( 'woocommerce_email_classes', array( $this, 'add_email_class' ), 20 );
		}
	}

	/**
	 * Add email class to WooCommerce emails
	 *
	 * @param array $emails Email classes.
	 * @return array
	 */
	public function add_email_class( $emails ) {
		// Double check classes exist
		if ( ! class_exists( 'WRR_Email' ) ) {
			return $emails;
		}

		try {
			// Get email instance
			$email_instance = WRR_Email::instance();
			
			if ( ! $email_instance || ! is_a( $email_instance, 'WC_Email' ) ) {
				return $emails;
			}
			
			// WooCommerce uses the email ID as the key for sections
			// Register with email ID as primary key (this is what WooCommerce expects)
			$emails[ $email_instance->id ] = $email_instance;
		} catch ( Exception $e ) {
			// Log error but don't break
			error_log( 'WRR Email registration error: ' . $e->getMessage() );
		}
		
		return $emails;
	}
}

