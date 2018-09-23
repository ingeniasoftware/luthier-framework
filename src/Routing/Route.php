<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */
namespace Luthier\Routing;

use Symfony\Component\Routing\Route as SfRoute;

/**
 * Represents a Route. The most important here is the compile() method, 
 * which returns the actual Symfony Route object.
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Route
{

    /**
     * Relative path
     * 
     * @var string
     */
    private $path;

    /**
     * Full (absolute) path
     *
     * @var string
     */
    private $fullPath;

    /**
     * @var string
     */
    private $name;

    /**
     * Accepted HTTP Verbs
     *
     * @var string[]
     * 
     * @access private
     */
    private $methods = [];

    /**
     * @var string|callable
     */
    private $action;

    /**
     * @var array
     */
    private $middleware = [];

    /**
     * @var string
     */
    private $namespace = '';

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var string[]
     */
    private $schemes = [];

    /**
     * @var RouteParam[] Array of route parameters
     */
    public $params = [];

    /**
     * @var int
     */
    public $paramOffset;

    /**
     * @var bool
     */
    public $hasOptionalParams = false;

    /** 
     * @param array|string  $methods  Route methods
     * @param array         $route    Route arguments
     * 
     * @throws \Exception
     */
    public function __construct($methods, array $route)
    {
        if ($methods == 'any') {
            $methods = RouteBuilder::HTTP_VERBS;
        } elseif (is_string($methods)) {
            $methods = [
                strtoupper($methods)
            ];
        } else {
            array_shift($route);
        }

        foreach ($methods as $method) {
            $this->methods[] = strtoupper($method);
        }

        [
            $path,
            $action
        ] = $route;

        $this->path = trim($path, '/') == '' ? '/' : trim($path, '/');

        if (! is_callable($action) && count(explode('@', $action)) != 2) {
            throw new \Exception('Route action must be in controller@method syntax or be a valid callback');
        }

        $this->action = $action;

        $attributes = isset($route[2]) && is_array($route[2]) ? $route[2] : NULL;

        if (! empty(RouteBuilder::getContext('prefix'))) {
            $prefixes = RouteBuilder::getContext('prefix');
            foreach ($prefixes as $prefix) {
                $this->prefix .= trim($prefix, '/') != '' ? '/' . trim($prefix, '/') : '';
            }
            $this->prefix = trim($this->prefix, '/');
        }

        if (! empty(RouteBuilder::getContext('namespace'))) {
            $namespaces = RouteBuilder::getContext('namespace');
            foreach ($namespaces as $namespace) {
                $this->namespace .= trim($namespace, '/') != '' ? '/' . trim($namespace, '/') : '';
            }
            $this->namespace = trim($this->namespace, '/');
        }

        if (! empty(RouteBuilder::getContext('middleware')['route'])) {
            $middleware = RouteBuilder::getContext('middleware')['route'][0];
            foreach ($middleware as $_middleware) {
                if (! in_array($_middleware, $this->middleware)) {
                    $this->middleware[] = $_middleware;
                }
            }
        }

        if (! empty(RouteBuilder::getContext('host'))) {
            $host = RouteBuilder::getContext('host')[0];
            $this->host = $host;
        }

        if (! empty(RouteBuilder::getContext('schemes'))) {
            $schemes = RouteBuilder::getContext('schemes');
            if (! empty($schemes)) {
                $this->schemes = $schemes[0];
            }
        }

        if ($attributes !== NULL) {
            if (isset($attributes['namespace'])) {
                $this->namespace = (! empty($this->namespace) ? '/' : '') . trim($attributes['namespace'], '/');
            }
            if (isset($attributes['prefix'])) {
                $this->prefix .= (! empty($this->prefix) ? '/' : '') . trim($attributes['prefix'], '/');
            }
            if (isset($attributes['middleware'])) {
                if (is_string($attributes['middleware'])) {
                    $attributes['middleware'] = [
                        $attributes['middleware']
                    ];
                }
                $this->middleware = array_merge($this->middleware, array_unique($attributes['middleware']));
            }
        }

        $params = [];
        $this->fullPath = [];

        $_fullpath = trim($this->prefix, '/') != '' ? $this->prefix . '/' . $this->path : $this->path;

        $_fullpath = trim($_fullpath, '/') == '' ? '/' : trim($_fullpath, '/');

        foreach (explode('/', $_fullpath) as $i => $segment) {
            if (preg_match('/^\{(.*)\}$/', $segment)) {
                if ($this->paramOffset === null) {
                    $this->paramOffset = $i;
                }

                $param = new RouteParam($segment);

                if (in_array($param->getName(), $params)) {
                    throw new \Exception('Duplicate route parameter "' . $param->getName() . '" in route "' . $this->path . '"');
                }

                $params[] = $param->getName();

                if ($param->isOptional()) {
                    $this->hasOptionalParams = true;
                } else {
                    if ($this->hasOptionalParams) {
                        throw new \Exception('Required "' . $param->getName() . '" route parameter is not allowed at this position in "' . $this->path . '" route');
                    }
                }

                $this->params[] = $param;
                $this->fullPath[] = '{' . $param->getName() . ($param->isOptional() ? '?' : '') . '}';
            } else {
                $this->fullPath[] = $segment;
            }
        }

        $this->fullPath = implode('/', $this->fullPath);
    }

    /**
     * Compiles the route to a Symfony route
     * 
     * @return array ([string $name, \Symfony\Component\Routing\Route $route])
     */
    public function compile()
    {
        $path = $this->fullPath;

        if (is_callable($this->action)) {
            $controller = $this->action;
        } else {
            $controller = (! empty($this->namespace) ? $this->namespace . '\\' : '') . implode('::', explode('@', $this->action));
        }

        $defaults = [
            '_controller' => $controller,
            '_orig_route' => $this
        ];

        $requirements = [];

        foreach ($this->params as $param) {
            $requirements[$param->getName()] = $param->getRegex();
        }

        $options = [];
        $host = $this->host;
        $schemes = $this->schemes;
        $methods = $this->methods;

        return [
            $this->name,
            new SfRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods)
        ];
    }

    /**
     * Gets (or sets) a route parameter
     * 
     * @param  string  $name  Parameter name
     * @param  mixed   $value Parameter value
     * 
     * @return mixed|void  
     */
    public function param(string $name, $value = null)
    {
        foreach ($this->params as &$param) {
            if ($name == $param->getName()) {
                if ($value !== null) {
                    $param->value = $value;
                }
                return $param->value;
            }
        }
    }

    /**
     * Sets the route name
     * 
     * @param string $name Route name
     * 
     * @return self
     */
    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Adds a middleware to route
     * 
     * @param mixed $middleware Route middleware
     * 
     * @return self
     */
    public function middleware($middleware)
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }

    /**
     * Sets the route host
     * 
     * @param string $host Route host
     * 
     * @return self
     */
    public function host(string $host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Sets the route accepted HTTP schemes
     *
     * @param  array  $schemes
     *
     * @return self
     */
    public function schemes(array $schemes)
    {
        $this->schemes = $schemes;
        return $this;
    }

    /**
     * Adds the built-in AJAX middleware to the current route
     *
     * @return self
     */
    public function ajax()
    {
        $this->middleware[] = new \Luthier\Http\Middleware\AjaxMiddleware();
        return $this;
    }

    /**
     * Checks if the route has a specific parameter
     *
     * @param  string  $name Parameter name
     *
     * @return bool
     */
    public function hasParam(string $name)
    {
        foreach ($this->params as &$param) {
            if ($name == $param->getName()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the route name
     *
     * @return string
     *
     * @access public
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets route name [alias of Route::name()]
     *
     * @param  string  $name Route name
     *
     * @return self
     */
    public function setName(string $name)
    {
        return $this->name($name);
    }

    /**
     * Gets route path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get route full (absolute) path
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * Gets route prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Gets route action
     *
     * @return string|callable
     *
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Gets route middleware
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Gets route namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Gets route accepted HTTP verbs
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Gets route host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Gets route accepted HTTP schemes
     *
     * @return array
     */
    public function getSchemes()
    {
        return $this->schemes;
    }

    /**
     * Gets all route sticky parameters
     *
     * @return array
     */
    public function getStickyParams()
    {
        $sticky = [];

        foreach ($this->params as $param) {
            if ($param->isSticky()) {
                $sticky[] = $param->getName();
            }
        }

        return $sticky;
    }

    /**
     * Sets the route path
     *
     * @param  string  $path Route path
     *
     * @return self
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Sets the route action
     *
     * @param  string|callable  $action Route action
     *
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Sets the route host
     *
     * @param  string  $host
     *
     * @return self
     */
    public function setHost(string $host)
    {
        return $this->host($host);
    }

    /**
     * Sets the route accepted HTTP schemes (method chaining)
     *
     * @param  array  $schemes Accepted HTTP schemes
     *
     * @return self
     */
    public function setSchemes(array $schemes)
    {
        return $this->schemes($schemes);
    }
}