<?php

/**
 * RouteBuilder class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Luthier\Exception\RouteNotFoundException;

class RouteBuilder
{
    const HTTP_VERBS = ['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS','TRACE'];

    private static $context = [
        'middleware' => [
            'route'  => [],
            'global' => [
                'pre_controller'  => [],
                'controller'      => [],
                'post_controller' => [],
            ],
        ],
        'namespace' => [],
        'prefix'    => [],
        'params'    => [],
        'host'      => [],
        'schemes'   => [],
    ];


    public static function __callStatic($callback, array $args)
    {
        if($callback == 'match')
        {
            $methods = $args[0];
        }
        else
        {
            $methods = $callback;
        }

        return new Route($methods, $args);
    }


    public static function group($router, $prefix, $attributes, $routes = null)
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

        $routes($router);

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


    public static function middleware($middleware, $point = 'pre_controller')
    {
        if(!is_array($middleware))
        {
            $middleware = [ $middleware ];
        }

        foreach($middleware as $_middleware)
        {
            if(!in_array($_middleware, self::$context['middleware']['global'][$point]))
            {
                self::$context['middleware']['global'][$point][] = $_middleware;
            }
        }
    }


    public static function getContext($context)
    {
        return self::$context[$context];
    }

}