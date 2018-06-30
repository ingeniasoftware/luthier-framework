<?php

/**
 * Router class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Luthier\Routing\Route as LuthierRoute;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class Router
{

    /**
     * Defined Luthier Framework routes
     *
     * @var $routes
     *
     * @access protected
     */
    protected $routes = [
        'objects' => [],
        'names'   => []
    ];


    /**
     * Compiled routes
     *
     * @var $compiledRoutes
     *
     * @access protected
     */
    protected $compiledRoutes;


    /**
     * Current route
     *
     * @var $currentRoute
     *
     * @access protected
     */
    protected $currentRoute = null;



    /**
     * Symfony route generator
     *
     * @var $routeGenerator
     *
     * @access protected
     */
    protected $routeGenerator;


    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct()
    {
        $this->compiledRoutes = new RouteCollection();
    }


    /**
     * __call() magic method
     *
     * @param  mixed   $method
     * @param  array   $args
     *
     * @return mixed
     *
     * @access public
     */
    public function __call($method, array $args)
    {
        if( $method === 'group' )
        {
            array_unshift($args, $this);
        }

        $route = call_user_func_array([RouteBuilder::class, $method], $args);

        if($route instanceof \Luthier\Routing\Route)
        {
            $this->routes['objects'][] = $route;
            return $route;
        }
    }


    /**
     * __invoke() magic method
     *
     * @return mixed
     *
     * @access public
     */
    public function __invoke(RequestContext $requestContext)
    {
        foreach($this->routes['objects'] as $i => $luthierRoute)
        {
            [$name, $route] = $luthierRoute->compile();

            if(empty($name))
            {
                $name = '__anonymous_route' . str_pad($i,4,'0',STR_PAD_LEFT);
            }
            else
            {
                if(isset($this->routes['names'][$name]))
                {
                    throw new \Exception("Duplicated '{$luthierRoute->getName()}' route");
                }

                $this->routes['names'][$name] = $luthierRoute->getStickyParams();
            }

            $this->compiledRoutes->add($name, $route);
        }

       $this->routeGenerator = new UrlGenerator($this->compiledRoutes, $requestContext);

        return $this->compiledRoutes;
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

        if(!isset($this->routes['names'][$name]))
        {
            throw new \Exception("Undefined \"$name\" route");
        }

        foreach($this->routes['names'][$name] as $stickyParam)
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
     * Run middleware
     *
     * @return mixed
     *
     * @access public
     */
    public function runMiddleware($name, $request, $response, $next)
    {
        return call_user_func_array(RouteBuilder::getMiddleware($name), [$request, $response, $next]);
    }
}