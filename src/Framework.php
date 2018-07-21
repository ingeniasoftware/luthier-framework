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

use Luthier\Http\Request;
use Luthier\Http\Response;
use Luthier\Routing\RouteBuilder;
use Symfony\Component\HttpKernel;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

/**
 * An application of Luthier Framework
 *
 * @author Anderson Salas <anderson@ingenia.me>
 * 
 * @todo Unit testing
 * 
 */
class Framework
{
    const VERSION = '0.1.0a';

    /**
     * @var \Luthier\Framework
     */
    protected static $instance;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $envFolder;
    
    /** 
     * @var string
     */
    protected $cacheFolder;

    /**
     * @var \Luthier\Container
     */
    protected $container;

    /**
     * Application configuration
     *
     * @var array
     */
    protected $config = [];
    
    /**
     * @param array|null  $config     Application configuration
     * @param Container   $container  Application container
     * @param SfRequest   $request    Symfony request 
     * 
     * @throws \Exception
     */
    public function __construct(?array $config = [], Container $container = null, SfRequest $request = null)
    {
        $this->container = $container ?? new Container();
        $this->config    = $config    ?? [];
        $this->request   = $request   ?? SfRequest::createFromGlobals();
        
        if(!defined('LUTHIER_TEST_MODE'))
        {
            (new \Whoops\Run)->pushHandler(new \Whoops\Handler\PrettyPageHandler())
                ->register();
        }
        
        foreach(Container::getDefaultContainer() as $name => $class)
        {
            if(!$this->container->has($name))
            {
                $this->container->service($name, $class);
            }
        }
    }

    /**
     * __call() magic method
     *
     * @return mixed
     */
    public function __call($method, $args)
    {        
        if( in_array(strtoupper($method), RouteBuilder::HTTP_VERBS)
            || in_array($method, ['match', 'command'])
            || method_exists($this->container->get('router'), $method) )
        {
            return call_user_func_array([$this->container->get('router'), $method], $args);
        }

        throw new \BadMethodCallException("Call to undefined method Luthier\Framework::{$method}() ");
    }

    /**
     * __get() magic method
     *
     * @return mixed
     */
    public function __get($property)
    {
        if(isset($this->{$property}))
        {
            // Does the property exists?
            return $this->{$property};
        }
        else if($this->container->has($property))
        {
            // Is the property registered as a service/property in the DI container?
            return $this->container->get($property);
        }
        else
        {
            throw new \Exception("Trying to get undefined property Luthier\Framework::$property");
        }
    }

    /**
     * Gets the application Symfony request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets the application .env file path
     *
     * @param string $envFolder Folder where is located the application .env file
     *
     * @return self
     */
    public function setEnvFolder(string $envFolder)
    {
        $this->envFolder = $envFolder;
        return $this;
    }

    
    /**
     * Sets the application dependency container
     *
     * @param \Luthier\Container $container The dependency injection container
     *
     * @return mixed
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Sets the application Symfony request
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Symfony Request object
     *
     * @return self
     */
    public function setRequest(SfRequest $request)
    {
        $this->request = $request;
        return $this;
    }
    
    /**
     * Sets (or appends) the application configuration array
     *
     * @param  array  $config
     * @param  bool   $append
     *
     * @return self
     */
    public function setConfig(array $config, bool $append = FALSE)
    {
        if(!$append)
        {
            $this->config = $config;
        }
        else
        {
            $this->config = array_merge($this->config, $config);
        }
        return $this;
    }
    
    /**
     * Gets the parsed configuration
     * 
     * @return array
     */
    public function getConfig()
    {
        $configuration = new Configuration($this->config, $this->envFolder);
        return $configuration->parse();
    }

    /**
     * Runs the application
     *
     * @return void
     */
    public function run()
    {        
        $config = $this->getConfig();

        if(!in_array($config['APP_ENV'], ['development', 'production'], TRUE))
        {
            throw new \Exception('The application environment is not valid');
        }

        switch($config['APP_ENV'])
        {
            case 'development':
                error_reporting(-1);
                ini_set('display_errors',1);
                break;
            case 'production':
                error_reporting(0);
                ini_set('display_errors',0);
        }

        foreach($config as $name => $value)
        {
            $this->container->parameter($name, $value);
        }
        
        if(!$this->isCli())
        {
            $requestHandler = $this->container->get('request_handler');
            $cacheFolder    = $this->container->get('APP_CACHE');
                        
            if(!empty($cacheFolder))
            {
                $requestHandler = new HttpKernel\HttpCache\HttpCache(
                    $requestHandler,
                    new HttpKernel\HttpCache\Store($cacheFolder)
                );
            }
            
            $requestHandler->handle($this->request)->send();
        }
        else
        {
            $commandLoader = new FactoryCommandLoader($this->container->get('router')->getCommands());
            $application   = new Application();
            $application->setCommandLoader($commandLoader)->run();
        }
    }
}

