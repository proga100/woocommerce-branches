<?php

namespace WPO\WC\PDF_Invoices\Documents;

use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;



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
			$this->title = __('Account Statement', 'woocommerce-pdf-invoices-packing-slips');
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
			return apply_filters("wpo_wcpdf_{$this->slug}_title", __('Account Statement', 'woocommerce-pdf-invoices-packing-slips'), $this);
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

	}

endif; // class_exists

return new Statement();
