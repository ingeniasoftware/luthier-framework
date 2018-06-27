<?php

/**
 * Router class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Luthier\Routing\Route as LuthierRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{

    /**
     * Defined Luthier Framework routes
     *
     * @var $routes
     *
     * @access protected
     */
    protected $routes = [];


    /**
     * Current route
     *
     * @var $currentRoute
     *
     * @access protected
     */
    protected $currentRoute = null;


    /**
     * __call() magic method
     *
     * @param  mixed        $method
     * @param  array        $args
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
            $this->routes[] = $route;
            return $route;
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
        $routes = new RouteCollection();

        foreach($this->routes as $i => $route)
        {
            [$name, $route] = $route->compile();

            if(empty($name))
            {
                $name = '__anonymous_route' . $i;
            }

            $routes->add($name, $route);
        }

        return $routes;
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

}