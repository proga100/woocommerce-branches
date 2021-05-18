<?php if (!empty($_GET['user_id'])) : ?>
	<?php
	$user_id = sanitize_text_field($_GET['user_id']);
	$user = new WP_User($user_id);
	//update_post_meta(28177, 'user_id', $user_id );
	$account_statements = get_posts(array(
		'post_type' => 'account_statement',
		'posts_per_page' => -1,
		'meta_key' => 'user_id',
		'meta_value' => $user_id,
		'meta_compare' => '='
	));
	show_data($account_statements);
	?>
    <table class="table">
        <thead>
        <tr>
            <th data-sort="string">Name</th>
            <th data-sort="string">Email</th>
            <th data-sort="float">Total spent (<?php echo get_option('woocommerce_currency'); ?>)</th>
        </tr>
        </thead>

        <tbody>
        <tr>
            <td><?php echo $user->display_name; ?></td>
            <td><?php echo $user->user_email; ?></td>
            <td><?php echo wc_get_customer_total_spent($user->ID); ?></td>
        </tr>
        </tbody>
    </table>
<?php endif; ?>
