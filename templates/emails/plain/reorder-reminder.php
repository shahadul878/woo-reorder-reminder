<?php
/**
 * Re-Order Reminder Email Template (Plain)
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

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( $email_heading ) . " =\n\n";

$customer_name = $order->get_billing_first_name() ? $order->get_billing_first_name() : __( 'Customer', 'woo-reorder-reminder' );
$product_name  = $product->get_name();
$reorder_link  = add_query_arg( 'add-to-cart', $product->get_id(), wc_get_cart_url() );
$unsubscribe_link = add_query_arg(
	array(
		'wrr_unsubscribe' => 1,
		'email'            => rawurlencode( $order->get_billing_email() ),
		'nonce'            => wp_create_nonce( 'wrr_unsubscribe_' . $order->get_billing_email() ),
	),
	home_url()
);

printf( esc_html__( 'Hi %s,', 'woo-reorder-reminder' ), esc_html( $customer_name ) );
echo "\n\n";

printf(
	esc_html__( 'It\'s been a while since you last purchased %s. We wanted to remind you to reorder if you need it again.', 'woo-reorder-reminder' ),
	esc_html( $product_name )
);
echo "\n\n";

echo esc_html__( 'Re-Order Now:', 'woo-reorder-reminder' ) . "\n";
echo esc_url( $reorder_link ) . "\n\n";

if ( $additional_content ) {
	echo wp_kses_post( $additional_content ) . "\n\n";
}

echo esc_html__( 'If you no longer wish to receive these reminders, you can unsubscribe here:', 'woo-reorder-reminder' ) . "\n";
echo esc_url( $unsubscribe_link ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action( 'woocommerce_email_footer', $email );

