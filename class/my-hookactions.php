<?php
/** 
 *	管理画面: WP Hook Actions
 */
add_action( 'plugins_loaded', array('olbHookAction', 'moReload' ) );

class olbHookAction {
	/**
	 *	MOファイルをロード: load textdomain
	 */
	public static function moReload(){
//		if ( get_locale() != WPLANG ) {
//			if ( is_textdomain_loaded( OLBsystem::TEXTDOMAIN ) ) {
				unload_textdomain( OLBsystem::TEXTDOMAIN );
//			}
			load_plugin_textdomain( OLBsystem::TEXTDOMAIN, false, dirname(dirname(plugin_basename(__FILE__))).'/languages');
//		}
	}
	
	/** 
	 *	管理バーの項目を非表示: Hide menu in admin-bar (for member)
	 */
	public static function hideAdminBarMenu($wp_admin_bar){
		$wp_admin_bar->remove_menu('wp-logo');
		$wp_admin_bar->remove_menu('my-account');
	}
	public static function hideAdminHeadMenu(){
		echo <<<EOD
<style type="text/css">
#contextual-help-link-wrap { display: none; }
#footer-upgrade { display: none; }
</style>
EOD;
	}
	public static function addAdminBarMenu(){
		global $wp_admin_bar, $olb;
		$wp_admin_bar->add_menu(
			array(
				'id' => 'olb_mypage',
				'title' => __('My page', OLBsystem::TEXTDOMAIN),
				'href' => get_permalink(get_page_by_path($olb->member_page)->ID)
			)
		);
		$wp_admin_bar->add_menu(
			array(
				'id' => 'olb_logout',
				'title' => __('Logout', OLBsystem::TEXTDOMAIN),
				'href' => wp_logout_url()
			)
		);
	}
	public static function hideAdminFooter(){
		echo '';
	}
	public static function hideDashboard(){
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);		// 現在の状況
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);	// 最近のコメント
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);	// 被リンク
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);			// プラグイン
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);		// クイック投稿
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);		// 最近の下書き
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);			// WordPressブログ
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);			// WordPressフォーラム
	}
	public static function hideSideMenu(){
		global $menu;
		unset($menu[2]);	// ダッシュボード
		unset($menu[4]);	// 区切り線
		unset($menu[5]);	// 投稿
		unset($menu[10]);	// メディア
		unset($menu[15]);	// リンク
		unset($menu[20]);	// ページ
		unset($menu[25]);	// コメント
		unset($menu[59]);	// 区切り線
		unset($menu[60]);	// テーマ
		unset($menu[65]);	// プラグイン
	//	unset($menu[70]);	// プロフィール
		unset($menu[75]);	// ツール
		unset($menu[80]);	// 設定
		unset($menu[90]);	// 区切り線
	}

	/** 
	 *	プロフィール項目の非表示: Hide profile items
	 */
	public static function hideProfileItem(){
		$version = get_bloginfo('version');

		if(substr($version, 0, 3) < '3.6') {
			$settings = array(
				'table:nth-of-type(1){display:none;}',						// 個人設定: private setting method
				'table:nth-of-type(3) tr:nth-child(2){display:none;}',		// 	ウェブサイト: website
				'table:nth-of-type(3) tr:nth-child(3){display:none;}',		// 	AIM
				'table:nth-of-type(3) tr:nth-child(4){display:none;}',		// 	Yahoo ID
				'table:nth-of-type(3) tr:nth-child(5){display:none;}',		// 	Jabber / Google Talk
			//	'table:nth-of-type(3) tr:nth-child(6){display:none;}',		// 	ほか(1) 住所: address
			//	'table:nth-of-type(3) tr:nth-child(7){display:none;}',		// 	ほか(2) 電話番号: phone
			//	'table:nth-of-type(3) tr:nth-child(8){display:none;}',		// 	ほか(3) スカイプID: skype ID

				'table:nth-of-type(4) tr:nth-child(1){display:none;}',		// 	プロフィール: profile
				);
		}
		else {
			$settings = array(
				'table:nth-of-type(1){display:none;}',						// 個人設定: private setting method
				'table:nth-of-type(3) tr:nth-child(2){display:none;}',		// 	ウェブサイト: website
				'table:nth-of-type(4) tr:nth-child(1){display:none;}',		// 	プロフィール: profile
				);
		}

		$css = '';
		foreach($settings as $c){
			$css .= $c."\n";
		}
		echo <<<EOD
<style type="text/css">
{$css}
</style>
EOD;
	}

	/** 
	 *	プロフィール項目の追加: Add profile items for contact
	 */
	public static function addProfileContact($meta) {
		//項目の追加
		$meta['user_address'] = __('Address', OLBsystem::TEXTDOMAIN);
		$meta['user_phone'] = __('Phone', OLBsystem::TEXTDOMAIN);
		$meta['user_skype'] = __('Skype ID', OLBsystem::TEXTDOMAIN);
		return $meta;
	}

	/** 
	 *	プロフィール追加項目の表示: Show additional fields of profile (for Member)
	 */
	public static function showAddedProfile(){
		$user = new olbAuth();
		$html = '';
		echo apply_filters( 'olb_added_profile', $html, $user );
	}

	/** 
	 *	プロフィール追加項目のHTML: HTML code of additional fields
	 */
	public static function additional_fields( $html, $user ){
		global $olb;

		$title = __('Online-Booking-System Additional Fields', OLBsystem::TEXTDOMAIN);
		$format = <<<EOD
<h3>%s</h3>
<table class="form-table">
EOD;
		$html = sprintf( $format, $title );

		$options = OLBsystem::getPluginOptions( 'settings' );
		// 購読者
		if ( in_array( 'subscriber', $user->data['roles'] ) ) {
			$description = __('The term of validity is updated after the check of payment.', OLBsystem::TEXTDOMAIN);
			$format = <<<EOD
<tr>
<th>%s</th>
<td>%s <span class="description" style="margin-left:20px">(%s)</span></td>
</tr>
EOD;
			if ( empty( $options['indefinite'] ) ) {
				$html .= sprintf( $format, __('Term of validity', OLBsystem::TEXTDOMAIN), $user->data['olbterm'], $description );
			}
		}
		// 投稿者
		if ( in_array( 'author', $user->data['roles'] ) ) {
			$format = <<<EOD
<tr>
<th>%s</th>
<td>%s</td>
</tr>
EOD;
			$value = ( $user->data['olbgroup'] ) ? __('Teacher', OLBsystem::TEXTDOMAIN) : '('.__('Not set', OLBsystem::TEXTDOMAIN).')';
			$html .= sprintf( $format, __('Property "Teacher"', OLBsystem::TEXTDOMAIN), $value );
		}
		$html .= "</table>\n";
		return $html;
	}

	/** 
	 *	プロフィール項目の追加(管理者用): Show additional fields of profile (for Admin)
	 */
	public static function addProfileMeta(){

		if (empty($_POST['user_id'])) {
			$user_id = $_GET['user_id'];
		}
		else {
			$user_id = $_POST['user_id'];
		}
		$user = new olbAuth($user_id);
		$html = '';
		echo apply_filters( 'olb_added_profile_admin', $html, $user );
		return;
	}

	/** 
	 *	プロフィール追加項目のHTML(管理者用): HTML code of additional fields (for Admin)
	 */
	public static function additional_fields_admin( $html, $user ){
		global $olb;
		$options = OLBsystem::getPluginOptions( 'settings' );

		$title = __('Online-Booking-System Additional Fields', OLBsystem::TEXTDOMAIN);
		$html = sprintf( "<h3>%s</h3>\n", $title );

		// 投稿者のみ
		if(in_array('author', $user->data['roles'])){
			$checked = '';
			if($user->isRoomManager()){
				$checked = 'checked="checked"';
			}
			$format = <<<EOD
<table class="form-table olb_admin_profile_fields">
<tr>
<th>%s</th>
<td><label for="olbgroup"><input type="checkbox" name="olbgroup" id="olbgroup" value="teacher" %s/> %s</label>
<p><span class="description">%s</span></p></td>
</tr>
EOD;
			$html .= sprintf($format, __('Property "Teacher"', OLBsystem::TEXTDOMAIN), $checked, __('Teacher', OLBsystem::TEXTDOMAIN), __( 'When this item is checked, the "Role" is changed to "Author".', OLBsystem::TEXTDOMAIN ) );
		}

		// 購読者のみ
		if(in_array('subscriber', $user->data['roles'])){
			$class = array();
			if ( !empty( $options['indefinite'] ) ) {
				$class[] = 'olb_indefinite';
			}
			$format = <<<EOD
<table class="form-table olb_admin_profile_fields">
<tr class="%s">
<th><label for="olbterm">%s</label></th>
<td><input type="text" name="olbterm" id="olbterm" value="%s" /> ex. %s</td>
</tr>
EOD;
			$html .= sprintf($format, implode( ' ', $class ), __('Term of validity', OLBsystem::TEXTDOMAIN), $user->data['olbterm'], date( 'Y-m-d', current_time('timestamp')+60*60*24*30 ) );
		}
		$html .= "</table>\n";
		return $html;
	}

	/**
	 *	Extra fields in "Add New User"
	 */
	public static function ex_newuser_fields( $user ) {
		$html = '';
		$html = apply_filters( 'olb_ex_newuser_profile', $html, $user );
		echo $html;
	}

	/**
	 *	Extra profile in "Add New User"
	 */
	public static function ex_newuser_profile( $html, $user ) {
		global $pagenow;

		if ( in_array( $pagenow, array( 'user-new.php' ) ) ) {
			$checked = ( isset( $_POST['olbgroup'] ) ) ? 'checked' : '';
			$extra_fields = <<<EOD
<h3 id="%TITLE_ID%" class="title">%TITLE%</h3>
<table id="%TABLE_ID%" class="form-table %TITLE_ID%">
	<tr>
		<th scope="row">%TEACHER_LABEL%</th>
		<td>
			<label for="olbgroup"><input type="checkbox" name="olbgroup" id="olbgroup" value="teacher" %TEACHER_CHECKED% /> %TEACHER_TEXT%</label>
			<p><span class="description">%TEACHER_DESCRIPTION%</span></p>
		</td>
	</tr>
</table>
EOD;
			$search = array(
				'%TITLE_ID%',
				'%TITLE%',
				'%TABLE_ID%',
				'%TEACHER_LABEL%',
				'%TEACHER_CHECKED%',
				'%TEACHER_TEXT%',
				'%TEACHER_DESCRIPTION%',
			);
			$replace = array(
				'olb_admin_profile_fields_title',
				__( 'Online-Booking-System Additional Fields', OLBsystem::TEXTDOMAIN ),
				'olb_admin_profile_fields',
				__( 'Property "Teacher"', OLBsystem::TEXTDOMAIN ),
				$checked,
				__( 'Teacher', OLBsystem::TEXTDOMAIN ),
				__( 'When this item is checked, the "Role" is changed to "Author".', OLBsystem::TEXTDOMAIN ),
			);
			$extra_fields = str_replace( $search, $replace, $extra_fields );
			$html .= $extra_fields;
		}
		return $html;
	}

	/**
	 *	Save extra fields in "user new"
	 */
	public static function save_ex_newuser_fields( $user_id ) {

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		if ( isset( $_POST['olbgroup'] ) ) {
			update_user_meta( $user_id, 'olbgroup', $_POST['olbgroup'] );
		}
	}

	/**
	 *	Load script in "user new"
	 */
	public static function ex_script() {
		$screen = get_current_screen();
		if ( $screen->id == 'user-edit' || ( $screen->id == 'user' && $screen->action = 'add' ) ) {
?>
<script type="text/javascript">
jQuery( document ).ready( function( $ ) {
	$( document ).on( 'change', 'input#olbgroup', function() {
		if ( $( this ).prop('checked') ) {
			if ( $( 'select[name="role"]' ).val() != 'author' ) {
				$( 'select[name="role"]' ).val( 'author' );
			}
		}
	});
	$( document ).on( 'change', 'select[name="role"]', function() {
		if ( $( this ).val() != 'author' ) {
			if ( $( 'input#olbgroup' ).prop( 'checked' ) ) {
				$( 'input#olbgroup' ).prop( 'checked', false );
			}
		}
	});
});
</script>
<?php
		}
	}

	/** 
	 *	ツールバー非表示: Hide admin tool bar
	 */
	public static function inUserRegister($user_id){
		// ツールバー非表示: Hide admin bar
		update_user_meta($user_id, "show_admin_bar_front", 'false');
	}

	/** 
	 *	プロフィール追加項目の保存: Save added items of profile
	 */
	public static function inUpdateProfile( $user_id, $old_user_data ){
		global $olb;

		if ( $olb->operator->isLoggedIn() && $olb->operator->isAdmin() ) {
			// 講師
			$oldgroup = get_user_meta($user_id, 'olbgroup', true);
			if (!empty($_POST['olbgroup'])){
				update_user_meta($user_id, 'olbgroup', $_POST['olbgroup']);
			}
			else {
				delete_user_meta($user_id, 'olbgroup', '');
			}
			$newgroup = get_user_meta($user_id, 'olbgroup', true);
			if($oldgroup != $newgroup){
				$result = array( 'user_id'=>$user_id, 'old'=>$oldgroup, 'new'=>$newgroup );
				$result = apply_filters('olb_update_profile_group', $result );
			}

			// process payment
			$result = array(
				'type'    => 'admin',
				'user_id' => $user_id,
				'old'     => 0,
				'new'     => 0,
				'days'    => 0,
			 );

			// UPDATE TERM (of validity)
			$days = 0;
			$oldterm = get_user_meta( $user_id, 'olbterm', true );
			if ( empty( $oldterm ) ) {
				$oldterm = date( 'Y-m-d', current_time('timestamp') );
			}
			list( $oy, $om, $od ) = explode( '-', $oldterm );
			$om = intval( $om );
			$od = intval( $od );
			if (!empty($_POST['olbterm'])){
				$newterm = str_replace( '/', '-', $_POST['olbterm'] );
				if( preg_match('/^([2-9][0-9]{3})-(0[1-9]{1}|1[0-2]{1})-(0[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})$/', $newterm ) ) {
					list( $ny, $nm, $nd ) = explode( '-', $newterm );
					$nm = intval( $nm );
					$nd = intval( $nd );
					$days = ( mktime( 0, 0, 0, $nm, $nd, $ny ) - mktime( 0, 0, 0, $om, $od, $oy ) ) / ( 60 * 60 * 24 );
					if ( $days != 0 ) {
						$result['days'] = $days;
					}
				}
			}
			else {
				$result['oldterm'] = get_user_meta( $user_id, 'olbterm', true );
				$result['newterm'] = '';
				delete_user_meta($user_id, 'olbterm', '');
			}

			$result = apply_filters( 'olb_update_profile', $result );
			$result = apply_filters( 'olb_update_term', $result );
			$result = apply_filters( 'olb_update_log', $result );
		}
	}

	/**
	 *	保有チケットの更新: Update 'possession tickets'
	 */
	public static function update_ticket( $result ) {
		global $olb;

		if ( $result['old'] != $result['new'] ) {
			update_user_meta( $result['user_id'], $olb->ticket_metakey, $result['new'] );
		}
		return $result;
	}

	/**
	 *	有効期限の更新: Update 'term of validity'
	 */
	public static function update_term( $result ) {
		global $olb;

		$days = 0;
		if ( !empty( $result['days'] ) ) {
			$days = intval( $result['days'] );
		}
		// ex. Using 'ticket system' and 'auto update expire'
		else {
			$result = apply_filters( 'olb_update_term_exception', $result );
			$days = $result['days'];
		}

		if ( $days ) {
			$now = current_time('timestamp');
			$term = $oldterm = get_user_meta( $result['user_id'], 'olbterm', true );
			// Start date
			// When updating the point, it starts from today.
			if ( $result['old'] != $result['new'] ) {
				if ( $result['type'] == 'admin' ) {
					if ( empty( $days ) || empty( $term ) ) {
						$term = date( 'Y-m-d', $now );
					}
				}
				else {
					if ( empty( $term ) || strcmp( $term, date( 'Y-m-d', $now ) ) < 0) {
						$term = date( 'Y-m-d', $now );
					}
				}
			}
			else if ( empty( $term ) || ( strcmp( $term, date( 'Y-m-d', $now ) ) < 0 && $result['type'] != 'admin' ) ){
				$term = date( 'Y-m-d', $now );
			}
			list( $y, $m, $d ) = explode( '-', $term );

			$newterm = date( 'Y-m-d', mktime( 0, 0, 0, $m, $d + $days, $y ) );
			update_user_meta( $result['user_id'], 'olbterm', $newterm );
			$result['oldterm'] = $oldterm;
			$result['newterm'] = $newterm;
			$result['days'] = $days;
		}
		return $result;
	}

	/*
	 *	チケット更新ログ: Update logs (Tickets)
	 */
	public static function update_log( $result ) {
		global $wpdb, $olb;

		if ( ( $result['old'] != $result['new'] ) || ( $result['oldterm'] != $result['newterm'] ) ) {
			$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
			$table = $prefix."logs";
			$increment = $result['new'] - $result['old'];
			$now = current_time('timestamp');
			$ret = $wpdb->insert(
				$table,
				array(
					'uid'       => $result['user_id'],
					'type'      => $result['type'],
					'data'      => serialize( $result ),
					'points'    => $increment,
					'timestamp' => $now
				)
			);
		}
		return $result;
	}

	/** 
	 *	プロフィール追加項目の削除: Delete added items of profile (when delete user)
	 */
	public static function inDeleteUser($user_id){
		global $olb;

		delete_user_meta($user_id, 'olbgroup');
		delete_user_meta($user_id, 'olbterm');
		delete_user_meta($user_id, $olb->ticket_metakey );
	}

	/** 
	 *	ログイン後のリダイレクト: Redirect after login
	 */
	public static function redirectAfterLogin($user_login, $current_user){
		global $olb;

		$parse = parse_url( $_SERVER['HTTP_REFERER'] );
		if ( isset( $parse['query'] ) ) {
			parse_str( $parse['query'], $query );
		}
		$user = new olbAuth($current_user->ID);
		if($user->isMember()) {
			if ( isset( $query['redirect_to'] ) ) {
				header('Location: '.$query['redirect_to']);
				exit;
			}
			header('Location: '.get_permalink(get_page_by_path($olb->member_page)->ID));
			exit;
		}
		else if($user->isRoomManager()){
			if ( isset( $query['redirect_to'] ) ) {
				header('Location: '.$query['redirect_to']);
				exit;
			}
			header('Location: '.get_permalink(get_page_by_path($olb->edit_schedule_page)->ID));
			exit;
		}
		else if( $user->isAdmin() ) {
			if ( isset( $query['redirect_to'] ) ) {
				header('Location: '.$query['redirect_to']);
				exit;
			}
		}
	}

	/** 
	 *	ログアウト後のリダイレクト: Redirect after logout
	 */
	public static function redirectAfterLogout(){

		header('Location: '.get_option('siteurl'));
		exit;
	}

	/** 
	 *	各ページへのアクセス制限: Control for access to  special page
	 */
	public static function inSpecialPageAccess() {
		global $olb, $wpdb, $post;

		$current_post = $post;
		$user = new olbAuth();

		$redirect_info = array();
		if ( !$user->isLoggedIn() ) {
			if ( !empty( $_SERVER['REDIRECT_URL'] ) ) {
				$redirect_url = ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' )
							   .$_SERVER['HTTP_HOST']
							   .$_SERVER['REDIRECT_URL'];
				if ( !empty( $_SERVER['REDIRECT_QUERY_STRING'] ) ) {
					$redirect_url .= ( strstr( $redirect_url, '?' ) ) ? '&' : '?';
					$redirect_url .= $_SERVER['REDIRECT_QUERY_STRING'];
				}
				$redirect_url = urlencode( $redirect_url );
				$redirect_info = array(
					'redirect_to='.$redirect_url
					);
			}
		}

		// 先祖postのID
		$post_ancestors = get_post_ancestors( $current_post->ID );
		$ancestor_id = array_pop( $post_ancestors );
		$ancestor = ( $ancestor_id ) ? get_post($ancestor_id) : $current_post;

		// 会員ページ(ログイン中のみ): Member-page(only login member)
		if($ancestor->post_name==$olb->member_page){
			if(!$user->isLoggedIn()){
				if(empty($olb->login_page)) {
					$url = wp_login_url();
				}
				else {
					$url = get_permalink(get_page_by_path($olb->login_page)->ID);
				}
				if ( !empty( $redirect_info ) ) {
					$url .= ( strstr( $url, '?' ) ) ? '&' : '?';
					$url = $url.implode( '&', $redirect_info );
				}
				header('Location: '.$url);
				exit;
			}
			if(!$user->isAdmin() && !$user->isMember()){
				header('Location: '.$olb->home);
				exit;
			}
			// Admin
			if ( $user->isAdmin() ) {
				self::admin_pretending_switch( 'user', $current_post, $ancestor );
			}
		}

		// 予約ページ(ログイン中のみ): Reservation-page(only login member)
		if($ancestor->post_name==$olb->reserve_form_page){
			if(!$user->isLoggedIn()){
				if(empty($olb->login_page)) {
					$url = wp_login_url();
				}
				else {
					$url = get_permalink(get_page_by_path($olb->login_page)->ID);
				}
				if ( !empty( $redirect_info ) ) {
					$url .= ( strstr( $url, '?' ) ) ? '&' : '?';
					$url = $url.implode( '&', $redirect_info );
				}
				header( 'Location: '.$url );
				exit;
			}
			if(!$user->isMember()){
				if ( isset( $olb->qs['t'] ) && isset( $olb->qs['room_id'] ) ) {
					list( $date, $time ) = olbTimetable::parseTimetableKey( $olb->qs['t'] );
					$record = olbTimetable::reserved( $olb->qs['room_id'], $date, $time );
					// Reserved -> Cancel from teacher
					if ( $record['user_id'] ) {
						if ( $user->isAdmin() || $user->data['id'] == $record['room_id'] ) {
							$url = get_permalink(get_page_by_path($olb->cancel_form_page)->ID);
							if ( $user->isAdmin() ) {
								$url = add_query_arg( $olb->qs, $url );
							} else {
								$url = add_query_arg( array( 't' => $olb->qs['t'] ), $url );
							}
							header('Location: '.$url );
							exit;
						}
					}
				}
				header('Location: '.$olb->home);
				exit;
			}
		}

		// スケジュール設定ページ(管理者か講師のみ): Scheduling-page(only admin and room manager)
		if($ancestor->post_name==$olb->edit_schedule_page){
			if(!$user->isLoggedIn()){
				if(empty($olb->login_page)) {
					$url = wp_login_url();
				}
				else {
					$url = get_permalink(get_page_by_path($olb->login_page)->ID);
				}
				if ( !empty( $redirect_info ) ) {
					$url .= ( strstr( $url, '?' ) ) ? '&' : '?';
					$url = $url.implode( '&', $redirect_info );
				}
				header('Location: '.$url);
				exit;
			}
			if(!$user->isAdmin() && !$user->isRoomManager()){
				header('Location: '.$olb->home);
				exit;
			}
			// Admin
			if ( $user->isAdmin() && $_SERVER['REQUEST_METHOD']!='POST' ) {
				self::admin_pretending_switch( 'room', $current_post, $ancestor );
			}
		}

		// 講師によるキャンセルページ(ログイン中のみ): Cancellation-page(only admin and room manager)
		if($ancestor->post_name==$olb->cancel_form_page){
			if(!$user->isLoggedIn()){
				if(empty($olb->login_page)) {
					$url = wp_login_url();
				}
				else {
					$url = get_permalink(get_page_by_path($olb->login_page)->ID);
				}
				if ( !empty( $redirect_info ) ) {
					$url .= ( strstr( $url, '?' ) ) ? '&' : '?';
					$url = $url.implode( '&', $redirect_info );
				}
				header('Location: '.$url);
				exit;
			}
			if(!$user->isAdmin() && !$user->isRoomManager()){
				header('Location: '.$olb->home);
				exit;
			}
		}
	}

	/**
	 *	管理者によるユーザーのふりを解除する
	 */
	public static function admin_pretending_switch( $mode, $current_post, $ancestor ) {
		global $olb;

		$url = ( empty( $_SERVER["HTTPS"] ) ) ? "http://" : "https://";
		$url .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

		// Pretending off
		if ( isset( $olb->qs['pretend_off'] ) ) {
			unset( $_SESSION['admin_pretend'] );
			$url = get_permalink($current_post->ID);
			header('Location: '.$url);
			exit;
		}

		$id_key = $mode.'_id';

		if ( !isset( $olb->qs[$id_key] ) ) {
			if ( isset( $_SESSION['admin_pretend'] ) && $_SESSION['admin_pretend']['ancestor'] == $ancestor->ID ) {
				$url .= ( strstr( $url, '?' ) ) ? '&' : '?';
				$url .= sprintf( $id_key.'=%d', $_SESSION['admin_pretend'][$id_key]);
				header('Location: '.$url);
				exit;
			}
		}
	}

	/**
	 *	管理者による会員マイページへのアクセス: Accessing to member's my page by admin
	 */
	public static function admin_access_mypage( $content ) {
		global $olb;

		if ( is_page( $olb->member_page ) ) {
			if ( $olb->operator->isLoggedIn() && $olb->operator->isAdmin() ) {
				ob_start();
				$target_user = false;
				$args = array(
					'pretends' => 'user',
				);
				$target_user = apply_filters( 'olb_admin_pretending_user', $target_user, $args );
				if( empty( $target_user ) ) {
					$content = ob_get_contents();
					ob_end_clean();
				}
				return $content;
			}
		}
		return $content;
	}

	/**
	 *	フォームアクション: form action
	 */
	public static function formAction(){
		global $olb, $wpdb, $post;
		
		if($_SERVER['REQUEST_METHOD']=='POST'){
			$current_post = $post;
			$olbform = new olbFormAction();

			switch($current_post->post_name){
			case $olb->reserve_form_page:
				$olbform->reservation();
				break;

			case $olb->cancel_form_page:
				$olbform->cancellation();
				break;
			
			case $olb->report_form_page:
				$olbform->report();
				break;

			case $olb->edit_schedule_page:
				$olbform->scheduler();
				break;
			}
		}
	}

	/** 
	 *	フロント用style/script読込: Load css and js for front page
	 */
	public static function loadFrontHeader() {
		global $olb;

		echo <<<EOD
<link rel="stylesheet" href="{$olb->mypluginurl}front.css" type="text/css" />
EOD;
	}

	/** 
	 *	フロント用style/script読込: Load css and js for front page
	 */
	public static function front_script() {
		global $olb;
		wp_enqueue_script( 
			OLBsystem::TEXTDOMAIN.'_script',			// handle
			$olb->mypluginurl.'front.js',		// src
			array( 'jquery' ),						// deps
			OLBsystem::PLUGIN_VERSION, 				// ver
			true 									// in footer
		);
	}

	/** 
	 *	講師URL保存: Save url of teacher's page
	 */
	public static function saveRoomURL($post_id){
		// 週間スケジュールから講師IDを取得しページURLを保存: get room-id from tag of weekly schecule, save url
		$content = str_replace("\"", '', stripslashes( get_post( $post_id )->post_content ) ); //$content = str_replace("\"", '', stripslashes($_POST['content']));
		$match = preg_match('/\[olb_weekly_schedule\sid=[0-9]+\]/', $content, $matches);
		if($match){
			$id = preg_replace('/[^0-9]/', '', $matches[0]);
			$room = new olbAuth($id);
			if(!empty($room->data['id'])){
				if($room->isRoomManager()) {
					wp_update_user(array('ID'=>$room->data['id'], 'user_url'=>get_permalink($post_id)));
					update_user_meta( $room->data['id'], 'olbprofile', $post_id );
				}
			}
		}
	}

	/** 
	 *	講師URL削除: Delete url of teacher's page
	 */
	public static function deleteRoomURL($post_id){
		// 週間スケジュールから講師IDを取得しページURLを削除: get room-id from tag of weekly schecule, delete url
		$post = get_post($post_id);
		$content = str_replace("\"", '', stripslashes($post->post_content));
		if(!empty($post->ID)){
			$match = preg_match('/\[olb_weekly_schedule\sid=[0-9]+\]/', $content, $matches);
			if($match){
				$id = preg_replace('/[^0-9]/', '', $matches[0]);
				$room = new olbAuth($id);
				if(!empty($room->data['id'])){
					if($room->isRoomManager()) {
						wp_update_user(array('ID'=>$room->data['id'], 'user_url'=>''));
						delete_user_meta( $room->data['id'], 'olbprofile' );
					}
				}
			}
		}
	}

	/** 
	 *	ユーザー一覧: Customize of user list
	 */
	public static function addUsersColumns($column_headers){
		$column_headers['olbgroup'] = __('Teacher', OLBsystem::TEXTDOMAIN);
		$column_headers['ID'] = __('ID', OLBsystem::TEXTDOMAIN);
		return $column_headers;
	}

	public static function customUsersColumn($custom_column, $column_name, $user_id) {
		$custom_column = apply_filters( 'olb_users_custom_column', $custom_column, $column_name, $user_id );
		return $custom_column;
	}

	public static function customUsersColumnFilter($custom_column, $column_name, $user_id) {
		$user_info = get_userdata($user_id);
		${$column_name} = $user_info->$column_name;
		$custom_column = ${$column_name};
		if ( $column_name == 'olbgroup' && ${$column_name} == 'teacher' ) {
			$custom_column = __('Teacher', OLBsystem::TEXTDOMAIN);
		}
		return $custom_column;
	}

	public static function sortableUsersColumns($columns){
		$columns['olbgroup'] = __('Teacher', OLBsystem::TEXTDOMAIN);
		return $columns;
	}

	public static function orderbyUsersColumn($vars) {
		if(isset($vars['orderby']) && $vars['orderby'] == 'olbgroup') {
			$vars = array_merge($vars, array(
				'meta_key' => 'olbgruoup',
				'orderby' => 'meta_value',
				'order'     => 'asc'
			) );
		}
		return $vars;
	}

	/** 
	 *	プラグインの更新
	 */
	public static function plugin_update_check() {
		global $wpdb, $olb;

		if(!is_admin()){
			return;
		}

		$installed_version = get_option('olbversion');
		$new_version = $installed_version;

		// PLUGIN
		if($installed_version['plugin'] < OLBsystem::PLUGIN_VERSION) {

			// Add 'Members info' page (ver 0.3.0 -> 0.3.1)
			if($installed_version['plugin'] < '0.3.1'
				&& empty(get_page_by_path($pages['edit_schedule_page'].'/'.$default['specialpages']['members_info_page'])->ID)){

				$pages = OLBsystem::getPluginOptions( 'specialpages' );
				$default = OLBsystem::setDefaultOptions();

				$parent = get_page_by_path($pages['edit_schedule_page']);
				$parent_id = $parent->ID;
				$args = array(
					'post_title'     => __('Members information', OLBsystem::TEXTDOMAIN),
					'post_content'   => "[olb_refer_members_info]\n"
								   ."<h3>".__('Recent history', OLBsystem::TEXTDOMAIN)."</h3>\n"
								   ."[olb_refer_members_history]<p>".__('No history', OLBsystem::TEXTDOMAIN)."</p>[/olb_refer_members_history]",
					'post_name'     => $default['specialpages']['members_info_page'],
					'post_parent'    => $parent_id,
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed'
				);
				wp_insert_post($args);
				$options = get_option(OLBsystem::TEXTDOMAIN);
				$options['specialpages']['members_info_page'] = $default['specialpages']['members_info_page'];
				update_option(OLBsystem::TEXTDOMAIN, $options);
			}

			// Add 'Members info' page (ver 0.3.1 -> 0.4.0)
			if($installed_version['plugin'] < '0.4.0') {
				$options_key = OLBsystem::TEXTDOMAIN;
				$old_options = get_option( $options_key );
				$new_options = OLBsystem::setDefaultOptions();
				foreach( $new_options['settings'] as $key=>$value ) {
					if ( in_array( $key, array( 'limit_per_month', 'ticket_system', 'ticket_metakey', 'ticket_expire' ) ) ) {
						$old_options['settings'][$key] = $value;
					}
				}
				update_option( $options_key, $olb_options );

				$parent = get_page_by_path($olb->member_page);
				$parent_id = $parent->ID;
				$args = array(
					'post_title'    => __('Ticket Logs', OLBsystem::TEXTDOMAIN),
					'post_content'  => '[olb_ticket_logs]<p>'.__('No Logs', OLBsystem::TEXTDOMAIN).'</p>[/olb_ticket_logs]',
					'post_name'     => __('Ticket Logs', OLBsystem::TEXTDOMAIN),
					'post_parent'    => $parent_id,
					'post_status'    => 'draft',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed'
				);
				wp_insert_post($args);
			}
			$new_version['plugin'] = OLBsystem::PLUGIN_VERSION;
			update_option('olbversion', $new_version);
		}
	}

	/** 
	 *	データベース構造の更新
	 */
	public static function db_update_check() {
		global $wpdb, $olb;

		if(!is_admin()){
			return;
		}

		$installed_version = get_option('olbversion');
		$new_version = $installed_version;
		// DATABASE
		if($installed_version['db'] < OLBsystem::DB_VERSION ) {
			// UPDATE TABLE  (ver 0.2.0.1 -> 0.3.0)
			$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
			if($installed_version['db'] < '0.3.0' 
				&& $wpdb->get_var("SHOW TABLES LIKE '{$prefix}timetable'") == $prefix.'timetable'
				&& $wpdb->get_var("SHOW TABLES LIKE '{$prefix}history'") == $prefix.'history') {
				require_once(ABSPATH.'wp-admin/includes/upgrade.php');
				$sql = <<<EOD
ALTER TABLE {$prefix}timetable 
DROP COLUMN id,
DROP COLUMN user_id,
DROP COLUMN free,
DROP COLUMN absent,
ADD COLUMN seats int(11) NOT NULL COMMENT 'Seats',
ADD PRIMARY KEY (`date`,`time`,`room_id`);
EOD;
				$wpdb->query($sql);

				$sql = <<<EOD
ALTER TABLE {$prefix}history 
MODIFY COLUMN id bigint(20) NOT NULL COMMENT 'Reserve ID' AUTO_INCREMENT;
EOD;
				$wpdb->query($sql);
				$new_version['db'] = OLBsystem::DB_VERSION;
			}
			// UPDATE TABLE  (ver 0.3.0 -> 0.4.0)
			if($installed_version['db'] < '0.4.0' 
				&& $wpdb->get_var("SHOW TABLES LIKE '{$prefix}logs'") != $prefix.'logs') {
				require_once(ABSPATH.'wp-admin/includes/upgrade.php');
				$sql = <<<EOD
CREATE TABLE IF NOT EXISTS {$prefix}logs (
id bigint(20) NOT NULL AUTO_INCREMENT,
uid bigint(20) NOT NULL,
type varchar(256) NOT NULL,
data text NOT NULL,
points bigint(20) NOT NULL,
timestamp bigint(20) NOT NULL,
UNIQUE KEY id (id)
) AUTO_INCREMENT=1;
EOD;
				dbDelta($sql);

				$wpdb->query($sql);
				$new_version['db'] = OLBsystem::DB_VERSION;
			}
			update_option('olbversion', $new_version);
		}
	}

	/** 
	 *	プラグイン有効化
	 */
	public static function activation() {
		global $wpdb;

		$version = get_option('olbversion');

		// CREATE TABLE
		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefix.'timetable')) != $prefix.'timetable' &&
			$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefix.'history')) != $prefix.'history' &&
			$wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefix.'logs')) != $prefix.'logs') {
			require_once(ABSPATH.'wp-admin/includes/upgrade.php');
			$sql = <<<EOD
CREATE TABLE IF NOT EXISTS {$prefix}timetable (
date date NOT NULL COMMENT 'Reserve date',
time time NOT NULL COMMENT 'Reserve time',
room_id int NOT NULL COMMENT 'Room ID',
seats int(11) NOT NULL COMMENT 'Seats',
PRIMARY KEY (`date`,`time`,`room_id`)
);
EOD;
			dbDelta($sql);

			$sql = <<<EOD
CREATE TABLE IF NOT EXISTS {$prefix}history (
id bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Reserve ID',
date date NOT NULL COMMENT 'Reserve date',
time time NOT NULL COMMENT 'Reserve time',
room_id int NOT NULL COMMENT 'Room ID',
user_id int NOT NULL COMMENT 'User ID',
free int NOT NULL  COMMENT 'Free',
absent int NOT NULL  COMMENT 'Absent',
PRIMARY KEY (id)
);
EOD;
			dbDelta($sql);

			$sql = <<<EOD
CREATE TABLE IF NOT EXISTS {$prefix}logs (
id bigint(20) NOT NULL AUTO_INCREMENT,
uid bigint(20) NOT NULL,
type varchar(256) NOT NULL,
data text NOT NULL,
points bigint(20) NOT NULL,
timestamp bigint(20) NOT NULL,
UNIQUE KEY id (id)
) AUTO_INCREMENT=1;
EOD;
			dbDelta($sql);

			$version['db'] = OLBsystem::DB_VERSION;
		}
		$version['plugin'] = OLBsystem::PLUGIN_VERSION;
		update_option('olbversion', $version);

		$default_options = OLBsystem::setDefaultOptions();
		$specialpages = $default_options['specialpages'];
		/*
				'daily_schedule_page' => 'schedule',
				'reserve_form_page'   => 'reservation',
				'cancel_form_page'    => 'cancel',
				'report_form_page'    => 'report',
				'edit_schedule_page'  => 'editschedule',
				'member_page'         => 'mypage',
				'login_page'          => '',
		*/
		$memer_page_content = __('
<p>
Hello [olb_member_data key="name"].
</p>

<h4>Term of validity:</h4>
<p>
[olb_member_data key="olbterm"]
[olb_if_expire]Expired[/olb_if_expire]
</p>

<h4>Free ticket:</h4>
<p>[olb_member_data key="free"] left.</p>

<h4>History:</h4>
[olb_members_history perpage="5" pagenavi="0"]
<p>(No history)</p>
[/olb_members_history]

<h4>Reserved:</h4>
[olb_members_schedule perpage="5" pagenavi="0"]
<p>(No schedule)</p>
[/olb_members_schedule]
', OLBsystem::TEXTDOMAIN);

		// INSERT PAGE
		$pages = array(
			'edit_schedule' => array(
				'post_title'     => __('Scheduler for teacher', OLBsystem::TEXTDOMAIN),
				'post_content'   => '[olb_edit_schedule]',
				'post_name'      => $specialpages['edit_schedule_page'],
				'post_status'   => 'publish',
			),
			'cancel_form' => array(
				'post_title'     => __('Cancel form for teacher', OLBsystem::TEXTDOMAIN),
				'post_content'   => '[olb_cancel_form]',
				'post_name'      => $specialpages['cancel_form_page'],
				'post_status'   => 'publish',
			),
			'report_form' => array(
				'post_title'     => __('Report form for teacher', OLBsystem::TEXTDOMAIN),
				'post_content'   => '[olb_report_form]',
				'post_name'      => $specialpages['report_form_page'],
				'post_status'   => 'publish',
			),
			'reserve_form' => array(
				'post_title'     => __('Reservation and cancellation form', OLBsystem::TEXTDOMAIN),
				'post_content'   => '[olb_reserve_form]',
				'post_name'      => $specialpages['reserve_form_page'],
				'post_status'   => 'publish',
			),
			'member_page' => array(
				'post_title'     => __('Members my-page', OLBsystem::TEXTDOMAIN),
				'post_content'   => $memer_page_content,
				'post_name'      => $specialpages['member_page'],
				'post_status'   => 'publish',
			),
			'daily_schedule' => array(
				'post_title'     => __('Daily schedule', OLBsystem::TEXTDOMAIN),
				'post_content'   => "[olb_calendar]\n[olb_daily_schedule]",
				'post_name'      => $specialpages['daily_schedule_page'],
				'post_status'   => 'publish',
			),
		);
		$child_pages = array(
			'edit_schedule' => array(
				'teachers_history' => array(
					'post_title'    => __('Teachers history', OLBsystem::TEXTDOMAIN),
					'post_content'  => '[olb_teachers_history]<p>'.__('No history', OLBsystem::TEXTDOMAIN).'</p>[/olb_teachers_history]',
					'post_name'     => __('Teachers history', OLBsystem::TEXTDOMAIN),
					'post_status'   => 'publish',
				),
				'teachers_schedule' => array(
					'post_title'    => __('Teachers schedule', OLBsystem::TEXTDOMAIN),
					'post_content'  => '[olb_teachers_schedule]<p>'.__('No schedule', OLBsystem::TEXTDOMAIN).'</p>[/olb_teachers_schedule]',
					'post_name'     => __('Teachers schedule', OLBsystem::TEXTDOMAIN),
					'post_status'   => 'publish',
				),
				'members_info' => array(
					'post_title'    => __('Members information', OLBsystem::TEXTDOMAIN),
					'post_content'  => "[olb_refer_members_info]\n"
									   ."<h3>".__('Recent history', OLBsystem::TEXTDOMAIN)."</h3>\n"
									   ."[olb_refer_members_history]<p>".__('No history', OLBsystem::TEXTDOMAIN)."</p>[/olb_refer_members_history]",
					'post_name'     => $specialpages['members_info_page'],
					'post_status'   => 'publish',
				),
			),
			'member_page' => array(
				'ticket_logs' => array(
					'post_title'    => __('Ticket Logs', OLBsystem::TEXTDOMAIN),
					'post_content'  => '[olb_ticket_logs]<p>'.__('No Logs', OLBsystem::TEXTDOMAIN).'</p>[/olb_ticket_logs]',
					'post_name'     => __('Ticket Logs', OLBsystem::TEXTDOMAIN),
					'post_status'    => 'draft',
				),
				'members_history' => array(
					'post_title'    => __('Members history', OLBsystem::TEXTDOMAIN),
					'post_content'  => '[olb_members_history]<p>'.__('No history', OLBsystem::TEXTDOMAIN).'</p>[/olb_members_history]',
					'post_name'     => __('Members history', OLBsystem::TEXTDOMAIN),
					'post_status'   => 'publish',
				),
				'members_schedule' => array(
					'post_title'    => __('Members schedule', OLBsystem::TEXTDOMAIN),
					'post_content'  => '[olb_members_schedule]<p>'.__('No schedule', OLBsystem::TEXTDOMAIN).'</p>[/olb_members_schedule]',
					'post_name'     => __('Members schedule', OLBsystem::TEXTDOMAIN),
					'post_status'   => 'publish',
				),
			)
		);
		foreach($pages as $name=>$page){
			$p = get_page_by_path( $page['post_name'] );
			if ( empty( $p ) ){
				$args = array_merge(
					$page, 
					array(
						'post_type'      => 'page',
						'comment_status' => 'closed',
						'ping_status'    => 'closed'
					)
				);
				$parent_id = wp_insert_post($args);
			}
			else {
				$parent_id = $p->ID;
			}
			if(isset($child_pages[$name])){
				foreach($child_pages[$name] as $child){
					$c = get_page_by_path( $page['post_name'].'/'.$child['post_name'] );
					if ( empty( $c ) ){
						$args = array_merge(
							$child, 
							array(
								'post_parent'    => $parent_id,
								'post_type'      => 'page',
								'comment_status' => 'closed',
								'ping_status'    => 'closed'
							)
						);
						$child_id = wp_insert_post($args);
					}
				}
			}
		}
	}

	/** 
	 *	プラグイン無効化
	 */
	public static function deactivation() {
		wp_clear_scheduled_hook('olb_cron');
	}

	/** 
	 *	プラグイン削除
	 */
	public static function uninstall() {
		delete_option(OLBsystem::TEXTDOMAIN);
		delete_option('olbversion');
	}

	/** 
	 *	CRON処理のインターバル追加
	 */
	public static function cron_add_interval($schedules){
		$schedules['10sec'] = array(
			'interval' => 10,
			'display' => '10sec'
		);
		$schedules['halfhour'] = array(
			'interval' => 60*30,
			'display' => 'harfhour'
		);
		return $schedules;
	}

	/** 
	 *	CRON処理
	 */
	public static function olb_cron_do(){
		global $wpdb, $olb;

		$preserve_day = current_time('timestamp') - ($olb->preserve_past*60*60*24);
		$prefix = $wpdb->prefix.OLBsystem::TABLEPREFIX;
		$query = "DELETE FROM ".$prefix."timetable WHERE `date`<%s";
		$ret = $wpdb->query($wpdb->prepare($query, array(date('Y-m-d', $preserve_day))), ARRAY_A);
	}

	/** 
	 *	CRON更新
	 */
	public static function olb_cron_update() {
		global $olb;

		if ( !wp_next_scheduled( 'olb_cron' ) ) {
			wp_schedule_event(time(), $olb->cron_interval, 'olb_cron');
		}
	}


}
?>
