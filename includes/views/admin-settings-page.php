<?php

/**
 * Admin Settings Page View
 *
 * @package WRR
 */

defined('ABSPATH') || exit;

// Get current settings
$enable_reminder = get_option('wrr_enable_reminder', 'yes');
$reminder_days   = get_option('wrr_reminder_days', 30);
$unsubscribed    = get_option('wrr_unsubscribed_emails', array());

// Handle form submission
$message = '';
$message_type = '';

if (isset($_POST['wrr_save_settings']) && check_admin_referer('wrr_settings', 'wrr_settings_nonce')) {
	$enable_reminder = isset($_POST['wrr_enable_reminder']) ? 'yes' : 'no';
	$reminder_days   = isset($_POST['wrr_reminder_days']) ? absint($_POST['wrr_reminder_days']) : 30;

	update_option('wrr_enable_reminder', $enable_reminder);
	update_option('wrr_reminder_days', $reminder_days);

	$message = __('Settings saved successfully!', 'woo-reorder-reminder');
	$message_type = 'success';
}

// Get statistics
$sent_count    = WRR_Logger::get_log_count('sent');
$pending_count = WRR_Logger::get_log_count('pending');
$failed_count  = WRR_Logger::get_log_count('failed');
$total_count   = WRR_Logger::get_log_count();
?>

<div class="wrap wrr-settings-page">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

	<?php if ($message) : ?>
		<div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
			<p><?php echo esc_html($message); ?></p>
		</div>
	<?php endif; ?>

	<div class="wrr-settings-container" style="display: flex; gap: 20px; margin-top: 20px;">
		<div class="wrr-settings-main" style="flex: 2;">
			<form method="post" action="">
				<?php wp_nonce_field('wrr_settings', 'wrr_settings_nonce'); ?>

				<div class="wrr-settings-section" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
					<h2 style="margin-top: 0;"><?php esc_html_e('General Settings', 'woo-reorder-reminder'); ?></h2>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="wrr_enable_reminder"><?php esc_html_e('Enable Reminder System', 'woo-reorder-reminder'); ?></label>
								</th>
								<td>
									<label>
										<input type="checkbox" name="wrr_enable_reminder" id="wrr_enable_reminder" value="yes" <?php checked($enable_reminder, 'yes'); ?> />
										<?php esc_html_e('Enable automatic reorder reminders', 'woo-reorder-reminder'); ?>
									</label>
									<p class="description"><?php esc_html_e('Uncheck to disable all reminder emails globally.', 'woo-reorder-reminder'); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="wrr_reminder_days"><?php esc_html_e('Default Reminder Days', 'woo-reorder-reminder'); ?></label>
								</th>
								<td>
									<input type="number" name="wrr_reminder_days" id="wrr_reminder_days" value="<?php echo esc_attr($reminder_days); ?>" min="1" step="1" class="small-text" />
									<p class="description"><?php esc_html_e('Number of days after order completion to send reminder. This can be overridden per product or by customer preference.', 'woo-reorder-reminder'); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="wrr-settings-section" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
					<h2 style="margin-top: 0;"><?php esc_html_e('Email Settings', 'woo-reorder-reminder'); ?></h2>
					<p>
						<?php esc_html_e('Email templates and settings are managed in', 'woo-reorder-reminder'); ?>
						<a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=email&section=wrr_reorder_reminder')); ?>">
							<?php esc_html_e('WooCommerce → Settings → Emails → Re-Order Reminder', 'woo-reorder-reminder'); ?>
						</a>
					</p>
					<p>
						<button type="button" class="button" id="wrr_send_test_email_btn">
							<?php esc_html_e('Send Test Email', 'woo-reorder-reminder'); ?>
						</button>
						<span id="wrr_test_email_result" style="margin-left: 10px;"></span>
					</p>
					<div id="wrr_test_email_form" style="margin-top: 15px; display: none;">
						<input type="email" id="wrr_test_email_address" placeholder="<?php esc_attr_e('Enter email address', 'woo-reorder-reminder'); ?>" style="width: 300px; padding: 5px;" />
						<button type="button" class="button button-primary" id="wrr_send_test_email_submit">
							<?php esc_html_e('Send', 'woo-reorder-reminder'); ?>
						</button>
					</div>
				</div>

				<div class="wrr-settings-section" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
					<h2 style="margin-top: 0;"><?php esc_html_e('Unsubscribed Emails', 'woo-reorder-reminder'); ?></h2>
					<?php if (! empty($unsubscribed)) : ?>
						<p><?php
						/* translators: %d: number of unsubscribed emails */
						printf(esc_html__('Total unsubscribed: %d', 'woo-reorder-reminder'), count($unsubscribed));
						?></p>
						<textarea readonly style="width: 100%; height: 150px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea(implode("\n", $unsubscribed)); ?></textarea>
						<p class="description"><?php esc_html_e('These email addresses will not receive reminder emails.', 'woo-reorder-reminder'); ?></p>
					<?php else : ?>
						<p><?php esc_html_e('No unsubscribed emails.', 'woo-reorder-reminder'); ?></p>
					<?php endif; ?>
				</div>

				<p class="submit">
					<?php submit_button(__('Save Settings', 'woo-reorder-reminder'), 'primary', 'wrr_save_settings', false); ?>
				</p>
			</form>
		</div>

		<div class="wrr-settings-sidebar" style="flex: 1;">
			<div class="wrr-stats-box" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
				<h3 style="margin-top: 0;"><?php esc_html_e('Statistics', 'woo-reorder-reminder'); ?></h3>
				<ul style="list-style: none; padding: 0; margin: 0;">
					<li style="padding: 10px 0; border-bottom: 1px solid #eee;">
						<strong><?php esc_html_e('Total Logs:', 'woo-reorder-reminder'); ?></strong>
						<span style="float: right;"><?php echo esc_html($total_count); ?></span>
					</li>
					<li style="padding: 10px 0; border-bottom: 1px solid #eee;">
						<strong style="color: #46b450;"><?php esc_html_e('Sent:', 'woo-reorder-reminder'); ?></strong>
						<span style="float: right;"><?php echo esc_html($sent_count); ?></span>
					</li>
					<li style="padding: 10px 0; border-bottom: 1px solid #eee;">
						<strong style="color: #f0b849;"><?php esc_html_e('Pending:', 'woo-reorder-reminder'); ?></strong>
						<span style="float: right;"><?php echo esc_html($pending_count); ?></span>
					</li>
					<li style="padding: 10px 0;">
						<strong style="color: #dc3232;"><?php esc_html_e('Failed:', 'woo-reorder-reminder'); ?></strong>
						<span style="float: right;"><?php echo esc_html($failed_count); ?></span>
					</li>
				</ul>
				<p style="margin-top: 15px;">
					<a href="<?php echo esc_url(admin_url('admin.php?page=wrr-logs')); ?>" class="button">
						<?php esc_html_e('View All Logs', 'woo-reorder-reminder'); ?>
					</a>
				</p>
			</div>

			<div class="wrr-info-box" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
				<h3 style="margin-top: 0;"><?php esc_html_e('Quick Links', 'woo-reorder-reminder'); ?></h3>
				<ul style="list-style: disc; padding-left: 20px;">
					<li>
						<a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=wrr_settings')); ?>">
							<?php esc_html_e('WooCommerce Settings', 'woo-reorder-reminder'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url(admin_url('admin.php?page=wrr-logs')); ?>">
							<?php esc_html_e('View Logs', 'woo-reorder-reminder'); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=email&section=wrr_reorder_reminder')); ?>">
							<?php esc_html_e('Email Template Settings', 'woo-reorder-reminder'); ?>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#wrr_send_test_email_btn').on('click', function() {
		$('#wrr_test_email_form').toggle();
		$('#wrr_test_email_address').focus();
	});

	$('#wrr_send_test_email_submit').on('click', function() {
		var email = $('#wrr_test_email_address').val();
		if (!email) {
			alert('<?php esc_html_e('Please enter an email address', 'woo-reorder-reminder'); ?>');
			return;
		}

		var $button = $(this);
		var $result = $('#wrr_test_email_result');
		var originalText = $button.text();

		$button.prop('disabled', true).text('<?php esc_html_e('Sending...', 'woo-reorder-reminder'); ?>');
		$result.html('');

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
					$result.html('<span style="color: #46b450;"><?php esc_html_e('✓ Test email sent successfully!', 'woo-reorder-reminder'); ?></span>');
				} else {
					$result.html('<span style="color: #dc3232;">' + (response.data || '<?php esc_html_e('Error sending email', 'woo-reorder-reminder'); ?>') + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: #dc3232;"><?php esc_html_e('Error sending email', 'woo-reorder-reminder'); ?></span>');
			},
			complete: function() {
				$button.prop('disabled', false).text(originalText);
			}
		});
	});
});
</script>

