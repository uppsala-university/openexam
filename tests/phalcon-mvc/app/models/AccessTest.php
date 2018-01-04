<?php

/*
 * Copyright (C) 2014-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace OpenExam\Models;

use OpenExam\Library\Security\User;
use OpenExam\Tests\Phalcon\TestModel;
use OpenExam\Tests\Phalcon\UniqueUser;

/**
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AccessModel extends Access
{

        public function initialize()
        {
                parent::initialize();
        }

}

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-12-15 at 09:20:59.
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class AccessTest extends TestModel
{

        /**
         * The model resource name.
         */
        const MODEL = 'access';

        /**
         * @group model
         */
        public function testRelations()
        {
                $object = Access::findFirstById($this->_sample->getSample(self::MODEL)['id']);
                self::assertNotNull($object);

                self::assertNotEquals(0, $object->exam->count());
                self::assertTrue(count($object->exam) == 1);
        }

        /**
         * @covers OpenExam\Models\Access::getSource
         * @group model
         */
        public function testGetSource()
        {
                $object = new AccessModel();
                $expect = "access";
                $actual = $object->getSource();
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Models\Access::create
         * @group model
         */
        public function testCreate()
        {
                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, true));
                        $this->cleanup($model);
                }

                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, false));
                        $this->cleanup($model);
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
                        self::assertTrue($this->create($model, $user, false));
                        $this->cleanup($model);
                }

                $user = $this->getDI()->get('user');
                $roles = $this->_capabilities->getRoles(self::MODEL);

                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
                        if (in_array('create', $actions)) {
                                self::assertTrue($this->create($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->create($model, $user, false));  // action denied
                        }
                        $this->cleanup($model);
                }
        }

        /**
         * @covers OpenExam\Models\Access::update
         * @group model
         */
        public function testUpdate()
        {
                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, true));
                }

                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, false));
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        self::assertTrue($this->update($model, $user, false));
                }

                $user = $this->getDI()->get('user');
                $roles = $this->_capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->_sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        if (in_array('update', $actions)) {
                                self::assertTrue($this->update($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->update($model, $user, false));  // action denied
                        }
                }
        }

        /**
         * @covers OpenExam\Models\Access::delete
         * @group model
         */
        public function testDelete()
        {
                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, true));
                        $this->cleanup($model);
                }

                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, false));
                        $this->cleanup($model);
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
                        $this->persist($model);
                        self::assertTrue($this->delete($model, $user, false));
                        $this->cleanup($model);
                }

                $user = $this->getDI()->get('user');
                $roles = $this->_capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->_sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL, false));
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
         * @covers OpenExam\Models\Access::find
         * @group model
         */
        public function testRead()
        {
                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ pass: primary role unset");
                foreach ($roles as $role) {
                        $user->setPrimaryRole(null);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, true));
                }

                $user = new User();
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user not authenticated");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, false));
                }

                $user = new User((new UniqueUser())->user);
                $roles = $this->_capabilities->getRoles();

                self::info("+++ fail: user without roles");
                foreach ($roles as $role) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        self::assertTrue($this->read($model, $user, false));
                }

                $user = $this->getDI()->get('user');
                $roles = $this->_capabilities->getRoles(self::MODEL);

                self::info("sample=%s", print_r($this->_sample->getSample(self::MODEL), true));
                self::info("rolemap=%s", print_r($roles, true));

                self::info("+++ pass: user has roles");
                foreach ($roles as $role => $actions) {
                        $user->setPrimaryRole($role);
                        $model = new Access();
                        $model->assign($this->_sample->getSample(self::MODEL));
                        if (in_array('read', $actions)) {
                                self::assertTrue($this->read($model, $user, true));   // action allowed
                        } else {
                                self::assertTrue($this->read($model, $user, false));  // action denied
                        }
                }
        }

}
