<?php

class OrderPackageSplit
{
	public $doctype = '';
	public $products_in_packing = [];

	public function __construct()
	{

		add_action('woocommerce_admin_order_item_headers', [$this, 'woocommerce_admin_order_item_headers']);
		add_action('woocommerce_admin_order_item_values', [$this, 'woocommerce_admin_order_item_values'], 10, 3);

		add_action("wp_ajax_stm_packing_products", [$this, "stm_packing_products"]);

		add_filter('wpo_wcpdf_order_items_data', [$this, 'wpo_wcpdf_order_items_data'], 10, 3);
		add_filter('wpo_wcpdf_templates_totals', [$this, 'wpo_wcpdf_templates_totals'], 10, 3);

		add_filter('woocommerce_order_get_items', [$this, 'filter_woocommerce_order_get_items'], 15, 2);


	}

	public function stm_packing_products()
	{
		if (!wp_verify_nonce($_REQUEST['nonce'], "stm_packing_products")) {
			exit("No naughty business please");
		}
		$post_id = (int)($_GET['post_id']);

		$stm_packing_products = $_POST['product_ids'];

		if (update_post_meta($post_id, 'products_in_packing', $stm_packing_products)) {
			$messages = __('Successfully Packing Products updated', 'woocoomerce-branches');
		} else {
			$messages = __('Successfully Packing Products updated', 'woocoomerce-branches');
		}

		wp_send_json(['messages' => $messages, 'products_in_packing' => $stm_packing_products]);
	}

	public function generate_admin_script($order_id)
	{
		$nonce = wp_create_nonce("stm_packing_products");
		$admin_url = admin_url('admin-ajax.php?action=stm_packing_products&post_id=' . $order_id . '&nonce=' . $nonce);
		wp_enqueue_script('woocommerce-order-check', FLANCE_BRANCHES_URL . '/assets/js/woocommerce-order-check.js', ['jquery'], time(), true);
		wp_add_inline_script('woocommerce-order-check', 'var stm_packing_products_admin_url = "' . $admin_url . '";', 'before');

	}

	public function woocommerce_admin_order_item_headers($order)
	{
		$this->generate_admin_script($order->get_id());
		?>
        <th class="item-th-check">Check to include in packing slip</th>
		<?php
	}

	public function woocommerce_admin_order_item_values($product, $item, $item_id)
	{

		$product_id = (!empty($product)) ? $product->get_id() : null;
		//if (!$product_id) return;
		?>
        <td class="item-td-check">
            <input type="checkbox" checked
                   class="package_split_exclude" data-stm_product_id="<?php echo $product_id ?>"
                   id="package_split_add_<?php echo $item_id ?>"/>
        </td>
		<?php
	}

	public function wpo_wcpdf_order_items_data($data_list, $order, $doc_type)
	{
		if ($doc_type == 'packing-slip') {
			$this->doctype = $doc_type;
			$modified_data_list = [];

			$this->products_in_packing = (get_post_meta($order->get_id(), 'products_in_packing', true)) ? get_post_meta($order->get_id(), 'products_in_packing', true) : [];
			
			foreach ($data_list as $item_id => $product) {
				if (!in_array($product['product_id'], $this->products_in_packing)) {
					$modified_data_list[$item_id] = $product;
				}
			}
			$data_list = $modified_data_list;
		}
		return $data_list;
	}

	public function wpo_wcpdf_templates_totals($totals_data, $doc_type, $document)
	{
		if ($doc_type == 'packing-slip') {
			//  show_data( $document);
			$this->doctype = $doc_type;

			//show_data($totals_data);
		}
		return $totals_data;
	}

	function filter_woocommerce_order_get_items($items, $instance)
	{
		if ($this->doctype == 'packing-slip') {
			$modified_items = [];
			foreach ($items as $item_id => $item_values) {
				if (!in_array($item_values->get_product_id(), $this->products_in_packing)) {
					$modified_items[$item_id] = $item_values;
				}
				$items = $modified_items;
			}
		}
		return $items;
	}
}