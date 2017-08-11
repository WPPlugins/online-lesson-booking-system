<?php
/** 
 *	ユーザー情報: User info 
 */
add_filter( 'olb_get_user_data', array( 'olbAuth', 'get_user_data' ), 10, 2 );
add_action( 'init', array( 'olbAuth', 'init' ), 10 );

class olbAuth {

	public $data = array();
	public $loggedin = null;

	/** 
	 *	CONSTRUCT
	 */
	public function __construct($user_id = null) {
		global $current_user;

		$currentuser = false;
		if(!empty($current_user->ID)) {
			$currentuser = self::getUser($current_user->ID);
		}

		if(empty($user_id)){
			// current user
			if(!empty($currentuser)){
				$this->data = $currentuser;
				$this->loggedin = true;
			}
		}
		else {
			if($user_id == $currentuser['id']) {
				$this->data = $currentuser;
				$this->loggedin = true;
			}
			else {
				$this->data = self::getUser($user_id);
				if(!empty($this->data)){
					$this->loggedin = false;
				}
			}
		}
	}

	/**
	 *	Init
	 */
	public static function init() {
		add_filter( 'olb_is_not_expire', array( 'olbAuth', 'check_is_not_expire' ), 10 );
	}

	/** 
	 *	ログイン状態の検査: Log-in inspection
	 */
	public function isLoggedIn(){
		if($this->loggedin){
			return true;
		}
		return false;
	}

	/**  
	 *	特定ユーザーの情報を取得: Get user info
	 */
	public static function getUser($user_id){
		$args = array(
			'include' => array($user_id),
			);
		$user_query = new WP_User_Query($args);
		list($user) = $user_query->results;
		/*
		WP_User Object(
		    [data] => stdClass Object(
		            [ID] => 2
		            [user_login] => user02
		            [user_pass] => **********************************
		            [user_nicename] => user02
		            [user_email] => hoge@example.com
		            [user_url] => 
		            [user_registered] => 2013-01-01 00:00:00
		            [user_activation_key] => 
		            [user_status] => 0
		            [display_name] => user02
		        )
		    [ID] => 2
		    [caps] => Array(
		            [subscriber] => 1
		        )
		    [cap_key] => a_wp_capabilities
		    [roles] => Array(
		            [0] => subscriber
		        )
		    [allcaps] => Array(
		            [read] => 1
		            [level_0] => 1
		            [subscriber] => 1
		        )
		    [filter] => 
		)
		*/
		$userdata = array();
		if(!empty($user->ID)) {
			$userdata = apply_filters( 'olb_get_user_data', $userdata, $user );
		}
		return $userdata;
	}
 
	/** 
	 *	特定ユーザーの拡張情報を取得: Get user data
	 */
	public static function get_user_data( $userdata, $user ) {
		global $olb;

		$userdata = array(
			'id'        => $user->ID,
			'loginname' => $user->user_login,
			'email'     => $user->user_email,
			'firstname' => $user->user_firstname,
			'lastname'  => $user->user_lastname,
			'name'      => $user->display_name,
			'roles'     => $user->roles,
			'address'   => get_user_meta( $user->ID, 'user_address', true ),
			'phone'     => get_user_meta( $user->ID, 'user_phone', true ),
			'skype'     => get_user_meta( $user->ID, 'user_skype', true ),
			'olbgroup'  => get_user_meta( $user->ID, 'olbgroup', true ),
			'olbterm'   => get_user_meta( $user->ID, 'olbterm', true ),
		);
		return $userdata;
	}

	/**  
	 *	特定ユーザーの情報をhtmlで取得: Get user info (html)
	 */
	public static function htmlUser($userdata){
		$options = OLBsystem::getPluginOptions( 'settings' );
		$term = ( empty( $options['indefinite'] ) ) ? '<tr><th>%LABEL_TERM%</th><td>%USER_TERM%</td></tr>'."\n" : '';
		$format = <<<EOD
<table id="members_info" class="members_info">
<tr><th>%LABEL_ID%</th><td>%USER_ID%</td></tr>
<tr><th>%LABEL_NAME%</td><td>%USER_NAME%</td></tr>
<tr><th>%LABEL_SKYPE%</th><td>%USER_SKYPE%</td></tr>
{$term}<tr><th>%LABEL_EMAIL%</th><td>%USER_EMAIL%</td></tr>
<tr><th>%LABEL_FIRSTNAME%</th><td>%USER_FIRSTNAME%</td></tr>
<tr><th>%LABEL_LASTNAME%</th><td>%USER_LASTNAME%</td></tr>
<tr><th>%LABEL_ADDRESS%</th><td>%USER_ADDRESS%</td></tr>
<tr><th>%LABEL_PHONE%</th><td>%USER_PHONE%</td></tr>
</table>
EOD;
		$search = array(
			'%LABEL_ID%',        '%USER_ID%',
			'%LABEL_NAME%',      '%USER_NAME%',
			'%LABEL_SKYPE%',     '%USER_SKYPE%',
			'%LABEL_TERM%',     '%USER_TERM%',
			'%LABEL_EMAIL%',     '%USER_EMAIL%',
			'%LABEL_FIRSTNAME%', '%USER_FIRSTNAME%',
			'%LABEL_LASTNAME%',  '%USER_LASTNAME%',
			'%LABEL_ADDRESS%',   '%USER_ADDRESS%',
			'%LABEL_PHONE%',     '%USER_PHONE%',
		);
		$replace = array(
			__('ID', OLBsystem::TEXTDOMAIN), $userdata['id'],
			__('Name', OLBsystem::TEXTDOMAIN), $userdata['name'],
			__('Skype ID', OLBsystem::TEXTDOMAIN), $userdata['skype'],
			__('Term', OLBsystem::TEXTDOMAIN), $userdata['olbterm'],
			__('Email', OLBsystem::TEXTDOMAIN), $userdata['email'],
			__('First name', OLBsystem::TEXTDOMAIN), $userdata['firstname'],
			__('Last name', OLBsystem::TEXTDOMAIN), $userdata['lastname'],
			__('Address', OLBsystem::TEXTDOMAIN), $userdata['address'],
			__('Phone', OLBsystem::TEXTDOMAIN), $userdata['phone'],
		);
		$html = str_replace($search, $replace, $format);
		return $html;
	}

	/** 
	 *	全ユーザー情報を取得: Get all users info
	 */
	public static function getAll(){
		$args = array(
				'role' => 'subscriber',
			);

		$users = array();
		$userlist = get_users( $args );
		foreach( $userlist as $user ) {
			$userdata = array();
			$users[] = apply_filters( 'olb_get_user_data', $userdata, $user );
		}
		return $users;
	}

	/** 
	 *	管理者の検査: Administrator inspection 
	 */
	public function isAdmin(){
		if(in_array('administrator', $this->data['roles'])){
			return true;
		}
		return false;
	}

	/** 
	 *	講師の検査: Room manager inspection 
	 */
	public function isRoomManager(){
		if(in_array('author', $this->data['roles']) && $this->data['olbgroup']=='teacher'){
			return true;
		}
		return false;
	}

	/** 
	 *	会員の検査: Member inspection 
	 */
	public function isMember(){
		if(in_array('subscriber', $this->data['roles']) && !self::isRoomManager()){
			return true;
		}
		return false;
	}

	/** 
	 *	会員の有効期限の検査: Member term of validity inspection 
	 */
	public function isNotExpire($date){
		$args = array(
			'date' => $date,
			'user' => $this,
			'result' => false
			);
		$args = apply_filters( 'olb_is_not_expire', $args );
		return $args['result'];
	}
	public static function check_is_not_expire( $args ) {
		$user = $args['user'];
		$date = $args['date'];

		$options = OLBsystem::getPluginOptions( 'settings' );
		if ( !empty( $options['indefinite'] ) ) {
			$args['result'] = true;
		}
		else if ( $user->isMember() && !empty( $user->data['olbterm'] ) && $user->data['olbterm']>=$date ) {
			$args['result'] = true;
		}
		return $args;
	}

	/** 
	 *	無料予約の可否: Propriety of free reservation  
	 */
	public function canFreeReservation(){
		global $wpdb, $olb;

		if(self::isMember() && $olb->free > 0) {
			$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
			$query = 'SELECT COUNT(*) as count FROM '.$prefix.'history WHERE `user_id`=%d AND `free`=%d';
			$ret = $wpdb->get_row($wpdb->prepare($query, array($this->data['id'], 1)), ARRAY_A);
			$free = $olb->free - $ret['count'];
			return ( $free < 0 ) ? 0 : $free ;
		}
		return 0;
	}

}
?>
