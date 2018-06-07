<?php

class PwtcMembers {

    private static $initiated = false;

	public static function init() {
		if ( !self::$initiated ) {
			self::init_hooks();
		}
    }
    
	// Initializes plugin WordPress hooks.
	private static function init_hooks() {
        self::$initiated = true;
        
		add_action( 'wp_ajax_pwtc_members_lookup', 
            array( 'PwtcMembers', 'members_lookup_callback') );
            
		// Register shortcode callbacks
		add_shortcode('pwtc_members_lookup', 
			array( 'PwtcMembers', 'shortcode_members_lookup'));

    }

    public static function members_lookup_callback() {
		$limit = intval($_POST['limit']);
        $query_args = [
            'meta_key' => 'last_name',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'number' => $limit
		];
		
		$page_number = 1;
		if (isset($_POST['page_number'])) {
			$page_number = intval($_POST['page_number']);
		}
		if ($page_number == 1) {
			$offset = 0;  
		}
		else {
			$offset = ($page_number-1)*$limit;
		}
		$query_args['offset'] = $offset;

        $member_names = [];
        $user_query = new WP_User_Query( $query_args );
        $members = $user_query->get_results();
        if ( !empty($members) ) {
            foreach ( $members as $member ) {
                $member_info = get_userdata( $member->ID );
                $member_names[] = [
                    'ID' => $member->ID,
                    'first_name' => $member_info->first_name,
                    'last_name' => $member_info->last_name,
                    'email' => $member_info->user_email
                ];
            }
		}
		
		$total_users = $user_query->total_users;
		$total_pages = ceil($user_query->total_users/$limit);

        $response = array(
			'members' => $member_names,
			'total_pages' => $total_pages,
			'page_number' => $page_number
		);

        echo wp_json_encode($response);
        wp_die();
    }
    
	/*************************************************************/
	/* Shortcode report generation functions
	/*************************************************************/
 
	// Generates the [pwtc_members_lookup] shortcode.
	public static function shortcode_members_lookup($atts) {
		$a = shortcode_atts(array('limit' => 10), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return 'Please log in to search the members directory.';
		}
		else {
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			function populate_members_table(members) {
				var header = '<table><tr style="text-align: left"><th>Email</th><th>Last Name</th><th>First Name</th></tr></table>';
				$('.pwtc-members-display-div').append(header);
				members.forEach(function(item) {
					var data = '<tr userid="' + item.ID + '">' +
					'<td><a href="#"><i class="fa fa-user"></i></a> ' + item.email + '</td>' + 
					'<td>' + item.last_name + '</td>' +
					'<td>' + item.first_name + '</td>' +
					'</tr>';
					$('.pwtc-members-display-div table').append(data);    
				});
            }

			function create_paging_form(pagenum, numpages) {
				$('.pwtc-members-display-div').append(
					'<form class="page-frm">' +
                    '<input class="prev-btn button" style="margin: 0" type="button" value="< Prev"/>' +
					'<span style="margin: 0 10px">Page ' + pagenum + ' of ' + numpages + '</span>' +
                    '<input class="next-btn button" style="margin: 0" type="button" value="Next >"/>' +
					'<span class="page-msg" style="margin: 0 10px"></span>' +
					'<input name="pagenum" type="hidden" value="' + pagenum + '"/>' +
					'<input name="numpages" type="hidden" value="' + numpages + '"/>' +
					'</form>'
				);
				$('.pwtc-members-display-div .page-frm .prev-btn').on('click', function(evt) {
					evt.preventDefault();
					load_members_table('prev');
				});
				if (pagenum == 1) {
					$('.pwtc-members-display-div .page-frm .prev-btn').attr("disabled", "disabled");
				}
				else {
					$('.pwtc-members-display-div .page-frm .prev-btn').removeAttr("disabled");
				}
				$('.pwtc-members-display-div .page-frm .next-btn').on('click', function(evt) {
					evt.preventDefault();
					load_members_table('next');
				});
				if (pagenum == numpages) {
					$('.pwtc-members-display-div .page-frm .next-btn').attr("disabled", "disabled");
				}
				else {
					$('.pwtc-members-display-div .page-frm .next-btn').removeAttr("disabled");
				}
			}

			function lookup_members_cb(response) {
				var res = JSON.parse(response);
				$('.pwtc-members-display-div').empty();
				if (res.error) {
					$('.pwtc-members-display-div').append(
						'<div><strong>Error:</strong> ' + res.error + '</div>');
				}
				else {
					if (res.message !== undefined) {
						$('.pwtc-members-display-div').append(
							'<div><strong>Warning:</strong> ' + res.message + '</div>');
					}
					if (res.members.length > 0) {
						populate_members_table(res.members);
						if (res.total_pages > 1) {
							create_paging_form(res.page_number, res.total_pages);
						}
					}
					else {
						$('.pwtc-members-display-div').append('<div>No members found.</div>');					
					}
				}
			}   

			function load_members_table(mode) {
				//var action = $('.pwtc-mapdb-search-div .search-frm').attr('action');
                var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_members_lookup',
					'limit': <?php echo $a['limit'] ?>
				};
				if (mode != 'search') {
					var pagenum = $(".pwtc-members-display-div .page-frm input[name='pagenum']").val();
					var numpages = $(".pwtc-members-display-div .page-frm input[name='numpages']").val();
					if (mode == 'prev') {
						data.page_number = parseInt(pagenum) - 1;
					}
					else if (mode == 'next') {
						data.page_number = parseInt(pagenum) + 1;
					}
					$('.pwtc-members-display-div .page-frm .page-msg').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
				}
				else {
					$('.pwtc-members-display-div').html('<i class="fa fa-spinner fa-pulse"></i> Loading...');
				}

				$.post(action, data, lookup_members_cb); 
			}

            load_members_table('search');
		});
	</script>
	<div class="pwtc-members-display-div"></div>
	<?php
			return ob_get_clean();
		}
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
    }
    
	public static function plugin_deactivation( ) {
		self::write_log( 'PWTC Members plugin deactivated' );
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