<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Events;

use Luthier\Http\Request;
use Luthier\Http\Response;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event triggered before the response is sent to the client
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class ResponseEvent extends Event
{
    /**
     * @var \Luthier\Http\Request
     */
    private $request;

    /**
     * @var \Luthier\Http\Response
     */
    private $response;

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->response = $response;
        $this->request = $request;
    }

    /**
     * @return \Luthier\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return \Luthier\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}