<?php
/** 
 *	講師情報: Room info (as teacher)
 */
add_filter( 'olb_get_room_data', array( 'olbRoom', 'get_room_data' ), 10, 2 );
add_filter( 'olb_get_portrait', array( 'olbRoom', 'get_portrait' ), 10, 2 );

class olbRoom extends olbPaging {

	/** 
	 *	CONSTRUCT
	 */
	public function __construct($limit) {
		$this->limit = $limit;			// スケジュールページの表示講師数(1ページ当たり)
		$this->recordmax = self::recordMax();				// 有効な講座数
		$this->pagemax = ceil($this->recordmax/$this->limit);	// ページ数

		self::getCurrentPage();
	}

	/** 
	 *	講師ID指定で講師情報を取得: Get room-info by room_id
	 */
	public static function get($room_id) {
		global $wpdb;

		$args = array(
				'include' => array($room_id),
				'meta_key' => 'olbgroup',
				'meta_value' => 'teacher',
				'meta_compare' => '=',
				'number' => 1,
			);

		list( $room ) = get_users( $args );
		$roomdata = array();
		if ( !empty( $room->ID ) ) {
			$roomdata = apply_filters( 'olb_get_room_data', $roomdata, $room );
		}
		return $roomdata;
	}

	/** 
	 *	講師データを取得: Get room data
	 */
	public static function get_room_data( $roomdata, $room ) {
		$roomdata = array(
				'id'       => $room->data->ID,
				'nicename' => $room->data->user_nicename,
				'name'     => $room->data->display_name,
				'status'   => $room->data->user_status,
				'email'    => $room->data->user_email,
				'url'      => $room->data->user_url,
				'olbprofile'  => get_user_meta( $room->data->ID, 'olbprofile', true ), 
				'olbgroup' => get_user_meta( $room->data->ID, 'olbgroup', true ),
			);
		return $roomdata;
	}

	/**
	 *	講師自画像を取得: Get portrait of room
	 */
	public static function get_portrait( $portrait, $room ) {
		$p = get_the_post_thumbnail( $room['olbprofile'], 'thumbnail' );
		$portrait =  ( !empty( $p ) ) ? sprintf( '<div class="portrait"><a href="%s">%s</a></div>', $room['url'], $p ) : '';
		return $portrait;
	}

	/** 
	 *	講師一覧情報を取得: Get list of room
	 */
	public function getList(){
		global $wpdb;

		$args = array(
				'meta_key' => 'olbgroup',
				'meta_value' => 'teacher',
				'meta_compare' => '=',
			);
		if($this->limit!=0) {
			if($this->offset!=0) {
				$args['offset'] = $this->offset;
				$args['number'] = $this->limit;
			}
			else {
				$args['number'] = $this->limit;
			}
		}
		$rooms = array();
		$roomlist = get_users( $args );
		foreach( $roomlist as $room ) {
			$roomdata = array();
			$rooms[] = apply_filters( 'olb_get_room_data', $roomdata, $room );
		}
		return $rooms;
	}

	/** 
	 *	全講師情報を取得: Get all rooms info
	 */
	public static function getAll(){
		$args = array(
				'meta_key' => 'olbgroup',
				'meta_value' => 'teacher',
				'meta_compare' => '=',
			);

		$rooms = array();
		$roomlist = get_users( $args );
		foreach( $roomlist as $room ) {
			$roomdata = array();
			$rooms[] = apply_filters( 'olb_get_room_data', $roomdata, $room );
		}
		return $rooms;
	}

	/** 
	 *	講師数を取得: Get count of rooms
	 */
	public function recordMax(){
		global $wpdb;

		$args = array();
		$prefix = $wpdb->prefix;
		$query = "SELECT COUNT(*) as count FROM ".$prefix."users as u "
				."INNER JOIN ".$prefix."usermeta as um "
				."WHERE um.meta_key='olbgroup' AND um.meta_value='teacher' AND um.user_id=u.ID ";
		$ret = $wpdb->get_row( $query, ARRAY_A);
		return $ret['count'];
	}


}
?>
