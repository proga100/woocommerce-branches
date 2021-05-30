<?php

use Automattic\WooCommerce\Admin\API\Reports\Customers\Controller;
use  Automattic\WooCommerce\Admin\API\Reports\Customers\Query;
if (class_exists('Automattic\WooCommerce\Admin\API\Reports\Customers\Controller')) {
    class AccountStatement extends Controller
    {
        public $request = [];

        public function __construct()
        {
            add_action('admin_menu', array($this, 'menu'), 999); // Add menu
            add_action('init', [$this, 'init']);
            add_action('init', [$this, 'account_statement_post_type']);
        }

        public function init()
        {
            //add_filter( 'woocommerce_rest_prepare_report_customers', [$this, 'woocommerce_rest_prepare_report_customers'],10,3 );
        }

        function woocommerce_rest_prepare_report_customers($response, $report, $request)
        {
            $response->data['account_statement'] = 'yandex.com';
            return $response;
        }

        public function menu()
        {
            $this->options_page_hook = add_submenu_page(
                'woocommerce',
                __('Account Statement', 'woocommerce-branches'),
                __('Account Statement', 'woocommerce-branches'),
                'manage_woocommerce',
                'flance_account_page',
                array($this, 'account_statement_page')
            );

            $this->options_page_hook = add_submenu_page(
                'flance_account_page',
                __('Account Statement', 'woocommerce-branches'),
                __('Account Statement', 'woocommerce-branches'),
                'manage_woocommerce',
                'flance_user_account_statement',
                array($this, 'user_account_statement_page')
            );
        }

        public function account_statement_page()
        {
            wp_enqueue_style('datatable_style', FLANCE_BRANCHES_URL . 'assets/datatable/jquery.dataTables.css', null, time(), 'all');
            wp_enqueue_style('bootstrapstyle', FLANCE_BRANCHES_URL . 'assets/datatable/bootstrap.min.css');
            wp_enqueue_style('bootstrapthemestyle', FLANCE_BRANCHES_URL . 'assets/datatable/bootstrap-theme.min.css');
            wp_enqueue_script('bootstrap-script', FLANCE_BRANCHES_URL . 'assets/datatable/bootstrap.min.js', array(), true);
            wp_enqueue_script('datatable_script', FLANCE_BRANCHES_URL . 'assets/datatable/jquery.dataTables.js', array('jquery'), 'all');

            $this->locate_template('account_statement');
        }

        public function user_account_statement_page()
        {
            $this->locate_template('user_account_statement');
        }

        public function get_total_order($user_id)
        {
            $orders = get_orders_list($user_id);
            return get_order_total($orders);
        }

        public function locate_template($file)
        {
            $request = [];
            $this->get_request();
            $customers = $this->get_items($request);
            include_once FLANCE_BRANCHES_PATH . "/template/woocommerce/admin/$file.php";
        }

        public function get_request()
        {

            $current_page = (!empty($_GET['paged'])) ? $_GET['paged'] : 1;
            $users_per_page = (!empty($_GET['posts_per_page'])) ? $_GET['posts_per_page'] : -1;
            $this->request = array(
                'order' => 'ASC',
                'number' => $users_per_page,
                'paged' => $current_page
            );
        }

        public function get_items($request = [])
        {
            $query_args = $this->request;

            $customers_query = new WP_User_Query($query_args);

            $customers = $customers_query->get_results();

            return $customers;
        }

        public function get_data()
        {
            $args = $this->get_query_vars();
            $data_store = \WC_Data_Store::load('report-customers');
            $results = $data_store->get_data($args);
            show_data($results);
            return $results;
        }


        /*
        * Creating a function to create our CPT
        */

        function account_statement_post_type()
        {

// Set UI labels for Custom Post Type
            $labels = array(
                'name' => _x('Account Statement', 'Post Type General Name', 'wordpress-branches'),
                'singular_name' => _x('Account Statement', 'Post Type Singular Name', 'wordpress-branches'),
                'menu_name' => __('Account Statement', 'wordpress-branches'),
                'all_items' => __('All Account Statement', 'wordpress-branches'),
                'view_item' => __('View Account Statement', 'wordpress-branches'),
                'search_items' => __('Search Account Statement', 'wordpress-branches'),
                'not_found' => __('Not Found', 'wordpress-branches'),
                'not_found_in_trash' => __('Not found in Trash', 'wordpress-branches'),
            );

            $args = array(
                'label' => __('Account Statement', 'wordpress-branches'),
                'labels' => $labels,
                // Features this CPT supports in Post Editor
                'supports' => array('title', 'custom-fields',),
                // You can associate this CPT with a taxonomy or custom taxonomy.
                'taxonomies' => array('users'),
                /* A hierarchical CPT is like Pages and can have
                * Parent and child items. A non-hierarchical CPT
                * is like Posts.
                */
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => false,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => true,
                'menu_position' => 5,
                'can_export' => true,
                'has_archive' => true,
                'exclude_from_search' => false,
                'publicly_queryable' => true,
                'capability_type' => 'post',
                'show_in_rest' => true,

            );

            // Registering your Custom Post Type
            register_post_type('account_statement', $args);

        }

    }

    new AccountStatement();

}