<?php

/**
 * Main Plugin Class
 *
 * @package WRR
 */

defined('ABSPATH') || exit;

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
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes()
    {
		require_once WRR_PATH . 'includes/class-wrr-cron.php';
		require_once WRR_PATH . 'includes/class-wrr-order.php';
		// Email class will be loaded after WooCommerce is available
		require_once WRR_PATH . 'includes/class-wrr-settings.php';
		require_once WRR_PATH . 'includes/class-wrr-logger.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks()
    {
		register_activation_hook(WRR_FILE, array( 'WRR_Cron', 'activate' ));
		register_activation_hook(WRR_FILE, array( 'WRR_Logger', 'create_log_table' ));
		register_deactivation_hook(WRR_FILE, array( 'WRR_Cron', 'deactivate' ));

		// Load text domain
		add_action('init', array( $this, 'load_textdomain' ));

		// Initialize components
		add_action('init', array( $this, 'init_components' ));

		// Register email filter early - hook it directly so it's available when WooCommerce initializes emails
		// The filter will load the class when it's called
		add_filter('woocommerce_email_classes', array( $this, 'add_email_class' ), 20);
	}

	/**
	 * Load plugin textdomain
	 * Note: For WordPress.org plugins, translations are loaded automatically since WordPress 4.6.
	 * This method is kept for backward compatibility but does not call load_plugin_textdomain()
	 * as it's discouraged for WordPress.org plugins.
	 */
	public function load_textdomain()
    {
		// WordPress.org automatically loads translations, so load_plugin_textdomain() is not needed.
		// Translations are loaded automatically from the /languages directory.
		// This method is kept for backward compatibility but remains empty.
	}

	/**
	 * Initialize plugin components
	 */
	public function init_components()
    {
		WRR_Cron::instance();
		WRR_Order::instance();
		WRR_Settings::instance();
		WRR_Logger::instance();
	}

	/**
	 * Add email class to WooCommerce emails
	 * This filter is called when WooCommerce initializes its email system
	 *
	 * @param array $emails Email classes.
	 * @return array
	 */
	public function add_email_class($emails)
    {
		// Ensure WC_Email exists - if not, we can't extend it
		if (! class_exists('WC_Email')) {
			return $emails;
		}

		// Load email class if not already loaded
		if (! class_exists('WRR_Email')) {
			require_once WRR_PATH . 'includes/class-wrr-email.php';
		}

		// If class still doesn't exist, something went wrong
		if (! class_exists('WRR_Email')) {
			return $emails;
		}

		try {
			// Get email instance
			$email_instance = WRR_Email::instance();

			if (! $email_instance || ! is_a($email_instance, 'WC_Email')) {
				if (defined('WP_DEBUG') && WP_DEBUG) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
					error_log('WRR Debug: Email instance invalid or not WC_Email');
				}
				return $emails;
			}

			// WooCommerce uses email ID as the key for email settings
			// Only register once with the email ID to avoid duplicates
			// Check if already registered to prevent duplicates
			if (! isset($emails[ $email_instance->id ])) {
				$emails[ $email_instance->id ] = $email_instance;
				if (defined('WP_DEBUG') && WP_DEBUG) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
					error_log('WRR Debug: Email registered with ID: ' . $email_instance->id);
				}
			}
		} catch (Exception $e) {
			// Log error but don't break
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Error logging for debugging
				error_log('WRR Email registration error: ' . $e->getMessage());
			}
		}

		return $emails;
	}
}
