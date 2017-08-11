<?php
/** 
 *	タイムテーブル予約表の設定: Settings for Timetable Reservation 
 */

load_plugin_textdomain( OLBsystem::TEXTDOMAIN, false, dirname(dirname(plugin_basename(__FILE__))).'/languages');

class OLBsystem {
	// システム設定
	const TABLEPREFIX = 'olb_';
	const TEXTDOMAIN = 'olbsystem';
	const URL = 'http://olbsys.com/';
	const PLUGIN_VERSION = '0.8.0';
	const DB_VERSION = '0.4.0';

	// タイムテーブル基本設定
	public $starttime           = null;		// 表示開始時刻(default: 09:00)
	public $endtime             = null;		// 表示終了時刻(default: 20:00)
	public $interval            = null;		// 時間間隔(default: 30min)
	public $reserve_deadline    = null;		// 受付時間(min)
	public $cancel_deadline     = null;		// キャンセル受付時(min)
	public $daymax              = null;		// 1ページ当たりの表示日数(default: 1週間)
	public $term                = null;		// 1ページ当たりの表示時間(default: 1週間)
	public $opentime            = null;		// [Option] 営業開始時刻
	public $closetime           = null;		// [Option] 営業終了時刻
	public $limit_per_day       = null;		// 1日当たりの予約回数上限
	public $limit_per_month     = null;		// 1ヶ月当たりの予約回数上限
	public $free                = null;		// 無料予約回数上限
	public $ticket_system       = null;		// チケット制（会員別の予約可能数）の使用
	public $ticket_metakey      = null;		// 会員のチケット数保存キー(user_meta)
	public $ticket_expire       = null;		// チケット有効期限

	// タイムテーブル表示パラメータ($_GET)
	public $room_id             = null;		// 表示させる講師ID
	public $user_id             = null;		// 閲覧しているユーザーID
	public $operator            = null;		// 捜査中のユーザー情報
	public $startdate           = null;		// 表示開始日

	// タイムテーブル表示用変数
	public $enddate             = null;		// 表示終了日
	public $rooms               = null;		// 講師情報

	public $qs                  = array();	// $_SERVER['QUERY_STRING']

	public $mypluginurl         = null;		// プラグインURL
	public $home                = null;		// サイトURL
	public $daily_schedule_page = null;		// 日別スケジュール表示ページ
	public $reserve_form_page   = null;		// 予約フォームページ
	public $cancel_form_page    = null;		// 予約キャンセルページ
	public $report_form_page    = null;		// 評価ページ
	public $edit_schedule_page  = null;		// スケジュール設定ページ
	public $login_page          = null;		// ログインページ
	public $members_info_page   = null;		// 会員情報参照ページ

	public $preserve_past       = null;		// 過去スケジュール保存日数
	public $cron_interval       = null;		// cron実行間隔

	/** 
	 *	CONSTRUCT
	 */
	public function __construct() {
		$mywpinit = new olbInitFunction();
		add_action( 'plugins_loaded', array( &$this, 'init' ) );
		add_action( 'plugins_loaded', array('olbHookAction', 'db_update_check'), 11 );
		add_action( 'init', array('olbHookAction', 'plugin_update_check'), 11 );
	}
	
	/**
	 *	初期化
	 */
	public function init(){

		$options                   = self::getPluginOptions( 'settings' );
		$pages                     = self::getPluginOptions( 'specialpages' );

		$this->mypluginurl         = dirname(plugin_dir_url(__FILE__)).'/';

		$this->home                = get_option('home');		// サイトURL

		$this->daily_schedule_page = $pages['daily_schedule_page'];			// 日別スケジュール表示ページ
		$this->reserve_form_page   = $pages['reserve_form_page'];			// 予約フォームページ
		$this->cancel_form_page    = $pages['cancel_form_page'];			// 予約キャンセルページ
		$this->report_form_page    = $pages['report_form_page'];			// 評価ページ
		$this->edit_schedule_page  = $pages['edit_schedule_page'];			// スケジュール設定ページ
		$this->member_page         = $pages['member_page'];					// 会員ページ
		$this->login_page          = $pages['login_page'];					// ログインページ
		$this->members_info_page   = $pages['members_info_page'];			// 会員情報参照ページ

		$this->starttime           = $options['starttime'];					// 表示開始時刻(default: 09:00)
		$this->endtime             = $options['endtime'];					// 表示終了時刻(default: 20:00)
		$this->interval            = $options['interval'];					// 時間間隔(default: 30min)
		$this->reserve_deadline    = $options['reserve_deadline'];			// 受付時間(min)
		$this->cancel_deadline     = $options['cancel_deadline'];			// キャンセル受付時間(min)
		$this->daymax              = $options['daymax'];					// 1ページ当たりの表示日数(default: 1週間)
		$this->term                = $options['term'];						// 1ページ当たりの表示時間(default: 1週間)
		$this->opentime            = $options['starttime'];					// [Option] 営業開始時刻
		$this->closetime           = $options['endtime'];					// [Option] 営業終了時刻
		$this->limit_per_day       = $options['limit_per_day'];				// １日当たりの予約回数上限
		$this->limit_per_month     = $options['limit_per_month'];			// １日当たりの予約回数上限
		$this->free                = $options['free'];						// 無料予約回数
		$this->ticket_system       = $options['ticket_system'];				// チケット制（会員別の予約可能数）の使用
		$this->ticket_metakey      = $options['ticket_metakey'];			// 会員のチケット数保存キー（user_meta）
		$this->ticket_expire       = $options['ticket_expire'];				// チケットの有効期限（default:60days）

		$this->room_per_page       = $options['room_per_page'];				// 日別スケジュールの1ページ当たりの表示数
		$this->preserve_past       = $options['preserve_past'];				// 過去スケジュール保存日数
		$this->profile_customize   = $options['profile_customize'];			// プロフィールページの改変
		$this->cron_interval       = $options['cron_interval'];				// CRON実行間隔

		if (!empty($_SERVER['QUERY_STRING'])){
			parse_str($_SERVER['QUERY_STRING'], $this->qs);
		}

		$this->current_user();
		$this->operator = new olbAuth();
		if ( isset( $this->operator->data['id'] ) ) {
			if ( $this->operator->isMember() ) {
				$this->user_id = $this->operator->data['id'];
			} elseif (!empty($this->qs['user_id']) && preg_match('/^[0-9]+$/', $this->qs['user_id'])){
				$user = new olbAuth($this->qs['user_id']);
				if ($user->data['id']) {
					$this->user_id = $this->qs['user_id'];
				}
			}

			if ( $this->operator->isRoomManager() ) {
				$this->room_id = $this->operator->data['id'];
			} elseif ( !empty($this->qs['room_id']) && preg_match('/^[0-9]+$/', $this->qs['room_id']) ) {
				$user = new olbAuth($this->qs['room_id']);
				if ( $user->data['id'] ) {
					$this->room_id = $this->qs['room_id'];
				}
			}
		}

		$currenttime = current_time('timestamp');
		/*
		 *	ex.
		 *	closetime: 22:00  now: 2013-06-01 21:00  -> OK 
		 *	closetime: 22:00  now: 2013-06-01 23:00  -> OK 
		 *	closetime: 22:00  now: 2013-06-02 00:00  -> OK 
		 *
		 *	closetime: 24:00  now: 2013-06-01 23:00  -> OK
		 *	closetime: 24:00  now: 2013-06-02 00:00  -> now: 2013-06-01 24:00
		 *	closetime: 24:00  now: 2013-06-02 01:00  -> OK
		 *
		 *	closetime: 25:00  now: 2013-06-01 23:00  -> OK
		 *	closetime: 25:00  now: 2013-06-02 00:00  -> now: 2013-06-01 24:00
		 *	closetime: 25:00  now: 2013-06-02 02:00  -> OK
		 */
		if ( !empty( $this->qs['date'] ) && preg_match( '/^([2-9][0-9]{3})-(0[1-9]{1}|1[0-2]{1})-(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $this->qs['date'] ) ) {
			$this->startdate = $this->qs['date'];
			$this->enddate = date( 'Y-m-d', strtotime( $this->startdate ) + $this->term );
		}
		else if( $this->closetime >= '24:00' && olbTimetable::calcHour($this->closetime, -24*60) >= olbTimetable::calcHour(date('H:i:s', $currenttime), 0)){
			list($y, $m, $d) = explode('-', date('Y-m-d', $currenttime));
			$this->startdate = date('Y-m-d', mktime(0, 0, 0, $m, $d-1, $y));
			$this->enddate = date( 'Y-m-d', strtotime( $this->startdate ) + $this->term );
		}
		else {
			$this->startdate = date( 'Y-m-d', current_time('timestamp') );
			$this->enddate = date( 'Y-m-d', strtotime( $this->startdate ) + $this->term );
		}

	}

	/** 
	 *	プラグインオプション設定の取得: Get plugins options
	 */
	public static function getPluginOptions( $group = null, $key = null ) {

		$options_key = OLBsystem::TEXTDOMAIN;
		$olb_options = get_option( $options_key );
		if ( empty( $olb_options ) ) {
			$olb_options = self::setDefaultOptions();
			update_option( $options_key, $olb_options );
			$olb_options = get_option( $options_key );
		}
		if ( !empty( $group ) ) {
			if ( isset( $olb_options[$group] ) ) {
				if ( !empty( $key ) && isset( $olb_options[$group][$key] ) ) {
					return $olb_options[$group][$key];
				} else {
					return $olb_options[$group];
				}
			}
		} else {
			return $olb_options;
		}
	}

	/** 
	 *	プラグインオプションのデフォルト設定: Set default plugins options
	 */
	public static function setDefaultOptions(){
		$default_options = array(
			'settings' => array(
				'home'                => get_option('home'),
				'starttime'           => '09:00',
				'endtime'             => '18:00',
				'opentime'            => '09:00',
				'closetime'           => '18:00',
				'interval'            => 30,
				'daymax'              => 7,
				'term'                => 604800,
				'reserve_deadline'    => 30,
				'cancel_deadline'     => 30,
				'limit_per_day'       => 2,
				'limit_per_month'     => 0,
				'free'                => 2,
				'indefinite'          => 0,
				'ticket_system'       => 0,
				'ticket_metakey'      => 'olbticket',
				'ticket_expire'       => 60,
				'room_per_page'       => 3,

				'preserve_past'       => 7,
				'profile_customize'   => 1,
				'cron_interval'       => 'daily',
				),

			'specialpages' => array(
				'daily_schedule_page' => 'schedule',
				'reserve_form_page'   => 'reservation',
				'cancel_form_page'    => 'cancel',
				'report_form_page'    => 'report',
				'edit_schedule_page'  => 'editschedule',
				'member_page'         => 'mypage',
				'login_page'          => '',
				'members_info_page'   => 'membersinfo',
				),

			'mail' => array(
				'from_email'          => sprintf('%s <%s>', get_option('blogname'), get_option('admin_email')),
				'signature'           => "----\n".get_option('blogname')."\n".get_option('home')."\n",
				'reservation_subject' => __('Reserved: %RESERVE_DATE% %RESERVE_TIME% %ROOM_NAME%', OLBsystem::TEXTDOMAIN),
				'reservation_subject_to_teacher' => __('Reserved: %RESERVE_DATE% %RESERVE_TIME% by %USER_NAME%(Skype: %USER_SKYPE%)', OLBsystem::TEXTDOMAIN),
				'reservation_message' => __('
To: %USER_NAME% (Skype: %USER_SKYPE%)

Your reservation was received.
[Reservation ID] %RESERVE_ID%
[Teacher] %ROOM_NAME%
[Date/Time] %RESERVE_DATE% %RESERVE_TIME%

(Received: %SEND_TIME%)

If you cancel, please offer from the following URL %CANCEL_DEADLINE%.
%CANCEL_URL%

', OLBsystem::TEXTDOMAIN),
				'cancel_subject'      => __('Cancelled: %RESERVE_DATE% %RESERVE_TIME% %ROOM_NAME%', OLBsystem::TEXTDOMAIN),
				'cancel_subject_to_teacher' => __('Cancelled: %RESERVE_DATE% %RESERVE_TIME% by %USER_NAME%', OLBsystem::TEXTDOMAIN),
				'cancel_message'      => __('
To: %USER_NAME% (Skype: %USER_SKYPE%)

Your cancellation was received.
[Reservation ID] %RESERVE_ID%
[Teacher] %ROOM_NAME%
[Date/Time] %RESERVE_DATE% %RESERVE_TIME%

(Received: %SEND_TIME%)

', OLBsystem::TEXTDOMAIN),
				'cancel_subject_by_teacher' => __('Cancelled by teacher: %RESERVE_DATE% %RESERVE_TIME% %ROOM_NAME%', OLBsystem::TEXTDOMAIN),
				'cancel_subject_by_teacher_to_teacher' => __('Cancelled by teacher: %RESERVE_DATE% %RESERVE_TIME% by %USER_NAME%', OLBsystem::TEXTDOMAIN),
				'cancel_message_by_teacher' => __('
To: %USER_NAME% (Skype: %USER_SKYPE%)

Your reservation was canceled by teacher.
[Reservation ID] %RESERVE_ID%
[Teacher] %ROOM_NAME%
[Date/Time] %RESERVE_DATE% %RESERVE_TIME%
[Message]
%MESSAGE%

(Canceled: %SEND_TIME%)

', OLBsystem::TEXTDOMAIN),
				'send_to_admin_too'   => '',
				),
			);
		return $default_options;
	}

	/** 
	 *	管理画面へのお知らせ: Show Admin-page notices
	 */
	public static function showAdminNotices(){
		if (0){
			 echo <<<EOD
'<div class="updated">
<p>(Admin notices)</p>
</div>'
EOD;
		}
	}

	/**
	 *	Get DOW
	 */
	public static function dow( $weekday ) {
		$dow = array(
			__( 'Sun', OLBsystem::TEXTDOMAIN ),
			__( 'Mon', OLBsystem::TEXTDOMAIN ),
			__( 'Tue', OLBsystem::TEXTDOMAIN ),
			__( 'Wed', OLBsystem::TEXTDOMAIN ),
			__( 'Thu', OLBsystem::TEXTDOMAIN ),
			__( 'Fri', OLBsystem::TEXTDOMAIN ),
			__( 'Sat', OLBsystem::TEXTDOMAIN )
		);
		if ( $weekday >= 0 && $weekday <= 6 ) {
			return $dow[$weekday];
		}
		return false;
	}

	/**
	 *	Get DOW name
	 */
	public static function dow_lower( $weekday ) {
		return strtolower( date( 'l', mktime( 0, 0, 0, 6, 1 + $weekday, 2014 ) ) );
	}

	/**
	 *	Get current user info;
	 */
	public function current_user() {
		global $wp_version, $current_user;

		// Current operator
		if ( version_compare( $wp_version, '4.5.0' ) >= 0 ) {
			$current_user = wp_get_current_user();
		}
		else{
			get_currentuserinfo();
		}
	}
}
?>
