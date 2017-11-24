<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ExportController.php
// Created: 2016-04-14 11:56:58
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Utility;

use Exception;
use OpenExam\Controllers\GuiController;
use OpenExam\Library\Catalog\Principal;
use OpenExam\Library\Core\Error;
use OpenExam\Library\Model\Exception as ModelException;
use OpenExam\Library\Render\Renderer;
use OpenExam\Models\Exam;
use OpenExam\Models\Student;

/**
 * Data export controller.
 *
 * Notice: 
 * -----------
 * This is a temporary dropin. The real controller is much wider, but not
 * yet fully implemented and tested.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ExportController extends GuiController
{

        /**
         * Show or download list of students. Only accessable by invigilators.
         * 
         * @param int $eid The exam ID.
         * @param string $download
         */
        public function studentsAction($eid, $download = null)
        {
                // 
                // Sanitize request parameters:
                // 
                if (!($eid = $this->filter->sanitize($eid, "int"))) {
                        throw new Exception("Missing or invalid exam ID", Error::PRECONDITION_FAILED);
                }

                // 
                // Check route access:
                // 
                $this->checkAccess(array(
                        'eid' => $eid
                ));

                // 
                // Load view or download PDF.
                // 
                if (isset($download)) {
                        $this->studentsDownload($eid);
                } else {
                        $this->studentsShowView($eid);
                }
        }

        /**
         * Download student registrations as PDF.
         * @param int $eid The exam ID.
         */
        private function studentsDownload($eid)
        {
                $this->view->disable();

                // 
                // Get authentication token for system local service:
                // 
                if (file_exists($this->config->render->token)) {
                        $token = file_get_contents($this->config->render->token);
                } else {
                        $token = $this->config->render->token;
                }

                // 
                // Token based authentication supoorting user impersonation
                // is allowed for localhost request.
                // 
                $expand = $this->url->get("utility/export/students/");
                $target = sprintf("http://localhost/%s/%d?token=%s&user=%s", $expand, $eid, $token, $this->user->getPrincipalName());

                // 
                // Send PDF using render service.
                // 
                $render = $this->render->getRender(Renderer::FORMAT_PDF);
                $render->send("students-registrations-exam-$eid.pdf", array(
                        array(
                                'page' => $target
                        )
                ));
        }

        /**
         * Show students.
         * @throws ModelException
         */
        private function studentsShowView($eid)
        {
                // 
                // Get students in this exam and exam data.
                // 
                if (($stud = Student::find("exam_id = $eid")) == false) {
                        throw new ModelException("Failed find student on exam");
                }
                if (($exam = Exam::findFirst($eid)) == false) {
                        throw new ModelException("Failed get exam data");
                }

                // 
                // Sort student list on lastname. We need to access each
                // student to get lastname decoration.
                // 
                $students = array();
                foreach ($stud as $s) {
                        $students[] = $s;
                }
                usort($students, function($s1, $s2) {
                        return strcmp($s1->lname, $s2->lname);
                });

                // 
                // Exam contact information.
                // 
                $contact = $this->catalog->getPrincipal($exam->creator, Principal::ATTR_PN);

                // 
                // Set data for view:
                // 
                $this->view->setVar('students', $students);
                $this->view->setVar('exam', $exam);
                $this->view->setvar('contact', $contact);

                // 
                // Use no layout at all:
                // 
                $this->view->setLayout(null);
        }

}
