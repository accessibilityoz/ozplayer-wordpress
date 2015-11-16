ozplayer-wordpress
==================

OzPlayer support for WordPress.

Note that while this plugin is released under GPLv2 the OzPlayer accessible
video player is commercial software. Please see http://oz-player.com/ for
information.

The full OzPlayer documentation is available from http://a11yoz.com/ozp-docs

This code is in an alpha state.

To use, copy the "ozplayer" directory from this repository into your wp-plugins directory
then activate the plugin.  By default the plugin uses the "free as in beer" version of the
player hosted from our CDN.

You'll also need to set up a config.js file and provide the URL for that. Likewise
a CSS file with the transcript CSS.

There's an example config.js included here. It assumes you're loading jQuery.

There are a number of options available in the admin settings page. Most of these
are defaults that can be over-ridden in the shortcode if you need to.

Two shortcodes are provided, "[ozplayer]" and "[video]". We over-ride the system
default "[video]" shortcode but are largely backwards-compatible -- this plugin is
an adaptation of the original built-in video support.

Here's an example, playing video from Amazon CloudFront. This is a direct copy from our website:

```
[ozplayer poster="/wp-content/uploads/2014/09/ozplayer-poster.png" mp4="http://dhjrqu8yhdp3e.cloudfront.net/media/ozplayer-small-3.mp4" webm="http://dhjrqu8yhdp3e.cloudfront.net/media/ozplayer.webm" captions="/wp-content/uploads/2014/08/ozplayer.vtt" transcript="/wp-content/uploads/2014/08/ozplayer-transcript.vtt" mp3="http://dhjrqu8yhdp3e.cloudfront.net/media/ozplayer.mp3" ogg="http://dhjrqu8yhdp3e.cloudfront.net/media/ozplayer.ogg"]
```

The following arguments are available:

| Name | Description
| ---- | ------------
| src | Source URL for the video. Recommend only using this for YouTube videos, but it works the same as the built-in [video] shortcode
| mp4 | URL for the MP4 version of the video
| webm | URL for the WebM version of the video
| mp3 | URL for the MP3 version of the audio descriptions
| ogg | URL for the OGG Vorbis version of the audio descriptions
| transcript | URL for the VTT file containing transcript extras
| captions | URL for the VTT file containing the captions
| color | Highlight color. See admin panel for options
| lang | Language. Right now only 'en' is supported but feel free to add translations in ozplayer-lang
| poster | URL for the poster image
| width | Width of the video. Will be scaled down if this is wider than the content area width
| height | Height of the video. Will be scaled down if the video is too wide for the content area, preserving the aspect ratio
| transcript_open | Have the transcript visible? ("1" or "yes" for on, anything else for off)
| transcript_on | Should the transcript be on at all? ("1" or "yes" for on, anything else for off)
| captions_on | Turn on the captions? ("1" or "yes" for on, anything else for off)
| ad_on | Turn on the audio descriptions? ("1" or "yes" for on, anything else for off)

All but a video source (either the src, mp4, or webm values) are optional.
