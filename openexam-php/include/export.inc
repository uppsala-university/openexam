<?php

//
// Copyright (C) 2010 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/export.inc
// Author: Anders Lövgren
// Date:   2010-12-11
//
// Export utility. Depends on the SimpleXML and DOM extensions.
//
// The exported XML document will have this layout:
//
// <openexam format="version">
//  <project>
//    // ... project properties (name, description, ...)
//  </project>
//  <topics>
//    <topic id="num">
//      <name>str</name>
//      <randomize>num</randomize>
//    </topic>
//      ...
//  </topics>
//  <questions>
//    <question topic="id">
//      // ... question fields
//    </question>
//    <question topic="id">
//      // ... question fields
//    </question>
//      ...
//  </questions>
// </openexam>
//

if (!defined("OPENEXAM_EXPORT_FORMAT_VERSION")) {
        define("OPENEXAM_EXPORT_FORMAT_VERSION", 6071);
}

class ExportException extends Exception
{

        public function __construct($message)
        {
                parent::__construct($message);
        }

}

class Export
{

        private $manager;

        public function __construct($exam)
        {
                if (!extension_loaded("SimpleXML")) {
                        throw new ExportException("The SimpleXML extension is not loaded");
                }
                if (!extension_loaded("dom")) {
                        throw new ExportException("The DOM Document extension (dom) is not loaded");
                }
                $this->manager = new Manager($exam);
        }

        //
        // Writes data to file.
        // 
        public function write($filename)
        {
                $this->output($filename);
        }

        //
        // Sends data to peer.
        //
        public function send()
        {
                ob_end_clean();

                $data = $this->manager->getData();
                $name = sprintf("%s - %s - %s",
                                $data->getExamOrgUnit(),
                                $data->getExamName(),
                                $data->getExamStartTime());

                header("Content-type: application/xml;charset=utf-8\n");
                header("Content-Disposition: attachment;filename=\"$name.xml\"");
                header("Cache-Control: no-cache");
                header("Pragma-directive: no-cache");
                header("Cache-directive: no-cache");
                header("Pragma: no-cache");
                header("Expires: 0");

                $this->output();
        }

        //
        // Output exam to stream.
        //
        private function output($filename = null)
        {
                $root = new SimpleXMLElement("<?xml version='1.0' standalone='yes'?><openexam></openexam>\n");

                // 
                // Set export format version:
                //
                $root->addAttribute("format", OPENEXAM_EXPORT_FORMAT_VERSION);

                //
                // Get the data to be exported:
                //
                $pdata = $this->manager->getData();
                $qdata = $this->manager->getQuestions();
                $tdata = array();

                //
                // Collect all topics:
                //
                foreach ($qdata as $q) {
                        if (!isset($tdata[$q->getTopicID()])) {
                                $tdata[$q->getTopicID()]['name'] = $q->getTopicName();
                                $tdata[$q->getTopicID()]['randomize'] = $q->getTopicRandom();
                        }
                }

                //
                // The top nodes:
                //
                $pnode = $root->addChild("project");
                $tnode = $root->addChild("topics");
                $qnode = $root->addChild("questions");

                //
                // Create project child nodes:
                //
                $pnode->addChild("name", $pdata->getExamName());
                $pnode->addChild("description", $pdata->getExamDescription());
                $pnode->addChild("orgunit", $pdata->getExamOrgUnit());
                $pnode->addChild("starttime", $pdata->getExamStartTime());
                $pnode->addChild("endtime", $pdata->getExamStartTime());
                $pnode->addChild("created", $pdata->getExamCreated());
                $pnode->addChild("updated", $pdata->getExamUpdated());
                $pnode->addChild("creator", $pdata->getExamCreator());
                $pnode->addChild("grades", $pdata->getExamGrades());

                //
                // Create all topic nodes:
                //
                foreach ($tdata as $id => $t) {
                        $child = $tnode->addChild("topic");
                        $child->addAttribute("id", $id);
                        $child->addChild("name", $t['name']);
                        $child->addChild("randomize", $t['randomize']);
                }

                //
                // Create all question nodes:
                //
                foreach ($qdata as $q) {
                        $child = $qnode->addChild("question");
                        $child->addAttribute("topic", $q->getTopicID());
                        $child->addChild("score", $q->getQuestionScore());
                        $child->addChild("name", $q->getQuestionName());
                        $child->addChild("text", $q->getQuestionText());
                        $child->addChild("publisher", $q->getQuestionPublisher());
                        $child->addChild("video", $q->getQuestionVideo());
                        $child->addChild("image", $q->getQuestionImage());
                        $child->addChild("audio", $q->getQuestionAudio());
                        $child->addChild("type", $q->getQuestionType());
                        $child->addChild("status", $q->getQuestionStatus());
                        $child->addChild("comment", $q->getQuestionComment());
                }

                // 
                // Now, the only thing left is to insert a comment line in the
                // header. Unfortunate, Simple XML don't support comments, but 
                // we can use DOM for that with a little more code:
                //
                if (!($node = dom_import_simplexml($root))) {
                        throw new ExportException("Failed import XML tree to DOM document.");
                }

                $dom = new DOMDocument('1.0', 'utf-8');
                $dom->formatOutput = true;

                $comment = sprintf(_(" OpenExam project exported %s "), strftime('%c'));
                $dom->appendChild($dom->createComment($comment));

                $node = $dom->importNode($node, true);
                $node = $dom->appendChild($node);

                //
                // Now send the XML content to its destination:
                //
                if (!isset($file)) {
                        echo $dom->saveXML();
                } else {
                        $dom->save($filename);
                }
        }

}

?>
