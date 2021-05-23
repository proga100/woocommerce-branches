<?php

function get_orders_list($user_id = '')
{
	if (!$user_id) $user_id = ($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
	$customer_orders = [];
	if ($user_id > 0) {
		$order_statues = '';
		$customer_orders = wc_get_orders([
			'type' => 'shop_order',
			'limit' => -1,
			'customer_id' => $user_id,
			'meta_key' => 'parent_id', // The postmeta key field
			'meta_compare' => 'NOT EXISTS'
		]);
		$customer_orders = assign_orders_key($customer_orders);
		$customer_parent_orders = get_parent_orders($user_id);
		$customer_orders = $customer_orders + $customer_parent_orders;
		return $customer_orders;
	}
}

function get_parent_orders($user_id)
{
	$args = [
		'type' => 'shop_order',
		'limit' => -1,
		'meta_value' => $user_id,
		'meta_key' => 'parent_id', // The postmeta key field
		'meta_compare' => '=', // The comparison argument
	];

	$query = new WC_Order_Query($args);
	$query = assign_orders_key($query->get_orders());
	return $query;
}

function assign_orders_key($queries)
{

	$query_modified = [];
	foreach ($queries as $query) {
		$query_modified[$query->get_id()] = $query;
	}

	return $query_modified;
}

function get_order_total($orders)
{
	$total_order = 0;
	foreach ($orders as $order) :

		$rec_amt = ($order->get_status() == 'completed') ? $order->get_total() : 0;
		$total_order += $order->get_total() - $rec_amt;
	endforeach;
	return $total_order;
}