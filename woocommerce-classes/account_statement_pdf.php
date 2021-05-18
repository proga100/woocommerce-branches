<?php

class account_statement_pdf
{
	public function __construct()
	{
		add_filter('wpo_wcpdf_document_classes', [$this, 'wpo_wcpdf_document_classes']);
	}

	public function wpo_wcpdf_document_classes($doc)
	{
		$doc['\WPO\WC\PDF_Invoices\Documents\Statement'] = include('plugins_overrides/woocommerce-pdf-ips-templates/documents/class-wcpdf-statement.php');
		return $doc;
	}

	public function generate_pdf()
	{

	}
}

$pdf = new account_statement_pdf();
$pdf->generate_pdf();
