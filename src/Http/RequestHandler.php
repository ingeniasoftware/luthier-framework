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
use Luthier\Controller;
use Luthier\Routing\Route;
use Luthier\Events;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Framework own implementation of the Symfony HttpKernelInterface.
 * Receives and handles the requests, returning a Symfony Response object.
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RequestHandler implements HttpKernelInterface
{
    /**
     * @var \Luthier\Container
     */
    protected $container;

    /**
     * @var \Luthier\Routing\RouteBuilder
     */
    protected $router;

    /**
     * @var \Symfony\Component\Routing\Matcher\UrlMatcher
     */
    protected $matcher;

    /**
     * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolver
     */
    protected $argumentResolver;
    
    /**
     * Luthier Request object
     * 
     * @var \Luthier\Http\Request
     */
    protected $luthierRequest;
    
    /**
     * Luthier Response object
     * 
     * @var \Luthier\Http\Response
     */
    protected $luthierResponse;
    
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    /**
     * @param ContainerInterface $container Dependecy container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->router = $container->get('router');
        $this->dispatcher = $container->get('dispatcher');
        $this->luthierRequest = $container->get('request');
        $this->luthierResponse = $container->get('response');
        
        $this->matcher = new UrlMatcher($this->router->getRoutes(), $this->router->getRequestContext());
        $this->argumentResolver = new ArgumentResolver();
    }

    /**
     * Resolves the controller and arguments from matched route
     * 
     * @param SfRequest $request
     * @param array $match
     * @throws \Exception
     * @return array ([$callback, $arguments])
     */
    private function resolveController(SfRequest &$request, array $match)
    {
        $match['request']  = $this->luthierRequest->setRequest($request);
        $match['response'] = $this->luthierResponse->setResponse();
        
        $request->attributes->add($match);

        if(is_string($match['_controller']))
        {
            $callback = explode('::', $match['_controller'], 2);
            
            if(isset($callback[0]) && isset($callback[1]))
            {
                $callback = [new $callback[0]($this->container), $callback[1]];
                
                if(!$callback[0] instanceof Controller)
                {
                    throw new \Exception("Your controller MUST extend the Luthier\Controller class");
                }
            }
            else
            {
                throw new \Exception("Unable to create a new instance of {$match['_controller']}");
            }
        }
        elseif(is_callable($match['_controller']) && $match['_controller'] instanceof \Closure)
        {
            $callback = \Closure::bind($match['_controller'], new Controller($this->container), Controller::class);
        }
        else
        {
            throw new \Exception("The route controller action is not a valid callback");
        }
        
        $arguments = $this->argumentResolver->getArguments($request, $callback);
        
        return [$callback, $arguments];
    }
    
    /**
     * Prepares the Luthier Route object and set their parameter values from the URL
     * 
     * @param string $url
     * @param array $arguments
     * @param Route $route
     */
    private function prepareRouting(string $url, array &$arguments, Route $route)
    {
        foreach($arguments as $i => $arg)
        {
            if($arg === null)
            {
                unset($arguments[$i]);
            }
        }
        
        $offset = 0;
        
        foreach( explode('/', trim($url, '/')) as $i => $urlSegment )
        {
            $routeSegment = explode('/', $route->getFullPath())[$i];
            if(substr($routeSegment,0,1) == '{' && substr($routeSegment,-1) == '}')
            {
                $route->params[$offset]->value = $urlSegment;
                $offset++;
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * 
     * @see \Symfony\Component\HttpKernel\HttpKernelInterface::handle()
     */
    public function handle(SfRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $luthierRequest  = $this->luthierRequest;
        $luthierResponse = $this->luthierResponse;
        $dispatcher = $this->dispatcher;
        
        $this->dispatcher->dispatch('request', new Events\RequestEvent($luthierRequest, $luthierResponse));

        if($this->router->count() == 0)
        {
            return $this->router->getWelcomeScreenResponse('Welcome to Luthier Framework. It seems that there is no route defined in your application yet');
        }

        $this->matcher->getContext()->fromRequest($request);

        try
        {
            $match = $this->matcher->match($request->getPathInfo());
            $route = $match['_orig_route'];
            
            [$callback, $arguments] = $this->resolveController($request, $match);
            $this->prepareRouting($request->getPathInfo(), $arguments, $route);
            $middlewareStack = $this->router->getMiddlewareStack($route);
            
            $this->dispatcher->dispatch('pre_controller', new Events\PreControllerEvent($luthierRequest, $luthierResponse, $middlewareStack, $callback, $arguments));
            
            ResponseIterator::handle($middlewareStack, $callback, $arguments, $luthierRequest, $luthierResponse);
        }
        catch (ResourceNotFoundException|NotFoundHttpException $e)
        {
            $finalResponse = call_user_func_array($this->router->getHttpNotFoundCallback(), [$luthierRequest, $finalResponse ?? $luthierResponse, $e]);
        }
        catch (\Exception $e)
        {
            $finalResponse = call_user_func_array($this->router->getErrorCallback(), [$luthierRequest, $finalResponse ?? $luthierResponse, $route ?? null, $e]);
        }

        $this->dispatcher->dispatch('response', new Events\ResponseEvent($luthierRequest, $luthierResponse));
        
        return $luthierResponse->getResponse();
    }
}

