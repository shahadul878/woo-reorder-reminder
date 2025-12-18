<?php

/**
 * Logs Page View
 *
 * @package WRR
 */

defined('ABSPATH') || exit;

$logs = WRR_Logger::get_logs();
$sent_count = WRR_Logger::get_log_count('sent');
$pending_count = WRR_Logger::get_log_count('pending');
$failed_count = WRR_Logger::get_log_count('failed');
?>
<div class="wrap">
	<h1><?php esc_html_e('Re-Order Reminder Logs', 'easy-reorder-reminder'); ?></h1>

	<div class="wrr-stats" style="display: flex; gap: 20px; margin: 20px 0;">
		<div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
			<strong><?php esc_html_e('Sent', 'easy-reorder-reminder'); ?>:</strong> <?php echo esc_html($sent_count); ?>
		</div>
		<div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
			<strong><?php esc_html_e('Pending', 'easy-reorder-reminder'); ?>:</strong> <?php echo esc_html($pending_count); ?>
		</div>
		<div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
			<strong><?php esc_html_e('Failed', 'easy-reorder-reminder'); ?>:</strong> <?php echo esc_html($failed_count); ?>
		</div>
	</div>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e('ID', 'easy-reorder-reminder'); ?></th>
				<th><?php esc_html_e('Order ID', 'easy-reorder-reminder'); ?></th>
				<th><?php esc_html_e('Product', 'easy-reorder-reminder'); ?></th>
				<th><?php esc_html_e('Email', 'easy-reorder-reminder'); ?></th>
				<th><?php esc_html_e('Status', 'easy-reorder-reminder'); ?></th>
				<th><?php esc_html_e('Sent At', 'easy-reorder-reminder'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($logs)) : ?>
				<tr>
					<td colspan="6" style="text-align: center; padding: 20px;">
						<?php esc_html_e('No logs found.', 'easy-reorder-reminder'); ?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ($logs as $log) : ?>
					<?php
					$product = wc_get_product($log['product_id']);
					$product_name = $product ? $product->get_name() : __('Product not found', 'easy-reorder-reminder');
					$status_class = 'sent' === $log['status'] ? 'color: #46b450;' : ( 'failed' === $log['status'] ? 'color: #dc3232;' : 'color: #f0b849;' );
					?>
					<tr>
						<td><?php echo esc_html($log['id']); ?></td>
						<td>
							<a href="<?php echo esc_url(admin_url('post.php?post=' . $log['order_id'] . '&action=edit')); ?>">
								#<?php echo esc_html($log['order_id']); ?>
							</a>
						</td>
						<td>
							<?php if ($product) : ?>
								<a href="<?php echo esc_url(admin_url('post.php?post=' . $log['product_id'] . '&action=edit')); ?>">
									<?php echo esc_html($product_name); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html($product_name); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html($log['email']); ?></td>
						<td style="<?php echo esc_attr($status_class); ?>">
							<strong><?php echo esc_html(ucfirst($log['status'])); ?></strong>
						</td>
						<td><?php echo esc_html($log['sent_at']); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

