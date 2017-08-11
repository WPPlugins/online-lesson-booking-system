<?php
/** 
 *	関数: my functions
 */

class olbFunction {
	/*
	 *	本文欄のショートコード[olb_weekly_schedule id="xx"]のidを取得: 
	 */
	public static function getTeacherIdFromPost($content){
		$content = str_replace("\"", '', stripslashes($content));
		$match = preg_match('/\[olb_weekly_schedule\sid=[0-9]+\]/', $content, $matches);
		if($match){
			$id = preg_replace('/[^0-9]/', '', $matches[0]);
			return $id;
		}
		return false;
	}

	/** 
	 *	エラーメッセージ: Error message
	 */
	public static function errorMessage( $information, $code){
		switch( $code ) {
			case 'PARAMETER_INSUFFICIENT':
				return __('Parameter is insufficient.', OLBsystem::TEXTDOMAIN);
			case 'INVALID_PARAMETER':
				return __('Invalid parameter is contained.', OLBsystem::TEXTDOMAIN);
			case 'NO_ROOM':
				return __('Specified record does not exist.', OLBsystem::TEXTDOMAIN);
			case 'NO_RECORD':
				return __('Specified record does not exist.', OLBsystem::TEXTDOMAIN);
			case 'NOT_RESERVED':
				return __('Specified timetable is not reserved. It cannot cancel.', OLBsystem::TEXTDOMAIN);
			case 'DOUBLE_BOOKING':
				return __('You have double-booking on this date and time.', OLBsystem::TEXTDOMAIN);
			case 'ALREADY_RESERVED':
				return __('Specified timetable is reserved. It cannot reserve.', OLBsystem::TEXTDOMAIN);
			case 'OTHERS_RECORD':
				return __('Specified timetable is reserved by other member. You cannot reserve.', OLBsystem::TEXTDOMAIN);
			case 'RESERVE_EXPIRED':
				return __('Member has reached expiration date.', OLBsystem::TEXTDOMAIN);
			case 'RESERVE_TIMEOVER':
				return __('Time is up. It cannot reserve.', OLBsystem::TEXTDOMAIN);
			case 'CANCEL_TIMEOVER':
				return __('Time is up. It cannot cancel.', OLBsystem::TEXTDOMAIN);
			case 'RESERVE_LIMIT_PER_DAY':
				return __('The number of reservations reached a limit per day.', OLBsystem::TEXTDOMAIN);
			case 'RESERVE_LIMIT_PER_MONTH':
				return __('The number of reservations reached a limit per month.', OLBsystem::TEXTDOMAIN);
			case 'CANCEL_FAILED':
				return __('Cancellation processing failed. It was not able to cancel.', OLBsystem::TEXTDOMAIN);
			case 'RESERVE_FAILED':
				return __('Reservation processing failed. It was not able to reserve.', OLBsystem::TEXTDOMAIN);
			case 'USER_SEND_ERROR':
				return __('Sending email to user failed.', OLBsystem::TEXTDOMAIN);
			case 'CHOOSE_TEACHER':
				return __('Teacher is not specified.', OLBsystem::TEXTDOMAIN);
			case 'CHOOSE_MEMBER':
				return __('Member is not specified.', OLBsystem::TEXTDOMAIN);
		//	case 'NO_SKYPE_ID':
		//		return __('No Skype-ID.', OLBsystem::TEXTDOMAIN);
			case 'NO_MEMBERS':
				return __('No members.', OLBsystem::TEXTDOMAIN);
			case 'NO_TEACHERS':
				return __('No teachers.', OLBsystem::TEXTDOMAIN);
			case 'NONEXISTENT_TEACHER':
				return __('Specified teacher does not exist.', OLBsystem::TEXTDOMAIN);
			case 'NONEXISTENT_MEMBER':
				return __('Specified member does not exist.', OLBsystem::TEXTDOMAIN);
			case 'NONCE_ERROR':
				return __('Onetime token error.', OLBsystem::TEXTDOMAIN);
			case 'SUCCESS_RESERVE':
				return __('It reserved.', OLBsystem::TEXTDOMAIN);
			case 'SUCCESS_CANCEL':
				return __('It canceled.', OLBsystem::TEXTDOMAIN);
			default:
				return false;
		}

	}

	/** 
	 *	メッセージ表示: Show message
	 */
	public static function showMessage($code){
		$information = '';
		return apply_filters( 'olb_error', $information, $code );
	}

}

class olbInitFunction {

	/** 
	 *	CONSTRUCT
	 */
	public function __construct() {
		$pluginfile = dirname(plugin_dir_path(__FILE__)).'/olb-system.php';
		add_action('widgets_init', create_function('', 'return register_widget("olbWidgetMembersMenu");'));
		add_action('widgets_init', create_function('', 'return register_widget("olbWidgetTeachersMenu");'));
		add_action('widgets_init', create_function('', 'return register_widget("olbWidgetAdminsMenu");'));
		register_activation_hook($pluginfile, array('olbHookAction', 'activation'));
		add_action('init', array(&$this, 'init'));
	}

	/** 
	 *	会員用管理画面メニュー削除: Hide menu in admin page (for member)
	 */
	public static function hideMenuAdminpage(){
		$options_key = OLBsystem::TEXTDOMAIN;
		$olb_options = get_option($options_key);
		if (!current_user_can('level_2') && $olb_options['settings']['profile_customize']) {
			add_action('admin_bar_menu', array('olbHookAction', 'hideAdminBarMenu'));
			add_action('wp_before_admin_bar_render', array('olbHookAction', 'addAdminBarMenu'));
			add_action('admin_head', array('olbHookAction', 'hideAdminHeadMenu'));
			add_action('wp_dashboard_setup', array('olbHookAction', 'hideDashboard'));
			add_action('admin_menu', array('olbHookAction', 'hideSideMenu'));
			add_filter('admin_footer_text', array('olbHookAction', 'hideAdminFooter'));
			add_action('admin_head-profile.php', array('olbHookAction', 'hideProfileItem'));
			add_filter('pre_site_transient_update_core', '__return_zero');
			remove_action('wp_version_check', 'wp_version_check');
			remove_action('admin_init', '_maybe_update_core');
		}
	}
	/** 
	 *	初期化(WordPress用): Init for WordPress
	 */
	public function init(){
		$pluginfile = dirname(plugin_dir_path(__FILE__)).'/olb-system.php';

		register_deactivation_hook($pluginfile, array('olbHookAction', 'deactivation'));
		register_uninstall_hook($pluginfile, array('olbHookAction', 'uninstall'));

		add_filter('cron_schedules', array('olbHookAction', 'cron_add_interval'));
		add_action('olb_cron',  array('olbHookAction', 'olb_cron_do'));
		add_action('wp', array('olbHookAction', 'olb_cron_update'));

		add_action('admin_notices', array('OLBsystem', 'showAdminNotices'));

		add_action('admin_init', array('olbInitFunction', 'hideMenuAdminpage'));
		add_action('manage_users_columns', array('olbHookAction', 'addUsersColumns'));
		add_action('manage_users_custom_column', array('olbHookAction', 'customUsersColumn'), 9, 3);
		add_filter('manage_users_sortable_columns', array('olbHookAction', 'sortableUsersColumns'));
		add_filter('request', array('olbHookAction', 'orderbyUsersColumn'));
		add_action('wp_login', array('olbHookAction', 'redirectAfterLogin'), 10, 2);
		add_action('wp_logout', array('olbHookAction', 'redirectAfterLogout'));

		add_filter('user_contactmethods', array('olbHookAction', 'addProfileContact'));
		add_action('show_user_profile', array('olbHookAction', 'showAddedProfile'));
		add_action('edit_user_profile', array('olbHookAction', 'addProfileMeta'));
		add_action('user_register', array('olbHookAction', 'inUserRegister'));
		add_action('profile_update', array('olbHookAction', 'inUpdateProfile'), 10, 2);
		add_action('delete_user', array('olbHookAction', 'inDeleteUser'));
		add_action('template_redirect', array('olbHookAction', 'inSpecialPageAccess'), 10);
		add_action('template_redirect', array('olbHookAction', 'formAction'), 11);
		add_action('wp_head', array('olbHookAction', 'loadFrontHeader'));
		add_action('wp_enqueue_scripts', array('olbHookAction', 'front_script'), 99);
		add_action('publish_post', array('olbHookAction', 'saveRoomURL'));
		add_action('trash_post', array('olbHookAction', 'deleteRoomURL'));

		add_action('admin_menu', array('olbAdminPage', 'addAdminMenu'));

		add_filter( 'olb_error',  array( 'olbFunction', 'errorMessage' ), 10, 2 );
		add_filter( 'olb_can_reservation',  array( 'olbTimetable', 'canReservation' ), 10, 5 );
		add_filter( 'olb_added_profile', array( 'olbHookAction', 'additional_fields'), 10, 2 );
		add_filter( 'olb_added_profile_admin', array( 'olbHookAction', 'additional_fields_admin'), 10, 2 );
		add_filter( 'olb_update_term', array( 'olbHookAction', 'update_term' ), 10, 1  );
		add_filter( 'olb_update_log', array( 'olbHookAction', 'update_log' ), 10, 1  );
		add_filter( 'olb_line_of_logs', array( 'olb_logs', 'line' ), 10, 2  );
		add_filter( 'olb_admin_pretending_user', array( 'olbFormAction', 'admin_pretending_user' ), 10, 2 );
		add_filter( 'the_content', array( 'olbhookAction', 'admin_access_mypage' ), 10, 1 ); 
 
		add_action( 'olb_users_custom_column', array('olbHookAction', 'customUsersColumnFilter'), 10, 3);
		add_action( 'user_new_form', array( 'olbhookAction', 'ex_newuser_fields' ) );
		add_action( 'user_register', array( 'olbhookAction', 'save_ex_newuser_fields' ) );
		add_filter( 'olb_ex_newuser_profile', array( 'olbhookAction', 'ex_newuser_profile' ), 10, 2 );
		add_action( 'admin_footer', array( 'olbhookAction', 'ex_script' ) );

		/** 
		 *	ショートコード: Short code
		 */

		// ウィジェットでショートコード利用: Use shortcode in widget
		add_filter('widget_text', 'do_shortcode');

		// 日別スケジュール: Daily schedule board
		add_shortcode('olb_daily_schedule', array('olbShortcode', 'showDailySchedule'));

		// 週間スケジュール: Weekly schedule board
		add_shortcode('olb_weekly_schedule', array('olbShortcode', 'showWeeklySchedule'));

		// スケージュール設定: Edit schedule board (for room manager)
		add_shortcode('olb_edit_schedule', array('olbShortcode', 'showEditSchedule'));

		// 予約フォーム: Reservation form
		add_shortcode('olb_reserve_form', array('olbShortcode', 'showReserveForm'));

		// 講師用キャンセルフォーム: Reservation form
		add_shortcode('olb_cancel_form', array('olbShortcode', 'showCancelForm'));

		// 講師用評価フォーム: Report form
		add_shortcode('olb_report_form', array('olbShortcode', 'showReportForm'));

		// 会員情報参照: Refer members information 
		add_shortcode('olb_refer_members_info', array('olbShortcode', 'referMembersInfo'));

		// 会員受講履歴参照: Refer members attendance history
		add_shortcode('olb_refer_members_history', array('olbShortcode', 'referMembersHistory'));

		// 会員受講履歴: Shows member's attendance history 
		add_shortcode('olb_members_history', array('olbShortcode', 'showMembersHistory'));

		// 会員予定表示: Show member's future schedule
		add_shortcode('olb_members_schedule', array('olbShortcode', 'showMembersSchedule'));

		// 講師講義履歴: Shows member's attendance history 
		add_shortcode('olb_teachers_history', array('olbShortcode', 'showRoomHistory'));

		// 講師予定表示: Show member's future schedule
		add_shortcode('olb_teachers_schedule', array('olbShortcode', 'showRoomSchedule'));

		// ログイン中ユーザー情報の表示: Show member data
		add_shortcode('olb_member_data', array('olbShortcode', 'showMemberData'));

		// ログイン中ユーザーの有効期限表示: Show members term of validity
		add_shortcode('olb_if_expire', array('olbShortcode', 'showIfExpire'));

		// ログイン中会員ユーザーにのみ表示: Show block only member
		add_shortcode('olb_if_member', array('olbShortcode', 'showIfMember'));

		// ログイン中講師にのみ表示: Show block only room-manager
		add_shortcode('olb_if_manager', array('olbShortcode', 'showIfManager'));

		// ポイント更新履歴: Shows member's update ticket logs 
		add_shortcode('olb_ticket_logs', array('olbShortcode', 'show_ticket_logs'));

	}

}
?>
