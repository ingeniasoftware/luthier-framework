<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Http;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Luthier\UtilsTrait;

/**
 * Wrapper of the Symfony Request object 
 * 
 * Includes shortcuts for manipulating incoming requests. Is registered
 * in the dependency container as the "request" service
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Request
{
    use UtilsTrait;
    
    /**
     * Symfony request object
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Gets the Symfony request object
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * Sets the Symfony request object
     * 
     * @param SfRequest $request
     * 
     * @return self
     */
    public function setRequest(SfRequest $request = null)
    {
        $this->request = $request === NULL
            ? SfRequest::createFromGlobals()
            : $request;
        
        return $this;
    }

    public function __call($property, $args)
    {
        // Is any of the following shortcuts?
        $httpContainers = [
            'attributes' => 'attributes',
            'post'       => 'request',
            'get'        => 'query',
            'server'     => 'server',
            'file'       => 'files',
            'session'    => 'session',
            'header'     => 'header',
            'cookie'     => 'cookies',
        ];

        if(in_array($property, array_keys($httpContainers)))
        {
            $name    = $args[0] ?? NULL;
            $default = $args[1] ?? NULL;

            // ... return their values directly
            return $name !== NULL
                ? $this->request->{$httpContainers[$property]}->get($name, $default)
                : $this->request->{$httpContainers[$property]}->all();
        }
        // Is a Symfony Request method? call it
        else if( method_exists($this->request, $property) )
        {
            return call_user_func_array([$this->request, $property], $args);
        }
        else
        {
            throw new \BadMethodCallException ("Call to undefined method Request::{$property}()");
        }
    }

    /**
     * Checks if the current request is an AJAX request
     *
     * @return bool
     */
    public function isAjax()
    {
        return strtolower($this->server('HTTP_X_REQUESTED_WITH','')) === 'xmlhttprequest';
    }

}