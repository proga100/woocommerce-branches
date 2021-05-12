<?php
/**
 * Class WC_Email_Customer_Processing_Order file.
 *
 * @package WooCommerce\Emails
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
require_once FLANCE_BRANCHES_PATH . '/woocommerce-classes/woocommerce_order_statuses.php';
if (!class_exists('WC_Email_Customer_Ordered_Child', false)) :

	/**
	 * Customer Processing Order Email.
	 *
	 * An email sent to the customer when a new order is paid for.
	 *
	 * @class       WC_Email_Customer_Processing_Order
	 * @version     3.5.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Ordered_Child extends WC_Email
	{

		/**
		 * Constructor.
		 */
		public function __construct()
		{
			$this->id = 'customer_ordered_child';
			$this->customer_email = true;

			$this->title = __('Processing order', 'woocommerce');
			$this->description = __('This is an order notification sent to customers containing order details after payment.', 'woocommerce');
			$this->template_html = 'emails/customer-processing-order.php';
			$this->template_plain = 'emails/plain/customer-processing-order.php';
			$this->placeholders = array(
				'{order_date}' => '',
				'{order_number}' => '',
			);

			// Triggers for this email.
			add_action('woocommerce_order_status_cancelled_to_processing_notification', array($this, 'trigger'), 10, 2);
			add_action('woocommerce_order_status_failed_to_processing_notification', array($this, 'trigger'), 10, 2);
			add_action('woocommerce_order_status_on-hold_to_processing_notification', array($this, 'trigger'), 10, 2);
			add_action('woocommerce_order_status_pending_to_processing_notification', array($this, 'trigger'), 10, 2);

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_subject()
		{
			return __('Your {site_title} order has been received!', 'woocommerce');
		}

		/**
		 * Get email heading.
		 *
		 * @return string
		 * @since  3.1.0
		 */
		public function get_default_heading()
		{
			return __('Thank you for your order', 'woocommerce');
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger($order_id, $order = false)
		{
			$this->setup_locale();

			if ($order_id && !is_a($order, 'WC_Order')) {
				$order = wc_get_order($order_id);
			}
			$parent_user_id = woocommerce_order_statuses::set_parent_id();
			if (is_a($order, 'WC_Order')) {
				$this->object = $order;
				if ($parent_user_id) {
					$user_id = get_current_user_id();
					$current_user = wp_get_current_user();
					$email = $current_user->user_email;
					$this->recipient = $email;
				} else {
					$this->recipient = $this->object->get_billing_email();
				}
				$this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

			if ($this->is_enabled() && $this->get_recipient()) {
				$this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html()
		{


			return wc_get_template_html(
				$this->template_html,
				array(
					'order' => $this->object,
					'email_heading' => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin' => false,
					'plain_text' => false,
					'email' => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain()
		{


			return wc_get_template_html(
				$this->template_plain,
				array(
					'order' => $this->object,
					'email_heading' => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin' => false,
					'plain_text' => true,
					'email' => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @return string
		 * @since 3.7.0
		 */
		public function get_default_additional_content()
		{
			return __('Thanks for using {site_url}!', 'woocommerce');
		}
	}

endif;

return new WC_Email_Customer_Ordered_Child();
