<?php
/**
 * Plugin Name: OzPlayer
 * Plugin URI: http://oz-player.com
 * Description: OzPlayer support for WordPress
 * Version: 4.1
 * Author: AccessibilityOz
 * Author URI: https://www.accessibilityoz.com/
 * License: GPL2
 */

/*  Copyright 2021  AccessibilityOz  (email : support@accessibilityoz.com)

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

add_option("ozplayer_base", '//ozplayer.global.ssl.fastly.net/4.1');
add_option("ozplayer_config_js", '//ozplayer.global.ssl.fastly.net/4.1/config.js');
add_option("ozplayer_transcript_css", '//ozplayer.global.ssl.fastly.net/4.1/transcript.css');
add_option("ozplayer_commercial", 0);
add_option("ozplayer_lang", 'en');
add_option("ozplayer_color", 'blue');
add_option("ozplayer_transcript_heading", "Video transcript");
add_option("ozplayer_transcript_open", 0);
add_option("ozplayer_captions_on", 0);
add_option("ozplayer_ad_on", 0);
add_option("ozplayer_transcript_on", 1);

function add_query_vars_filter($vars)
{
    $vars[] = "audio_described";
    return $vars;
}

add_filter('query_vars', 'add_query_vars_filter');

/**
 * The Video shortcode.
 *
 * This implements the functionality of the Video Shortcode for displaying
 * WordPress mp4s in a post.
 *
 * @param array $attr {
 *     Attributes of the shortcode.
 *
 * @type string $src URL to the source of the video file. Default empty.
 * @type int $height Height of the video embed in pixels. Default 360.
 * @type int $width Width of the video embed in pixels. Default $content_width or 640.
 * @type string $poster The 'poster' attribute for the `<video>` element. Default empty.
 * @type string $loop The 'loop' attribute for the `<video>` element. Default empty.
 * @type string $autoplay The 'autoplay' attribute for the `<video>` element. Default empty.
 * @type string $preload The 'preload' attribute for the `<video>` element.
 *                            Default 'metadata'.
 * @type string $class The 'class' attribute for the `<video>` element.
 *                            Default 'wp-video-shortcode'.
 * @type string $id The 'id' attribute for the `<video>` element.
 *                            Default 'video-{$post_id}-{$instances}'.
 * @type string $color Colour for highlights
 * @type string $captions URL to the captions VTT file
 * @type string $transcript URL to the transcript-extras VTT file
 * @type string $lang Language code
 *                            Default 'en'
 * @type string $mp4 URL for MP4 version of video
 * @type string $webm URL for WebM version of video
 * @type string $mp3 URL for MP3 version of audio descriptions
 * @type string $ogg URL for OGG Vorbis version of audio descriptions
 * }
 * @param string $content Optional. Shortcode content.
 * @return string HTML content to display video.
 * @since 3.6.0
 *
 */
function ozp_video_shortcode($attr, $content = '')
{
    global $content_width;
    $post_id = get_post() ? get_the_ID() : 0;

    static $instances = 0;
    $instances++;

    $ozplayer_base = get_option('ozplayer_base');
    $config_js = get_option('ozplayer_config_js');
    $transcript_heading = get_option('ozplayer_transcript_heading');
    $commercial = get_option('ozplayer_commercial');

    /**
     * Filter the default video shortcode output.
     *
     * If the filtered output isn't empty, it will be used instead of generating
     * the default video template.
     *
     * @param string $html Empty variable to be replaced with shortcode markup.
     * @param array $attr Attributes of the video shortcode.
     * @param string $content Video shortcode content.
     * @param int $instances Unique numeric ID of this video shortcode instance.
     * @since 3.6.0
     *
     * @see wp_video_shortcode()
     *
     */
    $html = apply_filters('wp_video_shortcode_override', '', $attr, $content, $instances);
    if ('' !== $html)
        return $html;

    $video = null;

    $default_types = wp_get_video_extensions();
    $defaults_atts = array(
        'src' => '',
        'ad_src' => '',
        'ogg' => '',
        'mp3' => '',
        'mp4' => '',
        'webm' => '',
        'lang' => get_option('ozplayer_lang'),
        'color' => get_option('ozplayer_color'),
        'transcript_css' => get_option('ozplayer_transcript_css'),
        'commercial' => get_option('ozplayer_commercial'),
        'poster' => '',
        'loop' => '',
        'autoplay' => '',
        'preload' => 'metadata',
        'width' => '1920',
        'height' => '1080',
        'captions' => '',
        'ad_captions' => '',
        'transcript' => '',
        'ad_transcript' => '',
        'transcript_on' => get_option('ozplayer_transcript_on'),
        'transcript_open' => get_option('ozplayer_transcript_open'),
        'ad_on' => get_option('ozplayer_ad_on'),
        'captions_on' => get_option('ozplayer_captions_on')
    );

    foreach ($default_types as $type)
        $defaults_atts[$type] = '';

    $atts = shortcode_atts($defaults_atts, $attr, 'video');
    extract($atts);

    if (!empty($ad_src) && get_query_var('audio_described', 'no') == 'yes') {
        $src = $ad_src;
        $transcript = $ad_transcript;
        $captions = $ad_captions;
    }


    if (is_admin()) {
        // shrink the video so it isn't huge in the admin
        if ($width > $defaults_atts['width']) {
            $height = round(($height * $defaults_atts['width']) / $width);
            $width = $defaults_atts['width'];
        }
    } else {
        // if the video is bigger than the theme
        if (!empty($content_width) && $width > $content_width) {
            $height = round(($height * $content_width) / $width);
            $width = $content_width;
        }
    }

#	$yt_pattern = '#^https?://(:?www\.)?(:?youtube\.com/watch|youtu\.be/)#';
    $yt_pattern = '#//(:?www\.)?(:?youtube\.com/watch|youtu\.be/)#';

    $primary = false;
    if (!empty($src)) {
        if (!preg_match($yt_pattern, $src)) {
            $tmp = preg_replace('/\?.*/', '', $src);
            $type = wp_check_filetype($tmp, wp_get_mime_types());
            if (!in_array(strtolower($type['ext']), $default_types)) {
                return sprintf('<a class="wp-embedded-video" href="%s">%s</a>', esc_url($src), esc_html($src));
            }
        }
        $primary = true;
        array_unshift($default_types, 'src');
    } else {
        foreach ($default_types as $ext) {
            if (!empty($$ext)) {
                $tmp = preg_replace('/\?.*/', '', $$ext);
                $type = wp_check_filetype($tmp, wp_get_mime_types());
                if (strtolower($type['ext']) === $ext)
                    $primary = true;
            }
        }
    }

    if (!$primary) {
        $videos = get_attached_media('video', $post_id);
        if (empty($videos))
            return;

        $video = reset($videos);
        $src = wp_get_attachment_url($video->ID);
        if (empty($src))
            return;

        array_unshift($default_types, 'src');
    }

    /**
     * Queue up the OzPlayer-related scripts and CSS
     */

    if (!$commercial) {
        $ozplayer_script = "ozplayer.free.js";
    } else {
        $ozplayer_script = "ozplayer.min.js";
    }

    wp_enqueue_script('ozp-me', $ozplayer_base . "/ozplayer-core/mediaelement.min.js", null, null, true);
    wp_enqueue_script('ozp-ozp', $ozplayer_base . "/ozplayer-core/" . $ozplayer_script, array('ozp-me'), null, true);

    wp_enqueue_script('ozp-lang', $ozplayer_base . "/ozplayer-lang/" . $lang . ".js", array('ozp-me', 'ozp-ozp'), null, true);
    wp_enqueue_script('ozp-config', $config_js, array('ozp-me', 'ozp-ozp', 'jquery'), null, true);

    wp_enqueue_style('ozp-css', $ozplayer_base . "/ozplayer-core/ozplayer.min.css");
    wp_enqueue_style('ozp-transcript', $transcript_css);
    wp_enqueue_style('ozp-colour', $ozplayer_base . "/ozplayer-skin/highlights-" . $color . ".css", "ozp-css");

    /**
     * Filter the class attribute for the video shortcode output container.
     *
     * @param string $class CSS class or list of space-separated classes.
     * @since 3.6.0
     *
     */

    $id = sprintf('video-%d-%d', $post_id, $instances);
    $atts = array(
        'width' => absint($width),
        'height' => absint($height),
        'poster' => esc_url($poster),
    );

    // These ones should just be omitted altogether if they are blank
    foreach (array('poster', 'loop', 'autoplay', 'preload') as $a) {
        if (empty($atts[$a]))
            unset($atts[$a]);
    }

    $attr_strings = array();
    foreach ($atts as $k => $v) {
        $attr_strings[] = $k . '="' . esc_attr($v) . '"';
    }

    $fallback_html = '<div class="ozplayer-fallback"><ul>';
    $falback_html .= sprintf('<img src="%s" alt=""/><ul>', $poster);
    $html = '';
    $html .= sprintf('<video %s controls="controls" preload="none">', join(' ', $attr_strings));

    $fileurl = '';
    $source = '<source type="%s" src="%s" />';
    foreach ($default_types as $fallback) {
        if (!empty($$fallback)) {
            if (empty($fileurl))
                $fileurl = $$fallback;

            if ('src' === $fallback && preg_match($yt_pattern, $src)) {
                $type = array('type' => 'video/x-youtube');
                $fallback_html .= sprintf('<li><a href="%s">View on YouTube</a></li>', $src);
            } else {
                $type = wp_check_filetype($$fallback, wp_get_mime_types());
                $fallback_html .= sprintf('<li><a href="%s">Download video</a></li>', $src);
            }
#			$url = add_query_arg( '_', $instances, $$fallback );
            $html .= sprintf($source, $type['type'], esc_url($$fallback));
        }
    }

    if (!empty($content)) {
        if (false !== strpos($content, "\n"))
            $content = str_replace(array("\r\n", "\n", "\t"), '', $content);

        $content = preg_replace('#kind=.subtitles.#', 'kind="captions"', $content);
        $html .= trim($content);
    }

    $transcript_html = '';
    $transcript_attr = '';
    if (!empty($captions)) {
        $yesno = '';
        if ($captions_on == 1 || $captions_on == "yes") {
            $yesno = 'default="default"';
        }
        $html .= sprintf('<track src="%s" kind="captions" srclang="%s" %s/>', $captions, $lang, $yesno);
        $fallback_html .= sprintf('<li><a href="%s">Download captions</a></li>', $captions);
        if ($transcript_on == 1 || $transcript_on == "yes") {
            $open = '';
            if ($transcript_open == 1 || $transcript_open == "yes") {
                $open = 'open="open"';
            }
            $transcript_html = sprintf('<details class="ozplayer-expander" %s><summary>%s</summary><div id="transcript-%s" class="ozplayer-transcript"></div></details>', $open, $transcript_heading, $id);
            $transcript_attr = sprintf('data-transcript="transcript-%s"', $id);
        }
    }
    if (!empty($transcript)) {
        $html .= sprintf('<track src="%s" kind="metadata" data-kind="transcript" srclang="%s"/>', $transcript, $lang);
    }

    $ad_html = '';
    if (!empty($mp3) or !empty($ogg)) {
        $ad = '';
        if ($ad_on == 1 || $ad_on == "yes") {
            $ad = 'data-default="default"';
        }
        $ad_html = sprintf('<audio preload="none" %s>', $ad);
        if (!empty($mp3)) {
            $ad_html .= sprintf('<source src="%s" type="audio/mp3" />', $mp3);
            $fallback_html .= sprintf('<li><a href="%s">Download audio descriptions (MP3)</a></li>', $mp3);
        }
        if (!empty($ogg)) {
            $ad_html .= sprintf('<source src="%s" type="audio/ogg" />', $ogg);
            $fallback_html .= sprintf('<li><a href="%s">Download audio descriptions (OGG)</a></li>', $ogg);
        }
        $ad_html .= "</audio>";
    } else {
        if (!empty($ad_src)) {
            $ad_html = '<audio data-on="' . get_permalink() . '?audio_described=yes" data-off="' . get_permalink() . '?audio_described=no"></audio>';
        }
    }

    $fallback_html .= '</ul></div>';
    $html .= $fallback_html . "</video>";

    $html = sprintf('<figure id="%s-container" class="ozplayer-container"><div id="%s" class="ozplayer" data-responsive="%s-container" data-controls="row" %s>%s %s</div>%s</figure>', $id, $id, $id, $transcript_attr, $html, $ad_html, $transcript_html);

    /**
     * Filter the output of the video shortcode.
     *
     * @param string $html Video shortcode HTML output.
     * @param array $atts Array of video shortcode attributes.
     * @param string $video Video file.
     * @param int $post_id Post ID.
     * @param string $library Media library used for the video shortcode.
     * @since 3.6.0
     *
     */
    return apply_filters('ozp_video_shortcode', $html, $atts, $video, $post_id, $library);
}

add_shortcode('ozplayer', 'ozp_video_shortcode');
remove_shortcode('video');
add_shortcode('video', 'ozp_video_shortcode');

add_action('admin_menu', 'ozp_plugin_menu');

function ozp_plugin_menu()
{
    add_options_page('OzPlayer Options', 'OzPlayer', 'manage_options', 'ozplayer-options', 'ozp_plugin_options');
    add_action('admin_init', 'register_ozp_settings');

}

function register_ozp_settings()
{ // whitelist options
    register_setting('ozplayer-group', 'ozplayer_base');
    register_setting('ozplayer-group', 'ozplayer_color');
    register_setting('ozplayer-group', 'ozplayer_transcript_css');
    register_setting('ozplayer-group', 'ozplayer_transcript_heading');
    register_setting('ozplayer-group', 'ozplayer_config_js');
    register_setting('ozplayer-group', 'ozplayer_transcript_open');
    register_setting('ozplayer-group', 'ozplayer_captions_on');
    register_setting('ozplayer-group', 'ozplayer_ad_on');
    register_setting('ozplayer-group', 'ozplayer_transcript_on');
    register_setting('ozplayer-group', 'ozplayer_commercial');
}

function ozp_plugin_options()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>

    <div class="wrap">
        <h2>OzPlayer Options</h2>
        <form method="post" action="options.php">
            <?php settings_fields('ozplayer-group'); ?>
            <?php do_settings_sections('ozplayer-group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">OzPlayer base URL</th>
                    <td><input type="text" name="ozplayer_base"
                               value="<?php echo esc_attr(get_option('ozplayer_base')); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Use commercial version?</th>
                    <td><input type="checkbox" name="ozplayer_commercial"
                               value="1"<?php checked(1 == get_option('ozplayer_commercial')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">config.js URL</th>
                    <td><input type="text" name="ozplayer_config_js"
                               value="<?php echo esc_attr(get_option('ozplayer_config_js')); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Transcript CSS URL</th>
                    <td><input type="text" name="ozplayer_transcript_css"
                               value="<?php echo esc_attr(get_option('ozplayer_transcript_css')); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">OzPlayer highlight color</th>
                    <td><select name="ozplayer_color" value="<?php echo esc_attr(get_option('ozplayer_color')); ?>">
                            <option <?php selected('red', get_option('ozplayer_color')); ?>>red</option>
                            <option <?php selected('blue', get_option('ozplayer_color')); ?>>blue</option>
                            <option <?php selected('green', get_option('ozplayer_color')); ?>>green</option>
                            <option <?php selected('orange', get_option('ozplayer_color')); ?>>orange</option>
                            <option <?php selected('pink', get_option('ozplayer_color')); ?>>pink</option>
                            <option <?php selected('purple', get_option('ozplayer_color')); ?>>purple</option>
                            <option <?php selected('yellow', get_option('ozplayer_color')); ?>>yellow</option>
                        </select></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Transcript heading</th>
                    <td><input type="text" name="ozplayer_transcript_heading"
                               value="<?php echo esc_attr(get_option('ozplayer_transcript_heading')); ?>"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Transcript open by default?</th>
                    <td><input type="checkbox" name="ozplayer_transcript_open"
                               value="1"<?php checked(1 == get_option('ozplayer_transcript_open')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Transcript on default?</th>
                    <td><input type="checkbox" name="ozplayer_transcript_on"
                               value="1"<?php checked(1 == get_option('ozplayer_transcript_on')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Captions on by default?</th>
                    <td><input type="checkbox" name="ozplayer_captions_on"
                               value="1"<?php checked(1 == get_option('ozplayer_captions_on')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Audio descriptions on by default?</th>
                    <td><input type="checkbox" name="ozplayer_ad_on"
                               value="1"<?php checked(1 == get_option('ozplayer_ad_on')); ?> /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

?>
