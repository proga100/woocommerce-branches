<?php

use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;


class OrderPackageSplit
{
	public $doctype = '';
	public $products_in_packing = [];

	public function __construct()
	{

		add_action('woocommerce_admin_order_item_headers', [$this, 'woocommerce_admin_order_item_headers']);
		add_action('woocommerce_admin_order_item_values', [$this, 'woocommerce_admin_order_item_values'], 10, 3);

		add_action("wp_ajax_stm_packing_products", [$this, "stm_packing_products"]);
		add_action("wp_ajax_stm_packing_products_add", [$this, "stm_packing_products_add"]);

		add_filter('wpo_wcpdf_order_items_data', [$this, 'wpo_wcpdf_order_items_data'], 10, 3);
		add_filter('wpo_wcpdf_templates_totals', [$this, 'wpo_wcpdf_templates_totals'], 10, 3);

		add_filter('woocommerce_order_get_items', [$this, 'filter_woocommerce_order_get_items'], 15, 2);
		add_action('add_meta_boxes_shop_order', array($this, 'add_meta_boxes_child'));
		
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

	public function stm_packing_products_add()
	{
		if (!wp_verify_nonce($_REQUEST['nonce'], "stm_packing_products_add")) {
			exit("No naughty business please");
		}
		$post_id = (int)($_GET['post_id']);
		$stm_packing_products = $_POST['product_ids'];
		$stm_packing_products = json_encode($stm_packing_products);

		if (update_post_meta($post_id, 'products_in_packing_add', $stm_packing_products)) {
			$messages = __('Successfully Packing Products updated', 'woocoomerce-branches');
		} else {
			$messages = __('Successfully Packing Products updated', 'woocoomerce-branches');
		}

		wp_send_json(['messages' => $messages, 'products_in_packing' => $stm_packing_products]);
	}

	public function generate_admin_script($order_id)
	{
		$nonce = wp_create_nonce("stm_packing_products_add");
		$generate_wpo_wcpdf = wp_create_nonce('generate_wpo_wcpdf');

		$stm_packing_products_admin_url['admin_url'] = admin_url('admin-ajax.php?action=stm_packing_products_add&post_id=' . $order_id . '&nonce=' . $nonce);
		$stm_packing_products_admin_url['pdf_link_generate_wpo_wcpdf'] = get_admin_url() . 'admin-ajax.php?action=generate_wpo_wcpdf&document_type=packing-slip&order_ids=' . $order_id . '&_wpnonce=' . $generate_wpo_wcpdf;
		$stm_packing_products_admin_url = json_encode($stm_packing_products_admin_url);

		wp_enqueue_style('multiple-select.min.css', FLANCE_BRANCHES_URL . '/assets/select-multiple/multiple-select.min.css', time(), true);
		wp_enqueue_script('multiple-select.min.js', FLANCE_BRANCHES_URL . '/assets/select-multiple/multiple-select.min.js', ['jquery'], time(), true);

		wp_enqueue_script('woocommerce-order-check', FLANCE_BRANCHES_URL . '/assets/js/woocommerce-order-check.js', ['jquery'], time(), true);
		wp_add_inline_script('woocommerce-order-check', 'var stm_packing_products_admin_url = ' . $stm_packing_products_admin_url . ';', 'before');

	}

	public function woocommerce_admin_order_item_headers($order)
	{
		$this->generate_admin_script($order->get_id());
		?>
        <!--  <th class="item-th-check">Check to include in Total packing slip</th> -->
        <th class="item-th-check">Single item Packing Slip</th>
		<?php
	}

	public function woocommerce_admin_order_item_values($product, $item, $item_id)
	{
		$product_id = (!empty($product)) ? $product->get_id() : null;
		$nonce = wp_create_nonce('generate_wpo_wcpdf');
		//if (!$product_id) return;
		?>
        <!--
        <td class="item-td-check">
            <input type="checkbox" checked
                   class="package_split_exclude" data-stm_product_id="<?php echo $product_id ?>"
                   id="package_split_add_<?php echo $item_id ?>"/>

        </td>
        -->
        <td class="item-td-check-button">
			<?php $pdf_link = get_admin_url() . 'admin-ajax.php?action=generate_wpo_wcpdf&amp;document_type=packing-slip&amp;order_ids=' . $item->get_order_id() . '&amp;_wpnonce=' . $nonce . '&amp;stm_product_id[]=' . $product_id; ?>
            <a id="print_spdf_<?php echo $product_id ?>" href="<?php echo $pdf_link ?>"
               class="btn btn-success" target="_blank" alt="PDF Packing Slip">
                <span class="dashicons dashicons-media-interactive"></span>PDF</a>
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
				if (!empty($_GET['stm_product_id'])) {

					if (in_array($product['product_id'], $_GET['stm_product_id'])) $modified_data_list[$item_id] = $product;
				} elseif (!in_array($product['product_id'], $this->products_in_packing)) {
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
				if (!empty($_GET['stm_product_id'])) {
					if (in_array($item_values->get_product_id(), $_GET['stm_product_id'])) $modified_items[$item_id] = $item_values;;
				} elseif (!in_array($item_values->get_product_id(), $this->products_in_packing)) {
					$modified_items[$item_id] = $item_values;
				}
				$items = $modified_items;
			}
		}
		return $items;
	}

	/**
	 * Add the meta box on the single order page
	 */
	public function add_meta_boxes_child()
	{

		// create PDF buttons
		add_meta_box(
			'wpo_wcpdf-box-child',
			__('Create PDF splitted Packing slips', 'woocommerce-branches'),
			array($this, 'pdf_actions_meta_box_child'),
			'shop_order',
			'side',
			'default'
		);
	}

	/**
	 * Create the meta box content on the single order page
	 */
	public function pdf_actions_meta_box_child($post)
	{
		global $post_id;
		$meta_box_actions = array();
		$documents = WPO_WCPDF()->documents->get_documents();

		$order = WCX::get_order($post->ID);
		$packings_id = 0;
		$products = OrderPackageSplit::get_products($order);
		$products_packings = (get_post_meta($post->ID, 'products_in_packing_add', true)) ? get_post_meta($post->ID, 'products_in_packing_add', true) : '';
		$products_packings = (json_decode($products_packings, true)) ? json_decode($products_packings, true): [];
		foreach ($documents as $document) {
			$document_title = $document->get_title();
			if ($document = wcpdf_get_document($document->get_type(), $order)) {
				$document_title = is_callable(array($document, 'get_title')) ? $document->get_title() : $document_title;
				$meta_box_actions[$document->get_type()] = array(
					'url' => wp_nonce_url(admin_url("admin-ajax.php?action=generate_wpo_wcpdf&document_type={$document->get_type()}&order_ids=" . $post_id), 'generate_wpo_wcpdf'),
					'alt' => esc_attr("PDF " . $document_title),
					'title' => "PDF " . $document_title,
					'exists' => is_callable(array($document, 'exists')) ? $document->exists() : false,
				);
			}
		}

		$meta_box_actions = apply_filters('wpo_wcpdf_meta_box_actions', $meta_box_actions, $post_id);

		?>
        <ul class="wpo_wcpdf-actions">
			<?php
			$data = $meta_box_actions['packing-slip'];
			$exists = (isset($data['exists']) && $data['exists'] == true) ? 'exists' : '';
			///  $pdf_slip = ('<li><a href="%1$s" class="button %4$s" target="_blank" alt="%2$s">%3$s</a></li>', $data['url'], $data['alt'], $data['title'], $exists);
			?>
        </ul>
        <div class="stm-container">
			<?php
			//show_data($products_packings);
			foreach ($products_packings as $packings_id => $products_packing) {
				$url = $data['url'];
				$products_packing = explode(',', $products_packing);
				//show_data($products_packing);
				foreach ($products as $product_id => $product_name) {
					if (in_array($product_id, $products_packing)) {
						$url .= '&stm_product_id[]=' . $product_id;
					}
				}
				$packing_number = '';
				$pdf_slip = sprintf('<a href="%1$s" class="button %4$s" target="_blank" alt="%2$s">%3$s</a>', $url . '', $data['alt'], $packing_number . ' ' . $data['title'], $exists);
				?>
                <div class="stm-row stm-addable">
                    <div class="stm-col-3 pdf_slip_<?php echo $packings_id ?>"><?php echo $pdf_slip ?></div>
                    <div class="stm-col-4"><select data-item_id="<?php echo $packings_id ?>" multiple
                                                   id="products_packing_added_<?php echo $packings_id ?>"
                                                   class="products_packing_added addable">
							<?php foreach ($products as $product_id => $product_name) {
								$selected = (in_array($product_id, $products_packing)) ? 'selected' : '';
								?>
                                <option value="<?php echo $product_id ?>" <?php echo $selected ?> ><?php echo $product_name ?></option>
								<?php
							} ?>
                        </select>
                    </div>
                    <div class="stm-col-2 rem_pdf_slip_<?php echo $packings_id ?>">
                        <a class="button exists rem_pdf" data-item_id="<?php echo $packings_id ?>"
                           id="rem_packing_id_<?php echo $packings_id ?>">Remove PDF Packing
                            Slip</a>
                    </div>
                </div>
				<?php
			}
			$packings_id = $packings_id + 1;

			$packing_number = '';
			$url = $data['url'];
			$pdf_slip = sprintf('<a href="%1$s" class="button %4$s" target="_blank" alt="%2$s">%3$s</a>', $url . '', $data['alt'], $packing_number . ' ' . $data['title'], $exists);

			?>
            <div class="stm-row stm-add">

                <div class="stm-col-3 pdf_slip_<?php echo $packings_id ?>"><?php echo $pdf_slip ?></div>
                <div class="stm-col-4">
                    <select data-item_id="<?php echo $packings_id ?>"
                            multiple id="products_packing_added_<?php echo $packings_id ?>"
                            class="products_packing_added">
						<?php foreach ($products as $product_id => $product_name) {
							?>
                            <option value="<?php echo $product_id ?>"><?php echo $product_name ?></option>
							<?php
						} ?>
                    </select>
                </div>


                <div class="stm-col-2 add_pdf_slip_<?php echo $packings_id ?>">
                    <a class="button exists add_pdf" data-item_id="<?php echo $packings_id ?>"
                       id="add_packing_id_<?php echo $packings_id ?>">Add PDF Packing
                        Slip</a>
                </div>
                <div class="stm-col-2 rem_pdf_slip_<?php echo $packings_id ?>">
                    <a class="button exists rem_pdf" data-item_id="<?php echo $packings_id ?>"
                       id="rem_packing_id_<?php echo $packings_id ?>">Remove PDF Packing
                        Slip</a>
                </div>
            </div>
        </div>
		<?php
	}

	public static function get_products($order)
	{
		$items = $order->get_items();
		$products = [];
		foreach ($items as $item) {
			$product_name = $item->get_name();
			$product_id = $item->get_product_id();
			$products[$product_id] = $product_name;
		}
		return $products;
	}
}
