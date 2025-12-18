<?php
/**
 * Thank You Page Reminder Day Selector Template
 *
 * @package WRR
 * @var WC_Order $order
 */

defined('ABSPATH') || exit;

// Check if reminders are enabled
$reminders_enabled = get_option('wrr_enable_reminder', 'yes');
if ('yes' !== $reminders_enabled) {
	// Debug: Uncomment to see why selector is not showing
	// error_log( 'WRR Debug: Reminders disabled globally' );
	return;
}

// Get available reminder day options
$default_days = absint(get_option('wrr_reminder_days', 30));
$day_options  = apply_filters('wrr_reminder_day_options', array( 15, 30, 45, 60, 90 ));

// Check if customer already selected a preference
$selected_days = get_post_meta($order->get_id(), '_wrr_customer_reminder_days', true);
$selected_days = $selected_days ? absint($selected_days) : $default_days;

// Check if order has products with reminders enabled
$has_reminder_products = false;
foreach ($order->get_items() as $item) {
	$product_id = $item->get_product_id();
	if ($product_id) {
		$product_enabled = get_post_meta($product_id, '_wrr_enable', true);
		if ('no' !== $product_enabled) {
			$has_reminder_products = true;
			break;
		}
	}
}

if (! $has_reminder_products) {
	// Debug: Uncomment to see why selector is not showing
	// error_log( 'WRR Debug: No products with reminders enabled in order #' . $order->get_id() );
	return;
}
?>

<div class="wrr-thankyou-reminder-selector" style="margin: 30px 0; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
	<h3 style="margin-top: 0;"><?php esc_html_e('When would you like a reminder to reorder?', 'woo-reorder-reminder'); ?></h3>
	<p style="margin-bottom: 15px;"><?php esc_html_e('Choose when you\'d like to receive a reminder email to reorder these products:', 'woo-reorder-reminder'); ?></p>
	
	<form id="wrr-reminder-days-form" method="post" style="margin-bottom: 0;">
		<input type="hidden" name="wrr_order_id" value="<?php echo esc_attr($order->get_id()); ?>" />
		<input type="hidden" name="wrr_nonce" value="<?php echo esc_attr(wp_create_nonce('wrr_save_reminder_days_' . $order->get_id())); ?>" />
		
		<select name="wrr_reminder_days" id="wrr_reminder_days" style="padding: 8px 12px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px; min-width: 200px;">
			<?php foreach ($day_options as $days) : ?>
				<option value="<?php echo esc_attr($days); ?>" <?php selected($selected_days, $days); ?>>
					<?php
					if (1 === $days) {
						/* translators: %d: number of days (singular) */
						printf(esc_html__('%d day', 'woo-reorder-reminder'), $days);
					} else {
						/* translators: %d: number of days (plural) */
						printf(esc_html__('%d days', 'woo-reorder-reminder'), $days);
					}
					?>
				</option>
			<?php endforeach; ?>
		</select>
		
		<button type="submit" class="button" style="margin-left: 10px; padding: 8px 20px;">
			<?php esc_html_e('Save Preference', 'woo-reorder-reminder'); ?>
		</button>
	</form>
	
	<div id="wrr-reminder-message" style="margin-top: 10px; display: none;"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#wrr-reminder-days-form').on('submit', function(e) {
		e.preventDefault();
		
		var form = $(this);
		var messageDiv = $('#wrr-reminder-message');
		var button = form.find('button[type="submit"]');
		var originalText = button.text();
		
		button.prop('disabled', true).text('<?php echo esc_js(__('Saving...', 'woo-reorder-reminder')); ?>');
		messageDiv.hide();
		
		$.ajax({
			url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
			type: 'POST',
			data: {
				action: 'wrr_save_reminder_days',
				order_id: $('input[name="wrr_order_id"]').val(),
				reminder_days: $('#wrr_reminder_days').val(),
				nonce: $('input[name="wrr_nonce"]').val(),
				key: '<?php echo esc_js($order->get_order_key()); ?>'
			},
			success: function(response) {
				if (response.success) {
					messageDiv.html('<span style="color: #46b450;"><?php echo esc_js(__('✓ Preference saved successfully!', 'woo-reorder-reminder')); ?></span>').show();
					setTimeout(function() {
						messageDiv.fadeOut();
					}, 3000);
				} else {
					messageDiv.html('<span style="color: #dc3232;">' + (response.data || '<?php echo esc_js(__('Error saving preference', 'woo-reorder-reminder')); ?>') + '</span>').show();
				}
				button.prop('disabled', false).text(originalText);
			},
			error: function() {
				messageDiv.html('<span style="color: #dc3232;"><?php echo esc_js(__('Error saving preference', 'woo-reorder-reminder')); ?></span>').show();
				button.prop('disabled', false).text(originalText);
			}
		});
	});
});
</script>

