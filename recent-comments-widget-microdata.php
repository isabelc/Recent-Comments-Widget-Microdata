<?php
/*
Plugin Name: Recent Comments Widget Microdata
Plugin URI: http://isabelcastillo.com/free-plugins/recent-comments-widget-microdata
Description: WordPress widget just like the regular WP Recent Comments, but with schema.org microdata.
Version: 0.4-beta11
Author: Isabel Castillo
Author URI: http://isabelcastillo.com
License: GPL2
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
	}

	function widget( $args, $instance ) {

		if ( ! isset( $args['widget_id'] ) )
				$args['widget_id'] = $this->id;

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
		if ( is_array( $comments ) && $comments ) {
			// Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
			$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
			_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );

			foreach ( (array) $comments as $comment ) {

				$comment_post = get_post( $comment->comment_post_ID );

				$output .=  '<li class="recentcomments"  itemprop="comment" itemscope itemtype="http://schema.org/Comment">';

				/* translators: comments widget: 1: comment author, 2: post link */

				$output .= sprintf( _x( '%1$s on %2$s', 'widgets' ),
					'<span class="comment-author-link" itemprop="author" itemscope itemtype="http://schema.org/Person"><span itemprop="name">' .  $comment->comment_author . '</span></span>',
					'<span itemprop="about" itemscope itemtype="http://schema.org/TechArticle"><a href="' . esc_url( get_comment_link( $comment ) ) . '" itemprop="discussionUrl"><span itemprop="headline">' . $comment_post->post_title . '</span></a>'// closing </span> is below
				);
				/************************************************************
				*
				* @todo 

				Missing image microdata. Required for AMP.

				*
				************************************************************/
				$output .= '<meta itemscope itemprop="mainEntityOfPage" itemType="https://schema.org/WebPage" itemid="' . get_permalink( $comment_post->ID ) . '" /><span itemprop="author" itemscope itemtype="https://schema.org/Person"><meta itemprop="name" content="' . $comment_post->post_author . '"></span><span itemprop="publisher" itemscope itemtype="https://schema.org/Organization"><meta itemprop="name" content="Isabel Castillo"><span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject"><meta itemprop="url" content="' . get_template_directory_uri() . '/isa_framework_images/logo-60.png"><meta itemprop="width" content="500"><meta itemprop="height" content="60"></span></span><meta itemprop="datePublished" content="' . $comment_post->post_date . '"><meta itemprop="dateModified" content="' . $comment_post->post_modified . '"/>';

				$output .= '</span>';
				$output .= '</li>';

			}
		}
		$output .= '</ul>';
		$output .= $args['after_widget'];
		echo $output;

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		return $instance;
	}

	function form( $instance ) {
		$title  = isset( $instance['title'] ) ? $instance['title'] : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of comments to show:' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
	
}
