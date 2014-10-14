<?php
/*
Plugin Name: Recent Comments Widget Microdata
Plugin URI: http://isabelcastillo.com/free-plugins/recent-comments-widget-microdata
Description: WordPress widget just like the regular WP Recent Comments, but with schema.org microdata.
Version: 0.2
Author: Isabel Castillo
Author URI: http://isabelcastillo.com
License: GPL2

Copyright 2014 Isabel Castillo

This file is part of Recent Comments Widget Microdata.

Recent Comments Widget Microdata is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Recent Comments Widget Microdata is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Recent Comments Widget Microdata. If not, see <http://www.gnu.org/licenses/>.
*/

class Recent_Comments_Widget_Microdata_Plugin {

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}
	
	function register_widget() {
		register_widget( 'Recent_Comments_Microdata' );
	}
}

$recent_comments_widget_microdata = Recent_Comments_Widget_Microdata_Plugin::get_instance();

/**
 * Adds Recent_Comments_Microdata widget
 */
class Recent_Comments_Microdata extends WP_Widget {
	function __construct() {
		$widget_ops = array('classname' => 'widget_recent_comments_microdata', 'description' => __( 'Your site&#8217;s most recent comments.' ) );
		parent::__construct('recent-comments-microdata', __('Recent Comments with Microdata'), $widget_ops);
		$this->alt_option_name = 'widget_recent_comments_microdata';
		add_action( 'comment_post', array($this, 'flush_widget_cache') );
		add_action( 'edit_comment', array($this, 'flush_widget_cache') );
		add_action( 'transition_comment_status', array($this, 'flush_widget_cache') );
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_recent_comments_microdata', 'widget');
	}

	function widget( $args, $instance ) {
		global $comments, $comment;

		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get('widget_recent_comments_microdata', 'widget');
		}
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		$output = '';

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Comments' );
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number )
			$number = 5;
		$comments = get_comments( apply_filters( 'widget_comments_args', array(
			'number'      => $number,
			'status'      => 'approve',
			'post_status' => 'publish'
		) ) );
		$output .= $args['before_widget'];
		if ( $title ) {
			$output .= $args['before_title'] . $title . $args['after_title'];
		}
		$output .= '<ul id="recentcomments">';
		if ( $comments ) {
			// Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );
			foreach ( (array) $comments as $comment) {
				$output .=  '<li class="recentcomments"  itemprop="comment" itemscope itemtype="http://schema.org/Comment">' . /* translators: comments widget: 1: comment author, 2: post link */ sprintf(_x('%1$s on %2$s', 'widgets'), get_comment_author_link(), '<span itemprop="about" itemscope itemtype="http://schema.org/BlogPosting"><a href="' . esc_url( get_comment_link($comment->comment_ID) ) . '" itemprop="discussionUrl"><span itemprop="name">' . get_the_title($comment->comment_post_ID) . '</span></a></span>') . '</li>';
			}
		}
		$output .= '</ul>';
		$output .= $args['after_widget'];
		echo $output;
		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = $output;
			wp_cache_set( 'widget_recent_comments_microdata', $cache, 'widget' );
		}
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = absint( $new_instance['number'] );
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_recent_comments_microdata']) )
			delete_option('widget_recent_comments_microdata');

		return $instance;
	}

	function form( $instance ) {
		$title  = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of comments to show:' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
	
}
