<?php

function get_orders_list($user_id='')
{
	if (!$user_id) $user_id = ($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
	$customer_orders = [];
	if ($user_id > 0) {
		$order_statues = '';
		$customer_orders = wc_get_orders([
			'type' => 'shop_order',
			'limit' => -1,
			'customer_id' => $user_id
		]);
		return $customer_orders;
	}
}

function get_order_total($orders)
{
	$total_order =0;
	foreach ($orders as $order) :
		$rec_amt = ($order->get_status() == 'completed') ? $order->get_total() : 0;
		$total_order += $order->get_total() - $rec_amt;
	endforeach;
	return $total_order;
}


