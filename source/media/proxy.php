<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/media/proxy.php
// Author: Anders Lövgren
// Date:   2011-01-14
//
//
// This script provides a proxy function for delivering HTTPS secured (media)
// content from an unsecured web server. 
//
// The script treats all request parameters as an URL pointing to an external
// resource. Any of the following kind of requests should work:
// 
// 1. proxy.php?url=http://www.example.com?xxx   (the prefered request format)
// 2. proxy.php?url=www.example.com?xxx
// 3. proxy.php?http://www.example.com?xxx
// 4. proxy.php?www.example.com?xxx
//
// The query string could be URL-encoded. An optional port number is accepted
// in the query string: proxy.php?http:8080://www.example.com?xxx
//

class Proxy
{

        private $url;
        private $curl;

        public function __construct($url)
        {
                $this->url = $url;
        }

        public function validate()
        {
                if (preg_match("|.*:(\d*)//.*|", $this->url)) {
                        return;
                }
                if (preg_match("|^www\.*|", $this->url)) {
                        $this->url = sprintf("http://%s", $this->url);
                        return;
                }
                die(sprintf("Invalid URL: %s", $this->url));
        }

        public function deliver()
        {
                $this->curl = curl_init();

                curl_setopt($this->curl, CURLOPT_URL, $this->url);
                curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);

                $content = curl_exec($this->curl);
                header(sprintf("Content-Type: %s\n", curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE)));
                echo $content;

                curl_close($this->curl);
        }

}

//
// Sanity check:
//
if (count($_REQUEST) == 0) {
        printf("Media proxy for unsecured web servers.\n");
        printf("Usage: proxy.php?url=str, where str is an URL\n");
        exit(1);
}
if (!extension_loaded("curl")) {
        die("The cURL extension is not loaded.");
}

// 
// Use the query string from the server:
// 
if (strncmp("url=", $_SERVER['QUERY_STRING'], 4) == 0) {
        $_REQUEST['url'] = substr($_SERVER['QUERY_STRING'], 4);
} else {
        $_REQUEST['url'] = $_SERVER['QUERY_STRING'];
}

$proxy = new Proxy(urldecode($_REQUEST['url']));
$proxy->validate();
$proxy->deliver();
?>
