<?php


class parent_branch_user
{
	public function __construct()
	{
		add_action('init', [$this, 'run_init']);
	}

	public function run_init()
	{
		add_action('show_user_profile', [$this, 'my_user_profile_edit_action']);
		add_action('edit_user_profile', [$this, 'my_user_profile_edit_action']);

		add_action('personal_options_update', [$this, 'my_user_profile_update_action']);
		add_action('edit_user_profile_update', [$this, 'my_user_profile_update_action']);

	}

	function my_user_profile_edit_action($user)
	{

		$selected = (isset($user->parent_customer_id) && $user->parent_customer_id) ? $user->parent_customer_id : '';
		$users = get_users('orderby=nicename');

		?>
        <h2><?php esc_html_e('Parent branch', 'woocommerce-branches') ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="dropdown"><?php esc_html_e('Parent branch', 'woocommerce-branches') ?></label></th>
                <td>
                    <select name="parent_customer_id" id="parent_customer_id">
                        <option value=""><?php esc_html_e('Please Select', 'woocommerce-branches') ?></option>
						<?php foreach ($users as $user) : ?>
                            <option value="<?php echo $user->ID ?>" <?php echo $sel = ($selected == $user->ID) ? 'selected' : ''; ?> >
								<?php echo $user->display_name ?></option>
						<?php endforeach; ?>
                    </select>

                </td>
            </tr>
        </table>
		<?php
	}

	function my_user_profile_update_action($user_id)
	{
		update_user_meta($user_id, 'parent_customer_id', $_POST['parent_customer_id']);
	}

}

new parent_branch_user();

function show_data($val)
{
	echo '<pre>';
	print_r($val);
	echo '</pre>';
}