<?php
/**
 * @see       https://github.com/xerron/phalcon-expressive for the canonical source repository
 * @copyright Copyright (c) 2016-2017 U-w-U Digital Marketing Perú Inc. (http://www.u-w-u.com)
 * @license   https://github.com/xerron/phalcon-expressive/blob/master/LICENSE.md New BSD License
 */

namespace PhalconTest\TestAsset;

use Phalcon\Di\Config;

class ExtendedConfig extends Config
{
    protected $config = [
        'services' => [
            InvokableObject::class => InvokableObject::class,
        ],
        'shared' => [
            'foo' => [
                InvokableObject::class,
            ],
        ],
    ];
}