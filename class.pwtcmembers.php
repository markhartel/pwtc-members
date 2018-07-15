<?php

class PwtcMembers {

	const VIEW_MEMBERS_CAP = 'pwtc_view_members';
	const EDIT_MEMBERS_CAP = 'pwtc_edit_members';
	const ADD_MEMBERS_CAP = 'pwtc_add_members';
	const DELETE_MEMBERS_CAP = 'pwtc_delete_members';
	const EDIT_LEADERS_CAP = 'pwtc_edit_leaders';

    private static $initiated = false;

	public static function init() {
		if ( !self::$initiated ) {
			self::init_hooks();
		}
    }
    
	// Initializes plugin WordPress hooks.
	private static function init_hooks() {
		self::$initiated = true;
		
		add_action( 'template_redirect', 
			array( 'PwtcMembers', 'download_user_list' ) );
        
		add_action( 'wp_ajax_pwtc_members_lookup', 
            array( 'PwtcMembers', 'members_lookup_callback') );
		add_action( 'wp_ajax_pwtc_members_fetch_profile', 
            array( 'PwtcMembers', 'members_fetch_profile_callback') );
            
		// Register shortcode callbacks
		add_shortcode('pwtc_members_lookup', 
			array( 'PwtcMembers', 'shortcode_members_lookup'));
//		add_shortcode('pwtc_members_download', 
//			array( 'PwtcMembers', 'shortcode_members_download'));

	}
	
	public static function download_user_list() {
		//self::write_log('In download_user_list');
		if (current_user_can(self::VIEW_MEMBERS_CAP)) {
			//self::write_log('Past current_user_can');
			if (isset($_POST['pwtc-members-download'])) {
				//self::write_log('Post argument verified');
				/*
				$query_args = [
					'meta_key' => 'last_name',
					'orderby' => 'meta_value',
					'order' => 'ASC'
				];

				$name = $_POST['name'];

				$role = $_POST['role'];
				if ($role != 'all') {
					$query_args['role'] = $role;
				}
				*/

				//self::write_log($query_args);

				header('Content-Description: File Transfer');
				header("Content-type: text/txt");
				header("Content-Disposition: attachment; filename=test.txt");
				echo 'This is a test.';

/*
				$today = date('Y-m-d', current_time('timestamp'));
				header('Content-Description: File Transfer');
				header("Content-type: text/csv");
				header("Content-Disposition: attachment; filename={$today}_{$name}.csv");
				//self::write_log('Past header write');
				$fp = fopen('php://output', 'w');
				//self::write_log('Past fopen');
				fputcsv($fp, ['Email', 'Last Name', 'First Name']);
				//self::write_log('Past fputcsv header write');
				$user_query = new WP_User_Query( $query_args );
				//self::write_log('Past WP_User_Query');
				$members = $user_query->get_results();
				//self::write_log($members);
				if ( !empty($members) ) {
					foreach ( $members as $member ) {
						$member_info = get_userdata($member->ID);
						//self::write_log($member_info);
						fputcsv($fp, [$member_info->user_email, $member_info->last_name, $member_info->first_name]);
					}
				}

				fclose($fp);
				//self::write_log('Past fclose');
*/
				die;
			}
		}
	}

	public static function members_fetch_profile_callback() {
		$userid = intval($_POST['userid']);
		$member_info = get_userdata($userid);
        $response = array(
			'userid' => $userid,
			'first_name' => $member_info->first_name,
			'last_name' => $member_info->last_name,
			'email' => $member_info->user_email
		);

        echo wp_json_encode($response);
        wp_die();
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
		
		if (isset($_POST['include'])) {
			$roles = self::parse_include_exclude($_POST['include']);
			if (!empty($roles)) {
				$query_args['role__in'] = $roles;
			}
		}

		if (isset($_POST['exclude'])) {
			$roles = self::parse_include_exclude($_POST['exclude']);
			if (!empty($roles)) {
				$query_args['role__not_in'] = $roles;
			}
		}

		if (isset($_POST['role'])) {
			$role = $_POST['role'];
			if ($role != 'all') {
				if (substr($role, 0, 1) === "!") {
					$not_role = substr($role, 1, strlen($role)-1);
					if (isset($query_args['role__not_in'])) {
						$query_args['role__not_in'][] = $not_role;
					}
					else {
						$query_args['role__not_in'] = [$not_role];
					}
				}
				else {
					$query_args['role__in'] = [$role];
				}
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
					'email' => $member_info->user_email,
					'phone' => ''
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
		global $wp_roles;
		$roles2 = [];
		foreach ($roles as $role) {
			if ($role == 'all') {
				$roles2[] = [
					'label' => 'All',
					'value' => $role,
				];
			}
			else {
				if (substr($role, 0, 1) === "!") {
					$not_role = substr($role, 1, strlen($role)-1);
					if (isset($wp_roles->roles[$not_role])) {
						$roles2[] = [
							'label' => 'Not ' . $wp_roles->roles[$not_role]['name'],
							'value' => $role,
						];	
					}
				}
				else {
					if (isset($wp_roles->roles[$role])) {
						$roles2[] = [
							'label' => $wp_roles->roles[$role]['name'],
							'value' => $role,
						];	
					}
				}
			}
		}
		return $roles2;
	}

	public static function parse_include_exclude($role_str) {
		$roles = [];
		if (!empty($role_str)) {
			$tok = strtok($role_str, ",");
			while ($tok !== false) {
				$roles[] = $tok;
				$tok = strtok(",");
			}
		}
		return $roles;
	}
    
	/*************************************************************/
	/* Shortcode report generation functions
	/*************************************************************/
 
	// Generates the [pwtc_members_lookup] shortcode.
	public static function shortcode_members_lookup($atts) {
		$a = shortcode_atts(array('limit' => 10, 'roles' => 'all', 'include' => '', 'exclude' => ''), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return 'Please log in to search the membership directory.';
		}
		else {
			$can_view = current_user_can(self::VIEW_MEMBERS_CAP);
			$can_edit = current_user_can(self::EDIT_MEMBERS_CAP);
			$can_add = current_user_can(self::ADD_MEMBERS_CAP);
			$can_delete = current_user_can(self::DELETE_MEMBERS_CAP);
			$can_edit_leaders = current_user_can(self::EDIT_LEADERS_CAP);
			$roles = self::parse_roles($a['roles']);
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 

			function populate_members_table(members) {
				var header = '<table class="pwtc-mapdb-rwd-table"><tr><th>Last Name</th><th>First Name</th><th>Email</th><th>Phone</th>' +
				<?php if ($can_view or $can_edit or $can_edit_leaders or $can_delete) { ?>
				'<th>Actions</th>' +
				<?php } ?>
				'</tr></table>';
				$('.pwtc-members-display-div').append(header);
				members.forEach(function(item) {
					var data = '<tr userid="' + item.ID + '">' +
					'<td data-th="Last Name">' + item.last_name + '</td>' + 
					'<td data-th="First Name">' + item.first_name + '</td>' +
					'<td data-th="Email">' + item.email + '</td>' +
					'<td data-th="Phone">' + item.phone + '</td>' +
					<?php if ($can_view or $can_edit or $can_edit_leaders or $can_delete) { ?>
					'<td data-th="Actions">' +
						<?php if ($can_edit or $can_edit_leaders) { ?>
						'<a class="member-profile-a" title="Edit member profile."><i class="fa fa-pencil-square"></i></a> ' +
						<?php } else if ($can_view) { ?>
						'<a class="member-profile-a" title="View member profile."><i class="fa fa-eye"></i></a> ' +
						<?php } ?>
						<?php if ($can_delete) { ?>
						'<a class="member-delete-a" title="Delete member."><i class="fa fa-user-times"></i></a> ' +
						<?php } ?>
					'</td>' +
					<?php } ?>
					'</tr>';
					$('.pwtc-members-display-div table').append(data);    
				});
				<?php if ($can_view or $can_edit or $can_edit_leaders) { ?>
				$('.pwtc-members-display-div table .member-profile-a').on('click', function(e) {
					$("#edit-user-profile input[type='text']").val('');
					var userid = $(this).parent().parent().attr('userid');
					$("#edit-user-profile input[name='userid']").val(userid);
					var action = "<?php echo admin_url('admin-ajax.php'); ?>";
					var data = {
						'action': 'pwtc_members_fetch_profile',
						'userid': userid
					};
					$.post(action, data, display_user_profile_cb);
				});
				<?php } ?>
				<?php if ($can_delete) { ?>
				$('.pwtc-members-display-div table .member-delete-a').on('click', function(e) {
					var userid = $(this).parent().parent().attr('userid');
					$("#delete-user-profile").html('This is the popup that deletes member user ID ' + userid + '.');
					$.fancybox.open( {href : '#delete-user-profile'} );
				});
				<?php } ?>
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

			<?php if ($can_view or $can_edit or $can_edit_leaders) { ?>
			function display_user_profile_cb(response) {
				var res = JSON.parse(response);
				if (res.error) {
				}
				else {
					$("#edit-user-profile input[name='first_name']").val(res.first_name);
					$("#edit-user-profile input[name='last_name']").val(res.last_name);
					$("#edit-user-profile input[name='email']").val(res.email);
					$('#user-profile-tabs').foundation('selectTab', 'user-profile-panel1');
					$.fancybox.open( {href : '#edit-user-profile'} );
				}
			}
			<?php } ?>

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
						$('.pwtc-members-display-div').append('<div><i class="fa fa-exclamation-triangle"></i> No members found.</div>');					
					}
				}
			}   

			function load_members_table(mode) {
                var action = "<?php echo admin_url('admin-ajax.php'); ?>";
				var data = {
					'action': 'pwtc_members_lookup',
					'include': $(".pwtc-members-search-div .search-frm input[name='include']").val().trim(),
					'exclude': $(".pwtc-members-search-div .search-frm input[name='exclude']").val().trim(),
					'limit': <?php echo $a['limit'] ?>
				};
				if (mode != 'search') {
					data.role = $(".download-frm input[name='role']").val();
					data.email = $(".download-frm input[name='email']").val();
					data.last_name = $(".download-frm input[name='last_name']").val();
					data.first_name = $(".download-frm input[name='first_name']").val();
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
					data.role = $(".pwtc-members-search-div .search-frm .role").val();
					data.email = $(".pwtc-members-search-div .search-frm input[name='email']").val().trim();
					data.last_name = $(".pwtc-members-search-div .search-frm input[name='last_name']").val().trim();
					data.first_name = $(".pwtc-members-search-div .search-frm input[name='first_name']").val().trim();
					$(".download-frm input[name='role']").val(data.role);
					$(".download-frm input[name='email']").val(data.email);
					$(".download-frm input[name='last_name']").val(data.last_name);
					$(".download-frm input[name='first_name']").val(data.first_name);	
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

			<?php if ($can_edit or $can_edit_leaders) { ?>
			$('#edit-user-profile .profile-frm').on('submit', function(evt) {
				evt.preventDefault();
				$.fancybox.close();
			});
			<?php } ?>
			<?php if ($can_edit_leaders and !$can_edit) { ?>
			$("#edit-user-profile .profile-frm #user-profile-panel1 input[type='text']").attr("disabled", "disabled");
			$("#edit-user-profile .profile-frm #user-profile-panel1 select").attr("disabled", "disabled");
			$("#edit-user-profile .profile-frm #user-profile-panel2 input[type='text']").attr("disabled", "disabled");
			$("#edit-user-profile .profile-frm #user-profile-panel2 select").attr("disabled", "disabled");
			<?php } else if (!$can_edit_leaders and !$can_edit) { ?>
			$("#edit-user-profile .profile-frm input[type='text']").attr("disabled", "disabled");
			$("#edit-user-profile .profile-frm select").attr("disabled", "disabled");
			<?php } ?>

			<?php if ($can_add) { ?>
			$('.new-member-a').on('click', function(e) {
				$.fancybox.open( {href : '#add-user-profile'} );
			});
			<?php } ?>

			<?php if ($can_view) { ?>
			$('.download-member-a').on('click', function(e) {
				$('.download-frm').submit();
			});
			<?php } ?>

            load_members_table('search');
		});
	</script>
	<?php if ($can_add) { ?>
	<div id="add-user-profile" style="display: none">
		This is the popup that adds a new member.
	</div>
	<?php } ?>
	<?php if ($can_delete) { ?>
	<div id="delete-user-profile" style="display: none">
		This is the popup that deletes a member.
	</div>
	<?php } ?>
	<?php if ($can_view or $can_edit or $can_edit_leaders) { ?>
	<div id="edit-user-profile" style="display: none">
		<form class="profile-frm">
			<ul class="tabs" data-tabs id="user-profile-tabs">
				<li class="tabs-title is-active"><a href="#user-profile-panel1">Basic Info</a></li>
				<li class="tabs-title"><a href="#user-profile-panel2">Membership</a></li>
				<li class="tabs-title"><a href="#user-profile-panel3">Ride Leader</a></li>
			</ul>
			<div class="tabs-content" data-tabs-content="user-profile-tabs">
				<div class="tabs-panel is-active" id="user-profile-panel1">
					<div class="row">
						<div class="small-6 columns">
							<label>First Name
								<input type="text" name="first_name"/>
							</label>
						</div>
						<div class="small-6 columns">
							<label>Last Name
								<input type="text" name="last_name"/>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label>Email
								<input type="text" name="email"/>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label>Phone
								<input type="text" name="phone"/>
							</label>
						</div>
					</div>
					<div class="row">
						<div class="small-12 medium-12 columns">
							<label>Street Address 
								<input type="text" name="address" />
							</label>
						</div>
					</div>
					<div class="row">
						<div class="small-6 large-4 columns">
							<label>City
								<input type="text" name="city" />
							</label>
						</div>
						<div class="small-6 large-4 columns">
							<label>State
								<input type="text" name="state" />
							</label>
						</div>
						<div class="small-6 large-4 columns">
							<label>Zipcode
								<input type="text" name="zip" />
							</label>
						</div>
					</div>
				</div>
				<div class="tabs-panel" id="user-profile-panel2">
					<div class="row">
						<div class="small-12 large-4 columns">
							<label>Date Joined
								<input type="text" name="date_joined" />
							</label>
						</div>
						<div class="small-12 large-4 columns">
							<label>Date Updated
								<input type="text" name="date_updated" />
							</label>
						</div>
						<div class="small-12 large-4 columns">
							<label>Date Expires
								<input type="text" name="date_expires" />
							</label>
						</div>
					</div>
					<div class="row">
						<div class="small-12 large-4 columns">
							<label>Rider ID
								<input type="text" name="rider_id" />
							</label>
						</div>
						<div class="small-12 large-4 columns">
							<label>Show in Membership Directory
								<select>
									<option value="no" selected>No</option>
									<option value="yes">Yes</option>
								</select>
							</label>
						</div>
						<div class="small-12 large-4 columns">
							<label>Payment is Pending
								<select>
									<option value="no" selected>No</option>
									<option value="yes">Yes</option>
								</select>
							</label>
						</div>
					</div>
					<div class="row">
						<div class="small-12 large-4 columns">
							<label>Elected Position
								<select>
									<option value="none" selected>None</option>
									<option value="President">President</option>
									<option value="Vice President">Vice President</option>
									<option value="Membership Secretary">Membership Secretary</option>
									<option value="Recording Secretary">Recording Secretary</option>
									<option value="Treasurer">Treasurer</option>
									<option value="Road Captain (Jan-Dec)">Road Captain (Jan-Dec)</option>
									<option value="Road Captain (Jul-Jun)">Road Captain (Jul-Jun)</option>
									<option value="Member at Large (Jan-Dec)">Member at Large (Jan-Dec)</option>
									<option value="Member at Large (Jul-Jun)">Member at Large (Jul-Jun)</option>
								</select>
							</label>
						</div>
						<div class="small-12 large-4 columns">
							<label>Appointed Position
							<select>
									<option value="none" selected>None</option>
									<option value="Pioneer Coordinator">Pioneer Coordinator</option>
									<option value="Historian">Historian</option>
									<option value="Librarian">Librarian</option>
									<option value="Program Director">Program Director</option>
									<option value="QR Reporter">QR Reporter</option>
									<option value="Raffle Coordinator">Raffle Coordinator</option>
									<option value="Statistician">Statistician</option>
									<option value="Web Master">Web Master</option>
									<option value="Banquet">Banquet</option>
									<option value="Helmet Committee">Helmet Committee</option>
									<option value="Boxes and Bob">Boxes and Bob</option>
								</select>
							</label>
						</div>
						<div class="small-12 large-4 columns">
							<label>Membership Type
								<select>
									<option value="Paid" selected>Paid</option>
									<option value="Service">Service</option>
									<option value="Lifetime">Lifetime</option>
								</select>
							</label>
						</div>
					</div>
				</div>
				<div class="tabs-panel" id="user-profile-panel3">
					<div class="row">
						<div class="small-12 medium-6 columns">
							<label>Use Contact Email
								<select>
									<option value="no" selected>No, use account email</option>
									<option value="yes">Yes</option>
								</select>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label>Contact Email
								<input type="text" name="contact_email"/>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label>Contact Voice Phone
								<input type="text" name="voice_phone"/>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label>Contact Text Phone
								<input type="text" name="text_phone"/>
							</label>
						</div>
						<div class="small-12 medium-6 columns">
							<label>Is Ride Leader
								<select>
									<option value="no" selected>No</option>
									<option value="yes">Yes</option>
								</select>
							</label>
						</div>
					</div>
				</div>
			</div>
			<div class="row column">
				<input type="hidden" name="userid"/>
				<?php if ($can_edit or $can_edit_leaders) { ?>
				<input class="accent button" type="submit" value="Submit"/>
				<?php } ?>
			</div>
		</form>
	</div>
	<?php } ?>
	<div class='pwtc-members-search-div'>
		<ul class="accordion" data-accordion data-allow-all-closed="true">
			<li class="accordion-item" data-accordion-item>
				<a href="#" class="accordion-title"><i class="fa fa-search"></i> Click Here To Search</a>
				<div class="accordion-content" data-tab-content>
					<form class="search-frm">
						<input type="hidden" name="include" value="<?php echo $a['include']; ?>"/>
						<input type="hidden" name="exclude" value="<?php echo $a['exclude']; ?>"/>
						<?php if (count($roles) == 1) { ?>
						<input class="role" type="hidden" name="role" value="<?php echo $roles[0]['value']; ?>"/>
						<?php } ?>
						<div>
							<div class="row">
								<div class="small-12 medium-3 columns">
                        			<label>Last Name
										<input type="text" name="last_name"/>
                        			</label>
                    			</div>
								<div class="small-12 medium-3 columns">
                        			<label>First Name
										<input type="text" name="first_name"/>
                        			</label>
                    			</div>
								<div class="small-12 medium-3 columns">
                        			<label>Email
										<input type="text" name="email"/>
                        			</label>
                    			</div>
								<?php if (count($roles) > 1) { ?>
								<div class="small-12 medium-3 columns">
                                	<label>Role
							        	<select class="role">
										<?php foreach ( $roles as $role ) { ?>
											<option value="<?php echo $role['value']; ?>"><?php echo $role['label']; ?></option>
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
	<div class="button-group">
		<?php if ($can_add) { ?>
  		<a class="new-member-a button" title="Create new member."><i class="fa fa-user-plus"></i> New</a>
		<?php } ?>
		<?php if ($can_view) { ?>
  		<a class="download-member-a button" title="Download member information."><i class="fa fa-download"></i> Download</a>
		<?php } ?>
	</div>
	<div class="pwtc-members-display-div"></div>
	<form class="download-frm">
		<input type="hidden" name="pwtc-members-download" value="yes"/>
		<input type="hidden" name="include" value="<?php echo $a['include']; ?>"/>
		<input type="hidden" name="exclude" value="<?php echo $a['exclude']; ?>"/>
		<input type="hidden" name="role" value="<?php echo $roles[0]['value']; ?>"/>
		<input type="hidden" name="last_name" value=""/>
		<input type="hidden" name="first_name" value=""/>
		<input type="hidden" name="email" value=""/>
	</form>
	<?php
			return ob_get_clean();
		}
	}

	// Generates the [pwtc_members_download] shortcode.
/*
	public static function shortcode_members_download($atts) {
		$a = shortcode_atts(array('role' => 'all', 'name' => 'unnamed', 'label' => 'Unlabeled', 'type' => 'csv'), $atts);
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			return 'Please log in to download the membership directory.';
		}
		else if (current_user_can(self::VIEW_MEMBERS_CAP) == false) {
			return 'You are not allowed to download the membership directory.';
		}
		else {
			ob_start();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) { 
			$('.pwtc-members-download-btn a').on('click', function(e) {
        		e.preventDefault();
				//$('.pwtc-members-download-btn form').submit();
				$(this).parent().find('form').submit();
			});
		});
	</script>
	<div class="pwtc-members-download-btn">
		<a href="#" class="button"><i class="fa fa-download"></i> <?php echo $a['label']; ?></a>
		<form method="post">
			<input type="hidden" name="pwtc-members-download" value="yes"/>
			<input type="hidden" name="role" value="<?php echo $a['role']; ?>"/>
			<input type="hidden" name="type" value="<?php echo $a['type']; ?>"/>
			<input type="hidden" name="name" value="<?php echo $a['name']; ?>"/>
		</form>
	</div>
	<?php
			return ob_get_clean();
		}
	}
*/

	/*************************************************************/
	/* Plugin capabilities management functions.
	/*************************************************************/

	public static function add_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->add_cap(self::VIEW_MEMBERS_CAP);
		$admin->add_cap(self::EDIT_MEMBERS_CAP);
		$admin->add_cap(self::ADD_MEMBERS_CAP);
		$admin->add_cap(self::DELETE_MEMBERS_CAP);
		self::write_log('PWTC Members plugin added capabilities to administrator role');
	}

	public static function remove_caps_admin_role() {
		$admin = get_role('administrator');
		$admin->remove_cap(self::VIEW_MEMBERS_CAP);
		$admin->remove_cap(self::EDIT_MEMBERS_CAP);
		$admin->remove_cap(self::ADD_MEMBERS_CAP);
		$admin->remove_cap(self::DELETE_MEMBERS_CAP);
		self::write_log('PWTC Members plugin removed capabilities from administrator role');
	}

	public static function create_ms_role() {
		$ms = get_role('membership_secretary');
		if ($ms === null) {
			$ms = add_role('membership_secretary', 'Membership Secretary');
			self::write_log('PWTC Members plugin added membership_secretary role');
		}
		if ($ms !== null) {
			$ms->add_cap(self::VIEW_MEMBERS_CAP);
			$ms->add_cap(self::EDIT_MEMBERS_CAP);
			$ms->add_cap(self::ADD_MEMBERS_CAP);
			$ms->add_cap(self::DELETE_MEMBERS_CAP);
				self::write_log('PWTC Members plugin added capabilities to membership_secretary role');
		} 
		$captain = get_role('ride_captain'); 
		if ($captain !== null) {
			$captain->add_cap(self::VIEW_MEMBERS_CAP);
			$captain->add_cap(self::EDIT_LEADERS_CAP);
			pwtc_mileage_write_log('PWTC Members plugin added capabilities to ride_captain role');
		} 
	}

	public static function remove_ms_role() {
		$users = get_users(array('role' => 'membership_secretary'));
		if (count($users) > 0) {
			$stat = get_role('membership_secretary');
			$stat->remove_cap(self::VIEW_MEMBERS_CAP);
			$stat->remove_cap(self::EDIT_MEMBERS_CAP);
			$stat->remove_cap(self::ADD_MEMBERS_CAP);
			$stat->remove_cap(self::DELETE_MEMBERS_CAP);
			self::write_log('PWTC Members plugin removed capabilities from membership_secretary role');
		}
		else {
			$ms = get_role('membership_secretary');
			if ($ms !== null) {
				remove_role('membership_secretary');
				self::write_log('PWTC Members plugin removed membership_secretary role');
			}
		}
		$captain = get_role('ride_captain'); 
		if ($captain !== null) {
			$captain->remove_cap(self::VIEW_MEMBERS_CAP);
			$captain->remove_cap(self::EDIT_LEADERS_CAP);
			pwtc_mileage_write_log('PWTC Members plugin removed capabilities from ride_captain role');
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
		self::add_caps_admin_role();
		self::create_ms_role();
    }
    
	public static function plugin_deactivation( ) {
		self::write_log( 'PWTC Members plugin deactivated' );
		self::remove_caps_admin_role();
		self::remove_ms_role();
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