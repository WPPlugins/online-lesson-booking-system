<?php
/** 
 *	タイムテーブル: Timetable
 */
add_filter( 'olb_to_user_email', array( 'olbFormAction', 'to_user_email' ), 10, 1 );
add_filter( 'olb_to_teacher_email', array( 'olbFormAction', 'to_teacher_email' ), 10, 1 );
add_filter( 'olb_email_values', array( 'olbFormAction', 'email_values' ), 10, 2 );

class olbFormAction {

	/**
	 *	ユーザー宛先: User's email
	 */
	public static function to_user_email( $email ) {
		/*
		 Default email: e.g. 'John <john@example.com>'
		 How to add other addresses,
		 e.g. 'John <john@example.com>, paula@example.com, ...'
		*/
		return $email;
	}

	/**
	 *	講師宛先: Teacher's email
	 */
	public static function to_teacher_email( $email ) {
		/*
		 Default email: e.g. 'John <john@example.com>'
		 How to add other addresses,
		 e.g. 'John <john@example.com>, paula@example.com, ...'
		*/
		// Send to Admin too
		$olb_options = get_option( OLBsystem::TEXTDOMAIN );
		if ( ! empty( $olb_options['mail']['send_to_admin_too'] ) ) {
			$email .= ','.$olb_options['mail']['send_to_admin_too'];
		}
		return $email;
	}

	/**
	 *	予約・キャンセル通知メールの変数: The variable of the notice mail of reservation
	 */
	public static function email_values( $args, $result ) {
		list( $search, $replace ) = $args;
		return array( $search, $replace );
	}

	/**
	 *	予約・キャンセル: Reservation and Cancellation by member
	 */
	public static function reservation() {
		global $wpdb, $olb;

		$error = '';
		if (empty($_POST['onetimetoken']) || !wp_verify_nonce($_POST['onetimetoken'], OLBsystem::TEXTDOMAIN)) {
			$error = 'NONCE_ERROR';
		}
		else if(empty($_POST['room_id']) || empty($_POST['user_id']) || empty($_POST['reservedate']) || empty($_POST['reservetime'])) {
			$error = 'PARAMETER_INSUFFICIENT';
		}
		else if($_POST['reserveaction']!='reserve' && $_POST['reserveaction']!='cancel') {
			$error = 'INVALID_PARAMETER';
		}
		else {
			$result = array();
			$result = apply_filters( 'olb_can_reservation', $result, $_POST['room_id'], $_POST['user_id'], $_POST['reservedate'], $_POST['reservetime'] );
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

			$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
			// 予約
			if($_POST['reserveaction']=='reserve' && $code=='NOT_RESERVED'){
				$record['user_id'] = $user->data['id'];
				$record['free']	= ( $user->canFreeReservation() ) ? 1 : 0;
				$table = $prefix."history";
				$ret = $wpdb->insert(
								$table,
								array(
									'date'=>$record['date'],
									'time'=>$record['time'],
									'room_id' => $record['room_id'],
									'user_id' => $record['user_id'],
									'free' => $record['free']
								)
							);
				$record = $olb->reserved($record['room_id'], $record['date'], $record['time']);
				$result['record'] = $record;
				do_action( 'olb_reservation', $result );
			}
			// CANCEL
			else if($_POST['reserveaction']=='cancel' && $code=='ALREADY_RESERVED'){
				$query = "DELETE FROM ".$prefix."history WHERE `id`=%d";
				$ret = $wpdb->query($wpdb->prepare($query, array($record['id'])), ARRAY_A);
				if(!$ret){
					$error = 'CANCEL_FAILED';
				}
				else {
					do_action( 'olb_cancellation', $result );
				}
			}
			// エラーあり
			else {
				$error = $code;
			}
		}
		$datetime = olbTimetable::getTimetableKey($record['date'], $record['time']);
		$url = get_permalink(get_page_by_path($olb->reserve_form_page)->ID);
		// エラーあり
		if($error) {
			if ( in_array( $error, array( 'PARAMETER_INSUFFICIENT', 'INVALID_PARAMETER','NO_RECORD', 'NONEXISTENT_TEACHER' ) ) ) {
				$qs = array(
					'error' => $error
				);
			} else {
				$qs = array(
					't' => $datetime,
					'room_id' => $record['room_id']
				);
			}
			$return_url = add_query_arg( $qs, $url );
			header('Location:'.$return_url);
			exit;
		}


		$options = $olb->getPluginOptions('mail');
		$rem = -1;
		if ( !empty( $user->data['olbterm'] ) ) {
			list( $ty, $tm, $td ) = explode( '-', $user->data['olbterm'] );
			$t = mktime( 0, 0, 0, $tm, $td, $ty ) - current_time( 'timestamp' );
			$rem = ceil( $t / ( 60*60*24 ) );
		}
		if ( $rem >= 0 ) {
			$rem_text = sprintf( __( '%d days left', OLBsystem::TEXTDOMAIN ), $rem );
		}
		else {
			$rem_text = __( 'Expired', OLBsystem::TEXTDOMAIN );
		}
		$search = array(
			'%USER_ID%',
			'%USER_NAME%',
			'%USER_FIRST_NAME%',
			'%USER_LAST_NAME%',
			'%USER_EMAIL%',
			'%USER_SKYPE%',
			'%USER_TERM%',
			'%USER_TERM_REM%',
			'%ROOM_NAME%',
			'%ROOM_FIRST_NAME%',
			'%ROOM_LAST_NAME%',
			'%ROOM_SKYPE%',
			'%RESERVE_ID%',
			'%RESERVE_DATE%',
			'%RESERVE_TIME%',
			'%SEND_TIME%',
			);
		$room_info = get_userdata( $room['id'] );
		$replace = array(
			$user->data['id'],
			$user->data['name'],
			$user->data['firstname'],
			$user->data['lastname'],
			$user->data['email'],
			$user->data['skype'],
			$user->data['olbterm'],
			$rem_text,
			$room['name'],
			$room_info->first_name,
			$room_info->last_name,
			$room_info->user_skype,
			$record['id'],
			$record['date'],
			substr($record['time'], 0, 5),
			date('Y-m-d H:i:s', current_time('timestamp')),
			);

		list( $search, $replace ) = apply_filters( 'olb_email_values', array( $search, $replace ), $result );
		// 予約通知
		if($_POST['reserveaction']=='reserve'){
			$datetime = olbTimetable::getTimetableKey($_POST['reservedate'], $_POST['reservetime']);
			$cancel_url = get_permalink(get_page_by_path($olb->reserve_form_page)->ID);
			$cancel_query = (strstr($cancel_url, '?')) ? '&' : '?';
			$cancel_query .= sprintf('t=%s&room_id=%d', $datetime, $_POST['room_id']);
			$search = array_merge($search, array(
				'%CANCEL_DEADLINE%',
				'%CANCEL_URL%',
				));
			$replace = array_merge($replace, array(
				sprintf(__('%d minutes before start time', OLBsystem::TEXTDOMAIN), $olb->cancel_deadline),
				$cancel_url.$cancel_query,
				));

			list($mail_body, $to_user_subject, $to_teacher_subject) = str_replace(
					$search,
					$replace,
					array(
						$options['reservation_message'],
						$options['reservation_subject'],
						$options['reservation_subject_to_teacher']
						)
				);
			if($olb->free > 0){
				$free = $user->canFreeReservation();
				if($record['free'] || $free) {
					$mail_body .= "\n";
					if($record['free']){
						$mail_body .= __('Free reservation applied.', OLBsystem::TEXTDOMAIN)."\n";
					}
					$mail_body .= sprintf(__('Your free reservation: %d times left.', OLBsystem::TEXTDOMAIN)."\n", $user->canFreeReservation());
				}
			}
			$to_user_signature = $options['signature'];
		}
		// キャンセル通知
		else {
			list($mail_body, $to_user_subject, $to_teacher_subject) = str_replace(
					$search,
					$replace,
					array(
						$options['cancel_message'],
						$options['cancel_subject'],
						$options['cancel_subject_to_teacher']
						)
				);
			$to_user_signature = $options['signature'];
		}
		$to_user_body = $mail_body.$to_user_signature;
		$to_user_headers = sprintf("From: %s\r\n", $options['from_email']);
		$to_user_email = $user->data['email'];
		$to_user_email = apply_filters( 'olb_to_user_email', $to_user_email );

		$ret = olbTimetable::sendReserveMail($to_user_email , $to_user_subject, $to_user_body, $to_user_headers);
		// エラーあり
		if(!$ret) {
			$error = 'USER_SEND_ERROR';
			$url = get_permalink(get_page_by_path($olb->reserve_form_page)->ID);
			$query_string = (strstr($url, '?')) ? '&' : '?';
			$query_string .= sprintf('error=%s', $error);
			header('Location:'.$url.$query_string);
			exit;
		}

		// 講師宛
		$to_teacher_body = $mail_body;
		$to_teacher_headers = sprintf("From: %s\r\n", $to_user_email);
		$to_teacher_email = $room['email'];
		$to_teacher_email = apply_filters( 'olb_to_teacher_email', $to_teacher_email );

		$ret = olbTimetable::sendReserveMail($to_teacher_email, $to_teacher_subject, $to_teacher_body, $to_teacher_headers);

		// ex. '?t=2013-07-08_1200&room_id=2'
		$qs = array(
			't' => $datetime,
			'room_id' => $record['room_id'],
			'success' => $_POST['reserveaction']
		);
		$return_url = add_query_arg( $qs, $url );
		header('Location:'.$return_url);
		exit;
	}

	/**
	 *	講師都合のキャンセル: Cancellation by teacher
	 */
	public static function cancellation() {
		global $wpdb, $olb;
		
		$error = '';
		if (empty($_POST['onetimetoken']) || !wp_verify_nonce($_POST['onetimetoken'], OLBsystem::TEXTDOMAIN)) {
			$error = 'NONCE_ERROR';
		}
		else if(empty($_POST['room_id']) || empty($_POST['user_id']) || empty($_POST['reservedate']) || empty($_POST['reservetime'])) {
			$error = 'PARAMETER_INSUFFICIENT';
		}
		else if($_POST['reserveaction']!='cancel') {
			$error = 'INVALID_PARAMETER';
		}
		else {
			$result = olbTimetable::canCancellation($_POST['room_id'], $_POST['reservedate'], $_POST['reservetime']);
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

			// CANCEL
			if($_POST['reserveaction']=='cancel' && $code=='ALREADY_RESERVED'){
				$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
				$query = "DELETE FROM ".$prefix."history WHERE `id`='%d'";
				$ret = $wpdb->query($wpdb->prepare($query, array($record['id'])), ARRAY_A);
				if(!$ret){
					$error = 'CANCEL_FAILED';
				}
				$query = "DELETE FROM ".$prefix."timetable WHERE `room_id`='%d' AND `date`='%s' AND `time`='%s'";
				$ret = $wpdb->query($wpdb->prepare($query, array($record['room_id'], $record['date'], $record['time'])), ARRAY_A);
				do_action( 'olb_cancellation_by_teacher', $result );
			}
			// エラーあり
			else {
				$error = $code;
			}
		}
		// エラーあり
		if($error) {
			$url = get_permalink(get_page_by_path($olb->cancel_form_page)->ID);
			$query_string = (strstr($url, '?')) ? '&' : '?';
			$query_string .= sprintf('error=%s', $error);
			header('Location:'.$url.$query_string);
			exit;
		}

		$options = $olb->getPluginOptions('mail');
		$rem = -1;
		if ( !empty( $user->data['olbterm'] ) ) {
			list( $ty, $tm, $td ) = explode( '-', $user->data['olbterm'] );
			$t = mktime( 0, 0, 0, $tm, $td, $ty ) - current_time( 'timestamp' );
			$rem = ceil( $t / ( 60*60*24 ) );
		}
		if ( $rem >= 0 ) {
			$rem_text = sprintf( __( '%d days left', OLBsystem::TEXTDOMAIN ), $rem );
		}
		else {
			$rem_text = __( 'Expired', OLBsystem::TEXTDOMAIN );
		}
		// キャンセル通知
		$search = array(
			'%USER_ID%',
			'%USER_NAME%',
			'%USER_FIRST_NAME%',
			'%USER_LAST_NAME%',
			'%USER_EMAIL%',
			'%USER_SKYPE%',
			'%USER_TERM%',
			'%USER_TERM_REM%',
			'%ROOM_NAME%',
			'%ROOM_FIRST_NAME%',
			'%ROOM_LAST_NAME%',
			'%ROOM_SKYPE%',
			'%RESERVE_ID%',
			'%RESERVE_DATE%',
			'%RESERVE_TIME%',
			'%SEND_TIME%',
			'%MESSAGE%'
			);
		$room_info = get_userdata( $room['id'] );
		$replace = array(
			$user->data['id'],
			$user->data['name'],
			$user->data['firstname'],
			$user->data['lastname'],
			$user->data['email'],
			$user->data['skype'],
			$user->data['olbterm'],
			$rem_text,
			$room['name'],
			$room_info->first_name,
			$room_info->last_name,
			$room_info->user_skype,
			$record['id'],
			$record['date'],
			substr($record['time'], 0, 5),
			date('Y-m-d H:i:s', current_time('timestamp')),
			$_POST['message'],
			);
		list( $search, $replace ) = apply_filters( 'olb_email_values', array( $search, $replace ), $result );

		list($mail_body, $to_user_subject, $to_teacher_subject) = str_replace(
				$search,
				$replace,
				array(
					$options['cancel_message_by_teacher'],
					$options['cancel_subject_by_teacher'],
					$options['cancel_subject_by_teacher_to_teacher']
					)
			);
		$to_user_signature = $options['signature'];
		$to_user_body = $mail_body.$to_user_signature;
		$to_user_headers = sprintf("From: %s\r\n", $options['from_email']);
		$to_user_email = $user->data['email'];
		$to_user_email = apply_filters( 'olb_to_user_email', $to_user_email );

		$ret = olbTimetable::sendReserveMail($to_user_email , $to_user_subject, $to_user_body, $to_user_headers);
		// エラーあり
		if(!$ret) {
			$error = 'USER_SEND_ERROR';
			$url = get_permalink(get_page_by_path($olb->reserve_form_page)->ID);
			$query_string = (strstr($url, '?')) ? '&' : '?';
			$query_string .= sprintf('error=%s', $error);
			header('Location:'.$url.$query_string);
			exit;
		}

		// 講師宛
		$to_teacher_body = $mail_body;
		$to_teacher_headers = sprintf("From: %s\r\n", $to_user_email);
		$to_teacher_email = $room['email'];
		$to_teacher_email = apply_filters( 'olb_to_teacher_email', $to_teacher_email );

		$ret = olbTimetable::sendReserveMail($to_teacher_email, $to_teacher_subject, $to_teacher_body, $to_teacher_headers);
		$url = get_permalink(get_page_by_path($olb->edit_schedule_page)->ID);
		if(empty($_POST['returnurl'])) {
			header('Location:'.$url );
		}
		else {
			header('Location:'.$_POST['returnurl'] );
		}
		exit;
	}

	/**
	 *	出欠レポート: Report of absent by teacher
	 */
	public static function report() {
		global $wpdb, $olb;
		
		$error = '';
		if (empty($_POST['onetimetoken']) || !wp_verify_nonce($_POST['onetimetoken'], OLBsystem::TEXTDOMAIN)) {
			$error = 'NONCE_ERROR';
		}
		else if(empty($_POST['room_id']) || empty($_POST['user_id']) || empty($_POST['reservedate']) || empty($_POST['reservetime'])) {
			$error = 'PARAMETER_INSUFFICIENT';
		}
		else if($_POST['reserveaction']!='report') {
			$error = 'INVALID_PARAMETER';
		}
		else {
			$result = olbTimetable::canReport($_POST['room_id'], $_POST['reservedate'], $_POST['reservetime']);
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

			$where = array('id'=>$record['id']);
			$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
			$query = "UPDATE ".$prefix."history SET absent=%d WHERE `id`=%d";
			// REPORT
			if($_POST['reserveaction']=='report' && $code=='ALREADY_RESERVED'){
				$absent = (isset($_POST['absent'])) ? 1 : 0;
				$ret = $wpdb->query($wpdb->prepare($query, array($absent, $record['id'])), ARRAY_A);
			}
			// エラーあり
			else {
				$error = $code;
			}
		}

		$url = get_permalink(get_page_by_path($olb->report_form_page)->ID);
		// エラーあり
		if($error) {
			$query_string = (strstr($url, '?')) ? '&' : '?';
			$query_string .= sprintf('error=%s', $error);
			header('Location:'.$url.$query_string);
			exit;
		}
		
		if(empty($_POST['returnurl'])) {
			header('Location:'.$url );
		}
		else {
			header('Location:'.$_POST['returnurl'] );
		}
		exit;
	}

	/**
	 *	講師用スケジューラ: Scheduler for teacher
	 */
	public static function scheduler() {
		global $wpdb, $olb;
		
		$error = '';
		if (empty($_POST['onetimetoken']) || !wp_verify_nonce($_POST['onetimetoken'], OLBsystem::TEXTDOMAIN)) {
			$url = get_permalink(get_page_by_path($olb->edit_schedule_page)->ID);
			$query_string = (strstr($url, '?')) ? '&' : '?';
			if(!empty($olb->qs)){
				$query_string .= implode('&', $olb->qs).'&';
			}
			$query_string .= 'error=NONCE_ERROR';
			header('Location:'.$url.$query_string);
			exit;
		}
		$user = new olbAuth();
		$x = array();

		foreach($_POST['new'] as $key=>$value){
			if($_POST['org'][$key]!=$value){
				$x[$key]=$value;
			}
		}

		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		foreach($x as $key=>$value){
			list($date, $time) = olbTimetable::parseTimetableKey($key);
			if($value=='close'){
				$query = "DELETE FROM ".$prefix."timetable "
					  ."WHERE `date`='%s' AND `time`='%s' AND `room_id`='%s' ";
				$result = $wpdb->query($wpdb->prepare($query, array($date, $time, $_POST['room_id'])), ARRAY_A);
			}
			// $value == 'open'
			else {
				$table = $prefix."timetable";
				$result = $wpdb->insert(
								$table,
								array(
									'date'=>$date,
									'time'=>$time,
									'room_id' => $_POST['room_id']
								)
							);
			}
		}
		$qs = array();
		$query_string = '';
		if($user->isAdmin() && !empty($_POST['room_id'])){
			$qs[] = 'room_id='.$_POST['room_id'];
		}
		if(!empty($_POST['date'])){
			$qs[] = 'date='.$_POST['date'];
		}
		$url = get_permalink(get_page_by_path($olb->edit_schedule_page)->ID);
		$query_string = (strstr($url, '?')) ? '&' : '?';
		$query_string .= implode('&', $qs);
		header('Location:'.$url.$query_string);
		exit;
	}

	/**
	 *	管理者用ツール: Dashboard for admin
	 */
	public static function admin_pretending_selecter($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'pretends' => null,
				),
				$atts
			)
		);

		ob_start();
		$admin_show = false;
		$information = '';
		if ( $olb->operator->isLoggedIn() && $olb->operator->isAdmin() ) {
				$url = ( empty( $_SERVER["HTTPS"] ) ) ? "http://" : "https://";
				$url .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

			switch( $pretends ) {
			case 'teacher':
				$rooms = olbRoom::getAll();
				if ( !empty( $rooms ) ) {
					echo '<div>'.__('Select teacher', OLBsystem::TEXTDOMAIN).'</div>';
					echo <<<EOD
<form name="userlist">
<select name="link" onChange="if(document.userlist.link.value){location.href=document.userlist.link.value;}">
<option></option>
EOD;

					foreach($rooms as $r){
						$query_string = ( strstr( $url, '?' ) ) ? '&' : '?';
						$query_string .= sprintf('room_id=%d', $r['id']);
						$name = $r['name'].'('.$r['id'].')';
						printf('<option value="%s%s">%s</option>'."\n", $url, $query_string, $name );
					}
					echo "</select>\n</form>\n";

					echo '<div>'.__("Or input teacher's ID (ex.'2')", OLBsystem::TEXTDOMAIN).'</div>';
					echo <<<EOD
<form name="usersearch" method="get">
<input type="text" name="room_id" value="" />
<input type="submit" value="go" />
</form>
EOD;
				}
				else {
					$error = 'NO_TEACHERS';
					echo apply_filters( 'olb_error', $information, $error );
				}
				break;

			case 'user':
				$users = olbAuth::getAll();
				if ( !empty( $users ) ) {
					echo '<div>'.__('Select member', OLBsystem::TEXTDOMAIN).'</div>';
					echo <<<EOD
<form name="userlist">
<select name="link" onChange="if(document.userlist.link.value){location.href=document.userlist.link.value;}">
<option></option>
EOD;

					foreach($users as $u){
						$query_string = ( strstr( $url, '?' ) ) ? '&' : '?';
						$query_string .= sprintf('user_id=%d', $u['id']);
						$name = $u['loginname'].' ('.$u['id'].') '.': '.$u['name'];
						printf('<option value="%s%s">%s</option>'."\n", $url, $query_string, $name );
					}
					echo "</select>\n</form>\n";

					echo '<div>'.__("Or input member's ID (ex.'5')", OLBsystem::TEXTDOMAIN).'</div>';
					echo <<<EOD
<form name="usersearch" method="get">
<input type="text" name="user_id" value="" />
<input type="submit" value="go" />
</form>
EOD;

				}
				else {
					$error = 'NO_MEMBERS';
					echo apply_filters( 'olb_error', $information, $error );
				}
				break;

			default:
				break;
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	public static function admin_pretending_user( $target_user, $args ) {
		global $olb, $post;

		$ret = false;
		$information = $error = '';
		if ( $olb->operator->isLoggedIn() && $olb->operator->isAdmin() ) {
			$target_user = null;
			switch( $args['pretends'] ) {
			case 'teacher':
				if ( isset($olb->qs['room_id'] ) ) {
					$target_user = new olbAuth($olb->qs['room_id']);
					if ( !empty( $target_user->data['id'] ) && $target_user->isRoomManager() ) {
						$ret = $target_user;
						echo self::admin_pretending_now( 'room', $target_user );
					}
					else {
						$error = 'NONEXISTENT_TEACHER';
					}
				}
				else {
					$error = 'CHOOSE_TEACHER';
				}
				if ( $error ) {
					echo olbFormAction::admin_pretending_selecter( $args );
				}
				break;

			case 'user':
				if ( isset($olb->qs['user_id'] ) ) {
					$target_user = new olbAuth($olb->qs['user_id']);
					if ( !empty( $target_user->data['id'] ) && $target_user->isMember() ) {
						$ret = $target_user;
						echo self::admin_pretending_now( 'user', $target_user );
					}
					else {
						$error = 'NONEXISTENT_MEMBER';
					}
				}
				else {
					$error = 'CHOOSE_MEMBER';
				}
				if ( $error ) {
					echo olbFormAction::admin_pretending_selecter( $args );
				}
				break;

			default:

			}
		}
		return $ret;
	}

	public static function admin_pretending_now( $mode, $target_user ) {
		global $olb, $post;

		$url = ( empty( $_SERVER["HTTPS"] ) ) ? "http://" : "https://";
		$url .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

		// 先祖postのID
		$post_ancestors = get_post_ancestors( $post->ID );
		$ancestor_id = array_pop( $post_ancestors );
		$ancestor = ( $ancestor_id ) ? get_post( $ancestor_id ) : $post;

		if ( $mode == 'user' ) {
			$_SESSION['admin_pretend'] = array(
				'ancestor' => $ancestor->ID,
				'user_id' => $target_user->data['id']
			);
			$message = __('Admin is pretending a user', OLBsystem::TEXTDOMAIN );
		}
		if ( $mode == 'room' ) {
			$_SESSION['admin_pretend'] = array(
				'ancestor' => $ancestor->ID,
				'room_id' => $target_user->data['id']
			);
			$message = __('Admin is pretending a teacher', OLBsystem::TEXTDOMAIN );
		}
		$html = sprintf( '<p>%s "%s". [<a href="%s">%s</a>]</p>', 
			$message, 
			$target_user->data['name'].'(ID:'.$target_user->data['id'].')', 
			$url.'&pretend_off', 
			__('OFF', OLBsystem::TEXTDOMAIN )
		);
		return $html;
	}

}
?>