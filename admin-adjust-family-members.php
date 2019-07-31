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

	function adjust_end_cb(response) {
        var res = JSON.parse(response);
        $('#adjust-dates-section .adjust-end-date .msg-div').html(res.status);
    }

	function adjust_start_cb(response) {
        var res = JSON.parse(response);
        $('#adjust-dates-section .adjust-start-date .msg-div').html(res.status);
    }

    $('#adjust-dates-section .adjust-end-date .adjust-frm').on('submit', function(evt) {
        evt.preventDefault();
        $('#adjust-dates-section .adjust-end-date .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
        var detect_only = false;
        if ($("#adjust-dates-section .adjust-end-date .adjust-frm input[name='detect_only']").is(':checked')) {
            detect_only = true;
        }        
        var action = $('#adjust-dates-section .adjust-end-date .adjust-frm').attr('action');
        var data = {
            'action': 'pwtc_members_adjust_family_members',
            'nonce': '<?php echo wp_create_nonce('pwtc_members_adjust_family_members'); ?>',
            'detect_only': detect_only
        };
        $.post(action, data, adjust_end_cb);
    });

    $('#adjust-dates-section .adjust-start-date .adjust-frm').on('submit', function(evt) {
        evt.preventDefault();
        $('#adjust-dates-section .adjust-start-date .msg-div').html('<i class="fa fa-spinner fa-pulse"></i> Please wait...');
        var detect_only = false;
        if ($("#adjust-dates-section .adjust-start-date .adjust-frm input[name='detect_only']").is(':checked')) {
            detect_only = true;
        }        
        var action = $('#adjust-dates-section .adjust-start-date .adjust-frm').attr('action');
        var data = {
            'action': 'pwtc_members_adjust_member_since_date',
            'nonce': '<?php echo wp_create_nonce('pwtc_members_adjust_member_since_date'); ?>',
            'detect_only': detect_only
        };
        $.post(action, data, adjust_start_cb);
    });

});
</script>
    <div id="adjust-dates-section">
        <div class="adjust-end-date">
        <p>Adjust all memberships so that their expires date matches that of the family membership to which they belong.</p>
        <form class="adjust-frm  pwtc-members-stacked-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
            <span>Detect Only</span>
            <span class="pwtc-members-checkbox-wrap">
			    <input type="checkbox" name="detect_only" checked/>
		    </span>
            <input type="submit" value="Adjust" class="button button-primary button-large"/>
        </form>
        <p><div class="msg-div"></div></p>
        </div>
        <div class="adjust-start-date">
        <p>Adjust all memberships so that their member since date matches the year that their rider ID was issued.</p>
        <form class="adjust-frm pwtc-members-stacked-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
            <span>Detect Only</span>
            <span class="pwtc-members-checkbox-wrap">
			    <input type="checkbox" name="detect_only" checked/>
		    </span>
            <input type="submit" value="Adjust" class="button button-primary button-large"/>
        </form>
        <p><div class="msg-div"></div></p>
        </div>
    </div>
<?php
}
?>
</div>
<?php
