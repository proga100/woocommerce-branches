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

          <?php foreach ($customers as $user) : ?>

               <?php $customer = new WP_User($user->ID); ?>

               <tr>
                    <td><?php echo $user->display_name; ?></td>
                    <td><?php echo $user->user_email; ?></td>
                    <td><?php echo wc_get_customer_total_spent($user->ID); ?></td>
                     <td> <a href="<?php echo get_admin_url().'admin.php?page=flance_user_account_statement&user_id='.$user->ID; ?>" >Customer Account page </a></td>
               </tr>

          <?php endforeach; ?>

          </tbody>
     </table>

<?php endif; ?>
