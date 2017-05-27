<?php

/**
 * @see       https://github.com/xerron/phalcon-expressive for the canonical source repository
 * @copyright Copyright (c) 2016-2017 U-w-U Digital Marketing Perú Inc. (http://www.u-w-u.com)
 * @license   https://github.com/xerron/phalcon-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Phalcon\Expressive;

use Phalcon\Expressive\Router\Route;

class Router extends \Phalcon\Mvc\Router
{
    /**
     * {@inheritdoc}
     */
    public function add($pattern, $paths = null, $httpMethods = null, $position = Router::POSITION_LAST)
    {
        $route = new Route($pattern, $paths, $httpMethods);

        switch ($position) {
            case parent::POSITION_LAST:
                $this->_routes[] = $route;
                break;
            case parent::POSITION_FIRST:
                $this->_routes = array_merge([$route], $this->_routes);
                break;
            default:
                throw new \Exception("Invalid route position");
        }

        return $route;
    }
}
