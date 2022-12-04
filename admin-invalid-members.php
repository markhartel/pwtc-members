<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if (!current_user_can($capability)) {
?> 
	<p><strong>Access Denied</strong> - you do not have the rights to view this page.</p>
<?php   
}
else {
    $invalid_members = array();
    $test_users = self::fetch_member_role_users();
    $results = PwtcMembers::fetch_users_with_no_memberships();
    foreach ($results as $item) {
        $userid = $item[0];
        if (in_array($userid, $test_users)) {
            $invalid_members[] = $userid;
        }
    }
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 

	function fix_this_cb(response) {
        var res = JSON.parse(response);
        $('#invalid-members-section .msg-div').html(res.status);
    }

    $('#invalid-members-section .fix-frm').on('submit', function(evt) {
        evt.preventDefault();
        $('#invalid-members-section .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
        var action = $('#invalid-members-section .fix-frm').attr('action');
        var data = {
            'action': 'pwtc_members_fix_invalid_members',
            'nonce': '<?php echo wp_create_nonce('pwtc_members_fix_invalid_members'); ?>'
        };
        $.post(action, data, fix_this_cb);
    });

});
</script>
    <div id="invalid-members-section">
        <p>Use this page to detect any user accounts that do not have a membership but are erroneously marked with the <code>current_member</code> or <code>expired_member</code> role. If any are found, you are given the option to fix these records.</p>
        <?php if (empty($invalid_members)) { ?>
        <p>No users found with invalid memberships roles.</p>
        <?php } else { ?>
        <table class="pwtc-members-rwd-table">
            <caption>Users With Invalid Membership Roles</caption>
            <tr><th>User ID</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Actions</th></tr>
            <?php
            foreach ($invalid_members as $item) {
                $userid = $item;
                $user_info = get_userdata( $userid ); 
                if ($user_info) {
                    $edit_url = admin_url('user-edit.php?user_id=' . $userid);       
            ?>
			<tr>
				<td data-th="ID"><?php echo $userid; ?></td>
				<td data-th="Email"><?php echo $user_info->user_email; ?></td>
				<td data-th="First"><?php echo $user_info->first_name; ?></td>
                <td data-th="Last"><?php echo $user_info->last_name; ?></td>
                <td data-th="Actions"><a title="Edit user account profile." href="<?php echo $edit_url; ?>" target="_blank">Edit</a></td>
            </tr>
            <?php 
                }
            } 
            ?>
        </table>
        <p>Fix the user accounts listed above.</p>
        <div>
        <form class="fix-frm" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
            <input type="submit" value="Fix This" class="button button-primary button-large"/>
        </form>
        </div>
        <p><div class="msg-div"></div></p>
        <?php } ?>
    </div>
<?php
}
?>
</div>
<?php
