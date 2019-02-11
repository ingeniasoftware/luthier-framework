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
use Luthier\Routing\Route;
use Luthier\Http\Request;
use Luthier\Http\Response;

/**
 * An application Controller
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
     * @var \Luthier\Routing\Route
     */
    protected $route;

    /**
     * Controller init
     * 
     * @param ContainerInterface $container
     * @param Request $request
     * @param Response $response
     * @param Route $route
     * 
     * @internal
     * 
     * @return self
     */
    public function init(ContainerInterface $container, Request $request, Response $response, Route $route)
    {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
        $this->route = $route;
        return $this;
    }

    /**
     * __get() magic method
     * 
     * @param string $property
     * 
     * @throws \Exception
     * 
     * @return mixed
     */
    public function __get($property)
    {
        if ($this->container->has($property)) {
            return $this->container->get($property);
        } else {
            throw new \Exception("Trying to get undefined property $property");
        }
    }

    /**
     * Generates a route URL
     *
     * @param  string  $name    Route name
     * @param  array   $params  Route parameters
     *
     * @return string
     */
    public function route(string $name, array $params = [])
    {
        return $this->container->get('router')->getRouteByName($name, $params);
    }
}