<?php

class PwtcMembers {

    private static $initiated = false;

	public static function init() {
		if ( !self::$initiated ) {
			self::init_hooks();
		}
    }
    
	private static function init_hooks() {
		self::$initiated = true;

		/* Register script and style enqueue callbacks */

		add_action( 'wp_enqueue_scripts', 
			array( 'PwtcMembers', 'load_report_scripts' ) );

		/* Register woocommerce customization callbacks */

		add_action('woocommerce_checkout_update_user_meta',
			array( 'PwtcMembers', 'checkout_update_user_meta_callback' ) ); 

		// Force pretty format on entered phone numbers.
		add_filter( 'woocommerce_process_myaccount_field_billing_phone',
			'pwtc_members_format_phone_number' );
		add_filter( 'woocommerce_process_checkout_field_billing_phone',
			'pwtc_members_format_phone_number' );

		add_filter( 'wc_memberships_members_area_my_membership_details', 
			array( 'PwtcMembers', 'members_area_membership_details_callback' ), 10, 2 );

		add_action( 'woocommerce_product_query',
			array( 'PwtcMembers', 'product_query_callback' ) );

		add_action( 'wc_memberships_after_user_membership_member_details',
			array( 'PwtcMembers', 'after_user_member_details_callback' ) );

		add_action( 'wc_memberships_grant_membership_access_from_purchase', 
			array( 'PwtcMembers', 'user_membership_granted_callback' ), 10, 2);

		/* Register shortcode callbacks */

		add_shortcode('pwtc_member_statistics', 
			array( 'PwtcMembers', 'shortcode_member_statistics'));

		add_shortcode('pwtc_member_families', 
			array( 'PwtcMembers', 'shortcode_member_families'));

		add_shortcode('pwtc_member_new_members', 
			array( 'PwtcMembers', 'shortcode_member_new_members'));

		add_shortcode('pwtc_member_renew_nag', 
			array( 'PwtcMembers', 'shortcode_member_renew_nag'));

		add_shortcode('pwtc_member_accept_release', 
			array( 'PwtcMembers', 'shortcode_member_accept_release'));

		/* Register AJAX request/response callbacks */
	
	}

	/*************************************************************/
	/* Script and style enqueue callback functions
	/*************************************************************/

	public static function load_report_scripts() {
        wp_enqueue_style('pwtc_members_report_css', 
			PWTC_MEMBERS__PLUGIN_URL . 'reports-style.css', array(),
			filemtime(PWTC_MEMBERS__PLUGIN_DIR . 'reports-style.css'));
	}

	/*************************************************************/
	/* Wordpress/woocommerce customization callback functions
	/*************************************************************/

	// Set "Release Accepted" field in user profile upon checkout.
	public static function checkout_update_user_meta_callback ($customer_id) {
		update_field('release_accepted', true, 'user_'.$customer_id);
	}

	// Fix expiration date display for family members on My Account page.
	public static function members_area_membership_details_callback ($details, $user_membership) {
		if (function_exists('wc_memberships_for_teams_get_user_membership_team')) {
			$team = wc_memberships_for_teams_get_user_membership_team( $user_membership->get_id() );
			if ( $team ) {
				$team_expire = array(
					'expires' => array(
						'label'   => 'Family Expires',
						'content' => date_i18n( wc_date_format(), $team->get_local_membership_end_date( 'timestamp' ) ),
						'class'   => 'my-membership-detail-user-membership-expires',
					),
				);
				if ( array_key_exists( 'expires', $details ) ) {
					$details = array_replace( $details, $team_expire );
				}
			}
		}
		return $details;
	}

	// Exclude membership products from store page.
	public static function product_query_callback ($q) {
		$tax_query = (array) $q->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field' => 'slug',
			'terms' => array( 'memberships' ),
			'operator' => 'NOT IN'
		);
		$q->set( 'tax_query', $tax_query );	
	}

	// Add the Rider ID and Release Acceptance flag to the bottom of the Member Details box.
	public static function after_user_member_details_callback ($userid) {
		$rider_id = get_field('rider_id', 'user_'.$userid);
		if (!$rider_id) {
			$rider_id = '';
		}
		$phone = get_user_meta($userid, 'billing_phone', true);
		if (!empty($phone)) {
			$phone = pwtc_members_format_phone_number($phone);
		}
		$release_accepted = get_field('release_accepted', 'user_'.$userid) ? 'yes' : 'no';
		$privacy_requested = get_field('directory_excluded', 'user_'.$userid) ? 'yes' : 'no';
		?>
		<div><?php echo $phone; ?></div>
		<div>Rider ID: <?php echo $rider_id; ?></div>
		<div>Release Accepted: <?php echo $release_accepted; ?></div>
		<div>Privacy Requested: <?php echo $privacy_requested; ?></div>
		<?php
	}

	public static function user_membership_granted_callback($membership_plan, $args = array()) {
		$user_membership_id = isset($args['user_membership_id']) ? absint($args['user_membership_id']) : null;
		$user_id = isset($args['user_id']) ? absint($args['user_id']) : null;
		$product_id = isset($args['product_id']) ? absint($args['product_id']) : null;

		if (!$user_membership_id) {
			return;
		}
		if (!$user_id) {
			return;
		}

		if (!get_field('send_membership_email', 'option')) {
			return;
		}

		$membership = wc_memberships_get_user_membership($user_membership_id);
		if (!$membership) {
			return;			
		}
		
		$user_data = get_userdata($user_id);
		if (!$user_data) {
			return;			
		}

		$member_email = $user_data->user_email;
		$member_name = $user_data->first_name . ' ' . $user_data->last_name;

		$member_riderid = get_field('rider_id', 'user_'.$user_id);
		if (!$member_riderid) {
			$member_riderid = '';
		}

		$member_type = $membership_plan->get_name(); 

		$member_cost = 'N/A';
		$product = $membership->get_product(true);
		if ($product) {
			$price = $product->get_price();
			$member_cost = '$' . $price;
		}

		$team = false;
		if (function_exists('wc_memberships_for_teams_get_user_membership_team')) {
			$team = wc_memberships_for_teams_get_user_membership_team( $user_membership_id );
		}

		if ( $team ) {
			$message = get_field('family_membership_email', 'option');
			$member_expires = date('F j, Y', $team->get_local_membership_end_date('timestamp'));
			$member_starts = date('F j, Y', $team->get_local_date('timestamp'));
			$member_type = 'Family ' . $member_type;
		}
		else {
			$message = get_field('individual_membership_email', 'option');
			$member_expires = date('F j, Y', $membership->get_local_end_date('timestamp'));
			$member_starts = date('F j, Y', $membership->get_local_start_date('timestamp'));
			$member_type = 'Individual ' . $member_type;
		}
		
		if (!$message) {
			$message = '';
		}

		$membersec_email = get_field('membership_captain_email', 'option');
		$membersec_name = get_field('membership_captain_name', 'option');

		$message = str_replace('{riderid}', $member_riderid, $message);
		$message = str_replace('{expires}', $member_expires, $message);
		$message = str_replace('{starts}', $member_starts, $message);
		$message = str_replace('{email}', $member_email, $message);
		$message = str_replace('{name}', $member_name, $message);
		$message = str_replace('{type}', $member_type, $message);
		$message = str_replace('{cost}', $member_cost, $message);

		$subject = get_field('membership_email_subject', 'option');
		if (!$subject) {
			$subject = '';
		}

		$to = $member_name . ' <' . $member_email . '>';
		$bcc = $membersec_name . ' <' . $membersec_email . '>';
		$headers = array(
			'Content-type: text/html;charset=utf-8'
		);
		if (get_field('bcc_membership_secretary', 'option')) {
			$headers[] = 'Bcc: ' . $bcc;
		}
		wp_mail($to, $subject, $message, $headers);
	}

	/*************************************************************/
	/* Shortcode generation callback functions
	/*************************************************************/

	// Generates the [pwtc_member_statistics] shortcode.
	public static function shortcode_member_statistics($atts) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the membership statistics.</p></div>';
		}
		else {
			$today = date('F j Y', current_time('timestamp'));
			self::count_membership('all'); //TODO: this is a hack!
			$total = self::count_membership(['wcm-active','wcm-expired','wcm-delayed','wcm-complimentary','wcm-paused','wcm-cancelled']);
			$active = self::count_membership('wcm-active');
			$expired = self::count_membership('wcm-expired');
			$delayed = self::count_membership('wcm-delayed');
			$complimentary = self::count_membership('wcm-complimentary');
			$paused = self::count_membership('wcm-paused');
			$cancelled = self::count_membership('wcm-cancelled');
			$multimembers = self::fetch_users_with_multi_memberships(true);
			ob_start();
			?>
			<div>Membership statistics as of <?php echo $today; ?><br>
			<?php echo $total; ?> total members:<ul>
			<li><?php echo $active; ?> active members</li>
			<li><?php echo $expired; ?> expired members</li>
			<li><?php echo $complimentary; ?> complimentary members</li>
			<li><?php echo $delayed; ?> delayed members</li>
			<li><?php echo $paused; ?> paused members</li>
			<li><?php echo $cancelled; ?> cancelled members</li>
			</ul><?php echo $multimembers; ?> users with multiple memberships</div>
			<?php
			return ob_get_clean();
		}
	}
	
	// Generates the [pwtc_member_families] shortcode.
	public static function shortcode_member_families($atts) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the family member statistics.</p></div>';
		}
		else {
			$today = date('F j Y', current_time('timestamp'));
			$families = self::count_family_memberships();
			$family_members = self::count_family_members();
			ob_start();
			?>
			<div>Family member statistics as of <?php echo $today; ?><br>
			<?php echo $families; ?> family memberships with a total of <?php echo $family_members; ?> family members
			</div>
			<?php
			return ob_get_clean();
		}
	}

	// Generates the [pwtc_member_new_members] shortcode.
	public static function shortcode_member_new_members($atts) {
		$a = shortcode_atts(array('lookback' => 0), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '<div class="callout small warning"><p>Please log in to view the new members.</p></div>';
		}
		else {
			$timezone = new DateTimeZone(pwtc_get_timezone_string());
			$month = new DateTime(date('Y-m-01', current_time('timestamp')), $timezone);
			$lookback = $a['lookback'];
			if ($lookback > 0) {
				$month->sub(new DateInterval('P' . $lookback . 'M'));
			}
			$query_args = [
				'nopaging'    => true,
				'post_status' => 'any',
				'post_type'   => 'wc_user_membership',
				'meta_key'    => '_start_date',
				'orderby'     => 'meta_value',
				'order'       => 'DESC',
			];			
			$the_query = new WP_Query($query_args);
			if (empty($the_query)) {
				return '<div class="callout small warning"><p>Cannot access new members.</p></div>';
			}
			if ( $the_query->have_posts() ) {
				ob_start();
				?>
				<div>New members since <?php echo $month->format('F Y'); ?>:<ul>
				<?php
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$start = new DateTime(get_post_meta(get_the_ID(), '_start_date', true), $timezone);
					if ($start->getTimestamp() < $month->getTimestamp()) {
						break;
					}
					?>
					<li><?php echo get_the_author(); ?> (<?php echo $start->format('M j'); ?>)</li>
					<?php						
				}
				?>
				</ul></div>
				<?php						
				wp_reset_postdata();
				return ob_get_clean();
			} 
			else {
				ob_start();
				?>
				<div>No new members since <?php echo $month->format('F Y'); ?></div>
				<?php						
				return ob_get_clean();
			}
		}
	}

	// Generates the [pwtc_member_renew_nag] shortcode.
	public static function shortcode_member_renew_nag($atts) {
		$a = shortcode_atts(array('renewonly' => 'no'), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '';
		}
		if (!function_exists('wc_memberships_get_user_memberships')) {
			return '';
		}
		$memberships = wc_memberships_get_user_memberships($current_user->ID);
		if (empty($memberships)) {
			if ($a['renewonly'] == 'yes') {
				return '';
			}
			else {
				ob_start();
				?>
				<div class="callout success"><p>You have no membership</p></div>		
				<?php
				return ob_get_clean();
			}
		}
		if (count($memberships) > 1) {
			ob_start();
			?>
			<div class="callout alert"><p>You have multiple memberships, please notify website admin to resolve</p></div>		
			<?php
			return ob_get_clean();
		}
		$membership = $memberships[0];
		if ($a['renewonly'] == 'yes') {
			if (!$membership->is_expired()) {
				return '';
			}
		}
		$team = false;
		if (function_exists('wc_memberships_for_teams_get_user_membership_team')) {
			$team = wc_memberships_for_teams_get_user_membership_team($membership->get_id());
		}
		if ($team) {
			if ($team->is_user_owner($current_user->ID)) {
				if ($team->is_membership_expired()) {
					ob_start();
					?>
					<div class="callout warning"><p>Your family membership "<?php echo $team->get_name(); ?>" expired on <?php echo date('F j, Y', $team->get_local_membership_end_date('timestamp')); ?>. <a href="<?php echo $team->get_renew_membership_url(); ?>">Click here to renew</a></p></div>		
					<?php
					return ob_get_clean();
				}
				else {
					ob_start();
					?>
					<div class="callout success"><p>Your family membership "<?php echo $team->get_name(); ?>" will expire on <?php echo date('F j, Y', $team->get_local_membership_end_date('timestamp')); ?></p></div>		
					<?php
					return ob_get_clean();
				}
			}
			else {
				if ($team->is_membership_expired()) {
					ob_start();
					?>
					<div class="callout warning"><p>Your family membership "<?php echo $team->get_name(); ?>" expired on <?php echo date('F j, Y',$team->get_local_membership_end_date('timestamp')); ?>, please ask the membership owner to renew</p></div>		
					<?php
					return ob_get_clean();	
				}
				else {
					ob_start();
					?>
					<div class="callout success"><p>Your family membership "<?php echo $team->get_name(); ?>" will expire on <?php echo date('F j, Y',$team->get_local_membership_end_date('timestamp')); ?></p></div>		
					<?php
					return ob_get_clean();
				}
			}
		}
		else {
			if ($membership->is_expired()) {
				ob_start();
				?>
				<div class="callout warning"><p>Your individual membership expired on <?php echo date('F j, Y', $membership->get_local_end_date('timestamp')); ?>. <a href="<?php echo $membership->get_renew_membership_url(); ?>">Click here to renew</a></p></div>		
				<?php
				return ob_get_clean();
			}
			else {
				if ($membership->has_end_date()) {
					ob_start();
					?>
					<div class="callout success"><p>Your individual membership will expire on <?php echo date('F j, Y', $membership->get_local_end_date('timestamp')); ?></p></div>		
					<?php
					return ob_get_clean();
				}
				else {
					ob_start();
					?>
					<div class="callout success"><p>Your individual membership will never expire</p></div>		
					<?php
					return ob_get_clean();
				}
			}
		}
	}	

	// Generates the [pwtc_member_accept_release] shortcode.
	public static function shortcode_member_accept_release($atts) {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return '';
		}
		$userid = $current_user->ID;
		if (isset($_POST['pwtc_membership_accept_release'])) {
			update_field('release_accepted', true, 'user_'.$userid);
		}
		if (get_field('release_accepted', 'user_'.$userid)) {
			return '';
		}
		ob_start();
		?>
		<div class="callout warning"><p>Please read and accept the Club's <a href="/terms-and-conditions" target="_blank">terms and conditions</a>.<form method="POST"><button class="button" type="submit" name="pwtc_membership_accept_release">I Accept</button></form></p></div>		
		<?php
		return ob_get_clean();
	}

	/*************************************************************/
	/* AJAX request/response callback functions
	/*************************************************************/

	/*************************************************************/
	/* Utility functions.
	/*************************************************************/

	public static function count_membership($status) {
		$query_args = [
			'nopaging'    => true,
			'post_status' => $status,
			'post_type' => 'wc_user_membership',
			'fields' => 'ids',
			'cache_results'  => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,		
		];			
		$the_query = new WP_Query($query_args);
		if (empty($the_query)) {
			return 'unknown';
		}
		return '' . $the_query->found_posts;
	}

	public static function count_family_memberships() {
		$query_args = [
			'nopaging'    => true,
			'post_status' => 'any',
			'post_type' => 'wc_memberships_team',
			'fields' => 'ids',
			'cache_results'  => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,		
		];			
		$the_query = new WP_Query($query_args);
		if (empty($the_query)) {
			return 'unknown';
		}
		return '' . $the_query->found_posts;
	}

	public static function count_family_members() {
		$query_args = [
			'nopaging'    => true,
			'post_status' => 'any',
			'post_type' => 'wc_memberships_team',
		];			
		$the_query = new WP_Query($query_args);
		if (empty($the_query)) {
			return 'unknown';
		}
		$count = 0;
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$members = get_post_meta(get_the_ID(), '_member_id', false);
				$count += count($members);
			}
			wp_reset_postdata();
		}
		return '' . $count;
	}

	public static function fetch_users_with_multi_memberships($count_only = false) {
		global $wpdb;
		$post_type = 'wc_user_membership';
		$select_item = 'distinct a.post_author';
		if ($count_only) {
			$select_item = 'count(' . $select_item . ')';
		}
		$stmt = $wpdb->prepare(
			"select " . $select_item .
			" from " . $wpdb->posts . " as a inner join " . $wpdb->posts . " as b" . 
			" where a.post_author = b.post_author and a.ID <> b.ID" . 
			" and a.post_type = %s and b.post_type = %s" . 
			" and a.post_status not in ('auto-draft', 'trash')" . 
			" and b.post_status not in ('auto-draft', 'trash')", $post_type, $post_type);
		if ($count_only) {
			$results = $wpdb->get_var($stmt);
		}
		else {
			$results = $wpdb->get_results($stmt, ARRAY_N);
		}
		return $results;
	}

	/*************************************************************/
	/* Plugin capabilities management functions.
	/*************************************************************/

	public static function add_caps_admin_role() {
		$admin = get_role('administrator');
		self::write_log('PWTC Members plugin added capabilities to administrator role');
	}

	public static function remove_caps_admin_role() {
		$admin = get_role('administrator');
		self::write_log('PWTC Members plugin removed capabilities from administrator role');
	}

    /*************************************************************/
    /* Plugin installation and removal functions.
    /*************************************************************/

	public static function plugin_activation() {
		self::write_log( 'PWTC Members plugin activated' );
		if ( version_compare( $GLOBALS['wp_version'], PWTC_MEMBERS__MINIMUM_WP_VERSION, '<' ) ) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('PWTC Members plugin requires Wordpress version of at least ' . PWTC_MEMBERS__MINIMUM_WP_VERSION);
		}
		self::add_caps_admin_role();
    }
    
	public static function plugin_deactivation( ) {
		self::write_log( 'PWTC Members plugin deactivated' );
		self::remove_caps_admin_role();
    }
    
	public static function plugin_uninstall() {
		self::write_log( 'PWTC Members plugin uninstall' );	
    }
    
    public static function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }

}