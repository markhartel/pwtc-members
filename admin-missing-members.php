<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if (!current_user_can($capability)) {
?> 
	<p><strong>Access Denied</strong> - you do not have the rights to view this page.</p>
<?php   
}
else {
    if (isset($_POST['_wpnonce'])) {
        if (wp_verify_nonce($_POST['_wpnonce'], 'pwtc_members_fix_missing_members')) {
            if (isset($_POST['fix_missing_members'])) {
                self::detect_missing_members(true);
            }
            if (isset($_POST['fix_invalid_current'])) {
                self::detect_invalid_current_members(true);
            }
            if (isset($_POST['fix_invalid_expired'])) {
                self::detect_invalid_expired_members(true);
            }
         }
    }
	
    $missing_members = self::detect_missing_members();
    $invalid_current_members = self::detect_invalid_current_members();
    $invalid_expired_members = self::detect_invalid_expired_members();
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 
});
</script>
    <div id="missing-members-section">
        <p>Use this page to detect any user accounts that have a membership but are missing the proper <code>current_member</code> or <code>expired_member</code> role. If any are found, you are given the option to fix these records.</p>
        <?php if (empty($missing_members)) { ?>
        <p>No users found with missing memberships roles.</p>
        <?php } else { ?>
        <table class="pwtc-members-rwd-table">
            <caption>Users With Missing Membership Roles</caption>
            <tr><th>User ID</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Actions</th></tr>
            <?php
            foreach ($missing_members as $item) {
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
        <div>
        <form method="POST">
            <?php wp_nonce_field('pwtc_members_fix_missing_members'); ?>
            <input type="submit" name="fix_missing_members" value="Fix These User Accounts" class="button button-primary button-large"/>
        </form>
        </div>
        <p><div class="msg-div"></div></p>
        <?php } ?>
	<hr>
        <?php if (empty($invalid_current_members)) { ?>
        <p>No users found with incorrect current membership roles.</p>
        <?php } else { ?>
        <table class="pwtc-members-rwd-table">
            <caption>Users With Incorrect Current Membership Roles</caption>
            <tr><th>User ID</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Actions</th></tr>
            <?php
            foreach ($invalid_current_members as $item) {
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
        <div>
        <form method="POST">
            <?php wp_nonce_field('pwtc_members_fix_missing_members'); ?>
            <input type="submit" name="fix_invalid_current" value="Fix These User Accounts" class="button button-primary button-large"/>
        </form>
        </div>
        <?php } ?>
        <hr>
        <?php if (empty($invalid_expired_members)) { ?>
        <p>No users found with incorrect expired membership roles.</p>
        <?php } else { ?>
        <table class="pwtc-members-rwd-table">
            <caption>Users With Incorrect Expired Membership Roles</caption>
            <tr><th>User ID</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Actions</th></tr>
            <?php
            foreach ($invalid_expired_members as $item) {
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
        <div>
        <form method="POST">
            <?php wp_nonce_field('pwtc_members_fix_missing_members'); ?>
            <input type="submit" name="fix_invalid_expired" value="Fix These User Accounts" class="button button-primary button-large"/>
        </form>
        </div>
         <?php } ?>
    </div>
<?php
}
?>
</div>
<?php
