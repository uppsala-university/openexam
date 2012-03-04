<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/handler/handler.inc
// Author: Anders Lövgren
// Date:   2011-01-28
//
// Support classes and interface for escape sequence content handlers. A
// handler converts a format string (i.e. a LaTeX formula) into an image.
//

class HandlerException extends Exception
{

        public function __construct($message, $code = 0)
        {
                parent::__construct($message, $code);
        }

}

//
// New handlers should implement this interface. Implementations should throw
// an HandlerException on error.
//
interface HandlerType
{

        //
        // The $data parameter is the input data. The $path parameter is an
        // absolute path that the result image should be written to.
        //
        function process($data, $path);

        // 
        // Get the MIME-type of files created by this handler. This is typical
        // one of the image/* types.
        // 
        function mime();
}

//
// This factory class creates an instance of class for handling a type request.
// Throws an HandlerException if a suitable class is missing.
// 
class HandlerFactory
{

        private $type;  // format type
        private $file;  // include file
        private $name;  // class name
        private $path;

        public function __construct($type)
        {
                $this->type = $type;
                $this->name = sprintf("%sHandler", ucfirst($this->type));
                $this->file = sprintf("%s.inc", $this->type);
                $this->path = sprintf("%s/%s", dirname(__FILE__), $this->file);
        }

        public function create()
        {
                if (file_exists($this->path)) {
                        include($this->path);
                        return new $this->name();
                } else {
                        $message = sprintf("No handler class %s for format type %s exist in %s", $this->name, $this->type, $this->file);
                        throw new HandlerException($message);
                }
        }

}

//
// This class manage the cache of images produced for format type.
//
class HandlerCache
{

        private $type;  // The format type
        private $data;  // The format string
        private $path;  // The image cache dir
        private $file;  // The image file
        private $hash;  // A unique hash based on content (data)

        public function __construct($type, $data)
        {
                $this->type = $type;
                $this->data = $data;
                $this->hash = md5($data);

                $this->path = sprintf("%s/%s/%s/%s", CACHE_DIR, $type, $this->hash[0], substr($this->hash, 0, 2));
                $this->file = sprintf("%s/%s", $this->path, $this->hash);

                $this->setup();
        }

        private function setup()
        {
                if (!file_exists($this->path)) {
                        if (!mkdir($this->path, 0777, true)) {
                                $message = sprintf("Failed create cache directory %s", $this->path);
                                throw new HandlerException($message);
                        }
                }
        }

        public function __get($name)
        {
                return $this->$name;
        }

        public function exists()
        {
                return file_exists($this->file);
        }

        public function send($mime)
        {
                header(sprintf("Content-Type: %s\n", $mime));
                header(sprintf("Content-Length: %d\n", filesize($this->file)));
                readfile($this->file);
        }

}

// 
// Question text scanner class.
// 
class HandlerScanner
{

        private $text;  // question text

        const PATTERN = "/<%(\w+)[\s\n]*((\n|.)*?)[\s\n]%>/m";
        const REPLACE = "<img src='images/handler.php?type=%s&data=%s'>";

        public function __construct($text)
        {
                $this->text = $text;
        }

        // 
        // Replace all <%type data %> escape sequences with HTML code that
        // calls the handler with proper encoded arguments.
        // 
        public function expand()
        {
                return preg_replace_callback(self::PATTERN, function($match) {
                                return sprintf(HandlerScanner::REPLACE, $match[1], urlencode($match[2]));
                        }, $this->text);
        }

}

?>
