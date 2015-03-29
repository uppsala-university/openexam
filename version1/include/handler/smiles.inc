<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/handler/smiles.inc
// Author: Anders Lövgren
// Date:   2011-01-28
//
// Handler for smiles notation strings. Uses an external REST web service for
// actually creating the image from input data.
//

if (!defined('HANDLER_SMILES_SERVICE')) {
        define('HANDLER_SMILES_SERVICE', 'http://ws1.bmc.uu.se:8182/cdk/depict/180/180/%s');
}

class SmilesHandler implements HandlerType
{

        const mime = "image/png";

        private $curl;
        private $url;

        public function __construct()
        {
                if (!extension_loaded("curl")) {
                        throw new HandlerException("The curl extention is missing");
                }
                if (!($this->curl = curl_init())) {
                        throw new HandlerException("Failed initialize curl");
                }
        }

        public function __destruct()
        {
                if ($this->curl) {
                        curl_close($this->curl);
                }
        }

        public function process($data, $path)
        {
                $this->url = sprintf(HANDLER_SMILES_SERVICE, $data);

                if (!curl_setopt($this->curl, CURLOPT_URL, $this->url)) {
                        throw new HandlerException(curl_error($this->curl), curl_errno($this->curl));
                }
                if (!curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true)) {
                        throw new HandlerException(curl_error($this->curl), curl_errno($this->curl));
                }

                if (!($content = curl_exec($this->curl))) {
                        throw new HandlerException(curl_error($this->curl), curl_errno($this->curl));
                }

                if (($code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE)) != 200) {
                        throw new HandlerException("Invalid smiles string", $code);
                }

                if (!(file_put_contents($path, $content))) {
                        $message = sprintf("Failed save content to %s", $path);
                        throw new HandlerException($message);
                }
        }

        public function mime()
        {
                return self::mime;
        }

}

?>
