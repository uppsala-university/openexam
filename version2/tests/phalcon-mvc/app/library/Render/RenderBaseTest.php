<?php

namespace OpenExam\Library\Render;

use OpenExam\Tests\Phalcon\TestCase;

/**
 * Custom class for testing RenderBase.
 */
class CustomRender extends RenderBase
{

        public function getOptions()
        {
                return $this->_globals;
        }

}

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-11-27 at 12:23:59.
 */
class RenderBaseTest extends TestCase
{

        /**
         * Checksum and file size properties:
         */
        private static $_output = array(
                'png'    => array(
                        'md5'  => 'b1242ce26730c72c91725e06adeb6cdc',
                        'size' => 1863147,
                        'mime' => 'image/png',
                        'type' => 'PNG image data, 1024 x 454, 8-bit/color RGBA, non-interlaced'
                ),
                'jpeg'   => array(
                        'md5'  => 'ed78c0ca0257a6a64c51c6b6e2ba7e5d',
                        'size' => 44197,
                        'mime' => 'image/jpeg',
                        'type' => 'JPEG image data, JFIF standard 1.01'
                ),
                'bmp'    => array(
                        'md5'  => 'ef46e21c679eb9d63d62368f13eae714',
                        'size' => 1394742,
                        'mime' => 'image/x-ms-bmp',
                        'type' => 'PC bitmap, Windows 3.x format, 1024 x 454 x 24'
                ),
                'svg'    => array(
                        'md5'  => '153131d44c1e4aaa94801bce150ff2ab',
                        'size' => 117052,
                        'mime' => 'image/svg+xml',
                        'type' => 'SVG Scalable Vector Graphics image'
                ),
                'pdf'    => array(
                        'md5'  => '768ea4a8a82a9a6e6a63a3f6a11dcf3c',
                        'size' => 30102,
                        'mime' => 'application/pdf',
                        'type' => 'PDF document, version 1.4'
                ),
                'unlink' => false,
                'check'  => false
        );
        /**
         * @var CustomRender
         */
        private $_object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp()
        {
                $this->_object = new CustomRender();
                $this->cwd = getcwd();
                chdir(__DIR__);
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown()
        {
                chdir($this->cwd);
        }

        /**
         * @covers OpenExam\Library\Render\RenderBase::setOptions
         * @group render
         */
        public function testSetOptions()
        {
                $actual = $this->_object->getOptions();
                self::assertTrue(is_array($actual));
                self::assertNotNull($actual);

                $expect = array('opt1' => 'val1', 'opt2' => 'val2');
                $this->_object->setOptions($expect);
                $actual = $this->_object->getOptions();
                self::assertTrue(is_array($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Render\RenderBase::addOption
         * @group render
         */
        public function testAddOption()
        {
                $actual = $this->_object->getOptions();
                self::assertTrue(is_array($actual));
                self::assertNotNull($actual);

                $expect = array('opt1' => 'val1', 'opt2' => 'val2');
                foreach ($expect as $key => $val) {
                        $this->_object->addOption($key, $val);
                }
                $actual = $this->_object->getOptions();
                self::assertTrue(is_array($actual));
                self::assertNotNull($actual);
                self::assertEquals($expect, $actual);
        }

        /**
         * @covers OpenExam\Library\Render\RenderBase::render
         * @group render
         */
        public function testRender()
        {
                // 
                // Check MIME type using builtin magic database.
                // 
                $finfo = finfo_open(FILEINFO_RAW | FILEINFO_MIME_TYPE);

                // 
                // Test image rendering:
                // 
                $globals = array(
                        'in'           => 'index.html',
                        'imageQuality' => '95'
                );

                foreach (array('png', 'jpeg', 'svg', 'bmp') as $type) {
                        $globals['out'] = sprintf("%s/render-base-test.%s", sys_get_temp_dir(), $type);

                        $stime = microtime(true);
                        self::assertTrue($this->_object->render('image', $globals));
                        $etime = microtime(true);
                        
                        self::assertTrue(file_exists($globals['out']));
                        self::assertTrue(filesize($globals['out']) != 0);

                        self::info("%s: %.04f sec", basename($globals['out']), $etime - $stime);
                        
                        self::assertEquals(self::$_output[$type]['mime'], finfo_file($finfo, $globals['out'], FILEINFO_MIME_TYPE));
                        self::assertEquals(self::$_output[$type]['type'], finfo_file($finfo, $globals['out'], FILEINFO_RAW));

                        if (self::$_output['check']) {
                                self::assertEquals(self::$_output[$type]['size'], filesize($globals['out']));
                                self::assertEquals(self::$_output[$type]['md5'], md5_file($globals['out']));
                        }
                        if (self::$_output['unlink'] && file_exists($globals['out'])) {
                                unlink($globals['out']);
                        }
                }

                // 
                // Test PDF rendering:
                // 
                $globals = array(
                        'imageQuality' => '95'
                );
                $objects = array(
                        array('page' => 'index.html')
                );

                foreach (array('pdf') as $type) {
                        $globals['out'] = sprintf("%s/render-base-test.%s", sys_get_temp_dir(), $type);

                        $stime = microtime(true);
                        self::assertTrue($this->_object->render('pdf', $globals, $objects));
                        $etime = microtime(true);
                        
                        self::assertTrue(file_exists($globals['out']));
                        self::assertTrue(filesize($globals['out']) != 0);

                        self::info("%s: %.04f sec", basename($globals['out']), $etime - $stime);
                        
                        self::assertEquals(self::$_output[$type]['mime'], finfo_file($finfo, $globals['out'], FILEINFO_MIME_TYPE));
                        self::assertEquals(self::$_output[$type]['type'], finfo_file($finfo, $globals['out'], FILEINFO_RAW));

                        // 
                        // Replace timestamp:
                        // 
                        $content = file_get_contents($globals['out']);
                        $content = preg_replace("/CreationDate \(.*?\)/", "CreationDate (D:20141127140623+01'00')", $content);
                        file_put_contents($globals['out'], $content);

                        if (self::$_output['check']) {
                                self::assertEquals(self::$_output[$type]['size'], filesize($globals['out']));
                                self::assertEquals(self::$_output[$type]['md5'], md5_file($globals['out']));
                        }
                        if (self::$_output['unlink'] && file_exists($globals['out'])) {
                                unlink($globals['out']);
                        }
                }

                finfo_close($finfo);
        }

}
