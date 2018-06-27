<?php

/**
 * Route class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

use Symfony\Component\Routing\Route as SfRoute;

class Route
{
    /**
     * Route relative path
     *
     * @var $path
     *
     * @access private
     */
    private $path;


    /**
     * Route absolute (full) path
     *
     * @var $fullPath
     *
     * @access private
     */
    private $fullPath;


    /**
     * Route name
     *
     * @var $name
     *
     * @access private
     */
    private $name;


    /**
     * Route accepted HTTP Verbs
     *
     * @var $methods
     *
     * @access private
     */
    private $methods = [];


    /**
     * Route callback/controller
     *
     * @var $action
     *
     * @access private
     */
    private $action;


    /**
     * Array of route assigned middleware
     *
     * @var $middleware
     *
     * @access private
     */
    private $middleware = [];


    /**
     * Route namespace
     *
     * @var $namespace
     *
     * @access private
     */
    private $namespace = '';


    /**
     * Route prefix
     *
     * @var $prefix
     *
     * @access private
     */
    private $prefix = '';


    /**
     * Route host
     *
     * @var $host
     *
     * @access private
     */
    private $host = '';


    /**
     * Array of accepted HTTP schemes
     *
     * @var $schemes
     *
     * @access private
     */
    private $schemes = [];


    /**
     * Array of route parameters
     *
     * @var $params
     *
     * @access public
     */
    public $params = [];


    /**
     * Segment index when start the parameters (if any)
     *
     * @var $paramOffset
     *
     * @access public
     */
    public $paramOffset;


    /**
     * Has the route any optional parameter?
     *
     * @var $hasOptionalParams
     *
     * @access public
     */
    public $hasOptionalParams = false;


    /**
     * Class constructor
     *
     * @param  string|array  $methods Accepted route methods
     * @param  array         $route Route attributes
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct($methods, array $route)
    {
        if($methods == 'any')
        {
            $methods = RouteBuilder::HTTP_VERBS;
        }
        elseif(is_string($methods))
        {
            $methods = [ strtoupper($methods) ];
        }
        else
        {
            array_shift($route);
        }

        foreach($methods as $method)
        {
            $this->methods[] = strtoupper($method);
        }

        [$path, $action] = $route;

        $this->path = trim($path, '/') == '' ? '/' : trim($path, '/');

        if(!is_callable($action) && count(explode('@', $action)) != 2)
        {
            throw new \Exception('Route action must be in controller@method syntax or be a valid callback');
        }

        $this->action = $action;

        $attributes = isset($route[2]) && is_array($route[2])
            ? $route[2]
            : NULL;

        if(!empty(RouteBuilder::getContext('prefix')))
        {
            $prefixes = RouteBuilder::getContext('prefix');

            foreach($prefixes as $prefix)
            {
                $this->prefix .= trim($prefix,'/') != ''
                     ? '/' .trim($prefix, '/')
                     : '';
            }

            $this->prefix = trim($this->prefix,'/');
        }

        if(!empty(RouteBuilder::getContext('namespace')))
        {
            $namespaces = RouteBuilder::getContext('namespace');

            foreach($namespaces as $namespace)
            {
                $this->namespace .= trim($namespace, '/') != ''
                    ? '/' .trim($namespace, '/')
                    : '';
            }

            $this->namespace = trim($this->namespace,'/');
        }

        if(!empty(RouteBuilder::getContext('middleware')['route']))
        {
            $middlewares = RouteBuilder::getContext('middleware')['route'];

            foreach($middlewares as $middleware)
            {
                if(!in_array($middleware, $this->middleware))
                {
                    $this->middleware[] = $middleware;
                }
            }
        }

        if(!empty(RouteBuilder::getContext('host')))
        {
            $host = RouteBuilder::getContext('host')[0];
            $this->host = $host;
        }

        if(!empty(RouteBuilder::getContext('schemes')))
        {
            $schemes = RouteBuilder::getContext('schemes');

            foreach($schemes as $scheme)
            {
                if(!in_array($scheme, $this->schemes))
                {
                    $this->schemes[] = $scheme;
                }
            }
        }

        if($attributes !== NULL)
        {
            if(isset($attributes['namespace']))
            {
                $this->namespace = (!empty($this->namespace) ? '/' : '' ) . trim($attributes['namespace'], '/');
            }

            if(isset($attributes['prefix']))
            {
                $this->prefix .= (!empty($this->prefix) ? '/' : '' ) . trim($attributes['prefix'], '/');
            }

            if(isset($attributes['middleware']))
            {
                if(is_string($attributes['middleware']))
                {
                    $attributes['middleware'] = [ $attributes['middleware'] ];
                }

                $this->middleware = array_merge($this->middleware, array_unique($attributes['middleware']));
            }
        }

        $params         = [];
        $this->fullPath = [];

        $_fullpath = trim($this->prefix,'/') != ''
            ? $this->prefix . '/' . $this->path
            : $this->path;

        $_fullpath = trim($_fullpath, '/') == ''
            ? '/'
            : trim($_fullpath, '/');

        foreach(explode('/', $_fullpath) as $i => $segment)
        {
            if(preg_match('/^\{(.*)\}$/', $segment))
            {
                if($this->paramOffset === null)
                {
                    $this->paramOffset = $i;
                }

                $param  = new RouteParam($segment);

                if(in_array($param->getName(), $params))
                {
                    throw new \Exception('Duplicate route parameter "' . $param->getName() . '" in route "' .  $this->path . '"');
                }

                $params[] = $param->getName();

                if( $param->isOptional() )
                {
                    $this->hasOptionalParams = true;
                }
                else
                {
                    if( $this->hasOptionalParams )
                    {
                        throw new \Exception('Required "' . $param->getName() . '" route parameter is not allowed at this position in "' . $this->path . '" route');
                    }
                }

                $this->params[]   = $param;
                $this->fullPath[] = '{' . $param->getName() . ( $param->isOptional() ? '?' : '') . '}';
            }
            else
            {
                $this->fullPath[] = $segment;
            }
        }

        $this->fullPath = implode('/', $this->fullPath);
    }


    /**
     * Compile the current route into a Symfony Routing Component route
     *
     * @return mixed
     *
     * @access public
     */
    public function compile()
    {
        $name = $this->name;
        $path = $this->fullPath;

        $defaults = [
            '_controller' => $this->action,
            '_instance'   => $this,
        ];
        $requirements = [];

        foreach($this->params as $param)
        {
            $requirements[$param->getName()] = $param->getRegex();
        }

        $options    = [];
        $host       = $this->host;
        $schemes    = $this->schemes;
        $methods    = $this->methods;

        return [$this->name,  new SfRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods)];
    }


    /**
     * Get or set a route parameter (if exists)
     *
     * @param  string  $name Parameter name
     * @param  mixed   $value (Optional)
     *
     * @return mixed
     *
     * @access public
     */
    public function param(string $name, $value = null)
    {
        foreach($this->params as &$param)
        {
            if($name == $param->getName())
            {
                if($value !== null)
                {
                    $param->value = $value;
                }
                return $param->value;
            }
        }
    }


    /**
     * Set the route name (method chaining)
     *
     * @param  string  $name
     *
     * @return self
     *
     * @access public
     */
    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }


    /**
     * Set the route host (method chaining)
     *
     * @param  string  $host
     *
     * @return self
     *
     * @access public
     */
    public function host(string $host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Set the route accepted HTTP schemes (method chaining)
     *
     * @param  array  $schemes
     *
     * @return self
     *
     * @access public
     */
    public function schemes(array $schemes)
    {
        $this->schemes = $schemes;
        return $this;
    }


    /**
     * Check if the route has a specific parameter
     *
     * @param  string  $name
     *
     * @return bool
     *
     * @access public
     */
    public function hasParam(string $name)
    {
        foreach($this->params as &$param)
        {
            if($name == $param->getName())
            {
                return true;
            }
        }
        return false;
    }


    /**
     * Get the route name
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
     * Set route name [alias of Route::name()]
     *
     * @param  string  $name
     *
     * @return self
     *
     * @access public
     */
    public function setName(string $name)
    {
        return $this->name($name);
    }


    /**
     * Get route path
     *
     * @return string
     *
     * @access public
     */
    public function getPath()
    {
        return $this->path;
    }


    /**
     * Get route absolute (full) path
     *
     * @return string
     *
     * @access public
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }


    /**
     * Get route prefix
     *
     * @return string
     *
     * @access public
     */
    public function getPrefix()
    {
        return $this->prefix;
    }


    /**
     * Get route action
     *
     * @return mixed
     *
     * @access public
     */
    public function getAction()
    {
        return $this->action;
    }


    /**
     * Get route middleware
     *
     * @return array
     *
     * @access public
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }


    /**
     * Get route namespace
     *
     * @return mixed
     *
     * @access public
     */
    public function getNamespace()
    {
        return $this->namespace;
    }


    /**
     * Get route accepted HTTP verbs
     *
     * @return array
     *
     * @access public
     */
    public function getMethods()
    {
        return $this->methods;
    }


    /**
     * Get route host
     *
     * @return string
     *
     * @access public
     */
    public function getHost()
    {
        return $this->host;
    }


    /**
     * Get route accepted HTTP schemes
     *
     * @return mixed
     *
     * @access public
     */
    public function getSchemes()
    {
        return $this->schemes;
    }


    /**
     * Set route path (method chaining)
     *
     * @param  string  $path
     *
     * @return self
     *
     * @access public
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }


    /**
     * Set route action (method chaining)
     *
     * @param  string  $action
     *
     * @return self
     *
     * @access public
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Set route host (method chaining)
     *
     * @param  string  $host
     *
     * @return self
     *
     * @access public
     */
    public function setHost(string $host)
    {
        return $this->host($host);
    }

    /**
     * Set route accepted HTTP schemes (method chaining)
     *
     * @param  array  $schemes
     *
     * @return self
     *
     * @access public
     */
    public function setSchemes(array $schemes)
    {
        return $this->schemes($schemes);
    }
}