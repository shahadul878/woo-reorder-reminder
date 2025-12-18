<?php

/**
 * Plugin Name: Easy WooCommerce Re-Order Reminder
 * Plugin URI: https://github.com/shahadul878/woo-reorder-reminder
 * Description: Automatically remind customers to reorder products after a defined time period.
 * Version: 1.0.0
 * Author: H M Shahadul Islam
 * Author URI: https://github.com/shahadul878
 * Text Domain: woo-reorder-reminder
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WRR_VERSION', '1.0.0');
define('WRR_FILE', __FILE__);
define('WRR_PATH', plugin_dir_path(__FILE__));
define('WRR_URL', plugin_dir_url(__FILE__));
define('WRR_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	add_action('admin_notices', function () {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e('Easy WooCommerce Re-Order Reminder requires WooCommerce to be installed and active.', 'woo-reorder-reminder'); ?></p>
		</div>
		<?php
	});
	return;
}

// Load the main plugin class
require_once WRR_PATH . 'includes/class-wrr-plugin.php';

/**
 * Initialize the plugin
 */
function wrr_init()
{
	return WRR_Plugin::instance();
}

add_action('plugins_loaded', 'wrr_init');

