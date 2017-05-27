<?php

/**
 * @see       https://github.com/xerron/phalcon-expressive for the canonical source repository
 * @copyright Copyright (c) 2016-2017 U-w-U Digital Marketing Perú Inc. (http://www.u-w-u.com)
 * @license   https://github.com/xerron/phalcon-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Phalcon\Di;


class Di extends \Phalcon\Di
{

    /**
     * Configure the service di
     *
     * Valid top keys are:
     *
     * - services: service name => service instance pairs
     * - shared: service name => flag pairs; the flag is a boolean indicating
     *   whether or not the service is shared.
     *
     * @param  array $config
     * @return self
     */
    public function configure(array $config)
    {
        if (isset($config['services'])) {
            foreach ($config['services'] as $name => $definition) {
                $this->set($name, $definition);
            }
        }

        if (isset($config['shared'])) {
            foreach ($config['shared'] as $name => $definition) {
                $this->setShared($name, $definition);
            }
        }

        return $this;
    }

}