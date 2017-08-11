<?php
/** 
 *	タイムテーブル: Timetable
 */

class olbTimetable extends OLBsystem{

	// 時刻計算: Calc of time 
	public static function calcHour($now, $add){
		/**
		 * $now   = '10:30'
		 * $add   = 40(min)
		 * return = '11:10'
		 *
		 * $now   = '10:30:01'
		 * $add   = 40(min)
		 * return = '11:11'
		 *
		 * $now   = '00:00'
		 * $add   = 24*60(min)
		 * return = '24:00'
		 *
		 * $now   = '26:00'
		 * $add   = -24*60(min)
		 * return = '02:00'
		 */
		$t = explode(':', $now);
		$h = $t[ 0 ];
		$m = $t[ 1 ];
		$s = ( isset( $t[ 2 ] ) ) ? $t[ 2 ] : 0;
		if(intval($s) > 0){
			$m++;
		}
		$m = $m + $add;
		if ($m>=60){
			while($m>=60){
				$h++;
				$m = $m - 60;
			}
		}
		if ($m<0){
			while($m<0){
				$h--;
				$m = $m + 60; 
			}
		}

		return sprintf('%02d:%02d', $h, $m);
	}

	/** 
	 *	タイムテーブルの取得: Get timetable
	 */
	public function get($reserved){
		$timetable = array();
		foreach($reserved as $r){
			// ex. $key='2013-06-11_1040'
			$key = self::getTimetableKey($r['date'], $r['time']);
			$timetable[$key] = $r;
			/** 
			 * array(
			 * 		'2013-06-11_1040' => array(...),
			 * 		'2013-06-11_1120' => array(...),
			 * 		'2013-06-11_1200' => array(...),
			 * );
			 */
		}
		return $timetable;
	}

	/** 
	 *	日時とroom_idからOPEN情報を取得: Get open state (with date and time and room_id)
	 *	(日別スケジュールで使用): Used in daily schedule board
	 */
	public static function opened($room_id, $date) {
		global $wpdb;

		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		$query = "SELECT h.id, t.date, t.time, t.room_id, h.user_id, h.free, h.absent FROM ".$prefix."timetable t "
		        ."LEFT JOIN ".$prefix."history h ON t.date=h.date AND t.time=h.time AND t.room_id=h.room_id "
		        ."WHERE t.room_id IN (%d) "
		        ."AND t.date=%s ";
		$ret = $wpdb->get_results($wpdb->prepare($query, array($room_id, $date)), ARRAY_A);
		return $ret;
	}

	/** 
	 *	開始日と終了日からOPEN情報を取得: Get open state of period (with startdate ~ enddate)
	 *	(週間スケジュールで使用): Used in weekly schedule board
	 */
	public function openedList(){
		global $wpdb;

		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		$query = "SELECT h.id, t.date, t.time, t.room_id, h.user_id, h.free, h.absent FROM ".$prefix."timetable t "
		        ."LEFT JOIN ".$prefix."history h ON t.date=h.date AND t.time=h.time AND t.room_id=h.room_id "
		        ."WHERE t.room_id IN (%d) "
		        ."AND t.date>=%s AND t.date<=%s";
		$ret = $wpdb->get_results($wpdb->prepare($query, array($this->room_id, $this->startdate, $this->enddate)), ARRAY_A);
		return $ret;
	}

	/** 
	 *	予約情報を取得(キー:講師ID,日時): Get rservation data
	 */
	public static function reserved($room_id, $date, $time) {
		global $wpdb;

		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		$query = "SELECT h.id, t.date, t.time, t.room_id, h.user_id, h.free, h.absent FROM ".$prefix."timetable t "
		        ."LEFT JOIN ".$prefix."history h ON t.date=h.date AND t.time=h.time AND t.room_id=h.room_id "
		        ."WHERE t.room_id=%d "
		        ."AND t.date=%s AND t.time=%s ";
		$ret = $wpdb->get_row($wpdb->prepare($query, array($room_id, $date, $time)), ARRAY_A);

		if(empty($ret)){
			return false;
		}
		else {
			return $ret;
		}
	}

	/** 
	 *	１日の予約数: Limit of reservation per day
	 */
	public static function reservedPerDay($user_id, $date){
		global $wpdb;

		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		$query = "SELECT COUNT(*) as count FROM ".$prefix."history "
				."WHERE `user_id`=%d AND `date`=%s";
		$ret = $wpdb->get_row($wpdb->prepare($query, array($user_id, $date)), ARRAY_A);

		if(empty($ret)){
			return false;
		}
		else {
			return $ret;
		}
	}

	/** 
	 *	1ヶ月の予約数: Limit of reservation per month
	 */
	public static function reservedPerMonth($user_id, $date){
		global $wpdb;

		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		$query = "SELECT COUNT(*) as count FROM ".$prefix."history "
				."WHERE `user_id`=%d AND `date` LIKE '%s'";
		$ret = $wpdb->get_row($wpdb->prepare($query, array($user_id, substr( $date, 0, 7 ).'%' )), ARRAY_A);

		if(empty($ret)){
			return false;
		}
		else {
			return $ret;
		}
	}

	/** 
	 *	１日の予約数制限内か: Less than the limit of reservation per day 
	 */
	public static function canReservePerDay($user_id, $date) {
		global $olb;

		$ret = self::reservedPerDay($user_id, $date);
		if( empty( $olb->limit_per_day ) ) {
			return true;
		}
		elseif ( $ret['count']<$olb->limit_per_day ){
			return true;
		}
		return false;
	}

	/** 
	 *	1ヶ月の予約数制限内か: Less than the limit of reservation per month 
	 */
	public static function canReservePerMonth($user_id, $date) {
		global $olb;

		$ret = self::reservedPerMonth($user_id, $date);
		if( empty( $olb->limit_per_month ) ){
			return true;
		}
		elseif( $ret['count'] < $olb->limit_per_month ){
			return true;
		}
		return false;
	}

	/** 
	 *	ダブルブッキングチェック: Check double booking
	 */
	public static function isDoubleBooking($user_id, $date, $time) {
		global $wpdb;

		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		$query = "SELECT COUNT(*) as count FROM ".$prefix."history "
				."WHERE `user_id`=%d AND `date`=%s AND `time`=%s";
		$ret = $wpdb->get_row($wpdb->prepare($query, array($user_id, $date, $time)), ARRAY_A);

		if(empty($ret)){
			return false;
		}
		else {
			return $ret['count'];
		}
	}

	/** 
	 *	会員による予約の可否: Can member do reservation ? 
	 */
	public static function canReservation( $result, $room_id, $user_id, $date, $time ) {
		global $olb;

		$result = array(
			'code' => null,
			'record' => null,
			'user' => null,
			'room' => null,
			);
		if(!preg_match('/^[0-9]+$/', $room_id) ||
			!preg_match('/^[0-9]+$/', $user_id) ||
			!preg_match('/^([2-9][0-9]{3})-(0[1-9]{1}|1[0-2]{1})-(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $date) ||
			//!preg_match('/^([0-1][0-9]{1}|2[0-4]{1}):[0-5][0-9]{1}:[0-5][0-9]{1}$/', $time)) {
			!preg_match('/^[0-9]{2}:[0-5][0-9]{1}:[0-5][0-9]{1}$/', $time)) {
			$result['code'] = 'INVALID_PARAMETER';
			return $result;
		}

		$user = new olbAuth($user_id);
		$room = olbRoom::get($room_id);
		$record = olbTimetable::reserved($room_id, $date, $time);
		/** 
		 *	[reserved]
		 *	$record = array( 
		 *		'id'      => 1595,
		 *		'date'    => '2013-07-09',
		 *		'time'    => '10:00:00',
		 *		'room_id' => 1,
		 *		'user_id' => 3,
		 *		'free'    => 0,
		 *		'absent'  => 0,
		 *	)
		 *
		 *	[not reserved]
		 *	$record = array( 
		 *		'id'      => (null),
		 *		'date'    => '2013-07-09',
		 *		'time'    => '10:00:00',
		 *		'room_id' => 1,
		 *		'user_id' => (null),
		 *		'free'    => (null),
		 *		'absent'  => (null),
		 *	)
		 *
		 */

		$result['record'] = $record;
		$result['user'] = $user;
		$result['room'] = $room;

		if ( empty( $room ) ) {
			$result['code'] = 'NONEXISTENT_TEACHER';
		}
		elseif( empty( $record ) ) {
			$result['code'] = 'NO_RECORD';
		}
		// 空き: not reserved
		elseif ( empty( $record['user_id'] ) ) {
			// 期限切れ & 無料予約数残なし
			if( !$user->isNotExpire( $date ) && !$user->canFreeReservation() ) {
				$result['code'] = 'RESERVE_EXPIRED';
			}
			// 受付時間オーバー
			elseif ( olbTimetable::isTimeover( 'reserve', $date, $time ) ) {
				$result['code'] = 'RESERVE_TIMEOVER';
			}
			// 1日の予約数上限
			elseif ( !olbTimetable::canReservePerDay( $user_id, $date ) ) {
				$result['code'] = 'RESERVE_LIMIT_PER_DAY';
			}
			// 1ヶ月の予約数上限
			elseif ( !olbTimetable::canReservePerMonth( $user_id, $date ) ) {
				$result['code'] = 'RESERVE_LIMIT_PER_MONTH';
			}
			// 同日時を別予約済み
			elseif( olbTimetable::isDoubleBooking( $user_id, $date, $time ) ) {
				$result['code'] = 'DOUBLE_BOOKING';
			}
			else {
				$result['code'] = 'NOT_RESERVED';
			}
		}
		// 自身予約済: reserved by self
		elseif ( $record['user_id'] == $user_id ) {
			if ( olbTimetable::isTimeover( 'cancel', $date, $time ) ) {
				$result['code'] = 'CANCEL_TIMEOVER';
			}
			else {
				$result['code'] = 'ALREADY_RESERVED';
			}
		}
		// 他者予約済: reserved by others
		else {
			$result['code'] = 'OTHERS_RECORD';
		}

		return $result;
	}

	/** 
	 *	講師によるキャンセルの欠陥: Inspaction of defect in cancel 
	 */
	public static function canCancellation($room_id, $date, $time){
		$result = array(
			'code' => null,
			'record' => null,
			'user' => null,
			'room' => null,
			);

		if(!preg_match('/^[0-9]+$/', $room_id) ||
			!preg_match('/^([2-9][0-9]{3})-(0[1-9]{1}|1[0-2]{1})-(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $date) ||
			//!preg_match('/^([0-1][0-9]{1}|2[0-4]{1}):[0-5][0-9]{1}:[0-5][0-9]{1}$/', $time)) {
			!preg_match('/^[0-9]{2}:[0-5][0-9]{1}:[0-5][0-9]{1}$/', $time)) {
			$result['code'] = 'INVALID_PARAMETER';
			return $result;
		}

		$room = olbRoom::get($room_id);
		$record = olbTimetable::reserved($room_id, $date, $time);
		/** 
		 *	[reserved]
		 *	$record = array( 
		 *		'id'      => 1595,
		 *		'date'    => '2013-07-09',
		 *		'time'    => '10:00:00',
		 *		'room_id' => 1,
		 *		'user_id' => 3,
		 *		'free'    => 0,
		 *		'absent'  => 0,
		 *	)
		 *
		 *	[not reserved]
		 *	$record = array( 
		 *		'id'      => (null),
		 *		'date'    => '2013-07-09',
		 *		'time'    => '10:00:00',
		 *		'room_id' => 1,
		 *		'user_id' => (null),
		 *		'free'    => (null),
		 *		'absent'  => (null),
		 *	)
		 *
		 */

		$result['record'] = $record;
		$result['room'] = $room;

		if(empty($record)) {
			$result['code'] = 'NO_RECORD';
		}
		// 空き(キャンセル不可): not reserved (cannot cancel)
		else if(empty($record['user_id'])) {
			$result['code'] = 'NOT_RESERVED';
		}
		else if(olbTimetable::isTimeover('cancel', $date, $time)){
			$result['code'] = 'CANCEL_TIMEOVER';
			$user = new olbAuth($record['user_id']);
			$result['user'] = $user;
		}
		else {
			$result['code'] = 'ALREADY_RESERVED';
			$user = new olbAuth($record['user_id']);
			$result['user'] = $user;
		}
		return $result;
	}

	/** 
	 *	講師によるレポートの欠陥: Inspaction of defect in report 
	 */
	public static function canReport($room_id, $date, $time){
		global $wpdb;
		$result = olbTimetable::canCancellation($room_id, $date, $time);
		if($result['code']=='CANCEL_TIMEOVER'){
			$result['code'] = 'ALREADY_RESERVED';
		}
		// If old schedules of teacher is already deleted
		if ( $result['code']=='NO_RECORD') {
			$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
			$query = "SELECT * FROM ".$prefix."history WHERE room_id=%d AND date=%s AND time=%s ";
			$record = $wpdb->get_row($wpdb->prepare($query, array($room_id, $date, $time)), ARRAY_A);
			if ( ! empty( $record ) ){
				$result['record'] = $record;
				$user = new olbAuth($record['user_id']);
				$result['user'] = $user;
				$result['code'] = 'ALREADY_RESERVED';
			}
		}
		return $result;
	}

	/** 
	 *	予約/キャンセルフォーム: Reserve(or cancel) form by member
	 */
	public static function htmlReserveForm($out = false){
		global $olb;

		$error = '';
		$html = '';
		ob_start();

		if(empty($olb->qs['t']) && empty($olb->qs['room_id']) ) {
			$error = 'PARAMETER_INSUFFICIENT';
		}
		else {
			// エラー表示: Show error
			if(!empty($olb->qs['error'])){
				$error = $olb->qs['error'];
			}
			else if(empty($olb->qs['t']) || empty($olb->qs['room_id'])){
				$error = 'PARAMETER_INSUFFICIENT';
			}
		}

		if(!$error) {
			$date = $olb->getQueryDate();
			$time = $olb->getQueryTime();
			$formaction = get_permalink(get_page_by_path($olb->reserve_form_page)->ID);

			$action = null;

			$result = array();
			$result = apply_filters( 'olb_can_reservation', $result, $olb->room_id, $olb->operator->data['id'], $date, $time );
			/** 
			 *	$result = array( 
			 *		'code'   => 'RESERVE_OK',
			 *		'record' => array(
			 *			'id'      => 56,
			 *			'room_id' => 5,
			 *			'user_id' => 6,
			 *			'date'    => '2013-07-11',
			 *			'time'    => '10:00:00'
			 *			'free'    => 0
			 *			'absent'  => 0
			 *		),
			 *		'user'   => olbAuth Object(
			 *		),
			 *		'room'   => array(
			 *		),
			 *	)
			 */
			extract($result);	// $code, $record, $user, $room

			if($code=='NOT_RESERVED' || $code=='ALREADY_RESERVED') {
				if($code=='NOT_RESERVED'){
					$already_reserved = '';
					if($olb->operator->canFreeReservation()) {
						$free = sprintf('<span class="free">%s</span>', __('FREE', OLBsystem::TEXTDOMAIN));
					}
					else {
						$free = '';
					}
					$action = 'reserve';
					$reserve_id = '---';
					$submit = __('reserve', OLBsystem::TEXTDOMAIN);
					$btnclass = 'reserve_btn';
				}
				else {
					$already_reserved = sprintf('<span class="already_reserved">%s</span>', __('Reserved', OLBsystem::TEXTDOMAIN));
					if($record['free']){
						$free = sprintf('<span class="free">%s</span>', __('FREE', OLBsystem::TEXTDOMAIN));
					}
					else {
						$free = '';
					}
					$action = 'cancel';
					$reserve_id = $record['id'];
					$submit = __('cancel', OLBsystem::TEXTDOMAIN);
					$btnclass = 'cancel_btn';
				}
				$message_key = '';
				if(!empty($olb->qs['success'])){
					switch($olb->qs['success']){
					case 'reserve':
						$message_key = 'SUCCESS_RESERVE';
						break;

					case 'cancel':
						$message_key = 'SUCCESS_CANCEL';
						break;
					}
				}

				$format = <<<EOD
<form id="reservation" class="reservation" method="post" action="%FORMACTION%">
<dl>
<dt>%LABEL_ID%:</dt>
<dd>%ID% {$already_reserved} {$free}
<input type="hidden" id="reserve_id" name="reserve_id" value="%ID%" />
<dt>%LABEL_ROOM%:</dt>
<dd>%ROOM_NAME%
<input type="hidden" id="room_id" name="room_id" value="%ROOM_ID%" />
</dd>
<dt>%LABEL_USER%:</dt>
<dd>%USER_NAME%(Skype: %USER_SKYPE%)
<input type="hidden" id="user_id" name="user_id" value="%USER_ID%" />
</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%
<input type="hidden" id="reservedate" name="reservedate" value="%DATE%" />
<input type="hidden" id="reservetime" name="reservetime" value="%TIME%" />
</dd>
</dl>
%NONCE%
<input type="hidden" id="reserveaction" name="reserveaction" value="%ACTION%" />
<input type="submit" id="reservesubmit" name="reservesubmit" value="%SUBMIT%" class="{$btnclass}" />
EOD;

				if($message_key) {
					$format .= '<div class="alert alert-success">%MESSAGE%</div>';
				}
				$format .= '</form>';

				$search = array(
						'%FORMACTION%',
						'%LABEL_ID%',
						'%ID%',
						'%LABEL_ROOM%',
						'%ROOM_NAME%',
						'%ROOM_ID%',
						'%LABEL_USER%',
						'%USER_NAME%',
						'%USER_ID%',
						'%USER_SKYPE%',
						'%LABEL_DATETIME%',
						'%DATETIME%',
						'%DATE%',
						'%TIME%',
						'%NONCE%',
						'%ACTION%',
						'%SUBMIT%',
						'%MESSAGE%',
					);
				$replace = array(
						$formaction,
						__('Reserve ID', OLBsystem::TEXTDOMAIN),
						$reserve_id,
						__('Teacher', OLBsystem::TEXTDOMAIN),
						sprintf('<a href="%s">%s</a>', $room['url'], $room['name']),
						$olb->room_id,
						__('User', OLBsystem::TEXTDOMAIN),
						$olb->operator->data['name'],
						$olb->operator->data['id'],
						$olb->operator->data['skype'],
						__('Date/Time', OLBsystem::TEXTDOMAIN),
						sprintf( '%s %s', $date, substr( $time, 0, 5 ) ),
						$date,
						$time,
						wp_nonce_field(OLBsystem::TEXTDOMAIN, 'onetimetoken', true, false),
						$action,
						$submit,
						olbFunction::showMessage($message_key),
					);
				echo str_replace($search, $replace, $format);
			}
			// エラーあり: from canReservation()
			else {
				$error = $code;
			}
		}

		if($error){
			$information = '';
			if(in_array($error, array(
					'CANCEL_TIMEOVER',
				))) {
				$already_reserved = sprintf('<span class="already_reserved">%s</span>', __('Reserved', OLBsystem::TEXTDOMAIN));
				if($record['free']) {
					$free = sprintf('<span class="free">%s</span>', __('FREE', OLBsystem::TEXTDOMAIN));
				}
				else {
					$free = '';
				}
				$format = <<<EOD
<div id="reservation" class="reservation timeover">
<dl>
<dt>%LABEL_ID%:</dt>
<dd>%ID% {$already_reserved} {$free}
<input type="hidden" id="reserve_id" name="reserve_id" value="%ID%" />
<dt>%LABEL_ROOM%:</dt>
<dd>%ROOM_NAME%
<input type="hidden" id="room_id" name="room_id" value="%ROOM_ID%" />
</dd>
<dt>%LABEL_USER%:</dt>
<dd>%USER_NAME%(Skype: %USER_SKYPE%)
<input type="hidden" id="user_id" name="user_id" value="%USER_ID%" />
</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%
<input type="hidden" id="reservedate" name="reservedate" value="%DATE%" />
<input type="hidden" id="reservetime" name="reservetime" value="%TIME%" />
</dd>
</dl>
<div class="alert">%MESSAGE%</div>
</div>
EOD;
				$search = array(
						'%LABEL_ID%',
						'%ID%',
						'%LABEL_ROOM%',
						'%ROOM_NAME%',
						'%ROOM_ID%',
						'%LABEL_USER%',
						'%USER_NAME%',
						'%USER_ID%',
						'%USER_SKYPE%',
						'%LABEL_DATETIME%',
						'%DATETIME%',
						'%DATE%',
						'%TIME%',
						'%MESSAGE%',
					);
				$replace = array(
						__('Reserve ID', OLBsystem::TEXTDOMAIN),
						$record['id'],
						__('Teacher', OLBsystem::TEXTDOMAIN),
						sprintf('<a href="%s">%s</a>', $room['url'], $room['name']),
						$olb->room_id,
						__('User', OLBsystem::TEXTDOMAIN),
						$olb->operator->data['name'],
						$olb->operator->data['id'],
						$olb->operator->data['skype'],
						__('Date/Time', OLBsystem::TEXTDOMAIN),
						sprintf( '%s %s', $date, substr( $time, 0, 5 ) ),
						$date,
						$time,
						apply_filters( 'olb_error', $information, $error ),
					);
			}
			else if(in_array($error, array(
					'PARAMETER_INSUFFICIENT',
					'INVALID_PARAMETER',
					'NO_RECORD',
					'NONEXISTENT_TEACHER',

				))) {
				$format = <<<EOD
<div id="reservation" class="reservation">
<div class="alert alert-error">%MESSAGE%</div>
</div>
EOD;
				$search = array(
					'%MESSAGE%',
					);
				$replace = array(
					apply_filters( 'olb_error', $information, $error ),
					);
			}
			else {
				// 'RESERVE_EXPIRED';
				// 'RESERVE_TIMEOVER';
				// 'RESERVE_LIMIT_PER_DAY';
				// 'RESERVE_LIMIT_PER_MONTH';
				// 'DOUBLE_BOOKING';
				// 'OTHERS_RECORD';

				$format = <<<EOD
<div id="reservation" class="reservation">
<dl>
<dt>%LABEL_ROOM%:</dt>
<dd>%ROOM_NAME%</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%</dd>
</dl>
<div class="alert">%MESSAGE%</div>
</div>
EOD;
				$search = array(
						'%LABEL_ROOM%',
						'%ROOM_NAME%',
						'%LABEL_DATETIME%',
						'%DATETIME%',
						'%DATE%',
						'%TIME%',
						'%MESSAGE%',
					);
				$replace = array(
						__('Teacher', OLBsystem::TEXTDOMAIN),
						sprintf('<a href="%s">%s</a>', $room['url'], $room['name']),
						__('Date/Time', OLBsystem::TEXTDOMAIN),
						sprintf( '%s %s', $date, substr( $time, 0, 5 ) ),
						$date,
						$time,
						apply_filters( 'olb_error', $information, $error ),
					);
			}
			echo str_replace($search, $replace, $format);
		}
		$html = ob_get_contents();
		$html = apply_filters( 'olb_reservation_form', $html, $result, $error );
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	講師用キャンセルフォーム: Cancel form for room manager
	 */
	public static function htmlCancelForm($out = false){
		global $olb;

		$error = '';
		$html = '';
		ob_start();

		if(empty($olb->qs)) {
			$error = 'PARAMETER_INSUFFICIENT';
		}
		else {
			// エラー表示: Show error
			if(!empty($olb->qs['error'])){
				$error = $olb->qs['error'];
			}
			else if(empty($olb->qs['t'])){
				$error = 'PARAMETER_INSUFFICIENT';
			}
			else if($olb->operator->isAdmin()) {
				if(empty($olb->qs['room_id'])){
					$error = 'PARAMETER_INSUFFICIENT';
				}
			}
		}

		if(!$error) {
			$date = $olb->getQueryDate();
			$time = $olb->getQueryTime();
			$formaction = get_permalink(get_page_by_path($olb->cancel_form_page)->ID);

			$action = null;

			$result = olbTimetable::canCancellation($olb->room_id, $date, $time); 
			/** 
			 *	$result = array( 
			 *		'code'   => 'RESERVE_OK',
			 *		'record' => array(
			 *			'id'      => 56,
			 *			'room_id' => 5,
			 *			'user_id' => 6,
			 *			'date'    => '2013-07-11',
			 *			'time'    => '10:00:00'
			 *			'free'    => 0
			 *			'absent'  => 0
			 *		),
			 *		'user'   => olbAuth Object(
			 *		),
			 *		'room'   => array(
			 *		),
			 *	)
			 */
			extract($result);	// $code, $record, $user, $room

			if ( in_array( $code, array( 'ALREADY_RESERVED', 'CANCEL_TIMEOVER' ) ) ) {
				$user_name = $user->data['name'];
				$members_info_url = get_permalink(get_page_by_path($olb->edit_schedule_page.'/'.$olb->members_info_page)->ID);
				if($members_info_url) {
					$members_info_url .= (strstr($members_info_url, '?')) ? '&' : '?';
					$members_info_url .= 'user_id='.$user->data['id'];
					$user_name = sprintf('<a href="%s">%s</a>', $members_info_url, $user->data['name']);
				}
			}

			if($code=='ALREADY_RESERVED') {
				$action = 'cancel';
				$submit = __('cancel', OLBsystem::TEXTDOMAIN);
				$returnurl = ( !strpos( $_SERVER['HTTP_REFERER'], 'redirect_to' ) ) ? $_SERVER['HTTP_REFERER'] : get_permalink( get_page_by_path( $olb->edit_schedule_page )->ID );

				$format = <<<EOD
<form id="reservation" class="reservation" method="post" action="%FORMACTION%">
<p>%ATTENTION%</p>
<dl>
<dt>%LABEL_ID%:</dt>
<dd>%ID%
<input type="hidden" id="reserve_id" name="reserve_id" value="%ID%" />
<dt>%LABEL_ROOM%:</dt>
<dd>%ROOM_NAME%
<input type="hidden" id="room_id" name="room_id" value="%ROOM_ID%" />
</dd>
<dt>%LABEL_USER%:</dt>
<dd>%USER_NAME%(Skype: %USER_SKYPE%)
<input type="hidden" id="user_id" name="user_id" value="%USER_ID%" />
</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%
<input type="hidden" id="reservedate" name="reservedate" value="%DATE%" />
<input type="hidden" id="reservetime" name="reservetime" value="%TIME%" />
</dd>
<dt>%LABEL_MESSAGE%:</dt>
<dd>
<textarea id="message" name="message" rows="5"></textarea>
</dd>
</dl>
%NONCE%
<input type="hidden" id="reserveaction" name="reserveaction" value="%ACTION%" />
<input type="submit" id="reservesubmit" name="reservesubmit" value="%SUBMIT%" />
<input type="hidden" id="returnurl" name="returnurl" value="%RETURN_URL%" />
</form>
EOD;
				$search = array(
						'%FORMACTION%',
						'%ATTENTION%',
						'%LABEL_ID%',
						'%ID%',
						'%LABEL_ROOM%',
						'%ROOM_NAME%',
						'%ROOM_ID%',
						'%LABEL_USER%',
						'%USER_NAME%',
						'%USER_ID%',
						'%USER_SKYPE%',
						'%LABEL_DATETIME%',
						'%DATETIME%',
						'%DATE%',
						'%TIME%',
						'%LABEL_MESSAGE%',
						'%NONCE%',
						'%ACTION%',
						'%SUBMIT%',
						'%RETURN_URL%'
					);
				$replace = array(
						$formaction,
						__('*) When timetable is canceled by a teacher, it is closed at the same time', OLBsystem::TEXTDOMAIN),
						__('Reserve ID', OLBsystem::TEXTDOMAIN),
						$record['id'],
						__('Teacher', OLBsystem::TEXTDOMAIN),
						$room['name'],
						$olb->room_id,
						__('User', OLBsystem::TEXTDOMAIN),
						$user_name,
						$user->data['id'],
						$user->data['skype'],
						__('Date/Time', OLBsystem::TEXTDOMAIN),
						sprintf( '%s %s', $date, substr( $time, 0, 5 ) ),
						$date,
						$time,
						__('Message', OLBsystem::TEXTDOMAIN),
						wp_nonce_field(OLBsystem::TEXTDOMAIN, 'onetimetoken', true, false),
						$action,
						$submit,
						$returnurl,
					);
				echo str_replace($search, $replace, $format);
			}
			// エラーあり: from canCancellation()
			else {
				$error = $code;
			}
		}

		if($error){
			$information = '';
			if(in_array($error, array(
					'PARAMETER_INSUFFICIENT',
					'INVALID_PARAMETER',
					'NO_RECORD',

				))){
				$format = <<<EOD
<div id="reservation" class="reservation">
<div class="alert alert-error">%MESSAGE%</div>
</div>
EOD;
			}
			if(in_array($error, array(
					'CANCEL_TIMEOVER'
				))){
				$format = <<<EOD
<div id="reservation" class="reservation timeover">
<dl>
<dt>%LABEL_ID%:</dt>
<dd>%ID% {$already_reserved} {$free}
<input type="hidden" id="reserve_id" name="reserve_id" value="%ID%" />
<dt>%LABEL_ROOM%:</dt>
<dd>%ROOM_NAME%
<input type="hidden" id="room_id" name="room_id" value="%ROOM_ID%" />
</dd>
<dt>%LABEL_USER%:</dt>
<dd>%USER_NAME%(Skype: %USER_SKYPE%)
<input type="hidden" id="user_id" name="user_id" value="%USER_ID%" />
</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%
<input type="hidden" id="reservedate" name="reservedate" value="%DATE%" />
<input type="hidden" id="reservetime" name="reservetime" value="%TIME%" />
</dd>
</dl>
<div class="alert">%MESSAGE%</div>
</div>
EOD;
			}
			else {
				// 'NOT_RESERVED';

				$format = <<<EOD
<div id="reservation" class="reservation">
<dl>
<dt>%LABEL_USER%:</dt>
<dd>%USER_NAME%(Skype: %USER_SKYPE%)</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%</dd>
</dl>
<div class="alert">%MESSAGE%</div>
</div>
EOD;
			}
			$search = array(
					'%LABEL_ID%',
					'%ID%',
					'%LABEL_ROOM%',
					'%ROOM_NAME%',
					'%ROOM_ID%',
					'%LABEL_USER%',
					'%USER_NAME%',
					'%USER_SKYPE%',
					'%LABEL_DATETIME%',
					'%DATETIME%',
					'%DATE%',
					'%TIME%',
					'%MESSAGE%',
					'%SUBMIT%'
				);
			$replace = array(
					__('Reserve ID', OLBsystem::TEXTDOMAIN),
					$record['id'],
					__('Teacher', OLBsystem::TEXTDOMAIN),
					$room['name'],
					$olb->room_id,
					__('User', OLBsystem::TEXTDOMAIN),
					$user_name,
					$user->data['skype'],
					__('Date/Time', OLBsystem::TEXTDOMAIN),
					sprintf( '%s %s', $date, substr( $time, 0, 5 ) ),
					$date,
					$time,
					apply_filters( 'olb_error', $information, $error ),
					$submit
				);
			echo str_replace($search, $replace, $format);
		}
		$html = ob_get_contents();
		$html = apply_filters( 'olb_cancellation_form', $html, $result, $error );
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	講師用評価フォーム: Repot form for room manager
	 */
	public static function htmlReportForm($out = false){
		global $olb;

		$error = '';
		$html = '';
		ob_start();

		if(empty($olb->qs)) {
			$error = 'PARAMETER_INSUFFICIENT';
		}
		else {
			// エラー表示: Show error
			if(!empty($olb->qs['error'])){
				$error = $olb->qs['error'];
			}
			else if(empty($olb->qs['t'])){
				$error = 'PARAMETER_INSUFFICIENT';
			}
			else if($olb->operator->isAdmin()) {
				if(empty($olb->qs['room_id'])){
					$error = 'PARAMETER_INSUFFICIENT';
				}
			}
		}

		if(!$error) {
			$date = $olb->getQueryDate();
			$time = $olb->getQueryTime();
			$formaction = get_permalink(get_page_by_path($olb->report_form_page)->ID);

			$action = null;

			$result = olbTimetable::canReport($olb->room_id, $date, $time); 
			/**
			 *	$result = array( 
			 *		'code'=> 'RESERVE_OK',
			 *		'record' => array(
			 *			'id' => 56,
			 *			'room_id' => 5,
			 *			'user_id' => 6,
			 *			'date' => '2013-07-11',
			 *			'time' => '10:00:00'
			 *		),
			 *		'user' => olbAuth Object(
			 *		),
			 *		'room' => array(
			 *		),
			 *	)
			 */
			extract($result);	// $code, $record, $user, $room

			if($code=='ALREADY_RESERVED') {
				$action = 'report';
				$submit = __('update', OLBsystem::TEXTDOMAIN);
				$absent_checked = ($record['absent']) ? 'checked="checked"' : '';

				$user_name = $user->data['name'];
				$members_info_url = get_permalink(get_page_by_path($olb->edit_schedule_page.'/'.$olb->members_info_page)->ID);
				if($members_info_url) {
					$members_info_url .= (strstr($members_info_url, '?')) ? '&' : '?';
					$members_info_url .= 'user_id='.$user->data['id'];
					$user_name = sprintf('<a href="%s">%s</a>', $members_info_url, $user->data['name']);
				}

				$format = <<<EOD
<form id="reservation" class="reservation" method="post" action="%FORMACTION%">
%ATTENTION%
<dl>
<dt>%LABEL_ID%:</dt>
<dd>%ID%
<input type="hidden" id="reserve_id" name="reserve_id" value="%ID%" />
<dt>%LABEL_ROOM%:</dt>
<dd>%ROOM_NAME%
<input type="hidden" id="room_id" name="room_id" value="%ROOM_ID%" />
</dd>
<dt>%LABEL_USER%:</dt>
<dd>%USER_NAME%(Skype: %USER_SKYPE%)
<input type="hidden" id="user_id" name="user_id" value="%USER_ID%" />
</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%
<input type="hidden" id="reservedate" name="reservedate" value="%DATE%" />
<input type="hidden" id="reservetime" name="reservetime" value="%TIME%" />
</dd>
<dt>%LABEL_ABSENT%:</dt>
<dd>
<label for="absent"><input type="checkbox" id="absent" name="absent" value="1" {$absent_checked}/>%LABEL_ABSENT%</label>
</dd>
</dl>
%NONCE%
<input type="hidden" id="reserveaction" name="reserveaction" value="%ACTION%" />
<input type="submit" id="reservesubmit" name="reservesubmit" value="%SUBMIT%" />
<input type="hidden" id="returnurl" name="returnurl" value="%RETURN_URL%" />
</form>
EOD;
				$search = array(
						'%FORMACTION%',
						'%ATTENTION%',
						'%LABEL_ID%',
						'%ID%',
						'%LABEL_ROOM%',
						'%ROOM_NAME%',
						'%ROOM_ID%',
						'%LABEL_USER%',
						'%USER_NAME%',
						'%USER_ID%',
						'%USER_SKYPE%',
						'%LABEL_DATETIME%',
						'%DATETIME%',
						'%DATE%',
						'%TIME%',
						'%LABEL_ABSENT%',
						'%NONCE%',
						'%ACTION%',
						'%SUBMIT%',
						'%RETURN_URL%'
					);
				$replace = array(
						$formaction,
						sprintf( '<p>%s</p>', '' ),
						__('Reserve ID', OLBsystem::TEXTDOMAIN),
						$record['id'],
						__('Teacher', OLBsystem::TEXTDOMAIN),
						$room['name'],
						$olb->room_id,
						__('User', OLBsystem::TEXTDOMAIN),
						$user_name,
						$user->data['id'],
						$user->data['skype'],
						__('Date/Time', OLBsystem::TEXTDOMAIN),
						sprintf( '%s %s', $date, substr( $time, 0, 5 ) ),
						$date,
						$time,
						__('Absent', OLBsystem::TEXTDOMAIN),
						wp_nonce_field(OLBsystem::TEXTDOMAIN, 'onetimetoken', true, false),
						$action,
						$submit,
						$_SERVER['HTTP_REFERER'],
					);
				echo str_replace($search, $replace, $format);
			}
			// エラーあり: from canCancellation()
			else {
				$error = $code;
			}
		}

		if($error){
			$information = '';
			if(in_array($error, array(
					'PARAMETER_INSUFFICIENT',
					'INVALID_PARAMETER',
					'NO_RECORD',

				))){
				$format = <<<EOD
<div id="reservation" class="reservation">
<div class="alert alert-error">%MESSAGE%</div>
</div>
EOD;
			}
			else {
				$format = <<<EOD
<div id="reservation" class="reservation">
<dl>
<dt>%LABEL_ROOM%:</dt>
<dd>%ROOM_NAME%</dd>
<dt>%LABEL_DATETIME%:</dt>
<dd>%DATETIME%</dd>
</dl>
<div class="alert alert-error">%MESSAGE%</div>
<input type="button" id="reservesubmit" name="reservesubmit" value="%SUBMIT%" />
</div>
EOD;
			}
			$search = array(
					'%LABEL_ROOM%',
					'%ROOM_NAME%',
					'%LABEL_DATETIME%',
					'%DATETIME%',
					'%DATE%',
					'%TIME%',
					'%MESSAGE%',
					'%SUBMIT%'
				);
			$replace = array(
					__('Teacher', OLBsystem::TEXTDOMAIN),
					$room['name'],
					__('Date/Time', OLBsystem::TEXTDOMAIN),
					sprintf( '%s %s', $date, substr( $time, 0, 5 ) ),
					$date,
					$time,
					apply_filters( 'olb_error', $information, $error ),
					$submit
				);
			echo str_replace($search, $replace, $format);
		}
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	予約通知: Send mail of reserved
	 */
	public static function sendReserveMail($to, $subject, $body, $header = array()){
		$ret = wp_mail($to, $subject, $body, $header);
		return $ret;
	}

	/** 
	 *	予約受付時間終了の検査: Timeover inspection
	 */
	public static function isTimeover($type, $date, $time){
		global $olb;

		$time = substr($time, 0, 5);
		$now = date('Y-m-d_Hi', current_time('timestamp'));
		if($type == 'reserve'){
			$limittime = self::calcHour($time, $olb->reserve_deadline*(-1));
		}
		if($type == 'cancel'){
			$limittime = self::calcHour($time, $olb->cancel_deadline*(-1));
		}
		list( $h, $i ) = explode( ':', $limittime );
		list( $y, $m, $d ) = explode( '-', $date );
		$limit = date('Y-m-d_Hi', mktime( $h, $i, 0, $m, $d, $y ) );
		if($now >= $limit){
			return true;
		}
		else {
			return false;
		}
	}

	/** 
	 *	タイムテーブルのキー文字列作成: Get key of timetable
	 */
	public static function getTimetableKey($date, $time){
		// ex. key = '2013-06-11_1040'
		return sprintf('%s_%s',$date, str_replace(':', '', substr($time, 0, 5)));
	}

	/** 
	 *	タイムテーブルのキー文字列分解: Parse key of timetable
	 */
	public static function parseTimetableKey($key){
		// ex. '2013-06-11_1040' > '2013-06-11' + '10:40:00'
		$date = substr($key, 0, 10);
		$time = substr($key, 11, 2).':'.substr($key, -2).':00';
		return array($date, $time);
	}

	/** 
	 *	URLクエリから日時を取得: Get time from query-string
	 */
	public function getQueryTime($action = 'time') {
		$str = '';
		if(!empty($this->qs['t'])){
			list($date, $time) = self::parseTimetableKey($this->qs['t']);
			if($action=='time'){
				$str = $time;
			}
			if($action=='date'){
				$str = $date;
			}
		}
		return $str;
	}

	/** 
	 *	URLクエリから日付を取得: Get date from query-string
	 */
	public function getQueryDate($action = 'date') {
		return $this->getQueryTime($action);
	}


	/** 
	 * 予約フォームページへのリンクURL: Link URL to reservation form
	 */
	public function htmlReserveLink($room_id, $time, $linktext, $out = false){
		global $olb;

		ob_start();
		$url = get_permalink(get_page_by_path($olb->reserve_form_page)->ID);
		if(strstr($url, '?')) {
			$format = '<a href="%1$s&t=%2$s&room_id=%3$s">%4$s</a>'."\n";
		}
		else {
			$format = '<a href="%1$s?t=%2$s&room_id=%3$s">%4$s</a>'."\n";
		}
		printf($format,
				get_permalink(get_page_by_path($olb->reserve_form_page)->ID),
				$time,
				$room_id,
				$linktext
			);
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 * 講師用キャンセルフォームページへのリンクURL: Link URL to cancel form (for Room manager)
	 */
	public function htmlCancelLink($time, $linktext, $out = false){
		ob_start();
		$url = get_permalink(get_page_by_path($this->cancel_form_page)->ID);
		// admin
		if($this->operator->isAdmin()) {
			if(strstr($url, '?')) {
				$format = '<a href="%s&t=%s&room_id=%s">%s</a>'."\n";
			}
			else {
				$format = '<a href="%s?t=%s&room_id=%s">%s</a>'."\n";
			}
			printf($format,
					get_permalink(get_page_by_path($this->cancel_form_page)->ID),
					$time,
					$this->room_id,
					$linktext
				);
		}
		// room manager
		else {
			if(strstr($url, '?')) {
				$format = '<a href="%s&t=%s">%s</a>'."\n";
			}
			else {
				$format = '<a href="%s?t=%s">%s</a>'."\n";
			}
			printf($format,
					get_permalink(get_page_by_path($this->cancel_form_page)->ID),
					$time,
					$linktext
				);
		}
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 * 講師用評価フォームページへのリンクURL: Link URL to report form (for Room manager)
	 */
	public function htmlReportLink($time, $linktext, $out = false){
		ob_start();
		$url = get_permalink(get_page_by_path($this->report_form_page)->ID);
		// admin
		if($this->operator->isAdmin()) {
			if(strstr($url, '?')) {
				$format = '<a href="%s&t=%s&room_id=%s">%s</a>'."\n";
			}
			else {
				$format = '<a href="%s?t=%s&room_id=%s">%s</a>'."\n";
			}
			printf($format,
					$url,
					$time,
					$this->room_id,
					$linktext
				);
		}
		// room manager
		else {
			if(strstr($url, '?')) {
				$format = '<a href="%s&t=%s">%s</a>'."\n";
			}
			else {
				$format = '<a href="%s?t=%s">%s</a>'."\n";
			}
			printf($format,
					$url,
					$time,
					$linktext
				);
		}
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	受講履歴の表示: Output reservation history
	 */
	public function htmlReservationHistory($out = false){
		$history = self::getHistory();

		ob_start();

		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}


	/** 
	 *	週間スケジュールの表示: Output weekly schedule board
	 */
	public function htmlWeeklySchedule($out = false){
		$header = self::htmlWeeklyHeader();
		$body =	self::htmlWeeklyBody();

		ob_start();
		echo '<table id="weekly_schedule" class="weekly_schedule">'."\n"
			.'<thead>'."\n"
			.$header
			.'</thead>'."\n";

		echo '<tbody>'."\n"
			.$body
			.'</tbody>'."\n"
			.'</table>'."\n";
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 * 週間スケジュールの日付行の出力: Output the line of date in weekly schedule board
	 */
	public function htmlWeeklyHeader($out = false){
		ob_start();
		printf('<tr class="head">'."\n".'<th>%s</th>'."\n", __('Date', OLBsystem::TEXTDOMAIN));

		for($i=0; $i<$this->daymax; $i++){
			$mt = strtotime($this->startdate)+(60*60*24*$i);		// sec
			$day = date('m/d', $mt);
			$w = date( 'w', $mt );
			$wday = sprintf( '(%s)', OLBsystem::dow( $w ) );	// ex.'(Sun)'
			$wclass = strtolower(date('l', $mt));		// ex.'sunday'

			printf('<th class="%1$s">%2$s<br>%3$s</th>'."\n", $wclass, $day, $wday);
		}
		echo "</tr>\n";
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	週間スケジュールの時間割の出力: Output the timetable in weekly schedule board
	 */
	public function htmlWeeklyBody($out = false) {
		$reserved = olbTimetable::openedList();
		$timetable = olbTimetable::get($reserved);

		$t = $this->starttime;
		$th = substr($this->starttime, 0, 2);
		$tm = substr($this->endtime, -2);

		$user = new olbAuth();
		$this->user_id = $user->data['id'];
		ob_start();

		while($t < $this->endtime){
			$trclass = array( 'time'.str_replace( ':', '', $t ) );

			if(!empty($this->opentime) && $t < $this->opentime){
				$trclass[] = 'invalid';
			}
			else if(!empty($this->closetime) && $t > $this->closetime){
				$trclass[] = 'invalid';
			}
			else {
				$trclass[] = 'valid';
			}
			printf('<tr class="%1$s">'."\n".'<th class="times">%2$s</th>'."\n", implode(' ', $trclass), $t);

			for($i=0; $i<$this->daymax; $i++) {
				$class = array();
				$html = __( '-', OLBsystem::TEXTDOMAIN );
				$class[] = 'status';

				$mt = strtotime($this->startdate)+(60*60*24*$i);		// sec
				$class[] = strtolower(date('l', $mt));					// ex.'sunday'
				$key = self::getTimetableKey(date('Y-m-d', $mt), $t);	// ex.'2013-06-06_1030

				// 受付時刻が過ぎている場合: Time up (reserve)
				$date = date('Y-m-d', $mt);
				if(!in_array('invalid', $trclass) && self::isTimeover('reserve', $date, $t)){
					$class[] = 'past';
				}
				// 講師指定あり
				if (!empty($this->room_id)) {
					// 指定された講師による開講があるか: 
					if (!empty($timetable[$key]['room_id']) && $timetable[$key]['room_id']==$this->room_id) {
						// 受付終了: Time up
						if(in_array('past', $class) && !in_array('invalid', $class)){
							$class[] = 'closed';
							$html = __( 'closed', OLBsystem::TEXTDOMAIN );
						}
						else{
							// ユーザーの予約が入っているか
							if (!empty($timetable[$key]['user_id'])) {
								// 指定されたユーザーによる予約か
								if (!empty($this->user_id) && $timetable[$key]['user_id']==$this->user_id) {
									$class[] = 'you';
									$html = self::htmlReserveLink($this->room_id, $key, __( 'You', OLBsystem::TEXTDOMAIN ) );
								}
								// 開講(予約済)
								else {
									$class[] = 'closed';
									$html = __( 'closed', OLBsystem::TEXTDOMAIN );
								}
							}
							// 開講(予約受付中)
							else {
								$class[] = 'open';
								$html = self::htmlReserveLink($this->room_id, $key, __( 'Open', OLBsystem::TEXTDOMAIN ) );
							}
						}
					}
				}
				printf('<td class="%1$s">%2$s</td>'."\n", implode(' ', $class), $html);
			}
			echo '</tr>';

			$t = $this->calcHour($t, $this->interval);
			$th = substr($t, 0, 2);
			$tm = substr($t, -2);
		}
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	日別個別スケジュールの表示: Output daily schedule board (single)
	 */
	public function dailyTime($out = true){
		$body =	self::htmlDailyColumnTime(false);

		ob_start();
		echo '<table id="single_schedule_time" class="single_schedule_time">'."\n"
			.'<tbody>'."\n"
			.$body
			.'</tbody>'."\n"
			.'</table>'."\n";
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	日別個別スケジュールの表示: Output daily schedule board (single)
	 */
	public function dailyStatus($room_id, $type, $out = true){

		if(empty($this->qs['date'])) {
			//$date = date('Y-m-d', current_time('timestamp'));
			$date = $this->startdate;
		}
		else {
			$date = $this->qs['date'];
		}
		$body =	self::htmlDailyColumnStatus($room_id, $date, $type, false);

		ob_start();
		echo '<table id="single_schedule_{$room_id}" class="single_schedule_status">'."\n"
			.'<tbody>'."\n"
			.$body
			.'</tbody>'."\n"
			.'</table>'."\n";
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	日別個別スケジュールの表示: Output timetable in daily schedule board (single)
	 */
	public function htmlDailyColumnStatus($room_id, $date, $type = 'a', $out = true) {
		$t = $this->starttime;
		$th = substr($this->starttime, 0, 2);
		$tm = substr($this->endtime, -2);

		$user = new olbAuth();
		$this->user_id = $user->data['id'];
		ob_start();
		// 表示開始時刻 - 表示終了時刻
		while($t < $this->endtime){
			$trclass = array( 'time'.str_replace( ':', '', $t ) );
			// 営業開始時刻が指定されている場合
			if(!empty($this->opentime) && $t < $this->opentime){
				$trclass[] = 'invalid';
			}
			// 営業終了時刻が指定されている場合
			else if(!empty($this->closetime) && $t > $this->closetime){
				$trclass[] = 'invalid';
			}
			// 受付時刻が過ぎている場合
			else if($date<date('Y-m-d') || self::isTimeover('reserve', $date, $t)){
				$trclass[] = 'past';
			}
			else {
				$trclass[] = 'valid';
			}

			printf('<tr class="%1$s">'."\n", implode(' ', $trclass));

			$mt = strtotime($date);						// sec
			$key = self::getTimetableKey(date('Y-m-d', $mt), $t);				// ex.'2013-06-06_1030

			$reserved = olbTimetable::opened($room_id, $date);
			$timetable = olbTimetable::get($reserved);
			$room = olbRoom::get($room_id);

			$class = array();
			$html = __( '-', OLBsystem::TEXTDOMAIN );
			$class[] = 'status';

			// 指定された講師による開講があるか
			if (!empty($timetable[$key]['room_id']) && $timetable[$key]['room_id']==$room_id) {
				// 受付時間終了
				if(in_array('past', $trclass)){
					$class[] = 'closed';
					if($type=='a'){
						$html = __( 'closed', OLBsystem::TEXTDOMAIN );
					}
					else if($type=='b'){
						$html = $t;
					}
				}
				else {
					// ユーザーの予約が入っているか
					if (!empty($timetable[$key]['user_id'])) {
						// 指定されたユーザーによる予約か
						if (!empty($this->user_id) && $timetable[$key]['user_id']==$this->user_id) {
							$class[] = 'you';
							if($type=='a'){
								$html = self::htmlReserveLink($room_id, $key, __( 'You', OLBsystem::TEXTDOMAIN ) );
							}
							else if($type=='b'){
								$html = self::htmlReserveLink($room_id, $key, $t);
							}
						}
						// 開講(予約済)
						else {
							$class[] = 'closed';
							if($type=='a'){
								$html = __( 'closed', OLBsystem::TEXTDOMAIN );
							}
							else if($type=='b'){
								$html = $t;
							}
						}
					}
					// 開講(予約受付中)
					else {
						$class[] = 'open';
						if($type=='a'){
							$html = self::htmlReserveLink($room_id, $key, __( 'Open', OLBsystem::TEXTDOMAIN ) );
						}
						else if($type=='b'){
							$html = self::htmlReserveLink($room_id, $key, $t);
						}
					}
				}
			}
			printf('<td class="%1$s">%2$s</td>'."\n", implode(' ', $class), $html);
			echo '</tr>';

			// 次の時刻を設定
			$t = $this->calcHour($t, $this->interval);
			$th = substr($t, 0, 2);
			$tm = substr($t, -2);
		}
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	日別個別スケジュールの時間帯: Output timetable in daily schedule board (single)
	 */
	public function htmlDailyColumnTime($out = true) {
		$t = $this->starttime;
		$th = substr($this->starttime, 0, 2);
		$tm = substr($this->endtime, -2);

		ob_start();
		// 表示開始時刻 - 表示終了時刻
		while($t < $this->endtime){
			$trclass = array();
			// 営業開始時刻が指定されている場合
			if(!empty($this->opentime) && $t < $this->opentime){
				$trclass[] = 'invalid';
			}
			// 営業終了時刻が指定されている場合
			else if(!empty($this->closetime) && $t > $this->closetime){
				$trclass[] = 'invalid';
			}
			// 受付時刻が過ぎている場合
			else if($date<date('Y-m-d') || self::isTimeover('reserve', $date, $t)){
				$trclass[] = 'past';
			}
			else {
				$trclass[] = 'valid';
			}
			printf('<tr class="%1$s">'."\n".'<th class="times">%2$s</th>'."\n", implode(' ', $trclass), $t);
			echo '</tr>';

			// 次の時刻を設定
			$t = $this->calcHour($t, $this->interval);
			$th = substr($t, 0, 2);
			$tm = substr($t, -2);
		}
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	日別スケジュールの表示: Output daily schedule board
	 */
	public function htmlDailySchedule($rooms, $date, $out = false){
		$header = self::htmlDailyScheduleHeader($rooms);
		$body =	self::htmlDailyScheduleBody($rooms, $date);

		ob_start();
		echo '<table id="daily_schedule" class="daily_schedule">'."\n"
			.'<thead>'."\n"
			.$header
			.'</thead>'."\n";

		echo '<tbody>'."\n"
			.$body
			.'</tbody>'."\n"
			.'</table>'."\n";
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	日別スケジュールの講師行の出力: Output the line of room in daily schedule board
	 */
	public function htmlDailyScheduleHeader($rooms, $out = false){

		ob_start();
		printf('<tr class="head">'."\n".'<th>%s</th>'."\n", __('Teacher', OLBsystem::TEXTDOMAIN));

		$class = '';
		for($i=0; $i<count($rooms); $i++){
			if(!empty($rooms[$i]['url'])) {
				$portrait = '';
				$portrait = apply_filters( 'olb_get_portrait', $portrait, $rooms[$i] );
				printf('<th class="%1$s">%4$s<a href="%3$s">%2$s</a></th>'."\n", $class, $rooms[$i]['name'], $rooms[$i]['url'], $portrait);
			}
			else {
				printf('<th class="%1$s">%2$s</th>'."\n", $class, $rooms[$i]['name']);
			}
		}
		echo "</tr>\n";
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	日別スケジュールの時間割の出力: Output timetable in daily schedule board
	 */
	public function htmlDailyScheduleBody($rooms, $date, $out = false) {
		$t = $this->starttime;
		$th = substr($this->starttime, 0, 2);
		$tm = substr($this->endtime, -2);

		$user = new olbAuth();
		$this->user_id = ( isset( $user->data['id'] ) ) ? $user->data['id'] : null;
		ob_start();
		// 表示開始時刻 - 表示終了時刻
		while($t < $this->endtime){
			$trclass = array( 'time'.str_replace( ':', '', $t ) );
			// 営業開始時刻が指定されている場合
			if(!empty($this->opentime) && $t < $this->opentime){
				$trclass[] = 'invalid';
			}
			// 営業終了時刻が指定されている場合
			else if(!empty($this->closetime) && $t > $this->closetime){
				$trclass[] = 'invalid';
			}
			// 受付時刻が過ぎている場合
			else if($date<date('Y-m-d') || self::isTimeover('reserve', $date, $t)){
				$trclass[] = 'past';
			}
			else {
				$trclass[] = 'valid';
			}
			printf('<tr class="%1$s">'."\n".'<th class="times">%2$s</th>'."\n", implode(' ', $trclass), $t);

			$mt = strtotime($date);						// sec
			$key = self::getTimetableKey(date('Y-m-d', $mt), $t);				// ex.'2013-06-06_1030
			// 講師一覧
			for($i=0; $i<count($rooms); $i++) {
				$reserved = olbTimetable::opened($rooms[$i]['id'], $date);
				$timetable = olbTimetable::get($reserved);
				$room = olbRoom::get($rooms[$i]['id']);

				$class = array();
				$html = __( '-', OLBsystem::TEXTDOMAIN );
				$class[] = 'status';

				// 指定された講師による開講があるか
				if (!empty($timetable[$key]['room_id']) && $timetable[$key]['room_id']==$rooms[$i]['id']) {
					// 受付時間終了
					if(in_array('past', $trclass)){
						$class[] = 'closed';
						$html = __( 'closed', OLBsystem::TEXTDOMAIN );
					}
					else {
						// ユーザーの予約が入っているか
						if (!empty($timetable[$key]['user_id'])) {
							// 指定されたユーザーによる予約か
							if (!empty($this->user_id) && $timetable[$key]['user_id']==$this->user_id) {
								$class[] = 'you';
								$html = self::htmlReserveLink($rooms[$i]['id'], $key, __( 'You', OLBsystem::TEXTDOMAIN ) );
							}
							// 開講(予約済)
							else {
								$class[] = 'closed';
								$html = __( 'closed', OLBsystem::TEXTDOMAIN );
							}
						}
						// 開講(予約受付中)
						else {
							$class[] = 'open';
							$html = self::htmlReserveLink($rooms[$i]['id'], $key, __( 'Open', OLBsystem::TEXTDOMAIN ) );
						}
					}
				}
				printf('<td class="%1$s">%2$s</td>'."\n", implode(' ', $class), $html);
			}
			echo '</tr>';

			// 次の時刻を設定
			$t = $this->calcHour($t, $this->interval);
			$th = substr($t, 0, 2);
			$tm = substr($t, -2);
		}
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	ページナビ用クエリ: Set the query string of page navigation
	 */
	public function setPageNaviQuery($today, $days, $query_string){
		parse_str($query_string, $next);
		$next['date'] = date('Y-m-d', strtotime($today)+60*60*24*$days);
		$nextq = http_build_query($next);
		return $nextq;
	}

	/** 
	 *	スケジュール設定表の出力: Output the schedule board for edit
	 */
	public function htmlEditSchedule($out = false){
		$error = '';

		ob_start();
		printf('<form id="edit_schedule" method="post" action="%s">', get_permalink(get_page_by_path($this->edit_schedule_page)->ID));
		printf('<input type="hidden" name="date" value="%s" />', $this->startdate);
		printf('<input type="hidden" name="room_id" value="%s" />', $this->room_id);

		$header = self::htmlEditScheduleHeader();
		$body =	self::htmlEditScheduleBody();

		printf('<input type="submit" name="submit" value="%s" class="submit" />', __( 'update', OLBsystem::TEXTDOMAIN ) );
		echo '<table class="edit_schedule">'."\n"
			.'<thead>'."\n"
			.$header
			.'</thead>'."\n";

		echo '<tbody>'."\n"
			.$body
			.'</tbody>'."\n"
			.'</table>'."\n";
		printf('<input type="submit" name="submit" value="%s" class="submit" />', __( 'update', OLBsystem::TEXTDOMAIN ) );
		wp_nonce_field(OLBsystem::TEXTDOMAIN, 'onetimetoken');
		echo '</form>'."\n";

		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	スケジュール設定表の日付行の出力: Output the line of date in schedule board for edit
	 */
	public function htmlEditScheduleHeader($out = false){
		ob_start();
		echo '<tr class="head">'."\n"
			.'<th>'.__( 'Date', OLBsystem::TEXTDOMAIN ).'</th>'."\n";

		for($i=0; $i<$this->daymax; $i++){
			$mt = strtotime($this->startdate)+(60*60*24*$i);		// sec
			$day = date('m/d', $mt);					// ex.'06/06'
			$w = date( 'w', $mt );
			$wday = sprintf( '(%s)', OLBsystem::dow( $w ) );	// ex.'(Sun)'
			$wclass = strtolower(date('l', $mt));		// ex.'sunday'
			printf('<th class="%1$s">%2$s<br>%3$s</th>'."\n", $wclass, $day, $wday);
		}
		echo "</tr>\n";
		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/** 
	 *	スケジュール設定表の時間割の出力: Output timetable in schedule board for edit
	 */
	public function htmlEditScheduleBody($out = false) {
		$reserved = olbTimetable::openedList();
		$timetable = olbTimetable::get($reserved);

		$t = $this->starttime;
		$th = substr($this->starttime, 0, 2);
		$tm = substr($this->endtime, -2);

		ob_start();
		while($t < $this->endtime){
			$trclass = array( 'time'.str_replace( ':', '', $t ) );

			if(!empty($this->opentime) && $t < $this->opentime){
				$trclass[] = 'invalid';
			}
			else if(!empty($this->closetime) && $t > $this->closetime){
				$trclass[] = 'invalid';
			}
			else {
				$trclass[] = 'valid';
			}
			printf('<tr class="%1$s">'."\n".'<th class="times">%2$s</th>'."\n", implode(' ', $trclass), $t);

			for($i=0; $i<$this->daymax; $i++) {
				$class = array();
				$html = __( '-', OLBsystem::TEXTDOMAIN );
				$class[] = 'status';

				$mt = strtotime($this->startdate)+(60*60*24*$i);		// sec
				$class[] = strtolower(date('l', $mt));		// ex.'sunday'
				$key = self::getTimetableKey(date('Y-m-d', $mt), $t);				// ex.'2013-06-06_1030

				// 受付時刻が過ぎている場合
				$date = date('Y-m-d', $mt);
				if(!in_array('invalid', $trclass) && self::isTimeover('reserve', $date, $t)){
					$class[] = 'past';
				}
				if(!in_array('invalid', $trclass)){ 
					// 講師指定あり
					if (!empty($this->room_id)) {
						// 指定された講師による開講があるか
						if (!empty($timetable[$key]['room_id']) && $timetable[$key]['room_id']==$this->room_id) {
							// 受付終了
							if(in_array('past', $class) && !in_array('invalid', $class)){
								$class[] = 'past';
								$html = $t;
							}
							//else{
								// ユーザーの予約が入っているか
								if (!empty($timetable[$key]['user_id'])) {
									$class[] = 'reserved';
									$html = self::htmlCancelLink($key, $t);
								}
								// 開講(予約受付中)
								else {
									$class[] = 'open';
									$html = $t;
								}
							//}
						}
						else {
							$class[] = 'closed';
							$html = $t;
						}
					}
				}

				if(in_array('open', $class)){
					$open = 'open';
				}
				else if(in_array('you', $class)){
					$open = 'reserved';
				}
				else {
					$open = 'close';
				}

				if(in_array('valid', $trclass) && (in_array('open', $class) || in_array('closed', $class))){
					$html .= sprintf('<input type="hidden" name="org[%1$s]" value="%2$s" />', $key, $open );
					$html .= sprintf('<input type="hidden" name="new[%1$s]" value="%2$s" />', $key, $open );
				}
				printf('<td class="%1$s">%2$s</td>'."\n", implode(' ', $class), $html);
			}
			echo '</tr>';

			// 次の時刻を設定
			$t = $this->calcHour($t, $this->interval);
			$th = substr($t, 0, 2);
			$tm = substr($t, -2);
		}

		$html = ob_get_contents();
		ob_end_clean();
		if ($out) {
			echo $html;
		}
		else {
			return $html;
		}
	}

}
?>
