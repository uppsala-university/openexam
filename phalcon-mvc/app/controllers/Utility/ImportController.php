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
// File:    ImportController.php
// Created: 2015-04-15 02:26:49
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Controllers\Utility;

use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Import\Exception;
use OpenExam\Library\Import\FileImport;
use Phalcon\Mvc\View;

/**
 * Import GUI controller.
 * 
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class ImportController extends GuiController
{

        /**
         * Student import action.
         */
        public function studentsAction($mode)
        {
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                // 
                // Sanitize request parameters:
                // 
                if (!($mode = $this->filter->sanitize($mode, "string"))) {
                        throw new Exception("Expected mode parameter", Error::BAD_REQUEST);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess();

                // 
                // Process import task:
                // 
                switch ($mode) {
                        case 'file':
                                $this->studentFileAction();
                                break;
                        case 'group':
                                $this->studentGroupAction();
                                break;
                        default:
                                throw new Exception("Unknown mode $mode", Error::BAD_REQUEST);
                }
        }

        public function missingAction()
        {
                $this->checkAccess();
        }

        /**
         * Support for import of students from file.
         */
        private function studentFileAction()
        {
                $file = $this->request->getUploadedFiles()[0];
                $type = $this->request->get('type');

                $import = FileImport::create(FileImport::TYPE_STUDENT_REGISTRATIONS, $type, $file->getName());
                $import->setFile($file);

                $import->open();
                $import->read();
                $import->close();

                if (!($data = $import->getSheet())) {
                        $data = array();
                }
                if ($import->hasHeaders()) {
                        array_unshift($data, $import->getHeaders());
                }

                $this->view->setVar('data', $data);
        }

        /**
         * Support for import of student from catalog group.
         */
        private function studentGroupAction()
        {
                $group = $this->request->get('group');
                $domain = $this->request->get('domain');

                if (strlen(trim($domain)) == 0) {
                        $domain = null;
                }

                // 
                // Get user principal objects:
                // 
                $members = $this->catalog->getMembers($group, $domain);

                if (count($members) == 0) {
                        $this->dispatcher->forward(array(
                                'action' => 'missing'
                        ));
                        return false;
                }

                // 
                // Aggregate data in three columns:
                // 
                $data = array(array(
                                'user', 'tag', 'name'
                ));

                foreach ($members as $member) {
                        $data[] = array(
                                $member->principal,
                                $group,
                                $member->name
                        );
                }

                // 
                // Pass data to view:
                // 
                $this->view->setVar('data', $data);
        }

}
