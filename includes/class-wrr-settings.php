<?php
/**
 * Settings Handler Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

/**
 * WRR_Settings Class
 */
class WRR_Settings {

	/**
	 * Instance
	 *
	 * @var WRR_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WRR_Settings
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
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ) );
		add_action( 'admin_init', array( $this, 'handle_unsubscribe' ) );
		add_action( 'init', array( $this, 'handle_unsubscribe_frontend' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Add settings page
	 *
	 * @param array $settings Settings pages.
	 * @return array
	 */
	public function add_settings_page( $settings ) {
		$settings[] = include WRR_PATH . 'includes/class-wrr-settings-page.php';
		return $settings;
	}

	/**
	 * Add product fields
	 */
	public function add_product_fields() {
		global $post;

		$enable = get_post_meta( $post->ID, '_wrr_enable', true );
		$days   = get_post_meta( $post->ID, '_wrr_reminder_days', true );

		echo '<div class="options_group">';
		echo '<h3>' . esc_html__( 'Re-Order Reminder', 'woo-reorder-reminder' ) . '</h3>';

		woocommerce_wp_checkbox(
			array(
				'id'          => '_wrr_enable',
				'label'       => __( 'Enable reminder', 'woo-reorder-reminder' ),
				'description' => __( 'Enable reorder reminders for this product', 'woo-reorder-reminder' ),
				'value'       => $enable ? $enable : 'yes',
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_wrr_reminder_days',
				'label'             => __( 'Reminder days', 'woo-reorder-reminder' ),
				'description'       => __( 'Days after order completion to send reminder. Leave empty to use global setting.', 'woo-reorder-reminder' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
				),
				'value'             => $days ? $days : '',
			)
		);

		echo '</div>';
	}

	/**
	 * Save product fields
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_product_fields( $post_id ) {
		$enable = isset( $_POST['_wrr_enable'] ) ? 'yes' : 'no';
		$days   = isset( $_POST['_wrr_reminder_days'] ) ? absint( $_POST['_wrr_reminder_days'] ) : '';

		update_post_meta( $post_id, '_wrr_enable', $enable );
		if ( $days ) {
			update_post_meta( $post_id, '_wrr_reminder_days', $days );
		} else {
			delete_post_meta( $post_id, '_wrr_reminder_days' );
		}
	}

	/**
	 * Handle unsubscribe from admin
	 */
	public function handle_unsubscribe() {
		if ( ! isset( $_GET['wrr_unsubscribe'] ) || ! isset( $_GET['email'] ) || ! isset( $_GET['nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$email = sanitize_email( wp_unslash( $_GET['email'] ) );
		$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'wrr_unsubscribe_' . $email ) ) {
			wp_die( esc_html__( 'Invalid security token.', 'woo-reorder-reminder' ) );
		}

		$this->unsubscribe_email( $email );
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=wrr_settings&wrr_unsubscribed=1' ) );
		exit;
	}

	/**
	 * Handle unsubscribe from frontend
	 */
	public function handle_unsubscribe_frontend() {
		if ( ! isset( $_GET['wrr_unsubscribe'] ) || ! isset( $_GET['email'] ) || ! isset( $_GET['nonce'] ) ) {
			return;
		}

		$email = sanitize_email( wp_unslash( $_GET['email'] ) );
		$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'wrr_unsubscribe_' . $email ) ) {
			wp_die( esc_html__( 'Invalid security token.', 'woo-reorder-reminder' ) );
		}

		$this->unsubscribe_email( $email );

		// Show success message
		add_action( 'wp_footer', function() {
			?>
			<div style="position: fixed; top: 20px; right: 20px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 5px; z-index: 9999;">
				<?php esc_html_e( 'You have been unsubscribed from reorder reminders.', 'woo-reorder-reminder' ); ?>
			</div>
			<?php
		} );
	}

	/**
	 * Unsubscribe email
	 *
	 * @param string $email Email address.
	 */
	private function unsubscribe_email( $email ) {
		$unsubscribed = get_option( 'wrr_unsubscribed_emails', array() );
		if ( ! in_array( $email, $unsubscribed, true ) ) {
			$unsubscribed[] = $email;
			update_option( 'wrr_unsubscribed_emails', $unsubscribed );
		}
	}

	/**
	 * Add admin menu pages
	 */
	public function add_admin_menu() {
		// Main settings page
		add_menu_page(
			__( 'Re-Order Reminder', 'woo-reorder-reminder' ),
			__( 'Re-Order Reminder', 'woo-reorder-reminder' ),
			'manage_woocommerce',
			'wrr-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-email-alt',
			56
		);

		// Settings submenu (same as main page)
		add_submenu_page(
			'wrr-settings',
			__( 'Settings', 'woo-reorder-reminder' ),
			__( 'Settings', 'woo-reorder-reminder' ),
			'manage_woocommerce',
			'wrr-settings',
			array( $this, 'render_settings_page' )
		);

		// Logs submenu
		add_submenu_page(
			'wrr-settings',
			__( 'Re-Order Reminder Logs', 'woo-reorder-reminder' ),
			__( 'Logs', 'woo-reorder-reminder' ),
			'manage_woocommerce',
			'wrr-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Render main settings page
	 */
	public function render_settings_page() {
		include WRR_PATH . 'includes/views/admin-settings-page.php';
	}

	/**
	 * Render logs page
	 */
	public function render_logs_page() {
		$logs = WRR_Logger::get_logs();
		include WRR_PATH . 'includes/views/logs-page.php';
	}
}

