<?php


class AccountStatement
{
	public function __construct()
	{
		//add_action('admin_menu', array($this, 'menu'), 999); // Add menu
		//
		add_action('init', [$this, 'init']);
	}
	public function init(){
		add_filter( 'woocommerce_rest_prepare_report_customers', [$this, 'woocommerce_rest_prepare_report_customers'],10,3 );
	}

	function woocommerce_rest_prepare_report_customers($response, $report, $request){
		$response->data['account_statement'] = 'yandex.com';
		return $response;
	}

	public function menu()
	{
		$parent_slug = 'woocommerce';

		$this->options_page_hook = add_submenu_page(
			$parent_slug,
			__('Account Statement', 'woocommerce-branches'),
			__('Account Statement', 'woocommerce-branches'),
			'manage_woocommerce',
			'flance_account_page',
			array($this, 'settings_page')
		);
	}

	public function settings_page()
	{
		echo "tests";
	}
}

new AccountStatement();