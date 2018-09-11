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
 * Event triggered before the response is sent to the client
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class ResponseEvent extends Event
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