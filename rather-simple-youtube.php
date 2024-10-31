<?php

/*
	Plugin Name: Simple YouTube
	Plugin URI: http://jorijn.com
	Description: YouTube plug-in which makes you enter a channel and offers a pick-style video widget
	Version: 1.0
	Author: Jorijn Schrijvershof
	Author URI: http://jorijn.com
	License: GPL2

	Copyright 2013  Jorijn Schrijvershof  (email : jorijn@jorijn.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function syt_setup_theme_admin_menus()
{
	add_submenu_page('options-general.php', __('Simple YouTube Settings'), __('Simple YouTube'), 'manage_options', 'syt-settings', 'syt_display_settings_page');
}

function syt_show_message($message, $error = false)
{
	if ($error)
	{
		echo '<div id="message" class="error">';
	}
	else
	{
		echo '<div id="message" class="updated fade">';
	}

	echo '<p><strong>'.esc_html($message).'</strong></p></div>';
}

function syt_display_settings_page()
{
	if (!current_user_can('manage_options'))
	{
	    wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	if (isset($_POST['update_settings']))
	{
		if (!wp_verify_nonce($_POST['save_syt_settings_nonce'], 'save_syt_settings'))
		{
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		$username = $_POST['syt_user'];
		if (empty($username))
		{
			syt_show_message(__('Username cannot be empty.'), true);
		}
		else
		{
			update_option('syt_user', $username);
			syt_show_message(__('Settings succesfully updated.'), false);
		}
	}

	$prefilled_value = (($var = esc_attr(get_option('syt_user'))) !== false ? $var : (isset($_POST['syt_user']) ? esc_attr($_POST['syt_user']) : ''));

	?>
	<div class="wrap">
		<?php screen_icon('themes') ?>
		<h2><?php echo __('Simple YouTube Settings') ?></h2>
		<form action="" method="post">
			<?php wp_nonce_field('save_syt_settings', 'save_syt_settings_nonce'); ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="syt_user"><?php echo __('YouTube User') ?></label>
						</th>
						<td>
							<input type="text" name="syt_user" id="syt_user" class="regular-text" value="<?php echo $prefilled_value ?>">
							<p class="description">
								<?php echo __('This plug-in will display a list of all video\'s uploaded by this user.') ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<p>
				<input type="submit" value="<?php echo __('Save settings') ?>" class="button-primary"/>
			</p>
			<input type="hidden" name="update_settings" value="Y">
		</form>
	</div>
	<?php
}

function syt_register_widget()
{
	register_widget('SYT_Video_Widget');
}

class SYT_Video_Widget extends WP_Widget
{
	function SYT_Video_Widget()
	{
		parent::__construct(false, __('SimpleYouTube Video Widget'));
	}

	function get_videos()
	{
		$author    = get_option('syt_user', 'NationalGeographic'); // how 'bout some nice nature videos?
		$user_data = json_decode(file_get_contents('http://gdata.youtube.com/feeds/api/videos?author='.urlencode($author).'&v=2&alt=json'));
		$to_return = array();

		foreach ($user_data->feed->entry as $entry)
		{
			$title          = $entry->title->{'$t'};
			$id             = $entry->{'media$group'}->{'yt$videoid'}->{'$t'};
			$to_return[$id] = $title;
		}

		return $to_return;
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance['height'] = esc_attr($instance['height']);
		$instance['width']  = esc_attr($instance['width']);
		$instance['title']  = esc_attr($instance['title']);
		$instance['video']  = esc_attr($instance['video']);

		echo $before_widget;
		if (!empty($instance['title']))
		{
			echo $before_title.$instance['title'].$after_title;
		}
		?>
		<iframe width="<?php echo $instance['width'] ?>" height="<?php echo $instance['height'] ?>" src="http://www.youtube.com/embed/<?php echo $instance['video'] ?>?rel=0modestbranding=1" frameborder="0" allowfullscreen></iframe>
		<?php
		echo $after_widget;
	}

	function update ($new_instance, $old_instance)
	{
		return $new_instance;
	}

	function form($instance)
	{
		$videos = $this->get_videos();
		$title  = isset($instance['title']) ? $instance['title'] : '';
		$height = isset($instance['height']) ? $instance['height'] : '315';
		$width  = isset($instance['width']) ? $instance['width'] : '420';
		$video  = isset($instance['video']) ? $instance['video'] : '';

		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e( 'Width:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo esc_attr( $width ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('height'); ?>"><?php _e( 'Height:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr( $height ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('video'); ?>"><?php _e( 'Video:' ); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('video'); ?>" name="<?php echo $this->get_field_name('video'); ?>">
			<?php foreach ($videos as $video_id => $video_name): ?>
			<option<?php echo ($video_id == $video ? ' selected="selected"' : '') ?> value="<?php echo esc_attr($video_id) ?>"><?php echo esc_html($video_name) ?></option>
			<?php endforeach ?>
		</select>
		</p>
		<?php
	}
}

// add actions
add_action('admin_menu', 'syt_setup_theme_admin_menus');
add_action('widgets_init', 'syt_register_widget');
