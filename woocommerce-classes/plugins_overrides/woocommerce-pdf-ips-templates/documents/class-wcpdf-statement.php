<?php

namespace WPO\WC\PDF_Invoices\Documents;

use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;

require_once FLANCE_BRANCHES_PATH . '/woocommerce-classes/order_data.php';
require_once FLANCE_BRANCHES_PATH . '/woocommerce-classes/countries.php';


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('\\WPO\\WC\\PDF_Invoices\\Documents\\Statement')) :

	/**
	 * Invoice Document
	 *
	 * @class       \WPO\WC\PDF_Invoices\Documents\Invoice
	 * @version     2.0
	 * @category    Class
	 * @author      Ewout Fernhout
	 */

	class Statement extends Order_Document_Methods
	{
		/**
		 * Init/load the order object.
		 *
		 * @param int|object|WC_Order $order Order to init.
		 */
		public function __construct($order = 0)
		{
			// set properties
			$this->type = 'statement';
			$this->title = __('Statement of Account', 'woocommerce-pdf-invoices-packing-slips');
			$this->icon = WPO_WCPDF()->plugin_url() . "/assets/images/invoice.svg";
			add_filter('wpo_wcpdf_document_is_allowed', array($this, 'enable_statement'), 10, 2);
			// Call parent constructor
			parent::__construct($order);
		}

		public function enable_statement($allowed, $document)
		{
			if ($document->type == 'statement') $allowed = true;
			return $allowed;
		}


		public function get_title()
		{
			// override/not using $this->title to allow for language switching!
			return apply_filters("wpo_wcpdf_{$this->slug}_title", __('Statement of Account', 'woocommerce-pdf-invoices-packing-slips'), $this);
		}

		public function get_filename($context = 'download', $args = array())
		{
			$order_count = isset($args['order_ids']) ? count($args['order_ids']) : 1;

			$name = _n('statement', 'invoices', $order_count, 'woocommerce-pdf-invoices-packing-slips');

			if ($order_count == 1) {
				if (isset($this->settings['display_number']) && $this->settings['display_number'] == 'invoice_number') {
					$suffix = (string)$this->get_number();
				} else {
					if (empty($this->order) && isset($args['order_ids'][0])) {
						$order = WCX::get_order($args['order_ids'][0]);
						$suffix = is_callable(array($order, 'get_order_number')) ? $order->get_order_number() : '';
					} else {
						$suffix = is_callable(array($this->order, 'get_order_number')) ? $this->order->get_order_number() : '';
					}
				}
			} else {
				$suffix = date('Y-m-d'); // 2020-11-11
			}

			$filename = $name . '-' . $suffix . '.pdf';

			// Filter filename
			$order_ids = isset($args['order_ids']) ? $args['order_ids'] : array($this->order_id);
			$filename = apply_filters('wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context);

			// sanitize filename (after filters to prevent human errors)!

			return sanitize_file_name($filename);
		}

		public function template_styles()
		{
			$css = apply_filters('wpo_wcpdf_template_styles_file', $this->locate_template_file("style.css"));

			ob_start();
			if (file_exists($css)) {
				extract(wpo_wcpdf_templates_get_footer_settings($this, '2cm')); // $footer_height & $page_bottom
				?>
                @page {
                margin-top: 1cm;
                margin-bottom: <?php echo $page_bottom; ?>;
                margin-left: 2cm;
                margin-right: 2cm;
                }

                #footer {
                position: absolute;
                bottom: -<?php echo $footer_height; ?>;
                left: 0;
                right: 0;
                height: <?php echo $footer_height; ?>;
                text-align: center;
                border-top: 0.1mm solid gray;
                margin-bottom: 0;
                padding-top: 2mm;
                }
				<?php
				include($css);
			}
			$css = ob_get_clean();
			$css = apply_filters('wpo_wcpdf_template_styles', $css, $this);

			echo $css;
		}

		public function get_orders_list()
		{
			$user_id = ($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
			return get_orders_list($user_id);
		}

		public function get_user_data()
		{
			$user_id = ($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
			$customer = [];
			global $countries;
			if ($user_id > 0) {
				$customer['billing_first_name'] = get_user_meta($user_id, 'billing_first_name', true);
				$customer['billing_last_name'] = get_user_meta($user_id, 'billing_last_name', true);
				$customer['billing_address_1'] = get_user_meta($user_id, 'billing_address_1', true);
				$customer['billing_address_2'] = get_user_meta($user_id, 'billing_address_2', true);
				$customer['billing_city'] = get_user_meta($user_id, 'billing_city', true);
				$customer['billing_state'] = get_user_meta($user_id, 'billing_state', true);
				$country = get_user_meta($user_id, 'billing_country', true);
				$customer['billing_country'] =(!empty($countries[$country]))?$countries[$country]:$country ;
				$customer['billing_postcode'] = get_user_meta($user_id, 'billing_postcode', true);
				$customer['billing_email'] = get_user_meta($user_id, 'billing_email', true);
				$customer['billing_phone'] = get_user_meta($user_id, 'billing_phone', true);
				$customer['billing_company'] = get_user_meta($user_id, 'billing_company', true);
				return $customer;
			}
		}

	}
endif; // class_exists

return new Statement();
