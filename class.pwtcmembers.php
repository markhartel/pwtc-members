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

//		add_action( 'wc_memberships_user_membership_created', 
//			array( 'PwtcMembers', 'user_membership_created_callback' ), 999, 2);

		/* Register shortcode callbacks */

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
		$release_accepted = get_field('release_accepted', 'user_'.$userid) ? 'yes' : 'no';
		?>
		<div>Rider ID: <?php echo $rider_id; ?></div>
		<div>Release Accepted: <?php echo $release_accepted; ?></div>
		<?php
	}

	public static function user_membership_created_callback($membership_plan, $args = array()) {
		$user_membership_id = isset($args['user_membership_id']) ? absint($args['user_membership_id']) : null;
		$user_id = isset($args['user_id']) ? absint($args['user_id']) : null;
		$is_update = isset($args['is_update']) ? $args['is_update'] : false;

		if (!$user_membership_id) {
			return;
		}
		if (!$user_id) {
			return;
		}

		if ($is_update) {
			return;
		}

		$user_membership = wc_memberships_get_user_membership($user_membership_id);
		if (!$user_membership) {
			return;			
		}
		
		$user_data = get_userdata($user_id);
		if (!$user_data) {
			return;			
		}

		$rider_id = get_field('rider_id', 'user_'.$user_id);
		if (!$rider_id) {
			$rider_id = '';
		}

		$team = false;
		if (function_exists('wc_memberships_for_teams_get_user_membership_team')) {
			$team = wc_memberships_for_teams_get_user_membership_team( $user_membership_id );
		}

		if ( $team ) {

		}
		else {

		}		
	}

	/*************************************************************/
	/* Shortcode generation callback functions
	/*************************************************************/

	/*************************************************************/
	/* AJAX request/response callback functions
	/*************************************************************/

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