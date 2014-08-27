/*******************************************************************************
 Copyright (c) 2013-4 AccessibilityOz        http://www.accessibilityoz.com.au/
 ------------------------------------------------------------------------------
 OzPlayer [1.76] => configuration script
 ------------------------------------------------------------------------------
*******************************************************************************/
(function()
{

	
	//enable development settings
	//n.b. it may be helpful to have these true during development
	//then comment them or set to false when the site is published
	//(or just delete them, since both are false by default)
	//OzPlayer.define("alert-on-error",   	true);
	//OzPlayer.define("captions-nocache",  	true);
		
	//optionally re-define universal config 
	//n.b. these examples are not required, they're just examples
	//(and all these sample values are the same as their defaults)
	OzPlayer.define("default-volume",		0.7); 					//video and audio default volume (float from 0 to 1)
	OzPlayer.define("default-width",		400); 					//default width if <video width> is not defined (pixels gte 400)
	OzPlayer.define("default-height",		225); 					//default height if <video height> is not defined (pixels gte 225)
	OzPlayer.define("auto-hiding-delay", 	4); 					//auto-hiding delay for stack controls and skip links (float seconds, or zero to disable auto-hiding)
	OzPlayer.define("user-persistence", 	"ozplayer-userdata"); 	//user data persistence key (or empty-string to disable persistence)
	OzPlayer.define("allow-fullscreen", 	true); 					//add a fullscreen button (where supported by the browser)
	
	
	
	//initialise a video player, passing the player ID
	//n.b. you should initialise players as soon as possible after the markup
	//i.e. don't wait for window.onload or DOMContentLoaded or suchlike
	jQuery(".ozplayer").each(function() {
		new OzPlayer.Video(jQuery(this).attr("id"));
	});

})();
