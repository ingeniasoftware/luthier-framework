<?php

/**
 * PreRoutingEvent class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Events;

use Luthier\Http\{Request, Response};
use Luthier\Routing\Router;
use Symfony\Component\EventDispatcher\Event;

class PreRoutingEvent extends Event
{
    private $request;

    private $response;

    private $router;

    public function __construct(Request $request, Response $response, Router $router)
    {
        $this->response = $response;
        $this->request = $request;
        $this->router = $router;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getRouter()
    {
        return $this->router;
    }
}