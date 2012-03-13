<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/mplayer.inc
// Author: Anders Lövgren
// Date:   2010-05-07
// 
// Support class for embedding media player in HTML page. The output is 
// based on examples from http://cit.ucsf.edu/embedmedia/step1.php
// 
// The media player factory:
// -----------------------------
// 
// The idea behind the media player factory is:
// 
// 1. Detect media type (MIME) for an URL.
// 2. Create the media player that supports the detected media type.
// 3. Let the media player output the HTML for embedded plugin.
// 
// Content types and media players:
// --------------------------------------
// 
// These are the media player supported by this file:
// 
// *) GenericMediaPlayer: 
// 
//    The generic media player is used as a standard media player for content
//    types not handled by other players.
// 
// *) QuickTimeMediaPlayer:
// 
//    A media player that handles the content type video/quicktime (*.moov, 
//    *.mov, *.qt and *.qtvr).
// 
// *) FlashMediaPlayer:
// 
//    Handles video/x-flv (*.flv) and application/x-shockwave-flash (*.swf) 
//    content type. 
// 
// *) WindowsMediaPlayer:
// 
//    Handles Windows media files (like *.wma , *.asf and *.avi/*.divx) and 
//    their associated content types.
// 
// *) VLCMediaPlayer:
// 
//    The VideoLAN media player (VLC) plugin.
//
// 
// Default settings:
// 
if (!defined("MEDIA_PLAYER_AUTOSTART")) {
        define("MEDIA_PLAYER_AUTOSTART", true);
}
if (!defined("MEDIA_PLAYER_LOOP")) {
        define("MEDIA_PLAYER_LOOP", false);
}
if (!defined("MEDIA_PLAYER_WIDTH")) {
        define("MEDIA_PLAYER_WIDTH", 320);
}
if (!defined("MEDIA_PLAYER_HEIGHT")) {
        define("MEDIA_PLAYER_HEIGHT", 240);
}
if (!defined("MEDIA_PLAYER_MIME_TYPE")) {
        define("MEDIA_PLAYER_MIME_TYPE", "application/octet-stream");
}

// 
// The media player exception thrown by classes defined in this file.
// 
class MediaPlayerException extends Exception
{

        function __construct($message)
        {
                parent::__construct($message, 0);
        }

        function __toString()
        {
                return $this->message;
        }

}

// 
// Support class for detecting the MIME types from an referenced media file 
// URL. This class can be used to detect the MIME type for providing the 
// correct media player plugin.
// 
class MediaMimeType
{

        private $url;
        private $ext;
        private $mime = MEDIA_PLAYER_MIME_TYPE;
        private static $map =
                array(
                "application/ogg" => array(
                        "ext" => array("ogx"),
                        "type" => "media",
                        "desc" => "Ogg Multimedia File"
                ),
                "application/ram" => array(
                        "ext" => array("ram"),
                        "type" => "media",
                        "desc" => "RealMedia MetaFile"
                ),
                "application/vnd.ms-asf" => array(
                        "ext" => array(""),
                        "type" => "media",
                        "desc" => "Microsoft Media Format"
                ),
                "application/vnd.rn-realmedia" => array(
                        "ext" => array("rm", "rmj", "rmm", "rms", "rmvb", "rmx"),
                        "type" => "media",
                        "desc" => "RealMedia Document"
                ),
                "application/x-ogg" => array(
                        "ext" => array(""),
                        "type" => "media",
                        "desc" => "Ogg Multimedia File"
                ),
                "application/xspf+xml" => array(
                        "ext" => array("xspf"),
                        "type" => "media",
                        "desc" => "XSPF Playlist"
                ),
                "application/x-shockwave-flash" => array(
                        "ext" => array("spl", "swf"),
                        "type" => "media",
                        "desc" => "Shockwave Flash File"
                ),
                "audio/aac" => array(
                        "ext" => array("aac"),
                        "type" => "audio",
                        "desc" => "AAC Sound"
                ),
                "audio/ac3" => array(
                        "ext" => array("ac3"),
                        "type" => "audio",
                        "desc" => "Dolby Digital Audio"
                ),
                "audio/flac" => array(
                        "ext" => array("flac"),
                        "type" => "audio",
                        "desc" => "FLAC Audio"
                ),
                "audio/m3u" => array(
                        "ext" => array(""),
                        "type" => "audio",
                        "desc" => "MP3 Audio"
                ),
                "audio/midi" => array(
                        "ext" => array("kar", "mid", "midi"),
                        "type" => "audio",
                        "desc" => "MIDI Audio"
                ),
                "audio/mp2" => array(
                        "ext" => array("mp2"),
                        "type" => "audio",
                        "desc" => "MP2 Audio"
                ),
                "audio/mp4" => array(
                        "ext" => array("aac", "m4a"),
                        "type" => "audio",
                        "desc" => "MPEG-4 Audio"
                ),
                "audio/mpeg" => array(
                        "ext" => array("mp3", "mpga"),
                        "type" => "audio",
                        "desc" => "MP3 Audio"
                ),
                "audio/ogg" => array(
                        "ext" => array("oga", "ogg", "spx"),
                        "type" => "audio",
                        "desc" => "Ogg Audio"
                ),
                "audio/vnd.rn-realaudio" => array(
                        "ext" => array("ra", "rax"),
                        "type" => "audio",
                        "desc" => "RealAudio Document"
                ),
                "audio/vorbis" => array(
                        "ext" => array(""),
                        "type" => "audio",
                        "desc" => "Vorbis Audio"
                ),
                "audio/x-aiff" => array(
                        "ext" => array("aif", "aifc", "aiff"),
                        "type" => "audio",
                        "desc" => "AIFF/Amiga/Mac Audio"
                ),
                "audio/x-aiffc" => array(
                        "ext" => array(""),
                        "type" => "audio",
                        "desc" => "AIFF Audio"
                ),
                "audio/x-flac+ogg" => array(
                        "ext" => array("ogg"),
                        "type" => "audio",
                        "desc" => "Ogg FLAC Audio"
                ),
                "audio/x-mpegurl" => array(
                        "ext" => array("m3u", "m3u8", "vlc"),
                        "type" => "audio",
                        "desc" => "MP3 Audio Streamed"
                ),
                "audio/x-ms-asx" => array(
                        "ext" => array("asx", "wax", "wmx", "wvx"),
                        "type" => "media",
                        "desc" => "Microsoft ASX Playlist"
                ),
                "audio/x-ms-wma" => array(
                        "ext" => array("wma"),
                        "type" => "audio",
                        "desc" => "Windows Media Audio"
                ),
                "audio/x-oggflac" => array(
                        "ext" => array("audio"),
                        "type" => "audio",
                        "desc" => "MP2 Audio"
                ),
                "audio/x-pn-realaudio" => array(
                        "ext" => array(""),
                        "type" => "audio",
                        "desc" => "RealAudio Plugin File"
                ),
                "audio/x-speex+ogg" => array(
                        "ext" => array("ogg"),
                        "type" => "audio",
                        "desc" => "Ogg Speex Audio"
                ),
                "audio/x-vorbis+ogg" => array(
                        "ext" => array("ogg"),
                        "type" => "audio",
                        "desc" => "Ogg Vorbis Audio"
                ),
                "audio/x-wav" => array(
                        "ext" => array("wav"),
                        "type" => "audio",
                        "desc" => "WAV Audio"
                ),
                "video/3gpp" => array(
                        "ext" => array("3g2", "3ga", "3gp", "3gpp"),
                        "type" => "media",
                        "desc" => "3GPP Multimedia File"
                ),
                "video/dv" => array(
                        "ext" => array("dv"),
                        "type" => "video",
                        "desc" => "DV Video"
                ),
                "video/mp4" => array(
                        "ext" => array("m4v", "mp4"),
                        "type" => "video",
                        "desc" => "MPEG-4 Video"
                ),
                "video/mpeg" => array(
                        "ext" => array("m2t", "mp2", "mpe", "mpeg", "vob"),
                        "type" => "video",
                        "desc" => "MPEG Video"
                ),
                "video/ogm+ogg" => array(
                        "ext" => array("ogm"),
                        "type" => "video",
                        "desc" => "OGM Video"
                ),
                "video/ogg" => array(
                        "ext" => array("ogg"),
                        "type" => "video",
                        "desc" => "Ogg Video"
                ),
                "video/quicktime" => array(
                        "ext" => array("moov", "mov", "qt", "qtvr"),
                        "type" => "video",
                        "desc" => "QuickTime Video"
                ),
                "video/vnd.rn-realvideo" => array(
                        "ext" => array("rv", "rvx"),
                        "type" => "video",
                        "desc" => "RealVideo Document"
                ),
                "video/x-flv" => array(
                        "ext" => array("flv"),
                        "type" => "video",
                        "desc" => "Flash Video"
                ),
                "video/x-ms-asf" => array(
                        "ext" => array("asf"),
                        "type" => "video",
                        "desc" => "ASF Video"
                ),
                "video/x-ms-wmp" => array(
                        "ext" => array("wmp"),
                        "type" => "media",
                        "desc" => "Microsoft Media Format"
                ),
                "video/x-ms-wmv" => array(
                        "ext" => array("wmv"),
                        "type" => "video",
                        "desc" => "Windows Media Video"
                ),
                "video/x-msvideo" => array(
                        "ext" => array("avi", "divx"),
                        "type" => "video",
                        "desc" => "AVI Video"
                ),
                "video/x-nsv" => array(
                        "ext" => array("nsv"),
                        "type" => "video",
                        "desc" => "NullSoft Video"
                ),
                "video/x-ogm" => array(
                        "ext" => array(""),
                        "type" => "video",
                        "desc" => "Ogg/Ogm Video"
                ),
                "video/x-ogm+ogg" => array(
                        "ext" => array("ogm"),
                        "type" => "video",
                        "desc" => "OGM Video"
                ),
                "video/x-sgi-movie" => array(
                        "ext" => array("movie"),
                        "type" => "video",
                        "desc" => "SGI Video"
                ),
                "video/x-theora+ogg" => array(
                        "ext" => array("ogg"),
                        "type" => "video",
                        "desc" => "Ogg Theora Video"
                ),
                "video/x-theora" => array(
                        "ext" => array(""),
                        "type" => "video",
                        "desc" => "Ogg/Ogm Video"
                ),
                "uri/rtspt" => array(
                        "ext" => array(""),
                        "type" => "media",
                        "desc" => "Realtime Streaming Protocol Transport"
                ),
                "uri/rtspu" => array(
                        "ext" => array(""),
                        "type" => "media",
                        "desc" => "Realtime Streaming Protocol URI List"
                )
        );

        public function __construct($url)
        {
                $this->url = $url;
                $this->ext = substr(strrchr($this->url, "."), 1);
        }

        //
        // Detect mime type from the HTTP server. Returns true if successful.
        //
        public function detect()
        {
                if (!extension_loaded("http")) {
                        throw new MediaPlayerException("Missing PECL HTTP extension");
                }

                $info = array();
                $response = http_head($this->url, null, $info);
                if (!$response) {
                        return false;
                }

                $this->mime = $info['content_type'];
                return true;
        }

        //
        // Try to lookup mime type from extension. Returns true if successful.
        //
        public function lookup()
        {
                if ($this->ext) {
                        foreach (self::$map as $mime => $attr) {
                                if (in_array($this->ext, $attr['ext'])) {
                                        $this->mime = $mime;
                                        return true;
                                }
                        }
                }
                return false;
        }

        //
        // Get the mime type.
        //
        public function getMimeType()
        {
                return $this->mime;
        }

        public function getMediaType()
        {
                return self::$map[$this->mime]['type'];
        }

        //
        // Get the URL extension.
        //
        public function getExtension()
        {
                return $this->ext;
        }

        //
        // Get mime type description.
        //
        public function getDescription()
        {
                return $this->map[$this->mime]['desc'];
        }

}

// 
// Outputs HTML for embedded media.
// 
class MediaPlayerOutput
{

        private $name;
        private $type;
        private $url;
        private $window = null;
        private $control = null;

        public function __construct($name, $type, $url)
        {
                $this->name = $name;
                $this->type = $type;
                $this->url = $url;
        }

        public function setWindow($obj)
        {
                $this->window = $obj;

                if (!isset($this->window->object)) {
                        $this->window->object = null;
                }
                if (!isset($this->window->param)) {
                        $this->window->param = array();
                }
                if (!isset($this->window->embed)) {
                        $this->window->embed = null;
                }
        }

        public function setControl($obj)
        {
                $this->control = $obj;

                if (!isset($this->control->object)) {
                        $this->control->object = null;
                }
                if (!isset($this->control->param)) {
                        $this->control->param = array();
                }
                if (!isset($this->control->embed)) {
                        $this->control->embed = null;
                }
        }

        //
        // Print object tag. The embedded media must be defined inside the object
        // tags, but if object attributes are missing (null), then we simply skip
        // the object tags and print the embedded block only.
        //
        // If we output object tags, then it must include a classid attribute, or
        // the embedded section will be silently ignored, even if the browser fails
        // to render the object tag.
        //
        private function object($type, $attr, $params, $embed)
        {
                if (isset($attr)) {
                        // printf("<object class=\"media %s\" ", $type);
                        printf("<object ");
                        foreach ($attr as $name => $value) {
                                if (is_bool($value)) {
                                        printf("%s=\"%s\" ", $name, $value ? "true" : "false");
                                } elseif (is_numeric($value)) {
                                        printf("%s=\"%d\" ", $name, $value);
                                } else {
                                        printf("%s=\"%s\" ", $name, $value);
                                }
                        }
                        printf(">\n");
                        foreach ($params as $name => $value) {
                                if (is_bool($value)) {
                                        printf("<param name=\"%s\" value=\"%s\" />\n", $name, $value ? "true" : "false");
                                } elseif (is_numeric($value)) {
                                        printf("<param name=\"%s\" value=\"%d\" />\n", $name, $value);
                                } else {
                                        printf("<param name=\"%s\" value=\"%s\" />\n", $name, $value);
                                }
                        }
                }
                if (isset($embed)) {
                        self::embed($type, $embed);
                }
                if (isset($attr)) {
                        printf("</object>\n");
                }
        }

        private function embed($type, $attr)
        {
                printf("<embed class=\"media %s\" ", $type);
                foreach ($attr as $name => $value) {
                        if (is_bool($value)) {
                                printf("%s=\"%s\" ", $name, $value ? "true" : "false");
                        } elseif (is_numeric($value)) {
                                printf("%s=\"%d\" ", $name, $value);
                        } else {
                                printf("%s=\"%s\" ", $name, $value);
                        }
                }
                printf("/>\n");
        }

        //
        // Output plugin HTML for embedded media file.
        //
        public function send($object = null)
        {
                printf("<!-- Begin embedded %s file -->\n", $this->name);

                if (isset($this->window)) {
                        printf("<!-- Begin %s window -->\n", $this->type);
                        self::object($this->type,
                                        $this->window->object,
                                        $this->window->param,
                                        $this->window->embed);
                        printf("<!-- End %s window -->\n", $this->type);
                }

                if (isset($this->control)) {
                        printf("<!-- Begin %s controller -->\n", $this->type);
                        self::object($this->type,
                                        $this->control->object,
                                        $this->control->param,
                                        $this->control->embed);
                        printf("<!-- End %s controller -->\n", $this->type);
                }

                if (isset($object)) {
                        $object->control();
                }

                printf("<!-- Begin link to launch external media player -->\n");
                printf("<br/><a href=\"%s\" title=\"%s\" target=\"_blank\">%s</a>\n", $this->url,
                        _("Click to open the URL in an external media player"),
                        _("External Media Player"));
                printf("<!-- End link to launch external media player -->\n");

                printf("<!-- End embedded %s file -->\n", $this->name);
        }

}

// 
// The interface for all media players.
// 
interface MediaPlayer
{

        //
        // Generic settings:
        //
        public function setAutoStart($bool);

        public function setWidth($num);

        public function setHeight($num);

        public function setLoop($bool);

        public function getUrl();

        public function getAutoStart();

        public function getWidth();

        public function getHeight();

        public function getLoop();

        public function getMimeType();

        //
        // Plugin specific:
        //
        public function getPluginPage();     // URL

        public function getPluginType();     // MIME type
        //
        // Prints out the HTML code:
        //

        public function output();
}

// 
// Abstract base class for media players.
// 
abstract class AbstractMediaPlayer implements MediaPlayer
{

        //
        // Provides unique ID for player.
        //
        private static $counter = 0;
        //
        // Common properties and settings:
        //
        protected $mime;
        protected $url;
        protected $autostart = MEDIA_PLAYER_AUTOSTART;
        protected $height = MEDIA_PLAYER_HEIGHT;
        protected $width = MEDIA_PLAYER_WIDTH;
        protected $loop = MEDIA_PLAYER_LOOP;
        //
        // Plugin specific:
        //
        protected $pluginpage = null;
        protected $plugintype = null;

        protected function __construct($url, $detect)
        {
                $this->url = $url;

                if ($detect) {
                        $this->mime = new MediaMimeType($this->url);
                        if (!$this->mime->detect()) {
                                $this->mime->lookup();
                        }
                }
                $this->playerid = self::$counter++;
        }

        protected function generateClassId()
        {
                $md5 = md5(uniqid(rand(), true));
                $str = sprintf("%s-%s-%s-%s-%s", substr($md5, 0, 8), substr($md5, 8, 4), substr($md5, 12, 4), substr($md5, 16, 4), substr($md5, 20, 12));
                return $str;
        }

        function setMimeType($mime)
        {
                $this->mime = $mime;
        }

        public function setAutoStart($bool)
        {
                $this->autostart = $bool;
        }

        public function setWidth($num)
        {
                $this->width = $num;
        }

        public function setHeight($num)
        {
                $this->height = $num;
        }

        public function setLoop($bool)
        {
                $this->loop = $bool;
        }

        public function getUrl()
        {
                return $this->url;
        }

        public function getAutoStart()
        {
                return $this->autostart;
        }

        public function getWidth()
        {
                return $this->width;
        }

        public function getHeight()
        {
                return $this->height;
        }

        public function getLoop()
        {
                return $this->loop;
        }

        public function getMimeType()
        {
                return $this->mime;
        }

        public function getPluginPage()
        {
                return $this->pluginpage;
        }

        public function getPluginType()
        {
                return $this->plugintype;
        }

}

// 
// The generic media player.
// 
class GenericMediaPlayer extends AbstractMediaPlayer
{

        public function __construct($url = null, $detect = true)
        {
                parent::__construct($url, $detect);
        }

        //
        // Output HTML for the video window:
        //
        public function output()
        {
                $media->name = "Generic";
                $media->type = $this->mime->getMediaType();
                $media->url = $this->url;

                $window->embed->src = $this->url;
                $window->embed->width = $this->width;
                $window->embed->height = $this->height;
                $window->embed->name = sprintf("%s%d", $media->type, $this->playerid);
                $window->embed->type = $this->mime->getMimeType();
                $window->embed->controls = "controlpanel";
                $window->embed->controller = true;
                $window->embed->loop = $this->loop;
                $window->embed->autostart = $this->autostart;

                $output = new MediaPlayerOutput($media->name, $media->type, $media->url);
                $output->setWindow($window);

                $output->send();
        }

}

// 
// Apple QuickTime Player.
// 
class QuickTimeMediaPlayer extends AbstractMediaPlayer
{
        const classid = "clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B";
        const codebase = "http://www.apple.com/qtactivex/qtplugin.cab";

        public function __construct($url = null, $detect = true)
        {
                parent::__construct($url, $detect);

                $this->pluginpage = "http://www.apple.com/quicktime/download/";
        }

        public function output()
        {
                $media->name = "QuickTime";
                $media->type = $this->mime->getMediaType();
                $media->url = $this->url;

                $window->object->classid = self::classid;
                $window->object->codebase = self::codebase;
                $window->object->height = $this->height;
                $window->object->width = $this->width;

                $window->param->src = $this->url;
                $window->param->autoplay = $this->autostart;
                $window->param->controller = true;
                $window->param->loop = $this->loop;

                $window->embed->src = $this->url;
                $window->embed->autoplay = $this->autostart;
                $window->embed->controller = true;
                $window->embed->loop = $this->loop;
                $window->embed->pluginspage = $this->pluginpage;
                $window->embed->showlogo = true;
                $window->embed->scale = "tofit";

                $output = new MediaPlayerOutput($media->name, $media->type, $media->url);
                $output->setWindow($window);

                $output->send();
        }

}

// 
// Microsoft Windows Media Player.
// 
class WindowsMediaPlayer extends AbstractMediaPlayer
{
        const objectid = "mediaPlayer";
        const classid = "clsid:22d6f312-b0f6-11d0-94ab-0080c74c7e95";
        const codebase = "http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701";

        public function __construct($url = null, $detect = true)
        {
                parent::__construct($url, $detect);

                $this->plugintype = "application/x-oleobject";
                $this->pluginpage = "http://microsoft.com/windows/mediaplayer/en/download/";
        }

        public function output()
        {
                $media->name = "WindowsMedia";
                $media->type = $this->mime->getMediaType();
                $media->url = $this->url;

                $window->object->id = self::objectid;
                $window->object->classid = self::classid;
                $window->object->codebase = self::codebase;
                $window->object->type = $this->plugintype;
                $window->object->standby = _("Loading Microsoft Windows Media Player components...");
                $window->object->height = $this->height;
                $window->object->width = $this->width;

                $window->param->fileName = $this->url;
                $window->param->animationatStart = true;
                $window->param->transparentatStart = true;
                $window->param->autoStart = $this->autostart;
                $window->param->showControls = true;
                $window->param->loop = $this->loop;

                $window->embed->type = "application/x-mplayer2";
                $window->embed->id = self::objectid;
                $window->embed->name = self::objectid;
                $window->embed->displaysize = 4;
                $window->embed->autosize = -1;
                $window->embed->bgcolor = "darkblue";
                $window->embed->showcontrols = true;
                $window->embed->showtracker = -1;
                $window->embed->showdisplay = 0;
                $window->embed->showstatusbar = -1;
                $window->embed->videoborder3d = -1;
                $window->embed->src = $this->url;
                $window->embed->autostart = $this->autostart;
                $window->embed->designtimesp = 5311;
                $window->embed->loop = $this->loop;

                $output = new MediaPlayerOutput($media->name, $media->type, $media->url);
                $output->setWindow($window);

                $output->send();
        }

}

// 
// The media player for Read Media content.
// 
class RealMediaPlayer extends AbstractMediaPlayer
{
        const objectid = "rvocx";
        const classid = "clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA";

        public function __construct($url = null, $detect = true)
        {
                parent::__construct($url, $detect);

                $this->plugintype = "audio/x-pn-realaudio-plugin";
        }

        public function output()
        {
                $media->name = "RealMedia";
                $media->type = $this->mime->getMediaType();
                $media->url = $url;

                $window->object->id = self::objectid;
                $window->object->classid = self::classid;

                $window->param->src = $this->url;
                $window->param->autostart = $this->autostart;
                $window->param->controls = "imagewindow";
                $window->param->console = $media->type;
                $window->param->loop = $this->loop;

                $window->embed->src = $this->url;
                $window->embed->loop = $this->loop;
                $window->embed->type = $this->plugintype;
                $window->embed->controls = "imagewindow";
                $window->embed->console = $media->type;
                $window->embed->autostart = $this->autostart;

                $control->object->id = self::objectid;
                $control->object->classid = self::classid;

                $control->param->src = $this->url;
                $control->param->autostart = $this->autostart;
                $control->param->controls = "ControlPanel";
                $control->param->console = $media->type;

                $control->embed->src = $this->url;
                $control->embed->controls = "ControlPanel";
                $control->embed->type = $this->plugintype;
                $control->embed->console = $media->type;
                $control->embed->autostart = $this->autostart;

                $output = new MediaPlayerOutput($media->name, $media->type, $media->url);
                $output->setWindow($window);
                $output->setControl($control);

                $output->send();
        }

}

// 
// Macromedia Flash version 6.
// 
class FlashMediaPlayer extends AbstractMediaPlayer
{
        const classid = "clsid:D27CDB6E-AE6D-11CF-96B8-444553540000";
        const codebase = "http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0";

        const quality = "high";
        const bgcolor = "#ffffff";

        public function __construct($url = null, $detect = true)
        {
                parent::__construct($url, $detect);

                $this->plugintype = "application/x-shockwave-flash";
                $this->pluginpage = "http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash";
        }

        public function output()
        {
                //
                // FLV videos requires an player while SWF is flash video with the
                // player builtin. We need to take this in account when generating
                // the HTML output.
                //

                if ($this->mime->getMimeType() == "video/x-flv") {
                        $player->name = "flowplayer";
                        $player->url = sprintf("%s/media/flowplayer/flowplayer-3.2.0.swf", BASE_URL);
                        $player->conf = sprintf("config={'clip':{'url':'%s','autoPlay':%s,'scaling':'fit'}}",
                                        $this->url, $this->autostart ? "true" : "false");

                        $media->name = "Flash";
                        $media->type = $this->mime->getMediaType();
                        $media->url = $this->url;

                        $window->object->id = $player->name;
                        $window->object->classid = self::classid;
                        $window->object->codebase = self::codebase;
                        $window->object->width = $this->width;
                        $window->object->height = $this->height;

                        $window->param->movie = $player->url;
                        $window->param->quality = self::quality;
                        $window->param->bgcolor = self::bgcolor;
                        $window->param->loop = $this->loop;
                        $window->param->allowfullscreen = true;
                        $window->param->flashvars = $player->conf;

                        $window->embed->src = $player->url;
                        $window->embed->quality = self::quality;
                        $window->embed->bgcolor = self::bgcolor;
                        $window->embed->width = $this->width;
                        $window->embed->height = $this->height;
                        $window->embed->loop = $this->loop;
                        $window->embed->type = $this->plugintype;
                        $window->embed->pluginspage = $this->pluginpage;
                        $window->embed->allowfullscreen = true;
                        $window->embed->flashvars = $player->conf;

                        $output = new MediaPlayerOutput($media->name, $media->type, $media->url);
                        $output->setWindow($window);

                        $output->send();
                } else {
                        $media->name = "Flash";
                        $media->type = $this->mime->getMediaType();
                        $media->url = $this->url;

                        $window->object->classid = self::classid;
                        $window->object->codebase = self::codebase;
                        $window->object->width = $this->width;
                        $window->object->height = $this->height;

                        $window->param->movie = $this->url;
                        $window->param->quality = self::quality;
                        $window->param->bgcolor = self::bgcolor;
                        $window->param->loop = $this->loop;
                        $window->param->allowfullscreen = true;

                        $window->embed->src = $this->url;
                        $window->embed->quality = self::quality;
                        $window->embed->bgcolor = self::bgcolor;
                        $window->embed->width = $this->width;
                        $window->embed->height = $this->height;
                        $window->embed->loop = $this->loop;
                        $window->embed->type = $this->plugintype;
                        $window->embed->pluginspage = $this->pluginpage;
                        $window->embed->allowfullscreen = true;

                        $output = new MediaPlayerOutput($media->name, $media->type, $media->url);
                        $output->setWindow($window);

                        $output->send();
                }
        }

}

// 
// The VideoLAN (VLC) media player.
// 
class VLCMediaPlayer extends AbstractMediaPlayer
{
        const objectid = "vlc";
        const version = "VideoLAN.VLCPlugin.2";

        public function __construct($url = null, $detect = true)
        {
                parent::__construct($url, $detect);

                $this->plugintype = "application/x-vlc-plugin";
                $this->pluginpage = "http://www.videolan.org";
        }

        public function output()
        {
                $media->name = "VideoLAN (VLC)";
                $media->type = $this->mime->getMediaType();
                $media->url = $this->url;

                $window->embed->type = $this->plugintype;
                $window->embed->pluginspage = $this->pluginpage;
                $window->embed->version = self::version;
                $window->embed->id = self::objectid;
                $window->embed->width = $this->width;
                $window->embed->height = $this->height;
                $window->embed->target = $this->url;
                $window->embed->name = sprintf("%s%d", $media->type, $this->playerid);
                $window->embed->loop = $this->loop;
                $window->embed->autoplay = $this->autostart;

                $output = new MediaPlayerOutput($media->name, $media->type, $media->url);
                $output->setWindow($window);

                $this->control = "control";
                $output->send($this);
        }

        //
        // Outputs the custom control panel:
        //
        public function control()
        {
                $panel = array("vlc-play" => array(
                                array(
                                        "func" => "play()",
                                        "icon" => "media-playback-start.png",
                                        "title" => _("Start playback"),
                                ),
                                array(
                                        "func" => "pause()",
                                        "icon" => "media-playback-pause.png",
                                        "title" => _("Pause playback")
                                ),
                                array(
                                        "func" => "stop()",
                                        "icon" => "media-playback-stop.png",
                                        "title" => _("Stop playback")
                                )
                        ),
                        "vlc-seek" => array(
                                array(
                                        "func" => "backward()",
                                        "icon" => "media-seek-backward.png",
                                        "title" => _("Seek backward in media stream")
                                ),
                                array(
                                        "func" => "forward()",
                                        "icon" => "media-seek-forward.png",
                                        "title" => _("Seek forward in media stream")
                                )
                        ),
                        "vlc-screen" => array(
                                array(
                                        "func" => "fullscreen()",
                                        "icon" => "view-fullscreen.png",
                                        "title" => _("Switch to fullscreen mode"),
                                        "media" => "video"
                                )
                        )
                );

                printf("<script type=\"text/javascript\" language=\"javascript\">\n");
                include("js/vlc.js");
                printf("</script>\n");

                printf("<div class=\"vlc-ctrl\">\n");
                foreach ($panel as $group => $buttons) {
                        printf("<div class=\"%s\">\n", $group);
                        foreach ($buttons as $button) {
                                if (!isset($button['media']) || $button['media'] == $this->mime->getMediaType()) {
                                        printf("<img src=\"/openexam/icons/player/%s\" onclick=\"javascript:%s\" title=\"%s\" />\n",
                                                $button['icon'], $button['func'], $button['title']);
                                }
                        }
                        printf("</div>\n");
                }
                printf("</div>\n");
        }

}

// 
// This class inspects the MIME type referenced by the URL and returns a
// media player object suitable for playing that type of media.
// 
class MediaPlayerFactory
{

        public static function createMediaPlayer($url)
        {
                $mime = new MediaMimeType($url);
                if (!$mime->detect()) {
                        $mime->lookup();
                }

                //
                // See if system manager has tweaks:
                //
                if (class_exists('MediaPlayerConfig')) {
                        $config = new MediaPlayerConfig();
                        $mptype = $config->getPlayerId($mime->getMimeType());
                        if ($mptype != MEDIA_PLAYER_BUILTIN) {
                                $player = null;
                                switch ($mptype) {
                                        case MEDIA_PLAYER_WINDOWS:
                                                $player = new WindowsMediaPlayer($url, false);
                                                break;
                                        case MEDIA_PLAYER_QUICKTIME:
                                                $player = new QuickTimeMediaPlayer($url, false);
                                                break;
                                        case MEDIA_PLAYER_REALPLAY:
                                                $player = new RealMediaPlayer($url, false);
                                                break;
                                        case MEDIA_PLAYER_ADOBE_FLASH:
                                                $player = new FlashMediaPlayer($url, false);
                                                break;
                                        case MEDIA_PLAYER_VIDEOLAN:
                                                $player = new VLCMediaPlayer($url, false);
                                                break;
                                        case MEDIA_PLAYER_GENERIC:
                                                $player = new GenericMediaPlayer($url, false);
                                                break;
                                }
                                $player->setMimeType($mime);
                                return $player;
                        }
                }

                switch ($mime->getMimeType()) {
                        case "application/ram":
                        case "application/vnd.rn-realmedia":
                        case "audio/vnd.rn-realaudio":
                        case "audio/x-pn-realaudio-plugin":
                        case "video/vnd.rn-realvideo":
                        case "uri/rtspt":
                        case "uri/rtspu":
                                $player = new RealMediaPlayer($url, false);
                                $player->setMimeType($mime);
                                return $player;
                        case "application/vnd.ms-asf":
                        case "audio/x-ms-asx":
                        case "audio/x-ms-wma":
                        case "video/x-ms-asf":
                        case "video/x-ms-wmp":
                        case "video/x-ms-wmv":
                        case "video/x-msvideo":
                                $player = new WindowsMediaPlayer($url, false);
                                $player->setMimeType($mime);
                                return $player;
                        case "application/x-shockwave-flash":
                        case "video/x-flv":
                                $player = new FlashMediaPlayer($url, false);
                                $player->setMimeType($mime);
                                return $player;
                        case "video/quicktime":
                                $player = new QuickTimeMediaPlayer($url, false);
                                $player->setMimeType($mime);
                                return $player;
                        case "application/ogg":
                        case "application/x-ogg":
                        case "application/xspf+xml":
                        case "audio/aac":
                        case "audio/ac3":
                        case "audio/flac":
                        case "audio/m3u":
                        case "audio/midi":
                        case "audio/mp2":
                        case "audio/mp4":
                        case "audio/mpeg":
                        case "audio/ogg":
                        case "audio/vorbis":
                        case "audio/x-aiff":
                        case "audio/x-aiffc":
                        case "audio/x-flac+ogg":
                        case "audio/x-mpegurl":
                        case "audio/x-oggflac":
                        case "audio/x-speex+ogg":
                        case "audio/x-vorbis+ogg":
                        case "audio/x-wav":
                        case "video/3gpp":
                        case "video/dv":
                        case "video/mp4":
                        case "video/mpeg":
                        case "video/ogg":
                        case "video/ogm+ogg":
                        case "video/x-nsv":
                        case "video/x-ogm":
                        case "video/x-ogm+ogg":
                        case "video/x-sgi-movie":
                        case "video/x-theora":
                        case "video/x-theora+ogg":
                                $player = new GenericMediaPlayer($url, false);
                                $player->setMimeType($mime);
                                return $player;
                        default:
                                $player = new GenericMediaPlayer($url, false);
                                $player->setMimeType($mime);
                                return $player;
                }
        }

}

?>
