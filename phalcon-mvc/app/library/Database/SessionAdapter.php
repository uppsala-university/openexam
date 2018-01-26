<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

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
use Phalcon\Config;
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
         * The session models.
         * @var SessionModel[]
         */
        private $_session = array();

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
                if ($options instanceof Config) {
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
         * Find session model.
         * 
         * This method returns the session model from local store or database
         * model. A new session model is created if the session ID is not yet
         * loaded.
         * 
         * @param string $sessionId The session ID.
         * @return SessionModel
         */
        private function find($sessionId)
        {
                // 
                // Return from local store if exist:
                // 
                if (isset($this->_session[$sessionId])) {
                        return $this->_session[$sessionId];
                }

                // 
                // Query session in database model:
                // 
                if (($session = SessionModel::findFirst("session_id = '$sessionId'"))) {
                        $this->_session[$sessionId] = $session;
                        return $session;
                }

                // 
                // Create an unpersisted session model:
                // 
                $session = new SessionModel(array(
                        'session_id' => $sessionId,
                        'data'       => ''
                    )
                );

                // 
                // Keep object reference for subsequent use:
                // 
                $this->_session[$sessionId] = $session;
                return $session;
        }

        /**
         * Cleanup all session having this ID.
         * 
         * @param string $sessionId The session ID.
         */
        private function cleanup($sessionId)
        {
                if (($session = SessionModel::findFirst("session_id = '$sessionId'"))) {
                        $session->delete();
                }
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
                $session = $this->find($sessionId);
                return $session->data;
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
                // 
                // Ignore writing empty data:
                // 
                if (empty($data)) {
                        return true;
                }

                // 
                // Get session model:
                // 
                if (!($session = $this->find($sessionId))) {
                        return false;
                }

                // 
                // Don't update if data is unchanged, unless expire time has
                // passed and session need refresh:
                // 
                if ($session->data == $data) {
                        if ($this->getOption('expires') -
                            $this->getOption('refresh') +
                            $session->updated > time()) {
                                return true;
                        }
                }

                // 
                // Set session data:
                // 
                if (isset($session->id)) {
                        $session->session_id = $sessionId;
                        $session->data = $data;
                        $session->updated = time();
                } else {
                        $session->session_id = $sessionId;
                        $session->data = $data;
                        $session->created = time();
                        $session->updated = null;
                }

                // 
                // Clean persisted session in case of a race condition:
                // 
                if (($session->save() == false)) {
                        $this->cleanup($sessionId);
                } else {
                        return true;
                }

                // 
                // Try to save session:
                // 
                if (($session->save() == false)) {
                        throw new ModelException($session->getMessages()[0], Error::SERVICE_UNAVAILABLE);
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
                // 
                // Can't destroy session if session handling is stopped:
                // 
                if (!parent::isStarted()) {
                        return true;
                }

                // 
                // Get session ID from parent if null:
                // 
                if (is_null($sessionId)) {
                        $sessionId = parent::getId();
                }

                // 
                // Delete session model:
                // 
                $session = $this->find($sessionId);
                $result = $session->delete();

                // 
                // Remove session from local store:
                // 
                unset($this->_session[$sessionId]);

                return $result;
        }

        /**
         * {@inheritdoc}
         * @param  integer $maxlifetime
         * @return boolean
         */
        public function gc($maxlifetime)
        {
                // 
                // Check if garbage collection is enabled:
                // 
                if (!$this->getOption('cleanup')) {
                        return false;
                }

                // 
                // Don't garbage collect on $maxlifetime == 0:
                // 
                if ($maxlifetime == 0) {
                        return false;
                }

                // 
                // Adjust max lifetime if lower than configured:
                // 
                if ($maxlifetime < $this->getOption('expires')) {
                        $maxlifetime = $this->getOption('expires');
                }

                // 
                // Get session models for garbage collection:
                // 
                if (($sessions = SessionModel::find(array(
                            'conditions' => 'COALESCE(updated, created) + :max: < :now:',
                            'bind'       => array(
                                    'max' => $maxlifetime,
                                    'now' => time()
                            )
                    )))) {
                        return $sessions->delete();
                } else {
                        return true;
                }
        }

        private function getOption($key)
        {
                return $this->_options[$key];
        }

}
