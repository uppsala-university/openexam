<?php

// 
// Copyright (C) 2010, 2014 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/pdf.inc
// Author: Anders Lövgren
// Date:   2010-05-12
// 
// Support for generating PDF files.
// 
// Currently we do the conversion by using the application HTMLDOC for converting 
// structured HTML (called *.book files) to PDF, by piping HTML on its stdin using
// a UNIX command pipeline.
// 
// These classes should probably be modified to use a cache of already generated
// files, especial useful when generating results for a whole exam (takes about 
// 5 sec for 30 students). For a large axamination, we might hit the script execution
// timeout.
// 
//
// Version 1.8.27 of HTMLDOC do not support UTF-8. We must explicit convert
// from UTF-8 from database and LDAP. This causes some other problems, most
// notable is problem related to using gettext.
//

if (!defined('PDF_ENGINE')) {
        define('PDF_ENGINE', 'htmldoc');
}
if (!defined('PDF_LOGOTYPE')) {
        define('PDF_LOGOTYPE', sprintf('%s/images/uu-logotype-4f-84.png', BASE_URL));
}
if (!defined('PDF_LINK_COLOR')) {
        define('PDF_LINK_COLOR', '#000099');
}
if (!defined('PDF_LINK_STYLE')) {
        define('PDF_LINK_STYLE', 'plain');
}
if (!defined('PDF_MEDIA_SIZE')) {
        define('PDF_MEDIA_SIZE', 'A4');
}
if (!defined('PDF_MEDIA_TYPE')) {
        define('PDF_MEDIA_TYPE', 'plain');
}
if (!defined('PDF_MEDIA_LANDSCAPE')) {
        define('PDF_MEDIA_LANDSCAPE', false);
}
if (!defined('PDF_GENERATOR')) {
        define('PDF_GENERATOR', 'OpenExam PHP');
}
if (!defined('PDF_OUTPUT_FORMAT')) {
        define('PDF_OUTPUT_FORMAT', 'pdf14');
}
if (!defined('PDF_QUESTION_MIN_LENGTH')) {
        define('PDF_QUESTION_MIN_LENGTH', 12);
}

// 
// All classes feeding input to HTMLDOC should implement this interface
// and call begin() passing itself as a class reference.
// 
interface InputGenerator
{

        public function write($stream);
}

// 
// Encode the output stream from UTF-8 to ISO-8859-1. This class hooks into
// the stream processing when genrating the HTML for HTMLDOC and converts
// the character encoding from UTF-8 to ISO-8859-1.
//
// The //TRANSLIT is appended to the output charset to activate transliteration.
// This means that when a character can't be represented in the target charset,
// it can be approximated through one or several similarly looking characters.
//
class ResultStreamEncoder extends php_user_filter
{

        const icse = "UTF-8";
        const ocse = "ISO-8859-1//TRANSLIT";

        function filter($in, $out, &$consumed, $closing)
        {
                while ($bucket = stream_bucket_make_writeable($in)) {
                        $bucket->data = iconv(self::icse, self::ocse, $bucket->data);
                        $consumed += $bucket->datalen;
                        stream_bucket_append($out, $bucket);
                }
                return PSFS_PASS_ON;
        }

}

class HtmlDocPDF
{

        const filter_name = "result_stream_encoder";
        const filter_class = "ResultStreamEncoder";

        protected $title;     // The page title
        protected $author;    // The publisher
        protected $debug = false;
        protected $format = PDF_OUTPUT_FORMAT;
        protected $frontpage = null;

        protected function __construct($title, $author)
        {
                $this->title = $title;
                $this->author = $author;

                stream_filter_register(self::filter_name, self::filter_class);
        }

        //
        // Remember to remove the temporary front page file.
        //
        public function __destruct()
        {
                if (file_exists($this->frontpage)) {
                        unlink($this->frontpage);
                }
        }

        //
        // Enable debug (no command is executed and command input is written
        // to stdout instead).
        //
        public function setDebug($value)
        {
                $this->debug = $value;
        }

        //
        // Specifies the output format. Possible values are:
        //
        // *) "html":    Output plain HTML.
        // *) "htmlsep": Separate HTML files for each heading in the table-of-contents.
        // *) "ps" or
        //    "ps2":     PostScript Level 2.
        // *) "ps1":     PostScript Level 1.
        // *) "ps3":     PostScript Level 3.
        // *) "pdf11":   PDF 1.1/Acrobat 2.0.
        // *) "pdf12":   PDF 1.2/Acrobat 3.0.
        // *) "pdf" or
        //    "pdf13":   PDF 1.3/Acrobat 4.0.
        // *) "pdf14":   PDF 1.4/Acrobat 5.0.
        //
        public function setFormat($value)
        {
                $this->format = $value;
        }

        //
        // Get the command string for writing the PDF to target. If target is null,
        // then output is written to stdout.
        //
        private function getCommand($target)
        {
                $fmt = "htmldoc --book --format %s --titlefile %s --linkstyle %s --size %s --linkcolor '%s' --toctitle '%s' --toclevels 2 -";
                $cmd = sprintf($fmt, $this->format, $this->frontpage, PDF_LINK_STYLE, PDF_MEDIA_SIZE, PDF_LINK_COLOR, utf8_decode(_("Table of Contents")));
                if (isset($target)) {
                        $cmd .= sprintf(" -f %s", $target);
                }
                return $cmd;
        }

        //
        // Open the HTMLDOC pipeline and call back using the class reference to
        // get the input data using the multiplexed stream resource.
        //
        protected function begin($class, $target = null)
        {
                try {
                        if ($this->debug) {
                                $pipes = array(fopen("php://output", "w"));
                        } else {
                                $fds = array(
                                        0 => array("pipe", "r"),
                                        1 => array("pipe", "w"),
                                        2 => array("pipe", "w")
                                );

                                $cmd = $this->getCommand($target);
                                $env = array("SHELL" => "/bin/bash", "TZ" => "UTC");
                                $pipes = array();

                                if (version_compare(phpversion(), "5.0.0") < 0) {
                                        $timezone = getenv("TZ");
                                        putenv(sprintf("TZ=%s", $env['TZ']));
                                        $process = proc_open($cmd, $fds, $pipes);
                                        putenv(sprintf("TZ=%s", $timezone));
                                } else {
                                        $process = proc_open($cmd, $fds, $pipes, null, $env);
                                }

                                if (!is_resource($process)) {
                                        throw new Exception(sprintf("Failed start process %s", $cmd));
                                }
                        }

                        if ((isset($process) && is_resource($process)) || $this->debug) {
                                $filter = stream_filter_append($pipes[0], self::filter_name);
                                $class->write($pipes[0]);
                                stream_filter_remove($filter);

                                if (!$this->debug) {
                                        fclose($pipes[0]);

                                        $stdout = stream_get_contents($pipes[1]);
                                        $stderr = stream_get_contents($pipes[2]);

                                        if (strlen($stderr) > 0) {
                                                //
                                                // HTMLDOC got the bad habit of writing status info to stderr.
                                                //
                                        if (!preg_match("/^(BYTES: \d+|PAGES: \d+)$/msU", $stderr)) {
                                                        throw new Exception(sprintf("Failed generate PDF: %s", $stderr));
                                                }
                                        }
                                        if (strlen($stdout) > 0) {
                                                echo $stdout;
                                        }

                                        fclose($pipes[1]);
                                        fclose($pipes[2]);

                                        $result = proc_close($process);

                                        //
                                        // Check exit status of process.
                                        //
                                        if ($result > 0 && $result != 2) {
                                                throw new Exception(sprintf("HTMLDOC finished with non-zero exit code %d", $result), $result);
                                        }
                                }
                        }
                } catch (Exception $exception) {
                        error_log($exception);
                        throw $exception;
                }
        }

}

// 
// Generates the examination result PDF for a single student or all students
// registered on the exam. The output can either be sent to the browser or
// saved to a file.
// 
class ResultPDF extends HtmlDocPDF implements InputGenerator
{

        private $manager;
        private $student;   // Current student ID
        private $exam;      // Exam data
        private $ldap;      // For LDAP search
        private $board;     // Score board object
        private $grade;     // Examination grade object
        private $filter;    // Score board filter.
        private $stats;     // Statistics.

        public function __construct($examid)
        {
                $this->manager = new Manager($examid);
                $this->exam = $this->manager->getData();

                $this->ldap = new LdapSearch(LdapConnection::instance());
                $this->ldap->setAttributeFilter(array("displayname", "mail", "cn"));

                $this->filter = new ScoreBoardFilter($examid, true, DECODED_SHOW_UNATTENDED);

                $this->board = new ScoreBoard($this->exam->getExamID(), $this->filter);
                $this->grade = new ExamGrades($this->exam->getExamGrades());
                $this->stats = $this->getStatistics();

                parent::__construct($this->exam->getExamName(), $this->exam->getExamOrgUnit());
        }

        public function __destruct()
        {
                parent::__destruct();
        }

        //
        // Create the frontpage. If previous frontpage exists, then it's
        // unlinked (deleted) before the new one is created.
        //
        private function createFrontPage($name)
        {
                $stream = fopen($this->frontpage, "w");
                if ($stream) {
                        $filter = stream_filter_append($stream, self::filter_name);

                        fprintf($stream, "<html>\n");
                        fprintf($stream, "<body>\n");
                        fprintf($stream, "<br/><br/>\n");
                        fprintf($stream, "<center>\n");
                        fprintf($stream, "<img src=\"%s\" />\n", PDF_LOGOTYPE);
                        fprintf($stream, "<br/><br/><br/><br/><br/><br/>\n");
                        fprintf($stream, "<h1>%s</h1>\n", $this->title);
                        fprintf($stream, "<h2>%s</h2>\n", $this->author);
                        fprintf($stream, "<br/><br/><br/><br/>\n");
                        fprintf($stream, "<table><tr><td align=\"left\">\n");
                        fprintf($stream, "<h4>%s: %s</h4>\n", _("Examination Date"), strftime(DATE_FORMAT, strtotime($this->exam->getExamStartTime())));
                        fprintf($stream, "<h4>%s: %s</h4>\n", _("Participant"), $name);
                        fprintf($stream, "</td></tr></table>\n");
                        fprintf($stream, "</center>\n");
                        fprintf($stream, "</body>\n");
                        fprintf($stream, "</html>\n");

                        stream_filter_remove($filter);
                        fclose($stream);
                }
        }

        //
        // Output meta data.
        //
        private function printMetaData($stream)
        {
                fprintf($stream, "<!-- FOOTER CENTER \"\$CHAPTER\" -->\n");
                fprintf($stream, "<!-- FOOTER LEFT \"%s\" -->\n", _('Page $PAGE of $PAGES'));
                fprintf($stream, "<!-- FOOTER RIGHT \"\$DATE \$TIME\" -->\n");

                fprintf($stream, "<!-- MEDIA SIZE %s -->\n", strtoupper(PDF_MEDIA_SIZE));
                fprintf($stream, "<!-- MEDIA TYPE %s -->\n", strtoupper(PDF_MEDIA_TYPE));
                fprintf($stream, "<!-- MEDIA LANDSCAPE %s -->\n", PDF_MEDIA_LANDSCAPE ? "YES" : "NO");

                fprintf($stream, "<META NAME=\"AUTHOR\" CONTENT=\"%s\" />\n", $this->author);
                fprintf($stream, "<META NAME=\"GENERATOR\" CONTENT=\"%s\" />\n", PDF_GENERATOR);
                fprintf($stream, "<META NAME=\"KEYWORDS\" CONTENT=\"exam,openexam,php,online\" />\n");
                fprintf($stream, "<META NAME=\"SUBJECT\" CONTENT=\"%s\" />\n", $this->title);
        }

        //
        // Output the examination description section.
        //
        private function printSectionDescription($stream)
        {
                fprintf($stream, "<h1>%s</h1>\n", _("Description"));
                fprintf($stream, "<p>%s</p>\n", str_replace("\n", "<br/>", $this->exam->getExamDescription()));
        }

        //
        // Output the scores section.
        //
        private function printSectionScores($stream)
        {
                fprintf($stream, "<h1>%s</h1>\n", _("Examination Result"));

                //
                // The summary section.
                //
                $score = $this->board->getStudentScore($this->student);
                fprintf($stream, "<h2>%s</h2>\n", _("Summary"));
                fprintf($stream, "<p>" . _("You scored total %.01f of maximum %.01f points (%.01f%%) on this examination.") . "</p>\n", $score->getSum(), $this->board->getMaximumScore(), 100 * $score->getSum() / $this->board->getMaximumScore());

                //
                // The graduation section.
                //
                fprintf($stream, "<h2>%s</h2>\n", _("Graduation"));
                fprintf($stream, "<p>" . _("Your graduation on this examination is: <b>%s</b>.") . "</p>\n", $this->grade->getGrade($score->getSum()));

                //
                // The answer result details section.
                //
                fprintf($stream, "<h2>%s</h2>\n", _("Score Table"));
                fprintf($stream, "<p>%s</p>\n", _("This table shows a summary of your score versus the maximum scores. Questions without answer are marked with an '-' in the table."));
                fprintf($stream, "<table><tr align=\"right\"><th>%s</th><th>&nbsp;</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>\n", _("Question"), _("Score"), _("Max score"), _("Percent"), _("Notice"));
                $questions = $this->manager->getQuestions(null, $this->student);
                foreach ($questions as $question) {
                        fprintf($stream, "<tr align=\"right\"><td>%s</td><td>&nbsp;</td>", $question->getQuestionName());
                        $data = $this->board->getData($this->student, $question->getQuestionID());
                        if ($question->getQuestionStatus() == 'active') {
                                if ($data->getAnswerExist() == 'Y') {
                                        fprintf($stream, "<td>%.01f</td><td>%.01f</td><td>%.01f<td>", $data->getResultScore(), $question->getQuestionScore(), 100 * $data->getResultScore() / $question->getQuestionScore());
                                } else {
                                        fprintf($stream, "<td>-</td><td>%.01f</td><td>%.01f<td>", $question->getQuestionScore(), 0);
                                }
                        } else {
                                fprintf($stream, "<td>-</td><td>-</td><td>-<td><td>%s</td>", _("Removed"));
                                $this->removed++;
                        }
                        fprintf($stream, "</tr>\n");
                }
                fprintf($stream, "<tr align=\"right\"><td>---</td><td>&nbsp;</td><td>---</td><td>---</td><td>---</td></tr>\n");
                fprintf($stream, "<tr align=\"right\"><td><b>%s:</b></td><td>&nbsp;</td><td><b>%.01f</b></td><td><b>%.01f</b></td><td><b>%.01f%%</b></td></tr>\n", _("Summary"), $score->getSum(), $this->board->getMaximumScore(), 100 * $score->getSum() / $this->board->getMaximumScore());
                fprintf($stream, "</table><br/>\n");
        }

        //
        // Helper function for printing a question and (possibly) the students
        // answer to it.
        //
        private function printQuestionAnswer($stream, $question, $answer)
        {
                fprintf($stream, "<!-- NEED %d -->\n<br/>\n", PDF_QUESTION_MIN_LENGTH);
                fprintf($stream, "<h2>%s: %s</h2>\n", _("Question"), $question->getQuestionName());

                //
                // Output tags for image if question has one:
                //
                if ($question->hasQuestionImage()) {
                        fprintf($stream, "<img src=\"%s\" />\n", $question->getQuestionImage());
                }

                //
                // Print the block of text containing the question. Format this
                // section different depending on the question type.
                //
                if ($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                        fprintf($stream, "<p>%s</p>\n", str_replace("\n", "<br/>", $question->getQuestionText()));
                } elseif ($question->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE ||
                    $question->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE) {
                        $qchoice = Exam::getQuestionChoice($question->getQuestionText(), true);
                        fprintf($stream, "<p>%s</p>\n", str_replace("\n", "<br/>", $qchoice[0]));

                        fprintf($stream, "<ol>\n");
                        foreach ($qchoice[1] as $choice => $correct) {
                                fprintf($stream, "<li>%s</li>\n", $choice);
                        }
                        fprintf($stream, "</ol>\n");

                        fprintf($stream, "%s: %s\n", _("Correct answer"), implode(", ", array_keys($qchoice[1], true)));
                }
                fprintf($stream, "<p>[%s: %.01f]</p>\n", _("Max score"), $question->getQuestionScore());

                if ($question->getQuestionStatus() == 'removed') {
                        if ($question->getQuestionComment() != null) {
                                fprintf($stream, "<p><b>%s</b>: %s</p>\n", _("Teachers reason for removal"), $question->getQuestionComment());
                        } else {
                                fprintf($stream, "<p><b>%s</b></p>\n", _("This question was removed without an explanation from the teacher."));
                        }
                }

                //
                // The answer together with comment (if set).
                //
                if (isset($answer)) {
                        fprintf($stream, "<h3>%s</h3>\n", _("Answer"));
                        if ($question->getQuestionType() == QUESTION_TYPE_FREETEXT) {
                                $text = str_replace("\n", "<br/>", htmlspecialchars($answer->getAnswerText()));
                                fprintf($stream, "<p>%s</p>\n", $text);
                        } elseif ($question->getQuestionType() == QUESTION_TYPE_SINGLE_CHOICE ||
                            $question->getQuestionType() == QUESTION_TYPE_MULTI_CHOICE) {
                                $achoice = Exam::getQuestionChoice($answer->getAnswerText());
                                fprintf($stream, "<p>%s</p>\n", implode(", ", $achoice[1]));
                        }
                        if ($answer->hasAnswerComment()) {
                                fprintf($stream, "<u>%s</u>: %s<br/>\n", _("Your comments"), $answer->getAnswerComment());
                        }
                        if ($answer->hasResultComment()) {
                                fprintf($stream, "<u>%s</u>: %s<br/>\n", _("Teacher comments"), $answer->getResultComment());
                        }
                        fprintf($stream, "<p>[%s: %.01f]</p>\n", _("Score"), $answer->getResultScore());
                }
        }

        //
        // Output the question/answer section.
        //
        private function printSectionAnswers($stream)
        {
                $handler = new Correct($this->exam->getExamID());
                $answers = $handler->getStudentAnswers($this->student, true);
                $questions = $this->manager->getQuestions(null, $this->student);

                //
                // Build the associative array of answered ('a'), unanswered ('u')
                // and removed ('r') questions.
                //
                $qadata = array('a' => array(), 'u' => array(), 'r' => array());
                foreach ($questions as $question) {
                        foreach ($answers as $answer) {
                                if ($question->getQuestionID() == $answer->getQuestionID()) {
                                        break;
                                }
                        }
                        if (isset($answer)) {
                                $answered = $answer->getAnswerExist() == 'Y';
                        } else {
                                $answered = false;
                        }
                        if ($question->getQuestionStatus() == 'removed') {
                                $qadata['r'][] = array($question, $answered ? $answer : null);
                        } elseif ($answered) {


                                $qadata['a'][] = array($question, $answer);
                        } else {
                                $qadata ['u'][] = array($question, null);
                        }
                }

                //
                // Now loop thru the array of questions and answers. We use two separate
                // sections for answered and unanswered questions.
                //

                if (count($qadata['a']) > 0) {
                        fprintf($stream, "<h1>%s</h1>\n", _("Questions and Answers"));
                        foreach ($qadata['a'] as $data) {
                                $this->printQuestionAnswer($stream, $data[0], $data[1]);
                        }
                }

                if (count($qadata['u']) > 0) {
                        fprintf($stream, "<h1>%s</h1>\n", _("Unanswered Questions"));
                        foreach ($qadata['u'] as $data) {
                                $this->printQuestionAnswer($stream, $data[0], null);
                        }
                }

                if (count($qadata['r']) > 0) {


                        fprintf($stream, "<h1>%s</h1>\n", _("Removed Questions"));
                        fprintf($stream, "<p>" .
                            _("These questions has been removed from this examination. ") .
                            _("The questions, along with your answers (if any) and the teacher appraisal, is included here for reference, but do not affect your examination score or grade.") .
                            "</p>\n");
                        foreach ($qadata['r'] as $data) {
                                $this->printQuestionAnswer($stream, $data[0], $data[1]);
                        }
                }
        }

        //
        // Print sub section for examination creator.
        //
        private function printSubSectionExamCreator($stream)
        {
                $user = $this->ldap->searchPrincipalName($this->exam->getExamCreator());

                fprintf($stream, "<h5>%s:</h5>\n", _("Responsable for examination"));
                fprintf($stream, "<p><blockquote>" .
                    _("Main responsible person for the examination is <a href=\"mailto:%s\">%s</a>") .
                    "</blockquote></p>\n", $user->first()->getMail()->first(), $user->first()->getDisplayName()->first());
        }

        //
        // Print sub section for question contributors and answer correctors.
        //
        private function printSubSectionContributors($stream)
        {
                $contributors = $this->manager->getContributors();
                if ($contributors->count()) {

                        //
                        // Collect contributors first:
                        //
                        $qdata = array();
                        $questions = $this->manager->getQuestions();
                        foreach ($contributors as $contributor) {
                                foreach ($questions as $question) {
                                        if ($question->getQuestionPublisher() == $contributor->getContributorUser()) {
                                                $uuid = $contributor->getContributorUser();
                                                $name = $question->getQuestionName();
                                                $qdata[$uuid][] = trim($name, ".");
                                        }
                                }
                        }

                        //
                        // Output the list of contributors:
                        //
                        fprintf($stream, "<h5>%s:</h5>\n", _("Correction of answers"));
                        fprintf($stream, "<p><blockquote>" .
                            _("These people have contributed questions for the examination and corrected answers.") .
                            "</blockquote></p>\n");
                        fprintf($stream, "<ul>\n");
                        foreach ($qdata as $uuid => $qlist) {
                                $user = $this->ldap->searchPrincipalName($uuid);
                                fprintf($stream, "<li><a href=\"mailto:%s\">%s</a><br/><blockquote>%s: %s</blockquote></li>\n", $user->first()->getMail(), $user->first()->getDisplayName()->first(), _("Corrected questions"), implode(", ", $qlist));
                        }
                        fprintf($stream, "</ul>\n");
                }
        }

        //
        // Output the examination statistics section.
        //
        private function printSectionStatistics($stream)
        {
                fprintf($stream, "<h1>%s</h1>\n", _("Statistics"));
                fprintf($stream, "<p>" . _("This section shows overall statistics for all participients on this examination.") . "</p>\n");

                //
                // Generate the two statistics tables. We use NEED to ensure
                // that the tables don't get cut apart somewhere in the middle.
                //
                fprintf($stream, "<!-- NEED %d -->\n", count($this->stats->grades['grade']) + 3);
                fprintf($stream, "<h2>%s</h2>\n", _("Distribution"));

                fprintf($stream, "<p>" . _("This table shows the distribution by grades. The number of student allotted for each grade are:") . "</p>\n");
                fprintf($stream, "<table>\n");
                fprintf($stream, "<tr align=\"right\"><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>\n", _("Grade"), _("Threshold"), _("Students"), _("Percent"));
                $sum = array("st" => 0, "pc" => 0);
                foreach ($this->stats->grades['grade'] as $key => $val) {
                        fprintf($stream, "<tr align=\"right\"><td><b>%s</b></td><td>%.01f</td><td>%d</td><td>%.01f</td></tr>\n", $key, $this->grade->getThreshold($key), $val, 100 * $val / $this->board->getStudents()->count());
                        $sum['st'] += $val;
                        $sum['pc'] += 100 * $val / $this->board->getStudents()->count();
                }
                fprintf($stream, "<tr align=\"right\"><td>---</td><td>---</td><td>---</td><td>---</td></tr>\n");
                fprintf($stream, "<tr align=\"right\"><td><b>%s:</b></td><td>&nbsp;</td><td><b>%d</b></td><td><b>%.01f%%</b></td></tr>\n", _("Summary"), $sum['st'], $sum['pc']);
                fprintf($stream, "</table><br/>\n");

                fprintf($stream, "<!-- NEED %d -->\n", count($this->stats->grades['score']) + 3);
                fprintf($stream, "<p>" . _("This table shows the distribution of scores by student on this examination.") . "</p>\n");
                fprintf($stream, "<table>\n");
                fprintf($stream, "<tr><th>&nbsp;</th><th>%s</th><th>%s</th><th>%s</th></tr>\n", _("Score"), _("Percent"), _("Students"));
                $sum = array("st" => 0, "sc" => 0);
                foreach ($this->stats->grades['score'] as $key => $val) {
                        fprintf($stream, "<tr align=\"right\"><td>&nbsp;</td><td><b>%.01f</b></td><td>%.01f</td><td>%d</td></tr>\n", $key, 100 * $key / $this->board->getMaximumScore(), $val);
                        $sum['sc'] += $key * $val;
                        $sum['st'] += $val;
                }
                fprintf($stream, "<tr align=\"right\"><td>&nbsp;</td><td>---</td><td>---</td><td>---</td></tr>\n");
                fprintf($stream, "<tr align=\"right\"><td><b>%s:</b></td><td>%.01f</td><td>&nbsp;</td><td>%d</td></tr>\n", _("Summary"), $sum['sc'], $sum['st']);
                fprintf($stream, "<tr align=\"right\"><td><b>%s:</b></td><td>%.01f (%.01f)</td><td>%.01f%% (%.01f%%)</td></tr>\n", _("Mean Value"), $this->stats->mvs, $this->stats->mvf, 100 * $this->stats->mvs / $this->board->getMaximumScore(), 100 * $this->stats->mvf / $this->board->getMaximumScore());
                fprintf($stream, "<tr align=\"right\"><td><b>%s:</b></td><td>%.01f (%.01f)</td></tr>\n", _("Standard Deviation"), $this->stats->sds, $this->stats->sdf);
                fprintf($stream, "</table><br/>\n");

                //
                // This is funky stuff, so we better provide some description:
                //
                fprintf($stream, "<p>" .
                    _("The mean value, standard deviation and percentage within paranthesis '()' is calculated on all values. ") .
                    _("The other values are calculated by first removing the highest and lowest scores.") .
                    "</p>\n");

                //
                // The result ranges sub section.
                //
                fprintf($stream, "<!-- NEED %d -->\n", count($this->stats->grades['range']) + 3);
                fprintf($stream, "<h2>%s</h2>\n", _("Ranges"));

                fprintf($stream, "<p>" . _("This table shows the number of student in each range of percentage correct answers.") . "</p>\n");
                fprintf($stream, "<table>\n");
                fprintf($stream, "<tr><th>%s</th><th>%s</th></tr>\n", _("Range"), _("Students"));
                $sum = array("st" => 0);
                foreach ($this->stats->grades['range'] as $key => $val) {
                        $percent = $key * 10;
                        switch ($percent) {
                                case 0:
                                        fprintf($stream, "<tr align=\"right\"><td><b>< 10 %%</b></td><td>%d</td></tr>\n", $val);
                                        break;
                                case 90:
                                        fprintf($stream, "<tr align=\"right\"><td><b>> %d %%</b></td><td>%d</td></tr>\n", $percent, $val);
                                        break;
                                default:
                                        fprintf($stream, "<tr align=\"right\"><td><b>%d - %d %%</b></td><td>%d</td></tr>\n", $percent, $percent + 9, $val);
                                        break;
                        }
                        $sum['st'] += $val;
                }
                fprintf($stream, "<tr align=\"right\"><td>---</td><td>---</td><td>---</td></tr>\n");
                fprintf($stream, "<tr align=\"right\"><td><b>%s:</b></td><td>%d</td></tr>\n", _("Summary"), $sum['st']);
                fprintf($stream, "</table><br/>\n");
        }

        //
        // Output the reference section.
        //
        private function printSectionReference($stream)
        {
                $openexam = "http://it.bmc.uu.se/andlov/proj/openexam/";
                $htmldoc = "http://www.easysw.com/htmldoc/";

                fprintf($stream, "<h1>%s</h1>\n", _("Reference"));
                fprintf($stream, "<p>" . _("Information about this examination.") . "</p>\n");

                //
                // Print examination creator and question contributors:
                //
                if ($this->exam->getExamDetails() & RESULT_EXPOSE_EMPLOYEES) {
                        fprintf($stream, "<h2>%s</h2>\n", _("Contact Information"));
                        fprintf($stream, "<p>" . _("This section lists those people thats been involved in this examination.") . "</p>\n");

                        $this->printSubSectionExamCreator($stream);
                        $this->printSubSectionContributors($stream);
                }

                //
                // Print document info:
                //
                fprintf($stream, "<h2>%s</h2>\n", _("Document Information"));
                fprintf($stream, "<p>" .
                    _("This document was generated by <a href=\"%s\">OpenExam PHP</a> installed on <a href=\"%s/\">%s/</a> using the open source version of the <a href=\"%s\">HTMLDOC PostScript&#0174;/PDF generator</a> released under GPL license.") .
                    "</p>\n", $openexam, BASE_URL, BASE_URL, $htmldoc);
                fprintf($stream, "<p>" .
                    _("OpenExam PHP is a secure examination system developed at Uppsala Biomedical Centre, Uppsala University.") .
                    "</p>\n");
        }

        //
        // The arithmetric mean value.
        //
        private function getMeanValue(&$scores)
        {
                if (count($scores) == 0) {
                        return 0;
                }
                $n = count($scores);
                return array_sum($scores) / $n;
        }

        //
        // The standard deviation.
        //
        private function getStandardDeviation(&$scores)
        {
                if (count($scores) == 0) {
                        return 0;
                }

                $n = count($scores);
                $m = array_sum($scores) / $n;

                $sx = array();
                for ($i = 0; $i < $n; $i++) {
                        $sx[$i] = pow(($m - $scores[$i]), 2);
                }
                return sqrt(array_sum($sx) / $n);
        }

        // 
        // Get all statistics.
        // 
        private function getStatistics()
        {
                $stat = new stdClass();

                //
                // The grade distribution sub section.
                //
                $grades = array();
                foreach ($this->board->getStudents() as $student) {
                        $score = $this->board->getStudentScore($student->getStudentID());
                        $class = $this->grade->getGrade($score->getSum());
                        $value = sprintf("%.01f", $score->getSum());
                        $range = intval(floor(10 * $score->getSum() / $this->board->getMaximumScore()));
                        if (!isset($grades['grade'][$class])) {
                                $grades['grade'][$class] = 1;
                        } else {
                                $grades['grade'][$class] ++;   // One more with this grade
                        }
                        if (!isset($grades['score'][$value])) {
                                $grades['score'][$value] = 1;
                        } else {
                                $grades ['score'][$value] ++;
                        }
                        if (!isset($grades['range'][$range])) {
                                $grades['range'][$range] = 1;
                        } else {
                                $grades['range'][$range] ++;
                        }
                }
                krsort($grades['score']);
                krsort($grades['range']);

                //
                // Get mean value and standard deviation. We are going to present
                // values based on all scores and values where we first remove the
                // highest and lowest scores.
                //
                $scf = array();
                foreach ($grades['score'] as $key => $val) {
                        for ($i = 0; $i < $val; $i++) {
                                $scf[] = $key;
                        }
                }
                sort($scf);
                $scs = $scf;

                while (isset($scs[0]) && $scs[0] == 0) {
                        array_shift($scs);
                }
                array_pop($scs);

                $sdf = $this->getStandardDeviation($scf);
                $mvf = $this->getMeanValue($scf);

                $sds = $this->getStandardDeviation($scs);
                $mvs = $this->getMeanValue($scs);

                if ($sds == 0) {
                        $sds = $sdf;
                }
                if ($mvs == 0) {
                        $mvs = $mvf;
                }

                $stat->grades = $grades;
                $stat->sdf = $sdf;
                $stat->sds = $sds;
                $stat->mvf = $mvf;
                $stat->mvs = $mvs;

                return $stat;
        }

        //
        // This is our callback function implementing the interface InputGenerator.
        //
        public function write($stream)
        {
                fprintf($stream, "<html>\n<head><title>%s</title></head>\n<body>\n", $this->title);
                $this->printMetaData($stream);
                $this->printSectionDescription($stream);
                $this->printSectionScores($stream);
                $this->printSectionAnswers($stream);
                if ($this->exam->getExamDetails() & RESULT_OTHERS_STATISTIC) {
                        $this->printSectionStatistics($stream);
                }
                $this->printSectionReference($stream);
                fprintf($stream, "</body></html>\n");
        }

        //
        // Feed the HTMLDOC process with HTML (*.book) on stdin. If debug mode is enabled,
        // then no process is executed, instead the generated HTML is output on stdout.
        //
        private function generate($target = null)
        {
                //
                // We must create the frontpage before launching the process.
                //
                $data = $this->manager->getStudentData($this->student);
                $user = $this->ldap->searchUID($data->getStudentUser());
                if (!$user->first()->hasDisplayName()) {
                        $user = $this->ldap->searchPrincipalName($data->getStudentUser());
                }
                if ($user->first()->hasDisplayName()) {
                        $name = $user->first()->getDisplayName()->first();
                } else {
                        $name = "";
                }

                if (file_exists($this->frontpage)) {
                        unlink($this->frontpage);
                }
                $this->frontpage = tempnam("/tmp", "openexam-result");
                $this->createFrontPage($name);

                $this->begin($this, $target);
        }

        //
        // Get result from database and send the output to the browser.
        //
        public function send($student)
        {
                $data = $this->manager->getStudentData($student);

                if (strstr($this->format, "pdf")) {
                        header("Content-Type: application/pdf");
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", sprintf("%s-%s.pdf", $data->getStudentUser(), $data->getStudentCode())));
                } elseif (strstr($this->format, "ps")) {
                        header("Content-Type: application/postscript");
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", sprintf("%s-%s.ps", $data->getStudentUser(), $data->getStudentCode())));
                } elseif (strstr($this->format, "html")) {
                        header("Content-Type: text/html");
                        header(sprintf("Content-Disposition: attachment; filename=\"%s\"", sprintf("%s-%s.html", $data->getStudentUser(), $data->getStudentCode())));
                }

                $this->student = $student;
                $this->generate();
        }

        //
        // Get result from the database and save it to output file.
        //
        public function save($student, $file)
        {
                $this->student = $student;
                $this->generate($file);
        }

        //
        // Save all student results for this examination. Note that
        // the destination must be a writable directory.
        //
        public function saveAll($destdir)
        {
                $students = $this->board->getStudents();
                foreach ($students as $student) {
                        if (strstr($this->format, "pdf")) {
                                $file = sprintf("%s/%s-%s.pdf", $destdir, $student->getStudentUser(), $student->getStudentCode());
                        } elseif (strstr($this->format, "ps")) {
                                $file = sprintf("%s/%s-%s.ps", $destdir, $student->getStudentUser(), $student->getStudentCode());
                        } elseif (strstr($this->format, "html")) {
                                $file = sprintf("%s/%s-%s.html", $destdir, $student->getStudentUser(), $student->getStudentCode());
                        } else {
                                throw new Exception(sprintf("Unknown format '%s'", $this->format));
                        }

                        $this->student = $student->getStudentID();
                        $this->generate($file);
                }
        }

        //
        // Send all results as a zip-file.
        //
        public function sendAll()
        {
                $dirname = tempnam("/tmp", "openexam-result");
                $zipname = tempnam("/tmp", "openexam-result");

                if (!$dirname || !$zipname) {
                        throw new Exception("Failed create temporary file.");
                }

                //
                // For naming the result zip-file. Note that we use RFC 2822 format of date:
                //
                $data = $this->manager->getData();
                $name = sprintf("%s - %s", $data->getExamName(), date('r', strtotime($data->getExamStartTime())));

                $zip = new ZipArchive();
                if (!$zip->open($zipname, ZIPARCHIVE::OVERWRITE)) {
                        throw new Exception("Failed create zip-archive.");
                }
                unlink($dirname);
                if (!mkdir($dirname)) {
                        throw new Exception("Failed create temporary directory.");
                }

                $this->saveAll($dirname);
                if (!($handle = opendir($dirname))) {
                        throw new Exception("Failed open zip entry directory.");
                }

                //
                // Must add all files to zip-file and close the zip before unlinking the
                // temporary files added to zip-file, otherwise we get an empty archive.
                //
                while (($file = readdir($handle)) !== false) {
                        if ($file != "." && $file != "..") {
                                $path = sprintf("%s/%s", $dirname, $file);
                                $zip->addFile($path, $file);
                        }
                }
                $zip->close();
                rewinddir($handle);
                while (($file = readdir($handle)) !== false) {
                        if ($file != "." && $file != "..") {
                                $path = sprintf("%s/%s", $dirname, $file);
                                unlink($path);
                        }
                }
                closedir($handle);

                header("Content-Type: application/zip");
                header(sprintf("Content-Disposition: inline;filename=\"%s.zip\"", $name));
                header("Cache-Control: private");
                header("Pragma-directive: no-cache");
                header("Cache-directive: no-cache");
                header("Pragma: public");
                header("Expires: 0");
                readfile($zipname);

                if (file_exists($zipname)) {
                        unlink($zipname);
                }
                if (file_exists($dirname)) {
                        rmdir($dirname);
                }
        }

}

?>
