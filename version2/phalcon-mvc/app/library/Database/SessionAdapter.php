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
use Phalcon\Session\Adapter as AdapterBase;
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
 * The session.gc_maxlifetime should already been set by index.php to
 * session expire time in system/user config.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class SessionAdapter extends AdapterBase implements AdapterInterface
{

        /**
         * The cached session model.
         * @var SessionModel
         */
        private $_session = null;
        /**
         * Last queried session ID.
         * @var string 
         */
        private $_lastsid = null;

        /**
         * {@inheritdoc}
         *
         * @param array $options
         */
        public function __construct($options = array())
        {
                if (!isset($options)) {
                        throw new Exception("No configuration given");
                }
                if ($options instanceof \Phalcon\Config) {
                        $options = $options->toArray();
                }

                if (isset($options['name'])) {
                        ini_set('session.name', $options['name']);
                }
                if (isset($options['lifetime'])) {
                        ini_set('session.gc_maxlifetime', $options['lifetime']);
                }
                if (isset($options['cookie_lifetime'])) {
                        ini_set('session.cookie_lifetime', $options['cookie_lifetime']);
                }
                if (isset($options['cookie_secure'])) {
                        ini_set('session.cookie_secure', $options['cookie_secure']);
                }

                if (!isset($options['expires'])) {
                        $options['expires'] = ini_get('session.gc_maxlifetime');
                }
                if (!isset($options['refresh'])) {
                        $options['refresh'] = $options['expires'] - 1800;
                }
                if (!isset($options['cleanup'])) {
                        $options['cleanup'] = true;
                }

                session_set_save_handler(
                    array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc')
                );

                parent::__construct($options);
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
                if (empty($sessionId)) {
                        return "";
                }

                if ($sessionId != $this->_lastsid) {
                        $this->_session = null;
                        $this->_lastsid = $sessionId;
                }
                if (is_null($this->_session)) {
                        $this->_session = SessionModel::findFirstBySessionId($sessionId);
                }
                if (!$this->_session) {
                        return "";
                }

                return $this->_session->data;
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

                if (is_null($this->_session)) {
                        $this->_session = SessionModel::findFirstBySessionId($sessionId);
                }

                if (empty($data) && $this->_session) {
                        return $this->_session->delete();
                } elseif (empty($data)) {
                        return false;
                } elseif (!$this->_session) {
                        $this->_session = new SessionModel();
                }

                if ($this->_session->data == $data) {
                        if ($this->getOption('expires') -
                            $this->getOption('refresh') +
                            $this->_session->updated > time()) {
                                return true;
                        }
                }

                if (isset($this->_session->id)) {
                        $this->_session->session_id = $sessionId;
                        $this->_session->data = $data;
                        $this->_session->updated = time();
                } else {
                        $this->_session->session_id = $sessionId;
                        $this->_session->data = $data;
                        $this->_session->created = time();
                        $this->_session->updated = null;
                }

                if (($this->_session->save() == false)) {
                        throw new ModelException($this->_session->getMessages()[0], Error::SERVICE_UNAVAILABLE);
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

                if (empty($this->_session)) {
                        return true;
                }

                $result = $this->_session->delete();
                $this->_session = false;

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
                if (empty($this->_session)) {
                        return false;
                }
                if (!$this->getOption('cleanup')) {
                        return false;
                }
                if ($maxlifetime < $this->getOption('expires')) {
                        $maxlifetime = $this->getOption('expires');
                }

                $dbo = $this->_session->getWriteConnection();
                return $dbo->execute(
                        sprintf(
                            'DELETE FROM sessions WHERE COALESCE(updated, created) + %d < ?', $maxlifetime
                        ), array(time())
                );
        }

        private function getOption($key)
        {
                return $this->_options[$key];
        }

}
