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
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Framework own implementation of the Symfony HttpKernelInterface.
 * Handles the requests and returns a Symfony response.
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
     * @var \Symfony\Component\HttpKernel\Controller\ControllerResolver
     */
    protected $controllerResolver;
    
    /**
     * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolver
     */
    protected $argumentResolver;
    
    /**
     * Luthier Request object
     * 
     * @var \Luthier\Http\Request
     */
    protected $request;
    
    /**
     * Luthier Response object
     * 
     * @var \Luthier\Http\Response
     */
    protected $response;
    
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    /**
     * @param ContainerInterface $container Dependecy container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container  = $container;
        $this->router     = $container->get('router');
        $this->dispatcher = $container->get('dispatcher');
        
        $this->request  = new Request($container);
        $this->response = new Response($container);
        
        $this->matcher = new UrlMatcher($this->router->getRoutes(), $this->router->getRequestContext());
       
        $this->argumentResolver   = new ArgumentResolver();
        $this->controllerResolver = new ControllerResolver();
    }

    /**
     * @param SfRequest $request
     * @param array     $match
     * @param Route     $route
     * 
     * @throws \Exception
     * 
     * @return array ([$callback, $arguments])
     */
    private function resolveController(SfRequest &$request, array $match, Route $route)
    {
        // Both the (Luthier) Request and Response objects will be available 
        // as typehinted parameters of our routes callbacks/methods, just in case that
        // we prefer using that instead the $this->request/$this->response
        // properties
        $match['request']  = $this->request->setRequest($request);
        $match['response'] = $this->response->setResponse();
        $request->attributes->add($match);
        
        $callback = $this->controllerResolver->getController($request);

        // Is the current controller a Closure? Bind a new instance of the 
        // Luthier\Controller class
        if($callback instanceof \Closure)
        {
            $callback = \Closure::bind(
                $callback, 
                ( new Controller() )->setContainer($this->container)
                    ->setRequest($this->request)
                    ->setResponse($this->response)
                    ->setRoute($route),
                Controller::class
            );
        }
        // Is the current controller an instance of Luthier\Controller? set the container,
        // request, response and route object
        else if(isset($callback[0]) && $callback[0] instanceof Controller)
        {
            $callback[0]->setContainer($this->container)
                ->setRequest($this->request)
                ->setResponse($this->response)
                ->setRoute($route);
        }
        else 
        {
            // Not a closure or a object? we don't will handle that
            if(!isset($callback[0]) && !is_object($callback[0]))
            {
                throw new \Exception("The route does not contain a valid callback");
            }
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
        $luthierRequest  = $this->request;
        $luthierResponse = $this->response;
        $dispatcher = $this->dispatcher;
        
        // Dispatch the 'request' event
        $this->dispatcher->dispatch('request', new Events\RequestEvent($luthierRequest, $luthierResponse));

        if($this->router->count() == 0)
        {
            return $this->router->getWelcomeScreenResponse('Welcome to Luthier Framework. It seems that there is no route defined in your application yet');
        }

        $this->matcher->getContext()->fromRequest($request);

        try
        {
            $match = $this->matcher->match($request->getPathInfo());
         
            /** @var \Luthier\Routing\Route */
            $route = $match['_orig_route'];
            
            [$callback, $arguments] = $this->resolveController($request, $match, $route);
            $this->prepareRouting($request->getPathInfo(), $arguments, $route);
            
            $middlewareStack = $this->router->getMiddlewareStack($route);
            
            // Dispatch the 'pre_controller' event
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
        
        // Dispatch the 'response' event
        $this->dispatcher->dispatch('response', new Events\ResponseEvent($luthierRequest, $luthierResponse));
        
        return $luthierResponse->getResponse();
    }
}

