<?php

/**
 * @see       https://github.com/xerron/phalcon-expressive for the canonical source repository
 * @copyright Copyright (c) 2016-2017 U-w-U Digital Marketing Perú Inc. (http://www.zend.com)
 * @license   https://github.com/xerron/phalcon-expressive/blob/master/LICENSE.md New BSD License
 */
namespace Phalcon\Expressive;

use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\LazyLoader;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Micro\MiddlewareInterface;
use Phalcon\Mvc\Controller;

class Application extends Micro
{
    /**
     * Inject routes from configuration.
     *
     * Introspects the provided configuration for routes to inject in the
     * application instance.
     *
     * The following configuration structure can be used to define routes:
     *
     * <code>
     * return [
     *     'routes' => [
     *         [
     *             'path' => '/path/to/match',
     *             'middleware' => 'Middleware Service Name or Callable',
     *             'allowed_methods' => ['GET', 'POST', 'PATCH'],
     *             'options' => [
     *                 'stuff' => 'to',
     *                 'pass'  => 'to',
     *                 'the'   => 'underlying router',
     *             ],
     *         ],
     *         // etc.
     *     ],
     * ];
     * </code>
     *
     * Each route MUST have a path and middleware key at the minimum.
     *
     * The "allowed_methods" key may be omitted, can be either an array or the
     * value of the Zend\Expressive\Router\Route::HTTP_METHOD_ANY constant; any
     * valid HTTP method token is allowed, which means you can specify custom HTTP
     * methods as well.
     *
     * The "options" key may also be omitted, and its interpretation will be
     * dependent on the underlying router used.
     *
     * @param null|array $config If null, attempts to pull the 'config' service
     *     from the composed container.
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function injectRoutesFromConfig(array $config = null)
    {
        if (null === $config) {
            $config = $this->_dependencyInjector->has('config') ? $this->_dependencyInjector->getShared('config') : [];
        }

        if (!isset($config['routes']) || !is_array($config['routes'])) {
            return;
        }

        /** @var \Phalcon\Expressive\Router $router */
        $router = $this->getSharedService('router');

        foreach ($config['routes'] as $spec) {
            if (!isset($spec['path']) || !isset($spec['middleware'])) {
                continue;
            }

            $methods = 0xff;
            if (isset($spec['allowed_methods'])) {
                $methods = $spec['allowed_methods'];
                if (!is_array($methods)) {
                    throw new Exception\InvalidArgumentException(sprintf(
                        'Allowed HTTP methods for a route must be in form of an array; received "%s"',
                        gettype($methods)
                    ));
                }
            }

            $name = isset($spec['name']) ? $spec['name'] : null;
            //$route = new Route($spec['path'], $spec['middleware'], $methods, $name);
            $route = $router->add($spec['path'], null, $methods);
            $route->setName($name);

            if (isset($spec['options'])) {
                $options = $spec['options'];
                if (!is_array($options)) {
                    throw new Exception\InvalidArgumentException(sprintf(
                        'Route options must be an array; received "%s"',
                        gettype($options)
                    ));
                }

                $route->setOptions($options);
            }

            $this->_handlers[$route->getRouteId()] = $spec['middleware'];
        }
    }

    /**
     * @param null|string $
     */
    public function handle($uri = null)
    {
        $status = null;
        $realHandler = null;
        $dependencyInjector = $this->_dependencyInjector;

        if (!is_object($dependencyInjector)) {
            throw new \Exception("A dependency injection container is required to access required micro services");
        }

        try {

            $returnedValue = null;

            /**
             * Calling beforeHandle routing
             */
            $eventsManager = $this->_eventsManager;
            if (is_object($eventsManager)) {
                if ($eventsManager->fire("micro:beforeHandleRoute", $this) === false) {
                    return false;
                }
            }

            /**
             * Handling routing information
             */
            /** @var \Phalcon\Expressive\Router $router */
            $router = $dependencyInjector->getShared("router");

            /**
             * Handle the URI as normal
             */
            $router->handle($uri);

            /**
             * Check if one route was matched
             */
            $matchedRoute = $router->getMatchedRoute();
            if (is_object($matchedRoute)) {

                $handler = $this->_handlers[$matchedRoute->getRouteId()];

                if (!$handler) {
                    throw new \Exception("Matched route doesn't have an associated handler");
                }

                /**
                 * Updating active handler
                 */
                $this->_activeHandler = $handler;

                /**
                 * Calling beforeExecuteRoute event
                 */
                if (is_object($eventsManager)) {
                    if ($eventsManager->fire("micro:beforeExecuteRoute", $this) === false) {
                        return false;
                    } else {
                        $handler = $this->_activeHandler;
                    }
                }

                $beforeHandlers = $this->_beforeHandlers;
                if (is_array($beforeHandlers)) {

                    $this->_stopped = false;

                    /**
                     * Calls the before handlers
                     */
                    foreach ($beforeHandlers as $before) {

                        if (is_object($before)) {
                            if ($before instanceof MiddlewareInterface) {

                                /**
                                 * Call the middleware
                                 */
                                $status = $before->call($this);

                                /**
                                 * Reload the status
                                 * break the execution if the middleware was stopped
                                 */
                                if ($this->_stopped) {
                                    break;
                                }

                                continue;
                            }
                        }

                        if (!is_callable($before)) {
                            throw new \Exception("'before' handler is not callable");
                        }

                        /**
                         * Call the before handler, if it returns false exit
                         */
                        if (call_user_func($before) === false) {
                            return false;
                        }

                        /**
                         * Reload the 'stopped' status
                         */
                        if ($this->_stopped) {
                            return $status;
                        }
                    }
                }

                $params = $router->getParams();

                $modelBinder = $this->_modelBinder;

                /**
                 * Bound the app to the handler
                 */
                if (is_object($handler) && ($handler instanceof \Closure)) {
                    $handler = \Closure::bind($handler, $this);
                    if ($modelBinder != null) {
                        $routeName = $matchedRoute->getName();
                        if ($routeName != null) {
                            $bindCacheKey = "_PHMB_".$routeName;
                        } else {
                            $bindCacheKey = "_PHMB_".$matchedRoute->getPattern();
                        }
                        $params = $modelBinder->bindToHandler($handler, $params, $bindCacheKey);
                    }
                }

                /**
                 * Calling the Handler in the PHP userland
                 */

                if (is_array($handler)) {

                    $realHandler = $handler[0];

                    if ($realHandler instanceof Controller && $modelBinder != null) {
                        $methodName = $handler[1];
                        $bindCacheKey = "_PHMB_".get_class($realHandler)."_".$methodName;
                        $params = $modelBinder->bindToHandler($realHandler, $params, $bindCacheKey, $methodName);
                    }
                }

                /**
                 * Instead of double call_user_func_array when lazy loading we will just call method
                 */
                if ($realHandler != null && $realHandler instanceof LazyLoader) {
                    $methodName = $handler[1];
                    /**
                     * There is seg fault if we try set directly value of method to returnedValue
                     */
                    $lazyReturned = $realHandler->callMethod($methodName, $params, $modelBinder);
                    $returnedValue = $lazyReturned;
                } else {
                    // not work functions global. but work class with __invoke()
                    $returnedValue = call_user_func_array( new $handler, $params);
                }

                /**
                 * Calling afterBinding event
                 */
                if (is_object($eventsManager)) {
                    if ($eventsManager->fire("micro:afterBinding", $this) === false) {
                        return false;
                    }
                }

                $afterBindingHandlers = $this->_afterBindingHandlers;
                if (is_array($afterBindingHandlers)) {
                    $this->_stopped = false;

                    /**
                     * Calls the after binding handlers
                     */
                    foreach ($afterBindingHandlers as $afterBinding) {

                        if (is_object($afterBinding) && $afterBinding instanceof MiddlewareInterface) {

                            /**
                             * Call the middleware
                             */
                            $status = $afterBinding->call($this);

                            /**
                             * Reload the status
                             * break the execution if the middleware was stopped
                             */
                            if ($this->_stopped) {
                                break;
                            }

                            continue;
                        }

                        if (!is_callable($afterBinding)) {
                            throw new \Exception("'afterBinding' handler is not callable");
                        }

                        /**
                         * Call the afterBinding handler, if it returns false exit
                         */
                        if (call_user_func($afterBinding) === false) {
                            return false;
                        }

                        /**
                         * Reload the 'stopped' status
                         */
                        if ($this->_stopped) {
                            return $status;
                        }
                    }
                }

                /**
                 * Update the returned value
                 */
                $this->_returnedValue = $returnedValue;

                /**
                 * Calling afterExecuteRoute event
                 */
                if (is_object($eventsManager)) {
                    $eventsManager->fire("micro:afterExecuteRoute", $this);
                }

                $afterHandlers = $this->_afterHandlers;
                if (is_array($afterHandlers)) {

                    $this->_stopped = false;

                    /**
                     * Calls the after handlers
                     */
                    foreach ($afterHandlers as $after) {

                        if (is_object($after)) {
                            if ($after instanceof MiddlewareInterface) {

                                /**
                                 * Call the middleware
                                 */
                                $status = $after->call($this);

                                /**
                                 * break the execution if the middleware was stopped
                                 */
                                if ($this->_stopped) {
                                    break;
                                }

                                continue;
                            }
                        }

                        if (!is_callable($after)) {
                            throw new \Exception("One of the 'after' handlers is not callable");
                        }

                        $status = call_user_func($after);
                    }
                }

            } else {

                /**
                 * Calling beforeNotFound event
                 */
                $eventsManager = $this->_eventsManager;
                if (is_object($eventsManager)) {
                    if ($eventsManager->fire("micro:beforeNotFound", $this) === false) {
                        return false;
                    }
                }

                /**
                 * Check if a notfoundhandler is defined and it's callable
                 */
                $notFoundHandler = $this->_notFoundHandler;
                if (!is_callable($notFoundHandler)) {
                    throw new \Exception("Not-Found handler is not callable or is not defined");
                }

                /**
                 * Call the Not-Found handler
                 */
                $returnedValue = call_user_func($notFoundHandler);
            }

            /**
             * Calling afterHandleRoute event
             */
            if (is_object($eventsManager)) {
                $eventsManager->fire("micro:afterHandleRoute", $this, $returnedValue);
            }

            $finishHandlers = $this->_finishHandlers;

            if (is_array($finishHandlers)) {

                $this->_stopped = false;

                $params = null;

                /**
                 * Calls the finish handlers
                 */
                foreach ($finishHandlers as $finish) {

                    /**
                     * Try to execute middleware as plugins
                     */
                    if (is_object($finish)) {

                        if ($finish instanceof MiddlewareInterface) {

                            /**
                             * Call the middleware
                             */
                            $status = $finish->call($this);

                            /**
                             * break the execution if the middleware was stopped
                             */
                            if ($this->_stopped) {
                                break;
                            }

                            continue;
                        }
                    }

                    if (!is_callable($finish)) {
                        throw new \Exception("One of the 'finish' handlers is not callable");
                    }

                    if ($params === null) {
                        $params = [$this];
                    }

                    /**
                     * Call the 'finish' middleware
                     */
                    $status = call_user_func_array($finish, $params);

                    /**
                     * break the execution if the middleware was stopped
                     */
                    if ($this->_stopped) {
                        break;
                    }
                }
            }

        } catch (\Exception $e) {

            /**
             * Calling beforeNotFound event
             */
            $eventsManager = $this->_eventsManager;
            if (is_object($eventsManager)) {
                $returnedValue = $eventsManager->fire("micro:beforeException", $this, $e);
            }

            /**
             * Check if an errorhandler is defined and it's callable
             */
            $errorHandler = $this->_errorHandler;

            if ($errorHandler) {

                if (!is_callable($errorHandler)) {
                    throw new \Exception("Error handler is not callable");
                }

                /**
                 * Call the Error handler
                 */
                $returnedValue = call_user_func_array($errorHandler, [$e]);
                if (is_object($returnedValue)) {
                    if (!($returnedValue instanceof ResponseInterface)) {
                        throw $e;
                    }
                } else {
                    if ($returnedValue !== false) {
                        throw $e;
                    }
                }

            } else {
                if ($returnedValue !== false) {
                    throw $e;
                }
            }
        }

        /**
         * Check if the returned value is a string and take it as response body
         */
        if (is_string($returnedValue)) {
            $response = $dependencyInjector->getShared("response");
            $response->setContent($returnedValue);
            $response->send();
        }

        /**
         * Check if the returned object is already a response
         */
        if (is_object($returnedValue)) {
            if ($returnedValue instanceof ResponseInterface) {
                /**
                 * Automatically send the response
                 */
                $returnedValue->send();
            }
        }

        return $returnedValue;
    }

}