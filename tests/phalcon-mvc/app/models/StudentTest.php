<?php

namespace OpenExam\Models;

use OpenExam\Library\Security\User;
use OpenExam\Tests\Phalcon\TestModel;
use OpenExam\Tests\Phalcon\UniqueUser;

/**
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class StudentModel extends Student
{

        public function initialize()
        {
                parent::initialize();
        }

}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-15 at 18:28:47.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class StudentTest extends TestModel
{

        /**
         * The model resource name.
         */
        const MODEL = 'student';

        /**
         * @group model
         */
        public function testRelations()
        {
                $object = Student::findFirstById($this->sample->getSample(self::MODEL)['id']);
                self::assertNotNull($object);

                self::assertNotEquals(0, $object->exam->count());
                self::assertTrue(count($object->exam) == 1);

                self::assertNotEquals(0, $object->answers->count());
                self::assertTrue(count($object->answers) > 0);
        }

        /**
         * @covers OpenExam\Models\Student::getSource
         * @group model
         */
        public function testGetSource()
        {
                $object = new StudentModel();
                $expect = "students";
                $actual = $object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * Test model behavior.
         * @group model
         */
        public function testBehavior()
        {
                $user = $this->getDI()->get('user');

                /**
                 * Test anonymous code generation:
                 */
                $object = new Student();
                $object->assign($this->sample->getSample('student', false));
                $object->code = null;

                self::assertTrue($object->create());
                self::assertNotNull($object->code);
                self::assertTrue(is_string($object->code));

                $object->delete();

                /**
                 * Test user principal lookup:
                 */
                if (!isset($this->config->phpunit->username)) {
                        return;
                }
                
                // 
                // Requires configured data for LDAP lookup:
                // 
                $expect = $this->config->phpunit->username;
                $persnr = $this->config->phpunit->persnr;

                $object = new Student();
                $object->assign($this->sample->getSample('student', false));
                $object->user = $persnr;

                self::assertTrue($object->create());
                self::assertNotNull($object->user);
                self::assertTrue(is_string($object->user));

                $actual = $object->user;
                self::assertEquals($expect, $actual);

                $object->delete();
        }

        /**
         * @covers OpenExam\Models\Student::create
         * @group model
         */
        public function testCreate()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, true));
                        $this->cleanup($model);
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, false));
                        $this->cleanup($model);
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, false));
                        $this->cleanup($model);
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        if (in_array('create', $actions)) {
                                self::assertTrue($this->create($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->create($model, $user, false));  // action denied
                        }
                        $this->cleanup($model);
                }
        }

        /**
         * @covers OpenExam\Models\Student::update
         * @group model
         */
        public function testUpdate()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, true));
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, false));
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, false));
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        if (in_array('update', $actions)) {
                                self::assertTrue($this->update($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->update($model, $user, false));  // action denied
                        }
                }
        }

        /**
         * @covers OpenExam\Models\Student::delete
         * @group model
         */
        public function testDelete()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, true));
                        $this->cleanup($model);
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, false));
                        $this->cleanup($model);
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, false));
                        $this->cleanup($model);
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        if (in_array('delete', $actions)) {
                                self::assertTrue($this->delete($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->delete($model, $user, false));  // action denied
                        }
                        $this->cleanup($model);
                }
        }

        /**
         * @covers OpenExam\Models\Student::find
         * @group model
         */
        public function testRead()
        {
                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, true));
                }

                $user = new User();
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, false));
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, false));
                }

                $user = $this->getDI()->get('user');
                $roles = $this->capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Student();
                        $model->assign($this->sample->getSample(self::MODEL));
                        if (in_array('read', $actions)) {
                                self::assertTrue($this->read($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->read($model, $user, false));  // action denied
                        }
                }
        }

}
