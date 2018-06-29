<?php

/**
 * RouteBuilder class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Luthier\Routing\Router;

class RouteBuilder
{
    const HTTP_VERBS = ['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS','TRACE'];


    /**
     * Route context array
     *
     * @var static $context
     *
     * @access private
     */
    private static $context = [
        'middleware' => [
            'route'  => [],
            'global' => [],
        ],
        'namespace' => [],
        'prefix'    => [],
        'params'    => [],
        'host'      => [],
        'schemes'   => [],
    ];


    /**
     * __callStatic() magic method
     *
     * @param  mixed  $callback
     * @param  array  $args
     *
     * @return mixed
     *
     * @access public
     * @static
     */
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



    /**
     * Creates a new route group
     *
     * @param  ?Router   $router Current router instance
     * @param  mixed     $prefix Route group prefix
     * @param  mixed     $attributes Route group attributes
     * @param  mixed     $routes (Optional)
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function group(?Router $router, $prefix, $attributes, $routes = null)
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

        $routeCallbackArray = $router === null ? [] : [$router];

        call_user_func_array($routes, $routeCallbackArray);

        array_pop(self::$context['prefix']);

        if(isset($attributes['namespace']))
        {
            array_pop(self::$context['namespace']);
        }

        if(isset($attributes['middleware']))
        {
            array_pop(self::$context['middleware']['route']);
        }

        // FIXME: Schemes set bug with array (Warning: strtolower() expects parameter 1 to be string)

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
     * Define a global middleware
     *
     * @param  mixed        $middleware
     * @param  mixed        $point (Optional)
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function middleware($middleware)
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


    /**
     * Get RouteBuilder context var
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

}