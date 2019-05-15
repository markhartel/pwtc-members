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

				}
				$today = date('Y-m-d', current_time('timestamp'));
				header('Content-Description: File Transfer');
				header("Content-type: text/csv");
				header("Content-Disposition: attachment; filename={$today}_{$_POST['file']}.csv");
				$fp = fopen('php://output', 'w');
				fputcsv($fp, ['Username', 'Email', 'First Name', 'Last Name', 'User ID']);
				$user_query = new WP_User_Query( $query_args );
				$members = $user_query->get_results();
				if ( !empty($members) ) {
					foreach ( $members as $member ) {
						fputcsv($fp, [$member->user_login, $member->user_email, $member->first_name, $member->last_name, $member->ID]);
					}
				}
				fclose($fp);
				die;
			}
		}
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

}