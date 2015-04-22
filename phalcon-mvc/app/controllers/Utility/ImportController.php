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
                        default:
                                throw new Exception("Unknown mode $mode", Error::BAD_REQUEST);
                }
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

                $this->view->setVar('data', $import->getSheet());
        }

}
