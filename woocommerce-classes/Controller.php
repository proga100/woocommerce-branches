<?php
/**
 * REST API Reports customers controller
 *
 * Handles requests to the /reports/customers endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Customers;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\ExportableTraits;
use \Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use \Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
 use Automattic\WooCommerce\Admin\API\Reports\Customers\Controller;
/**
 * REST API Reports customers controller class.
 *
 * @extends WC_REST_Reports_Controller
 */
class Controller_child extends Controller {


	function prepare_reports_query( $request ) {
		$args                        = array();
		$args['registered_before']   = $request['registered_before'];
		$args['registered_after']    = $request['registered_after'];
		$args['order_before']        = $request['before'];
		$args['order_after']         = $request['after'];
		$args['page']                = $request['page'];
		$args['per_page']            = $request['per_page'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
		$args['match']               = $request['match'];
		$args['search']              = $request['search'];
		$args['searchby']            = $request['searchby'];
		$args['name_includes']       = $request['name_includes'];
		$args['name_excludes']       = $request['name_excludes'];
		$args['username_includes']   = $request['username_includes'];
		$args['username_excludes']   = $request['username_excludes'];
		$args['email_includes']      = $request['email_includes'];
		$args['email_excludes']      = $request['email_excludes'];
		$args['country_includes']    = $request['country_includes'];
		$args['country_excludes']    = $request['country_excludes'];
		$args['last_active_before']  = $request['last_active_before'];
		$args['last_active_after']   = $request['last_active_after'];
		$args['orders_count_min']    = $request['orders_count_min'];
		$args['orders_count_max']    = $request['orders_count_max'];
		$args['total_spend_min']     = $request['total_spend_min'];
		$args['total_spend_max']     = $request['total_spend_max'];
		$args['avg_order_value_min'] = $request['avg_order_value_min'];
		$args['avg_order_value_max'] = $request['avg_order_value_max'];
		$args['last_order_before']   = $request['last_order_before'];
		$args['last_order_after']    = $request['last_order_after'];
		$args['customers']           = $request['customers'];

		$between_params_numeric    = array( 'orders_count', 'total_spend', 'avg_order_value' );
		$normalized_params_numeric = TimeInterval::normalize_between_params( $request, $between_params_numeric, false );
		$between_params_date       = array( 'last_active', 'registered' );
		$normalized_params_date    = TimeInterval::normalize_between_params( $request, $between_params_date, true );
		$args                      = array_merge( $args, $normalized_params_numeric, $normalized_params_date );

		return $args;
	}

}
