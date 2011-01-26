<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/media.inc
// Author: Anders Lövgren
// Date:   2011-01-20
//
// Support classes for handling listing and upload of files to the media
// library. The media library is located under source/media and provides
// web resources to be used in exam questions, both as media URL's and
// embedded in the question body itself.
//
// The media library layout is:
//
// media/
//    +-- audio/
//    |      +-- {NN}/
//    |      +-- {NN}/
//    +-- image/
//    |      +-- ...
//   ...
//    +-- video/
//    |      +-- ...
//   ...
//
// Where {NN} denotes the unique ID of the examination.
//

if (!defined('MEDIA_UPLOAD_ENABLE')) {
        define('MEDIA_UPLOAD_ENABLE', false);
}
if (!defined('MEDIA_UPLOAD_DIR')) {
        define('MEDIA_UPLOAD_DIR', realpath(sprintf("%s/../source/media/files", dirname(__FILE__))));
}

class MediaException extends Exception
{

        public function __toString()
        {
                return $this->getMessage();
        }

}

class MediaFile
{

        private $exam;
        private $sect;
        private $name;

        public function __construct($exam, $sect, $name)
        {
                $this->exam = $exam;
                $this->sect = $sect;
                $this->name = $name;
        }

        public function __get($name)
        {
                if ($name == "url") {
                        return sprintf("%s/media/files/%s/%d/%s",
                                BASE_URL, $this->sect, $this->exam, $this->name);
                } elseif ($name == "path") {
                        return sprintf("%s/%s/%d/%s",
                                MEDIA_UPLOAD_DIR, $this->sect, $this->exam, $this->name);
                } else {
                        return $this->$name;
                }
        }

}

//
// This class manage the media library for a single examination.
// 
class MediaLibrary
{
        const audio = "audio";
        const image = "image";
        const video = "video";
        const media = "files";  // All media files at once

        private $exam;
        private $root;          // Root directory

        public function __construct($exam)
        {
                $this->exam = $exam;
                $this->root = MEDIA_UPLOAD_DIR;
        }

        //
        // This property provides read-only access. The name is one of the
        // section constants defined in this class.
        //
        public function __get($name)
        {
                switch ($name) {
                        case "audio":
                        case "image":
                        case "video":
                                return $this->files($name);
                        case "files":
                                $sect = array(self::audio, self::image, self::video);
                                $data = array();
                                foreach ($sect as $name) {
                                        $data[$name] = $this->files($name);
                                }
                                return $data;
                        default:
                                return $this->$name;
                }
        }

        //
        // Return an array of all files found under the given section. Each
        // file is represented by an media file object.
        //
        private function files($name)
        {
                $dir = realpath(sprintf("%s/%s/%d", $this->root, $name, $this->exam));
                $arr = array();

                if (file_exists($dir) && is_dir($dir)) {
                        if (($handle = opendir($dir))) {
                                while (($file = readdir($handle)) !== false) {
                                        if ($file == "." || $file == "..") {
                                                continue;
                                        }
                                        $arr[] = new MediaFile($this->exam, $name, $file);
                                }
                                closedir($handle);
                        }
                }
                return $arr;
        }

        //
        // Handles file upload to this exams media library.
        //
        // The $name parameter is the form name of the file upload field. The
        // $type parameter can be used to set to one of the media filetype
        // constants.
        //
        // Returns the object of the uploaded file.
        //
        public function add($name, $type = null)
        {
                $upload = new MediaUpload($this->exam, $name, $type);
                $upload->handle();
                return $upload;
        }

        //
        // Delete the named file from the media library. The file must exist
        // under the given section ($type).
        //
        public function delete($name, $type)
        {
                $path = sprintf("%s/%s/%d/%s", $this->root, $type, $this->exam, $name);
                if (file_exists($path)) {
                        if (!unlink($path)) {
                                throw new MediaException(sprintf(_("Failed delete file %s from media library."), $name));
                        }
                } else {
                        throw new MediaException(sprintf(_("The file %s can't be deleted because it don't exists."), $name));
                }
        }

}

//
// This class handles a single uploaded file.
//
class MediaUpload
{

        private $exam;          // The exam ID
        private $name;          // File upload name
        private $root;          // Root directory
        private $type;          // The media type (audio, image or video)
        private $mime = null;   // The MIME type
        private $path = null;   // Destination path (file system)
        private $file = null;   // Name of uploaded file
        private $url = null;    // Destination URL

        public function __construct($exam, $name, $type = null)
        {
                $this->exam = $exam;
                $this->name = $name;
                $this->type = $type;
                $this->root = MEDIA_UPLOAD_DIR;
        }

        public function __get($name)
        {
                return $this->$name;
        }

        //
        // Handles file upload to this exams media library. The $name parameter
        // is the form name of the file upload field.
        //
        public function handle()
        {
                //
                // TODO: encapsulate all file uploads in a separate class.
                //
                //
                // Sanity check first:
                //
                if (!MEDIA_UPLOAD_ENABLE || ini_get("file_uploads") == 0) {
                        throw new MediaException(_("Uploading files is not permitted by the system configuration."));
                }
                if (isset($_FILES[$this->name]['error'])) {
                        switch ($_FILES[$this->name]['error']) {
                                case UPLOAD_ERR_INI_SIZE:
                                        throw new MediaException("The uploaded file exceeds the upload_max_filesize directive in php.ini.");
                                case UPLOAD_ERR_FORM_SIZE:
                                        throw new MediaException("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
                                case UPLOAD_ERR_PARTIAL:
                                        throw new MediaException("The uploaded file was only partially uploaded.");
                                case UPLOAD_ERR_NO_FILE:
                                        throw new MediaException("No file was uploaded.");
                                case UPLOAD_ERR_NO_TMP_DIR:
                                        throw new MediaException("Missing a temporary folder.");
                                case UPLOAD_ERR_CANT_WRITE:
                                        throw new MediaException("Failed to write file to disk.");
                                case UPLOAD_ERR_EXTENSION:
                                        throw new MediaException("A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.");
                        }
                }
                if (!is_uploaded_file($_FILES[$this->name]['tmp_name'])) {
                        throw new MediaException(_("The file don't reference an uploaded file, possible file attack."));
                } else {
                        $this->file = $_FILES[$this->name]['name'];
                        $this->temp = $_FILES[$this->name]['tmp_name'];
                }

                //
                // Create the root directory if missing:
                //
                if (!file_exists($this->root)) {
                        if (!mkdir($this->root, 0777, true)) {
                                throw new MediaException(_("Failed create the media directory."));
                        }
                }

                //
                // Move the uploaded file to the root directory for media files:
                //
                $this->dest = sprintf("%s/%s", $this->root, $this->file);
                if (!move_uploaded_file($this->temp, $this->dest)) {
                        throw new MediaException(_("Failed move uploaded file."));
                }

                //
                // Try to detect media type (MIME).
                //
                if (!isset($this->type)) {
                        if (isset($_FILES[$this->name]['type'])) {
                                $this->mime = $_FILES[$this->name]['type'];
                                $this->type = $this->section($this->mime);
                        }
                        if (!isset($this->type)) {
                                $this->url = sprintf("%s/media/files/%s", BASE_URL, basename($this->dest));
                                $mime = new MediaMimeType($this->url);
                                if ($mime->detect()) {
                                        $this->mime = $mime->getMimeType();
                                        $this->type = $this->section($this->mime);
                                }
                        }
                }
                if (!isset($this->mime)) {
                        $this->url = sprintf("%s/media/files/%s", BASE_URL, basename($this->temp));
                        $mime = new MediaMimeType($this->url);
                        if ($mime->detect()) {
                                $this->mime = $mime->getMimeType();
                        }
                }

                //
                // Verify the media type.
                //
                if (!isset($this->mime)) {
                        unlink($this->dest);
                        throw new MediaException(_("Unknown MIME-type of upload file."));
                }
                if (!isset($this->type)) {
                        unlink($this->dest);
                        throw new MediaException(sprintf(_("Failed to classify the uploaded media file as either audio, image or video based on its MIME-type (%s). Please retry the file upload operation with an explicit set content type."), $this->mime));
                }

                //
                // Create directory structure and move the uploaded file now
                // that we have all information, like the proper section name.
                //
                $this->path = sprintf("%s/%s/%d", $this->root, $this->type, $this->exam);
                if (!file_exists($this->path)) {
                        if (!mkdir($this->path, 0777, true)) {
                                unlink($this->dest);
                                throw new MediaException(_("Failed create the media directory."));
                        }
                }
                $this->path = sprintf("%s/%s", $this->path, $this->file);
                if (!rename($this->dest, $this->path)) {
                        unlink($this->dest);
                        throw new MediaException(_("Failed move uploaded file."));
                }

                //
                // Set common properties:
                //
                $this->url = sprintf("%s/media/files/%s/%d/%s", BASE_URL, $this->type, $this->exam, $this->file);

                unset($this->dest);
                unset($this->temp);
        }

        //
        // Classify the MIME-type as either audio, image or video. Returns null
        // if the MIME-class don't belongs to one of these.
        //
        private function section($mime)
        {

                $matches = array();
                $pattern = "/^(audio|image|video)\/.*$/";

                if (preg_match($pattern, $mime, $matches)) {
                        return $matches[1];
                } else {
                        return null;
                }
        }

}

//
// Connects a form user interface with the media library. This class allows
// the user to select media files from popup frames.
//
class MediaSelect
{

        private $media;

        public function __construct($exam)
        {
                $this->media = new MediaLibrary($exam);
        }

        public function prepare()
        {
                printf("<script language=\"javascript\">\n");
                printf("var media_target=0;\n");
                printf("var media_select=null;\n");
                printf("function close_media() {\n");
                printf("    media_select.style.visibility = 'hidden';\n");
                printf("    return false;\n");
                printf("}\n");
                printf("function show_media(id,div,event) {\n");
                printf("    if(media_select) {\n");
                printf("        close_media();\n");
                printf("    }\n");
                printf("    media_target = id;\n");
                printf("    media_select = document.getElementById(div);\n");
                printf("    media_select.style.visibility = 'visible';\n");
                printf("    move_object(media_select,event);\n");
                printf("    return false;\n");
                printf("}\n");
                printf("function insert_media(obj,div) {\n");
                printf("    var target = document.getElementById(media_target);\n");
                printf("    target.value = obj.href;\n");
                printf("    close_media()\n");
                printf("    return false;\n");
                printf("}\n");
                printf("</script>\n");
        }

        //
        // Connects an event source with its target. The sect argument is the
        // media files section to connect with the event source, and defaults
        // to target is missing. The target is the destination of the selected
        // media file.
        // 
        // Both target and source should be HTML element ID's. This function
        // returns an javascript event handler to be used to select one of the
        // media files.
        //
        public function connect($target, $source, $sect = null)
        {
                if (!isset($sect)) {
                        $sect = $target;
                }
                $name = md5($sect);

                printf("<div id=\"%s\" class=\"mediamenu\">\n", $name);
                printf("<span id=\"menuhead\">%s:</span>\n", _("Click to insert URL"));
                printf("<ul>\n");
                foreach ($this->media->$sect as $file) {
                        $title = sprintf("%s: %d bytes\n%s: %s",
                                        _("Size"), 
                                        filesize($file->path),
                                        _("Modified"),
                                        strftime(DATETIME_FORMAT, filemtime($file->path)));
                        printf("<li><a href=\"%s\" onclick=\"return insert_media(this,'%s');\" title=\"%s\">%s</a></li>\n",
                                $file->url,
                                $name,
                                $title,
                                $file->name);
                }
                printf("</ul>\n");
                printf("<p class=\"mediamenu\">\n");
                printf("<a name=\"\" onclick=\"close_media();\">[%s]</a>\n", _("Close"));
                printf("<a href=\"%s/media/?exam=%d&amp;action=add&amp;type=%s\" target=\"_blank\" title=\"%s\">[+]</a>\n",
                        BASE_URL,
                        $this->media->exam,
                        $sect,
                        _("Add another file to the media library. Remember to reload this page to see the updated list."));
                printf("</p></div>\n");
                return sprintf("return show_media('%s','%s',event);", $target, $name);
        }

}

?>
