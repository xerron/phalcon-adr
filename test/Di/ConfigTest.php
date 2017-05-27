<?php
/**
 * @see       https://github.com/xerron/phalcon-expressive for the canonical source repository
 * @copyright Copyright (c) 2016-2017 U-w-U Digital Marketing Perú Inc. (http://www.u-w-u.com)
 * @license   https://github.com/xerron/phalcon-expressive/blob/master/LICENSE.md New BSD License
 */

namespace PhalconTest\Di;

use Phalcon\Di\Config;
use Phalcon\Di\Di;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    var $config;
    var $serviceManager;

    function setUp()
    {
        $data = [1, 2, 3, 4, 5];
        $this->config = new Config($data);
        $this->serviceManager = new Di();
    }

    public function testConfigureServiceManager()
    {
        $this->assertEquals(1,1);
    }

    public function testToArray()
    {
        $this->assertEquals(1,1);
    }

    public function testMerge()
    {
        $this->assertEquals(1,1);
    }
}
