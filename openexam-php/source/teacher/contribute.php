<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   source/teacher/contribute.php
// Author: Anders Lövgren
// Date:   2010-04-29
// 
// This page is used by teacher for contributing questions for an exam.
// 

// 
// Force logon for unauthenticated users:
// 
$GLOBALS['logon'] = true;

// 
// System check:
// 
if(!file_exists("../../conf/database.conf")) {
    header("location: setup.php?reason=database");
}
if(!file_exists("../../conf/config.inc")) {
    header("location: setup.php?reason=config");
}

// 
// Include external libraries:
// 
include "MDB2.php";
include "CAS.php";

// 
// Locale and internationalization support:
// 
include "include/locale.inc";

// 
// Include configuration:
// 
include "conf/config.inc";
include "conf/database.conf";

// 
// Include logon and user interface support:
// 
include "include/cas.inc";
include "include/ui.inc";
include "include/error.inc";
include "include/html.inc";

// 
// Include database support:
// 
include "include/database.inc";
include "include/ldap.inc";

// 
// Business logic:
// 
include "include/exam.inc";
include "include/teacher.inc";
include "include/teacher/manager.inc";
include "include/teacher/contribute.inc";
include "include/teacher/testcase.inc";

// 
// The contribute page:
// 
class ContributePage extends TeacherPage
{
    private $params = array( "exam"     => "/^\d+$/",
			     "action"   => "/^(add|edit|test|delete|remove|restore)$/",
			     "question" => "/^(\d+|all|active|removed)$/",
			     "comment"  => "/.*/",
			     "mode"     => "/^(save)$/",
			     "score"    => "/^\d+(\.\d)*$/",
			     "name"     => "/^.*$/", 
			     "quest"    => "/.*/", 
			     "type"     => "/^(freetext|single|multiple)$/",
			     "user"     => "/^[0-9a-zA-Z]{1,10}$/", 
			     "status"   => "/^(active|removed)$/",
			     "video"    => "/^(.*:\/\/.*|)$/", 
			     "audio"    => "/^(.*:\/\/.*|)$/",
			     "image"    => "/^(.*:\/\/.*|)$/" );
    
    public function __construct()
    {
	parent::__construct(_("Contribute Page"), $this->params);
    }

    // 
    // The main entry point. This is where all processing begins.
    // 
    public function printBody()
    {
        //
	// Authorization first:
	//
	if(isset($_REQUEST['exam'])) {
	    self::checkAccess();
	}
	
	//
	// Bussiness logic:
	//
	if(isset($_REQUEST['exam'])) {
	    if(isset($_REQUEST['action'])) {
		if($_REQUEST['action'] == "add") {
		    if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
			self::assert(array('score', 'name', 'quest', 'type', 'user'));
			self::saveAddQuestion();
		    } else {
			self::formAddQuestion();
		    }
		} elseif($_REQUEST['action'] == "edit") {
		    if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
			self::assert(array('score', 'name', 'quest', 'type', 'question', 'user'));
			self::saveEditQuestion();
		    } else {
			self::assert('question');
			self::formEditQuestion();
		    }
		} elseif($_REQUEST['action'] == "delete") {
		    self::assert('question');
		    if($_REQUEST['question'] == "all") {
			self::saveDeleteQuestions();
		    } else {
			self::saveDeleteQuestion();
		    }
		} elseif($_REQUEST['action'] == "remove") {
		    if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "save") {
			self::assert(array('question', 'comment'));
			self::saveRemoveQuestion();
		    } else {
			self::assert('question');
			self::formRemoveQuestion();
		    }
		} elseif($_REQUEST['action'] == "restore") {
		    self::assert('question');
		    self::saveRestoreQuestion();
		} elseif($_REQUEST['action'] == "test") {
		    self::checkAccess("test");
		    self::testExam();
		}
	    } else {
		if(isset($_REQUEST['question'])) {
		    self::showQuestions();
		} else {
		    self::showQuestions();
		}
	    }
	} else {
	    self::showAvailableExams();
	}
    }

    // 
    // Verify that the caller has been granted the required role on this exam.
    // 
    private function checkAccess($reason = null)
    {
	if(!isset($reason)) {
	    if(!$this->manager->isContributor(phpCAS::getUser())) {
		ErrorPage::show(_("Access denied!"),
				sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), "contributor"));
		exit(1);
	    }
	} elseif($reason == "test") {
	    if(!$this->manager->isTestCaseAllowed(phpCAS::getUser())) {
		ErrorPage::show(_("Access denied!"),
				_("Only the creator of the examination is allowed to run test case on it. The script processing has halted."));
		exit(1);
	    }
	}
    }
    
    // 
    // Delete all questions.
    // 
    private function saveDeleteQuestions()
    {
	$contrib = new Contribute($this->param->exam);
	$contrib->deleteQuestions();

	header(sprintf("location: contribute.php?exam=%d", $this->param->exam));
    }

    // 
    // Delete this question.
    // 
    private function saveDeleteQuestion()
    {
	$contrib = new Contribute($this->param->exam);
	$contrib->deleteQuestion($this->param->question);
	
	header(sprintf("location: contribute.php?exam=%d", $this->param->exam));
    }
    
    // 
    // Save answers posted by form.
    // 
    private function saveAddQuestion()
    {
	$video = isset($this->param->video) ? $this->param->video : "";
	$audio = isset($this->param->audio) ? $this->param->audio : "";
	$image = isset($this->param->image) ? $this->param->image : "";

	$contrib = new Contribute($this->param->exam);
	$contrib->addQuestion($this->param->exam, 
			      $this->param->score, 
			      utf8_encode($this->param->name), 
			      utf8_encode($this->param->quest), 
			      $this->param->type, 
			      $this->param->user, 
			      $video, 
			      $audio, 
			      $image);
	
	header(sprintf("location: contribute.php?exam=%d", $this->param->exam));
    }

    // 
    // Save answers posted by form.
    // 
    private function saveEditQuestion()
    {
	$video = isset($this->param->video) ? $this->param->video : "";
	$audio = isset($this->param->audio) ? $this->param->audio : "";
	$image = isset($this->param->image) ? $this->param->image : "";
	
	$contrib = new Contribute($this->param->exam);
	$contrib->editQuestion($this->param->question, 
			       $this->param->exam, 
			       $this->param->score, 
			       utf8_encode($this->param->name), 
			       utf8_encode($this->param->quest), 
			       $this->param->type, 
			       $this->param->user, 
			       $video, 
			       $audio, 
			       $image);

	header(sprintf("location: contribute.php?exam=%d", $this->param->exam));
    }
    
    // 
    // Marks a question as removed.
    // 
    private function saveRemoveQuestion()
    {
	$contrib = new Contribute($this->param->exam);
	$contrib->removeQuestion($this->param->question, utf8_encode($this->param->comment));

	header(sprintf("location: manager.php?exam=%d&action=show", $this->param->exam));
    }

    // 
    // Marks a question as restored.
    // 
    private function saveRestoreQuestion()
    {
	$contrib = new Contribute($this->param->exam);
	$contrib->restoreQuestion($this->param->question, $this->param->comment);

	header(sprintf("location: manager.php?exam=%d&action=show", $this->param->exam));
    }

    // 
    // Helper function for adding a new or editing an existing question.
    // 
    private function formPostQuestion(&$data, $action, &$exam, &$info)
    {
	$options = array( "freetext" => _("Freeform text question"),
			  "single"   => _("Single choice question"),
			  "multiple" => _("Multiple choice question")
			  );

	printf("<script type=\"text/javascript\">\n");
	printf("function clearform(form) {\n");
	printf("  form.name.value = \"\";  form.quest.value = \"\";\n");
	printf("  form.score.value = \"\"; form.video.value = \"\";\n");
	printf("  form.audio.value = \"\"; form.image.value = \"\";\n");
	printf("}\n");
	printf("</script>\n");

	$form = new Form("contribute.php", "POST");
	$form->setName("question");
	$form->addHidden("exam", $data->getExamID());
	$form->addHidden("mode", "save");
	$form->addHidden("action", $action);
	if($action == "edit") {
	    $form->addHidden("question", $data->getQuestionID());
	}
	if(!$info->isEditable()) {
	    $form->addHidden("name", utf8_decode($data->getQuestionName()));
	    $form->addHidden("quest", utf8_decode($data->getQuestionText()));
	    $form->addHidden("type", $data->getQuestionType());
	}
	
	$sect = $form->addSectionHeader(_("Required fields"));
	$sect->setClass("secthead");
	
	if($info->isEditable()) {	    
	    $input = $form->addTextBox("name", $data->hasQuestionName() ? utf8_decode($data->getQuestionName()) : "");
	    $input->setTitle(_("A short question name or simply a number"));
	    $input->setLabel(_("Name"));
	    $input->setSize(60);
	    
	    $input = $form->addTextArea("quest", 
					$data->hasQuestionText() || $action == "edit" ? 
					utf8_decode($data->getQuestionText()) : 
					_("Single or multi choice questions is defined by question text and an JSON encoded string of options, where the correct answers are marked as true (see example below). Single choice questions differs from multi choice question in that only one of the options is tagged as true. Freetext questions is simply defined as some text.\n\nAn example of a multiple choice question:\n-------------------------\nWhich one of these where part of Thin Lizzy during the classical year 1976?\n\n{\"Brian Robertsson\":true,\"Lars Adaktusson\":false,\"Scott Gorham\":true}\n-------------------------\n"));
	    $input->setTitle(_("The actual question is defined here."));
	    $input->setLabel(_("Question"));
	    $input->setClass("question");
	    
	    $combo = $form->addComboBox("type");
	    $combo->setLabel(_("Type"));
	    foreach($options as $value => $text) {
		$option = $combo->addOption($value, $text);
		if($data->getQuestionType() == $value) {
		    $option->setSelected();
		}
	    }
	}
	
	$input = $form->addTextBox("score", sprintf("%.01f", $data->hasQuestionScore() ? $data->getQuestionScore() : 0.0));
	$input->setLabel(_("Score"));
	
	if($info->isEditable()) {
	    $sect = $form->addSectionHeader(_("Optional fields"));
	    $sect->setClass("secthead");
	    
	    $input = $form->addTextBox("video", $data->hasQuestionVideo() ? $data->getQuestionVideo() : "");
	    $input->setLabel(_("Video URL"));
	    $input->setTitle(_("An URL address (like http://www.example.com/xxx) linking to an web resource related to this question. The resource will be embedded on the question page in the right hand sidebar."));
	    $input->setSize(70);

	    $input = $form->addTextBox("audio", $data->hasQuestionAudio() ? $data->getQuestionAudio() : "");
	    $input->setLabel(_("Audio URL"));
	    $input->setTitle(_("An URL address (like http://www.example.com/xxx) linking to an web resource related to this question. The resource will be embedded on the question page in the right hand sidebar."));
	    $input->setSize(70);

	    $input = $form->addTextBox("image", $data->hasQuestionImage() ? $data->getQuestionImage() : "");
	    $input->setLabel(_("Image URL"));
	    $input->setTitle(_("An URL address (like http://www.example.com/xxx) linking to an web resource related to this question. The resource will be embedded on the question page in the right hand sidebar."));
	    $input->setSize(70);
	} else {
	    $input = $form->addHidden("video", $data->hasQuestionVideo() ? $data->getQuestionVideo() : "");
	    $input = $form->addHidden("audio", $data->hasQuestionAudio() ? $data->getQuestionAudio() : "");
	    $input = $form->addHidden("image", $data->hasQuestionImage() ? $data->getQuestionImage() : "");
	}

	// 
	// Only allow the creator of the exam to change the publisher of an question. 
	// This is because the exam creator is the only person who can grant the required
	// contribute role to the target user.
	// 
	if($exam->getExamCreator() == phpCAS::getUser()) {
	    $sect = $form->addSectionHeader(_("Accounting"));
	    $sect->setClass("secthead");
	    
	    $input = $form->addTextBox("user", $data->hasQuestionPublisher() ? utf8_decode($data->getQuestionPublisher()) : phpCAS::getUser());
	    $input->setLabel(_("Corrector"));
	    $input->setTitle(_("This field sets the UU-ID (CAS-ID) of the person who's responsible for correcting the answers to this question.\n\nBy default, the same person that publish a question is also assigned as its corrector."));
	    $input->setSize(60);
	} else {
	    $form->addHidden("user", phpCAS::getUser());
	}
	
	$form->addSpace();
	$button = $form->addButton(BUTTON_SUBMIT, _("Submit"));
	$button->setLabel();
	$button = $form->addButton(BUTTON_RESET, _("Reset"));
	$button = $form->addButton(BUTTON_STANDARD, _("Clear"));
	$button->setEvent(EVENT_ON_CLICK, "clearform(document.question);return false;");
	
	$form->output();
    }
    
    // 
    // Show the form for adding a new question.
    // 
    private function formAddQuestion()
    {
	$data = $this->manager->getData();
	$info = $this->manager->getInfo();
	$qrec = new DataRecord(array("examid" => $this->param->exam, 
				     "questiontype" => "freetext" ));
	
	printf("<h3>" . _("Add Question") . "</h3>\n");
	printf("<p>" . _("This page let you add a new question in the examination '%s'") . "</p>\n", 
	       utf8_decode($data->getExamName()));
	
	self::formPostQuestion($qrec, "add", $data, $info);
    }
    
    // 
    // Show the form for editing an existing question.
    // 
    private function formEditQuestion()
    {
	$data = $this->manager->getData();
	$info = $this->manager->getInfo();
	$qrec = Exam::getQuestionData($this->param->question);

	printf("<h3>" . _("Edit Question") . "</h3>\n");
	printf("<p>" . _("This page let you edit the existing question in the examination '%s'") . "</p>\n", 
	       utf8_decode($data->getExamName()));
	
	self::formPostQuestion($qrec, "edit", $data, $info);
    }
    
    // 
    // Show form for marking a question as removed (not deleted).
    // 
    private function formRemoveQuestion()
    {
	$qrecord = Exam::getQuestionData($this->param->question);
	
	printf("<h3>" . _("Remove Question") . "</h3>\n");
	printf("<p>"  . 
	       _("This page let you mark the question '%s' as removed from this examination. ") . 
	       _("By marking this question as removed, any scores for answers will be ignored in the examination result. ") .
	       "</p>\n",
	       utf8_decode($qrecord->getQuestionName()));
	
	$form = new Form("contribute.php", "POST");
	$form->addHidden("exam", $this->param->exam);
	$form->addHidden("question", $this->param->question);
	$form->addHidden("action", "remove");
	$form->addHidden("mode", "save");
	$input = $form->addTextArea("comment");
	$input->setLabel(_("Comment"));
	$input->setTitle(_("The comment you add here will show up as the reason for question removal on the examination results."));
	$input->setClass("message");
	$form->addSpace();
	$input = $form->addButton(BUTTON_SUBMIT, _("Submit"));
	$input->setLabel();
	$input = $form->addButton(BUTTON_RESET, _("Reset"));
	$form->output();
    }

    // 
    // Create a test case of the exam and redirect user to it. This is also
    // known as dry-run, in that the original examination remains unmodified.
    // 
    private function testExam()
    {
	$test = new TestCase($this->param->exam);
	$test->create();
	header(sprintf("location: ../exam/index.php?exam=%d", $test->getExamID()));
    }
    
    // 
    // Show all questions for this exam.
    // 
    private function showQuestions()
    {
	$data = $this->manager->getData();
	$info = $this->manager->getInfo();
	$show = isset($this->param->question) ? $this->param->question : "active";

	$mode = array( "all" => _("All"), "active" => _("Active"), "removed" => _("Removed"));
	$disp = array();
	printf("<span class=\"links viewmode\">\n");
	foreach($mode as $name => $text) {
	    if($show != $name) {
		$disp[] = sprintf("<a href=\"?exam=%d&amp;question=%s\">%s</a>", 
				  $this->param->exam, $name, $text);
	    } else {
		$disp[] = $text;
	    }
	}
	printf("%s: %s\n", _("Show"), implode(", ", $disp));
	printf("</span>\n");
	
	printf("<h3>" . _("Manage Questions") . "</h3>\n");
	printf("<p>" . 
	       _("This page let you add, edit and remove questions in the examination '%s'. ") . 
	       _("You can only edit or remove a question if you are the publisher of the question or the creator of this examination.") . 
	       "</p>\n", 
	       utf8_decode($data->getExamName()));

	if(!$info->isContributable()) {
	    printf("<p>" . _("Notice: It's no longer possible to contribute or modify questions for this examination.") . "</p>\n");
	}
	
	$tree = new TreeBuilder(_("Questions"));
	$root = $tree->getRoot();
	if($info->isContributable()) {
	    $root->addLink(_("Add"), 
			   sprintf("?exam=%d&amp;action=add", $data->getExamID()), 
			   _("Click to add a question to this examination."));
	}
	
	$status = $show != "all" ? $show : null;
	$questions = $this->manager->getQuestions($status);
	
	foreach($questions as $question) {
	    $child = $root->addChild(sprintf("%s %s", _("Question"), utf8_decode($question->getQuestionName())));
	    if($question->getQuestionPublisher() == phpCAS::getUser() || $data->getExamCreator() == phpCAS::getUser()) {
		if(!$info->isDecoded()) {
		    $child->addLink(_("Edit"), sprintf("?exam=%d&amp;action=edit&amp;question=%d", 
						   $question->getExamID(),
						   $question->getQuestionID()));
		}
		if($info->isContributable()) {
		    $child->addLink(_("View"), sprintf("../exam/index.php?exam=%d&amp;question=%d",
						       $question->getExamID(),
						       $question->getQuestionID()),
				    _("Preview this question"), array("target" => "_blank"));
		    $child->addLink(_("Delete"), sprintf("?exam=%d&amp;action=delete&amp;question=%d", 
							 $question->getExamID(),
							 $question->getQuestionID()));
		}
	    }
	    $child->addChild(sprintf("%s: %.01f", _("Score"), $question->getQuestionScore()));
	    $child->addChild(sprintf("%s: %s", _("Publisher"), $this->getFormatName($question->getQuestionPublisher())));
	    $child->addChild(sprintf("%s: %s", _("Video"), $question->hasQuestionVideo() ? $question->getQuestionVideo() : _("No")));
	    $child->addChild(sprintf("%s: %s", _("Audio"), $question->hasQuestionAudio() ? $question->getQuestionAudio() : _("No")));
	    $child->addChild(sprintf("%s: %s", _("Image"), $question->hasQuestionImage() ? $question->getQuestionImage() : _("No")));
	    $child->addChild(sprintf("%s: %s", _("Type"), $question->getQuestionType()));
	    if($question->getQuestionStatus() == "removed") {
		$child->addChild(sprintf("%s: %s", _("Status"), $question->getQuestionStatus()));
		$child->addChild(sprintf("%s: %s", _("Comment"), utf8_decode($question->getQuestionComment())));
	    }
	    $subobj = $child->addChild(sprintf("%s:", _("Question Text")));
	    $subobj->addText(sprintf("<div class=\"examquest\">%s</div>", 
				     utf8_decode(str_replace("\n", "<br>", $question->getQuestionText()))));
	}
	$tree->output();
    }
        
    // 
    // Show all exams where caller has been granted the contribute role.
    // 
    private function showAvailableExams()
    {
	printf("<h3>" . _("Contribute Questions") . "</h3>\n");
	printf("<p>"  . _("Select the examination you wish to contribute questions for (applies only to contributable examinations).") . "</p>\n");

	$tree = new TreeBuilder(_("Examinations"));
	$root = $tree->getRoot();

	// 
	// Group the examinations by their state:
	// 
	$exams = Contribute::getExams(phpCAS::getUser());
	$nodes = array( 
			'c' => array( 'name' => _("Contributable"),
				      'data' => array() ),
			'a' => array( 'name' => _("Active"),
				      'data' => array() ),
			'f' => array( 'name' => _("Finished"),
				      'data' => array() )
			);
	
	foreach($exams as $exam) {
	    $manager = new Manager($exam->getExamID());
	    $state = $manager->getInfo();
	    if($state->isContributable()) {
		$nodes['c']['data'][] = array($exam->getExamName(), $state);
	    } elseif($state->isRunning()) {
		$nodes['a']['data'][] = array($exam->getExamName(), $state);
	    } elseif($state->isFinished()) {
		$nodes['f']['data'][] = array($exam->getExamName(), $state);
	    }
	}

	foreach($nodes as $type => $group) {
	    if(count($group['data']) > 0) {
		$node = $root->addChild($group['name']);
		foreach($group['data'] as $data) {
		    $name  = $data[0];
		    $state = $data[1];
		    $child = $node->addChild(utf8_decode($name));
		    if($state->isContributable()) {
			$child->setLink(sprintf("?exam=%d", $state->getInfo()->getExamID()),
					_("Click on this link to see all questions in this examination."));
			$child->addLink(_("Add"), 
					sprintf("?exam=%d&amp;action=add", $state->getInfo()->getExamID()),
					_("Click to add a question to this examination."));
		    }
		    $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamStartTime()))));
		    $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamEndTime()))));
		}
	    }
	}
	
	$tree->output();
    }
    
}

$page = new ContributePage();
$page->render();

?>
