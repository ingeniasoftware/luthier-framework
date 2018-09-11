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
 * Event triggered when a request is received in the application
 * and before the router starts matching URLs
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RequestEvent extends Event
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Luthier\Http\RequestInterface;
     */
    public function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * @return \Luthier\Http\ResponseInterface;
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
}