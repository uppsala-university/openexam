<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
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
        public function studentsAction($mode = null)
        {
                $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

                if (!isset($mode)) {
                        throw new Exception("Expected mode parameter", Error::BAD_REQUEST);
                }

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
