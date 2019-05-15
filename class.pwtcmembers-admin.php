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
		wp_enqueue_style('pwtc_members_report_css');
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
		add_submenu_page($parent_menu_slug, $page_title, $menu_title, $capability, $menu_slug, $function);

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

}