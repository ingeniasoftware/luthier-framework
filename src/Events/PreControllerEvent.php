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
 * Event triggered before the controller is executed
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class PreControllerEvent extends Event
{
    /**
     * @var \Luthier\Http\Request
     */
    private $request;

    /**
     * @var \Luthier\Http\Response;
     */
    private $response;
    
    /**
     * @var array
     */
    private $middlewareStack;
    
    /**
     * @var callable
     */
    private $callback;
    
    /**
     * @var array
     */
    private $arguments;
    
    /**
     * @param Request $request
     * @param Response $response
     * @param array $middlewareStack
     * @param callable $callback
     * @param array $arguments
     */
    public function __construct(Request $request, Response $response, array &$middlewareStack, callable &$callback, array &$arguments)
    {
        $this->response = $response;
        $this->request = $request;
        $this->middlewareStack = &$middlewareStack;
        $this->callback = &$callback;
        $this->arguments = &$arguments;
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

    /**
     * @return array
     */
    public function getMiddlewareStack()
    {
        return $this->middlewareStack;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $middlewareStack
     */
    public function setMiddlewareStack(array $middlewareStack)
    {
        $this->middlewareStack = $middlewareStack;
    }

    /**
     * @param callable $callback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }
}