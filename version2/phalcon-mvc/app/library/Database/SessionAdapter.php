<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Session.php
// Created: 2015-03-31 15:18:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Database;

use OpenExam\Library\Core\Error;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Models\Session as SessionModel;
use Phalcon\Session\Adapter As AdapterBase;
use Phalcon\Session\AdapterInterface;

/**
 * Session adapter based on session model.
 * 
 * This class implements the session adapter interface using the session
 * model itself as the storage backend. This adapter has the following
 * goals:
 * 
 * 1. Minimize the number of table insert/update.
 * 2. Use separate database connection for read/write.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SessionAdapter extends AdapterBase implements AdapterInterface
{

        /**
         * The cached session model.
         * @var SessionModel
         */
        private $session = false;

        /**
         * {@inheritdoc}
         *
         * @param array $options
         */
        public function __construct($options = null)
        {
                parent::__construct($options);
                session_set_save_handler(
                    array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc')
                );
        }

        /**
         * {@inheritdoc}
         * @return boolean
         */
        public function open()
        {
                return true;
        }

        /**
         * {@inheritdoc}
         * @return boolean
         */
        public function close()
        {
                return false;
        }

        /**
         * {@inheritdoc}
         * @param  string $sessionId
         * @return string
         */
        public function read($sessionId)
        {
                if (!$this->session) {
                        if (($this->session = SessionModel::findFirstBySessionId($sessionId)) == false) {
                                return "";
                        }
                }

                return $this->session->data;
        }

        /**
         * {@inheritdoc}
         * @param  string  $sessionId
         * @param  string  $data
         * @return boolean
         * @throws ModelException
         */
        public function write($sessionId, $data)
        {
                if (empty($data)) {
                        return false;
                }

                if (!$this->session) {
                        if (($this->session = SessionModel::findFirstBySessionId($sessionId)) == false) {
                                $this->session = new SessionModel();
                        }
                }

                if ($this->session->data == $data) {
                        if ($this->session->session_id == $sessionId) {
                                return true;
                        }

                        $maxlifetime = (int) ini_get('session.gc_maxlifetime');
                        if ($this->session->updated + $maxlifetime > time()) {
                                return true;
                        }
                }

                if (isset($this->session->id)) {
                        $this->session->session_id = $sessionId;
                        $this->session->data = $data;
                        $this->session->updated = time();
                } else {
                        $this->session->session_id = $sessionId;
                        $this->session->data = $data;
                        $this->session->created = time();
                        $this->session->updated = time();
                }

                if (($this->session->save() == false)) {
                        throw new ModelException($this->session->getMessages()[0], Error::SERVICE_UNAVAILABLE);
                } else {
                        return true;
                }
        }

        /**
         * {@inheritdoc}
         * @param  string  $sessionId
         * @return boolean
         */
        public function destroy($sessionId = null)
        {
                if (!$this->isStarted()) {
                        return true;
                }

                if (is_null($sessionId)) {
                        $sessionId = $this->getId();
                }

                if (!$this->session) {
                        return true;
                }

                $result = $this->session->delete();
                $this->session = false;

                session_regenerate_id();
                return $result;
        }

        /**
         * {@inheritdoc}
         * @param  integer $maxlifetime
         * @return boolean
         */
        public function gc($maxlifetime)
        {
                if (!$this->session) {
                        return true;
                }

                if ($this->session->updated + $maxlifetime > time()) {
                        return false;
                }

                $result = $this->session->delete();
                $this->session = false;

                return $result;
        }

}
