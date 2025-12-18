<?php
/**
 * Re-Order Reminder Email Template
 *
 * @package WRR
 * @var WC_Order $order
 * @var WC_Product $product
 * @var string $email_heading
 * @var string $additional_content
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var WRR_Email $email
 */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php
// Handle preview mode where order/product might be null
$customer_name = $order && method_exists($order, 'get_billing_first_name')
	? ( $order->get_billing_first_name() ? $order->get_billing_first_name() : __('Customer', 'woo-reorder-reminder') )
	: __('Customer', 'woo-reorder-reminder');

$product_name = $product && method_exists($product, 'get_name')
	? $product->get_name()
	: __('Sample Product', 'woo-reorder-reminder');

$product_id = $product && method_exists($product, 'get_id')
	? $product->get_id()
	: 0;

$reorder_link = $product_id > 0
	? add_query_arg('add-to-cart', $product_id, wc_get_cart_url())
	: wc_get_cart_url();

$customer_email = $order && method_exists($order, 'get_billing_email')
	? $order->get_billing_email()
	: 'customer@example.com';

$unsubscribe_link = add_query_arg(
	array(
		'wrr_unsubscribe' => 1,
		'email'            => rawurlencode($customer_email),
		'nonce'            => wp_create_nonce('wrr_unsubscribe_' . $customer_email),
	),
	home_url()
);
?>

<p><?php printf(esc_html__('Hi %s,', 'woo-reorder-reminder'), esc_html($customer_name)); ?></p>

<p><?php
	printf(
		esc_html__('It\'s been a while since you last purchased %s. We wanted to remind you to reorder if you need it again.', 'woo-reorder-reminder'),
		'<strong>' . esc_html($product_name) . '</strong>'
	);
    ?></p>

<p style="text-align: center; margin: 30px 0;">
	<a href="<?php echo esc_url($reorder_link); ?>" style="background-color: #96588a; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 4px; display: inline-block;">
		<?php esc_html_e('Re-Order Now', 'woo-reorder-reminder'); ?>
	</a>
</p>

<?php if ($additional_content) : ?>
	<p><?php echo wp_kses_post($additional_content); ?></p>
<?php endif; ?>

<p style="font-size: 12px; color: #666;">
	<?php esc_html_e('If you no longer wish to receive these reminders, you can', 'woo-reorder-reminder'); ?>
	<a href="<?php echo esc_url($unsubscribe_link); ?>"><?php esc_html_e('unsubscribe here', 'woo-reorder-reminder'); ?></a>.
</p>

<?php
do_action('woocommerce_email_footer', $email);

