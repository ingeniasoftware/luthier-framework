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
use Luthier\UtilsTrait;
use Luthier\Controller;
use Luthier\Routing\Route;
use Luthier\Events;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Luthier Framework own implementation of the Symfony HttpKernelInterface.
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RequestHandler implements HttpKernelInterface
{
    use UtilsTrait;

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
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * @param ContainerInterface $container Dependecy container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->router = $container->get('router');
        $this->logger = $container->get('logger');
        $this->dispatcher = $container->get('dispatcher');
        $this->request = $container->get('request');
        $this->response = $container->get('response');
        $this->matcher = new UrlMatcher($this->router->getRoutes(), $this->router->getRequestContext());
        $this->argumentResolver = new ArgumentResolver();
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
        // as typehinted parameters of the route callbacks/methods, just in case that
        // the developer prefers using it instead of the $this->request/$this->response
        // properties
        $match['request'] = $this->request;
        $match['response'] = $this->response;

        foreach ($this->container->get('PRIVATE_SERVICES') as [
            $name,
            $class
        ]) {
            if (class_exists($class)) {
                $match[$name] = new $class($this->container);
            }
        }

        $request->attributes->add($match);

        $callback = $this->controllerResolver->getController($request);

        if ($callback instanceof \Closure) {
            // Is the route action a closure? Let's bind it to a new instance of
            // Luthier\Controller
            $callback = \Closure::bind($callback, (new Controller())->setController($this->container, $this->request, $this->response, $route), Controller::class);
        } else if (isset($callback[0]) && $callback[0] instanceof Controller) {
            // Is the route action an instance of Luthier\Controller? then set the
            // container, request, response and current route
            $callback[0]->setController($this->container, $this->request, $this->response, $route);
        } else {
            if (! isset($callback[0]) && ! is_object($callback[0])) {
                // Not a closure or an object? sorry, we will not handle that
                throw new \Exception("The route does not contain a valid callback");
            }
        }

        $arguments = $this->argumentResolver->getArguments($request, $callback);

        return [
            $callback,
            $arguments
        ];
    }

    /**
     * @param string $url
     * @param array $arguments
     * @param Route $route
     * 
     * @return void
     */
    private function parseArguments(string $url, array &$arguments, Route $route)
    {
        $offset = 0;

        foreach ($arguments as $i => $arg) {
            if ($arg === null) {
                unset($arguments[$i]);
            }
        }

        foreach (explode('/', trim($url, '/')) as $i => $urlSegment) {
            $routeSegment = explode('/', $route->getFullPath())[$i];
            if (substr($routeSegment, 0, 1) == '{' && substr($routeSegment, - 1) == '}') {
                $route->params[$offset]->value = $urlSegment;
                $offset ++;
            }
        }
    }

    /**
     * @throws NotFoundHttpException
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function welcomeScreen()
    {
        if ($this->container->get('APP_ENV') == 'production') {
            throw new NotFoundHttpException('Your application does not contain any route');
        }

        ob_start();
        require __DIR__ . '/../Resources/Views/About.php';
        $responseBody = ob_get_clean();

        return new SfResponse($responseBody);
    }

    /**
     * @param SfRequest  $request
     * @param \Exception $exception
     * 
     * @return void
     */
    private function handle404(SfRequest $request, \Exception $exception)
    {
        $env = $this->container->get('APP_ENV');

        $this->logger->warning('HTTP 404: Not found (' . $request->getMethod() . ' ' . $request->getUri() . ')', [
            "REQUEST_HANDLER"
        ]);

        if ($env == 'development') {
            throw $exception;
        }

        $httpNotFoundCallback = $this->router->getHttpNotFoundCallback();

        Response::getRealResponse($httpNotFoundCallback !== null ? call_user_func_array($httpNotFoundCallback, [
            $this->request,
            $this->response
        ]) : $this->errorResponse($request, 404, 'Not found', 'The requested resource is not available or has been moved to another location'), $this->response);
    }

    /**
     * @param SfRequest  $request
     * @param Route      $route
     * @param \Exception $exception
     * 
     * @return void
     */
    private function handle405(SfRequest $request, ?Route $route, \Exception $exception)
    {
        $this->logger->warning('HTTP 405: Method not allowed (' . $request->getMethod() . ' ' . $request->getUri() . ')', [
            "REQUEST_HANDLER"
        ]);

        if ($this->container->get('APP_ENV') == 'development') {
            throw $exception;
        }

        $httpMethodNotAllowedCallback = $this->router->getHttpMethodNotAllowedCallback();

        Response::getRealResponse($httpMethodNotAllowedCallback !== null ? call_user_func_array($httpMethodNotAllowedCallback, [
            $this->request,
            $this->response,
            $route
        ]) : $this->errorResponse($request, 405, 'Not allowed', 'The request method is not allowed for this resource'), $this->response);
    }

    /**
     * @param mixed      $finalResponse
     * @param Route      $route
     * @param \Exception $exception
     * 
     * @throws \Exception
     * 
     * @return void
     */
    private function handleException(?Route $route, \Exception $exception)
    {
        $errorCallback = $this->router->getErrorCallback();

        if ($errorCallback !== NULL) {
            Response::getRealResponse(call_user_func_array($errorCallback, [
                $this->request,
                $this->response,
                $route,
                $exception
            ]), $this->response);

            $this->logger->error(get_class($exception) . ':' . $exception->getMessage(), [
                "REQUEST_HANDLER"
            ]);
        } else {
            throw $exception;
        }
    }

    /**
     * {@inheritDoc}
     * 
     * @see \Symfony\Component\HttpKernel\HttpKernelInterface::handle()
     */
    public function handle(SfRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this->request->setRequest($request);
        $this->response->setResponse();
        $this->logger->debug($request->getMethod() . ' ' . $request->getUri(), [
            'REQUEST_HANDLER'
        ]);
        $this->matcher->getContext()->fromRequest($request);

        // Dispatch the 'request' event
        $this->dispatcher->dispatch('request', new Events\RequestEvent($this->container));

        try {
            if ($this->router->count() == 0) {
                return $this->welcomeScreen();
            }

            $match = $this->matcher->match($request->getPathInfo());

            /** @var \Luthier\Routing\Route */
            $route = $match['_orig_route'];

            $this->router->setCurrentRoute($route);

            $this->logger->debug('Matched route ' . (! empty($route->getName()) ? '"' . $route->getName() . '"' : '<unnamed>') . ' (' . (! is_string($route->getAction()) ? '[anonymous@closure]' : $route->getAction()) . ') ' . 'for path "' . $route->getFullPath() . '" by router ' . get_class($this->router), [
                'REQUEST_HANDLER'
            ]);

            [
                $callback,
                $arguments
            ] = $this->resolveController($request, $match, $route);

            $this->parseArguments($request->getPathInfo(), $arguments, $route);

            $middlewareStack = $this->router->getMiddlewareStack($route);

            // Dispatch the 'pre_controller' event
            $this->dispatcher->dispatch('pre_controller', new Events\PreControllerEvent($this->container, $middlewareStack, $callback, $arguments));

            // Now with the route callback/arguments and the middleware stack, we can
            // start iterating it
            ResponseIterator::handle($this->router, $middlewareStack, $callback, $arguments, $this->request, $this->response);
        } catch (ResourceNotFoundException | NotFoundHttpException $exception) {
            $this->handle404($request, $exception);
        } catch (MethodNotAllowedException | MethodNotAllowedHttpException $exception) {
            $this->handle405($request, $route ?? null, $exception);
        } catch (\Exception $exception) {
            $this->handleException($route ?? null, $exception);
        }

        // Dispatch the 'response' event
        $this->dispatcher->dispatch('response', new Events\ResponseEvent($this->container));

        return $this->response->getResponse();
    }
}

