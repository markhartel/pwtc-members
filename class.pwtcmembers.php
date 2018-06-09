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

		if (isset($_POST['first_name'])) {
			if (!isset($query_args['meta_query'])) {
				$query_args['meta_query'] = [];
			}
			$query_args['meta_query'][] = [
				'key'     => 'first_name',
				'value'   => $_POST['first_name'],
				'compare' => 'LIKE'   
			];
		}

		if (isset($_POST['last_name'])) {
			if (!isset($query_args['meta_query'])) {
				$query_args['meta_query'] = [];
			}
			$query_args['meta_query'][] = [
				'key'     => 'last_name',
				'value'   => $_POST['last_name'],
				'compare' => 'LIKE'   
			];
		}

		if (isset($_POST['email'])) {
			$query_args['search'] = '*' . esc_attr($_POST['email']) . '*';
			$query_args['search_columns'] = array( 'user_email' );
		}	
		
		if (isset($_POST['role'])) {
			$role = $_POST['role'];
			if ($role != 'all') {
				$query_args['role'] = $role;
			}
		}
		
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
	
	public static function parse_roles($role_str) {
		$roles = [];
		$tok = strtok($role_str, ",");
		while ($tok !== false) {
			$roles[] = $tok;
			$tok = strtok(",");
		}
		return $roles;
	}
    
	/*************************************************************/
	/* Shortcode report generation functions
	/*************************************************************/
 
	// Generates the [pwtc_members_lookup] shortcode.
	public static function shortcode_members_lookup($atts) {
		$a = shortcode_atts(array('limit' => 10, 'roles' => 'all'), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return 'Please log in to search the members directory.';
		}
		else {
			$roles = self::parse_roles($a['roles']);
			//self::write_log($roles);
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
                var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_members_lookup',
					'role': $(".pwtc-members-search-div .search-frm .role").val(),
					'email': $(".pwtc-members-search-div .search-frm input[name='email']").val().trim(),
					'last_name': $(".pwtc-members-search-div .search-frm input[name='last_name']").val().trim(),
					'first_name': $(".pwtc-members-search-div .search-frm input[name='first_name']").val().trim(),
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

			$('.pwtc-members-search-div .search-frm').on('submit', function(evt) {
				evt.preventDefault();
				load_members_table('search');
			});

			$('.pwtc-members-search-div .search-frm .reset-btn').on('click', function(evt) {
				evt.preventDefault();
				$(".pwtc-members-search-div .search-frm input[type='text']").val(''); 
				$('.pwtc-members-display-div').empty();
				load_members_table('search');
			});

            load_members_table('search');
		});
	</script>
	<div class='pwtc-members-search-div'>
		<ul class="accordion" data-accordion data-allow-all-closed="true">
			<li class="accordion-item" data-accordion-item>
				<a href="#" class="accordion-title"><i class="fa fa-search"></i> Click Here To Search</a>
				<div class="accordion-content" data-tab-content>
					<form class="search-frm">
						<?php if (count($roles) == 1) { ?>
						<input class="role" type="hidden" name="role" value="<?php echo $roles[0]; ?>"/>
						<?php } ?>
						<div>
							<div class="row">
								<div class="small-12 medium-4 columns">
                        			<label>Email
										<input type="text" name="email"/>
                        			</label>
                    			</div>
								<div class="small-12 medium-4 columns">
                        			<label>Last Name
										<input type="text" name="last_name"/>
                        			</label>
                    			</div>
								<div class="small-12 medium-4 columns">
                        			<label>First Name
										<input type="text" name="first_name"/>
                        			</label>
                    			</div>
								<?php if (count($roles) > 1) { ?>
								<div class="small-12 medium-4 columns">
                                	<label>Role
							        	<select class="role">
										<?php foreach ( $roles as $role ) { ?>
											<option value="<?php echo $role; ?>"><?php echo $role; ?></option>
										<?php } ?>
                                        </select>                                
                                	</label>
                            	</div>
								<?php } ?>
							</div>
							<div class="row column">
								<input class="accent button" type="submit" value="Search"/>
								<input class="reset-btn accent button" type="button" value="Reset"/>
							</div>
						</div>
					</form>
				</div>
			</li>
		</ul>
	</div>
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