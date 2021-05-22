<?php

class account_statement_pdf
{
	public function __construct()
	{
		add_action('admin_enqueue_scripts', [$this, 'stm_admin_child_styles']);
		add_filter('wpo_wcpdf_document_classes', [$this, 'wpo_wcpdf_document_classes']);
	}

	public function wpo_wcpdf_document_classes($doc)
	{
		$doc['\WPO\WC\PDF_Invoices\Documents\Statement'] = include('plugins_overrides/woocommerce-pdf-ips-templates/documents/class-wcpdf-statement.php');
		return $doc;
	}

	public function stm_admin_child_styles()
	{
		wp_enqueue_style('woocommerce-pdf-style', FLANCE_BRANCHES_URL . '/assets/css/woocommerce-pdf-style.css', null, time(), 'all');

	}

	public function generate_pdf()
	{

	}
}

$pdf = new account_statement_pdf();
$pdf->generate_pdf();
