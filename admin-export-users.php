<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if (!current_user_can($capability)) {
?> 
	<p><strong>Access Denied</strong> - you do not have the rights to view this page.</p>
<?php   
}
else {
    $queries = PwtcMembers_Admin::fetch_canned_queries();
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 

	function load_query_cb(response) {
        var res = JSON.parse(response);
        if (res.error) {
            $("#export-user-section .query-div").hide();
            $("#export-user-section .err-msg").html(res.error);
        }
        else {
            $("#export-user-section .err-msg").empty();
            $("#export-user-section .query-frm textarea[name='includes']").val(res.includes);
            $("#export-user-section .query-frm textarea[name='excludes']").val(res.excludes);
            $("#export-user-section .query-frm input[name='file']").val(res.file);
            $("#export-user-section .query-frm #" + res.riderid).prop("checked", true);
            $("#export-user-section .query-div").show();
        }
	}   

    $('#export-user-section .query-slt').change(function() {
        var query = $('#export-user-section .query-slt').val();
        if (query != 'none') {
            $("#export-user-section .query-div").hide();
            var action = '<?php echo admin_url('admin-ajax.php'); ?>';
            var data = {
                'action': 'pwtc_members_fetch_query',
                'query': query
            };
            $.post(action, data, load_query_cb);
        }
        else {
            $("#export-user-section .query-div").hide();
        }
    });

    $("#export-user-section .query-div").hide();
    $('#export-user-section .query-slt').focus(); 
});
</script>
    <div id="export-user-section">
        <p>Canned Queries:&nbsp;
            <select class='query-slt'>
                <option value="none" selected>-- select a query --</option>
                <?php
                foreach ($queries as $name => $query) {
                    $label = $query['label'];
                ?>
                <option value="<?php echo $name; ?>"><?php echo $label; ?></option>
                <?php } ?>
            </select>
            &nbsp;<span class="err-msg"></span>
        </p>
        <div class="pwtc-members-search-sec query-div">
        <form class="pwtc-members-stacked-form query-frm" method="POST">
            <span>Include These Roles</span>
            <textarea name="includes" rows="5" wrap></textarea>
            <span>Exclude These Roles</span>
            <textarea name="excludes" rows="5" wrap></textarea>
            <span>Detect Rider ID</span>
            <span class="pwtc-members-checkbox-wrap">
                <input type="radio" id="off" name="riderid" value="off" checked/>
                <label for="off">Off</label>
            </span>
            <span class="pwtc-members-checkbox-wrap">
                <input type="radio" id="set" name="riderid" value="set"/>
                <label for="set">ID Set</label>
            </span>
            <span class="pwtc-members-checkbox-wrap">
                <input type="radio" id="not_set" name="riderid" value="not_set"/>
                <label for="not_set">ID Not Set</label>
            </span>
            <span>Export File Name</span>
            <input type="text" name="file" value="" required/>
            <span>Show Details</span>
            <span class="pwtc-members-checkbox-wrap">
                <input type="checkbox" name="details"/>
            </span>
            <input type="submit" value="Export Users" class="button button-primary button-large"/>
        </form>
        </div>
    </div>
<?php
}
?>
</div>
<?php
