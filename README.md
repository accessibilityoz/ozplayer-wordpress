ozplayer-wordpress
==================

OzPlayer support for WordPress.

Note that while this plugin is released under GPLv2 the OzPlayer accessible
video player is commercial software. Please see http://oz-player.com/ for
information.

This code is in an alpha state.

To use, copy the "ozplayer" directory from this repository into your wp-plugins directory
then activate the plugin. You'll also need to put your copy of the OzPlayer application
somewhere. The default assumption is that you've unpacked the -distrib.zip file in your
WordPress site's top-level directory, and then made a symlink to 'ozplayer'.

e.g.:

  cd /var/www/html
  unzip ~/OzPlayer-1.7-distrib.zip
  ln -s OzPlayer-1.7-distrib ozplayer

But if you put it elsewhere that's fine, just go in to the OzPlayer settings page
in the WordPress admin and set the "OzPlayer base URL" appropriately.

You'll also need to set up a config.js file and provide the URL for that. Likewise
a CSS file with the transcript CSS.

There's an example config.js included here. It assumes you're loading jQuery.
