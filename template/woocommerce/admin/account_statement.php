<?php if (!empty($customers)) :
	?>
    <div class="col-xs-8 col-sm-7 col-lg-10">
        <table class="table display stripe hover row-border order-column" id="table_id_1">

            <thead>
            <tr>
                <th data-sort="string">Name</th>
                <th data-sort="string">Email</th>
                <th data-sort="float">Total Current Overdue</th>
                <th data-sort="string">Account Statement</th>
                <th data-sort="string">Email</th>
            </tr>
            </thead>

            <tbody>

			<?php
			$nonce = wp_create_nonce('generate_wpo_wcpdf');
			foreach ($customers as $user) : ?>
				<?php $customer = new WP_User($user->ID); ?>
                <tr>
                    <td><?php echo $user->display_name; ?></td>
                    <td><?php echo $user->user_email; ?></td>
                    <td><?php echo wc_price($this->get_total_order($user->ID)); ?></td>
                    <td class="cl-pdf"><a class="btn btn-success" href="<?php echo get_admin_url() . 'admin-ajax.php?action=generate_wpo_wcpdf&document_type=statement&_wpnonce=' . $nonce . '&user_id=' . $user->ID; ?>">
                             <span class="dashicons dashicons-media-interactive"></span> PDF</a></td>
                    <td class="cl-pdf"><a class="btn btn-warning" href="">
                             <span class="dashicons dashicons-email"></span> Send</a></td>
                </tr>

			<?php endforeach; ?>

            </tbody>
        </table>

    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('#table_id_1').DataTable();
        });
    </script>

<?php endif; ?>
