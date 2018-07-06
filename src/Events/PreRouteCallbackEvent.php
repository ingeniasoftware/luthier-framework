<?php

/**
 * PreRouteCallbackEvent class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Events;

use Luthier\Http\{Request, Response};
use Luthier\Routing\Route;
use Symfony\Component\EventDispatcher\Event;

class PreRouteCallbackEvent extends Event
{
    private $request;

    private $response;

    private $route;

    private $routerCallback;

    private $routerArguments;

    public function __construct(Request $request, Response $response, Route &$route, callable $routerCallback, array $routerArguments)
    {
        $this->response = $response;
        $this->request = $request;
        $this->route = $route;
        $this->routerCallback = $routerCallback;
        $this->routerArguments = $routerArguments;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getRouterCallback()
    {
        return $this->routerCallback;
    }

    public function getRouterArguments()
    {
        return $this->routerArguments;
    }
}