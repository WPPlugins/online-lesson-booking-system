<?php
/** 
 *	ショートコード: Short code
 */
class olbShortcode {
	/** 
	 *	[ショートコード]日別スケジュール: [Short code]Dayly schedule board
	 */
	public static function showDailySchedule($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
				),
				$atts
			)
		);

		ob_start();
		$rooms = new olbRoom($olb->room_per_page);
		$format = <<<EOD
<div id="list_pagenavi" class="list_pagenavi">
<div id="prev_page" class="prev_page">%PREV_PAGE%</div>
<div id="list_datenavi" class="list_datenavi">
%PREV_DATE%
%CURRENT_DATE%
%NEXT_DATE%
</div>
<div id="next_page" class="next_page">%NEXT_PAGE%</div>
</div>
EOD;
		$search = array(
				'%PREV_PAGE%',
				'%PREV_DATE%',
				'%CURRENT_DATE%',
				'%NEXT_DATE%',
				'%NEXT_PAGE%',
			);
		$text_prev = __('&laquo; PREV', OLBsystem::TEXTDOMAIN);
		$text_next = __('NEXT &raquo;', OLBsystem::TEXTDOMAIN);
		$text_prevday = __('&laquo; PREV DAY', OLBsystem::TEXTDOMAIN);
		$text_nextday = __('NEXT DAY &raquo;', OLBsystem::TEXTDOMAIN);
		$replace = array(
				$rooms->getPrevPageLink(-1, $text_prev, $_SERVER['QUERY_STRING']),
				$rooms->getPrevDateLink($olb->startdate, -1, $text_prevday, $_SERVER['QUERY_STRING']),
				$olb->startdate,
				$rooms->getNextDateLink($olb->startdate, 1, $text_nextday, $_SERVER['QUERY_STRING']),
				$rooms->getNextPageLink( 1, $text_next, $_SERVER['QUERY_STRING']),
			);
		echo str_replace($search, $replace, $format);
		echo $olb->htmlDailySchedule($rooms->getList(), $olb->startdate);
		echo str_replace($search, $replace, $format);

		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}


	/** 
	 *	[ショートコード]週間スケジュール: [Short code]Weekly schedule board
	 */
	public static function showWeeklySchedule($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'id' => null,
				),
				$atts
			)
		);

		ob_start();
		$olb->room_id = $id;
		$format = <<<EOD
<div id="list_pagenavi" class="list_pagenavi">
<div id="prev_page" class="prev_page">&nbsp;</div>
<div id="list_datenavi" class="list_datenavi">
%PREV_DATE%
%CURRENT_DATE%
%NEXT_DATE%
</div>
<div id="next_page" class="next_page">&nbsp;</div>
</div>
EOD;
		$search = array(
				'%PREV_DATE%',
				'%CURRENT_DATE%',
				'%NEXT_DATE%',
			);
		$text_prevweek = __('&laquo; PREV WEEK', OLBsystem::TEXTDOMAIN);
		$text_nextweek = __('NEXT WEEK &raquo;', OLBsystem::TEXTDOMAIN);
		$replace = array(
				olbPaging::getPrevDateLink($olb->startdate, -7, $text_prevweek, $_SERVER['QUERY_STRING']),
				$olb->startdate,
				olbPaging::getNextDateLink($olb->startdate,  7, $text_nextweek, $_SERVER['QUERY_STRING']),
			);
		echo str_replace($search, $replace, $format);
		echo $olb->htmlWeeklySchedule();
		echo str_replace($search, $replace, $format);

		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]スケジュール設定: [Short code]Edit schedule board (for room manager)
	 */
	public static function showEditSchedule($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
				),
				$atts
			)
		);

		ob_start();
		$target_user = false;
		$args = array(
			'pretends' => 'teacher',
		);
		$target_user = apply_filters( 'olb_admin_pretending_user', $target_user, $args );

		if( $olb->operator->isLoggedIn() && ( $olb->operator->isRoomManager() || !empty( $target_user ) ) ) {
			$room_id = ( !empty( $target_user ) ) ? $target_user->data['id'] : $olb->room_id;
			$room = olbRoom::get( $room_id );

			printf('<h3>%s</h3>'."\n", $room['name']);
			$format = <<<EOD
<div id="list_pagenavi" class="list_pagenavi">
<div id="prev_page" class="prev_page">&nbsp;</div>
<div id="list_datenavi" class="list_datenavi">
%PREV_DATE%
%CURRENT_DATE%
%NEXT_DATE%
</div>
<div id="next_page" class="next_page">&nbsp;</div>
</div>
EOD;
			$search = array(
					'%PREV_DATE%',
					'%CURRENT_DATE%',
					'%NEXT_DATE%',
				);
			$text_prevweek = __('&laquo; PREV WEEK', OLBsystem::TEXTDOMAIN);
			$text_nextweek = __('NEXT WEEK &raquo;', OLBsystem::TEXTDOMAIN);
			$replace = array(
					olbPaging::getPrevDateLink($olb->startdate, -7, $text_prevweek, $_SERVER['QUERY_STRING']),
					$olb->startdate,
					olbPaging::getNextDateLink($olb->startdate,  7, $text_nextweek, $_SERVER['QUERY_STRING']),
				);
			echo str_replace($search, $replace, $format);
			echo $olb->htmlEditSchedule();
			echo str_replace($search, $replace, $format);
		}
		else {
			if ( !$olb->operator->isAdmin()) {
				printf( '<div class="alert alert-error">%s</div>', __('Do not have authority to show this page.', OLBsystem::TEXTDOMAIN) );
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]予約フォーム: [Short code]Reserve form
	 */
	public static function showReserveForm($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'position' => 'before',
				),
				$atts
			)
		);

		ob_start();
		if($olb->operator->isLoggedIn() && $olb->operator->isMember()){
			if($position == 'before') {
				echo $content;
			}
			echo olbTimetable::htmlReserveForm();
			if($position == 'after') {
				echo $content;
			}
		}
		else {
			printf( '<div class="alert alert-error">%s</div>', __('Do not have authority to show this page.', OLBsystem::TEXTDOMAIN) );
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}


	/** 
	 *	[ショートコード]講師用キャンセルフォーム: [Short code]Cancel form (from Room manager) 
	 */
	public static function showCancelForm($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'position' => 'before',
				),
				$atts
			)
		);

		ob_start();
		if($olb->operator->isLoggedIn() && ($olb->operator->isRoomManager() || $olb->operator->isAdmin())){
			if($position == 'before') {
				echo $content;
			}
			echo olbTimetable::htmlCancelForm();
			if($position == 'after') {
				echo $content;
			}
		}
		else {
			printf( '<div class="alert alert-error">%s</div>', __('Do not have authority to show this page.', OLBsystem::TEXTDOMAIN) );
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]講師用評価フォーム: [Short code]Report form (from Room manager) 
	 */
	public static function showReportForm($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'position' => 'before',
				),
				$atts
			)
		);

		ob_start();
		if($olb->operator->isLoggedIn() && ($olb->operator->isRoomManager() || $olb->operator->isAdmin())){
			if($position == 'before') {
				echo $content;
			}
			echo olbTimetable::htmlReportForm();
			if($position == 'after') {
				echo $content;
			}
		}
		else {
			printf( '<div class="alert alert-error">%s</div>', __('Do not have authority to show this page.', OLBsystem::TEXTDOMAIN) );
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]会員情報参照: [Short code]Refer members information
	 */
	public static function referMembersInfo($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
				),
				$atts
			)
		);

		ob_start();
		$information = $error = '';
		if($olb->operator->isLoggedIn() && ( $olb->operator->isRoomManager() || $olb->operator->isAdmin() ) ){
			if ( isset( $olb->qs['user_id'] ) ) {
				$user = olbAuth::getUser($_GET['user_id']);
				if(!empty($user)){
					echo olbAuth::htmlUser($user);
				}
				else {
					$error = 'NONEXISTENT_MEMBER';
				}
			}
			else {
				$error = 'PARAMETER_INSUFFICIENT';
			}
			if ( $error ) {
				printf( '<div class="alert alert-error">%s</div>', apply_filters( 'olb_error', $information, $error ) );
			}
		}
		else {
			printf( '<div class="alert alert-error">%s</div>', __('Do not have authority to show this page.', OLBsystem::TEXTDOMAIN) );
		}

		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]会員受講履歴参照: [Short code ] Refer member's attendance history 
	 */
	public static function referMembersHistory($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'perpage' => 10,
					'pagenavi' => false,
				),
				$atts
			)
		);

		ob_start();
		$information = $error = '';
		if($olb->operator->isLoggedIn() && ($olb->operator->isRoomManager() || $olb->operator->isAdmin() ) ) {
			if ( isset($olb->qs['user_id']) ) {
				$user = olbAuth::getUser($olb->qs['user_id']);
				if(!empty($user)){
					$records = new olb_member_history( $user['id'], $perpage);
					if($records->recordmax){
						if($pagenavi){
							echo $records->page_navi($records);
						}
						echo $records->html();
					}
					else {
						echo $content;
					}
				}
				else {
					$error = 'NONEXISTENT_MEMBER';
				}
			}
			else {
				$error = 'PARAMETER_INSUFFICIENT';
			}
			if ( $error ) {
				printf( '<div class="alert alert-error">%s</div>', apply_filters( 'olb_error', $information, $error ) );
			}
		}
		else {
			printf( '<div class="alert alert-error">%s</div>', __('Do not have authority to show this page.', OLBsystem::TEXTDOMAIN) );
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]会員受講履歴: [Short code ] Member's attendance history 
	 */
	public static function showMembersHistory($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'perpage' => 10,
					'pagenavi' => true,
				),
				$atts
			)
		);

		ob_start();
		$target_user = false;
		$args = array(
			'pretends' => 'user',
		);
		$target_user = apply_filters( 'olb_admin_pretending_user', $target_user, $args );

		if( $olb->operator->isLoggedIn() && ( $olb->operator->isMember() || !empty( $target_user ) ) ) {
			$user_id = ( !empty( $target_user ) ) ? $target_user->data['id'] : $olb->operator->data['id'];
			$records = new olb_member_history( $user_id, $perpage);
			if($records->recordmax){
				if($pagenavi){
					echo $records->page_navi($records);
				}
				echo $records->html();
			}
			else {
				echo $content;
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]会員予定表示: [Short code]Member's future schedule
	 */
	public static function showMembersSchedule($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'perpage' => 10,
					'pagenavi' => true,
				),
				$atts
			)
		);

		ob_start();
		$target_user = false;
		$args = array(
			'pretends' => 'user',
		);
		$target_user = apply_filters( 'olb_admin_pretending_user', $target_user, $args );

		if( $olb->operator->isLoggedIn() && ( $olb->operator->isMember() || !empty( $target_user ) ) ) {
			$user_id = ( !empty( $target_user ) ) ? $target_user->data['id'] : $olb->operator->data['id'];
			$records = new olb_member_schedule($user_id, $perpage);
			if($records->recordmax){
				if($pagenavi){
					echo $records->page_navi($records);
				}
				echo $records->html();
			}
			else {
				echo $content;
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]講師講義履歴: [Short code ] Teacher's lecuture history 
	 */
	public static function showRoomHistory($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'perpage' => 10,
					'pagenavi' => true,
				),
				$atts
			)
		);

		ob_start();
		$target_user = false;
		$args = array(
			'pretends' => 'teacher',
		);
		$target_user = apply_filters( 'olb_admin_pretending_user', $target_user, $args );

		if($olb->operator->isLoggedIn() && ( $olb->operator->isRoomManager() || !empty( $target_user ) ) ){
			$room_id = ( !empty( $target_user ) ) ? $target_user->data['id'] : $olb->operator->data['id'];
			$records = new olb_room_history( $room_id, $perpage);
			if($records->recordmax){
				if($pagenavi){
					echo $records->page_navi($records);
				}
				echo $records->html();
			}
			else {
				echo $content;
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]講師予定表示: [Short code] Teacher's future schedule
	 */
	public static function showRoomSchedule($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'perpage' => 10,
					'pagenavi' => true,
				),
				$atts
			)
		);

		ob_start();
		$target_user = false;
		$args = array(
			'pretends' => 'teacher',
		);
		$target_user = apply_filters( 'olb_admin_pretending_user', $target_user, $args );

		if($olb->operator->isLoggedIn() && ( $olb->operator->isRoomManager() || !empty( $target_user ) ) ){
			$room_id = ( !empty( $target_user ) ) ? $target_user->data['id'] : $olb->operator->data['id'];
			$records = new olb_room_schedule( $room_id, $perpage);
			if($records->recordmax){
				if($pagenavi){
					echo $records->page_navi($records);
				}
				echo $records->html();
			}
			else {
				echo $content;
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]チケット更新ログの表示: [Short code]Member's ticket update logs
	 */
	public static function show_ticket_logs($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'perpage' => 10,
					'pagenavi' => true,
				),
				$atts
			)
		);

		ob_start();
		$target_user = false;
		$args = array(
			'pretends' => 'user',
		);
		$target_user = apply_filters( 'olb_admin_pretending_user', $target_user, $args );

		if( $olb->operator->isLoggedIn() && ( $olb->operator->isMember() || !empty( $target_user ) ) ) {
			$user_id = ( !empty( $target_user ) ) ? $target_user->data['id'] : $olb->operator->data['id'];
			$records = new olb_logs($user_id, $perpage);
			if($records->recordmax){
				if($pagenavi){
					$format = <<<EOD
<div id="list_pagenavi" class="list_pagenavi">
<div id="prev_page" class="prev_page">%PREV_PAGE%</div>
<div id="next_page" class="next_page">%NEXT_PAGE%</div>
</div>
EOD;
					$search = array(
							'%PREV_PAGE%',
							'%NEXT_PAGE%',
						);
					$text_prev = __('&laquo; PREV', OLBsystem::TEXTDOMAIN);
					$text_next = __('NEXT &raquo;', OLBsystem::TEXTDOMAIN);
					$replace = array(
							$records->getPrevPageLink(-1, $text_prev, $_SERVER['QUERY_STRING']),
							$records->getNextPageLink( 1, $text_next, $_SERVER['QUERY_STRING']),
						);
					echo str_replace($search, $replace, $format);
				}
				echo $records->html();
			}
			else {
				echo $content;
			}
		}
		else {
			if ( empty( $olb->operator->data ) || !$olb->operator->isAdmin()) {
				printf( '<div class="alert alert-error">%s</div>', __('Do not have authority to show this page.', OLBsystem::TEXTDOMAIN) );
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]ログイン中ユーザー情報の表示: [Short code]Show member data
	 */
	public static function showMemberData($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'key' => null,	// key='name'
				),
				$atts
			)
		);
		// Member
		if($olb->operator->isLoggedIn() && $olb->operator->isMember()){
			if(isset($olb->operator->data[$key])){
				return $olb->operator->data[$key];
			}
			if($key=='free'){
				return $olb->operator->canFreeReservation();
			}
		}
		// Admin
		if($olb->operator->isLoggedIn() && $olb->operator->isAdmin()){
			if ( isset( $olb->qs['user_id'] ) ) {
				$user = new olbAuth( $olb->qs['user_id'] );
				if ( !empty( $user->data['id'] ) ) {
					if( isset( $user->data[$key] ) ) {
						return $user->data[$key];
					}
					if( $key=='free'){
						return $user->canFreeReservation();
					}
				}
			}

		}
		return false;
	}

	/** 
	 *	[ショートコード]ログイン中ユーザーの有効期限切れの際の表示: [Short code]Show block at expiration
	 */
	public static function showIfExpire($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
					'not' => null,
				),
				$atts
			)
		);

		ob_start();
		if($olb->operator->isLoggedIn() && $olb->operator->isMember()){
			$now = date('Y-m-d', current_time('timestamp'));
			if(isset($not)){
				if($olb->operator->isNotExpire($now)) {
					echo $content;
				}
			}
			else {
				if(!$olb->operator->isNotExpire($now)) {
					echo $content;
				}
			}
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]ログイン中会員にのみ表示: [Short code]Show block only member
	 */
	public static function showIfMember($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
				),
				$atts
			)
		);

		ob_start();
		if($olb->operator->isLoggedIn() && $olb->operator->isMember()){
			echo $content;
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/** 
	 *	[ショートコード]ログイン中講師にのみ表示: [Short code]Show block only room-manager
	 */
	public static function showIfManager($atts, $content = null){
		global $olb;
		extract(
			shortcode_atts(
				array(
				),
				$atts
			)
		);

		ob_start();
		if($olb->operator->isLoggedIn() && ($olb->operator->isRoomManager() || $olb->operator->isAdmin())){
			echo $content;
		}
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}
?>