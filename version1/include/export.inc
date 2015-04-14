<?php

//
// Copyright (C) 2010-2014 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   include/export.inc
// Author: Anders Lövgren
// Date:   2010-12-11
//
// Export utility. 
// 
// History: Only exports in OpenExam project format (XML) was supported and the data exported was
// limited to properties and questions. By request, other formats (i.e. PDF) was requested, as 
// well as, export to foreign systems. 
// 
// To meet these requirements, the export has been split into a generic frontend class (Export) 
// that uses pluggable classes that takes care of document formatting and a delegate class that
// interacts with the formatting class to output requested data.
// 
// New output formats can be added by inheriting from the ExportFormatter class an register the
// plugin in its create() method.
// 
// 
// Output format options:
// 
if (!defined("OPENEXAM_EXPORT_FORMAT_NATIVE")) {
        define("OPENEXAM_EXPORT_FORMAT_NATIVE", "native");
}
if (!defined("OPENEXAM_EXPORT_FORMAT_JSON")) {
        define("OPENEXAM_EXPORT_FORMAT_JSON", "json");
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
if (!defined("OPENEXAM_EXPORT_INCLUDE_ANSWERS")) {
        define("OPENEXAM_EXPORT_INCLUDE_ANSWERS", 8);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_RESULTS")) {
        define("OPENEXAM_EXPORT_INCLUDE_RESULTS", 16);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_STUDENTS")) {
        define("OPENEXAM_EXPORT_INCLUDE_STUDENTS", 32);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_ROLES")) {
        define("OPENEXAM_EXPORT_INCLUDE_ROLES", 64);
}
// 
// Simplified include options:
// 
if (!defined("OPENEXAM_EXPORT_INCLUDE_DEFAULT")) {
        define("OPENEXAM_EXPORT_INCLUDE_DEFAULT", OPENEXAM_EXPORT_INCLUDE_PROJECT | \
            OPENEXAM_EXPORT_INCLUDE_TOPICS | OPENEXAM_EXPORT_INCLUDE_QUESTIONS);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_CLONE")) {
        define("OPENEXAM_EXPORT_INCLUDE_CLONE", OPENEXAM_EXPORT_INCLUDE_PROJECT | \
            OPENEXAM_EXPORT_INCLUDE_TOPICS | OPENEXAM_EXPORT_INCLUDE_QUESTIONS | \
            OPENEXAM_EXPORT_INCLUDE_ROLES);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_CORRECTION")) {
        define("OPENEXAM_EXPORT_INCLUDE_CORRECTION", OPENEXAM_EXPORT_INCLUDE_DEFAULT | \
            OPENEXAM_EXPORT_INCLUDE_ANSWERS | OPENEXAM_EXPORT_INCLUDE_RESULTS | \
            OPENEXAM_EXPORT_INCLUDE_STUDENTS);
}
if (!defined("OPENEXAM_EXPORT_INCLUDE_ALL")) {
        define("OPENEXAM_EXPORT_INCLUDE_ALL", OPENEXAM_EXPORT_INCLUDE_DEFAULT | \
            OPENEXAM_EXPORT_INCLUDE_ANSWERS | OPENEXAM_EXPORT_INCLUDE_RESULTS | \
            OPENEXAM_EXPORT_INCLUDE_STUDENTS | OPENEXAM_EXPORT_INCLUDE_ROLES);
}

// 
// Version identifier for native export format:
// 
if (!defined("OPENEXAM_EXPORT_FORMAT_VERSION")) {
        define("OPENEXAM_EXPORT_FORMAT_VERSION", 6073);
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
 * Abstract base class for output formating classes. Classes should implement the abstract methods
 * and override dummy functions to provide a pluggable output formatter.
 */
abstract class ExportFormatter
{

        /**
         * House keeping function.
         */
        public function initialize()
        {
                // ignore
        }

        /**
         * House keeping function.
         */
        public function cleanup()
        {
                // ignore                
        }

        /**
         * This should be implemented in document oriented export classes to provide a front page.
         * Data oriented export classes can simply ignore this method.
         * @param ExportData $data The data to export.
         */
        public function addFrontPage($data)
        {
                // ignore                
        }

        /**
         * Add project data to the exported document.
         * @param ExportData $data The data to export.
         */
        abstract function addProjectData($data);

        /**
         * Add questions to the exported document.
         * @param ExportData $data The data to export.
         */
        abstract function addQuestions($data);

        /**
         * Add answers for this question to the exported document.
         * @param ExportData $data The data to export.
         */
        abstract function addAnswers($data);

        /**
         * Add result for answers to the exported document.
         * @param ExportData $data The data to export.
         */
        abstract function addResults($data);

        /**
         * Add topics to the exported document.
         * @param ExportData $data The data to export.
         */
        abstract function addTopics($data);

        /**
         * Add roles to the exported document.
         * @param ExportData $data The data to export.
         */
        abstract function addRoles($data);

        /**
         * Add students to the exported document.
         * @param ExportData $data The data to export.
         */
        abstract function addStudents($data);

        /**
         * Writes the formatted document to file.
         * 
         * @param DataRecord $data The project properties.
         * @param string $filename The file name.
         * @throws ExportException
         */
        abstract function write($filename);

        /**
         * Sends the formatted document to peer.
         * 
         * @param string $name Suggested "save as..." filename.
         * @throws ExportException
         */
        abstract function send($name);

        /**
         * Factory function for creating an output formatting object.
         * 
         * @param string $format The output format (see OPENEXAM_EXPORT_FORMAT_XXX definitions).
         * @return \ExportFormatOpenExam|\ExportFormatWord2007|\ExportFormatExcel2007|\ExportFormatPDF
         * @throws ExportException
         */
        public static function create($format)
        {
                switch ($format) {
                        case OPENEXAM_EXPORT_FORMAT_NATIVE:
                                return new ExportFormatOpenExam();
                        case OPENEXAM_EXPORT_FORMAT_JSON:
                                return new ExportFormatJSON();
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
         * @param string $format The export output format (OPENEXAM_EXPORT_FORMAT_XXX).
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
                $delegate = new ExportDelegate($formatter, $data);
                $delegate->send();
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
                $delegate = new ExportDelegate($formatter, $data);
                $delegate->write($filename);
        }

}

/**
 * Class representing the data to export.
 * 
 * @property-read DataRecord $project Project properties.
 * @property-read ResultSet $questions Examination questions.
 * @property-read array $answers Array of answers grouped by question ID.
 * @property-read array $results Array of results one-to-one mapped by answer ID.
 * @property-read array $topics Question topics.
 * @property-read ResultSet $roles Roles assigned to persons on the examiniation.
 * @property-read ResultSet $students List of students.
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
        const udata = "results";
        const rdata = "roles";
        const sdata = "students";

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
                $pdata = $tdata = $qdata = $rdata = $adata = $sdata = null;
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
                        $rdata = new DataRecord(array());
                }
                if ($options & OPENEXAM_EXPORT_INCLUDE_ANSWERS) {
                        $adata = array();
                }
                if ($options & OPENEXAM_EXPORT_INCLUDE_RESULTS) {
                        $udata = array();
                }
                if ($options & OPENEXAM_EXPORT_INCLUDE_STUDENTS) {
                        $sdata = $manager->getStudents();
                }

                //
                // Collect all topics:
                //
                if (isset($tdata) && isset($qdata)) {
                        foreach ($qdata as $q) {
                                if (!isset($tdata[$q->getTopicID()])) {
                                        $record = new DataRecord(array());
                                        $record->setTopicName($q->getTopicName());
                                        $record->setTopicRandom($q->getTopicRandom());
                                        $tdata[$q->getTopicID()] = $record;
                                }
                        }
                }

                // 
                // Collect all answers:
                // 
                if (isset($adata) && isset($qdata)) {
                        foreach ($qdata as $q) {
                                $correct = new Correct($this->exam);
                                $answers = $correct->getQuestionAnswers($q->getQuestionID());
                                $adata[$q->getQuestionID()] = $answers;
                        }
                }

                // 
                // Collect all results:
                // 
                if (isset($udata) && isset($adata)) {
                        foreach ($adata as $qid => $answers) {
                                foreach ($answers as $a) {
                                        $udata[$a->getAnswerID()] = $a;
                                }
                        }
                }

                // 
                // Collect all roles:
                // 
                if (isset($rdata)) {
                        $rdata->setContributors($manager->getContributors());
                        $rdata->setDecoders($manager->getDecoders());
                        $rdata->setExaminators($manager->getExaminators());
                }

                // 
                // Return data array:
                // 
                return array(
                        self::pdata => $pdata,
                        self::tdata => $tdata,
                        self::qdata => $qdata,
                        self::adata => $adata,
                        self::udata => $udata,
                        self::rdata => $rdata,
                        self::sdata => $sdata
                );
        }

}

/**
 * This class feeds data to the export formatter.
 */
class ExportDelegate
{

        private $formatter;
        private $data;
        private $name;

        /**
         * Constructor.
         * @param ExportFormatter $formatter The formatter object.
         * @param ExportData $data The data to export.
         */
        public function __construct($formatter, $data)
        {
                $this->formatter = $formatter;
                $this->data = $data;
                $this->name = sprintf("OpenExam Project - Exported - %s", strftime(DataRecord::formatDateTime(time())));
        }

        /**
         * Send the formatted document to peer.
         */
        public function send()
        {
                if (ob_get_level()) {
                        ob_end_clean();
                }
                $this->prepare();
                $this->formatter->send($this->name);
                $this->formatter->cleanup();
        }

        /**
         * Write the formatted document to file.
         * @param string $filename The destination file name.
         */
        public function write($filename)
        {
                $this->prepare();
                $this->formatter->write($filename);
                $this->formatter->cleanup();
        }

        private function prepare()
        {
                $this->formatter->initialize();
                $this->formatter->addFrontPage($this->data);
                if (isset($this->data->project)) {
                        $this->formatter->addProjectData($this->data);
                }
                if (isset($this->data->roles)) {
                        $this->formatter->addRoles($this->data);
                }
                if (isset($this->data->topics)) {
                        $this->formatter->addTopics($this->data);
                }
                if (isset($this->data->questions)) {
                        $this->formatter->addQuestions($this->data);
                }
                if (isset($this->data->answers)) {
                        $this->formatter->addAnswers($this->data);
                }
                if (isset($this->data->results)) {
                        $this->formatter->addResults($this->data);
                }
                if (isset($this->data->students)) {
                        $this->formatter->addStudents($this->data);
                }
        }

}

/**
 * Export the exam in OpenExam native format (XML). Depends on the 
 * SimpleXML and DOM extensions. See docs/schema/openexam.xsd for
 * XML schema.
 * 
 * @see docs/openexam.xsd
 */
class ExportFormatOpenExam extends ExportFormatter
{

        protected $root;
        protected $proj;

        public function __construct()
        {
                if (!extension_loaded("SimpleXML")) {
                        throw new ExportException("The SimpleXML extension is not loaded");
                }
                if (!extension_loaded("dom")) {
                        throw new ExportException("The DOM Document extension (dom) is not loaded");
                }
        }

        public function initialize()
        {
                $this->root = new SimpleXMLElement("<?xml version='1.0' standalone='yes'?><openexam></openexam>\n");
                $this->root->addAttribute("format", OPENEXAM_EXPORT_FORMAT_VERSION);
        }

        public function addAnswers($data)
        {
                $anode = $this->root->addChild("answers");
                foreach ($data->questions as $q) {
                        $answers = $data->answers[$q->getQuestionID()];
                        foreach ($answers as $a) {
                                $child = $anode->addChild("answer");
                                $child->addAttribute("id", $a->getAnswerID());
                                $child->addAttribute("question", $q->getQuestionID());
                                $child->addAttribute("user", $a->getStudentUser());
                                $child->addChild("text", $a->getAnswerText());
                                $child->addChild("score", $a->getResultScore());
                                $child->addChild("comment", $a->getAnswerComment());
                        }
                }
        }

        public function addResults($data)
        {
                $unode = $this->root->addChild("results");
                foreach ($data->results as $r) {
                        $child = $unode->addChild("result");
                        $child->addAttribute("id", $r->getResultID());
                        $child->addAttribute("answer", $r->getAnswerID());
                        $child->addAttribute("user", $r->getStudentUser());
                        $child->addChild("score", $r->getResultScore());
                        $child->addChild("comment", $r->getResultComment());
                }
        }

        public function addProjectData($data)
        {
                $this->proj = $data->project;   // Keep for future references.

                $pnode = $this->root->addChild("project");
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

        public function addQuestions($data)
        {
                $qnode = $this->root->addChild("questions");
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

        public function addRoles($data)
        {
                $rnode = $this->root->addChild("roles");
                $child = $rnode->addChild('contributor');
                foreach ($data->roles->getContributors() as $user) {
                        $child->addChild("user", $user->getContributorUser());
                }
                $child = $rnode->addChild('examinator');
                foreach ($data->roles->getExaminators() as $user) {
                        $child->addChild("user", $user->getExaminatorUser());
                }
                $child = $rnode->addChild('decoder');
                foreach ($data->roles->getDecoders() as $user) {
                        $child->addChild("user", $user->getDecoderUser());
                }
        }

        public function addStudents($data)
        {
                $snode = $this->root->addChild("students");
                foreach ($data->students as $student) {
                        $child = $snode->addChild("student");
                        $child->addChild("user", $student->getStudentUser());
                        $child->addChild("code", $student->getStudentCode());
                        $child->addChild("tag", $student->getStudentTag());
                }
        }

        public function addTopics($data)
        {
                $tnode = $this->root->addChild("topics");
                foreach ($data->topics as $id => $t) {
                        $child = $tnode->addChild("topic");
                        $child->addAttribute("id", $id);
                        $child->addChild("name", $t->getTopicName());
                        $child->addChild("random", $t->getTopicRandom());
                }
        }

        public function send($name)
        {
                header("Content-type: application/xml;charset=utf-8\n");
                header("Content-Disposition: attachement; filename=\"$name.xml\"");
                header("Cache-Control: private");
                header("Pragma-directive: no-cache");
                header("Cache-directive: no-cache");
                header("Pragma: public");
                header("Expires: 0");

                $this->output();
        }

        public function write($filename)
        {
                $this->output($filename);
        }

        /**
         * Send document to destination file or peer.
         * @param string $filename The file name.
         * @throws ExportException
         */
        private function output($filename = null)
        {
                // 
                // Now, the only thing left is to insert a comment line in the
                // header. Unfortunate, Simple XML don't support comments, but 
                // we can use DOM for that with a little more code:
                //
                if (!($node = dom_import_simplexml($this->root))) {
                        throw new ExportException("Failed import XML tree to DOM document.");
                }

                $dom = new DOMDocument('1.0', 'utf-8');
                $dom->formatOutput = true;

                $comment = sprintf(_(" OpenExam project exported %s "), utf8_encode(strftime('%c')));
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

/**
 * Export the exam in JSON encoded format. Let parent class handle data
 * aggregation and 
 */
class ExportFormatJSON extends ExportFormatOpenExam
{

        public function send($name)
        {
                header("Content-type: application/json;charset=utf-8\n");
                header("Content-Disposition: attachement; filename=\"$name.json\"");
                header("Cache-Control: private");
                header("Pragma-directive: no-cache");
                header("Cache-directive: no-cache");
                header("Pragma: public");
                header("Expires: 0");

                echo json_encode($this->root, JSON_PRETTY_PRINT);
        }

        public function write($filename = null)
        {
                if (isset($filename)) {
                        file_put_contents($filename, json_encode($this->root, JSON_PRETTY_PRINT));
                } else {
                        echo json_encode($this->root, JSON_PRETTY_PRINT);
                }
        }

}

?>
