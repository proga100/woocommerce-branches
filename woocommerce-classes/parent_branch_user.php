<?php


class parent_branch_user
{
	public function __constructor()
	{
		add_action('init', [$this, 'run_init']);
	}

	public function run_init()
	{
		add_action('show_user_profile', [$this, 'my_user_profile_edit_action']);
		add_action('edit_user_profile', [$this, 'my_user_profile_edit_action']);
		exit;
	}

	function my_user_profile_edit_action($user)
	{
		$checked = (isset($user->artwork_approved) && $user->artwork_approved) ? ' checked="checked"' : '';
		?>
        <h3>Other</h3>
        <label for="artwork_approved">
            <input name="artwork_approved" type="checkbox" id="artwork_approved" value="1"<?php echo $checked; ?>>
            Artwork approved
        </label>
		<?php
	}

}

new parent_branch_user();