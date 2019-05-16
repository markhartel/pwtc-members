<?php

class PwtcMembers_Admin {

    private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
    }
    
	private static function init_hooks() {
        self::$initiated = true;
        
        /* Register admin menu creation callbacks */
		add_action( 'admin_menu', array( 'PwtcMembers_Admin', 'plugin_menu' ) );
		
		/* Register script and style enqueue callbacks */
		add_action( 'admin_enqueue_scripts', array( 'PwtcMembers_Admin', 'load_admin_scripts' ) );

		add_action( 'wp_ajax_pwtc_members_fetch_query', 
			array( 'PwtcMembers_Admin', 'fetch_query_callback') );
	
	}  

	/*************************************************************/
	/* Script and style enqueue callback functions               */
	/*************************************************************/

	public static function load_admin_scripts($hook) {
		if (!strpos($hook, "pwtc_members")) {
            return;
		}
        wp_enqueue_style('pwtc_members_report_css', 
			PWTC_MEMBERS__PLUGIN_URL . 'reports-style.css', array(),
			filemtime(PWTC_MEMBERS__PLUGIN_DIR . 'reports-style.css'));
	}

    /* Admin menu and pages creation functions */

    public static function plugin_menu() {
		$plugin_options = PwtcMembers::get_plugin_options();

    	$page_title = $plugin_options['plugin_menu_label'];
    	$menu_title = $plugin_options['plugin_menu_label'];
    	$capability = 'manage_options';
    	$parent_menu_slug = 'pwtc_members_menu';
    	$function = array( 'PwtcMembers_Admin', 'plugin_menu_page');
    	$icon_url = '';
    	$position = $plugin_options['plugin_menu_location'];
        add_menu_page($page_title, $menu_title, $capability, $parent_menu_slug, $function, $icon_url, $position);

        $page_title = $plugin_options['plugin_menu_label'] . ' - Export Users';
    	$menu_title = 'Export Users';
    	$menu_slug = 'pwtc_members_export_users';
    	$capability = 'manage_options';
    	$function = array( 'PwtcMembers_Admin', 'page_export_users');
		$page = add_submenu_page($parent_menu_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
		add_action('load-' . $page, array('PwtcMembers_Admin','download_user_csv'));

        $page_title = $plugin_options['plugin_menu_label'] . ' - Multiple Memberships';
    	$menu_title = 'Multi Members';
    	$menu_slug = 'pwtc_members_multiple';
    	$capability = 'manage_options';
    	$function = array( 'PwtcMembers_Admin', 'page_multi_members');
		add_submenu_page($parent_menu_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

        remove_submenu_page($parent_menu_slug, $parent_menu_slug);
    }

    public static function plugin_menu_page() {
	}

    public static function page_export_users() {
		$plugin_options = PwtcMembers::get_plugin_options();
		$capability = 'manage_options';
		include('admin-export-users.php');
	}

    public static function page_multi_members() {
		$plugin_options = PwtcMembers::get_plugin_options();
		$capability = 'manage_options';
		include('admin-multi-members.php');
	}

	public static function download_user_csv() {
		if (current_user_can('manage_options')) {
			if (isset($_POST['includes']) and isset($_POST['excludes']) and isset($_POST['riderid']) and isset($_POST['file'])) {
				if (!empty($_POST['file'])) {
					$details = isset($_POST['details']) and $_POST['details'] == 'true' ? true : false;
					$query_args = [
						'meta_key' => 'last_name',
						'orderby' => 'meta_value',
						'order' => 'ASC'
					];
					$includes = self::parse_role_list($_POST['includes']);
					if (!empty($includes)) {
						$query_args['role__in'] = $includes;
					}
					$excludes = self::parse_role_list($_POST['excludes']);	
					if (!empty($excludes)) {
						$query_args['role__not_in'] = $excludes;
					}
					if ($_POST['riderid'] == 'not_set') {
						$query_args['meta_query'] = [];
						$query_args['meta_query'][] = [
							'relation' => 'OR',
							[
								'key'     => 'rider_id',
								'compare' => 'NOT EXISTS' 
							],
							[
								'key'     => 'rider_id',
								'value'   => ''    
							] 
						];			
					}
					else if ($_POST['riderid'] == 'set') {
						$query_args['meta_query'] = [];
						$query_args['meta_query'][] = [
							'relation' => 'AND',
							[
								'key'     => 'rider_id',
								'compare' => 'EXISTS' 
							],
							[
								'key'     => 'rider_id',
								'value'   => '',
								'compare' => '!='   
							] 
						];			
					}
					$today = date('Y-m-d', current_time('timestamp'));
					header('Content-Description: File Transfer');
					header("Content-type: text/csv");
					header("Content-Disposition: attachment; filename={$today}_{$_POST['file']}.csv");
					$fp = fopen('php://output', 'w');
					fputcsv($fp, self::get_download_csv_labels($details));
					$user_query = new WP_User_Query( $query_args );
					$members = $user_query->get_results();
					if ( !empty($members) ) {
						foreach ( $members as $member ) {
							fputcsv($fp, self::get_download_csv_data($member, $details));
						}
					}
					fclose($fp);
					die;
				}
			}
		}
	}

	public static function fetch_query_callback() {
		if (!isset($_POST['query'])) {
			$response = array(
				'error' => 'Input parameters needed to fetch query are missing.'
			);
			echo wp_json_encode($response);	
		}
		else {
			$queries = self::fetch_canned_queries();
			$query = $queries[$_POST['query']];
			if ($query) {
				$response = array(
					'label' => $query['label'],
					'includes' => $query['includes'],
					'excludes' => $query['excludes'],
					'riderid' => $query['riderid'],
					'file' => $query['file']
				);
				echo wp_json_encode($response);	
			}
			else {
				$response = array(
					'error' => 'Canned query not found.'
				);
				echo wp_json_encode($response);		
			}
		}
		wp_die();
	}

	public static function parse_role_list($roles) {
		$list = [];
		$tok = strtok($roles, " ");
		while ($tok !== false) {
			$list[] = $tok;
			$tok = strtok(" ");
		}
		return $list;
	}

	public static function fetch_canned_queries() {
		$results = [
			'current_members' => self::create_canned_query('Current Members', 'current_member', '', 'off', 'current_members'),
			'expired_members' => self::create_canned_query('Expired Members', 'expired_member', '', 'off', 'expired_members'),
			'ride_leaders' => self::create_canned_query('Ride Leaders', 'ride_leader', '', 'off', 'ride_leaders'),
			'no_riderid' => self::create_canned_query('Members Without Rider IDs', 'current_member expired_member', '', 'not_set', 'no_riderid'),
			'bogus_users' => self::create_canned_query('Bogus Users', '', 'administrator current_member expired_member customer ride_leader ride_captain statistician qr_editor', 'not_set', 'bogus_users'),
			'custom_query' => self::create_canned_query('Custom Query', '', '', 'off', '')
		];
		return $results;
	}

	public static function create_canned_query($label, $includes, $excludes, $riderid, $file) {
		return [
			'label' => $label,
			'includes' => $includes,
			'excludes' => $excludes,
			'riderid' => $riderid,
			'file' => $file
		];
	}

	public static function get_download_csv_labels($details = false) {
		$labels = [
			'Username', 
			'Email', 
			'First Name', 
			'Last Name', 
			'User ID'
		];
		if ($details) {
			$labels[] = 'Rider ID';
			$labels[] = 'Address 1';
			$labels[] = 'Address 2';
			$labels[] = 'City';
			$labels[] = 'State';
			$labels[] = 'Country';
			$labels[] = 'Postcode';
			$labels[] = 'Phone';
		}
		return $labels;
	}

	public static function get_download_csv_data($user, $details = false) {
		$data = [
			$user->user_login, 
			$user->user_email, 
			$user->first_name, 
			$user->last_name, 
			$user->ID
		];
		if ($details) {
            $rider_id = get_field('rider_id', 'user_'.$user->ID);
            if (!$rider_id) {
                $rider_id = '';
			}
			$data[]	= $rider_id;
			$data[]	= get_user_meta($user->ID, 'billing_address_1', true);
			$data[]	= get_user_meta($user->ID, 'billing_address_2', true); 
			$data[]	= get_user_meta($user->ID, 'billing_city', true); 
			$data[]	= get_user_meta($user->ID, 'billing_state', true); 
			$data[]	= get_user_meta($user->ID, 'billing_country', true); 
			$data[]	= get_user_meta($user->ID, 'billing_postcode', true);
			$data[]	= pwtc_members_format_phone_number(get_user_meta($user->ID, 'billing_phone', true)); 
		}
		return $data;		
	}

}