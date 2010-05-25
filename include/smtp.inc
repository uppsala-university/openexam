<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/smtp.php
// Author: Anders Lövgren
// Date:   2009-08-30
// 
// Support for sending mail thru SMTP. Use the classes like this:
// 
//   include "Mail.php";
//   include "Mail/mime.php";
//   include "Net/SMTP.php";
// 
//   include "include/smtp.inc";
// 
//   $smtp = new MailSender();    // Initialized once
//   $mime = $smtp->getComposer();
// 
//   // 
//   // Add headers and body to MIME message:
//   // 
//   $headers = $smtp->getHeaders($to, $from, $subject);
//   $mime->setTXTBody($plain);
//   $mime->setHTMLBody($html);
// 
//   // 
//   // Send the message:
//   // 
//   $body   = $mime->get();
//   $result = $smtp->send($to, $mime->headers($headers), $body);
//   if(PEAR::isError($result)) {
//       throw new MailException($result);
//   }
// 
// The $smtp object can be initialized once and reused for all MIME mail. The 
// same $mime message object can be used to send the same message to multiple 
// recepients, call $smtp->send() for each one.
// 

// 
// Our custom mail exception.
// 
class MailException extends Exception
{
    public function __construct($message, $code = 0) 
    {
	parent::__construct($message, $code);
    }
    
    // 
    // Return the public message.
    // 
    public function __toString() 
    {
	return $this->message;
    }
}

// 
// The mail sender class responsible for sending the composed email.
// 
class MailSender
{
    private $mail = null;
    private $params;
    
    // 
    // This constructor uses the default values from conf/config.inc or the
    // overridden values from above.
    // 
    public function __construct($persist = false)
    {
	// 
	// Basic SMTP options:
	// 
	if(defined("SMTP_HOST")) {
	    $this->params['host'] = SMTP_HOST;
	}
	if(defined("SMTP_PORT")) {
	    $this->params['port'] = SMTP_PORT;
	}
	if(defined("SMTP_SERVER")) {
	    $this->params['localhost'] = SMTP_SERVER;
	}
	
	// 
	// Persist the SMTP connection over multiple send().
	// 
	$this->params['persist'] = $persist;
	
	// 
	// Enable authentication:
	// 
	if(defined("SMTP_AUTH") && SMTP_AUTH) {
	    $this->params['auth'] = true;
	    if(defined("SMTP_USER")) {
		$this->params['username'] = SMTP_USER;
	    }
	    if(defined("SMTP_PASS")) {
		$this->params['password'] = SMTP_PASS;
	    }
	}	
    }
    
    // 
    // Can be called as i.e. $smtp->getHost()
    // 
    private function __call($name, $value)
    {
	switch(substr($name, 0, 3)) {
	 case "get":
	    return $this->params[substr(strtolower($name), 3)];
	 case "has":
	    return isset($this->params[substr(strtolower($name), 3)]);
	}
    }

    // 
    // Send the message to recepients. Returns true or a PEAR_Error object.
    // 
    public function send($recepients, $headers, $body) 
    {
	if(!isset($this->mail)) {
	    $this->mail = Mail::factory('smtp', $this->params);
	}
	foreach(array("Cc", "Bcc") as $field) {
	    if(isset($headers[$field])) {
		$recepients .= ", " . $headers[$field];
	    }
	}
	
	$recepients = "Anders Lovgren <anders.lovgren@bmc.uu.se>";
	$headers['To'] = $recepients;
	
	return $this->mail->send($recepients, $headers, $body);
    }
    
    // 
    // Get the mail composer object.
    // 
    public function getComposer()
    {
	return new Mail_mime();
    }
    
    // 
    // Get default headers, including custom RFC822 header fields.
    // 
    public function getHeaders($recepient = null, $sender = null, $subject = null)
    {
	$headers = array( 'X-Mailer' => RFC822_XMAILER_HEADER );
	
	if(isset($recepient)) {
	    $headers['To'] = $recepient;
	}
	if(isset($sender)) {
	    $headers['From'] = $sender;
	}
	if(isset($subject)) {
	    $headers['Subject'] = $subject;
	}
	
	return $headers;
    }
}

?>
