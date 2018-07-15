<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier;

use Psr\Container\ContainerInterface;

/**
 * Wrapper for the application container with shortcuts for basic operations with
 * the \Luthier\Http\Response object.
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Controller
{
    /**
     * @var \Pimple\Psr11\Container
     */
    protected $container;

    /**
     * @var \Luthier\Http\Request
     */
    protected $request;

    /**
     * @var \Luthier\Http\Response
     */
    protected $response;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request   = $container->get('request');
        $this->response  = $container->get('response');
    }

    public function __get($property)
    {
        if($this->container->has($property))
        {
            return $this->container->get($property);
        }
        else if($property == 'route')
        {
            return $this->container->get("router")->getCurrentRoute();
        }
        else
        {
            throw new \Exception("Trying to get undefined property $property");
        }
    }

    /**
     * Generates a route URL by its name
     *
     * @param  string  $name    Route name
     * @param  array   $params  Route parameters
     *
     * @return string
     */
    protected function route(string $name, array $params = [])
    {
        return Framework::container('router')->getRouteByName($name, $params);
    }
}