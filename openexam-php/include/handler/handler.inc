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
                        $message = sprintf("No handler class %s for format type %s exist in %s",
                                        $this->name,
                                        $this->type,
                                        $this->file);
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
        private $path;  // The image path

        public function __construct($type, $data)
        {
                $this->type = $type;
                $this->data = $data;
                $this->path = sprintf("%s/%s/%s", CACHE_DIR, $type, md5($data));
        }

        public function __get($name)
        {
                return $this->$name;
        }

        public function exists()
        {
                return file_exists($this->path);
        }

        public function send($mime)
        {
                header(sprintf("Content-Type: %s\n", $mime));
                header(sprintf("Content-Length: %d\n", filesize($this->path)));
                readfile($this->path);
        }

}

?>
