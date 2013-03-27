<?php

//
// Copyright (C) 2010-2013 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/export.inc
// Author: Anders Lövgren
// Date:   2010-12-11
//
// Export utility. 
// 
// 
// Output format options:
// 
if (!defined("OPENEXAM_EXPORT_OUTPUT_NATIVE")) {
        define("OPENEXAM_EXPORT_OUTPUT_NATIVE", "native");
}
if (!defined("OPENEXAM_EXPORT_OUTPUT_WORD_2007")) {
        define("OPENEXAM_EXPORT_OUTPUT_WORD_2007", "word");
}
if (!defined("OPENEXAM_EXPORT_OUTPUT_EXCEL_2007")) {
        define("OPENEXAM_EXPORT_OUTPUT_EXCEL_2007", "excel");
}
if (!defined("OPENEXAM_EXPORT_OUTPUT_PDF")) {
        define("OPENEXAM_EXPORT_OUTPUT_PDF", "pdf");
}

// 
// Include data options:
// 
if (!defined("OPENEXAM_EXPORT_INCLUDE_PROJECT")) {
        define("OPENEXAM_EXPORT_INCLUDE_PROJECT", 1);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_TOPICS")) {
        define("OPENEXAM_EXPORT_INCLUDE_TOPICS", 2);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_QUESTIONS")) {
        define("OPENEXAM_EXPORT_INCLUDE_QUESTIONS", 4);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_ROLES")) {
        define("OPENEXAM_EXPORT_INCLUDE_ROLES", 8);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_ANSWERS")) {
        define("OPENEXAM_EXPORT_INCLUDE_ANSWERS", 16);
}
// 
// Simplified include options:
// 
if (!defined("OPENEXAM_EXPORT_INCLUDE_DEFAULT")) {
        define("OPENEXAM_EXPORT_INCLUDE_DEFAULT", OPENEXAM_EXPORT_INCLUDE_PROJECT | \
            OPENEXAM_EXPORT_INCLUDE_TOPICS | OPENEXAM_EXPORT_INCLUDE_QUESTIONS);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_ALL")) {
        define("OPENEXAM_EXPORT_INCLUDE_ALL", OPENEXAM_EXPORT_INCLUDE_DEFAULT | \
            OPENEXAM_EXPORT_INCLUDE_ROLES | OPENEXAM_EXPORT_INCLUDE_ANSWERS);
}

// 
// Version identifier for native export format:
// 
if (!defined("OPENEXAM_EXPORT_FORMAT_VERSION")) {
        define("OPENEXAM_EXPORT_FORMAT_VERSION", 6072);
}

/**
 * Export exception class.
 */
class ExportException extends Exception
{

        public function __construct($message)
        {
                parent::__construct($message);
        }

}

/**
 * Abstract base class for output formatter classes. Classes that provides an output format 
 * should extend this class and implement the send() and write() methods.
 */
abstract class ExportFormatter
{

        /**
         * Writes examination data to file.
         * 
         * @param ExportData $data The data to export.
         * @param string $filename The file name.
         * @throws ExportException
         */
        abstract function write($data, $filename);

        /**
         * Send examination data to peer.
         * 
         * @param ExportData $data The data to export.
         * @throws ExportException
         */
        abstract function send($data);

        /**
         * Factory function for creating an output formatting object.
         * 
         * @param string $format The output format (see OPENEXAM_EXPORT_OUTPUT_XXX definitions).
         * @return \ExportFormatOpenExam|\ExportFormatWord2007|\ExportFormatExcel2007|\ExportFormatPDF
         * @throws ExportException
         */
        public static function create($format)
        {
                switch ($format) {
                        case OPENEXAM_EXPORT_OUTPUT_NATIVE:
                                return new ExportFormatOpenExam();
                        case OPENEXAM_EXPORT_OUTPUT_WORD_2007:
                                return new ExportFormatWord2007();
                        case OPENEXAM_EXPORT_OUTPUT_EXCEL_2007:
                                return new ExportFormatExcel2007();
                        case OPENEXAM_EXPORT_OUTPUT_PDF:
                                return new ExportFormatPDF();
                        default:
                                throw new ExportException(sprintf("Can't create export formatter for format '%s'", $format));
                }
        }

}

/**
 * This class should be used when exporting examination data.
 * @property-write string $format The export output format.
 * @property-write int $name An bitmask of one or more OPENEXAM_EXPORT_INCLUDE_XXX defines.
 */
class Export
{

        private $exam;
        private $format;
        private $options;

        /**
         * Constructor.
         * @param int $exam The examination ID.
         * @param string $format The export output format.
         * @param int $options An bitmask of one or more OPENEXAM_EXPORT_INCLUDE_XXX defines.
         */
        public function __construct($exam, $format = OPENEXAM_EXPORT_FORMAT_NATIVE, $options = OPENEXAM_EXPORT_INCLUDE_DEFAULT)
        {
                $this->exam = $exam;
                $this->format = $format;
                $this->options = $options;
        }

        public function __set($name, $value)
        {
                switch (lcfirst($name)) {
                        case "format":
                                $this->format = $value;
                                break;
                        case "options":
                                $this->options = $value;
                                break;
                        default:
                                throw new ExportException(sprintf("Unknown property name '%s'.", $name));
                }
        }

        /**
         * Send examination data to peer.
         * @throws ExportException
         */
        public function send()
        {
                $data = new ExportData($this->exam, $this->options);
                $formatter = ExportFormatter::create($this->format);
                $formatter->send($data);
        }

        /**
         * Write examination data to file.
         * @param string $filename The file name.
         * @throws ExportException
         */
        public function write($filename)
        {
                $data = new ExportData($this->exam, $this->options);
                $formatter = ExportFormatter::create($this->format);
                $formatter->write($data, $filename);
        }

}

/**
 * Class representing the data to export.
 * 
 * @property-read ResultSet $project Project properties.
 * @property-read ResultSet $topics Question topics.
 * @property-read ResultSet $questions Examination questions.
 * @property-read ResultSet $answers Answers grouped by question.
 * @property-read ResultSet $roles Roles assigned to persons on the examiniation.
 * @property-read array $data The raw examination data.
 * @property-read int $exam The examination ID.
 */
class ExportData
{

        const data = "data";
        const exam = "exam";

        // 
        // Constants for accessing the data array:
        // 
        const pdata = "project";
        const tdata = "topics";
        const qdata = "questions";
        const adata = "answers";
        const rdata = "roles";

        private $exam;
        private $data;

        /**
         * Constructor.
         * @param int $exam The examination ID.
         * @param int $options An bitmask of one or more OPENEXAM_EXPORT_INCLUDE_XXX defines.
         */
        public function __construct($exam, $options)
        {
                $this->exam = $exam;
                $this->data = $this->collect($options);
        }

        public function __get($name)
        {
                $name = lcfirst($name);
                if ($name == self::data || $name == self::exam) {
                        return $this->name;
                } else {
                        return $this->data[$name];
                }
        }

        public function __isset($name)
        {
                $name = lcfirst($name);
                return isset($this->$name) ? true : isset($this->data[$name]);
        }

        private function collect($options)
        {
                $pdata = $tdata = $qdata = $rdata = $adata = null;
                $manager = new Manager($this->exam);

                //
                // Get the data to be exported:
                //
                if ($options & OPENEXAM_EXPORT_INCLUDE_PROJECT) {
                        $pdata = $manager->getData();
                }
                if ($options & OPENEXAM_EXPORT_INCLUDE_TOPICS) {
                        $tdata = array();
                }
                if ($options & OPENEXAM_EXPORT_INCLUDE_QUESTIONS) {
                        $qdata = $manager->getQuestions();
                }
                if ($options & OPENEXAM_EXPORT_INCLUDE_ROLES) {
                        $rdata = array();
                        $manager = new Manager($this->exam);
                        $rdata['contributor'] = $manager->getContributors();
                        $rdata['decoder'] = $manager->getDecoders();
                        $rdata['examiniator'] = $manager->getExaminators();
                }
                if ($options & OPENEXAM_EXPORT_INCLUDE_ANSWERS) {
                        $adata = array();
                }

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
                // Collect all answers:
                // 
                foreach ($qdata as $q) {
                        $correct = new Correct($this->exam);
                        $answers = $correct->getQuestionAnswers($q->getQuestionID());
                        $adata[$q->getQuestionID()] = $answers;
                }

                // 
                // Return data array:
                // 
                return array(
                        self::pdata => $pdata,
                        self::tdata => $tdata,
                        self::qdata => $qdata,
                        self::adata => $adata,
                        self::rdata => $rdata
                );
        }

}

/**
 * Export the exam in OpenExam native format (XML). Depends on the SimpleXML and DOM extensions.
 *
 * The exported XML document will have this layout:
 * 
 * <pre><code>
 * <openexam format="version">
 *  <project>
 *     * ... project properties (name, description, ...)
 *  </project>
 *  <topics>
 *    <topic id="num">
 *      <name>str</name>
 *      <randomize>num</randomize>
 *    </topic>
 *      ...
 *  </topics>
 *  <questions>
 *    <question topic="id">
 *       * ... question fields
 *    </question>
 *    <question topic="id">
 *       * ... question fields
 *    </question>
 *      ...
 *  </questions>
 * </openexam>
 * </code></pre>
 */
class ExportFormatOpenExam extends ExportFormatter
{

        public function __construct()
        {
                if (!extension_loaded("SimpleXML")) {
                        throw new ExportException("The SimpleXML extension is not loaded");
                }
                if (!extension_loaded("dom")) {
                        throw new ExportException("The DOM Document extension (dom) is not loaded");
                }
        }

        public function write($data, $filename)
        {
                $this->output($data, $filename);
        }

        public function send($data)
        {
                ob_end_clean();

                $name = sprintf("%s - %s - %s", $data->project->getExamOrgUnit(), $data->project->getExamName(), $data->project->getExamStartTime());

                header("Content-type: application/xml;charset=utf-8\n");
                header("Content-Disposition: attachement; filename=\"$name.xml\"");
                header("Cache-Control: private");
                header("Pragma-directive: no-cache");
                header("Cache-directive: no-cache");
                header("Pragma: public");
                header("Expires: 0");

                $this->output($data);
        }

        /**
         * Output examination data to peer or filename. If filename is not null, then the data
         * is written to disk instead of the peer connection.
         * 
         * @param ExportData $data The data to export.
         * @param string $filename The file to write.
         * @throws ExportException
         */
        private function output($data, $filename = null)
        {
                $root = new SimpleXMLElement("<?xml version='1.0' standalone='yes'?><openexam></openexam>\n");

                // 
                // Set export format version:
                //
                $root->addAttribute("format", OPENEXAM_EXPORT_FORMAT_VERSION);


                //
                // Create project nodes:
                //
                if (isset($data->project)) {
                        $pnode = $root->addChild("project");
                        $pnode->addChild("name", $data->project->getExamName());
                        $pnode->addChild("description", $data->project->getExamDescription());
                        $pnode->addChild("orgunit", $data->project->getExamOrgUnit());
                        $pnode->addChild("starttime", $data->project->getExamStartTime());
                        $pnode->addChild("endtime", $data->project->getExamStartTime());
                        $pnode->addChild("created", $data->project->getExamCreated());
                        $pnode->addChild("updated", $data->project->getExamUpdated());
                        $pnode->addChild("creator", $data->project->getExamCreator());
                        $pnode->addChild("grades", $data->project->getExamGrades());
                }
                //
                // Create all topic nodes:
                //
                if (isset($data->topics)) {
                        $tnode = $root->addChild("topics");
                        foreach ($data->topics as $id => $t) {
                                $child = $tnode->addChild("topic");
                                $child->addAttribute("id", $id);
                                $child->addChild("name", $t['name']);
                                $child->addChild("randomize", $t['randomize']);
                        }
                }

                //
                // Create all question nodes:
                //
                if (isset($data->questions)) {
                        $qnode = $root->addChild("questions");
                        foreach ($data->questions as $q) {
                                $child = $qnode->addChild("question");
                                $child->addAttribute("id", $q->getQuestionID());
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
                }

                // 
                // Create answer nodes for this question:
                // 
                if (isset($data->answers)) {
                        $anode = $root->addChild("answers");
                        foreach ($data->questions as $q) {
                                $answers = $data->answers[$q->getQuestionID()];
                                foreach ($answers as $a) {
                                        $child = $anode->addChild("answer");
                                        $child->addAttribute("question", $q->getQuestionID());
                                        $child->addAttribute("user", $a->getStudentUser());
                                        $child->addChild("text", $a->getAnswerText());
                                        $child->addChild("score", $a->getResultScore());
                                        $child->addChild("acomment", $a->getAnswerComment());
                                        $child->addChild("rcomment", $a->getResultComment());
                                }
                        }
                }

                // 
                // Create role nodes:
                // 
                if (isset($data->roles)) {
                        $rnode = $root->addChild("roles");
                        foreach ($data->roles as $role => $users) {
                                if ($users->count() > 0) {
                                        $child = $rnode->addChild($role);
                                        foreach ($users as $user) {
                                                $hasfnc = sprintf("has%suser", $role);
                                                $getfnc = sprintf("get%suser", $role);
                                                if ($user->$hasfnc()) {
                                                        $child->addChild("user", $user->$getfnc());
                                                }
                                        }
                                }
                        }
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
                if (isset($filename)) {
                        $dom->save($filename);
                } else {
                        echo $dom->saveXML();
                }
        }

}

class ExportFormatWord2007 extends ExportFormatter
{

        public function send($data)
        {
                
        }

        public function write($data, $filename)
        {
                
        }

}

class ExportFormatExcel2007 extends ExportFormatter
{

        public function send($data)
        {
                
        }

        public function write($data, $filename)
        {
                
        }

}

class ExportFormatPDF extends ExportFormatter
{

        public function send($data)
        {
                
        }

        public function write($data, $filename)
        {
                
        }

}

?>
