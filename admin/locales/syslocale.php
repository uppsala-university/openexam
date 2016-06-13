<?php

// 
// List all system locales as PHP array.
// 

class ListLocales
{

        public $command = 'locale -a -v';
        private $locales = array();
        private $output = array();
        private $locale;
        private $language;
        private $match = array();

        public function getLocales()
        {
                return $this->locales;
        }

        public function enumLocales()
        {
                exec($this->command, $this->output);

                foreach ($this->output as $row) {
                        $row = trim($row);
                        $this->matchLocale($row);
                        $this->matchLanguage($row);
                }
        }

        private function matchLocale($row)
        {
                if (preg_match("/^(locale):\s+(.*?)\s+.*/", $row, $this->match)) {
                        $this->locale = trim($this->match[2]);
                }
        }

        private function matchLanguage($row)
        {
                if (preg_match("/^(language)\s+\|\s+(.*)/", $row, $this->match)) {
                        $this->locales[$this->locale] = trim($this->match[2]);
                }
        }

}

$listloc = new ListLocales();
$listloc->enumLocales();
$locales = $listloc->getLocales();

printf("<?php\n\n//\n// system locales: %s\n//\n\n", $listloc->command);
printf("return %s;\n", var_export($locales, true));
