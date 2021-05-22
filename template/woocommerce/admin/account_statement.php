<?php if (! empty($customers)) :
    ?>

     <table class="table">

          <thead>
                    <tr>
                         <th data-sort="string">Name</th>
                         <th data-sort="string">Email</th>
                         <th data-sort="float">Total Current Overdue</th>
                        <th data-sort="string">Account  Statement</th>
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
                    <td> <a href="<?php echo get_admin_url().'admin-ajax.php?action=generate_wpo_wcpdf&document_type=statement&_wpnonce='.$nonce.'&user_id='.$user->ID; ?>" >Account Statement PDF</a></td>
               </tr>

          <?php endforeach; ?>

          </tbody>
     </table>

<?php endif; ?>
