<?php

namespace OpenExam\Library\Database;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-04 at 16:59:06.
 */
class MigrationTest extends TestCase
{

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                
        }

        /**
         * @covers OpenExam\Library\Database\Migration::run
         * @group migration
         */
        public function testRun()
        {
                Migration::run($this->config, null, false);
        }

}