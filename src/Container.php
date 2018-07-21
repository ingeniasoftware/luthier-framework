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

use Pimple\Container as PimpleContainer;
use Pimple\Psr11\ServiceLocator;

/**
 * Facade of Pimple dependency injection container used by Luthier Framework
 *
 * This class contains methods for adding elements (services, properties and factories) to
 * the container:
 *
 *   * service()
 *   * parameter()
 *   * factory()
 *
 * The get() and has() methods retrieves and checks for the existence of
 * a element inside the container.
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Container
{
    /**
     * @var \Pimple\Container
     */
    protected $container;
    
    /**
     * @var array
     */
    protected $parameters = [];
    
    /**
     * @var array
     */
    protected $services = [];
        
    /**
     * The default container
     *
     * @var array
     */
    protected static $defaultContainer = [
        'router'          => \Luthier\Routing\RouteBuilder::class,
        'request_handler' => \Luthier\Http\RequestHandler::class,
        'dispatcher'      => \Symfony\Component\EventDispatcher\EventDispatcher::class,
    ];
    
    public function __construct()
    {
        $this->container = new PimpleContainer();
    }
    
    /**
     * __get() magic method
     *
     * @param string $service  The service/parameter/factory name to be fetched
     *
     * @return mixed
     */
    public function __get($service)
    {
        return $this->get($service);
    }
        
    /**
     * Gets a service callback from a string (in this case, a fully qualified class name)
     * 
     * @param string $name The service class name
     * 
     * @throws \InvalidArgumentException
     */
    private function getServiceCallback(string $service)
    {
        if(!class_exists($service))
        {
            throw new \InvalidArgumentException("Unable to register a new service: the class $service does not exists");
        }
        
        $container = $this->container;
        
        return function($container) use($service)
        {
            $defaultAliases = array_merge(
                array_keys(Configuration::getDefaultConfig()), array_keys(self::getDefaultContainer())
            );
            $userAliases = array_merge($this->services, $this->parameters);
            
            return new $service(
                new ServiceLocator(
                    $container,
                    array_unique(array_merge($defaultAliases,$userAliases))
                    )
                );
        };
    }
    
    /**
     * Returns the default Luthier Framework container
     *
     * @return array
     */
    public static function getDefaultContainer()
    {
        return self::$defaultContainer;
    }
    
    /**
     * Registers a new service in the container
     *
     * @param  string           $name     Service name
     * @param  callable|string  $service  Service callback
     *
     * @return self
     */
    public function service(string $name, $service)
    {       
        $this->container[$name] = is_string($service) 
            ? $this->getServiceCallback($service)
            : $service;

        if(!in_array($name, $this->services))
        {
            $this->services[] = $name;
        }
        
        return $this;
    }
    
    /**
     * Registers a new parameter in the container
     *
     * All parameter names will be converted to UPPERCASE
     *
     * @param  string  $name     Parameter name
     * @param  mixed   $value    Parameter value
     * @param  bool    $private  Mark the parameter as private
     *
     * @return self
     */
    public function parameter(string $name, $value)
    {
        $name = strtoupper($name);
        
        $this->container[$name] = is_callable($value)
            ? $this->container->protect($value)
            : $value;
               
        if(!in_array($name, $this->parameters))
        {
            $this->parameters[] = $name;
        }
        
        return $this;
    }
    
    /**
     * Registers a new factory in the container
     *
     * @param  string           $name     Factory name
     * @param  string|callable  $factory  Factory callback
     *
     * @return self
     */
    public function factory(string $name, $factory)
    {        
        $this->container[$name] = is_string($factory)
            ? $this->getServiceCallback($factory)
            : $service;
                
        if(!in_array($name, $this->services))
        {
            $this->services[] = $name;
        }
        
        return $this;
    }
    
    /**
     * Get a service/parameter from the container
     *
     * @param  string $name
     *
     * @return mixed
     *
     * @access public
     */
    public function get(string $name)
    {
        if($this->has($name))
        {
            return $this->container[$name];
        }
        else 
        {
            throw new \Exception("The '$name' service/parameter is not defined in the container");
        }    
    }
    
    /**
     * Check if the container has a specific service/parameter
     *
     * @param  string $name
     *
     * @return bool
     *
     * @access public
     */
    public function has(string $name)
    {
        return isset($this->container[$name]);
    }
    
    /**
     * @return array
     */
    public function getServices()
    {
        return $this->services;  
    }
}