<?php

/**
 * Router class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Router
{
    protected $routes = [];

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
        $i = 0;

        foreach($this->routes as $route)
        {
            [$name, $route] = $route->compile();

            if(empty($name))
            {
                $name = '__anonymous_route' . $i;
                $i++;
            }
            $routes->add($name, $route);
        }

        return $routes;
    }

}