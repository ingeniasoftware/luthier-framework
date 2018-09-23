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

class TwigDriver implements TemplateDriverInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \Twig_Loader_Filesystem
     */
    protected $loader;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->loader = new \Twig_Loader_Filesystem([$container->get('APP_PATH') . '/' . $container->get('TEMPLATE_DIR')]);

        $config = [];
        $config['cache'] = ! empty($container->get('APP_CACHE')) ? $container->get('APP_PATH') . '/' . $container->get('APP_CACHE') . '/templates' : false;
        $config['debug'] = $container->get('APP_ENV') === 'development';

        $this->twig = new \Twig_Environment($this->loader, $config);
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addFunction()
     */
    public function addFunction(string $name, callable $callback, bool $rawHtml = false)
    {
        $this->twig->addFunction(new \Twig_Function($name, $callback, ! $rawHtml ? [] : ['is_safe' => ['html']]));
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addGlobal()
     */
    public function addGlobal(string $name, $value)
    {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addFilter()
     */
    public function addFilter(string $name, callable $callback, bool $rawHtml = false)
    {
        $this->twig->addFilter(new \Twig_Filter($name, $callback, ! $rawHtml ? [] : ['is_safe' => ['html']]));
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addDirectory()
     */
    public function addDirectory(string $dir)
    {
        $this->loader->addPath($dir);
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::render()
     */
    public function render(string $template, array $vars = [], bool $return = false)
    {
        if (! substr($template, - 10) != '.html.twig') {
            $template .= '.html.twig';
        }

        $output = $this->twig->render($template, $vars);

        if (! $return) {
            $this->container->get('response')->write($output);
        }

        return $output;
    }
}