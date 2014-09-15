<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    ModelManager.php
// Created: 2014-09-11 14:32:05
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Model;

use DirectoryIterator;
use Phalcon\Builder\AllModels;
use Phalcon\DI\Injectable;
use Phalcon\DI\InjectionAwareInterface;
use Phalcon\Events\EventsAwareInterface;

/**
 * Description of ModelManager
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class ModelManager extends Injectable implements InjectionAwareInterface, EventsAwareInterface
{

        /**
         * Synchronize model.
         * @param array $options
         */
        public function sync(array $options)
        {
                $this->clean($options);
                $this->create($options);
                $this->update($options);
        }

        /**
         * Create model.
         * @param array $options
         */
        public function create(array $options)
        {
                $local = array(
                        'defineRelations' => true,
                        'foreignKeys'     => true,
                        'extends'         => 'ModelBase',
                        'namespace'       => 'OpenExam\Models',
                        'directory'       => $this->config->application->baseDir,
                        'config'          => $this->config,
                );

                $options = array_merge($local, $options);

                if (!chdir($this->config->application->baseDir)) {
                        throw new Exception("Failed switch directory to project root.");
                }
                if ($options['verbose']) {
                        $this->flash->notice(sprintf("Creating models in %s", $this->config->application->modelsDir));
                }

                $allmodels = new AllModels($options);
                $allmodels->build();

                $this->flash->success("Model create completed successful.");
                return true;
        }

        /**
         * backup model.
         * @param array $options
         */
        public function backup(array $options)
        {
                if ($options['verbose']) {
                        $this->flash->notice(sprintf("Starting backup of model to %s", $options['backup']));
                }

                if (file_exists($options['backup']) && !$options['force']) {
                        $this->flash->error("Destination directory exist. Use --force to force backup.");
                        return false;
                }
                if (!$options['force']) {
                        if (!mkdir($options['backup'], 0755, true)) {
                                throw new Exception("Failed create destination directory.");
                        }
                }

                $iterator = new DirectoryIterator($this->config->application->modelsDir);
                foreach ($iterator as $file) {
                        if ($file->isFile()) {
                                if ($options['verbose']) {
                                        $this->flash->notice(sprintf("Copying %s to %s", $file->getBasename(), $options['backup']));
                                }
                                $srcfile = $file->getRealPath();
                                $dstfile = sprintf("%s/%s", $options['backup'], $file->getFilename());
                                if (!copy($srcfile, $dstfile)) {
                                        throw new Exception(sprintf("Failed copy %s.", $file->getFilename()));
                                }
                        }
                }

                $this->flash->success("Model backup completed successful.");
                return true;
        }

        /**
         * Cleanup model.
         * @param array $options
         */
        public function clean(array $options)
        {
                if (!chdir($this->config->application->modelsDir)) {
                        throw new Exception("Failed switch to models directory.");
                }

                $iterator = new DirectoryIterator($this->config->application->modelsDir);
                foreach ($iterator as $file) {
                        if ($file->isFile()) {
                                if (preg_match("/.*s\.php$/", $file->getFilename())) {
                                        if ($options['verbose']) {
                                                $this->flash->notice(sprintf("Removing %s", $file->getRealPath()));
                                        }
                                        if (!unlink($file->getRealPath())) {
                                                throw new Exception(sprintf("Failed remove %s", $file->getRealPath()));
                                        }
                                }
                        }
                }
                $this->flash->success("Model cleanup completed successful.");
                return true;
        }

        /**
         * Update model.
         * @param array $options
         */
        public function update(array $options)
        {
                // TODO: implement automatic patch of models.
                $this->flash->error("Not yet implemented. Please update the model manual.");
                return false;
        }

}
