<?php
/**
 * Email Handler Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * WRR_Email Class
 */
if ( ! class_exists( 'WRR_Email' ) ) {
	class WRR_Email extends WC_Email {

	/**
	 * Instance
	 *
	 * @var WRR_Email
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WRR_Email
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
	public function __construct() {
		$this->id             = 'wrr_reorder_reminder';
		$this->title          = __( 'Re-Order Reminder', 'woo-reorder-reminder' );
		$this->description    = __( 'Email sent to customers to remind them to reorder products.', 'woo-reorder-reminder' );
		$this->customer_email = true;
		$this->template_html  = 'emails/reorder-reminder.php';
		$this->template_plain = 'emails/plain/reorder-reminder.php';
		$this->placeholders   = array(
			'{customer_name}' => '',
			'{product_name}'  => '',
			'{reorder_link}'  => '',
		);

		// Call parent constructor
		parent::__construct();
	}

	/**
	 * Get email subject
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Time to reorder {product_name}!', 'woo-reorder-reminder' );
	}

	/**
	 * Get email heading
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Don\'t forget to reorder {product_name}', 'woo-reorder-reminder' );
	}

	/**
	 * Get email content
	 *
	 * @return string
	 */
	public function get_default_content() {
		return __( 'Hi {customer_name},', 'woo-reorder-reminder' ) . "\n\n" .
			__( 'It\'s been a while since you last purchased {product_name}. We wanted to remind you to reorder if you need it again.', 'woo-reorder-reminder' ) . "\n\n" .
			__( 'Click here to add it to your cart: {reorder_link}', 'woo-reorder-reminder' ) . "\n\n" .
			__( 'If you no longer wish to receive these reminders, you can unsubscribe here: {unsubscribe_link}', 'woo-reorder-reminder' );
	}

	/**
	 * Trigger email
	 *
	 * @param WC_Order $order Order object.
	 * @param int      $product_id Product ID.
	 */
	public function trigger( $order, $product_id ) {
		if ( ! $order || ! $product_id ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$this->object      = $order;
		$this->product     = $product;
		$this->recipient   = $order->get_billing_email();
		$this->product_id  = $product_id;

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		// Set placeholders
		$this->placeholders['{customer_name}'] = $order->get_billing_first_name() ? $order->get_billing_first_name() : __( 'Customer', 'woo-reorder-reminder' );
		$this->placeholders['{product_name}']  = $product->get_name();
		$this->placeholders['{reorder_link}']  = $this->get_reorder_link( $product_id );
		$this->placeholders['{unsubscribe_link}'] = $this->get_unsubscribe_link( $this->recipient );

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get reorder link
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	private function get_reorder_link( $product_id ) {
		$cart_url = wc_get_cart_url();
		$link     = add_query_arg( 'add-to-cart', $product_id, $cart_url );
		return apply_filters( 'wrr_reorder_link', $link, $product_id );
	}

	/**
	 * Get unsubscribe link
	 *
	 * @param string $email Customer email.
	 * @return string
	 */
	private function get_unsubscribe_link( $email ) {
		$nonce = wp_create_nonce( 'wrr_unsubscribe_' . $email );
		$link  = add_query_arg(
			array(
				'wrr_unsubscribe' => 1,
				'email'            => rawurlencode( $email ),
				'nonce'            => $nonce,
			),
			home_url()
		);
		return $link;
	}

	/**
	 * Get content html
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'         => $this->object,
				'product'       => $this->product,
				'email_heading' => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			WRR_PATH . 'templates/'
		);
	}

	/**
	 * Get content plain
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'product'       => $this->product,
				'email_heading' => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			WRR_PATH . 'templates/'
		);
	}

	/**
	 * Initialize settings form fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'woo-reorder-reminder' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woo-reorder-reminder' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'woo-reorder-reminder' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => sprintf( __( 'Available placeholders: %s', 'woo-reorder-reminder' ), '{customer_name}, {product_name}, {reorder_link}' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email heading', 'woo-reorder-reminder' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => sprintf( __( 'Available placeholders: %s', 'woo-reorder-reminder' ), '{customer_name}, {product_name}, {reorder_link}' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'additional_content' => array(
				'title'       => __( 'Additional content', 'woo-reorder-reminder' ),
				'description' => __( 'Text to appear below the main email content.', 'woo-reorder-reminder' ),
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'woo-reorder-reminder' ),
				'type'        => 'textarea',
				'default'     => '',
				'desc_tip'    => true,
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'woo-reorder-reminder' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woo-reorder-reminder' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Send reorder email (static method for easy access)
	 *
	 * @param WC_Order $order Order object.
	 * @param int      $product_id Product ID.
	 * @return bool
	 */
	public static function send_reorder_email( $order, $product_id ) {
		$email = self::instance();
		$email->trigger( $order, $product_id );

		// Log email sent
		WRR_Logger::log( $order->get_id(), $product_id, $order->get_billing_email(), 'sent' );

		return true;
	}
} // End if class_exists check

