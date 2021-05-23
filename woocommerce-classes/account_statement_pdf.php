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
		//add_filter( 'woocommerce_email_attachments', array( $this, 'attach_pdf_to_email' ), 99, 4 );
		add_filter('wpo_wcpdf_meta_box_actions', [$this, 'wpo_wcpdf_meta_box_actions'], 10, 2);
	}


	public function wpo_wcpdf_meta_box_actions($meta_box_actions, $post_id)
	{
		$order = wc_get_order($post_id);
		$meta_box_actions_modified = [];
		$parent_id = (get_post_meta($post_id, 'parent_id', true)) ? get_post_meta($post_id, 'parent_id', true) : $order->get_user_id();
		foreach ($meta_box_actions as $document_type => $data) {
			if ($document_type != 'statement') {
			//	$data['url'] = wp_nonce_url(admin_url("admin-ajax.php?action=generate_wpo_wcpdf&document_type={$document_type}&user_id=" . $parent_id), 'generate_wpo_wcpdf');
			$meta_box_actions_modified[$document_type] = $data;
			}

		}
		return $meta_box_actions_modified;
	}

	public function attach_pdf_to_email($attachments, $email_id, $order, $email = null)
	{
		// check if all variables properly set
		if (!is_object($order) || !isset($email_id)) {
			return $attachments;
		}

		// Skip User emails
		if (get_class($order) == 'WP_User') {
			return $attachments;
		}

		$order_id = WCX_Order::get_id($order);

		if (!($order instanceof \WC_Order || is_subclass_of($order, '\WC_Abstract_Order')) && $order_id == false) {
			return $attachments;
		}

		// WooCommerce Booking compatibility
		if (get_post_type($order_id) == 'wc_booking' && isset($order->order)) {
			// $order is actually a WC_Booking object!
			$order = $order->order;
			$order_id = WCX_Order::get_id($order);
		}

		// do not process low stock notifications, user emails etc!
		if (in_array($email_id, array('no_stock', 'low_stock', 'backorder', 'customer_new_account', 'customer_reset_password'))) {
			return $attachments;
		}

		// final check on order object
		if (!($order instanceof \WC_Order || is_subclass_of($order, '\WC_Abstract_Order'))) {
			return $attachments;
		}

		$tmp_path = $this->get_tmp_path('attachments');
		if (!@is_dir($tmp_path) || !wp_is_writable($tmp_path)) {
			return $attachments;
		}

		// clear pdf files from temp folder (from http://stackoverflow.com/a/13468943/1446634)
		// array_map('unlink', ( glob( $tmp_path.'*.pdf' ) ? glob( $tmp_path.'*.pdf' ) : array() ) );

		// disable deprecation notices during email sending
		add_filter('wcpdf_disable_deprecation_notices', '__return_true');

		// reload translations because WC may have switched to site locale (by setting the plugin_locale filter to site locale in wc_switch_to_site_locale())
		if (apply_filters('wpo_wcpdf_allow_reload_attachment_translations', true)) {
			WPO_WCPDF()->translations();
			do_action('wpo_wcpdf_reload_attachment_translations');
		}

		$attach_to_document_types = $this->get_documents_for_email($email_id, $order);
		foreach ($attach_to_document_types as $document_type) {
			$email_order = apply_filters('wpo_wcpdf_email_attachment_order', $order, $email, $document_type);
			$email_order_id = WCX_Order::get_id($email_order);

			do_action('wpo_wcpdf_before_attachment_creation', $email_order, $email_id, $document_type);

			try {
				// prepare document
				// we use ID to force to reloading the order to make sure that all meta data is up to date.
				// this is especially important when multiple emails with the PDF document are sent in the same session
				$document = wcpdf_get_document($document_type, (array)$email_order_id, true);
				if (!$document) { // something went wrong, continue trying with other documents
					continue;
				}
				$filename = $document->get_filename();
				$pdf_path = $tmp_path . $filename;

				$lock_file = apply_filters('wpo_wcpdf_lock_attachment_file', true);

				// if this file already exists in the temp path, we'll reuse it if it's not older than 60 seconds
				$max_reuse_age = apply_filters('wpo_wcpdf_reuse_attachment_age', 60);
				if (file_exists($pdf_path) && $max_reuse_age > 0) {
					// get last modification date
					if ($filemtime = filemtime($pdf_path)) {
						$time_difference = time() - $filemtime;
						if ($time_difference < $max_reuse_age) {
							// check if file is still being written to
							if ($lock_file && $this->wait_for_file_lock($pdf_path) === false) {
								$attachments[] = $pdf_path;
								continue;
							} else {
								// make sure this gets logged, but don't abort process
								wcpdf_log_error("Attachment file locked (reusing: {$pdf_path})", 'critical');
							}
						}
					}
				}

				// get pdf data & store
				$pdf_data = $document->get_pdf();

				if ($lock_file) {
					file_put_contents($pdf_path, $pdf_data, LOCK_EX);
				} else {
					file_put_contents($pdf_path, $pdf_data);
				}

				// wait for file lock
				if ($lock_file && $this->wait_for_file_lock($pdf_path) === true) {
					wcpdf_log_error("Attachment file locked ({$pdf_path})", 'critical');
				}

				$attachments[] = $pdf_path;

				do_action('wpo_wcpdf_email_attachment', $pdf_path, $document_type, $document);
			} catch (\Exception $e) {
				wcpdf_log_error($e->getMessage(), 'critical', $e);
				continue;
			} catch (\Dompdf\Exception $e) {
				wcpdf_log_error('DOMPDF exception: ' . $e->getMessage(), 'critical', $e);
				continue;
			} catch (\Error $e) {
				wcpdf_log_error($e->getMessage(), 'critical', $e);
				continue;
			}
		}

		remove_filter('wcpdf_disable_deprecation_notices', '__return_true');

		return $attachments;
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

		if (empty($_GET['order_ids']) && empty($_GET['user_id'])) {
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
		$user_id = (array)array_map('absint', explode('x', $_GET['user_id']));
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
