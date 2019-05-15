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
});
</script>
    <div>
        <p>Under construction!</p>
        <form class="pwtc-members-stacked-form" method="POST">
            <span>Include These Roles</span>
            <textarea name="includes" rows="10" wrap></textarea>
            <span>Exclude These Roles</span>
            <textarea name="excludes" rows="10" wrap></textarea>
            <span>Detect Rider ID</span>
            <span class="pwtc-members-checkbox-wrap">
                <input type="radio" id="off" name="riderid" value="off"/>
                <label for="off">Off</label>
            </span>
            <span class="pwtc-members-checkbox-wrap">
                <input type="radio" id="set" name="riderid" value="set"/>
                <label for="set">Set</label>
            </span>
            <span class="pwtc-members-checkbox-wrap">
                <input type="radio" id="not_set" name="riderid" value="not_set"/>
                <label for="not_set">Not Set</label>
            </span>
            <span>Download File Name</span>
            <input type="text" name="file" value=""/>
            <input type="submit" value="Save" class="button button-primary button-large"/>
        </form>
    </div>
<?php
}
?>
</div>
<?php
