<?php

/**
 * Container class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as PimplePsrContainer;

class Container
{
    /**
     * Pimple instance
     *
     * @var $container
     *
     * @access protected
     */
    protected $container;


    /**
     * Private services/parameters
     *
     * @var $privateItems
     *
     * @access protected
     */
    protected $privateItems = [];


    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct()
    {
        $this->container = new PimpleContainer();
    }


    /**
     * __get() magic method
     *
     * @param  mixed  $service The service/parameter/factory name to be fetched
     *
     * @return mixed
     *
     * @access public
     */
    public function __get($service)
    {
        return $this->get($service);
    }


    /**
     * Get a Psr11 Pimple container
     *
     * This method also unset any service/parameter marked as private
     *
     * @return PimplePsrContainer
     *
     * @access public
     */
    public function getPsrContainer()
    {
        $container = clone $this->container;
        foreach($this->privateItems as $service)
        {
            unset($container[$service]);
        }

        return new PimplePsrContainer($container);
    }


    /**
     * Get a valid callback from a service name
     *
     * @param  string|callback $service
     *
     * @return callable
     *
     * @access private
     */
    private function getServiceCallback($service)
    {
        if(is_string($service))
        {
            if(!class_exists($service))
            {
                throw new \InvalidArgumentException("Unable to register a new service: the class $service does not exists");
            }

            $container = $this->container;
            return function($container) use($service)
            {
                return new $service();
            };
        }
        else if(is_callable($service))
        {
            return $service;
        }
        else
        {
            throw new \InvalidArgumentException("Unable to register a new service: the service callback must be a valid callback or a fully-qualified class name");
        }
    }


    /**
     * Register a new service
     *
     * @param  string           $name Service name
     * @param  callable|string  $service Service callback
     * @param  bool             $private Mark this service as private
     *
     * @return Container
     *
     * @access public
     */
    public function service(string $name, $service, bool $private = false)
    {
        $this->container[$name] = $this->getServiceCallback($service);
        if(!in_array($name, $this->privateItems) && $private)
        {
            $this->privateItems[] = $name;
        }

        return $this;
    }


    /**
     * Register a new parameter
     *
     * @param  string  $name Parameter name
     * @param  mixed   $value Parameter value
     * @param  bool    $private Mark this parameter as private
     *
     * @return Container
     *
     * @access public
     */
    public function parameter(string $name, $value, bool $private = false)
    {
        $this->container[$name] = is_callable($value)
            ? $this->container->protect($value)
            : $value;

        if(!in_array($name, $this->privateItems) && $private)
        {
            $this->privateItems[] = $name;
        }

        return $this;
    }


    /**
     * Register a new factory
     *
     * @param  string    $name Factory name
     * @param  callable  $service Factory callback
     * @param  bool      $private Mark this factory as private
     *
     * @return Container
     *
     * @access public
     */
    public function factory(string $name, callable $service, bool $private = false)
    {
        $this->container[$name] = $this->container->factory($this->getServiceCallback($service));
        if(!in_array($name, $this->privateItems) && $private)
        {
            $this->privateItems[] = $name;
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
        if(!$this->has($name))
        {
            throw new \Exception("The '$name' service/parameter is not defined in the container");
        }

        return $this->container[$name];
    }


    /**
     * Check if the container has a specific service/parameter
     *
     * @param  string $name
     *
     * @return mixed
     *
     * @access public
     */
    public function has(string $name)
    {
        return isset($this->container[$name]);
    }
}