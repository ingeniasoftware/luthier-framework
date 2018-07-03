<?php

/**
 * Application class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Pimple\Psr11\Container;
use Luthier\Http\{Request, Response, ResponseIterator};
use Luthier\Routing\{Router, Route};

class Application
{
    private $container;

    private $request;

    private $response;

    private $route;

    public function __construct(Container $container, Route $route, Request $request, Response $response )
    {
        $this->container = $container;
        $this->request   = $request;
        $this->response  = $response;
        $this->route     = $route;
    }

    public function __get($property)
    {
        if(in_array($property, ['request','response','route']))
        {
            return $this->{$property};
        }
        else if($this->container->has($property))
        {
            return $this->container->get($property);
        }
        else
        {
            throw new \Exception("Undefined property Application::$property");
        }
    }
}