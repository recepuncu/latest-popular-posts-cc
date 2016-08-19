<?php
/*
Plugin Name: Latest & Popular Posts Comment Count
Plugin URI: https://wordpress.org/plugins/latest-popular-posts-cc
Description: Sitenizin en güncel yazıları yorum sayıları ile birlikte.
Author: Recep Uncu
Author URI: http://recepuncu.com
Version: 1.0
License: GPL2
*/

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

//Style
function st_register_widget_styles() {
	wp_register_style( 'latest-popular-posts-cc', plugins_url( 'latest-popular-posts-cc/style.css' ) );
	wp_enqueue_style( 'latest-popular-posts-cc' );
}

add_action( 'wp_enqueue_scripts', 'st_register_widget_styles' );

//Thumb size
function st_thumb_setup() {
	add_image_size('xs-thumb', 64, 64, TRUE);
}
add_action('after_setup_theme', 'st_thumb_setup');


//wadget
add_action('widgets_init','register_recepuncu_latest_popular_posts_widget');

function register_recepuncu_latest_popular_posts_widget()
{
	register_widget('ST_Latest_Popular_Posts_Widget');
}

class ST_Latest_Popular_Posts_Widget extends WP_Widget{

	function ST_Latest_Popular_Posts_Widget()
	{
		parent::__construct( 'st_latest_popular_posts_widget','Son Yazılar (Yorum Sayısı)',array('description' => 'Sitenizin en güncel yazıları yorum sayıları ile birlikte.'));
	}


	/*-------------------------------------------------------
	 *				Front-end display of widget
	 *-------------------------------------------------------*/

	function widget($args, $instance)
	{
		extract($args);

		//get settings
		$title 			= apply_filters('widget_title', $instance['title'] );
		$count 			= $instance['count'];
		$cat_ID 		= $instance['cat_name'];
		$show_thumb 	= $instance['show_thumb'];
		
		echo $before_widget;

		$output = '';

		if ( $title )
			echo $before_title . $title . $after_title;

		global $wpdb;
		global $post;
		$sql = "SELECT 
				(SELECT COUNT(c.comment_ID) FROM $wpdb->comments c 
				WHERE p.ID = c.comment_post_ID 
				AND (c.comment_date > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND c.comment_date <= NOW())
				) comment_count_cc,
				IFNULL((SELECT c.comment_date FROM $wpdb->comments c WHERE p.ID = c.comment_post_ID ORDER BY c.comment_date DESC LIMIT 1), p.post_modified) last_comment_date,
				p.* FROM $wpdb->posts p
				WHERE p.post_type='post' AND p.post_status='publish'
				ORDER BY last_comment_date DESC, p.post_modified DESC
				LIMIT $count";

		$posts = $wpdb->get_results($sql);
		
		if(count($posts)>0){
			$output .='<div class="latest-popular-posts-cc">';

			foreach ($posts as $post): setup_postdata($post);
				$comments = get_comments(sprintf('post_id=%d', $post->ID));
				$output .='<div class="media">';

					if($show_thumb==1):
						$output .='<div class="pull-left">';
						if(has_post_thumbnail())
							$output .='<a href="'.get_permalink().'">'.get_the_post_thumbnail($post->ID, 'xs-thumb', array('class' => 'img-responsive')).'</a>';
						else
							$output .='<a href="'.get_permalink().'"><img src="http://placehold.it/64x64"></a>';
						$output .='</div>';
					endif;
				
					$output .='<div class="media-body">';
					$output .= '<h3 class="entry-title"><a href="'.get_permalink().'">'. get_the_title() .' ('.count($comments).')</a></h3>';
					$output .= '<div class="entry-meta small"><span class="st-lp-time">'. get_the_time() . '</span> <span clss="st-lp-date">' . get_the_date('d M Y') . '</span></div>';
					$output .='</div>';

				$output .='</div>';
			endforeach;

			wp_reset_query();

			$output .='</div>';
		}


		echo $output;

		echo $after_widget;
	}


	function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;

		$instance['title'] 			= strip_tags( $new_instance['title'] );
		$instance['cat_name'] 		= strip_tags( $new_instance['cat_name'] );
		$instance['count'] 			= strip_tags( $new_instance['count'] );
		$instance['show_thumb'] 	= strip_tags( $new_instance['show_thumb'] );

		return $instance;
	}


	function form($instance)
	{
		$defaults = array( 
			'title' 	=> 'Son Yazılar (Yorum Sayısı)',
			'cat_name' 	=> ' ',
			'count' 	=> 5,
			'show_thumb'=> 1
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
	?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Başlık:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<p style="display:none;">
			<label for="<?php echo $this->get_field_id( 'cat_name' ); ?>">Kategoriler:</label>
			<?php 
				$categories = get_categories(array('hierarchical' => false));
				if(isset($instance['cat_name'])) $cat_ID = $instance['cat_name'];
			?>
			<select class="widefat" id="<?php echo $this->get_field_id( 'cat_name' ); ?>" name="<?php echo $this->get_field_name( 'cat_name' ); ?>">
				<?php
				$op = '<option value="%s"%s>%s</option>';

				foreach ($categories as $category ) {

					if ($cat_ID === $category->cat_ID) {
			            printf($op, $category->cat_ID, ' selected="selected"', $category->name);
			        } else {
			            printf($op, $category->cat_ID, '', $category->name);
			        }
			    }
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>">Gösterilecek yazı sayısı:</label>
			<input type="number" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>" style="width:100%;" />
		</p>
		
		<div style="margin-bottom: 15px;">
			<span>Küçük Resim: </span>
		  <table>		  
			<tr>
			  <td><label>
				<input name="<?php echo $this->get_field_name( 'show_thumb' ); ?>" type="radio" value="1" <?php echo ($instance['show_thumb']==1 ? 'checked="checked"' : ''); ?> />
				Gösterilsin</label></td>
			  <td><label>
				<input type="radio" name="<?php echo $this->get_field_name( 'show_thumb' ); ?>" value="0" <?php echo ($instance['show_thumb']==0 ? 'checked="checked"' : ''); ?> />
				Gösterilmesin</label></td>
			</tr>
		  </table>		
		</div>

	<?php
	}
}