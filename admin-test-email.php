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
        if (res.status) {
            $('#test-email-section .msg-div').html(res.status);
		}
		else {
            $('#test-email-section .msg-div').empty();
            $('#test-email-section .msg-div').append('<h3>To:</h3><div>' + res.to + '</div>');
            $('#test-email-section .msg-div').append('<h3>Subject:</h3><div>' + res.subject + '</div>');
            $('#test-email-section .msg-div').append('<h3>Message:</h3><div>' + res.message + '</div>');
            $('#test-email-section .msg-div').append('<h3>Headers:</h3>');
            res.headers.forEach(function(item) {
                $('#test-email-section .msg-div').append('<div>' + item + '</div>');
            });
        }   
    }

    $('#test-email-section .email-frm').on('submit', function(evt) {
        evt.preventDefault();
        var member_email = $("#test-email-section .email-frm input[name='member_email']").val().trim();
        var email_to = $("#test-email-section .email-frm input[name='email_to']").val().trim();
        if (member_email.length > 0) {
            $('#test-email-section .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
            var action = $('#test-email-section .email-frm').attr('action');
            var data = {
                'action': 'pwtc_members_send_test_email',
                'member_email': member_email,
                'email_to': email_to
            };
            $.post(action, data, send_email_cb);
        }
        else {
            $('#test-email-section .msg-div').empty();
        }
    });

    $("#test-email-section .email-frm input[type='text']").val('');   
    $("#test-email-section .email-frm input[name='member_email']").focus();

});
</script>
    <div id="test-email-section">
        <p>Use this page to test the membership confirmation email mechanism. Leave the <strong>Email Confirmation To</strong> field blank to inspect the confirmation email content, no email will be sent.</p>        
        <div class="pwtc-members-search-sec">
            <form class="pwtc-members-stacked-form email-frm" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
                <span>Member Email</span>
                <input name="member_email" type="text" required/>
                <span>Email Confirmation To</span>
                <input name="email_to" type="text"/>
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
