<?php


class woocommerce_override_template
{
	public function __construct()
	{
		add_action('init', [$this, 'init_template']);
	}

	public function init_template()
	{
		add_filter('woocommerce_locate_template', [$this, 'woo_adon_plugin_template'], 1, 3);
	}

	public function woo_adon_plugin_template($template, $template_name, $template_path)
	{
		global $woocommerce;
		$_template = $template;
		if (!$template_path)
			$template_path = $woocommerce->template_url;

		$plugin_path = FLANCE_BRANCHES_PATH . '/template/woocommerce/';

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name
			)
		);

		if (file_exists($plugin_path . $template_name))
			$template = $plugin_path . $template_name;

		if (!$template)
			$template = $_template;

		return $template;
	}
}

new woocommerce_override_template();