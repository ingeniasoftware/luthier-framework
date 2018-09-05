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

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event triggered before the controller is executed
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class PreControllerEvent extends Event
{
    /**
     * @var ContainerInterface
     */
    private $container;
    
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
     * @param ContainerInterface  $container
     * @param array               $middlewareStack
     * @param callable            $callback
     * @param array               $arguments
     */
    public function __construct(ContainerInterface $container, array &$middlewareStack, callable &$callback, array &$arguments)
    {
        $this->container = $container;
        $this->middlewareStack = &$middlewareStack;
        $this->callback = &$callback;
        $this->arguments = &$arguments;
    }
    
    /**
     * @return \Luthier\Http\Request
     */
    public function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * @return \Luthier\Http\Response
     */
    public function getResponse()
    {
        return $this->container->get('response');
    }
    
    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
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