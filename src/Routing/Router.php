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
    protected $routes = [];

    protected $currentRoute = null;

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

    public function getRoutes()
    {
        return $this->routes;
    }

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

    public function setCurrentRoute(LuthierRoute $route)
    {
        $this->currentRoute = $route;
        return $this;
    }

    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

}