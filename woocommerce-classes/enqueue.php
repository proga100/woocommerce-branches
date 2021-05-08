<?php
function theme_enqueue_styles()
{
	wp_enqueue_style('woocommerce-style', FLANCE_BRANCHES_URL . '/assets/css/woocommerce-style.css', null, time(), 'all');
	//wp_enqueue_script('calculus', get_stylesheet_directory_uri() . '/assets/js/calculus.js', array( 'jquery' ), time());

}

add_action('wp_enqueue_scripts', 'theme_enqueue_styles');


function stm_admin_child_styles()
{

}

add_action('admin_enqueue_scripts', 'stm_admin_child_styles');