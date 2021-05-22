<?php

class account_statement_pdf
{
	public function __construct()
	{
		add_action('admin_enqueue_scripts', [$this, 'stm_admin_child_styles']);
		add_filter('wpo_wcpdf_document_classes', [$this, 'wpo_wcpdf_document_classes']);
		remove_all_actions('wp_ajax_generate_wpo_wcpdf');
		remove_all_actions('wp_ajax_nopriv_generate_wpo_wcpdf');
		add_action('wp_ajax_generate_wpo_wcpdf', array($this, 'generate_pdf_ajax'));
		add_action('wp_ajax_nopriv_generate_wpo_wcpdf', array($this, 'generate_pdf_ajax'));
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

	/**
	 * Load and generate the template output with ajax
	 */
	public function generate_pdf_ajax()
	{
		$guest_access = isset(WPO_WCPDF()->settings->debug_settings['guest_access']);
		if (!$guest_access && current_filter() == 'wp_ajax_nopriv_generate_wpo_wcpdf') {
			wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-pdf-invoices-packing-slips'));
		}

		// Check the nonce - guest access doesn't use nonces but checks the unique order key (hash)
		if (empty($_GET['action']) || (!$guest_access && !check_admin_referer($_GET['action']))) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-pdf-invoices-packing-slips'));
		}

		// Check if all parameters are set
		if (empty($_GET['document_type']) && !empty($_GET['template_type'])) {
			$_GET['document_type'] = $_GET['template_type'];
		}
		echo $_GET['user_id'];

		if (empty($_GET['order_ids'] ) && empty($_GET['user_id'])) {
			wp_die(__("You haven't selected any orders", 'woocommerce-pdf-invoices-packing-slips'));
		}

		if (empty($_GET['document_type'])) {
			wp_die(__('Some of the export parameters are missing.', 'woocommerce-pdf-invoices-packing-slips'));
		}

		// debug enabled by URL
		if (isset($_GET['debug']) && !($guest_access || isset($_GET['my-account']))) {
			$this->enable_debug();
		}

		// Generate the output
		$document_type = sanitize_text_field($_GET['document_type']);

		$order_ids = (array)array_map('absint', explode('x', $_GET['order_ids']));

		// Process oldest first: reverse $order_ids array if required
		if (count($order_ids) > 1 && end($order_ids) < reset($order_ids)) {
			$order_ids = array_reverse($order_ids);
		}

		// set default is allowed
		$allowed = true;

		if ($guest_access && isset($_GET['order_key'])) {
			// Guest access with order key
			if (count($order_ids) > 1) {
				$allowed = false;
			} else {
				$order = wc_get_order($order_ids[0]);
				if (!$order || !hash_equals($order->get_order_key(), $_GET['order_key'])) {
					$allowed = false;
				}
			}
		} else {
			// check if user is logged in
			if (!is_user_logged_in()) {
				$allowed = false;
			}

			// Check the user privileges
			if (!(current_user_can('manage_woocommerce_orders') || current_user_can('edit_shop_orders')) && !isset($_GET['my-account'])) {
				$allowed = false;
			}

			// User call from my-account page
			if (!current_user_can('manage_options') && isset($_GET['my-account'])) {
				// Only for single orders!
				if (count($order_ids) > 1) {
					$allowed = false;
				}

				// Check if current user is owner of order IMPORTANT!!!
				if (!current_user_can('view_order', $order_ids[0])) {
					$allowed = false;
				}
			}
		}

		$allowed = apply_filters('wpo_wcpdf_check_privs', $allowed, $order_ids);

		if (!$allowed) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-pdf-invoices-packing-slips'));
		}

		// if we got here, we're safe to go!
		try {
			$document = wcpdf_get_document($document_type, $order_ids, true);

			if ($document) {
				do_action('wpo_wcpdf_document_created_manually', $document, $order_ids); // note that $order_ids is filtered and may not be the same as the order IDs used for the document (which can be fetched from the document object itself)

				$output_format = WPO_WCPDF()->settings->get_output_format($document_type);
				// allow URL override
				if (isset($_GET['output']) && in_array($_GET['output'], array('html', 'pdf'))) {
					$output_format = $_GET['output'];
				}

				switch ($output_format) {
					case 'html':
						add_filter('wpo_wcpdf_use_path', '__return_false');
						$document->output_html();
						break;
					case 'pdf':
					default:
						if (has_action('wpo_wcpdf_created_manually')) {
							do_action('wpo_wcpdf_created_manually', $document->get_pdf(), $document->get_filename());
						}
						$output_mode = WPO_WCPDF()->settings->get_output_mode($document_type);
						$document->output_pdf($output_mode);
						break;
				}
			} else {
				wp_die(sprintf(__("Document of type '%s' for the selected order(s) could not be generated", 'woocommerce-pdf-invoices-packing-slips'), $document_type));
			}
		} catch (\Dompdf\Exception $e) {
			$message = 'DOMPDF Exception: ' . $e->getMessage();
			wcpdf_log_error($message, 'critical', $e);
			wcpdf_output_error($message, 'critical', $e);
		} catch (\Exception $e) {
			$message = 'Exception: ' . $e->getMessage();
			wcpdf_log_error($message, 'critical', $e);
			wcpdf_output_error($message, 'critical', $e);
		} catch (\Error $e) {
			$message = 'Fatal error: ' . $e->getMessage();
			wcpdf_log_error($message, 'critical', $e);
			wcpdf_output_error($message, 'critical', $e);
		}
		exit;
	}

}

$pdf = new account_statement_pdf();
$pdf->generate_pdf();
