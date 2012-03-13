<?php

//
// Copyright (C) 2011 Computing Department BMC,
// Uppsala Biomedical Centre, Uppsala University.
//
// File:   source/media/index.php
// Author: Anders Lövgren
// Date:   2011-01-20
//
// This script handles media for one or more examinations. It requires logon
// and is not intended to be used as a public media library browser.
//
// It provides these operations:
//
//   1. Listing of already uploaded files.
//   2. Functions for add/delete of existing files.
//   3. Handle file uploads thru HTTP POST.
//
// The caller must have contribute permissions to submit new files. No track
// is kept on who has uploaded which file, we trust in users cooperation. All
// users with one or more roles on an examination can access this page in at
// least read-only mode.
//
//
// Force logon for unauthenticated users:
//
$GLOBALS['logon'] = true;

//
// System check:
//
if (!file_exists("../../conf/database.conf")) {
        header("location: ../admin/setup.php?reason=database");
}
if (!file_exists("../../conf/config.inc")) {
        header("location: ../admin/setup.php?reason=config");
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

//
// Include support files:
//
include "include/media.inc";
include "include/mplayer.inc";
include "include/teacher.inc";
include "include/teacher/manager.inc";
include "include/teacher/contribute.inc";

class MediaPage extends TeacherPage
{

        private $params = array(
                "exam"   => "/^\d+$/",
                "action" => "/^(add|delete)$/",
                "file"   => "/.*/",
                "type"   => "/^(audio|image|video|auto)$/",
                "show"   => "/^(tree|flat|album)$/"
        );

        public function __construct()
        {
                parent::__construct(_("Media"), $this->params);
        }

        public function printBody()
        {
                //
                // Authorization first:
                //
                if (isset($_REQUEST['exam'])) {
                        self::checkAccess();
                }

                //
                // Bussiness logic:
                //
                if (isset($_REQUEST['exam'])) {
                        if (isset($_REQUEST['action'])) {
                                if ($_REQUEST['action'] == "add") {
                                        $this->addMediaFile();
                                } else {
                                        $this->assert(array("type", "file"));
                                        $this->deleteMediaFile();
                                }
                        } else {
                                $this->showMediaFiles();
                        }
                } else {
                        $this->showAvailableExams();
                }
        }

        private function showSubmitForm()
        {
                printf("<h3>" . _("Media Library") . "</h3>\n");
                printf("<p>" . _("Browse your local harddisk for the media file to submit. Supported types of files are audio, image or video. Maximum size of uploaded file is %0.1f MB, uploading a larger file is silently discarded.") . "</p>\n", MEDIA_UPLOAD_MAXSIZE / (1024 * 1024));

                $form = new Form("index.php", "POST", "file");
                $form->setEncodingType("multipart/form-data");
                $form->addHidden("MAX_FILE_SIZE", MEDIA_UPLOAD_MAXSIZE);
                $form->addHidden("exam", $this->param->exam);
                $form->addHidden("action", "add");

                $input = $form->addFileInput("file");
                $input->setLabel(_("File"));
                $input->setClass("file");
                $form->addSubmitButton("file");

                $types = array(
                        _("Auto detect") => "auto",
                        _("Audio")       => MediaLibrary::audio,
                        _("Image")       => MediaLibrary::image,
                        _("Video")       => MediaLibrary::video
                );
                $combo = $form->addComboBox("type");
                $combo->setLabel(_("Type"));
                foreach ($types as $name => $type) {
                        $option = $combo->addOption($type, $name);
                        if (isset($this->param->type) && $type == $this->param->type) {
                                $option->setSelected();
                        }
                }

                $form->output();

                MessageBox::show(MessageBox::hint, _("If file type is set to auto, then server side will try to auto detected and categorize the uploaded file."));
        }

        //
        // Add an media file to this examination.
        //
        private function addMediaFile()
        {
                if (isset($this->param->file)) {
                        if ($this->param->type == "auto") {
                                $this->param->type = null;
                        }
                        $lib = new MediaLibrary($this->param->exam);
                        $lib->add("file", $this->param->type);
                        header(sprintf("Location: index.php?exam=%d", $this->param->exam));
                } else {
                        $this->showSubmitForm();
                }
        }

        //
        // Delete an media file from this examination.
        //
        private function deleteMediaFile()
        {
                $lib = new MediaLibrary($this->param->exam);
                $lib->delete($this->param->file, $this->param->type);
                header(sprintf("Location: index.php?exam=%d", $this->param->exam));
        }

        //
        // Show media files in tree structure.
        //
        private function showMediaTree(&$media)
        {
                $tree = new TreeBuilder(_("Files"));
                $root = $tree->getRoot();

                $root->addLink(_("Add"), sprintf("?exam=%d&amp;action=add", $this->param->exam), sprintf(_("Click to add an %s file to this examination."), _("media")));

                foreach ($media as $sect => $files) {
                        $child = $root->addChild($sect);
                        $child->addLink(_("Add"), sprintf("?exam=%d&amp;action=add&amp;type=%s", $this->param->exam, $sect), sprintf(_("Click to add an %s file to this examination."), $sect));
                        foreach ($files as $file) {
                                $file->title = sprintf("%s: %d bytes\n%s: %s", _("Size"), filesize($file->path), _("Modified"), strftime(DATETIME_FORMAT, filemtime($file->path)));

                                $node = $child->addChild($file->name);
                                $node->setLink($file->url, $file->title);
                                $node->addLink(_("Delete"), sprintf("?exam=%d&amp;action=delete&amp;type=%s&amp;file=%s&amp;show=tree", $this->param->exam, $file->sect, $file->name), sprintf(_("Click to delete the %s file %s from to this examination."), $file->sect, $file->name));
                        }
                }
                $tree->output();
        }

        //
        // Show media files in table view.
        //
        private function showMediaTable(&$media)
        {
                $table = new Table();
                $row = $table->addRow();
                $row->addHeader(_("Name"));
                $row->addHeader(_("Type"));
                $row->addHeader(_("Size"));
                $row->addHeader(_("Modified"));
                $row->addHeader(_("Action"));
                foreach ($media as $sect => $files) {
                        foreach ($files as $file) {
                                $row = $table->addRow();
                                $row->addData($file->name)->setLink($file->url);
                                $row->addData($file->sect);
                                $row->addData(filesize($file->path));
                                $row->addData(strftime(DATETIME_FORMAT, filemtime($file->path)));
                                $row->addData(
                                        _("Delete"))->setLink(
                                        sprintf("?exam=%d&amp;action=delete&amp;type=%s&amp;file=%s&amp;show=flat", $this->param->exam, $file->sect, $file->name));
                        }
                }
                $table->output();

                $links = array(
                        _("Audio") => MediaLibrary::audio,
                        _("Image") => MediaLibrary::image,
                        _("Video") => MediaLibrary::video
                );
                $disp = array();
                foreach ($links as $text => $name) {
                        $disp[] = sprintf("<a href=\"?exam=%d&amp;action=add&amp;type=%s\">%s</a>", $this->param->exam, $name, $text);
                }

                printf("<br><p>%s: %s</p>\n", _("Add"), implode(", ", $disp));
        }

        //
        // Show media files in album mode.
        //
        private function showMediaAlbum(&$media)
        {
                $table = new Table();
                $table->setClass("album");
                foreach ($media as $sect => $files) {
                        $index = 0;
                        $split = 3;
                        $count = count($files);
                        $row = $table->addRow();
                        $link = sprintf("%s/media/?exam=%d&amp;action=add&amp;type=%s", BASE_URL, $this->param->exam, $sect);
                        $title = sprintf(_("Click to add an %s file to this examination."), $sect);
                        $cell = $row->addData(sprintf("%s: %d %s <span class=\"album_add\"><a href=\"%s\" title=\"%s\">[+]</a></span>", $sect, $count, ngettext("File", "Files", $count), $link, $title));
                        $cell->setColspan($split);
                        $cell->setClass("album_head");

                        foreach ($files as $file) {
                                if ($index++ % $split == 0) {
                                        $row = $table->addRow();
                                }
                                $icon = sprintf("/openexam/icons/mime/%s.png", $sect);
                                $image = new Image($icon, $file->name);
                                $cell = $row->addData();
                                $cell->setClass("album_icon");
                                $link = new Link(LINK_TYPE_HREF, $file->url);
                                $link->addElement($image);
                                $cell->addElement($link);
                                $cell->addElement(new BR());
                                $cell->addElement(new Cdata($file->name));
                                $cell->addElement(new BR());
                                $time = sprintf("%s: %s", _("Modified"), strftime(DATETIME_FORMAT, filemtime($file->path)));
                                $cell->addElement(new Cdata($time));
                                $cell->addElement(new BR());
                                $size = sprintf("%s: %d bytes", _("Size"), filesize($file->path));
                                $cell->addElement(new Cdata($size));
                        }
                        while ($index++ % $split != 0) {
                                $cell = $row->addData();
                                $cell->setClass("album_icon");
                        }
                }
                $table->output();
        }

        //
        // Show all media files in this examination.
        //
        private function showMediaFiles()
        {
                printf("<h3>" . _("Media Library") . "</h3>\n");
                printf("<p>" . _("This page shows all files currently uploaded on the server for this examination. The media file URL's can be used when composing questions.") . "</p>\n");

                $mode = array(
                        "tree"  => _("Tree"),
                        "flat"  => _("Flat"),
                        "album" => _("Album")
                );
                $disp = array();
                $show = isset($this->param->show) ? $this->param->show : "tree";
                printf("<span class=\"links viewmode\">\n");
                foreach ($mode as $name => $text) {
                        if ($show != $name) {
                                $disp[] = sprintf("<a href=\"?exam=%d&amp;show=%s\">%s</a>", $this->param->exam, $name, $text);
                        } else {
                                $disp[] = $text;
                        }
                }
                printf("%s: %s\n", _("Show"), implode(", ", $disp));
                printf("</span>\n");

                $lib = new MediaLibrary($this->param->exam);
                $media = $lib->files;

                if ($show == "tree") {
                        $this->showMediaTree($media);
                } elseif ($show == "flat") {
                        $this->showMediaTable($media);
                } elseif ($show == "album") {
                        $this->showMediaAlbum($media);
                }
        }

        public function printMenu()
        {
                
        }

        //
        // Show all exams where caller has been granted the contribute role.
        //
        private function showAvailableExams()
        {
                printf("<h3>" . _("Media Library") . "</h3>\n");
                printf("<p>" . _("Select the examination you wish to handle media files in (applies only to contributable examinations).") . "</p>\n");

                $tree = new TreeBuilder(_("Examinations"));
                $root = $tree->getRoot();

                //
                // Group the examinations by their state:
                //
                $exams = Contribute::getExams(phpCAS::getUser());
                $nodes = array(
                        'c' => array(
                                'name' => _("Contributable"),
                                'data' => array()
                        ),
                        'a' => array(
                                'name' => _("Active"),
                                'data' => array()
                        ),
                        'f' => array(
                                'name' => _("Finished"),
                                'data' => array()
                        )
                );

                foreach ($exams as $exam) {
                        $manager = new Manager($exam->getExamID());
                        $state = $manager->getInfo();
                        if ($state->isContributable()) {
                                $nodes['c']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isRunning()) {
                                $nodes['a']['data'][] = array($exam->getExamName(), $state);
                        } elseif ($state->isFinished()) {
                                $nodes['f']['data'][] = array($exam->getExamName(), $state);
                        }
                }

                foreach ($nodes as $type => $group) {
                        if (count($group['data']) > 0) {
                                $node = $root->addChild($group['name']);
                                foreach ($group['data'] as $data) {
                                        $name = $data[0];
                                        $state = $data[1];
                                        $child = $node->addChild($name);
                                        $child->setLink(sprintf("?exam=%d", $state->getInfo()->getExamID()), _("Click on this link to browse all media files in this examination."));
                                        if ($state->isContributable()) {
                                                $child->addLink(_("Add"), sprintf("?exam=%d&amp;action=add", $state->getInfo()->getExamID()), _("Click to add a media file to this examination."));
                                        }
                                        $child->addChild(sprintf("%s: %s", _("Starts"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamStartTime()))));
                                        $child->addChild(sprintf("%s: %s", _("Ends"), strftime(DATETIME_FORMAT, strtotime($state->getInfo()->getExamEndTime()))));
                                }
                        }
                }

                $tree->output();
        }

        //
        // Verify that the caller has been granted the required role on this exam.
        //
        private function checkAccess()
        {
                if (!$this->manager->isContributor(phpCAS::getUser())) {
                        ErrorPage::show(_("Access denied!"), sprintf(_("Only users granted the %s role on this exam can access this page. The script processing has halted."), "decoder"));
                        exit(1);
                }
        }

}

$page = new MediaPage();
$page->render();
?>
