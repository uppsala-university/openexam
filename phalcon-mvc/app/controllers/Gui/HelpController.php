<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    HelpController.php
// Created: 2015-11-23 22:05:44
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Controllers\Gui;

use Exception;
use OpenExam\Controllers\GuiController;
use OpenExam\Library\Core\Error;

/**
 * Help content controller.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class HelpController extends GuiController
{

        /**
         * Supported MIME types.
         * @var array 
         */
        private static $_mime = array(
                "pdf"  => "application/pdf",
                "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "odt"  => "application/vnd.oasis.opendocument.text"
        );
        /**
         * Help content description.
         * @var array 
         */
        private static $_help = array(
                "manual" => array(
                        "name" => "User Manuals",
                        "data" => array(
                                "teacher" => array(
                                        "name" => "The OpenExam Teacher Manual",
                                        "data" => array(
                                                "pdf"  => "Portable Document Format (PDF)",
                                                "docx" => "Microsoft Word OpenXML (Text)"
                                        ),
                                        "lang" => array("swedish", "english")
                                ),
                                "student" => array(
                                        "name" => "The OpenExam Student Manual",
                                        "data" => array(
                                                "pdf" => "Portable Document Format (PDF)",
                                                "odt" => "Open Document Format (Text)"
                                        ),
                                        "lang" => array("swedish", "english")
                                )
                        )
                )
        );

        public function initialize()
        {
                parent::initialize();

                $this->view->setVar('base', $this->url->getBaseUri() . '/help');
                $this->view->setVar('icon', $this->url->get('img/instruction-manual.jpg'));
                
                $this->view->setTemplateBefore('cardbox');
        }

        /**
         * Default controller action.
         * @location /help
         */
        public function indexAction()
        {
                $this->view->setVar("help", self::$_help);
        }

        /**
         * Handle manual request.
         * 
         * Deliver manual content or (if manual content is missing), provide
         * links to downloadable content.
         * 
         * @param string $target The target manual.
         * @param string $language The manual language.
         * @param string $format The manual format (e.g. pdf)
         * @throws Exception
         * 
         * @location /help/manual/*
         */
        public function manualAction($target = null, $language = null, $format = "pdf")
        {
                $languages = array(
                        'swedish' => 'sv',
                        'english' => 'en'
                );

                // 
                // Use prefered language unless explicit selecting other 
                // language. The default language is swedish.
                // 
                if (!isset($language)) {
                        $language = $this->locale->getLanguage(
                            $this->locale->getLocale()
                        );
                } elseif (array_key_exists($language, $languages)) {
                        $language = $languages[$language];
                } else {
                        $language = $languages['swedish'];
                }

                if ($target == "teacher") {
                        $this->teacherManual($target, $language, $format);
                        return;
                }
                if ($target == "student") {
                        $this->studentManual($target, $language, $format);
                        return;
                }

                $this->view->setVar("manual", self::$_help['manual']['data']);
        }

        /**
         * Handles the teacher manual.
         * 
         * This manual should be restricted. This is subject to changes and
         * should defined in access.def.
         * 
         * @param string $target The target manual.
         * @param string $language The manual language.
         * @param string $format The manual format (e.g. pdf)
         * @throws Exception
         */
        private function teacherManual($target, $language, $format)
        {

                $file = sprintf("openexam-%s-manual-%s.%s", $target, $language, $format);
                $path = sprintf("%s/manual/%s", $this->config->application->docsDir, $file);

                if (!$this->user->affiliation->isEmployee()) {
                        throw new Exception("The teacher manual is only available for employees", Error::FORBIDDEN);
                }

                $this->sendManual($path, $file, $format);
        }

        /**
         * Handles the student manual.
         * 
         * This manual should be restricted. This is subject to changes and
         * should defined in access.def.
         * 
         * @param string $target The target manual.
         * @param string $language The manual language.
         * @param string $format The manual format (e.g. pdf)
         * @throws Exception
         */
        private function studentManual($target, $language, $format)
        {

                $file = sprintf("openexam-%s-manual-%s.%s", $target, $language, $format);
                $path = sprintf("%s/manual/%s", $this->config->application->docsDir, $file);

                $this->sendManual($path, $file, $format);
        }

        /**
         * Send manual in requested format.
         * @param string $path The absolute path.
         * @param string $name The file name.
         * @param string $format Requested format (e.g. pdf)
         * @throws Exception
         */
        private function sendManual($path, $name, $format)
        {
                if (!file_exists($path)) {
                        throw new Exception("Can't locate requested manual", Error::FORBIDDEN);
                }
                if (!isset(self::$_mime[$format])) {
                        throw new Exception("Unsupported media format requested", Error::NOT_ACCEPTABLE);
                }

                $this->view->disable();
                $this->response->setContentType(self::$_mime[$format]);
                $this->response->setHeader("content-disposition", "attachment; filename=\"$name\"");
                readfile($path);
        }

}
