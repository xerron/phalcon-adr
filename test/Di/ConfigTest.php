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

/**
 * @covers \Phalcon\Di\Config
 */
class ConfigTest extends TestCase
{
    public function testMergeArrays()
    {
        $config = [
            'services' => [
                'foo' => \PhalconTest\TestAsset\InvokableObject::class,
            ],
            'shared' => [
                'foo' => [
                    \PhalconTest\TestAsset\InvokableObject::class,
                ]
            ],
        ];
        $configuration = new \PhalconTest\TestAsset\ExtendedConfig($config);
        $result = $configuration->toArray();
        $expected = [
            'services' => [
                'foo' => \PhalconTest\TestAsset\InvokableObject::class,
                \PhalconTest\TestAsset\InvokableObject::class => \PhalconTest\TestAsset\InvokableObject::class,
            ],
            'shared' => [
                'foo' => [
                    \PhalconTest\TestAsset\InvokableObject::class,
                    \PhalconTest\TestAsset\InvokableObject::class
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }
    public function testPassesKnownServiceConfigKeysToServiceManagerWithConfigMethod()
    {
        $expected = [
            'services' => [
                'foo' => $this,
            ],
            'shared' => [
                __CLASS__     => true,
                __NAMESPACE__ => false,
            ],
        ];
        $config = $expected + [
                'foo' => 'bar',
                'baz' => 'bat',
            ];
        $services = $this->prophesize(Di::class);
        $services->configure($expected)->willReturn('CALLED');
        $configuration = new Config($config);
        $this->assertEquals('CALLED', $configuration->configureServiceManager($services->reveal()));
        return [
            'array'  => $expected,
            'config' => $configuration,
        ];
    }
    /**
     * @depends testPassesKnownServiceConfigKeysToServiceManagerWithConfigMethod
     */
    public function testToArrayReturnsConfiguration($dependencies)
    {
        $configuration  = $dependencies['array'];
        $configInstance = $dependencies['config'];
        $this->assertSame($configuration, $configInstance->toArray());
    }
}
