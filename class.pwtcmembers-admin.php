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

		add_action( 'wp_ajax_pwtc_members_send_test_email', 
			array( 'PwtcMembers_Admin', 'send_test_email_callback') );

		add_action( 'wp_ajax_pwtc_members_fix_invalid_members', 
			array( 'PwtcMembers_Admin', 'fix_invalid_members_callback') );

		add_action( 'wp_ajax_pwtc_members_fix_missing_members', 
			array( 'PwtcMembers_Admin', 'fix_missing_members_callback') );

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

		$page_title = $plugin_options['plugin_menu_label'] . ' - Invalid Membership Roles';
    	$menu_title = 'Invalid Members';
    	$menu_slug = 'pwtc_members_invalid';
    	$capability = 'manage_options';
    	$function = array( 'PwtcMembers_Admin', 'page_invalid_members');
		add_submenu_page($parent_menu_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

		$page_title = $plugin_options['plugin_menu_label'] . ' - Missing Membership Roles';
    	$menu_title = 'Missing Members';
    	$menu_slug = 'pwtc_members_missing';
    	$capability = 'manage_options';
    	$function = array( 'PwtcMembers_Admin', 'page_missing_members');
		add_submenu_page($parent_menu_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

		$page_title = $plugin_options['plugin_menu_label'] . ' - Test Confirmation Email';
    	$menu_title = 'Test Confirm Email';
    	$menu_slug = 'pwtc_members_test_email';
    	$capability = 'manage_options';
    	$function = array( 'PwtcMembers_Admin', 'page_test_email');
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

	public static function page_invalid_members() {
		$plugin_options = PwtcMembers::get_plugin_options();
		$capability = 'manage_options';
		include('admin-invalid-members.php');
	}

	public static function page_missing_members() {
		$plugin_options = PwtcMembers::get_plugin_options();
		$capability = 'manage_options';
		include('admin-missing-members.php');
	}

	public static function page_test_email() {
		$plugin_options = PwtcMembers::get_plugin_options();
		$capability = 'manage_options';
		include('admin-test-email.php');
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

	public static function send_test_email_callback() {
		if (!current_user_can('manage_options')) {
			$response = array(
				'status' => 'Send test email failed - user access denied.'
			);		
		}
		else if (!isset($_POST['member_email']) or !isset($_POST['email_to']) or !isset($_POST['nonce'])) {
			$response = array(
				'status' => 'Send test email failed - AJAX arguments missing.'
			);		
		}
		else {
			$nonce = $_POST['nonce'];	
			if (!wp_verify_nonce($nonce, 'pwtc_members_send_test_email')) {
				$response = array(
					'status' => 'Send test email failed - nonce security check failed.'
				);
				echo wp_json_encode($response);
			}
			else {
				$user_data = get_user_by( 'email', $_POST['member_email'] );
				if (!$user_data) {
					$response = array(
						'status' => 'Send test email failed - no user with that email.'
					);		
				}
				else {
					if (!function_exists('wc_memberships_get_user_memberships')) {
						$response = array(
							'status' => 'Send test email failed - membership system not active.'
						);		
					}
					else {
						$memberships = wc_memberships_get_user_memberships($user_data->ID);
						if (empty($memberships)) {
							$response = array(
								'status' => 'Send test email failed - user has no memberships.'
							);		
						}
						else if (count($memberships) > 1) {
							$response = array(
								'status' => 'Send test email failed - user has multiple memberships.'
							);		
						}
						else {
							$membership = $memberships[0];
							$membership_plan = $membership->get_plan();
							if (!$membership_plan) {
								$response = array(
									'status' => 'Send test email failed - membership has no plan.'
								);			
							}
							else {
								$email_to = $_POST['email_to'];
								$email = PwtcMembers::build_confirmation_email($membership_plan, $user_data, $membership, $email_to);
								$esc_headers = array();
								foreach ( $email['headers'] as $header ) {
									$esc_headers[] = esc_html($header);
								}
								if (empty($email_to)) {
									$response = array(
										'to' => esc_html($email['to']),
										'subject' => esc_html($email['subject']),
										'message' => $email['message'],
										'headers' => $esc_headers
									);				
								}
								else {
									$status = wp_mail($email['to'], $email['subject'], $email['message'], $email['headers']);
									$sent_to = 'Email sent to ' . esc_html($email['to']);
									if ($status) {
										$response = array(
											'status' => $sent_to . ' - wp_mail returned true.'
										);				
									}
									else {
										$response = array(
											'status' => $sent_to . ' - wp_mail returned false.'
										);				
									}
								}
							}
						}
					}
				}
			}
		}
		echo wp_json_encode($response);
        wp_die();
	}

	public static function fetch_member_role_users() {
		$query_args = [
			'fields' => 'ID',
			'role__in' => ['current_member', 'expired_member']
		];
		$user_query = new WP_User_Query( $query_args );
		$users = $user_query->get_results();
		return $users;
	}

	public static function fetch_nonmember_role_users() {
		$query_args = [
			'fields' => 'ID',
			'role__not_in' => ['current_member', 'expired_member']
		];
		$user_query = new WP_User_Query( $query_args );
		$users = $user_query->get_results();
		return $users;
	}

	public static function fix_invalid_members_callback() {
		if (!current_user_can('manage_options')) {
			$response = array(
				'status' => 'Fix failed - user access denied.'
			);		
		}
		else if (!isset($_POST['nonce'])) {
			$response = array(
				'status' => 'Fix failed - AJAX arguments missing.'
			);
		}
		else {
			$nonce = $_POST['nonce'];	
			if (!wp_verify_nonce($nonce, 'pwtc_members_fix_invalid_members')) {
				$response = array(
					'status' => 'Fix failed - nonce security check failed.'
				);
				echo wp_json_encode($response);
			}
			else {
				$count = 0;
				$test_users = self::fetch_member_role_users();
				$results = PwtcMembers::fetch_users_with_no_memberships();
				foreach ($results as $item) {
					$userid = $item[0];
					if (in_array($userid, $test_users)) {
						$user_info = get_userdata( $userid ); 
						if ($user_info) {
							$count++;
							if (in_array('expired_member', $user_info->roles)) {
								$user_info->remove_role('expired_member');
							}
							if (in_array('current_member', $user_info->roles)) {
								$user_info->remove_role('current_member');
							}				
						}	
					}
				}
				$response = array(
					'status' => 'Fix successful - ' . $count . ' user accounts corrected.'
				);
			}		
		}
		echo wp_json_encode($response);
        wp_die();
	}

	public static function fix_missing_members_callback() {
		if (!current_user_can('manage_options')) {
			$response = array(
				'status' => 'Fix failed - user access denied.'
			);		
		}
		else if (!isset($_POST['nonce'])) {
			$response = array(
				'status' => 'Fix failed - AJAX arguments missing.'
			);
		}
		else {
			$nonce = $_POST['nonce'];	
			if (!wp_verify_nonce($nonce, 'pwtc_members_fix_missing_members')) {
				$response = array(
					'status' => 'Fix failed - nonce security check failed.'
				);
				echo wp_json_encode($response);
			}
			else {
				$count = 0;
				$multicount = 0;
				$test_users = self::fetch_nonmember_role_users();
				$results = PwtcMembers::fetch_users_with_memberships();
				foreach ($results as $item) {
					$userid = $item[0];
					if (in_array($userid, $test_users)) {
						$user_info = get_userdata( $userid ); 
						if ($user_info) {
							$memberships = wc_memberships_get_user_memberships($user_info->ID);
							if (count($memberships) == 1) {
								$count++;
								$membership = $memberships[0];
								if (!in_array('customer', $user_info->roles)) {
									$user_info->add_role('customer');
								}
								if (pwtc_members_is_expired($membership)) {
									if (!in_array('expired_member', $user_info->roles)) {
										$user_info->add_role('expired_member');
									}
									if (in_array('current_member', $user_info->roles)) {
										$user_info->remove_role('current_member');
									}
								}
								else {
									if (!in_array('current_member', $user_info->roles)) {
										$user_info->add_role('current_member');
									}
									if (in_array('expired_member', $user_info->roles)) {
										$user_info->remove_role('expired_member');
									}
								}						
							}
							else if (count($memberships) > 1) {
								$multicount++;
							}
						}	
					}
				}
				$msg = 'Fix successful - ' . $count . ' user accounts corrected.';
				if ($multicount > 0) {
					$msg .= ' ' . $multicount . ' user accounts with multiple memberships which were NOT corrected.';
				}
				$response = array(
					'status' => $msg
				);
			}		
		}
		echo wp_json_encode($response);
        wp_die();
	}
	
}