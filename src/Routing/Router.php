<?php

/**
 * Router class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Luthier\Routing\Route as LuthierRoute;
use Luthier\Http\Middleware\MiddlewareInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class Router
{
    /**
     * Allowed HTTP verbs
     */
    const HTTP_VERBS = ['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS','TRACE'];


    /**
     * Route builder context
     *
     * @var static $context
     *
     * @access private
     */
    private static $context = [
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
     * Routes (Luthier Route instances)
     *
     * @var $routes
     *
     * @access protected
     */
    protected $routes = [];


    /**
     * Route names and their sticky parameters
     *
     * @var $names
     *
     * @access protected
     */
    protected $names = [];


    /**
     * Current route
     *
     * @var $currentRoute
     *
     * @access protected
     */
    protected $currentRoute = null;


    /**
     * Symfony RouteCollection object
     *
     * @var $compiledRoutes
     *
     * @access protected
     */
    protected $routeCollection;


    /**
     * Symfony
     *
     * @var $routeGenerator
     *
     * @access protected
     */
    protected $routeGenerator;


    /**
     * Symfony RequestContext
     *
     * @var $requestContext
     *
     * @access protected
     */
    protected $requestContext;


    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct()
    {
        $this->routeCollection = new RouteCollection();
    }


    /**
     * __call() magic method
     *
     * @param  mixed  $callback
     * @param  array  $attributes
     *
     * @return mixed
     *
     * @access public
     */

    public function __call($callback, array $attributes)
    {
        if($callback == 'match')
        {
            $methods = $attributes[0];
        }
        else
        {
            $methods = $callback;
        }

        if($callback == 'cli')
        {
            $route = (new LuthierRoute(['GET'], $attributes))
                ->middleware(new \Luthier\Http\Middleware\CliMiddleware());
        }
        else
        {
            $route = new LuthierRoute($methods, $attributes);
        }

        $this->routes[] = $route;

        return $route;
    }




    /**
     * Compile ALL Luthier routes to Symfony native RouteCollection format
     *
     * @return mixed
     *
     * @access public
     */
    public function compile()
    {
        foreach($this->routes as $i => $_route)
        {
            [$name, $route] = $_route->compile();

            if(empty($name))
            {
                $name = '__unnamed_route_' . str_pad($i ,5, '0', STR_PAD_LEFT);
            }
            else
            {
                if(isset($this->names[$name]))
                {
                    throw new \Exception("Duplicated '$name' route");
                }

                $this->names[$name] = $_route->getStickyParams();
            }

            $this->routeCollection->add($name, $route);
        }

        $this->routeGenerator = new UrlGenerator($this->routeCollection, $this->requestContext);
        return $this->routeCollection;
    }


    /**
     * Creates a new route group
     *
     * @param  mixed     $prefix Route group prefix
     * @param  mixed     $attributes Route group attributes
     * @param  mixed     $routes (Optional)
     *
     * @return mixed
     *
     * @access public
     */
    public function group($prefix, $attributes, $routes = null)
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
            if(!is_array($attributes['middleware']) || !is_string($attributes['middleware']))
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

        //call_user_func_array($routes, [$this]);

        $routes = \Closure::bind($routes, $this, Router::class);
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
     * Define a global middleware callback or alias
     *
     * @param  mixed  $middleware
     *
     * @return mixed
     *
     * @access public
     */
    public function middleware($middleware)
    {
        if( count( func_get_args() ) == 2)
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

            return self::$context['middleware']['alias'][$name] = $middleware;
        }

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


    /**
     * Get defined Luthier Framework routes
     *
     * @return array
     *
     * @access public
     */
    public function getRoutes()
    {
        return $this->routes;
    }


    /**
     * Get ALL Luthier Framework routes compiled into Symfony Router Component route objects
     *
     * @return array
     *
     * @access public
     */
    public function getCompiledRoutes()
    {
        return $this->compiledRoutes;
    }


    /**
     * Get the current route
     *
     * @return LuthierRoute|null
     *
     * @access public
     */
    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }


    /**
     * Get a route by name
     *
     * @param  string       $name
     * @param  array        $args
     * @param  bool         $absoluteUrl
     *
     * @return mixed
     *
     * @access public
     */
    public function getRouteByName(string $name, array $args = [], bool $absoluteUrl = TRUE)
    {
        $route  = $this->currentRoute;

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
     * Get Route Builder context
     *
     * @param  string $context
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function getContext(string $context)
    {
        return self::$context[$context];
    }


    /**
     * Get a middleware callable
     *
     * @param  string|callable $name
     *
     * @return mixed
     *
     * @access public
     * @static
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
     * Set the current route
     *
     * @param  LuthierRoute $route
     *
     * @return mixed
     *
     * @access public
     */
    public function setCurrentRoute(LuthierRoute $route)
    {
        $this->currentRoute = $route;
    }


    /**
     * Set the current request context
     *
     * @param  RequestContext $requestContext
     *
     * @return mixed
     *
     * @access public
     */
    public function setRequestContext(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }
}