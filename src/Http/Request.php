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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Luthier\UtilsTrait;

/**
 * Wrapper of the Symfony Request object with useful methods and shortcuts
 * for manipulating requests.
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Request implements RequestInterface
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
     * {@inheritDoc}
     * 
     * @see \Luthier\Http\RequestInterface::getRequest()
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Http\RequestInterface::setRequest()
     */
    public function setRequest(SfRequest $request = null)
    {
        $this->request = $request ?? SfRequest::createFromGlobals();

        return $this;
    }
    
    public function __get($property)
    {
        if (($property == 'session' || $property == 'sessionFlash') && !$this->request->hasSession()) {            
            // Session start
            $sessionStorage = new NativeSessionStorage();
            $sessionStorage->setName($this->container->get('SESSION_NAME'));
            $this->request->setSession(new Session($sessionStorage));
        }
        
        if ($property == 'session') {
            return $this->request->getSession();
        } else if($property== 'sessionFlash') {
            return $this->request->getSession()->getFlashBag(); 
        } else {
            throw new \Exception("Try to get undefined Request::$property property");
        }
    }

    public function __call($method, $args)
    {
        $httpContainers = [
            'attributes'   => 'attributes',
            'post'         => 'request',
            'get'          => 'query',
            'server'       => 'server',
            'file'         => 'files',
            'header'       => 'headers',
            'cookie'       => 'cookies',
            'session'      => null,
            'sessionFlash' => null,
         ];

        if (in_array($method, array_keys($httpContainers))) {     
            $name    = $args[0] ?? NULL;
            $default = $args[1] ?? NULL;

            if ($method == 'session') {                
                return $name !== NULL
                    ? ($this->request->hasSession() 
                        ? $this->request->getSession()->get($name, $default)
                        : null)
                    : ($this->request->hasSession()
                        ? $this->request->getSession()->all()
                        : []);
            } else if($method == 'sessionFlash') {
                return $name !== NULL
                    ? ($this->request->hasSession()
                        ? $this->request->getSession()->getFlashBag()->get($name, $default ?? [])
                        : null)
                    : ($this->request->hasSession()
                        ? $this->request->getSession()->getFlashBag()->all()
                        : []);
            } else {
                return $name !== NULL
                    ? $this->request->{$httpContainers[$method]}->get($name, $default)
                    : $this->request->{$httpContainers[$method]}->all();
            }
        } else if(method_exists($this->request, $method)) {
            return call_user_func_array([$this->request, $method], $args);
        } else {
            throw new \BadMethodCallException("Call to undefined method Request::{$method}()");
        }
    }

    /**
     * Checks if the current request is an AJAX request (alias of Request::isXmlHttpRequest())
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->request->isXmlHttpRequest();
    }
    
    /**
     * {@inheritDoc}
     * 
     * @see \Luthier\Http\RequestInterface::baseUrl()
     */
    public function baseUrl(string $url = '') : string
    {
        if ($this->container->get('APP_URL') !== null) {
            return $this->container->get('APP_URL') . '/' . trim($url, '/');
        }

        return trim($this->request->getScheme() 
            . '://' . $this->request->getHost()
            .  $this->request->getBasePath() 
            . '/' . $url, '/');
    }
}