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
    private $path;

    private $fullPath;

    private $name;

    private $methods = [];

    private $action;

    private $middleware = [];

    private $namespace = '';

    private $prefix = '';

    private $host = '';

    private $schemes = [];

    public $params = [];

    public $paramOffset;

    public $hasOptionalParams = false;

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

        $params = [];

        $fullPath = trim($this->prefix,'/') != ''
            ? $this->prefix . '/' . $this->path
            : $this->path;

        $fullPath = trim($fullPath, '/') == ''
            ? '/'
            : trim($fullPath, '/');

        $this->fullPath = $fullPath;

        foreach(explode('/', $fullPath) as $i => $segment)
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

                $this->params[] = $param;
            }
        }
    }


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

    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function host(string $host)
    {
        $this->host = $host;
        return $this;
    }

    public function schemes(array $schemes)
    {
        $this->schemes = $schemes;
        return $this;
    }

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


    public function getName()
    {
        return $this->name;
    }


    public function setName(string $name)
    {
        return $this->name($name);
    }


    public function getPath()
    {
        return $this->path;
    }


    public function getFullPath()
    {
        return $this->fullPath;
    }


    public function getPrefix()
    {
        return $this->prefix;
    }


    public function getAction()
    {
        return $this->action;
    }


    public function getMiddleware()
    {
        return $this->middleware;
    }


    public function getNamespace()
    {
        return $this->namespace;
    }


    public function getMethods()
    {
        return $this->methods;
    }


    public function getHost()
    {
        return $this->host;
    }


    public function getSchemes()
    {
        return $this->schemes;
    }


    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }


    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }


    public function setHost(string $host)
    {
        return $this->host($host);
    }


    public function setSchemes(array $schemes)
    {
        return $this->schemes($schemes);
    }
}