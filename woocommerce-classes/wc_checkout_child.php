<?php


class wc_checkout_child
{
	public $logged_in_customer = null;
	public $parent_user_id = null;

	public function __construct()
	{

		add_action('init', [$this, 'init']);
	}

	public function init()
	{
		$this->set_parent_id();
		add_filter('woocommerce_checkout_get_value', [$this, 'set_checkout_input_values'], 10, 2);

		add_filter('woocommerce_ship_to_different_address_checked', [$this, 'woocommerce_ship_to_different_address_checked']);
	}

	public function woocommerce_ship_to_different_address_checked($shipping)
	{
		if ($this->parent_user_id) {
			$shipping = 1;
		}
		return $shipping;
	}

	public function set_parent_id()
	{
		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
			$this->parent_user_id = get_user_meta($user_id, 'parent_customer_id', true);
		}
	}

	public function set_checkout_input_values($value, $input)
	{

		$input_check_for_billing = explode('_', $input);
		if ($this->parent_user_id && in_array('billing', $input_check_for_billing)) {
			$value = $this->get_value($this->parent_user_id, $input);
		}
		return $value;
	}

	public function get_value($user_id, $input)
	{

		$customer_object = false;

		if (is_user_logged_in()) {
			// Load customer object, but keep it cached to avoid reloading it multiple times.
			if (is_null($this->logged_in_customer)) {
				$this->logged_in_customer = new WC_Customer($user_id, true);
			}
			$customer_object = $this->logged_in_customer;
		}

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
}

new wc_checkout_child();