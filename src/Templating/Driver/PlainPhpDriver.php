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

/**
 * Simple yet minimalist plain PHP template driver. Provides basic templating 
 * capabilities out-of-the-box such inheritance and blocks/placeholders. 
 * 
 * @author Anderson Salas <anderson@ingenia.me> 
 */
class PlainPhpDriver implements TemplateDriverInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    
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
     * @var array
     */
    protected $blocks = [];
    
    /**
     * @var mixed
     */
    protected $parentBlock;
    
    /**
     * @var array
     */
    protected $extending = [];
    
    /**
     * @var self
     */
    protected static $instance;
    
    public static function __callStatic($method, $args)
    {        
        if(isset(self::$instance->functions[$method]))
        {
            return call_user_func_array(self::$instance->functions[$method], $args);
        }
        else if($method == 'extends')
        {
            if(count($args) < 1)
            {
                throw new \InvalidArgumentException("The 'extends' function expects at least 2 arguments, " . count($args) . " provided");
            }
            
            $template = $args[0];
            $vars     = $args[1] ?? [];

            self::$instance->extending[] = [$template, $vars];
        }
        else if($method == 'block')
        {            
            if(count($args) < 1)
            {
                throw new \InvalidArgumentException("The 'block' function expects at least 2 arguments, " . count($args) . " provided");
            }
            
            $name    = $args[0];
            $content = $args[1] ?? null;
            
            if(!isset(self::$instance->blocks[$name]))
            {                
                self::$instance->blocks[$name] = $content;
            }
            else
            {
                if(is_callable(self::$instance->blocks[$name]))
                {
                    return call_user_func(self::$instance->blocks[$name]);
                }
                return self::$instance->blocks[$name];
            }
        }
        else
        {
            throw new \BadMethodCallException('Undefined "' . $method . '" template function');
        }
    }
    
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container     = $container;  
        $this->directories[] = $container->get('APP_PATH') . '/' . $container->get('TEMPLATE_DIR');
        $this->registerFunctions();
        self::$instance = &$this;
    }
    
    private function registerFunctions()
    {
        $instance = &$this;
        
        //
        // "$_" variable
        //
        // Lambda function used for invoke other registered functions
        // for this template engine.
        //
        $this->globals['_'] = function($name, ...$args) use($instance){
            if(isset($instance->functions[$name]))
            {
                return $instance->functions[$name](...$args);
            }
            throw new \Exception("Call to undefined template function $name()");
        };
        
        //
        // "$_b" variable
        //
        // Lambda function used for define and set blocks within the templates
        //
        $this->globals['_b'] = function($name, $content = null) use($instance){
            if(!isset($instance->blocks[$name]))
            {
                if($content !== null)
                {
                    $instance->blocks[$name] = $content;
                }
            }
            else
            {
                if(is_callable($instance->blocks[$name]))
                {
                    return call_user_func($instance->blocks[$name]);
                }
                return $instance->blocks[$name];
            }
        };
        
        //
        // "$_e" variable
        //
        //  Lambda function used to extend another templates
        //
        $this->globals['_e'] = function($template, $vars = []) use($instance){
            $instance->extending[] = [$template, $vars];
        };
    }
    
    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::render()
     */
    public function render(string $template, array $vars = [], bool $return = false)
    {                         
        $filename = null;
        
        foreach($this->directories as $dir)
        {
            $match = $dir . '/' . $template . (!substr($template,-4) != '.php' ? '.php' : '');
            if(file_exists($match))
            {
                $filename = $match;
                break;
            }
        }
        
        if($filename === null)
        {
            throw new \Exception("Unable to find template file '$template' (Looked at  " . implode(', ', $this->directories) . '")');
        }
        
        extract(array_merge($vars, $this->globals));
        
        ob_start();
        require $filename;
        $view = ob_get_clean();

        $extending = array_shift($this->extending);
        
        if(!empty($extending))
        {
            [$template, $vars] = $extending;
            return $this->render($template, $vars, $return);
        }
        
        if($return)
        {
            return $view;
        }
        
        $this->container->get('response')->write($view);   
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
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addFilter()
     */
    public function addFilter(string $name, callable $callback, bool $rawHtml = false)
    {
        $this->addFunction($name, $callback); 
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
     * @see \Luthier\Templating\Driver\TemplateDriverInterface::addDirectory()
     */
    public function addDirectory(string $dir)
    {
        $this->directories[] = $dir;
    }
}
