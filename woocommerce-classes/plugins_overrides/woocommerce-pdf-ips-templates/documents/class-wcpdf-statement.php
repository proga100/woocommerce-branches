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

            $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
            $name = esc_html__('statement', 'woocommerce-pdf-invoices-packing-slips');
            $suffix = date('Y-m-d'); // 2020-11-11


            // Filter filename
            $order_ids = isset($args['order_ids']) ? $args['order_ids'] : array($this->order_id);
            $filename = $name . '-user_id_' . $user_id . '-' . $suffix . '.pdf';

            $filename = apply_filters('wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context);

            // sanitize filename (after filters to prevent human errors)!

            return sanitize_file_name($filename);
        }

        public function template_styles()
        {
            if ($this->get_type() == 'statement') {
                $css = apply_filters('wpo_wcpdf_template_styles_file', $this->locate_template_file("style_statement.css"));
            } else {
                $css = apply_filters('wpo_wcpdf_template_styles_file', $this->locate_template_file("style.css"));
            }

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
            $user_id = $this->get_user_id();
            return get_orders_list($user_id);
        }

        public function get_user_id()
        {
            $user_id = ($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
            if ($user_id == 0) {
                $order = wc_get_order($this->order_id);
                $user_id = (get_post_meta($this->order_id, 'parent_id', true)) ? get_post_meta($this->order_id, 'parent_id', true) : $order->get_user_id();
            }
            return $user_id;
        }

        public function get_user_data()
        {
            $user_id = $this->get_user_id();
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
                $customer['billing_country'] = (!empty($countries[$country])) ? $countries[$country] : $country;
                $customer['billing_postcode'] = get_user_meta($user_id, 'billing_postcode', true);
                $customer['billing_email'] = get_user_meta($user_id, 'billing_email', true);
                $customer['billing_phone'] = get_user_meta($user_id, 'billing_phone', true);
                $customer['billing_company'] = get_user_meta($user_id, 'billing_company', true);
                return $customer;
            }
        }

        /**
         * Initialise settings
         */
        public function init_settings()
        {
            // Register settings.
            $page = $option_group = $option_name = 'wpo_wcpdf_documents_settings_statement';

            $settings_fields = array(
                array(
                    'type' => 'section',
                    'id' => 'statement',
                    'title' => '',
                    'callback' => 'section',
                ),
                array(
                    'type' => 'setting',
                    'id' => 'enabled',
                    'title' => __('Enable', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'enabled',
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'attach_to_email_ids',
                    'title' => __('Attach to:', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'multiple_checkboxes',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'attach_to_email_ids',
                        'fields' => $this->get_wc_emails(),
                        'description' => !is_writable(WPO_WCPDF()->main->get_tmp_path('attachments')) ? '<span class="wpo-warning">' . sprintf(__('It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email invoices.', 'woocommerce-pdf-invoices-packing-slips'), WPO_WCPDF()->main->get_tmp_path('attachments')) . '</span>' : '',
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'disable_for_statuses',
                    'title' => __('Disable for:', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'select',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'disable_for_statuses',
                        'options' => function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : array(),
                        'multiple' => true,
                        'enhanced_select' => true,
                        'placeholder' => __('Select one or more statuses', 'woocommerce-pdf-invoices-packing-slips'),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'display_shipping_address',
                    'title' => __('Display shipping address', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'select',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'display_shipping_address',
                        'options' => array(
                            '' => __('No', 'woocommerce-pdf-invoices-packing-slips'),
                            'when_different' => __('Only when different from billing address', 'woocommerce-pdf-invoices-packing-slips'),
                            'always' => __('Always', 'woocommerce-pdf-invoices-packing-slips'),
                        ),
                        // 'description'		=> __( 'Display shipping address (in addition to the default billing address) if different from billing address', 'woocommerce-pdf-invoices-packing-slips' ),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'display_email',
                    'title' => __('Display email address', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'display_email',
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'display_phone',
                    'title' => __('Display phone number', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'display_phone',
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'display_customer_notes',
                    'title' => __('Display customer notes', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'display_customer_notes',
                        'store_unchecked' => true,
                        'default' => 1,
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'display_date',
                    'title' => __('Display invoice date', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'select',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'display_date',
                        'options' => array(
                            '' => __('No', 'woocommerce-pdf-invoices-packing-slips'),
                            'invoice_date' => __('Invoice Date', 'woocommerce-pdf-invoices-packing-slips'),
                            'order_date' => __('Order Date', 'woocommerce-pdf-invoices-packing-slips'),
                        ),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'display_number',
                    'title' => __('Display invoice number', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'select',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'display_number',
                        'options' => array(
                            '' => __('No', 'woocommerce-pdf-invoices-packing-slips'),
                            'invoice_number' => __('Invoice Number', 'woocommerce-pdf-invoices-packing-slips'),
                            'order_number' => __('Order Number', 'woocommerce-pdf-invoices-packing-slips'),
                        ),
                        'description' => sprintf(
                            '<strong>%s</strong> %s <a href="https://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/invoice-numbers-explained/#why-is-the-pdf-invoice-number-different-from-the-woocommerce-order-number">%s</a>',
                            __('Warning!', 'woocommerce-pdf-invoices-packing-slips'),
                            __('Using the Order Number as invoice number is not recommended as this may lead to gaps in the invoice number sequence (even when order numbers are sequential).', 'woocommerce-pdf-invoices-packing-slips'),
                            __('More information', 'woocommerce-pdf-invoices-packing-slips')
                        ),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'next_invoice_number',
                    'title' => __('Next invoice number (without prefix/suffix etc.)', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'next_number_edit',
                    'section' => 'statement',
                    'args' => array(
                        'store' => 'invoice_number',
                        'size' => '10',
                        'description' => __('This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', 'woocommerce-pdf-invoices-packing-slips'),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'number_format',
                    'title' => __('Number format', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'multiple_text_input',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'number_format',
                        'fields' => array(
                            'prefix' => array(
                                'placeholder' => __('Prefix', 'woocommerce-pdf-invoices-packing-slips'),
                                'size' => 20,
                                'description' => __('to use the invoice year and/or month, use [invoice_year] or [invoice_month] respectively', 'woocommerce-pdf-invoices-packing-slips'),
                            ),
                            'suffix' => array(
                                'placeholder' => __('Suffix', 'woocommerce-pdf-invoices-packing-slips'),
                                'size' => 20,
                                'description' => '',
                            ),
                            'padding' => array(
                                'placeholder' => __('Padding', 'woocommerce-pdf-invoices-packing-slips'),
                                'size' => 20,
                                'type' => 'number',
                                'description' => __('enter the number of digits here - enter "6" to display 42 as 000042', 'woocommerce-pdf-invoices-packing-slips'),
                            ),
                        ),
                        'description' => __('note: if you have already created a custom invoice number format with a filter, the above settings will be ignored', 'woocommerce-pdf-invoices-packing-slips'),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'reset_number_yearly',
                    'title' => __('Reset invoice number yearly', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'reset_number_yearly',
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'my_account_buttons',
                    'title' => __('Allow My Account invoice download', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'select',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'my_account_buttons',
                        'options' => array(
                            'available' => __('Only when an invoice is already created/emailed', 'woocommerce-pdf-invoices-packing-slips'),
                            'custom' => __('Only for specific order statuses (define below)', 'woocommerce-pdf-invoices-packing-slips'),
                            'always' => __('Always', 'woocommerce-pdf-invoices-packing-slips'),
                            'never' => __('Never', 'woocommerce-pdf-invoices-packing-slips'),
                        ),
                        'custom' => array(
                            'type' => 'multiple_checkboxes',
                            'args' => array(
                                'option_name' => $option_name,
                                'id' => 'my_account_restrict',
                                'fields' => $this->get_wc_order_status_list(),
                            ),
                        ),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'invoice_number_column',
                    'title' => __('Enable invoice number column in the orders list', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'invoice_number_column',
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'disable_free',
                    'title' => __('Disable for free orders', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'disable_free',
                        'description' => sprintf(__("Disable document when the order total is %s", 'woocommerce-pdf-invoices-packing-slips'), function_exists('wc_price') ? wc_price(0) : 0),
                    )
                ),
                array(
                    'type' => 'setting',
                    'id' => 'use_latest_settings',
                    'title' => __('Always use most current settings', 'woocommerce-pdf-invoices-packing-slips'),
                    'callback' => 'checkbox',
                    'section' => 'statement',
                    'args' => array(
                        'option_name' => $option_name,
                        'id' => 'use_latest_settings',
                        'description' => __("When enabled, the document will always reflect the most current settings (such as footer text, document name, etc.) rather than using historical settings.", 'woocommerce-pdf-invoices-packing-slips')
                            . "<br>"
                            . __("<strong>Caution:</strong> enabling this will also mean that if you change your company name or address in the future, previously generated documents will also be affected.", 'woocommerce-pdf-invoices-packing-slips'),
                    )
                ),
            );

            WPO_WCPDF()->settings->add_settings_fields($settings_fields, $page, $option_group, $option_name);
            return;

        }


    }
endif; // class_exists

return new Statement();
