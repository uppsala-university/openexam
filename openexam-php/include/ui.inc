<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/ui.inc
// Author: Anders Lövgren
// Date:   2008-06-27
// 
// This file defines classes for rendering the pages using the user 
// interface (UI) template system. An minimalistic example showing 
// it's usage:
// 
//   class MyPage extends BasePage
//   {
//       public function __contruct()
//       {
//           parent::__construct("Title");
//       }
//   
//       public function printBody()
//       {
//           // Business logic goes here...
//       }
//   }
//
//   $page = new MyPage();
//   $page->render();        // Output HTML
// 
// At least the printBody() member function should be overloaded in the
// derived class. One thing to note is that all callback member function
// gets called using the instance of a class derived from this class.
//
// 
// Common interface for the base (inherited in pages) and the render 
// class (used from template pages, see template/ui/standard.ui).
// 
interface TemplatePage
{

        public function printHeader();      // Extra headers

        public function printTitle();       // Page title

        public function printBody();        // Page body

        public function showSidebar();      // Show sidebar or not?

        public function printSidebar();     // Optional sidebar menu

        public function printTopMenu();

        public function printMenu();        // Left side menu

        public function getPublishInfo();   // Publish info
}

// 
// This is the base class for all PHP pages. All script that output HTML 
// should extend this base class and override the relevant functions.
// 
// By default output buffring is on so that we can handle redirect from 
// within child classes, call ob_end_flush() to disable output buffering.
// 
class BasePage implements TemplatePage
{

        const pattern_float = "/^\d+([,.]\d+)*$/";      // match float point
        const pattern_index = "/^\d+$/";                // match database index
        const pattern_text = "/^.*/";                   // generic text or any
        const pattern_user = "/^[0-9a-zA-Z]{1,10}$/";   // username
        const pattern_url = "/^(.*:\/\/.*|)$/";         // i.e. http://server/file
        const pattern_course = "/^[0-9a-zA-Z]{1,10}$/"; // course code
        const pattern_code = "/^([0-9a-fA-F]{1,15}|)$/"; // registration code
        const pattern_year = "/^[0-9]{4}$/";            // YYYY
        const pattern_termin = "/^[1-2]$/";             // semester
        const pattern_name = "/^(\p{L}|\p{N}|\p{Z}|\p{P})+$/u"; // person

        private $title;
        protected $param;     // Washed request parameters
        private $params;
        private $template;

        protected function __construct($title, &$params = array(), $template = "standard")
        {
                $this->title = $title;
                $this->params = $params;
                $this->template = $template;

                // 
                // We must use self::validate() to ensure that we are calling
                // validate() in this class and *not* an overridden validate()
                // in derived class. This truely shows that PHP's is not a true 
                // OOP language :-(
                // 
                self::validate();
                ob_start();
        }

        //
        // This function should be called in the PHP script to render the page
        // using the selected UI template.
        //
        final public function render()
        {
                $page = new PageRender($this);
                include(sprintf("template/ui/%s.ui", $this->template));
        }

        //
        // Prints a successful message.
        //
        final public function success($message)
        {
                MessageBox::show(MessageBox::success, $message);
        }

        //
        // Prints an error message.
        //
        final public function error($message)
        {
                MessageBox::show(MessageBox::error, $message);
        }

        //
        // Validate access and request parameters. All validated request parameters
        // are automatic injected into $this->param, but only if they origins from
        // a GET or POST request.
        //
        public function validate()
        {
                foreach ($this->params as $param => $pattern) {
                        if (isset($_REQUEST[$param])) {
                                if (is_array($_REQUEST[$param])) {
                                        foreach ($_REQUEST[$param] as $value) {
                                                self::check($param, $pattern, $value);
                                        }
                                        self::inject($param, $pattern);
                                } else {
                                        self::check($param, $pattern, $_REQUEST[$param]);
                                        self::inject($param, $pattern);
                                }
                        }
                }
        }

        // 
        // Helper function for validate().
        // 
        final private function check($param, $pattern, $value)
        {
                if (!preg_match($pattern, $value)) {
                        $this->fatal(_("Request parameter error!"), sprintf(_("Invalid value for request parameter '%s' (expected a value matching pattern '%s')."), $param, $pattern));
                }
        }

        // 
        // Inject washed parameter.
        // 
        final private function inject($param, $pattern)
        {
                if (isset($_GET[$param])) {
                        $this->param->$param = $_GET[$param];
                } elseif (isset($_POST[$param])) {
                        $this->param->$param = $_POST[$param];
                }

                if ($pattern == self::pattern_float) {
                        self::convert($this->param->$param);
                }
        }

        // 
        // Float point number convertor.
        // 
        final private function convert(&$data)
        {
                if (is_array($data)) {
                        foreach ($data as $key => $val) {
                                $data[$key] = DataRecord::str2num($val);
                        }
                } else {
                        $data = DataRecord::str2num($data);
                }
                print_r($data);
        }

        //
        // Check that the array of required parameters is set.
        //
        final public function assert($names)
        {
                if (!is_array($names)) {
                        $names = array($names);
                }
                foreach ($names as $name) {
                        if (!isset($_REQUEST[$name])) {
                                $this->fatal(_("Missing required parameter!"), sprintf(_("The required request parameter '%s' is missing"), $name));
                        }
                }
        }

        // 
        // Display error page and terminate script with the given error code.
        // 
        final public function fatal($title, $message, $exitcode = 1)
        {
                ErrorPage::show($title, $message);
                exit($exitcode);
        }

        final public function printLogonStatus()
        {
                if (phpCAS::isAuthenticated()) {
                        printf(_("Logged on as") . " <b>%s</b> | <a href=\"?logout\">" . _("Logout") . "</a>\n", phpCAS::getUser());
                } else {
                        printf(_("Not logged on") . " | <a href=\"?logon\">" . _("Logon") . "</a>\n");
                }
        }

        //
        // Print current selected language. Also output an "on-hover" list (with id
        // lang and initial hidden) from which the user can choose another language.
        //
        final public function printLanguageSelect()
        {
                global $locale;

                //
                // Create target URI stripped from previous 'lang=xx':
                //
                $request = str_replace("&", "&amp;", $_SERVER['REQUEST_URI']);
                $pattern = "/([&?])lang=\w{1,2}/";
                if (preg_match($pattern, $request)) {
                        $request = preg_replace($pattern, "$1", $request);
                } elseif (!strstr($request, "?")) {
                        $request .= "?";
                } else {
                        $request .= "&amp;";
                }

                //
                // The language selection list:
                //
                printf("<div class=\"lang-select\" id=\"lang\">\n");
                printf(_("Select language to use:") . "<br />\n");
                foreach ($locale->getLanguageList() as $lang) {
                        if (!$locale->isAlias($lang)) {
                                $data = $locale->getLanguageData($lang);
                                printf("<a href=\"%slang=%s\"><img src=\"/openexam/images/flag/%s.png\" alt=\"Flag\" />&nbsp;%s</a><br />\n", $request, $lang, $lang, _($data["lang"]));
                        }
                }
                printf("</div>\n");

                //
                // This javascript code makes the list visible:
                //
                printf("<script type=\"text/javascript\" language=\"javascript\">\n");
                printf("function showLanguageSelect() {\n");
                printf("    var obj = document.getElementById('lang');\n");
                printf("    obj.style.visibility = 'visible';\n");
                printf("}\n");
                printf("</script>\n");

                //
                // Print current language in use:
                //
                printf("<div class=\"lang-show\">\n");
                printf(_("Language:") . " <a href=\"#\" onmousedown=\"showLanguageSelect(); return false;\" title=\"%s\"><img src=\"/openexam/images/flag/%s.png\" alt=\"Flag\" /></a>\n", _("Click on the flag to change language."), $locale->getLanguage());
                printf("</div>\n");
        }

        //
        // Should be overloaded (required).
        //
        public function printBody()
        {
                //{tr}: translator: the argument is the method name to redefine.
                printf("<h1>" . ("Missing page body (please redefine %s)") . "</h1>\n", __METHOD__);
        }

        //
        // Should be overloaded (recommended).
        //
        public function printTitle()
        {
                printf("%s - %s", PAGE_TITLE, $this->title);
        }

        //
        // Could be overloaded (optional).
        //
        public function printHeader()
        {
                global $locale;
                $data = $locale->getLanguageData($locale->getLanguage());

                printf("<meta http-equiv=\"Content-Type\" content=\"text/html;charset=%s\" />\n", $data['charset']);
                printf("<meta name=\"publisher\" content=\"BMC Administration - Uppsala Biomedical Centre - Uppsala Univertitet\" />\n");
                printf("<meta name=\"description\" content=\"Locker room booking system\" />\n");
                printf("<meta name=\"author\" content=\"http://it.bmc.uu.se/andlov/proj/\" />\n");
                printf("<link rel=\"stylesheet\" type=\"text/css\" href=\"%s/css/openexam.css\" />\n", BASE_URL);
                printf("<script type=\"text/javascript\" language=\"javascript\" src=\"%s/js/openexam.js\"></script>\n", BASE_URL);
                printf("<script type=\"text/javascript\" language=\"javascript\" src=\"%s/js/datetimepicker_css.js\"></script>\n", BASE_URL);
        }

        //
        // Could be overloaded (optional).
        //
        public function printMenu()
        {
                if (file_exists("standard.menu")) {
                        include("standard.menu");
                }
                if (file_exists("../standard.menu")) {
                        include("../standard.menu");
                }
                if (file_exists("../../standard.menu")) {
                        include("../../standard.menu");
                }
                if (defined("PAGE_VALIDATOR") && PAGE_VALIDATOR) {
                        echo "<span id=\"menuhead\">" . _("Validator:") . "</span>\n";
                        echo "<ul>\n";
                        if (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], "on") == 0) {
                                //
                                // The validator requires an explicit URL when using HTTPS. We
                                // also need to serve a cached image as W3C don't support HTTPS.
                                //
                                printf("<li><div class=\"validator\"><a href=\"http://validator.w3.org/check?uri=%s\"><img src=\"%s/images/w3c/valid-xhtml10\" alt=\"Valid XHTML 1.0!\" height=\"31\" width=\"88\" /></a></div></li>\n", urlencode(sprintf("https://%s%s", $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'])), BASE_URL);
                                printf("<li><div class=\"validator\"><a href=\"http://jigsaw.w3.org/css-validator/validator?uri=%s\"><img src=\"%s/images/w3c/vcss-blue\" alt=\"Valid CSS!\" height=\"31\" width=\"88\" /></a></div></li>\n", urlencode(sprintf("https://%s%s", $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'])), BASE_URL);
                        } else {
                                printf("<li><div class=\"validator\"><a href=\"http://validator.w3.org/check?uri=referer\"><img src=\"http://www.w3.org/Icons/valid-xhtml10\" alt=\"Valid XHTML 1.0!\" height=\"31\" width=\"88\" /></a></div></li>\n");
                                printf("<li><div class=\"validator\"><a href=\"http://jigsaw.w3.org/css-validator/check/referer\"><img src=\"http://jigsaw.w3.org/css-validator/images/vcss-blue\" alt=\"Valid CSS!\" height=\"31\" width=\"88\" /></a></div></li>\n");
                        }
                        echo "</ul>\n";
                }
        }

        //
        // Output a top level menu. This is usually used to print the menu
        // at top of the page.
        //
        public function printTopMenu()
        {
                if (file_exists("topmenu.menu")) {
                        include("topmenu.menu");
                }
        }

        //
        // Show sidebar menu or not?
        //
        public function showSidebar()
        {
                return file_exists("sidebar.menu");
        }

        //
        // Could be overloaded (optional).
        //
        public function printSidebar()
        {
                if (file_exists("sidebar.menu")) {
                        include("sidebar.menu");
                }
        }

        //
        // Could be overloaded (optional).
        //
        public function getPublishInfo()
        {
                if (file_exists("publish.inc")) {
                        include("publish.inc");
                } else {
                        include("template/publish.inc");
                }
                return get_publish_info();
        }

}

// 
// This is the class that gets called from the user interface template
// page to output HTML.
// 
final class PageRender implements TemplatePage
{

        private $page;

        public function __construct($page)
        {
                $this->page = $page;
        }

        //
        // Trap all exceptions here so we can have a centralized error handling.
        //
        public function printBody()
        {
                try {
                        $this->page->printBody();
                } catch (Exception $exception) {
                        $message = sprintf(_("Please report this to %s."), CONTACT_STRING);
                        $this->page->error(sprintf("<p>%s</p><p>%s</p>", $exception, $message));
                        error_log(sprintf("%s: %s (in %s:%d)", ERROR_LOG_PREFIX, $exception->getMessage(), $exception->getFile(), $exception->getLine()));
                        if ($exception instanceof DatabaseException) {
                                error_log(sprintf("%s: %s", ERROR_LOG_PREFIX, $exception->getUserInfo()));
                        }
                        if (ERROR_LOG_TRACE) {
                                error_log(sprintf("%s: %s", ERROR_LOG_PREFIX, $exception->getTraceAsString()));
                        }
                }
        }

        public function printHeader()
        {
                $this->page->printHeader();
        }

        public function printTitle()
        {
                $this->page->printTitle();
        }

        public function showSidebar()
        {
                return $this->page->showSidebar();
        }

        public function printSidebar()
        {
                $this->page->printSidebar();
        }

        public function printTopMenu()
        {
                $this->page->printTopMenu();
        }

        public function printMenu()
        {
                $this->page->printMenu();
        }

        public function printLogonStatus()
        {
                $this->page->printLogonStatus();
        }

        public function printManagerStatus()
        {
                if (method_exists($this->page, 'printManagerStatus')) {
                        $this->page->printManagerStatus();     // defined for teacher pages.
                }
        }

        public function printLanguageSelect()
        {
                $this->page->printLanguageSelect();
        }

        public function getPublishInfo()
        {
                return $this->page->getPublishInfo();
        }

}

?>
