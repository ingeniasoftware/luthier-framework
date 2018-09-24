<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Templating;

use Psr\Container\ContainerInterface;
use Luthier\Templating\Driver\TemplateDriverInterface;

/**
 * Luthier Framework template service. Provides a uniform API for
 * rendering views using templates engines within the application.
 *  
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Template
{

    /**
     * @var TemplateDriverInterface
     */
    protected $driver;

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

        $driver = $container->get('TEMPLATE_DRIVER') ?? 'default';

        // PLEASE NOTE: Only the (default) Plain PHP driver works out-of-the-box
        // with Luthier Framework. In order to use the other drivers you
        // MUST download the respective template engine with Composer.
        $defaultDrivers = ['default' => \Luthier\Templating\Driver\PlainPhpDriver::class,'twig' => \Luthier\Templating\Driver\TwigDriver::class,'blade' => \Luthier\Templating\Driver\BladeDriver::class];

        if (isset($defaultDrivers[$driver])) {
            $driver = $defaultDrivers[$driver];
        }

        $this->driver = new $driver($container);

        $this->configure();
    }

    /**
     * Configures the template engine, providing a consistent behavior across engines
     */
    private function configure()
    {
        if (! ($this->driver instanceof TemplateDriverInterface)) {
            throw new \Exception("The " . get_class($this->driver) . '" MUST implement the ' . TemplateDriverInterface::class . ' interface');
        }

        // Globals variables
        $this->driver->addGlobal('request', $this->container->get('request'));
        $this->driver->addGlobal('app', $this->container);

        // Built-in functions
        //
        // (This is a compatibility layer between plain PHP and some compiled
        // php template engines such Twig)
        $container = $this->container;

        $this->driver->addFunction('route', function ($name, $args = []) {
            return call_user_func_array('route', [$name,$args]);
        });

        $this->driver->addFunction('url', function ($url = '') use ($container) {
            return call_user_func_array([$this->container->get('request'),'baseUrl'], [$url]);
        });
        
        $this->driver->addFunction('validation_errors', function($field = null) use($container){
            return call_user_func_array([$container->get('validator'), 'getValidationErrors'], $field);
        });
        
        $this->driver->addFunction('csrf_field', function() use($container){
            $tokenName = $container->get('security')->getCsrfTokenName(); 
            $tokenHash = $container->get('security')->getCsrfTokenHash(); 
            if (empty($tokenName) || empty($tokenHash)) {
                return;
            }
            return '<input type="hidden" name="' . $tokenName . '" value="'. $tokenHash . '" />';
        });
    }

    public function __call($method, $args)
    {
        if (method_exists($this->driver, $method)) {
            return call_user_func_array([$this->driver,$method], $args);
        }
        throw new \BadMethodCallException('Call to undefined method ' . get_class($this->driver) . '::' . $method);
    }
}
