<?php

/**
 * Route class
 *
 * @copyright 2017 Ingenia Software C.A
 * @author Anderson Salas
 *
 */

namespace Luthier;

use \Symfony\Component\Routing;

class Route
{

    private static $http_verbs = ['get', 'post', 'put', 'patch', 'delete', 'options', 'trace', 'connect', 'head'];

    private static $routes = [];

    private static $middleware = [];

    private static $namespace = [];

    private static $prefix = [];

    public static function __callStatic($verb, $args)
    {
        if(in_array(strtolower($verb), self::$http_verbs))
        {
            $path = $args[0];
            $attr = array_slice($args,1);

            self::add($verb, $path, $attr[0]);
        }
        else
        {
            throw new \Exception('Call to undefined method Route::'.$verb);
        }
    }


    /**
     * Add route
     *
     * @param  String       $verb
     * @param  String       $path
     * @param  Array        $attr
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function add(String $verb, String $path, Array $attr): void
    {
        $route = [];

        // The HTTP Verb
        $route['verb'] = $verb;

        // Route path
        $route['path'] = $path;

        // Route (path) prefix
        $route['prefix'] = '';
        $group_prefix = self::$prefix;
        $group_prefix = array_pop($group_prefix);

        if($group_prefix !== NULL)
        {
            $route['prefix'] = trim($group_prefix,'/') . '/';
        }

        if(isset($attr['prefix']))
            $route['prefix'] .=  trim($route['prefix'] . $attr['prefix'],'/');

        $route['prefix']  = trim($route['prefix'],'/');

        // Controller
        if(!isset($attr['uses']))
            throw new \Exception('You must specify a route controller');

        $controller = $attr['uses'];
        if(count(explode('@', $controller)) != 2)
            throw new \Exception('Invalid controller@method route syntax');

        $route['controller'] = explode('@', $controller)[0];
        $route['method']     = explode('@', $controller)[1];

        // Alias (optional)
        $route['name'] = null;

        if(isset($attr['as']))
            $route['name'] = $attr['as'];

        // Middleware (optional)
        $route['middleware'] = [];
        $group_middleware = self::$middleware;
        $group_middleware = array_pop($group_middleware);

        if(is_array($group_middleware) && !empty($group_middleware))
        {
            $route['middleware'] = array_merge($route['middleware'], $group_middleware);
        }

        if(isset($attr['middleware']))
        {
            if(!is_array($attr['middleware']))
                throw new \Exception('Route middleware must be defined as array');
            $route['middleware'] += $attr['middleware'];
        }

        // Namespace (optional)
        $route['namespace'] = '';
        $group_namespace = self::$namespace;
        $group_namespace = array_pop($group_namespace);

        if($group_namespace !== NULL)
        {
            $route['namespace'] = rtrim($group_namespace,'\\') . '\\';
        }

        if(isset($attr['namespace']))
        {
            $route['namespace'] =  $route['namespace'] . rtrim($attr['namespace'],'\\');
        }
        $route['namespace'] = rtrim($route['namespace'], '\\');


        self::$routes[] = $route;
    }


    /**
     * Add group of routes
     *
     * @param  Array        $attr
     * @param  Callable     $routes
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function group(Array $attr, Callable $routes): void
    {
        // Middleware
        if(isset($attr['middleware']))
        {
            if(!is_array($attr['middleware']))
                throw new \Exception('Group middleware must be defined as array');

            foreach($attr['middleware'] as $middleware)
                self::$middleware[] = $middleware;
        }

        // Namespace
        if(isset($attr['namespace']))
            self::$namespace[] = $attr['namespace'];

        // Prefix
        if(isset($attr['prefix']))
            self::$prefix[] = $attr['prefix'];

        $routes();

        // Flushing nested attributes
        if(isset($attr['middleware']))
            foreach($attr['middleware'] as $middleware)
                array_pop(self::$middleware);

        if(isset($attr['namespace']))
            array_pop(self::$namespace);

        if(isset($attr['prefix']))
            array_pop(self::$prefix);
    }


    /**
     * Compile a raw route to a Symfony-compatible Route
     *
     * @param  array $route
     *
     * @return \Routing\Route
     *
     * @access private
     * @static
     */
    private static function compileRoute($route)
    {
        $route = (object) $route;
        $compiledRoute = [];

        // Route name
        $compiledRoute['name'] = $route->name;

        // Parsing the path
        $path  = '';
        if(!empty($route->prefix))
            $path .= trim($route->prefix, '/') . '/';
        $path .= trim($route->path, '/');
        $path  = trim($path, '/');
        $compiledRoute['path'] = $path;

        // Parsing the controller
        $controller = '';
        if(!empty($route->namespace))
            $controller = $route->namespace . '\\';
        $controller .= $route->controller . '::' . $route->method;
        $controller = trim($controller,'/');
        $compiledRoute['args']['_controller'] = $controller;

        // Parsing the route parameters

        return $compiledRoute;
    }


    /**
     * Defines a group/global namespace
     *
     * @param  String    $namespace
     * @param  Callable  $routes
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function namespace(String $namespace, Callable $routes = null)
    {
        self::$namespace[] = $namespace;

        if($routes !== null)
        {
            $routes();
            array_pop(self::$namespace);
        }

    }


    /**
     * Defines a group/global middleware
     *
     * @param  Array        $middleware
     * @param  Callable     $routes
     *
     * @return [add type]
     *
     * @access public
     * @static
     */
    public static function middleware(Array $middleware, Callable $routes = null)
    {
        self::$middleware[] = $middleware;

        if($routes !== null)
        {
            $routes();
            array_pop(self::$middleware);
        }
    }


    /**
     * Get all raw routes
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getRawRoutes()
    {
        return self::$routes;
    }


    /**
     * Get all routes in a compiled Symfony-compatible RouteCollection
     *
     * @return \Routing\RouteCollection
     *
     * @access public
     * @static
     */
    public static function getCompiledRoutes()
    {
        $routes = new Routing\RouteCollection();

        foreach(self::$routes as $r)
        {
            $route = (object) self::compileRoute($r);
            $routes->add($route->name, new Routing\Route($route->path, $route->args));
        }

        return $routes;
    }
}