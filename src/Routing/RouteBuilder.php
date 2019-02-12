<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Routing;

use Psr\Container\ContainerInterface;
use Luthier\Http\Middleware\AjaxMiddleware;
use Luthier\Http\Middleware\CsrfMiddleware;
use Luthier\Http\Middleware\MiddlewareInterface;
use Luthier\Http\Middleware\ValidationMiddleware;
use Luthier\Routing\Route as LuthierRoute;
use Luthier\Routing\Command as LuthierCommand;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Luthier Framework own abstraction of the Symfony Router component. Offers a easy 
 * and user-friendly way to define new routes by using a few methods:
 * 
 *   - get()
 *   - post()
 *   - patch()
 *   - put()
 *   - options()
 *   - trace()
 *   - head()
 *   - delete()
 *   
 * Multiple HTTP Verbs can be accepted in a route by using the match([]) method
 * 
 * The Route Builder also stores the callbacks what will be invoked when an application
 * error/exception occurs.
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RouteBuilder implements RouteBuilderInterface
{

    const HTTP_VERBS = ['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS','TRACE'];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array $context
     */
    protected static $context = [
        'middleware' => [
            'route'  => [],
            'global' => [],
            'alias'  => []
        ],
        'namespace' => [],
        'prefix'  => [],
        'params'  => [],
        'host'    => [],
        'schemes' => []    
    ];

    /**
     * @var Command[] $commands
     */
    protected $commands = [];

    /**
     * @var Route[] $routes
     */
    protected $routes = [];

    /**
     * @var array $names
     */
    protected $names = [];

    /**
     * @var RouteCollection $routeCollection
     */
    protected $routeCollection;

    /**
     * @var UrlGenerator $routeGenerator
     */
    protected $routeGenerator;

    /**
     * @var RequestContext $requestContext
     */
    protected $requestContext;

    /**
     * HTTP/1.1 404: Not Found error callback
     * 
     * @var callable $httpNotFoundCallback
     */
    protected $httpNotFoundCallback;

    /**
     * HTTP/1.1 405: Method Not Allowed error callback
     *
     * @var callable $httpNotAllowedCallback
     */
    protected $httpMethodNotAllowedCallback;

    /**
     * Error Handler used by the Request Handler (HttpKernel)
     *
     * @var callable $errorCallback
     */
    protected $errorHandler;

    /**
     * @var \Luthier\Routing\Route
     */
    protected $currentRoute;
    
    /**
     * @var bool $isSandboxed
     */
    protected static $isSandboxed = false;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->routeCollection = new RouteCollection();
        $this->requestContext = new RequestContext();
          
        // Built-in middleware
        $this->middleware('ajax', AjaxMiddleware::class);
        $this->middleware('csrf', CsrfMiddleware::class);
        $this->middleware('validation', ValidationMiddleware::class);
    }

    public function __call($callback, array $attributes)
    {
        if ($callback == 'command') {
            [$name,$_callback] = $attributes;

            $command = new LuthierCommand($name, $_callback);

            if (isset($this->commands[$command->getName()])) {
                echo 'ERROR: Duplicated ' . $command->getName() . ' command!' . PHP_EOL;
                exit(- 1);
            }

            $this->commands[$command->getName()] = function () use ($command) {
                return $command->compile();
            };

            return $command;
        } else if (in_array(strtoupper($callback), self::HTTP_VERBS) || in_array($callback, ['match', 'any'])) {

            if ($callback == 'match') {
                $methods = $attributes[0];
            } else {
                $methods = $callback;
            }

            $route = new LuthierRoute($methods, $attributes);
            $this->routes[] = $route;
            return $route;
        } else {
            throw new \BadMethodCallException("Call to undefined method Luthier\RouteBuilder::{$callback}() ");
        }
    }

    /**
     * Creates a new route group
     * 
     * Two syntax are accepted:
     *  
     *    // Only prefix and sub-routes
     *    $app->group('prefix', function(){ ... });
     *   
     *    // Both prefix and shared attributes:
     *    $app->group('prefix, [ 'attrs' ], function(){ ... });
     *   
     *  Inside the sub-routes definition callback, the router instance is binded to
     *  the `$this` variable
     * 
     * @param string $prefix     Group URL prefix
     * @param mixed  $attributes Group shared attributes
     * @param mixed  $routes     Group sub-routes definition callback
     * 
     * @throws \Exception
     * 
     * @return void
     */
    public function group(string $prefix, $attributes, $routes = null)
    {
        if ($routes === null && is_callable($attributes)) {
            $routes = $attributes;
            $attributes = [];
        }

        self::$context['prefix'][] = $prefix;

        if (isset($attributes['namespace'])) {
            self::$context['namespace'][] = $attributes['namespace'];
        }

        if (isset($attributes['schemes'])) {
            self::$context['schemes'][] = $attributes['schemes'];
        }

        if (isset($attributes['middleware'])) {
            if (! is_array($attributes['middleware']) && ! is_string($attributes['middleware'])) {
                throw new \Exception('Route group middleware must be an array o a string');
            }

            if (is_string($attributes['middleware'])) {
                $attributes['middleware'] = [$attributes['middleware']];
            }

            self::$context['middleware']['route'][] = $attributes['middleware'];
        }

        if (isset($attributes['host'])) {
            self::$context['host'] = $attributes['host'];
        }

        $routes = \Closure::bind($routes, $this, RouteBuilder::class);
        $routes();

        array_pop(self::$context['prefix']);

        if (isset($attributes['namespace'])) {
            array_pop(self::$context['namespace']);
        }

        if (isset($attributes['middleware'])) {
            array_pop(self::$context['middleware']['route']);
        }

        if (isset($attributes['schemes'])) {
            array_pop(self::$context['schemes']);
        }

        if (isset($attributes['host'])) {
            self::$context['host'] = NULL;
        }
    }

    /**
     * Defines (or runs) a global middleware 
     * 
     * The following syntax is accepted:
     * 
     *   // Defining a global middlware
     *   
     *   # Closure:
     *   $app->middleware('alias', function(Request $request, Response $response, Closure $next){ ... });
     *   
     *   # Callable:
     *   $app->middleware('alias', 'middleware_callable');
     *   
     *   // Runing a global middleware
     *   
     *   # From alias:
     *   $app->middleware('alias');
     *   
     *   # From a closure:
     *   $app->middleware(function(Request $request, Response $response, Closure $next){ ... });
     *   
     * You can also pass an array of middleware closures/alias to be executed:
     * 
     *   $app->middleware(['foo','bar','baz']);
     * 
     * @param string|callable|array $middleware Middleware to be defined or executed
     * 
     * @throws \InvalidArgumentException
     * 
     * @return void
     */
    public function middleware($middleware)
    {
        if (count(func_get_args()) == 2) {
            [$name,$middleware] = func_get_args();

            if (! is_string($name)) {
                throw new \InvalidArgumentException("The middleware alias must be a string");
            }

            if (! is_callable($middleware) && ! class_exists($middleware)) {
                throw new \InvalidArgumentException("Invalid middleware definition. Must be a valid callback." . (is_string($middleware) ? " (Does the '$middleware' class exists?)" : ''));
            }

            self::$context['middleware']['alias'][$name] = $middleware;
        } else {
            if (! is_array($middleware)) {
                $middleware = [$middleware];
            }
            
            if(self::$isSandboxed)
            {
                $routeMiddleware = isset(self::$context['middleware']['route'][0]) ? self::$context['middleware']['route'][0] : [];
                self::$context['middleware']['route'][0] = !is_array($routeMiddleware) ? $middleware : array_merge($routeMiddleware, $middleware);
                return;
            }

            foreach ($middleware as $_middleware) {
                if (! in_array($_middleware, self::$context['middleware']['global'])) {
                    self::$context['middleware']['global'][] = $_middleware;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Routing\RouteBuilderInterface::getRoutes()
     */
    public function getRoutes(): RouteCollection
    {
        foreach ($this->routes as $i => $luthierRoute) {
            [$name,$route] = $luthierRoute->compile();

            if (empty($name)) {
                $name = '__unnamed_route_' . str_pad($i, 3, '0', STR_PAD_LEFT);
            } else {
                if (isset($this->names[$name])) {
                    throw new \Exception("Duplicated '$name' route");
                }
                $this->names[$name] = $luthierRoute->getStickyParams();
            }

            $this->routeCollection->add($name, $route);
        }

        $this->routeGenerator = new UrlGenerator($this->routeCollection, $this->requestContext);

        return $this->routeCollection;
    }

    /**
     * Gets the defined application commands
     * 
     * @return Command[]
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Gets the current Route Builder context
     * 
     * @param string $name Context index
     * 
     * @return array|array[]
     */
    public static function getContext(string $name)
    {
        return self::$context[$name];
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Routing\RouteBuilderInterface::getRouteByName()
     */
    public function getRouteByName(string $name, array $args = [], bool $absoluteUrl = true): string
    {
        $route = $this->currentRoute;

        if (! isset($this->names[$name])) {
            throw new \Exception("Undefined \"$name\" route");
        }

        foreach ($this->names[$name] as $stickyParam) {
            if ($route->hasParam($stickyParam)) {
                $args[$stickyParam] = $route->param($stickyParam);
            }
        }
   
        $generated = $this->routeGenerator->generate($name, $args, $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : NULL);
        
        // If the APP_URL property is set, we'll generate the URLs based on that
        // value:
        $baseUrl = $this->container->get('APP_URL');
        if (!empty($baseUrl)) {
            $context  = $this->routeGenerator->getContext();
            $offset   = strpos($generated, $context->getBaseUrl());
            $segments = str_ireplace($context->getBaseUrl(), '', substr($generated, $offset));
            return $baseUrl . $segments;
        }
        
        // If not, Symfony will generate the URL for us:
        return $generated;
    }

    /**
     * Gets the current Symfony Routing RequestContext object
     * 
     * @return \Symfony\Component\Routing\RequestContext
     */
    public function getRequestContext()
    {
        return $this->requestContext;
    }

    /**
     * Returns a valid middleware callable from the given parameter
     * 
     * @param callable|string|object $middleware
     * @throws \Exception
     * 
     * @return callable
     */
    public function getMiddleware($middleware)
    {
        if (is_callable($middleware)) {
            if ($middleware instanceof \Closure) {
                $container = $this->container;
                $middleware = \Closure::bind($middleware, $container, ContainerInterface::class);
            }

            return $middleware;
        }

        if (is_string($middleware)) {
            if (isset(self::$context['middleware']['alias'][$middleware])) {
                return self::getMiddleware(self::$context['middleware']['alias'][$middleware]);
            }

            if (class_exists($middleware)) {
                $middleware = new $middleware($this->container);
            } else {
                throw new \Exception("Unknown \"$middleware\" middleware class/alias");
            }
        }

        if (! $middleware instanceof MiddlewareInterface) {
            throw new \Exception('The middleware "' . get_class($middleware) . '" MUST implement the ' . MiddlewareInterface::class . ' interface');
        }

        return function ($request, $response, $next) use ($middleware) {
            return $middleware->run($request, $response, $next);
        };
    }

    /**
     * @param \Closure $callback
     * 
     * @return \Closure
     */
    private function bindContainer(\Closure $callback)
    {
        return \Closure::bind($callback, $this->container, ContainerInterface::class);
    }

    /**
     * Sets the callback to be invoked when a HTTP 404 error occurs
     * 
     * @param callable $callback
     * 
     * @return \Luthier\Routing\RouteBuilder
     */
    public function setHttpNotFoundCallback(\Closure $callback)
    {
        $this->httpNotFoundCallback = $this->bindContainer($callback);
        return $this;
    }

    /**
     * Sets the callback to be invoked when a HTTP 405 error ocurrs
     * 
     * @param callable $callback
     * 
     * @return \Luthier\Routing\RouteBuilder
     */
    public function setMethodHttpNotAllowedCallback(\Closure $callback)
    {
        $this->httpMethodNotAllowedCallback = $this->bindContainer($callback);
        return $this;
    }

    /**
     * Sets the callback to be invoked when a error/exception occurs
     * 
     * @param callable $callback
     * 
     * @return self
     */
    public function setErrorHandler(\Closure $callback)
    {
        $this->errorHandler = $this->bindContainer($callback);
        return $this;
    }

    /**
     * @param LuthierRoute $route
     * 
     * @return self
     */
    public function setCurrentRoute(LuthierRoute $route)
    {
        $this->currentRoute = $route;
        return $this;
    }

    /**
     * Gets the httpNotFoundCallback property
     * 
     * @return callable|null
     */
    public function getHttpNotFoundCallback()
    {
        return $this->httpNotFoundCallback;
    }

    /**
     * Gets the httpMethodNotAllowedCallback property
     *
     * @return callable|null
     */
    public function getHttpMethodNotAllowedCallback()
    {
        return $this->httpMethodNotAllowedCallback;
    }

    /**
     * Gets the Error Handler callback
     *
     * @return callable|null
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /**
     * Gets an array of all middleware registered for current request
     * 
     * @return array
     */
    public function getMiddlewareStack(LuthierRoute $route)
    {
        $routeMiddleware = $route->getMiddleware() ?? [];
        $globalMiddleware = self::$context['middleware']['global'];
        return array_merge($globalMiddleware, $routeMiddleware);
    }

    /**
     * Counts the defined routes
     * 
     * @return int
     */
    public function count()
    {
        return count($this->routes);
    }

    /**
     * Adds an external routing file. The $router variable will be available to define new routes within that file.
     * 
     * @param string  $filename    External file
     * @param string  $prefix      Global prefix
     * @param array   $attributes  Global attributes
     */
    public function addRoutes(string $filename, string $prefix = '', array $attributes = [])
    {
        self::$isSandboxed = true;
        
        $appPath = $this->container->get('APP_PATH');      
        
        (function($router) use($appPath, $filename, $prefix, $attributes){
            $router->group($prefix, $attributes, function() use($router, $appPath, $filename){
                require($appPath . '/' . $filename . (substr($filename, -4) == '.php' ? '' : '.php') );
            });
        })($this);
        
        array_pop(self::$context['middleware']['route']);
        
        self::$isSandboxed = false;
    }
}