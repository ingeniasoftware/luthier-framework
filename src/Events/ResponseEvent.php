<?php

/**
 * ResponseEvent class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Events;

use Luthier\Http\{Request, Response};
use Symfony\Component\EventDispatcher\Event;

class ResponseEvent extends Event
{
    private $request;

    private $response;

    public function __construct(Request $request, Response $response)
    {
        $this->response = $response;
        $this->request = $request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }
}