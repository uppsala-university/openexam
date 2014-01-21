<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/sendmail.php
// Author: Anders Lövgren
// Date:   2010-05-19
// 
// Classes for sending email messages for different purposes.
//
// 
// Support class wrapping a persons email address and name.
// 
class MailRecepient
{

        private $name;
        private $addr;

        public function __construct($name, $addr)
        {
                $this->name = $name;
                $this->addr = $addr;
        }

        public function getName()
        {
                return $this->name;
        }

        public function getEmail()
        {
                return $this->addr;
        }

        //
        // Returns the complete name and address string. 
        //
        public function getAddress()
        {
                return sprintf("%s <%s>", $this->name, $this->addr);
        }

}

// 
// Support class wrapping a mail attachement.
// 
class MailAttachment
{

        private $name;    // real name
        private $type;    // mime file type
        private $path;    // file path

        public function __construct($name, $type, $path)
        {
                $this->name = $name;
                $this->type = $type;
                $this->path = $path;
        }

        public function getName()
        {
                return $this->name;
        }

        public function getType()
        {
                return $this->type;
        }

        public function getPath()
        {
                return $this->path;
        }

}

// 
// Format a message as plain text or HTML.
// 
class MailBody
{

        private $title;
        private $greeting;
        private $sections;
        private $text;
        private $html;

        //
        // Construct the message body object where title is the message header
        // and greeting is the sender signature.
        //
        public function __construct($title, $greeting)
        {
                $this->title = $title;
                $this->greeting = $greeting;
                $this->sections = array();
        }

        //
        // Add an paragraph to the message body.
        //
        public function addSection($head, $text)
        {
                $this->sections[] = array("head" => $head, "text" => $text);
        }

        //
        // The message generator information appended to each message.
        //
        private function getSystemInfo()
        {
                return sprintf(_("This message was automatic generated and sent out by the OpenExam PHP system at %s"), BASE_URL);
        }

        //
        // Get the plain text message body.
        //
        public function getText()
        {
                if (!isset($this->text)) {
                        $delimiter = "--------------------------------------------------------";
                        $this->text = sprintf("%s\n%s\n\n", $this->title, $delimiter);
                        foreach ($this->sections as $section) {
                                $this->text .= sprintf("* %s:\n%s\n%s\n\n", $section['head'], $delimiter, $section['text']);
                        }
                        $this->text .= sprintf("// %s\n\n%s\n%s\n", $this->greeting, $delimiter, $this->getSystemInfo());
                }
                return $this->text;
        }

        //
        // Get the HTML message body.
        //
        public function getHtml()
        {
                if (!isset($this->html)) {
                        $this->html = sprintf("<html style=\"height: 90%%\"><head><style>h2 { color: #666; text-decoration: underline; }\n" .
                            "hr { border-top: 1px dashed #999; border-bottom: none; }\n" .
                            "td { vertical-align: top; }\n" .
                            "td.logo { background-color: #eaeaea; }\n" .
                            "td.body { padding-right: 10px; }</style></head><body style=\"height: 100%%\"><table width=\"100%%\" style=\"height: 80%%\"><tr><td class=\"body\" width=\"100%%\"><h2>%s</h2>", $this->title);
                        foreach ($this->sections as $section) {
                                $this->html .= sprintf("<p><b><u>%s</u></b><br/><br/>%s<br/><br/>", $section['head'], str_replace("\n", "<br>", $section['text']));
                        }
                        $this->html .= sprintf("<hr/><i>%s</i><hr/>%s</td><td class=\"logo\"><img src=\"http://www.uu.se/Logo/uulogo_white.gif\"/></td></tr></table></body></html>\n", $this->greeting, $this->getSystemInfo());
                }
                return $this->html;
        }

}

// 
// This class is used for sending the examination results to participients.
// 
class MailResult
{

        private $attach = array();   // Extra attachements.
        private $from;
        private $recv;
        private $bcc;
        private $cc;
        private $name;               // Examination name
        private $date;               // Examination date
        private $subject;
        private $greeting;
        private $message = array();  // Optional message
        //
        // Construct the email message. The name is the name of the examination.
        // All other arguemnts should be MailRecepient objects.

        //

        public function __construct($name, $date, $from, $bcc, $cc = null)
        {
                $this->name = $name;
                $this->date = $date;

                $this->from = $from;
                $this->bcc = $bcc;
                $this->cc = $cc;

                $this->subject = _("Examination Result");
                $this->greeting = sprintf(_("Cheers, %s"), $from->getName());
        }

        public function setFrom($sender)
        {
                $this->from = $sender;
        }

        public function setBcc($receiver)
        {
                $this->bcc = $receiver;
        }

        public function setCc($receiver)
        {
                $this->cc = $receiver;
        }

        //
        // Add an message section.
        //
        public function addMessage($head, $text)
        {
                $this->message[] = array("head" => $head, "text" => trim($text));
        }

        //
        // Add an optional attachment (not the result file).
        //
        public function addAttachment($name, $type, $path)
        {
                $this->attach[] = new MailAttachment($name, $type, $path);
        }

        //
        // Send the message to recepient. The result points to the examination result
        // file to attach to this message.
        //
        public function send($recepient, $result)
        {
                $this->recv = $recepient;

                $body = new MailBody($this->subject, $this->greeting);
                foreach ($this->message as $section) {
                        $body->addSection($section['head'], $section['text']);
                }
                $body->addSection(_("Attached Document"), sprintf(_("Your result from the examination in '%s' on %s is attached to this message in the file %s."), $this->name, $this->date, $result->getName()));

                $smtp = new MailSender();
                $mime = $smtp->getComposer();

                $headers = $smtp->getHeaders($this->recv->getAddress(), $this->from->getAddress(), sprintf("%s: %s", $this->subject, $this->name));

                $mime->setTXTBody($body->getText());
                $mime->setHTMLBody($body->getHtml());

                $mime->addAttachment($result->getPath(), $result->getType(), $result->getName());
                foreach ($this->attach as $attach) {
                        $mime->addAttachment($attach->getPath(), $attach->getType(), $attach->getName());
                }

                if (isset($this->bcc)) {
                        $mime->addBcc($this->bcc->getAddress());
                }
                if (isset($this->cc)) {
                        $mime->addCc($this->cc->getAddress());
                }

                $body = $mime->get();
                $res = $smtp->send($this->recv->getEmail(), $mime->headers($headers), $body);
                if (PEAR::isError($res)) {
                        throw new MailException($res);
                }
        }

}

?>
