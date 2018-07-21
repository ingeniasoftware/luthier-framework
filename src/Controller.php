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
 * Simple wrapper of the application dependency container, that will be provided
 * by the request handler during the route match
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
     * @param ContainerInterface $container
     * 
     * @return self
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        return $this;
    }
    
    /**
     * @param Request $request
     * 
     * @return self
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }
    
    /**
     * @param Response $response
     * 
     * @return self
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }
    
    /**
     * @param Route $route
     * 
     * @return self
     */
    public function setRoute(Route $route)
    {
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