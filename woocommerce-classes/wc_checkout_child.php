<?php


class wc_checkout_child
{
	public $logged_in_customer = null;
	public $parent_user_id = null;
	public $billing_metas = ['billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_country',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_country',
		'billing_postcode',
		'billing_phone',
		'billing_email'];

	public function __construct()
	{

		add_action('init', [$this, 'init']);
	}

	public function init()
	{
		$this->set_parent_id();
		add_filter('woocommerce_checkout_get_value', [$this, 'set_checkout_input_values'], 100000, 2);

		add_filter('woocommerce_ship_to_different_address_checked', [$this, 'woocommerce_ship_to_different_address_checked']);

		add_action('woocommerce_thankyou', [$this, 'backup_update_billing_data'], 10, 1);

	}

	public function backup_update_billing_data($order_id)
	{
		if (!$order_id || !$this->parent_user_id)
			return;

		foreach ($this->billing_metas as $input) {
			$user_id = get_current_user_id();
			$user_billing_data = get_user_meta($user_id, "backup_$input", true);
			if ($user_billing_data) update_user_meta($user_id, $input, $user_billing_data);
		}

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

			self::set_backup_billing_meta($input, $value);

			$value = self::get_value($this->parent_user_id, $input);
		}
		return $value;
	}

	public static function set_backup_billing_meta($input, $value)
	{
		$user_id = get_current_user_id();
			$user_billing_data = get_user_meta($user_id, $input, true);
			//echo "$user_id $input $user_billing_data";
			if ($user_billing_data)
				update_user_meta($user_id, "backup_$input", $user_billing_data);
	}

	public static function get_value($user_id, $input)
	{
		global $wc_checkout_child;
		$customer_object = false;

		if (is_user_logged_in()) {
			// Load customer object, but keep it cached to avoid reloading it multiple times.
			if (is_null($wc_checkout_child->logged_in_customer)) {
				$wc_checkout_child->logged_in_customer = new WC_Customer($user_id, true);
			}
			$customer_object = $wc_checkout_child->logged_in_customer;
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

$wc_checkout_child = new wc_checkout_child();