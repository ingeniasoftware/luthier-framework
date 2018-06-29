<?php

/**
 * Luthier Framework
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Http\{Request, Response};
use Luthier\Routing\{Router, RouteBuilder};
use Symfony\Component\HttpFoundation\{Request as SfRequest, Response as SfResponse};
use Symfony\Component\HttpKernel;
use Symfony\Component\Routing;

class App
{
    const LUTHIER_VERSION = 1.0;

    /**
     * App instance object
     *
     * @var static $instance
     *
     * @access private
     */
    private static $instance;


    /**
     * Request middleware iterator
     *
     * @var static $requestIterator
     *
     * @access private
     */
    private static $requestIterator;



    /**
     * Next request closure
     *
     * @var static $nextRequest
     *
     * @access private
     */
    private static $nextRequest;


    /**
     * App request object
     *
     * @var $request
     *
     * @access protected
     */
    protected $request;


    /**
     * App response object
     *
     * @var $response
     *
     * @access protected
     */
    protected $response;


    /**
     * App router object
     *
     * @var $router
     *
     * @access protected
     */
    protected $router;


    /**
     * __call() magic method
     *
     * @param  mixed $method
     * @param  mixed $args
     *
     * @return mixed
     *
     * @access public
     * @throw \Exception
     */
    public function __call($method, $args)
    {
        if(in_array(strtoupper($method), RouteBuilder::HTTP_VERBS) || $method == 'match')
        {
            return call_user_func_array([$this->router, $method], $args);
        }
        else if(in_array($method, ['group', 'middleware']))
        {
            return call_user_func_array([$this->router, $method], $args);
        }
        else
        {
            throw new \Exception("Undefined method App::{$method}() ");
        }
    }


    /**
     * __get() magic method
     *
     * @param  mixed  $property
     *
     * @return mixed
     *
     * @access public
     * @throw \Exception
     */
    public function __get($property)
    {
        if(isset($this->{$property}))
        {
            return $this->{$property};
        }
        else if($property == 'route')
        {
            return $this->router->getCurrentRoute();
        }
        else
        {
            throw new \Exception("Undefined property App::$property ");
        }
    }


    /**
     * Get current APP instance
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function getInstance()
    {
        return self::$instance;
    }


    /**
     * Returns the request middleware stack iterator
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function getRequestIterator()
    {
        return self::$requestIterator;
    }


    /**
     * Continue to next middleware, or final request if no more middleware in the request
     * stack
     *
     * @param  mixed $request
     * @param  mixed $response
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function continueRequest($request, $response)
    {
        return call_user_func_array(self::$nextRequest, [$request, $response]);
    }

    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct()
    {
        $this->request  = new Request();
        $this->response = new Response();
        $this->router   = new Router();
        self::$instance = &$this;

        require_once __DIR__ . '/Functions.php';
    }


    /**
     * Run the application and dispatches the response
     *
     * @param  SfRequest $request Custom request object (optional)
     *
     * @return mixed
     *
     * @access public
     */
    public function run(SfRequest $request = null)
    {

        // Base request/response

        $request  = $request === null ? $this->request : $request;
        $response = $this->response;

        try
        {
            // Symfony Router Component url match
            $match = (
                new Routing\Matcher\UrlMatcher(
                    $this->router->getCompiledRoutes(),
                    (new Routing\RequestContext())->fromRequest($request->getSfRequest())
                )
            )->match($request->getSfRequest()->getPathInfo());

            $request->getSfRequest()->attributes->add($match);

            // Founded? Set the current route
            $route = $match['_instance'];
            $this->router->setCurrentRoute($route);

            // Resolve controller/arguments (again, with the Symfony Router Component)
            $controller = (new HttpKernel\Controller\ControllerResolver())
                ->getController($request->getSfRequest());

            $arguments  = (new HttpKernel\Controller\ArgumentResolver())
                ->getArguments($request->getSfRequest(), $controller);

            // Set the router parameter values from current url
            foreach($arguments as $i => $arg)
            {
                if($arg === null)
                {
                    unset($arguments[$i]);
                }
            }

            $paramOffset = 0;

            // Build the request stack array
            foreach( explode('/', trim($request->getSfRequest()->getPathInfo(), '/')) as $i => $urlSegment )
            {
                $routeSegment = explode('/', $route->getFullPath())[$i];
                if(substr($routeSegment,0,1) == '{' && substr($routeSegment,-1) == '}')
                {
                    $route->params[$paramOffset]->value = $urlSegment;
                    $paramOffset++;
                }
            }

            $requestStack = [];

            foreach(RouteBuilder::getContext('middleware')['global'] as $middleware)
            {
                $requestStack[] = $middleware;
            }

            // Set the request stack iterator and next request callback
            self::$requestIterator = function() use($requestStack, $controller, $arguments)
            {
                static $r = 0;
                return isset($requestStack[++$r])
                    ? [ $requestStack[$r] , NULL ]
                    : [ NULL, [$controller, $arguments] ];
            };
            self::$nextRequest = function($request, $response)
            {
                [$middleware, $action] = (\Luthier\App::getRequestIterator())();

                if($action !== NULL)
                {
                    [$controller, $arguments] = $action;
                    return call_user_func_array($controller, $arguments);
                }

                return call_user_func_array( $middleware, [$request, $response, function($request,$response){
                    return \Luthier\App::continueRequest($request, $response);
                }]);
            };

            if(count($requestStack) > 0)
            {
                $responseResult = call_user_func_array($requestStack[0],[$request, $response, function($request,$response){
                    return \Luthier\App::continueRequest($request, $response);
                }]);
            }
            else
            {
                $responseResult = call_user_func_array($controller, $arguments);
            }
        }
        catch(Routing\Exception\ResourceNotFoundException $e)
        {
            return (new SfResponse('Not Found', 404))->send();
        }
        catch(\Exception $e)
        {
            return (new SfResponse('An error occurred', 500))->send();
        }

        // The route returned a Symfony response object? send it

        if($responseResult instanceof SfResponse)
        {
            return $responseResult->send();
        }

        // If not, return the internal built response

        $response->getSfResponse()->send();
    }
}