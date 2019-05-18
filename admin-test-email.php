<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if (!current_user_can($capability)) {
?> 
	<p><strong>Access Denied</strong> - you do not have the rights to view this page.</p>
<?php   
}
else {
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 

	function send_email_cb(response) {
        var res = JSON.parse(response);
        $('#test-email-section .msg-div').html(res.message);
    }

    $('#test-email-section .email-frm').on('submit', function(evt) {
        evt.preventDefault();
        var member_email = $("#test-email-section .email-frm input[name='member_email']").val().trim();
        var email_to = $("#test-email-section .email-frm input[name='email_to']").val().trim();
        $('#test-email-section .msg-div').empty();
        if (member_email.length > 0 || email_to.length > 0) {
            var action = $('#test-email-section .email-frm').attr('action');
            var data = {
                'action': 'pwtc_members_send_test_email',
                'member_email': member_email,
                'email_to': email_to
            };
            $.post(action, data, send_email_cb);
        }
    });

    $("#test-email-section .email-frm input[type='text']").val('');   
    $("#test-email-section .email-frm input[name='member_email']").focus();

});
</script>
    <div id="test-email-section">
        <div class="pwtc-members-search-sec">
            <form class="pwtc-members-stacked-form email-frm" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
                <span>Member Email</span>
                <input name="member_email" type="text" required/>
                <span>Email Confirmation To</span>
                <input name="email_to" type="text" required/>
				<input class="button button-primary" type="submit" value="Send Email"/>
            </form>
        </div>
        <p><div class="msg-div"></div></p>
    </div>
<?php
}
?>
</div>
<?php
