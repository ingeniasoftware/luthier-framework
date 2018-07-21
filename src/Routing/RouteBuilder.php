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
use Luthier\Http\Request;
use Luthier\Http\Response;
use Luthier\Http\Middleware\AjaxMiddleware;
use Luthier\Http\Middleware\MiddlewareInterface;
use Luthier\Routing\Route as LuthierRoute;
use Luthier\Routing\Command as LuthierCommand;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use function Whoops\Util\ob_start;

/**
 * Abstraction of the Symfony Router component. Contains methods to defining routes,
 * for example:
 *   * get()
 *   * post()
 *   * patch()
 *   * put()
 *   * options()
 *   * trace()
 *   * head()
 *   * delete()
 *   
 * Multiple HTTP Verbs can be accepted in a route by using the match([]) method
 * 
 * The Route Builder also stores the callbacks what will be invoked when an application
 * error/exception occurs.
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RouteBuilder
{
    const HTTP_VERBS = ['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS','TRACE'];

    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * Route builder context
     *
     * @var array $context
     */
    protected static $context = [
        'middleware' => [
            'route'  => [],
            'global' => [],
            'alias'  => [],
        ],
        'namespace' => [],
        'prefix'    => [],
        'params'    => [],
        'host'      => [],
        'schemes'   => [],
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
     * @var Route $currentRoute
     */
    protected $currentRoute = null;
    
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
     * Callback to be invoked when a HTTP 404 error occurs
     * 
     * @var callable $httpNotFoundCallback
     */
    protected $httpNotFoundCallback;

    /**
     * Callback to be invoked when a HTTP 405 error occurs
     *
     * @var callable $httpNotAllowedCallback
     */
    protected $httpMethodNotAllowedCallback;

    /**
     * Callback to be invoked when a general error/exception occurs
     *
     * @var callable $errorCallback
     */
    protected $errorCallback;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->routeCollection = new RouteCollection();
        $this->requestContext  = new RequestContext();
        $this->middleware('ajax', AjaxMiddleware::class);
        
        // Default HTTP "Not found" error callback
        $this->httpNotFoundCallback = \Closure::bind(function(Request $request, Response $response, \Exception $exception)
        {
            if($this->get('APP_ENV') == 'development')
            {
                throw $exception;
            }
            
            \ob_start();
            require __DIR__ . '/../Resources/Views/HttpNotFoundError.php';
            $notFoundResponse = \ob_get_clean();
            return new SfResponse($notFoundResponse, 404);
        }, $container, ContainerInterface::class);
        
        // Default HTTP "Method not allowed" error callback
        $this->httpMethodNotAllowedCallback = \Closure::bind(function(Request $request, Response $response, ?LuthierRoute $route, \Exception $exception)
        {
            if($this->get('APP_ENV') == 'development')
            {
                throw $exception;
            }
            
            \ob_start();
            require __DIR__ . '/../Resources/Views/HttpNotAllowedError.php';
            $methodNotAllowedResponse = \ob_get_clean();
            return new SfResponse($methodNotAllowedResponse, 405);
        }, $container, ContainerInterface::class);
        
        // Default error/exception callback
        $this->errorCallback = \Closure::bind(function(Request $request, Response $response, ?LuthierRoute $route, \Exception $exception)
        {
            if($this->get('APP_ENV') == 'development')
            {
                throw $exception;
            }

            \ob_start();
            require __DIR__ . '/../Resources/Views/Error.php';
            $methodNotAllowedResponse = \ob_get_clean();
            return new SfResponse($methodNotAllowedResponse, 500);
        }, $container, ContainerInterface::class);
    }

    public function __call($callback, array $attributes)
    {
        if($callback == 'command')
        {
            [$name, $_callback] = $attributes;

            $command = new LuthierCommand($name, $_callback);

            if(isset($this->commands[$command->getName()]))
            {
                echo 'ERROR: Duplicated ' . $command->getName() . ' command!' . PHP_EOL;
                exit(-1);
            }

            $this->commands[$command->getName()] = function() use($command){
                return $command->compile();
            };

            return $command;
        }
        else if(in_array(strtoupper($callback), self::HTTP_VERBS) || in_array($callback, ['match']))
        {
            if($this->currentRoute !== NULL)
            {
                throw new \Exception("You may not define more routes after the first route has been dispatched");
            }

            if($callback == 'match')
            {
                $methods = $attributes[0];
            }
            else
            {
                $methods = $callback;
            }

            $route = new LuthierRoute($methods, $attributes);
            $this->routes[] = $route;
            return $route;
        }
        else
        {
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
     * @throws \Exception
     * 
     * @return void
     */
    public function group(string $prefix, $attributes, $routes = null)
    {
        if($routes === null && is_callable($attributes))
        {
            $routes     = $attributes;
            $attributes = [];
        }

        self::$context['prefix'][] = $prefix;

        if(isset($attributes['namespace']))
        {
            self::$context['namespace'][] = $attributes['namespace'];
        }

        if(isset($attributes['schemes']))
        {
            self::$context['schemes'][] = $attributes['schemes'];
        }

        if(isset($attributes['middleware']))
        {
            if(!is_array($attributes['middleware']) && !is_string($attributes['middleware']))
            {
                throw new \Exception('Route group middleware must be an array o a string');
            }

            if(is_string($attributes['middleware']))
            {
                $attributes['middleware'] = [ $attributes['middleware'] ];
            }

            self::$context['middleware']['route'][] = $attributes['middleware'];
        }

        if(isset($attributes['host']))
        {
            self::$context['host'] = $attributes['host'];
        }

        $routes = \Closure::bind($routes, $this, RouteBuilder::class);
        $routes();

        array_pop(self::$context['prefix']);

        if(isset($attributes['namespace']))
        {
            array_pop(self::$context['namespace']);
        }

        if(isset($attributes['middleware']))
        {
            array_pop(self::$context['middleware']['route']);
        }

        if(isset($attributes['schemes']))
        {
            array_pop(self::$context['schemes']);
        }

        if(isset($attributes['host']))
        {
            self::$context['host'] = NULL;
        }
    }  
    
    /**
     * Defines (or runs) a global middleware 
     * 
     * The following syntaxes are accepted:
     * 
     *   // Defining a global middlware
     *   
     *   # Closure
     *   $app->middleware('alias', function(Request $request, Response $response, Closure $next){ ... });
     *   # Callable
     *   $app->middleware('alias', 'middleware_callable');
     *   
     *   // Runing a global middleware
     *   
     *   # From alias
     *   $app->middleware('alias');
     *   # From a closure
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
        if(count(func_get_args()) == 2)
        {
            [$name, $middleware] = func_get_args();

            if(!is_string($name))
            {
                throw new \InvalidArgumentException("The middleware alias must be a string");
            }

            if(!is_callable($middleware) && !class_exists($middleware))
            {
                throw new \InvalidArgumentException("Invalid middleware definition. Must be a valid callback." . (is_string($middleware) ? " (Does the '$middleware' class exists?)" : ''));
            }

            self::$context['middleware']['alias'][$name] = $middleware;
        }
        else 
        {
            if(!is_array($middleware))
            {
                $middleware = [ $middleware ];
            }
            
            foreach($middleware as $_middleware)
            {
                if(!in_array($_middleware, self::$context['middleware']['global']))
                {
                    self::$context['middleware']['global'][] = $_middleware;
                }
            }
        }
    }
    
    /**
     * Returns all Luthier Routes compiled to a Symfony RouteCollection object 
     * 
     * @throws \Exception
     * 
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRoutes()
    {
        foreach($this->routes as $i => $route)
        {
            [$name, $route] = $route->compile();

            if(empty($name))
            {
                $name = '__unnamed_route_' . str_pad($i ,3, '0', STR_PAD_LEFT);
            }
            else
            {
                if(isset($this->names[$name]))
                {
                    throw new \Exception("Duplicated '$name' route");
                }
                $this->names[$name] = $route->getStickyParams();
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
        return $this->commands();
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
     * Gets a route URL by its name, or throws an exception if an undefined route was requested
     * 
     * @param string $name        Route name
     * @param array  $args        Route parameters (if any)
     * @param bool   $absoluteUrl Build an absolute url
     * @throws \Exception
     * 
     * @return string
     */
    public function getRouteByName(string $name, array $args = [], bool $absoluteUrl = TRUE)
    {
        $route = $this->currentRoute;

        if(!isset($this->names[$name]))
        {
            throw new \Exception("Undefined \"$name\" route");
        }

        foreach($this->names[$name] as $stickyParam)
        {
            if($route->hasParam($stickyParam))
            {
                $args[$stickyParam] = $route->param($stickyParam);
            }
        }

        return $this->routeGenerator->generate(
            $name,
            $args,
            $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : NULL
        );
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
    public static function getMiddleware($middleware) : callable
    {
        if(is_callable($middleware))
        {
            return $middleware;
        }

        if(is_string($middleware))
        {
            if(isset(self::$context['middleware']['alias'][$middleware]))
            {
                return self::getMiddleware(self::$context['middleware']['alias'][$middleware]);
            }

            if(class_exists($middleware))
            {
                $middleware = new $middleware();
            }
            else
            {
                throw new \Exception("Unknown \"$middleware\" middleware class/alias");
            }
        }

        if(!$middleware instanceof MiddlewareInterface)
        {
            throw new \Exception('The middleware "' . get_class($middleware) . '" MUST implement the '. MiddlewareInterface::class . ' interface' );
        }

        return function($request, $response, $next) use($middleware)
        {
            return $middleware->run($request, $response, $next);
        };
    }

    /**
     * Sets the callback to be invoked when a HTTP 404 error occurs
     * 
     * @param callable $callback
     * 
     * @return \Luthier\Routing\RouteBuilder
     */
    public function setHttpNotFoundCallback(callable $callback)
    {
        $this->httpNotFoundCallback = $callback;       
        return $this;
    }

    /**
     * Sets the callback to be invoked when a HTTP 405 error ocurrs
     * 
     * @param callable $callback
     * 
     * @return \Luthier\Routing\RouteBuilder
     */
    public function setMethodHttpNotAllowedCallback(callable $callback)
    {
        $this->httpMethodNotAllowedCallback = $callback;
        return $this;
    }

    /**
     * Sets the callback to be invoked when a error/exception occurs
     * 
     * @param callable $callback
     * 
     * @return \Luthier\Routing\RouteBuilder
     */
    public function setErrorCallback(callable $callback)
    {
        $this->errorCallback = $callback;
        return $this;
    }
    
    /**
     * Gets the httpNotFoundCallback property
     * 
     * @return callable
     */
    public function getHttpNotFoundCallback()
    {
        return $this->httpNotFoundCallback;
    }
    
    /**
     * Gets the httpMethodNotAllowedCallback property
     *
     * @return callable
     */
    public function getHttpMethodNotAllowedCallback()
    {
        return $this->httpMethodNotAllowedCallback;
    }
    
    /**
     * Gets the errorCallback property
     *
     * @return callable
     */
    public function getErrorCallback()
    {
        return $this->errorCallback;
    }
    
    /**
     * Gets an array of all middleware registered for current request
     * 
     * @return array
     */
    public function getMiddlewareStack(LuthierRoute $route)
    {
        $routeMiddleware  = $route->getMiddleware() ?? [];
        $globalMiddleware = self::$context['middleware']['global'];
        return array_merge($routeMiddleware,$globalMiddleware);
    }
    
    /**
     * Gets the Luthier welcome screen 
     * 
     * (This screen is displayed when no routes are defined in the application
     * as default response)
     * 
     * @param string $message
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getWelcomeScreenResponse(string $message = null)
    {
        if($this->container->get('APP_ENV') == 'production')
        {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        
        \ob_start();
        require __DIR__ . '/../Resources/Views/About.php';
        $methodNotAllowedResponse = \ob_get_clean();
        return new SfResponse($methodNotAllowedResponse);
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
}