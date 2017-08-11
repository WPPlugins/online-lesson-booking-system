<?php
/** 
 *	チケット制: Ticket system
 */
add_filter( 'olb_get_user_data', array( 'olb_ticket', 'get_user_extends' ), 11, 2 );
add_filter( 'olb_added_profile', array( 'olb_ticket', 'extended_fields'), 11, 2 );
add_filter( 'olb_added_profile_admin', array( 'olb_ticket', 'extended_fields_admin'), 11, 2 );
add_action( 'olb_reservation', array( 'olb_ticket', 'reservation' ), 10, 1 );
add_action( 'olb_cancellation', array( 'olb_ticket', 'cancellation' ), 10, 1 );
add_action( 'olb_cancellation_by_teacher', array( 'olb_ticket', 'cancellation' ), 10, 1 );
add_filter( 'olb_update_profile', array( 'olb_ticket', 'update_profile' ), 10, 1  );
add_filter( 'olb_update_term_exception', array( 'olb_ticket', 'update_term_exception' ), 10, 1  );
add_filter( 'olb_can_reservation', array( 'olb_ticket', 'extend_can_reservation' ), 11, 5 );
add_filter( 'olb_error',  array( 'olb_ticket', 'extend_error_message' ), 11, 2 );

add_filter( 'olb_email_values', array( 'olb_ticket', 'extend_email_values' ), 11, 2 );

class olb_ticket {

	/** 
	 *	ユーザーデータの拡張: Extended user data
	 */
	public static function get_user_extends( $userdata, $user ) {
		global $olb;

		$userticket  = get_user_meta( $user->ID, $olb->ticket_metakey, true );
		if ( empty( $userticket ) ) {
			$userticket = 0;
		}
		$extends = array(
			'olbticket'  => $userticket
			);
		$userdata = array_merge( $userdata, $extends );

		return $userdata;
	}

	/** 
	 *	拡張プロフィールの表示: Show the extended profile items ('olbticket')
	 */
	public static function extended_fields( $html, $user ) {
		global $olb;

		if($olb->ticket_system) {
			// 購読者
			if ( in_array( 'subscriber', $user->data['roles'] ) ) {
				$description = __('The possession tickets is updated after the check of payment.', OLBsystem::TEXTDOMAIN);
				$format = <<<EOD
<tr>
<th>%s</th>
<td>%s <span class="description" style="margin-left:20px">(%s)</span></td>
</tr>
EOD;
				$new = sprintf($format, __('Possession tickets', OLBsystem::TEXTDOMAIN), $user->data['olbticket'], $description);
				$html = str_replace( '</table>', $new."\n</table>", $html );
			}
		}
		return $html;
	}

	/** 
	 *	拡張プロフィールの表示(管理者): Show the extended profile items for admin ('olbticket') 
	 */
	public static function extended_fields_admin( $html, $user ) {
		global $olb;

		if($olb->ticket_system) {
			// 購読者のみ
			if(in_array('subscriber', $user->data['roles'])){
				$format = <<<EOD
<tr>
<th><label for="olbticket">%s</label></th>
<td><input type="text" name="olbticket" id="olbticket" value="%s" /> ex. 10
%s</td>
</tr>
EOD;
				$info = '';
				if ( $olb->ticket_system && !empty( $olb->ticket_expire ) ) {
					$info = sprintf( '<br><span class="description">%s</span>', __('If a possession ticket is updated, the term of validity will be automatically updated by the value of a plug-in option "term of validity of a ticket".', OLBsystem::TEXTDOMAIN) );
				}
				$new = sprintf($format, __('Possession tickets', OLBsystem::TEXTDOMAIN), $user->data['olbticket'], $info );
				$html = str_replace( '</table>', $new."\n</table>", $html );
			}
		}
		return $html;
	}

	/**
	 *	保有チケットの更新: Update 'possession tickets'
	 */
	public static function update_profile( $result ) {
		global $olb;

		extract( $result );
		/*
		$result = array(
			'type'    => 'admin',
			'user_id' => $user_id,
			'old'     => 0,
			'new'     => 0,
			'days'    => 0,
		);
		*/

		if($olb->ticket_system) {
			$old = get_user_meta( $user_id, $olb->ticket_metakey, true );
			if ( $_POST['olbticket'] != '' ){
				$new = intval( $_POST['olbticket'] );
				// Check integer
				if ( strval( $new ) == strval( intval( $new ) ) ) {
					if ( $new != $old ) {
						$result['old'] = $old;
						$result['new'] = $new;
					}
				}
			}
			else {
				if ( $new != $old ) {
					$result['old'] = $old;
					$result['new'] = $new;
				}
			}
			if ( $result['old'] != $result['new'] ) {
				update_user_meta( $result['user_id'], $olb->ticket_metakey, $result['new'] );
			}
		}
		return $result;
	}

	/**
	 *	保有チケットの有効期限更新: Update 'term of validity' of tickets
	 */
	public static function update_term_exception( $result ) {
		global $olb;

		/*
		$result = array(
			'type'    => 'admin',
			'user_id' => $user_id,
			'old'     => 0,
			'new'     => 0,
			'days'    => 0,
		);
		*/
		if ( $olb->ticket_system && ( intval( $olb->ticket_expire ) > 0 ) ) {
			if ( $result['new'] > $result['old'] ) {
				$now = current_time('timestamp');
				$term = get_user_meta( $result['user_id'], 'olbterm', true );
				if ( $result['type'] == 'paypal' && $result['ppdays'] > 0 ) {
					$add = $result['ppdays'];
				}
				else {
					$add = $olb->ticket_expire;
				}
				$newtime = $now + $add * 60 * 60 *24;
				$newterm = date( 'Y-m-d', $newtime );
				$days = 0;
				if ( empty( $term ) ) {
					$days = $add;
				}
				else if ( strcmp( $term, $newterm ) < 0 ) {
					list( $y, $m, $d ) = explode( '-', $term );
					$orgtime = mktime( 0, 0, 0, $m, $d, $y );
					$days = intval( ( $newtime - $orgtime ) / ( 24 * 60 * 60 ) );
				}
				$result['days'] = $days;
			}
		}
		return $result;
	}

	/** 
	 *	予約時チケット処理: Calculation of the ticket (in reservation)
	 */
	public static function reservation( $result ) {
		global $olb;
		/*
		$result = Array (
			[code] => NOT_RESERVED
			[record] => Array (
					[id] => 1234
					[date] => 2014-03-03
					[time] => 16:00:00
					[room_id] => 2
					[user_id] => 3
					[free] => 0
					[absent] => 0
				)
			[user] => olbAuth Object (
					[data] => Array (
							[id] => 3
							[loginname] => user03
							[email] => 03@example.com
							[firstname] => Jane
							[lastname] => Smith
							[name] => Jane Smith
							[roles] => Array (
									[0] => subscriber
								)
							[address] => xxxx xxxx
							[phone] => xxxx xxxx
							[skype] => janesmith
							[olbgroup] => 
							[olbterm] => 2015-03-03
							[olbticket] => 1000
						)
					[loggedin] => 1
				)
			[room] => Array (
					[id] => 2
					[nicename] => teacher02
					[name] => John Doe
					[status] => 0
					[email] => 02@example.com
					[url] => http://example.com/john-doe/
					[olbgroup] => teacher
					[olbcost] => 0
				)
			)
		*/
		extract( $result );	// $code, $record, $user, $room

		if ( $olb->ticket_system ) {
			if(!$record['free']) {
				$tickets = $user->data['olbticket'] - 1;
				update_user_meta($user->data['id'], $olb->ticket_metakey, $tickets );
			}
		}
		return;
	}

	/** 
	 *	キャンセル時チケット処理: Calculation of the ticket (in cancellation)
	 */
	public static function cancellation( $result ) {
		global $olb;

		extract( $result );	// $code, $record, $user, $room

		if ( $olb->ticket_system ) {
			if(!$record['free']) {
				$tickets = $user->data['olbticket'] + 1;
				update_user_meta($user->data['id'], $olb->ticket_metakey, $tickets );
			}
		}
		return;
	}

	/** 
	 *	会員による予約の可否判定の拡張: Extended judgment conditions for reservation 
	 */
	public static function extend_can_reservation( $result, $room_id, $user_id, $date, $time ) {
		global $olb;

		$user = $result['user'];
		$room = $result['room'];
		/*
		olbAuth Object $user (
			[data] => Array (
					[id] => 3
					[loginname] => user03
					[email] => 03@example.com
					[firstname] => Jane
					[lastname] => Smith
					[name] => Jane Smith
					[roles] => Array (
							[0] => subscriber
						)
					[address] => 
					[phone] => 
					[skype] => janesmith
					[olbgroup] => 
					[olbterm] => 2015-03-03
					[olbpoint] => 1000
				)
			[loggedin] => 1
		)

		Array $room (
			[id] => 2
			[nicename] => teacher02
			[name] => John Doe
			[status] => 0
			[email] => 02@example.com
			[url] => http://example.com/john-doe/
			[olbgroup] => teacher
			[olbcost] => 100
		)
		*/
		// 予約可能であれば再判定
		if ( $result['code'] == 'NOT_RESERVED' ) {
			// 無料予約数残なし
			if ( !$user->canFreeReservation() ) {
				// チケットシステム有効＋保有チケットなし
				if( $olb->ticket_system && empty( $user->data['olbticket'] ) ) {
					$result['code'] = 'USERTICKET_EMPTY';
				}
				else {

				}
			}
		}
		return $result;
	}

	/** 
	 *	エラーメッセージの拡張: Custom error message
	 */
	public static function extend_error_message( $information, $code ) {
		if ( $information === false ) {
			switch( $code ) {
			case 'USERTICKET_EMPTY':
				return __( 'Your possession tickets is empty. ', OLBsystem::TEXTDOMAIN );

			default:
				return false;
			}
		}
		else {
			return $information;
		}
	}

	/**
	 *	予約・キャンセル通知メールの拡張: Extend the variable in reservation mail
	 */
	public static function extend_email_values( $args, $result ) {
		global $olb;

		list( $search, $replace ) = $args;
		if ( $olb->ticket_system ) {
			$user = new olbAuth( $result['user']->data['id'] );
			array_push( $search, '%USER_TICKETS%' );
			array_push( $replace, $user->data['olbticket'] );
		}
		return array( $search, $replace );
	}
}
