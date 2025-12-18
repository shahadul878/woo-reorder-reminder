<?php

/**
 * Settings Page Class
 *
 * @package WRR
 */

defined('ABSPATH') || exit;

if (! class_exists('WC_Settings_Page')) {
	return;
}

/**
 * WRR_Settings_Page Class
 */
class WRR_Settings_Page extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct()
    {
		$this->id    = 'wrr_settings';
		$this->label = __('Re-Order Reminder', 'easy-reorder-reminder');

		parent::__construct();

		// Handle test email AJAX
		add_action('wp_ajax_wrr_send_test_email', array( $this, 'send_test_email_ajax' ));
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings()
    {
		$settings = array(
			array(
				'title' => __('Re-Order Reminder Settings', 'easy-reorder-reminder'),
				'type'  => 'title',
				'desc'  => __('Configure automatic reorder reminders for your customers.', 'easy-reorder-reminder'),
				'id'    => 'wrr_settings_title',
			),
			array(
				'title'    => __('Enable Reminder', 'easy-reorder-reminder'),
				'desc'     => __('Enable reorder reminders', 'easy-reorder-reminder'),
				'id'       => 'wrr_enable_reminder',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'desc_tip' => __('Uncheck to disable all reorder reminders.', 'easy-reorder-reminder'),
			),
			array(
				'title'             => __('Reminder Days', 'easy-reorder-reminder'),
				'desc'              => __('Number of days after order completion to send reminder', 'easy-reorder-reminder'),
				'id'                => 'wrr_reminder_days',
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
				),
				'default'           => '30',
				'desc_tip'          => __('This can be overridden per product.', 'easy-reorder-reminder'),
			),
			array(
				'title'    => __('Test Reminder Email', 'easy-reorder-reminder'),
				'desc'     => __('Send a test email to verify email settings', 'easy-reorder-reminder'),
				'id'       => 'wrr_test_email',
				'type'     => 'wrr_test_email',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wrr_settings_end',
			),
		);

		return apply_filters('wrr_settings', $settings);
	}

	/**
	 * Output custom field type
	 *
	 * @param array $value Field value.
	 */
	public function output_wrr_test_email_field($value)
    {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr(isset($value['id']) ? $value['id'] : ''); ?>"><?php echo esc_html(isset($value['title']) ? $value['title'] : ''); ?></label>
			</th>
			<td class="forminp">
				<input
					type="email"
					id="wrr_test_email_address"
					name="wrr_test_email_address"
					placeholder="<?php esc_attr_e('Enter email address', 'easy-reorder-reminder'); ?>"
					style="width: 300px;"
				/>
				<button type="button" class="button" id="wrr_send_test_email">
					<?php esc_html_e('Send Test Email', 'easy-reorder-reminder'); ?>
				</button>
				<?php if (isset($value['desc']) && !empty($value['desc'])) : ?>
					<p class="description"><?php echo esc_html($value['desc']); ?></p>
				<?php endif; ?>
				<div id="wrr_test_email_result" style="margin-top: 10px;"></div>
			</td>
		</tr>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#wrr_send_test_email').on('click', function() {
				var email = $('#wrr_test_email_address').val();
				if (!email) {
					alert('<?php esc_html_e('Please enter an email address', 'easy-reorder-reminder'); ?>');
					return;
				}
				$('#wrr_test_email_result').html('<span style="color: #666;"><?php esc_html_e('Sending...', 'easy-reorder-reminder'); ?></span>');
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wrr_send_test_email',
						email: email,
						nonce: '<?php echo esc_js(wp_create_nonce('wrr_test_email')); ?>'
					},
					success: function(response) {
						if (response.success) {
							$('#wrr_test_email_result').html('<span style="color: #46b450;"><?php esc_html_e('Test email sent successfully!', 'easy-reorder-reminder'); ?></span>');
						} else {
							$('#wrr_test_email_result').html('<span style="color: #dc3232;">' + (response.data || '<?php esc_html_e('Error sending email', 'easy-reorder-reminder'); ?>') + '</span>');
						}
					},
					error: function() {
						$('#wrr_test_email_result').html('<span style="color: #dc3232;"><?php esc_html_e('Error sending email', 'easy-reorder-reminder'); ?></span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Save settings
	 */
	public function save()
    {
		parent::save();
	}

	/**
	 * Send test email via AJAX
	 */
	public function send_test_email_ajax()
    {
		check_ajax_referer('wrr_test_email', 'nonce');

		if (! current_user_can('manage_woocommerce')) {
			wp_send_json_error(__('Permission denied', 'easy-reorder-reminder'));
		}

		$email = sanitize_email(wp_unslash($_POST['email']));
		if (! $email) {
			wp_send_json_error(__('Invalid email address', 'easy-reorder-reminder'));
		}

		// Create a mock order for testing
		$test_order = new WC_Order();
		$test_order->set_billing_email($email);
		$test_order->set_billing_first_name(__('Test', 'easy-reorder-reminder'));

		// Get first product
		$products = wc_get_products(array( 'limit' => 1 ));
		if (empty($products)) {
			wp_send_json_error(__('No products found. Please create at least one product first.', 'easy-reorder-reminder'));
		}

		$product = $products[0];
		$email_class = WRR_Email::instance();
		$email_class->trigger($test_order, $product->get_id());

		wp_send_json_success(__('Test email sent successfully!', 'easy-reorder-reminder'));
	}
}

