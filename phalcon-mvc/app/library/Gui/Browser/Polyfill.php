<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Polyfill.php
// Created: 2017-12-09 22:34:27
// 
// Author:  Anders Lövgren (QNET)
// 

namespace OpenExam\Library\Gui\Browser;

use Phalcon\Mvc\User\Component;
use RuntimeException;

/**
 * Web browser polyfill support.
 * 
 * Can be used both in cached and CDN mode. When used in cached mode, the polyfill
 * has to be local stored in the cache directory.
 * 
 * <code>
 * // 
 * // Used on CDN mode:
 * // 
 * if ($service->canDownload()) {
 *      printf("<script src=\"%s\"></script>\n", $service->getDownload());
 * }
 * 
 * // 
 * // Used in local cache mode:
 * // 
 * if ($service->hasPolyfill() {
 *      printf("<script src=\"%s\"></script>\n", $this->url->get($service->getPath()));
 * }
 * </code>
 *
 * The local cache can be automatic updated on-demand by using this method calls
 * prior to using the local cache:
 * <code>
 * if (!$service->hasPolyfill() && 
 *      $service->canDownload() && 
 *      $service->canCache()) {
 *      $service->setContent();
 * }
 * </code>
 * 
 * If theres no need to detect whether download and cache would succeed in adavance,
 * the local cache sequence can be simplified to:
 * <code>
 * if ($service->setContent()) {
 *      printf("<script src=\"%s\"></script>\n", $this->url->get($service->getPath()));
 * }
 * </code>
 * 
 * @author Anders Lövgren (QNET)
 */
class Polyfill extends Component
{

        /**
         * The default download service.
         */
        const DOWNLOAD_SERVICE = "https://cdn.polyfill.io/v2/polyfill.min.js";

        /**
         * The user agent string.
         * @var string 
         */
        private $_agent;
        /**
         * The user agent hash.
         * @var string 
         */
        private $_hash;
        /**
         * The cached file.
         * @var string 
         */
        private $_file;
        /**
         * The URI path.
         * @var string 
         */
        private $_path;
        /**
         * The download service.
         * @var string
         */
        private $_download = self::DOWNLOAD_SERVICE;

        /**
         * Constructor.
         * @param string $agent The user agent.
         */
        public function __construct($agent = null)
        {
                if (!isset($agent)) {
                        if (filter_has_var(INPUT_SERVER, 'HTTP_USER_AGENT')) {
                                $agent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT');
                        }
                }

                if (isset($agent)) {
                        $this->setAgent($agent);
                }
        }

        /**
         * Set user agent string.
         * 
         * Notice that the constructor will automatic set user agent string
         * @param string $agent The user agent string.
         */
        public function setAgent($agent)
        {
                $this->_agent = $agent;
                $this->_hash = md5($agent);
                $this->_file = sprintf("%s/%s.js", $this->config->application->polyfillsDir, $this->_hash);
                $this->_path = sprintf("js/polyfill/%s.js", $this->_hash);
        }

        /**
         * Set download service.
         * @param string $url The download service.
         */
        public function setDownload($url)
        {
                $this->_download = $url;
        }

        /**
         * Get download service.
         * @return string
         */
        public function getDownload()
        {
                return $this->_download;
        }

        /**
         * Check if polyfill can be downloaded.
         * @return string
         */
        public function canDownload()
        {
                return isset($this->_agent) && isset($this->_download);
        }

        /**
         * Check if cache is writable.
         * @return boolean
         */
        public function canCache()
        {
                return is_writable($this->_file);
        }

        /**
         * Check if polyfill exists.
         * @return boolean
         */
        public function hasPolyfill()
        {
                return file_exists($this->_file);
        }

        /**
         * Get polyfill file content.
         * @return string 
         */
        public function getContent()
        {
                return file_get_contents($this->_file);
        }

        /**
         * Get detected user agent.
         * @return string
         */
        public function getAgent()
        {
                return $this->_agent;
        }

        /**
         * Check if user agent string is set.
         * @return boolean
         */
        public function hasAgent()
        {
                return isset($this->_agent);
        }

        /**
         * Get polyfill filename.
         * @return string
         */
        public function getFile()
        {
                return $this->_file;
        }

        /**
         * Get polyfill user agent hash.
         * @return string
         */
        public function getHash()
        {
                return $this->_hash;
        }

        /**
         * Get polyfill URI path.
         * @return string
         */
        public function getPath()
        {
                return $this->_path;
        }

        /**
         * Download polyfill.
         * 
         * The polyfill is downloaded from the polyfill service passing the
         * current defined user agent. The response is cached if successful and
         * true is returned. Calling this method when polyfill is already cached
         * will simply return true.
         * 
         * @return boolean True if polyfill file exists.
         */
        public function setContent()
        {
                if (file_exists($this->_file)) {
                        return true;
                }

                if (!extension_loaded("curl")) {
                        throw new RuntimeException("The cURL extension is not loaded");
                }
                if (!$this->canDownload()) {
                        throw new RuntimeException("Either the user agent string is missing or download service is not defined");
                }

                if (!($curl = curl_init())) {
                        throw new RuntimeException("Failed initialize cURL");
                }

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_USERAGENT, $this->_agent);
                curl_setopt($curl, CURLOPT_URL, $this->_download);

                if (!($result = curl_exec($curl))) {
                        throw new RuntimeException(sprintf("Failed download polyfill from %s", $this->_download));
                }

                if (!file_put_contents($this->_file, $result)) {
                        throw new RuntimeException("Failed write to polyfill cache");
                }

                curl_close($curl);
                return true;
        }

}
