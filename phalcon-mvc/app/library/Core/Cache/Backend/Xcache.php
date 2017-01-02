<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Xcache.php
// Created: 2017-01-02 07:33:08
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Library\Core\Cache\Backend;

use Phalcon\Cache\Backend;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\Backend\Prefixable;

/**
 * Alternative XCache backend.
 * 
 * The XCache backend in Phalcon caused memory limit exhausted errors in some
 * cases. This class can be used as a replacement for that class.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class Xcache extends Backend implements BackendInterface
{

        use Prefixable;

        /**
         * {@inheritdoc}
         *
         * @param  string  $keyName
         * @return bool
         */
        public function delete($keyName)
        {
                $ckey = $this->getPrefixedIdentifier($keyName);
                return xcache_unset($ckey);
        }

        /**
         * {@inheritdoc}
         *
         * @param  string  $keyName
         * @param  string  $lifetime
         * @return bool
         */
        public function exists($keyName = null, $lifetime = null)
        {
                $ckey = $this->getPrefixedIdentifier($keyName);
                return xcache_isset($ckey);
        }

        /**
         * {@inheritdoc}
         *
         * @param  string     $keyName
         * @param  integer    $lifetime
         * @return mixed|null
         */
        public function get($keyName, $lifetime = null)
        {
                $ckey = $this->getPrefixedIdentifier($keyName);
                $data = xcache_get($ckey);

                $this->_lastKey = $ckey;

                return $this->_frontend->afterRetrieve($data);
        }

        /**
         * {@inheritdoc}
         *
         * @param  string $keyName
         * @param  string $content
         * @param  int    $lifetime
         * @param  bool   $stopBuffer
         * @throws \Phalcon\Cache\Exception
         */
        public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = true)
        {
                if ($keyName === null) {
                        $ckey = $this->_lastKey;
                } else {
                        $ckey = $this->getPrefixedIdentifier($keyName);
                }

                if ($content === null) {
                        $data = $this->_frontend->getContent();
                } else {
                        $data = $content;
                }

                if (is_string($data)) {
                        xcache_set($ckey, $data, $lifetime);
                } else {
                        xcache_set($ckey, serialize($data), $lifetime);
                }

                if ($stopBuffer) {
                        $this->_frontend->stop();
                }

                $this->_started = false;
        }

        /**
         * {@inheritdoc}
         * 
         * @todo Not yet implemented
         * @param  string $prefix
         * @return array
         */
        public function queryKeys($prefix = null)
        {
                return null;
        }

}
