<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    UserTask.php
// Created: 2017-04-06 11:00:37
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Console\Tasks;

use OpenExam\Library\Catalog\Principal;
use OpenExam\Models\User;

/**
 * User account task.
 * 
 * Use this task to generate user accounts inserted in the users table. The 
 * system itself don't provide a user database with stored password, instead 
 * these accounts is used together with anonymous logon.
 *
 * @author Anders Lövgren (QNET/BMC CompDept)
 */
class UserTask extends MainTask implements TaskInterface
{

        /**
         * Runtime options
         * @var array 
         */
        private $_options;
        /**
         * The username format (for UID).
         * @var string|callable 
         */
        private $_format;
        /**
         * The user domain.
         * @var string 
         */
        private $_domain;
        /**
         * The user source.
         * @var string 
         */
        private $_source;
        /**
         * The column map.
         * @var array 
         */
        private $_colmap = array(
                'fname', 'lname', 'mail', 'pnr'
        );

        public function initialize()
        {
                if (isset($this->config->user->generate->format)) {
                        $this->_format = $this->config->user->generate->format;
                } else {
                        $this->_format = 'oe%1$s%2$s%3$s';
                }

                if (isset($this->config->user->generate->domain)) {
                        $this->_domain = $this->config->user->generate->domain;
                } else {
                        $this->_domain = gethostname();
                }

                if (isset($this->config->user->generate->source)) {
                        $this->_source = $this->config->user->generate->source;
                } else {
                        $this->_source = 'usertask';
                }
        }

        public function helpAction()
        {
                parent::showUsage(self::getUsage());
        }

        public static function getUsage()
        {
                return array(
                        'header'   => 'User account and gecos task.',
                        'action'   => '--user',
                        'usage'    => array(
                                '--generate --input=file [--fname=col] [--lname=col] [--mail=col] [--pnr=col] [--output=file]'
                        ),
                        'options'  => array(
                                '--generate'    => 'Generate user accounts.',
                                '--input=file'  => 'User information input file (tab separated).',
                                '--output=file' => 'User information output file (tab separated).',
                                '--fname=col'   => 'Map column as first name',
                                '--lname=col'   => 'Map column as last name',
                                '--mail=col'    => 'Map column as email',
                                '--pnr=col'     => 'Map column as personal number',
                                '--force'       => 'Force account update.',
                                '--verbose'     => 'Be more verbose.',
                                '--dry-run'     => 'Just print whats going to be done.'
                        ),
                        'examples' => array(
                                array(
                                        'descr'   => 'Generate user accounts information from file',
                                        'command' => '--generate --input=file.txt'
                                ),
                                array(
                                        'descr'   => 'Generate user accounts information with column map (first column is 1)',
                                        'command' => '--generate --input=file.txt --fname=4 --lname=3 --mail=2 --pnr=1'
                                ),
                                array(
                                        'descr'   => 'Write user accounts generation information to file',
                                        'command' => '--generate --input=file1.txt --output=file2.txt'
                                ),
                        )
                );
        }

        /**
         * Generate accounts action.
         * @param array $params
         */
        public function generateAction($params = array())
        {
                $this->setOptions($params, 'generate');

                if (!file_exists($this->_options['input'])) {
                        $this->flash->error("The input file is missing");
                        return false;
                }

                if (!($handle = fopen($this->_options['input'], "r"))) {
                        $this->flash->error("Failed open input file");
                        return false;
                }
                if ($this->_options['output'] == false) {
                        $output = STDOUT;
                } elseif (!($output = fopen($this->_options['output'], "w"))) {
                        $this->flash->error("Failed open output file");
                        return false;
                }

                while (($line = fgets($handle))) {
                        if (strpos($line, "\t") !== false) {
                                $gecos = preg_split('/\t+/', trim($line));
                        } else {
                                $gecos = preg_split('/\s+/', trim($line));
                        }

                        $user = $this->getUser($this->getPrincipal($gecos));

                        if ($this->_options['dry-run']) {
                                $this->reportAction('logged', $user, $output);
                        } elseif ($user->id != 0 && !$this->_options['force']) {
                                $this->reportAction('exists', $user, $output);
                        } elseif ($user->id == 0) {
                                if ($user->create() == false) {
                                        $this->flash->error(sprintf("Failed create user %s: %s", $user->principal, $user->getMessages()[0]));
                                        return false;
                                }
                                $this->reportAction('create', $user, $output);
                        } else {
                                if ($user->update() == false) {
                                        $this->flash->error(sprintf("Failed update user %s: %s", $user->principal, $user->getMessages()[0]));
                                        return false;
                                }
                                $this->reportAction('update', $user, $output);
                        }
                }

                if (!fclose($handle)) {
                        $this->flash->error("Failed close input file");
                        return false;
                }
                if ($output && !fclose($output)) {
                        $this->flash->error("Failed close output file");
                        return false;
                }
        }

        /**
         * Get remapped gecos attribute.
         * 
         * @param string $name The attribute name (i.e. fname).
         * @param array $gecos The gecos information.
         * @return boolean|string
         */
        private function getAttribute($name, $gecos)
        {
                foreach ($this->_colmap as $key => $val) {
                        if ($name == $val && isset($gecos[$key])) {
                                return $gecos[$key];
                        }
                }

                return false;
        }

        /**
         * Get user principal object.
         * @param array $gecos The gecos information.
         * @return Principal
         */
        private function getPrincipal($gecos)
        {
                $principal = new Principal();
                $principal->gn = $this->getAttribute('fname', $gecos);
                $principal->sn = $this->getAttribute('lname', $gecos);
                $principal->mail = $this->getAttribute('mail', $gecos);
                $principal->pnr = $this->getAttribute('pnr', $gecos);

                if ($principal->pnr == '-') {
                        $principal->pnr = false;
                }

                $principal->normalize();
                $principal->generate($this->_format, $this->_domain);

                return $principal;
        }

        /**
         * Report user action.
         * @param string $action The action taken.
         * @param User $user The user model.
         * @param resource $handle The output stream.
         */
        private function reportAction($action, $user, $handle)
        {
                if (is_resource($handle)) {
                        $data = sprintf("%s\t%s\t%s\t%s\t%s\t[%s]\n", $user->principal, $user->givenName, $user->sn, $user->mail, $user->pnr, $action);
                        fwrite($handle, $data);
                }
                if ($this->_options['verbose']) {
                        $this->flash->notice(sprintf("User %s\t[%s]", $user->principal, $action));
                }
        }

        /**
         * Get existing or new user.
         * @param Principal $principal The user principal object.
         * @return User
         */
        private function getUser($principal)
        {
                if (!($user = User::findFirst(array(
                            'conditions' => 'principal = :principal:',
                            'bind'       => array(
                                    'principal' => $principal->principal
                            )
                    )))) {
                        $user = new User();
                }

                $user->uid = $principal->uid;
                $user->principal = $principal->principal;
                $user->pnr = $principal->pnr;
                $user->mail = $principal->mail;

                $user->givenName = $principal->gn;
                $user->sn = $principal->sn;
                $user->cn = $principal->name;
                $user->displayName = $principal->name;

                $user->o = $this->config->user->orgname;

                $user->source = $this->_source;
                $user->domain = $this->_domain;

                return $user;
        }

        /**
         * Set options from task action parameters.
         * @param array $params The task action parameters.
         * @param string $action The calling action.
         */
        private function setOptions($params, $action = null)
        {
                // 
                // Default options.
                // 
                $this->_options = array('verbose' => false, 'force' => false, 'dry-run' => false);

                // 
                // Supported options.
                // 
                $options = array('verbose', 'force', 'dry-run', 'generate', 'input', 'output', 'fname', 'lname', 'mail', 'pnr');
                $current = $action;

                // 
                // Set defaults.
                // 
                foreach ($options as $option) {
                        if (!isset($this->_options[$option])) {
                                $this->_options[$option] = false;
                        }
                }

                // 
                // Include action in options (for multitarget actions).
                // 
                if (isset($action)) {
                        $this->_options[$action] = true;
                }

                // 
                // Scan params for both --key and --key=val options.
                // 
                while (($option = array_shift($params))) {
                        if (in_array($option, $options)) {
                                $this->_options[$option] = true;
                                $current = $option;
                        } elseif (in_array($current, $options)) {
                                $this->_options[$current] = $option;
                        } else {
                                throw new Exception("Unknown task action/parameters '$option'");
                        }
                }

                foreach (array('fname', 'lname', 'mail', 'pnr') as $name) {
                        if ($this->_options[$name]) {
                                $this->_colmap[$this->_options[$name] - 1] = $name;
                        }
                }
        }

}
