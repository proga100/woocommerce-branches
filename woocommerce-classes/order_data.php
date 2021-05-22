<?php
use WPO\WC\PDF_Invoices\Documents\Order_Document_Methods;

class order_data extends Order_Document_Methods
{
	/**
	 * Init/load the order object.
	 *
	 * @param int|object|WC_Order $order Order to init.
	 */
	public function __construct($order = 0)
	{
		// Call parent constructor
		parent::__construct($order);
	}
}