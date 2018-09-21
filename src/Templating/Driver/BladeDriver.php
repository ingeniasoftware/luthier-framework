<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Templating\Driver;

use Psr\Container\ContainerInterface;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ViewServiceProvider;

/**
 * Driver for Laravel Blade template engine
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class BladeDriver implements TemplateDriverInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Illuminate\Container\Container
     */
    protected $illuminateContainer;

    /**
     * @var array
     */
    protected $globals = [];

    /**
     * @var array
     */
    protected $functions = [];

    /**
     * @var array
     */
    protected $directories = [];

    /**
     * @var \Illuminate\View\Factory
     */
    protected $blade;

    /**
     * @var \Illuminate\View\Engines\CompilerEngine
     */
    protected $engine;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->directories[] = $container->get('APP_PATH') . '/' . $container->get('TEMPLATE_DIR');
    }

    private function boot()
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;
        $config = [];

        $directories = $this->directories;
        $cache = ! empty($this->container->get('APP_CACHE')) ? $this->container->get('APP_PATH') . '/' . $this->container->get('APP_CACHE') . '/templates' : false;

        if (! file_exists($cache)) {
            mkdir($config['cache'], null, true);
        }

        $illuminateContainer = new Container();

        $illuminateContainer->bindIf('files', function () {
            return new Filesystem();
        }, true);

        $illuminateContainer->bindIf('events', function () {
            return new Dispatcher();
        }, true);

        $illuminateContainer->bindIf('config', function () use ($directories, $cache) {
            return ['view.paths' => $directories,'view.compiled' => $cache];
        }, true);

        (new ViewServiceProvider($illuminateContainer))->register();

        $this->illuminateContainer = $illuminateContainer;
        $this->blade = $this->illuminateContainer['view'];
        $this->engine = $this->illuminateContainer->make('view.engine.resolver')->resolve('blade');

        $instance = &$this;

        //
        // "$_" variable
        //
        // Lambda function used for invoke other registered functions
        // for this template engine.
        //
        $this->globals['_'] = function ($name, ...$args) use ($instance) {
            if (isset($instance->functions[$name])) {
                return $instance->functions[$name](...$args);
            }
            throw new \Exception("Call to undefined template function $name()");
        };
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addFunction()
     */
    public function addFunction(string $name, callable $callback, bool $rawHtml = false)
    {
        $this->functions[$name] = $callback;
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addGlobal()
     */
    public function addGlobal(string $name, $value)
    {
        $this->globals[$name] = $value;
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addFilter()
     */
    public function addFilter(string $name, callable $callback, bool $rawHtml = false)
    {
        $this->addFunction($name, $callback);
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addDirectory()
     */
    public function addDirectory(string $dir)
    {
        $this->directories[] = $dir;
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::render()
     */
    public function render(string $template, array $vars = [], bool $return = false)
    {
        $this->boot();

        $view = $this->blade->make($template, array_merge($vars, $this->globals), [])->render();

        if (! $return) {
            $this->container->get('response')->write($view);
        }

        return $view;
    }
}