<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Computer.php
// Created: 2017-03-21 18:49:51
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core\Exam\Student;

use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Models\Computer as ComputerModel;
use OpenExam\Models\Room as RoomModel;
use Phalcon\Mvc\User\Component;

/**
 * The student computer.
 * 
 * This class takes care of creating computer and room models. It will 
 * dynamic update computer models to match remote location based on the
 * system config (see the location service).
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Computer extends Component
{

        /**
         * Defaults for room.
         * @var array 
         */
        private static $_defloc = array(
                'name' => 'unknown',
                'desc' => 'Default room for ungrouped computers'
        );
        /**
         * The computer model.
         * @var ComputerModel
         */
        private $_computer;
        /**
         * The room model.
         * @var RoomModel
         */
        private $_room;

        /**
         * Constructor.
         * @param string $remote The remote address.
         */
        public function __construct($remote)
        {
                $this->setComputer($remote);
        }

        /**
         * Destructor.
         */
        public function __destruct()
        {
                unset($this->_computer);
                unset($this->_room);
        }

        /**
         * Get remote computer.
         * @return ComputerModel
         */
        public function getComputer()
        {
                return $this->_computer;
        }

        /**
         * Get computer room.
         * @return RoomModel
         */
        public function getRoom()
        {
                return $this->_room;
        }

        /**
         * Set computer model.
         * 
         * Insert new computer model or update existing in database. The 
         * computer location is dynamic changed to match system config if
         * needed.
         * 
         * @param string $remote The remote address.
         * @throws ModelException
         */
        private function setComputer($remote)
        {
                // 
                // Set computer room.
                // 
                $this->setRoom($remote);

                // 
                // Find existing computer:
                // 
                if (($this->_computer = ComputerModel::findFirst(array(
                            'conditions' => 'ipaddr = ?0',
                            'bind'       => array($remote)
                    ))) !== false) {
                        // 
                        // Move computer to new location if changed:
                        // 
                        if ($this->_computer->room_id != $this->_room->id) {
                                $this->_computer->room_id = $this->_room->id;
                                $this->_computer->update();
                        }
                        return;
                }

                // 
                // Create new computer in the detected room:
                // 
                $this->_computer = new ComputerModel();
                $this->_computer->room_id = $this->_room->id;
                $this->_computer->ipaddr = $remote;
                $this->_computer->port = 0;
                $this->_computer->password = strtoupper(md5(time()));
                $this->_computer->hostname = gethostbyaddr($remote);
                $this->_computer->created = strftime("%x %X");

                if ($this->_computer->create() == false) {
                        throw new ModelException($this->_computer->getMessages()[0]);
                }
        }

        /**
         * Set room model.
         * 
         * Insert new room model in database if missing. The default location
         * is used for unmatched remote addresses.
         * 
         * @param string $remote The remote address.
         * @throws ModelException
         */
        private function setRoom($remote)
        {
                // 
                // Get room location:
                // 
                $location = $this->getLocation($remote);

                if (!isset($location['name'])) {
                        if (isset($location['okey']) && isset($location['ckey']) && isset($location['pkey'])) {
                                $location['name'] = sprintf("%s-%s-%s", $location['okey'], $location['ckey'], $location['pkey']);
                        }
                }

                // 
                // Find existing room:
                // 
                if (($this->_room = RoomModel::findFirst(array(
                            'conditions' => 'name = ?0',
                            'bind'       => array($location['name'])
                    ))) !== false) {
                        return;
                }

                // 
                // Create new room in detected location:
                // 
                $this->_room = new RoomModel();
                $this->_room->name = $location['name'];
                $this->_room->description = $location['desc'];

                if ($this->_room->create() == false) {
                        throw new ModelException($this->_room->getMessages()[0]);
                }
        }

        /**
         * Get room location.
         * 
         * Use location service to map remote address against one of the
         * pre-configured locations. Return default location otherwise.
         * 
         * @param string $remote The remote address.
         * @return array
         */
        private function getLocation($remote)
        {
                if ($this->location) {
                        return $this->location->getEntry($remote, self::$_defloc);
                } else {
                        return self::$_defloc;
                }
        }

}
