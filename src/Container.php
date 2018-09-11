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
 * Pimple abstraction layer used by Luthier Framework
 *
 * Defines methods for adding elements (services, properties and factories) to
 * the container:
 *
 *  - service()
 *  - parameter()
 *  - factory()
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
    protected $publicAliases = [];

    /**
     * @var array
     */
    protected $privateAliases = [];

    /**
     * Gets the default container
     *
     * @var array
     */
    protected static $defaultContainer = [
        // (name) => [ (type), (class), (serviceLocatorAliases) ]
        'router' => [
            'service',
            \Luthier\Routing\RouteBuilder::class,
            []
        ],
        'request_handler' => [
            'service',
            \Luthier\Http\RequestHandler::class,
            [
                'PRIVATE_SERVICES'
            ]
        ],
        'dispatcher' => [
            'service',
            \Symfony\Component\EventDispatcher\EventDispatcher::class,
            []
        ],
        'request' => [
            'service',
            \Luthier\Http\Request::class,
            []
        ],
        'response' => [
            'service',
            \Luthier\Http\Response::class,
            []
        ],
        'logger' => [
            'service',
            \Luthier\Logger::class,
            []
        ],
        'database' => [
            'service',
            \Luthier\Database\Connection::class,
            []
        ],
        'template' => [
            'service',
            \Luthier\Templating\Template::class,
            []
        ]
    ];

    public function __construct()
    {
        $this->container = new PimpleContainer();
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
     * Gets a service callback from a string (in this case, a fully qualified class name)
     * 
     * @param string $name The service class name
     */
    private function getServiceCallback(string $service, array $locatorAliases = [])
    {
        $locatorAliases = array_merge(array_keys(Configuration::getDefaultConfig()), array_keys(self::getDefaultContainer()), $locatorAliases, $this->publicAliases);

        return function ($container) use ($service, $locatorAliases) {
            return new $service(new ServiceLocator($container, $locatorAliases));
        };
    }

    /**
     * Determines if a service is public or private based on the provided
     * name
     *  
     * @param string $name
     * @param mixed  $class
     * @param bool   $isPublic
     */
    private function parseItem(&$name, $class, &$isPublic)
    {
        if (substr($name, 0, 1) == '.') {
            $name = substr($name, 1);
            $isPublic = false;
        }

        if ($isPublic && ! in_array($name, $this->publicAliases)) {
            $this->publicAliases[] = $name;
        } else {
            if ($class !== null && is_string($class)) {
                $this->privateAliases[] = [
                    $name,
                    $class
                ];
            }
        }
    }

    /**
     * Registers a new service in the container
     * 
     * @param  string          $name           Service name
     * @param  callable|string $service        Service callback
     * @param  array           $locatorAliases Array of service aliases of the service locator
     * @param  bool            $isPublic       Set the service as public (will be available globally in the application)
     *
     * @return self
     */
    public function service(string $name, $service, array $locatorAliases = [], bool $isPublic = true)
    {
        $this->parseItem($name, $service, $isPublic);

        $this->container[$name] = is_string($service) ? $this->getServiceCallback($service, $locatorAliases) : $service;

        return $this;
    }

    /**
     * Registers a new factory in the container
     *
     * @param  string          $name           Factory name
     * @param  string|callable $factory        Factory callback
     * @param  array           $locatorAliases Array of service aliases of the service locator
     *
     * @return self
     */
    public function factory(string $name, $factory, array $locatorAliases = [])
    {
        $this->parseItem($name, $factory);

        $this->container[$name] = $this->container->factory(is_string($factory) ? $this->getServiceCallback($factory, $locatorAliases) : $factory);

        return $this;
    }

    /**
     * Registers a new parameter in the container
     *
     * All parameters are PUBLIC and their names will be converted to UPPERCASE
     *
     * @param  string $name    Parameter name
     * @param  mixed  $value   Parameter value
     * @param  bool   $private Mark the parameter as private
     *
     * @return self
     */
    public function parameter(string $name, $value)
    {
        $name = strtoupper($name);

        if (! in_array($name, $this->publicAliases)) {
            $this->publicAliases[] = $name;
        }

        $this->container[$name] = is_callable($value) ? $this->container->protect($value) : $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getPrivateAliases()
    {
        return $this->privateAliases;
    }

    /**
     * Gets a service/parameter from the container by its id
     *
     * @param  string $id
     *
     * @return mixed
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return $this->container[$id];
        } else {
            throw new \Exception("The '$id' service/parameter is not defined in the container");
        }
    }

    /**
     * Checks the existence of the provided service/parameter id
     *
     * @param  string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->container[$id]);
    }
}