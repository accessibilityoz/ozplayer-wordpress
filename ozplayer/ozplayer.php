<?php
/**
 * Plugin Name: OzPlayer
 * Plugin URI: http://oz-player.com
 * Description: OzPlayer support for WordPress
 * Version: 0.1
 * Author: Matt McLeod
 * Author URI: http://accessibilityoz.com.au/
 * License: GPL2
 */

/*  Copyright 2014  AccessibilityOz  (email : matt@accessibilityoz.com.au)

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

defined('ABSPATH') or die("No script kiddies please!");

add_option("ozplayer_base", '/ozplayer');
add_option("ozplayer_config_js", '/ozplayer-config.js');
add_option("ozplayer_transcript_css",'/ozplayer/transcript.css');
add_option("ozplayer_lang", 'en');
add_option("ozplayer_color", 'blue');
add_option("ozplayer_transcript_heading","Video transcript");

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
 *	   @type string $color    Colour for highlights
 *     @type string $captions URL to the captions VTT file
 *     @type string $transcript URL to the transcript-extras VTT file
 *     @type string $lang     Language code
 *                            Default 'en'
 *     @type string $mp4      URL for MP4 version of video
 *     @type string $webm     URL for WebM version of video
 *     @type string $mp3      URL for MP3 version of audio descriptions
 *     @type string $ogg      URL for OGG Vorbis version of audio descriptions
 * }
 * @param string $content Optional. Shortcode content.
 * @return string HTML content to display video.
 */
function ozp_video_shortcode( $attr, $content = '' ) {
	global $content_width;
	$post_id = get_post() ? get_the_ID() : 0;

	static $instances = 0;
	$instances++;

	$ozplayer_base = get_option('ozplayer_base');
	$config_js = get_option('ozplayer_config_js');
	$transcript_heading = get_option('ozplayer_transcript_heading');

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
		'ogg'		=> '',
		'mp3'      => '',
		'mp4'      => '',
		'webm'		=> '',
		'lang'      => get_option('ozplayer_lang'),
		'color'      => get_option('ozplayer_color'),
		'transcript_css' => get_option('ozplayer_transcript_css'),
		'poster'   => '',
		'loop'     => '',
		'autoplay' => '',
		'preload'  => 'metadata',
		'width'    => 640,
		'height'   => 360,
		'captions' => '',
		'transcript' => ''
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
	 * Queue up the OzPlayer-related scripts and CSS
	 */

    wp_enqueue_script('ozp-me',$ozplayer_base . "/ozplayer-core/mediaelement.min.js",null,null,true);
    wp_enqueue_script('ozp-ozp',$ozplayer_base . "/ozplayer-core/ozplayer.min.js",array('ozp-me'),null,true);
    wp_enqueue_script('ozp-lang',$ozplayer_base . "/ozplayer-lang/" . $lang . ".js",array('ozp-me','ozp-ozp'),null,true);
    wp_enqueue_script('ozp-config',$config_js,array('ozp-me','ozp-ozp','jquery'),null,true);

    wp_enqueue_style('ozp-css',$ozplayer_base . "/ozplayer-core/ozplayer.min.css");
    wp_enqueue_style('ozp-transcript',$transcript_css);
    wp_enqueue_style('ozp-colour',$ozplayer_base . "/ozplayer-skin/highlights-" . $color . ".css","ozp-css");

	/**
	 * Filter the class attribute for the video shortcode output container.
	 *
	 * @since 3.6.0
	 *
	 * @param string $class CSS class or list of space-separated classes.
	 */

	$id = sprintf( 'video-%d-%d', $post_id, $instances );
	$atts = array(
		'width'    => absint( $width ),
		'height'   => absint( $height ),
		'poster'   => esc_url( $poster ),
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
	$html .= sprintf( '<video %s controls="controls" preload="none">', join( ' ', $attr_strings ) );

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

	$transcript_html = '';
	$transcript_attr = '';
	if (! empty($captions)) {
		$html .= sprintf('<track src="%s" kind="captions" srclang="%s"/>', $captions, $lang);
	}
	if (! empty($transcript)) {
		$html .= sprintf('<track src="%s" kind="metadata" data-kind="transcript" srclang="%s"/>', $transcript, $lang);
		$transcript_html = sprintf('<details class="ozplayer-expander"><summary>%s</summary><div id="transcript-%s" class="ozplayer-transcript"></div></details>',$transcript_heading,$id);
		$transcript_attr = sprintf('data-transcript="transcript-%s"',$id);
	}
	$html .= "</video>";

	$ad_html = '';
	if (! empty($mp3) or ! empty($ogg)) {
		$ad_html = '<audio preload="none">';
		if (! empty($mp3)) {
			$ad_html .= sprintf('<source src="%s" type="audio/mp3" />',$mp3);
		}
		if (! empty($ogg)) {
			$ad_html .= sprintf('<source src="%s" type="audio/ogg" />',$ogg);
		}
		$ad_html .= "</audio>";
	}

	$html = sprintf( '<figure class="ozplayer-container"><div id="%s" class="ozplayer" data-controls="stack" %s>%s %s</div>%s</figure>', $id, $transcript_attr, $html, $ad_html, $transcript_html );

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
