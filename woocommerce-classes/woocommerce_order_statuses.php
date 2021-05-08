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
	}

	public function process_payment_order_status($status, $order)
	{
		$status = ($status == 'on-hold') ? 'ordered' : $status;
		return $status;
	}
}

new woocommerce_order_statuses();
