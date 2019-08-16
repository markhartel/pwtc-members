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

	function populate_end_detect_table(users) {
		$('#adjust-dates-section .adjust-end-date .msg-div').empty();
        if (users.length > 0) {
            $('#adjust-dates-section .adjust-end-date .msg-div').append(
                '<table class="pwtc-members-rwd-table"><tr><th>Email</th><th>First Name</th><th>Last Name</th><th>End Date</th><th>Family End</th></tr></table>');
            users.forEach(function(item) {
                $('#adjust-dates-section .adjust-end-date .msg-div table').append(
                    '<tr userid="' + item.userid + '">' + 
                    '<td data-th="Email">' + item.user_email + '</td>' +
                    '<td data-th="First Name">' + item.first_name + '</td>' +
                    '<td data-th="Last Name">' + item.last_name + '</td>' + 
                    '<td data-th="End Date">' + item.end_date + '</td>' +
                    '<td data-th="Family End">' + item.team_end + '</td>' +
                    '</tr>');    
            });
            $('#adjust-dates-section .adjust-end-date .adjust-frm').show();
        }
        else {
            $('#adjust-dates-section .adjust-end-date .msg-div').html('No mismatches detected.');
        }
    }

	function populate_start_detect_table(users) {
		$('#adjust-dates-section .adjust-start-date .msg-div').empty();
        if (users.length > 0) {
            $('#adjust-dates-section .adjust-start-date .msg-div').append(
                '<table class="pwtc-members-rwd-table"><tr><th>Email</th><th>First Name</th><th>Last Name</th><th>Rider ID</th><th>Start Date</th></tr></table>');
            users.forEach(function(item) {
                $('#adjust-dates-section .adjust-start-date .msg-div table').append(
                    '<tr userid="' + item.userid + '">' + 
                    '<td data-th="Email">' + item.user_email + '</td>' +
                    '<td data-th="First Name">' + item.first_name + '</td>' +
                    '<td data-th="Last Name">' + item.last_name + '</td>' + 
                    '<td data-th="Rider ID">' + item.riderid + '</td>' +
                    '<td data-th="Start Date">' + item.startdate + '</td>' +
                    '</tr>');    
            });
            $('#adjust-dates-section .adjust-start-date .adjust-frm').show();
        }
        else {
            $('#adjust-dates-section .adjust-start-date .msg-div').html('No mismatches detected.');
        }
    }

	function adjust_end_cb(response) {
        var res = JSON.parse(response);
        $('#adjust-dates-section .adjust-end-date .msg-div').html(res.status);
        $('#adjust-dates-section .adjust-end-date .adjust-frm').hide();
    }

	function detect_end_cb(response) {
        var res = JSON.parse(response);
        if (res.users) {
            populate_end_detect_table(res.users);
        }
        else {
            $('#adjust-dates-section .adjust-end-date .msg-div').html(res.status);
        }
    }

	function adjust_start_cb(response) {
        var res = JSON.parse(response);
        $('#adjust-dates-section .adjust-start-date .msg-div').html(res.status);
        $('#adjust-dates-section .adjust-start-date .adjust-frm').hide();
    }

	function detect_start_cb(response) {
        var res = JSON.parse(response);
        if (res.users) {
            populate_start_detect_table(res.users);
        }
        else {
            $('#adjust-dates-section .adjust-start-date .msg-div').html(res.status);
        }
    }

    $('#adjust-dates-section .adjust-end-date .adjust-frm').on('submit', function(evt) {
        evt.preventDefault();
        $('#adjust-dates-section .adjust-end-date .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');     
        var action = $('#adjust-dates-section .adjust-end-date .adjust-frm').attr('action');
        var data = {
            'action': 'pwtc_members_adjust_family_members',
            'nonce': '<?php echo wp_create_nonce('pwtc_members_adjust_family_members'); ?>',
            'detect_only': false
        };
        $.post(action, data, adjust_end_cb);
    });

    $('#adjust-dates-section .adjust-start-date .adjust-frm').on('submit', function(evt) {
        evt.preventDefault();
        $('#adjust-dates-section .adjust-start-date .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
        var action = $('#adjust-dates-section .adjust-start-date .adjust-frm').attr('action');
        var data = {
            'action': 'pwtc_members_adjust_member_since_date',
            'nonce': '<?php echo wp_create_nonce('pwtc_members_adjust_member_since_date'); ?>',
            'detect_only': false
        };
        $.post(action, data, adjust_start_cb);
    });

    $('#adjust-dates-section .adjust-end-date .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
    var data = {
        'action': 'pwtc_members_adjust_family_members',
        'nonce': '<?php echo wp_create_nonce('pwtc_members_adjust_family_members'); ?>',
        'detect_only': true
    };
    $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, detect_end_cb);

    $('#adjust-dates-section .adjust-start-date .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
    data = {
        'action': 'pwtc_members_adjust_member_since_date',
        'nonce': '<?php echo wp_create_nonce('pwtc_members_adjust_member_since_date'); ?>',
        'detect_only': true
    };
    $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, detect_start_cb);

});
</script>
    <div id="adjust-dates-section">
        <div class="adjust-end-date">
        <p>Detect all members whos expiration date does not match that of the family membership to which they belong.</p>
        <p><div class="msg-div"></div></p>
        <form class="adjust-frm pwtc-members-hidden" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
            <input type="submit" value="Fix This" class="button button-primary button-large"/>
        </form>
        </div>
        <div class="adjust-start-date">
        <p>Detect all members whos start date does not match the year that their rider ID was issued.</p>
        <p><div class="msg-div"></div></p>
        <form class="adjust-frm pwtc-members-hidden" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
            <input type="submit" value="Fix This" class="button button-primary button-large"/>
        </form>
        </div>
    </div>
<?php
}
?>
</div>
<?php
