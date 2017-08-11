<?php
/*
 *	会員専用ウィジェット: WP Widget for member
 */

class olbWidgetMembersMenu extends WP_Widget {
	
	function __construct() {
		$widget_option = array('description' => __('This block is displayed while member logged in.',OLBsystem::TEXTDOMAIN));
		$control_option = array('width' => 400, 'height' => 350);
		parent::__construct(false, $name=__('Members only', OLBsystem::TEXTDOMAIN), $widget_option, $control_option);
	}
	function widget($args, $instance) {
		global $olb;

		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$body = apply_filters('widget_body', $instance['body']);

		if($olb->operator->isLoggedIn() && $olb->operator->isMember()):
	?>
<aside id="olb-members-menu" class="widget widget_members_menu">
<?php
if($title){
	echo $before_title.$title.$after_title;
}
if($body){
	echo '<div>'.$body.'</div>';
}
?>
</aside>
	<?php
		endif;
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['body'] = trim($new_instance['body']);
		return $instance;
	}
	function form($instance) {
		global $olb;

		if(!empty($instance['title'])) {
			$title = esc_attr($instance['title']);
		}
		else {
			$title = __('Members menu', OLBsystem::TEXTDOMAIN);
		}
		if(!empty($instance['body'])) {
			$body = esc_attr($instance['body']);
		}
		else {
			$menu = array();
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>',
					get_permalink(get_page_by_path($olb->member_page)->ID),
					get_post(get_page_by_path($olb->member_page)->ID)->post_title),
				sprintf('<li><a href="%s">%s</a></li>',
					get_permalink(get_page_by_path($olb->daily_schedule_page)->ID),
					get_post(get_page_by_path($olb->daily_schedule_page)->ID)->post_title),
				));
			$child = get_posts('post_type=page&post_parent='.get_page_by_path($olb->member_page)->ID);
			if(!empty($child)){
				foreach($child as $c){
					$excludes = array(
						$olb->reserve_form_page,
					);
					if(in_array($c->post_name, $excludes)) {
						continue;
					}
					$menu[] = sprintf('<li><a href="%s">%s</a></li>',
						get_permalink($c->ID),
						$c->post_title);
				}
			}
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>', get_admin_url().'profile.php', __('Edit profile', OLBsystem::TEXTDOMAIN)),
				sprintf('<li><a href="%s">%s</a></li>', get_admin_url().'profile.php', __('Change password', OLBsystem::TEXTDOMAIN)),
				sprintf('<li><a href="%s">%s</a></li>', wp_logout_url(), __('Logout', OLBsystem::TEXTDOMAIN)),
				));
			foreach($menu as $m){
				$body .= $m."\n";
			}
			$body = "<ul>\n".$body."</ul>\n";
		}
		?>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>">
<?php _e('Title:'); ?>
</label>
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</p>

<p>
<label for="<?php echo $this->get_field_id('body'); ?>">
</label>
<textarea	class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('body'); ?>" name="<?php echo $this->get_field_name('body'); ?>">
<?php echo $body; ?>
</textarea>
</p>
		<?php
	}
}

/*
 *	講師専用ウィジェット: WP Widget for teacher
 */

class olbWidgetTeachersMenu extends WP_Widget {
	
	function __construct() {
		$widget_option = array('description' => __('This block is displayed while teacher logged in.',OLBsystem::TEXTDOMAIN));
		$control_option = array('width' => 400, 'height' => 350);
		parent::__construct(false, $name=__('Teachers only', OLBsystem::TEXTDOMAIN), $widget_option, $control_option);
	}
	function widget($args, $instance) {
		global $olb;

		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$body = apply_filters('widget_body', $instance['body']);

		if($olb->operator->isLoggedIn() && $olb->operator->isRoomManager()):
	?>
<aside id="olb-teachers-menu" class="widget widget_teachers_menu">
<?php
if($title){
	echo $before_title.$title.$after_title;
}
if($body){
	echo '<div>'.$body.'</div>';
}
?>
</aside>
	<?php
		endif;
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['body'] = trim($new_instance['body']);
		return $instance;
	}
	function form($instance) {
		global $olb;

		if(!empty($instance['title'])) {
			$title = esc_attr($instance['title']);
		}
		else {
			$title = __('Teachers menu', OLBsystem::TEXTDOMAIN);
		}
		if(!empty($instance['body'])) {
			$body = esc_attr($instance['body']);
		}
		else {
			$menu = array();
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>',
					get_permalink(get_page_by_path($olb->edit_schedule_page)->ID),
					get_post(get_page_by_path($olb->edit_schedule_page)->ID)->post_title),
				sprintf('<li><a href="%s">%s</a></li>',
					get_permalink(get_page_by_path($olb->daily_schedule_page)->ID),
					get_post(get_page_by_path($olb->daily_schedule_page)->ID)->post_title),
				));
			$child = get_posts('post_type=page&post_parent='.get_page_by_path($olb->edit_schedule_page)->ID);
			if(!empty($child)){
				foreach($child as $c){
					$excludes = array(
						$olb->reserve_form_page,
						$olb->cancel_form_page,
						$olb->report_form_page,
						$olb->members_info_page,
					);
					if(in_array($c->post_name, $excludes)) {
						continue;
					}
					$menu[] = sprintf('<li><a href="%s">%s</a></li>',
						get_permalink($c->ID),
						$c->post_title);
				}
			}
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>', get_admin_url().'profile.php', __('Edit profile', OLBsystem::TEXTDOMAIN)),
				sprintf('<li><a href="%s">%s</a></li>', get_admin_url().'profile.php', __('Change password', OLBsystem::TEXTDOMAIN)),
				sprintf('<li><a href="%s">%s</a></li>', wp_logout_url(), __('Logout', OLBsystem::TEXTDOMAIN)),
				));
			foreach($menu as $m){
				$body .= $m."\n";
			}
			$body = "<ul>\n".$body."</ul>\n";
		}
		?>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>">
<?php _e('Title:'); ?>
</label>
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</p>

<p>
<label for="<?php echo $this->get_field_id('body'); ?>">
</label>
<textarea	class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('body'); ?>" name="<?php echo $this->get_field_name('body'); ?>">
<?php echo $body; ?>
</textarea>
</p>
		<?php
	}
}

/*
 *	管理者専用ウィジェット: WP Widget for admin
 */

class olbWidgetAdminsMenu extends WP_Widget {
	
	function __construct() {
		$widget_option = array('description' => __('This block is displayed while administrator logged in.',OLBsystem::TEXTDOMAIN));
		$control_option = array('width' => 400, 'height' => 350);
		parent::__construct(false, $name=__('Admins only', OLBsystem::TEXTDOMAIN), $widget_option, $control_option);
	}
	function widget($args, $instance) {
		global $olb;

		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$body = apply_filters('widget_body', $instance['body']);

		if($olb->operator->isLoggedIn() && $olb->operator->isAdmin()):
	?>
<aside id="olb-admins-menu" class="widget widget_admins_menu">
<?php
if($title){
	echo $before_title.$title.$after_title;
}
if($body){
	echo '<div>'.$body.'</div>';
}
?>
</aside>
	<?php
		endif;
	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['body'] = trim($new_instance['body']);
		return $instance;
	}
	function form($instance) {
		global $olb;

		if(!empty($instance['title'])) {
			$title = esc_attr($instance['title']);
		}
		else {
			$title = __('Admins menu', OLBsystem::TEXTDOMAIN);
		}
		if(!empty($instance['body'])) {
			$body = esc_attr($instance['body']);
		}
		else {
/*
			$menu = array();
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>',
					get_permalink(get_page_by_path($olb->edit_schedule_page)->ID),
					get_post(get_page_by_path($olb->edit_schedule_page)->ID)->post_title),
				));
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>', get_admin_url(), __('Dash board', OLBsystem::TEXTDOMAIN)),
				sprintf('<li><a href="%s">%s</a></li>', wp_logout_url(), __('Logout', OLBsystem::TEXTDOMAIN)),
				));
*/			$menu = array();
			// for teacher
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>',
					get_permalink(get_page_by_path($olb->edit_schedule_page)->ID),
					get_post(get_page_by_path($olb->edit_schedule_page)->ID)->post_title),
				));
			$child = get_posts('post_type=page&post_parent='.get_page_by_path($olb->edit_schedule_page)->ID);
			if(!empty($child)){
				foreach($child as $c){
					$excludes = array(
						$olb->reserve_form_page,
						$olb->cancel_form_page,
						$olb->report_form_page,
						$olb->members_info_page,
					);
					if(in_array($c->post_name, $excludes)) {
						continue;
					}
					$menu[] = sprintf('<li><a href="%s">%s</a></li>',
						get_permalink($c->ID),
						$c->post_title);
				}
			}
			// for member
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>',
					get_permalink(get_page_by_path($olb->member_page)->ID),
					get_post(get_page_by_path($olb->member_page)->ID)->post_title),
				));
			$child = get_posts('post_type=page&post_parent='.get_page_by_path($olb->member_page)->ID);
			if(!empty($child)){
				foreach($child as $c){
					$excludes = array(
						$olb->reserve_form_page,
					);
					if(in_array($c->post_name, $excludes)) {
						continue;
					}
					$menu[] = sprintf('<li><a href="%s">%s</a></li>',
						get_permalink($c->ID),
						$c->post_title);
				}
			}
			// for admin
			$menu = array_merge($menu, array(
				sprintf('<li><a href="%s">%s</a></li>', get_admin_url(), __('Dash board', OLBsystem::TEXTDOMAIN)),
				sprintf('<li><a href="%s">%s</a></li>', wp_logout_url(), __('Logout', OLBsystem::TEXTDOMAIN)),
				));
			foreach($menu as $m){
				$body .= $m."\n";
			}
			$body = "<ul>\n".$body."</ul>\n";
		}
		?>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>">
<?php _e('Title:'); ?>
</label>
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</p>

<p>
<label for="<?php echo $this->get_field_id('body'); ?>">
</label>
<textarea	class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('body'); ?>" name="<?php echo $this->get_field_name('body'); ?>">
<?php echo $body; ?>
</textarea>
</p>
		<?php
	}
}

?>
