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

namespace OpenExam\Library\Security;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-09-24 at 05:03:10.
 */
class AclTest extends TestCase {

  /**
   * @var Acl
   */
  private $_object;
  private $_access;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    $this->_access = require CONFIG_DIR . '/access.def';
    $this->_object = new Acl($this->_access);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {

  }

  /**
   * @covers OpenExam\Library\Security\Acl::getAcl
   * @group security
   */
  public function testGetAcl() {
    $actual = $this->_object->getAcl();
    self::assertNotNull($actual);
    self::assertInstanceOf('Phalcon\Acl\Adapter\Memory', $actual);
  }

  /**
   * @group security
   */
  public function testConfig() {
    self::assertTrue(is_array($this->_access));
    self::assertTrue(isset($this->_access['roles']));
    self::assertTrue(isset($this->_access['permissions']));
    self::assertTrue(count($this->_access['roles']) > 0);
    self::assertTrue(count($this->_access['permissions']) > 0);
  }

  /**
   * @covers OpenExam\Library\Security\Acl::isAllowed
   * @group security
   */
  public function testIsAllowed() {
    //
    // These should all succeed:
    //
    foreach ($this->_access['roles'] as $role => $resources) {
      if (is_array($resources)) {
        $this->checkResources($role, $resources);
      } elseif (is_string($resources)) {
        $this->checkPermission($role, $resources, $resources, true);
      } else {
        fail("Impossible!");
      }
    }

    //
    // Undefined roles and actions should fail, while undefined
    // resources should succeed:
    //
    $this->checkPermission('custom', 'question', 'read', false); // non-existing role (custom)
    $this->checkPermission('invigilator', 'exam', 'toast', false); // non-existing action (toast)
    $this->checkPermission('invigilator', 'panncake', 'read', true); // non-existing resource (panncake)
  }

  /**
   * Check resources.
   * @param string $role The role.
   * @param array $resources Array of resources.
   * @group security
   */
  private function checkResources($role, $resources) {
    foreach ($resources as $resource => $action) {
      $actions = $this->_access['permissions'][$action];
      if (is_array($actions)) {
        $this->checkActions($role, $resource, $actions);
      } elseif (is_string($actions)) {
        $this->checkPermission($role, $resource, $actions, true);
      } else {
        fail("Impossible!");
      }
    }
  }

  /**
   * Check actions.
   * @param string $role The role.
   * @param string $resource The resource.
   * @param array $actions Array of actions.
   */
  private function checkActions($role, $resource, $actions) {
    foreach ($actions as $action) {
      $this->checkPermission($role, $resource, $action, true);
    }
  }

  /**
   * Check that role has permission to perform action on a resource.
   * @param string $role The role.
   * @param string $resource The resource.
   * @param string $action The action to perform.
   */
  private function checkPermission($role, $resource, $action, $expect) {
    $actual = $this->_object->isAllowed($role, $resource, $action);
    self::info("role=%s, resource=%s, action=%s: allowed=%s", $role, $resource, $action, $actual ? 'yes' : 'no');
    self::assertEquals($expect, $actual);
  }

}
