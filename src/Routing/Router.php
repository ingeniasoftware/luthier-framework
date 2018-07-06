<?php

/**
 * Router class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Luthier\Http\{Request, Response, ResponseIterator};
use Luthier\Routing\{Route as LuthierRoute, Command as LuthierCommand};
use Luthier\Http\Middleware\{AjaxMiddleware,MiddlewareInterface};
use Symfony\Component\Routing\Generator\{UrlGenerator, UrlGeneratorInterface};
use Symfony\Component\Routing\{Route, RouteCollection, RequestContext};
use Symfony\Component\HttpKernel;
use Symfony\Component\HttpFoundation\{Request as SfRequest, Response as SfResponse};
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Routing;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * Commands (Luthier Command objects)
     *
     * @var $commands
     *
     * @access protected
     */
    protected $commands = [];


    /**
     * Routes (Luthier Route objects)
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
        else
        {
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
            throw new \BadMethodCallException("Call to undefined method Luthier\Router::{$method}() ");
        }

    }


    /**
     * Compile ALL Luthier routes to Symfony native RouteCollection format
     *
     * @return mixed
     *
     * @access public
     */
    public function compileRoutes()
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


    /**
     * Handle request
     *
     * @param  Request   $request
     * @param  Response  $response
     *
     * @return mixed
     *
     * @access public
     */
    public function handle(Request $request, Response $response)
    {
        if(!$request->isCli())
        {
            $this->http($request, $response);
        }
        else
        {
            $this->cli($request);
        }
    }


    /**
     * Handle an http request
     *
     * @param  Request      $request
     * @param  Response     $response
     *
     * @return mixed
     *
     * @access private
     */
    private function http(Request $request, Response $response)
    {
        if(empty($this->routes))
        {
            ob_start();
            require __DIR__ . '/../Resources/About.php';
            $content = ob_get_clean();
            $response->getResponse()->setContent($content)->send();
            exit(1);
        }

        $context = new Routing\RequestContext();

        $this->setRequestContext($context);
        $this->middleware('ajax', AjaxMiddleware::class);

        try
        {
            // Matching the current url to a route and setting up their attributes
            $match = (
                new Routing\Matcher\UrlMatcher(
                    $this->compileRoutes(),
                    $context->fromRequest($request->getRequest())
                )
            )->match($request->getRequest()->getPathInfo());

            $request->getRequest()->attributes->add($match);

            $controller = (new HttpKernel\Controller\ControllerResolver())
                ->getController($request->getRequest());

            $arguments = (new HttpKernel\Controller\ArgumentResolver())
                ->getArguments($request->getRequest(), $controller);

            // Removing NULL arguments used in the callback to allow default arguments
            // values in the route definitions
            foreach($arguments as $i => $arg)
            {
                if($arg === null)
                {
                    unset($arguments[$i]);
                }
            }

            $route = $match['_instance'];

            // Now we assign the matched route parameters values from the url
            $offset = 0;

            foreach( explode('/', trim($request->getRequest()->getPathInfo(), '/')) as $i => $urlSegment )
            {
                $routeSegment = explode('/', $route->getFullPath())[$i];
                if(substr($routeSegment,0,1) == '{' && substr($routeSegment,-1) == '}')
                {
                    $route->params[$offset]->value = $urlSegment;
                    $offset++;
                }
            }

            $this->setCurrentRoute($route);

            $responseIterator = new ResponseIterator($request, $response, $route, $controller, $arguments);
            $responseResult = $responseIterator->dispatch();
        }
        catch(ResourceNotFoundException|NotFoundHttpException $e)
        {
            if(getenv('APP_ENV') == 'development')
            {
                throw $e;
            }
            return (new SfResponse('Not Found', 404))->send();
        }
        catch(\Exception $e)
        {
            if(getenv('APP_ENV') == 'development')
            {
                throw $e;
            }
            return (new SfResponse('An error occurred', 500))->send();
        }

        if(!$responseResult instanceof SfResponse)
        {
            $responseResult = $response->getResponse();
        }

        // ...finally, send the response:
        $responseResult->send();
    }



    /**
     * Handle a CLI request
     *
     * @param  Request  $request
     *
     * @return mixed
     *
     * @access private
     */
    private function cli(Request $request)
    {
        $cli = new FactoryCommandLoader($this->commands);
        $app = new Application();
        $app->setCommandLoader($cli);
        $app->run();
    }

}