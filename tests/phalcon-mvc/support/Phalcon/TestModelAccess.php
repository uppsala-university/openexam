<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    TestModelAccess.php
// Created: 2014-09-24 05:52:46
// 
// Author:  Anders Lövgren (QNET/BMC CompDept)
// 

namespace OpenExam\Tests\Phalcon;

use Exception;
use OpenExam\Library\Security\Roles;
use OpenExam\Library\Security\User;
use OpenExam\Models\Access\AuthorizationInterface;
use OpenExam\Models\Admin;
use OpenExam\Models\Contributor;
use OpenExam\Models\Corrector;
use OpenExam\Models\Decoder;
use OpenExam\Models\Exam;
use OpenExam\Models\Invigilator;
use OpenExam\Models\Student;
use OpenExam\Models\Teacher;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Transaction;

class LocalException extends Exception
{
        
}

/**
 * Helper class for testing model access.
 * 
 * Essential, this class creates a truth table of role -> resource -> action 
 * and uses it to validate that the outcome holds (permitted actions succeed 
 * and disallowed action fails).
 * 
 * Tests are performed using sample data inserted by running:
 * <code>
 * bash$> 'php phalcon-mvc/script/unittest.php --setup'
 * </code>
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class TestModelAccess extends TestModelBasic
{

        protected $sample;
        protected $values;
        protected $access;
        protected static $role = 'dummy';
        protected static $roles;
        protected static $resources;
        protected static $actions;
        protected $user;
        protected $task;

        /**
         * Constructor
         * @param AuthorizationInterface $object An model object.
         * @param string $access Path to access list definition.
         */
        public function __construct($object, $access = null)
        {
                parent::__construct($object);

                if (!isset(self::$roles)) {
                        self::$roles = array(
                                'admin', 'contributor', 'corrector', 'creator',
                                'decoder', 'invigilator', 'student', 'teacher'
                        );
                }

                if (!isset(self::$resources)) {
                        self::$resources = array(
                                'admin', 'answer', 'computer', 'contributor',
                                'corrector', 'decoder', 'exam', 'file',
                                'invigilator', 'lock', 'question', 'resource',
                                'result', 'room', 'student', 'teacher', 'topic'
                        );
                }

                if (!isset(self::$actions)) {
                        self::$actions = array(
                                'create', 'read', 'update', 'delete'
                        );
                }

                // 
                // Setup access list:
                // 
                if (!isset($access)) {
                        $access = CONFIG_DIR . '/access.def';
                }
                if (!file_exists($access)) {
                        self::error("Missing access control file $access");
                } else {
                        $this->access = self::getAccessList(require($access));
                }

                // 
                // Load sample data:
                //                
                $this->sample = sprintf("%s/unittest/sample.dat", $this->config->application->cacheDir);
                if (!file_exists($this->sample)) {
                        self::error("Sample data is missing, please run 'php phalcon-mvc/script/unittest.php --setup'");
                }
                $this->sample = unserialize(file_get_contents($this->sample));
                self::info("sample=%s", print_r($this->sample, true));

                // 
                // Set test values from sample data:
                // 
                $this->values = $this->sample[$object->getName()];
                $this->object->assign($this->values);
        }

        /**
         * Build role -> resource -> actions tree:
         * @param array $system The system configured access list.
         */
        private static function getAccessList($system)
        {
                $access = array();

                // 
                // All actions are disallow by default:
                // 
                foreach (self::$roles as $role) {
                        foreach (self::$resources as $resource) {
                                foreach (self::$actions as $action) {
                                        $access[$role][$resource][$action] = false;
                                }
                        }
                }

                // 
                // Merge in $system:
                // 
                foreach ($system['roles'] as $role => $resources) {
                        if (is_string($resources) && $resources == '*') {
                                foreach (self::$resources as $resource) {
                                        foreach (self::$actions as $action) {
                                                $access[$role][$resource][$action] = true;
                                        }
                                }
                                continue;
                        }
                        foreach ($resources as $resource => $action) {
                                $actions = $system['permissions'][$action];
                                if (is_string($actions) && $actions == '*') {
                                        foreach (self::$actions as $action) {
                                                $access[$role][$resource][$action] = true;
                                        }
                                } elseif (is_array($actions)) {
                                        foreach ($actions as $action) {
                                                $access[$role][$resource][$action] = true;
                                        }
                                } else {
                                        $access[$role][$resource][$actions] = true;
                                }
                        }
                }

                return $access;
        }

        /**
         * Create new model object using supplied values.
         */
        private function createModelObject()
        {
                self::info("object=%s", $this->object->getName());

                $role = $this->user->getPrimaryRole();
                $this->user->setPrimaryRole(null);

                $class = get_class($this->object);
                $this->object = new $class();
                $this->object->assign($this->values);

                $this->user->setPrimaryRole($role);
        }

        /**
         * Delete model object.
         */
        private function deleteModelObject()
        {
                self::info("object=%s", $this->object->getName());

                $role = $this->user->getPrimaryRole();
                $this->user->setPrimaryRole(null);

                if (isset($this->object->new_id)) {
                        $this->object->id = $this->object->new_id;
                }
                if ($this->object->id != 0) {
                        $this->object->delete();
                }

                $this->user->setPrimaryRole($role);
        }

        /**
         * Set username on all defined roles.
         * @param Model[] $roles The role models.
         * @param string $user The username.
         */
        protected function setAllRolesUser($roles, $user)
        {
                $this->getDI()->set('db', $roles[key($roles)]->getWriteConnection());
                
                $transaction = new Transaction($this->getDI());
                $transaction->begin();

                self::info("Setting all roles user to %s", $user);
                foreach ($roles as $role => $object) {
                        $object->setTransaction($transaction);

                        if ($this->object->getName() == $role) {
                                $this->object->user = $user;
                        }
                        if ($role == Roles::CREATOR) {
                                $object->creator = $user;
                        } else {
                                $object->user = $user;
                        }
                        if ($object->update() == false) {
                                foreach ($object->getMessages() as $message) {
                                        $transaction->rollback($message->getMessage());
                                        self::error($message);
                                }
                        }

                        self::info("Set user %s on %s (%s)", $user, $object->getName(), get_class($object));
                }
                
                $transaction->commit();

                self::info("Verifying roles for user %s", $user);
                foreach ($roles as $role => $object) {
                        $class = get_class($object);
                        $model = $class::findFirstById($object->id);
                        self::info("Verifying user %s on %s (%s)", $user, $model->getName(), get_class($model));
                        self::assertEquals($object->dump(), $model->dump());
                }
        }

        /**
         * Check role access on this model.
         * 
         * Checking that allowed actions succeed is equal important that
         * checking that disallowed action fails.
         * 
         * @group model
         * @group security
         */
        public function testModelAccess()
        {
                self::info("name=%s", $this->object->getName());

                // 
                // Use sample exam:
                // 
                $roles = array();
                $roles['admin'] = Admin::findFirst($this->sample['admin']['id']);
                $roles['teacher'] = Teacher::findFirst($this->sample['teacher']['id']);
                $roles['creator'] = Exam::findFirst($this->sample['exam']['id']);
                $roles['contributor'] = Contributor::findFirst($this->sample['contributor']['id']);
                $roles['decoder'] = Decoder::findFirst($this->sample['decoder']['id']);
                $roles['invigilator'] = Invigilator::findFirst($this->sample['invigilator']['id']);
                $roles['student'] = Student::findFirst($this->sample['student']['id']);
                $roles['corrector'] = Corrector::findFirst($this->sample['corrector']['id']);

                // 
                // Inject unauthenticated user:
                // 
                $this->user = new User();
                $this->getDI()->set('user', $this->user);

                // 
                // Reset roles user:
                // 
                $role = $this->user->setPrimaryRole(null);
                $this->setAllRolesUser($roles, 'user1');
                $this->user->setPrimaryRole($role);

                foreach ($roles as $role => $object) {
                        self::info("%d: +++ user=%s, data=%s", __LINE__, $this->user->getPrincipalName(), print_r($object->dump(), true));
                }

                // 
                // Should pass. If primary role is unset, then the model
                // layer should grant unrestricted access.
                // 
                self::info("*** expect: pass (primary role unset) ***");
                foreach ($roles as $role => $object) {
                        try {
                                $this->user->setPrimaryRole(null);
                                $this->checkModelAccess($role, null);
                        } catch (Exception $exception) {
                                self::error($exception, "Unexpected exception (%s)", get_class($this->object));
                        }
                }

                // 
                // Should fail. Primary role is set, but user is not
                // authenticated.
                // 
                self::info("*** expect: fail (user not authenticated) ***");
                foreach ($roles as $role => $object) {
                        try {
                                $this->user->setPrimaryRole($role);
                                $this->checkModelAccess($role, 'auth');
                        } catch (Exception $exception) {
                                self::error($exception, "Unexpected exception (%s)", get_class($this->object));
                        }
                }

                // 
                // Inject a fake authenticated user:
                // 
                $this->user = new User((new UniqueUser())->user);
                $this->getDI()->set('user', $this->user);

                // 
                // Should fail. User is authenticated, but lacks any roles.
                // 
                self::info("*** expect: fail (no assigned roles) ***");
                foreach ($roles as $role => $object) {
                        try {
                                $this->user->setPrimaryRole($role);
                                $this->checkModelAccess($role, 'role');
                        } catch (Exception $exception) {
                                self::error($exception, "Unexpected exception (%s)", get_class($this->object));
                        }
                }

                foreach ($roles as $role => $object) {
                        self::info("%d: +++ user=%s, data=%s", __LINE__, $this->user->getPrincipalName(), print_r($object->dump(), true));
                }

                // 
                // Set roles to authenticated user:
                // 
                $role = $this->user->setPrimaryRole(null);
                $this->setAllRolesUser($roles, $this->user->getPrincipalName());
                $this->user->setPrimaryRole($role);

                foreach ($roles as $role => $object) {
                        self::info("%d: +++ user=%s, data=%s", __LINE__, $this->user->getPrincipalName(), print_r($object->dump(), true));
                }

                // 
                // Update model create values:
                // 
                foreach (array('user', 'creator') as $key) {
                        if (isset($this->values[$key])) {
                                $this->values[$key] = $this->user->getPrincipalName();
                        }
                }

                // 
                // Should succeed. Authenticated user has the requested
                // primary role (changed by $object->update()).
                // 
                self::info("*** expect: pass (primary roles assigned) ***");
                foreach ($roles as $role => $object) {
                        try {
                                $this->user->setPrimaryRole($role);
                                $this->checkModelAccess($role, 'access');
                        } catch (Exception $exception) {
                                self::error($exception, "Unexpected exception (%s)", get_class($this->object));
                        }
                }

                // 
                // Keep phpunit happy:
                // 
                self::assertTrue(true);
        }

        private function checkModelAccess($role, $error)
        {
                self::info("role=%s, user=%s", $role, $this->user->getPrincipalName());

                foreach ($this->access[$role] as $resource => $actions) {
                        if ($resource == $this->object->getName()) {
                                $this->createModelObject();
                                foreach ($actions as $action => $permit) {
                                        $this->checkModelAction($action, $permit, $error);
                                }
                                $this->deleteModelObject();
                        }
                }
        }

        /**
         * Check performing action on current object.
         * 
         * @param string $action The action to perform.
         * @param bool $permit Should model action be permitted?
         * @param string $error The expected exception message.
         * @group model
         * @group security
         */
        private function checkModelAction($action, $permit, $error)
        {
                self::info("action=%s, permit=%s, error=%s", $action, $permit ? "yes" : "no", $error);

                try {
                        switch ($action) {
                                case 'create':
                                        $this->checkModelCreate($permit, $error);
                                        break;
                                case 'read':
                                        $this->checkModelRead($permit, $error);
                                        break;
                                case 'update':
                                        $this->checkModelUpdate($permit, $error);
                                        break;
                                case 'delete':
                                        $this->checkModelDelete($permit, $error);
                                        break;
                                default:
                                        self::error("Unknown action $action");
                        }
                } catch (LocalException $exception) {
                        self::error($exception);
                } catch (Exception $exception) {
                        if ($exception->getMessage() == 'user' || $exception->getMessage() == 'acl') {
                                self::error($exception, "System configuration error");
                        } elseif (isset($error) && $exception->getMessage() == $error) {
                                self::success("Trapped expected exception (type %s)", $exception->getMessage());
                        } elseif ($permit == false && $exception->getMessage() == 'access') {
                                self::success("Blocked access for disallowed role (type %s)", $exception->getMessage());
                        } else {
                                self::error($exception, "Unexpected exception");
                        }
                }
        }

        /**
         * Test create on current object.
         * 
         * @param bool $permit Should model action be permitted?
         * @param string $error The expected exception message.
         * @group model
         * @group security
         */
        private function checkModelCreate($permit, $error)
        {
                self::info("model=%s, user=%s, role=%s, permit=%s, error=%s", $this->object->getName(), $this->user->getPrincipalName(), $this->user->getPrimaryRole(), $permit ? "yes" : "no", $error);

                // 
                // For read, update and delete to be successful tested, its 
                // required that we preserve the orignial object ID. This is
                // is because we need to preserve the model relations with
                // other models for authorization to make sense.
                // 
                if ($this->object->id != 0) {
                        $this->object->old_id = $this->object->id;
                        $this->object->id = 0;
                }
                if ($this->object->create() == false) {
                        throw new LocalException(print_r($this->object->getMessages(), true));
                } else {
                        $this->object->new_id = $this->object->id;
                        $this->object->id = $this->object->old_id;
                }
                if (!$permit && isset($error)) {
                        throw new LocalException("Expected $error exception not thrown (not permitted).");
                } else {
                        self::success("Created object %s(id=%d)", $this->object->getName(), $this->object->new_id);
                }
        }

        /**
         * Test read on current object.
         * 
         * @param bool $permit Should model action be permitted?
         * @param string $error The expected exception message.
         * @group model
         * @group security
         */
        private function checkModelRead($permit, $error)
        {
                self::info("model=%s, user=%s, role=%s, permit=%s, error=%s", $this->object->getName(), $this->user->getPrincipalName(), $this->user->getPrimaryRole(), $permit ? "yes" : "no", $error);

                if ($this->object->id == 0) {
                        self::warn("Object id == 0 (skipped)");
                        return;
                }

                $class = get_class($this->object);

                if (($object = $class::findFirstById($this->object->id)) == false) {
                        throw new LocalException(print_r($this->object->getMessages(), true));
                }
                if (!$permit && isset($error)) {
                        throw new LocalException("Expected $error exception not thrown (not permitted).");
                } else {
                        self::success("Read object %s(id=%d)", $this->object->getName(), $this->object->id);
                }
        }

        /**
         * Test update on current object.
         * 
         * @param bool $permit Should model action be permitted?
         * @param string $error The expected exception message.
         * @group model
         * @group security
         */
        private function checkModelUpdate($permit, $error)
        {
                self::info("model=%s, user=%s, role=%s, permit=%s, error=%s", $this->object->getName(), $this->user->getPrincipalName(), $this->user->getPrimaryRole(), $permit ? "yes" : "no", $error);

                if ($this->object->id == 0) {
                        self::warn("Object id == 0 (skipped)");
                        return;
                }
                if ($this->object->update() == false) {
                        throw new LocalException(print_r($this->object->getMessages(), true));
                }
                if (!$permit && isset($error)) {
                        throw new LocalException("Expected $error exception not thrown (not permitted).");
                } else {
                        self::success("Updated object %s(id=%d)", $this->object->getName(), $this->object->id);
                }
        }

        /**
         * Test delete on current object.
         * 
         * @param bool $expect Expected result.
         * @param string $error The expected exception message.
         * @group model
         * @group security
         */
        private function checkModelDelete($permit, $error)
        {
                self::info("model=%s, user=%s, role=%s, permit=%s, error=%s", $this->object->getName(), $this->user->getPrincipalName(), $this->user->getPrimaryRole(), $permit ? "yes" : "no", $error);

                if ($this->object->id == 0) {
                        self::warn("Object id == 0 (skipped)");
                        return;
                }
                // 
                // Delete the created object, not the one from sample data.
                // 
                if (isset($this->object->new_id)) {
                        $this->object->id = $this->object->new_id;
                }
                if ($this->object->delete() == false) {
                        throw new LocalException(print_r($this->object->getMessages(), true));
                } else {
                        $this->object->id = $this->object->old_id;
                }
                if (!$permit && isset($error)) {
                        throw new LocalException("Expected $error exception not thrown (not permitted).");
                } else {
                        self::success("Deleted object %s(id=%d)", $this->object->getName(), $this->object->new_id);
                }
        }

}
