<?php
/**
 * Plugin Name: OzPlayer
 * Plugin URI: http://oz-player.com
 * Description: OzPlayer support for WordPress
 * Version: 0.1
 * Author: Matt McLeod
 * Author URI: http://accessibilityoz.com.au/
 * License: GPL
 */

defined('ABSPATH') or die("No script kiddies please!");

/**
 * The Video shortcode.
 *
 * This implements the functionality of the Video Shortcode for displaying
 * WordPress mp4s in a post.
 *
 * @since 3.6.0
 *
 * @param array $attr {
 *     Attributes of the shortcode.
 *
 *     @type string $src      URL to the source of the video file. Default empty.
 *     @type int    $height   Height of the video embed in pixels. Default 360.
 *     @type int    $width    Width of the video embed in pixels. Default $content_width or 640.
 *     @type string $poster   The 'poster' attribute for the `<video>` element. Default empty.
 *     @type string $loop     The 'loop' attribute for the `<video>` element. Default empty.
 *     @type string $autoplay The 'autoplay' attribute for the `<video>` element. Default empty.
 *     @type string $preload  The 'preload' attribute for the `<video>` element.
 *                            Default 'metadata'.
 *     @type string $class    The 'class' attribute for the `<video>` element.
 *                            Default 'wp-video-shortcode'.
 *     @type string $id       The 'id' attribute for the `<video>` element.
 *                            Default 'video-{$post_id}-{$instances}'.
 * }
 * @param string $content Optional. Shortcode content.
 * @return string HTML content to display video.
 */
function ozp_video_shortcode( $attr, $content = '' ) {
	global $content_width;
	$post_id = get_post() ? get_the_ID() : 0;

	static $instances = 0;
	$instances++;

	/**
	 * Filter the default video shortcode output.
	 *
	 * If the filtered output isn't empty, it will be used instead of generating
	 * the default video template.
	 *
	 * @since 3.6.0
	 *
	 * @see wp_video_shortcode()
	 *
	 * @param string $html      Empty variable to be replaced with shortcode markup.
	 * @param array  $attr      Attributes of the video shortcode.
	 * @param string $content   Video shortcode content.
	 * @param int    $instances Unique numeric ID of this video shortcode instance.
	 */
	$html = apply_filters( 'wp_video_shortcode_override', '', $attr, $content, $instances );
	if ( '' !== $html )
		return $html;

	$video = null;

	$default_types = wp_get_video_extensions();
	$defaults_atts = array(
		'src'      => '',
		'poster'   => '',
		'loop'     => '',
		'autoplay' => '',
		'preload'  => 'metadata',
		'width'    => 640,
		'height'   => 360,
	);

	foreach ( $default_types as $type )
		$defaults_atts[$type] = '';

	$atts = shortcode_atts( $defaults_atts, $attr, 'video' );
	extract( $atts );

	if ( is_admin() ) {
		// shrink the video so it isn't huge in the admin
		if ( $width > $defaults_atts['width'] ) {
			$height = round( ( $height * $defaults_atts['width'] ) / $width );
			$width = $defaults_atts['width'];
		}
	} else {
		// if the video is bigger than the theme
		if ( ! empty( $content_width ) && $width > $content_width ) {
			$height = round( ( $height * $content_width ) / $width );
			$width = $content_width;
		}
	}

	$yt_pattern = '#^https?://(:?www\.)?(:?youtube\.com/watch|youtu\.be/)#';

	$primary = false;
	if ( ! empty( $src ) ) {
		if ( ! preg_match( $yt_pattern, $src ) ) {
			$type = wp_check_filetype( $src, wp_get_mime_types() );
			if ( ! in_array( strtolower( $type['ext'] ), $default_types ) ) {
				return sprintf( '<a class="wp-embedded-video" href="%s">%s</a>', esc_url( $src ), esc_html( $src ) );
			}
		}
		$primary = true;
		array_unshift( $default_types, 'src' );
	} else {
		foreach ( $default_types as $ext ) {
			if ( ! empty( $$ext ) ) {
				$type = wp_check_filetype( $$ext, wp_get_mime_types() );
				if ( strtolower( $type['ext'] ) === $ext )
					$primary = true;
			}
		}
	}

	if ( ! $primary ) {
		$videos = get_attached_media( 'video', $post_id );
		if ( empty( $videos ) )
			return;

		$video = reset( $videos );
		$src = wp_get_attachment_url( $video->ID );
		if ( empty( $src ) )
			return;

		array_unshift( $default_types, 'src' );
	}

	/**
	 * Filter the media library used for the video shortcode.
	 *
	 * @since 3.6.0
	 *
	 * @param string $library Media library used for the video shortcode.
	 */
	$library = apply_filters( 'wp_video_shortcode_library', 'mediaelement' );
	if ( 'mediaelement' === $library && did_action( 'init' ) ) {
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );
	}

	/**
	 * Filter the class attribute for the video shortcode output container.
	 *
	 * @since 3.6.0
	 *
	 * @param string $class CSS class or list of space-separated classes.
	 */
	$atts = array(
		'class'    => apply_filters( 'wp_video_shortcode_class', 'wp-video-shortcode' ),
		'id'       => sprintf( 'video-%d-%d', $post_id, $instances ),
		'width'    => absint( $width ),
		'height'   => absint( $height ),
		'poster'   => esc_url( $poster ),
		'loop'     => $loop,
		'autoplay' => $autoplay,
		'preload'  => $preload,
	);

	// These ones should just be omitted altogether if they are blank
	foreach ( array( 'poster', 'loop', 'autoplay', 'preload' ) as $a ) {
		if ( empty( $atts[$a] ) )
			unset( $atts[$a] );
	}

	$attr_strings = array();
	foreach ( $atts as $k => $v ) {
		$attr_strings[] = $k . '="' . esc_attr( $v ) . '"';
	}

	$html = '';
	if ( 'mediaelement' === $library && 1 === $instances )
		$html .= "<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->\n";
	$html .= sprintf( '<video %s controls="controls">', join( ' ', $attr_strings ) );

	$fileurl = '';
	$source = '<source type="%s" src="%s" />';
	foreach ( $default_types as $fallback ) {
		if ( ! empty( $$fallback ) ) {
			if ( empty( $fileurl ) )
				$fileurl = $$fallback;

			if ( 'src' === $fallback && preg_match( $yt_pattern, $src ) ) {
				$type = array( 'type' => 'video/youtube' );
			} else {
				$type = wp_check_filetype( $$fallback, wp_get_mime_types() );
			}
			$url = add_query_arg( '_', $instances, $$fallback );
			$html .= sprintf( $source, $type['type'], esc_url( $url ) );
		}
	}

	if ( ! empty( $content ) ) {
		if ( false !== strpos( $content, "\n" ) )
			$content = str_replace( array( "\r\n", "\n", "\t" ), '', $content );

		$html .= trim( $content );
	}

	if ( 'mediaelement' === $library )
		$html .= wp_mediaelement_fallback( $fileurl );
	$html .= '</video>';

	$html = sprintf( '<div style="width: %dpx; max-width: 100%%;" class="wp-video">%s</div>', $width, $html );

	/**
	 * Filter the output of the video shortcode.
	 *
	 * @since 3.6.0
	 *
	 * @param string $html    Video shortcode HTML output.
	 * @param array  $atts    Array of video shortcode attributes.
	 * @param string $video   Video file.
	 * @param int    $post_id Post ID.
	 * @param string $library Media library used for the video shortcode.
	 */
	return apply_filters( 'ozp_video_shortcode', $html, $atts, $video, $post_id, $library );
}
add_shortcode( 'ozplayer', 'ozp_video_shortcode' );


?>
