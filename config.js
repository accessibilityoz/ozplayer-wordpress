/*******************************************************************************
 Copyright (c) 2013-20 AccessibilityOz        http://www.accessibilityoz.com.au/
 ------------------------------------------------------------------------------
 OzPlayer [4.1] => configuration script
 ------------------------------------------------------------------------------
 *******************************************************************************/
(function () {

    //enable development settings
    //n.b. it may be helpful to have these true during development
    //then comment them or set to false when the site is published
    //(or just delete them, since both are false by default)
    //OzPlayer.define("alert-on-error",   true);
    //OzPlayer.define("captions-nocache", true);

    //initialise a video player, passing the player ID
    //n.b. you should initialise players as soon as possible after the markup
    //i.e. don't wait for window.onload or DOMContentLoaded or suchlike
    //new OzPlayer.Video("demo");
    OzPlayer.init();

    // add listener for play event
    // OzPlayer.addListener(function (event) {
	//
    //     console.log({
    //         "event": 'video',
    //         "player_id": event.media.id,
    //         "interaction": event.type,
    //         "video_url": event.media.src,
    //         "duration": event.media.duration,
    //         "player_type": event.media.renderer
    //     });
    // });

})();
