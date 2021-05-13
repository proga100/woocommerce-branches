<?php


class woocommerce_order_statuses
{
	public function __construct()
	{
		add_action('init', [$this, 'init_template']);


	}

	public function init_template()
	{
		add_filter('woocommerce_bacs_process_payment_order_status', [$this, 'process_payment_order_status'], 10, 2);
		add_filter('woocommerce_cheque_process_payment_order_status', [$this, 'process_payment_order_status'], 10, 2);
		add_filter('woocommerce_email_classes', [$this, 'woocommerce_email_classes']);
		//add_filter('woocommerce_defer_transactional_emails', [$this, 'woocommerce_defer_transactional_emails']);
		//apply_filters( $this->get_hook_prefix() . $address . '_' . $prop, $value, $this );
		add_filter('wc_order_statuses', [$this, 'so_39252649_remove_processing_status'], 1000000);
	}

	public function so_39252649_remove_processing_status($statuses)
	{
		$rem_stat = ['wc-pending' => 0, 'wc-cancelled' => 1, 'wc-on-hold' => 3, 'wc-failed' => 5, 'wc-checkout-draft' => 6];
		$statuses = array_diff_key($statuses, $rem_stat);
		return $statuses;
	}

	public static function set_parent_id()
	{
		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
			return $parent_user_id = get_user_meta($user_id, 'parent_customer_id', true);
		} else {
			return null;
		}
	}

	public static function get_value($user_id, $input)
	{
		$customer_object = false;

		$customer_object = new WC_Customer($user_id, true);

		if (is_callable(array($customer_object, "get_$input"))) {
			$value = $customer_object->{"get_$input"}();
		} elseif ($customer_object->meta_exists($input)) {
			$value = $customer_object->get_meta($input, true);
		}

		if ('' === $value) {
			$value = null;
		}
		return $value;
	}

	public function woocommerce_email_classes($email_classes)
	{
		$email_classes_add =[
			'WC_Email_Customer_Dispatched_Child' =>'class-wc-email-customer-dispatched.php',
			'WC_Email_Customer_Ordered_Child' => 'class-wc-email-customer-ordered.php',
			'WC_Email_Customer_Processing_Child' => 'class-wc-email-customer-processing.php',
			'WC_Email_Customer_Invoice_Child' => 'class-wc-email-customer-invoice.php'
		];
		foreach ($email_classes_add as $key=>$email_class_file){
			$email_classes[$key] = include_once FLANCE_BRANCHES_PATH . "/woocommerce-classes/email_classes/$email_class_file";
		}

		array_merge($email_classes_add,$email_classes );
		return $email_classes;
	}

	public function process_payment_order_status($status, $order)
	{
		$status = ($status == 'on-hold') ? 'ordered' : $status;
		return $status;
	}

	public function woocommerce_defer_transactional_emails()
	{

	}

	function ordered_status_custom_notification($order_id, $order)
	{
		// HERE below your settings
		//$heading = __('Your Awaiting delivery order', 'woocommerce');
		//$subject = '[{site_title}] Awaiting delivery order ({order_number}) - {order_date}';

		// Getting all WC_emails objects
		$mailer = WC()->mailer()->get_emails();

		// Customizing Heading and subject In the WC_email processing Order object
		//$mailer['WC_Email_Customer_Processing_Order']->heading = $heading;
		//$mailer['WC_Email_Customer_Processing_Order']->subject = $subject;

		// Sending the customized email
		$mailer['WC_Email_Customer_Ordered_Child']->trigger($order_id);
	}
}

class woocommerce_order_statuses_new
{

	public function __construct()
	{
		add_action('init', [$this, 'register_custom_post_status'], 20);
		add_action('init', [$this, 'init']);
	}

	function init()
	{
		// Adding custom status 'awaiting-delivery' to order edit pages dropdown
		add_filter('wc_order_statuses', [$this, 'custom_wc_order_statuses'], 20, 1);
		add_filter('bulk_actions-edit-shop_order', [$this, 'custom_dropdown_bulk_actions_shop_order'], 20, 1);
		// Adding action for 'awaiting-delivery'
		add_filter('woocommerce_email_actions', [$this, 'custom_email_actions'], 20, 1);
		add_action('woocommerce_order_status_wc-awaiting-delivery', array(WC(), 'send_transactional_email'), 10, 1);

		// Sending an email notification when order get 'awaiting-delivery' status
		add_action('woocommerce_order_status_awaiting-delivery', [$this, 'backorder_status_custom_notification'], 20, 2);
	}

	function register_custom_post_status()
	{
		register_post_status('wc-awaiting-delivery', array(
			'label' => _x('Awaiting delivery', 'Order status', 'woocommerce'),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop('Awaiting delivery <span class="count">(%s)</span>', 'Awaiting delivery <span class="count">(%s)</span>', 'woocommerce')
		));
	}


	function custom_wc_order_statuses($order_statuses)
	{
		$order_statuses['wc-awaiting-delivery'] = _x('Awaiting delivery', 'Order status', 'woocommerce');
		return $order_statuses;
	}


	function custom_dropdown_bulk_actions_shop_order($actions)
	{
		$actions['mark_awaiting-delivery'] = __('Mark Awaiting delivery', 'woocommerce');
		return $actions;
	}


	function custom_email_actions($action)
	{
		$actions[] = 'woocommerce_order_status_wc-awaiting-delivery';
		return $actions;
	}


	function backorder_status_custom_notification($order_id, $order)
	{
		// HERE below your settings
		$heading = __('Your Awaiting delivery order', 'woocommerce');
		$subject = '[{site_title}] Awaiting delivery order ({order_number}) - {order_date}';

		// Getting all WC_emails objects
		$mailer = WC()->mailer()->get_emails();

		// Customizing Heading and subject In the WC_email processing Order object
		$mailer['WC_Email_Customer_Processing_Order']->heading = $heading;
		$mailer['WC_Email_Customer_Processing_Order']->subject = $subject;

		// Sending the customized email
		$mailer['WC_Email_Customer_Processing_Order']->trigger($order_id);
	}
}

///new woocommerce_order_statuses_new();
