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
 * Event triggered when a request is received in the application
 * and before the router starts matching URLs
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RequestEvent extends Event
{
    /**
     * @var \Luthier\Http\Request
     */
    private $request;

    /**
     * @var \Luthier\Http\Response
     */
    private $router;

    /**
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return \Luthier\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * @return \Luthier\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}