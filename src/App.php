<?php

/**
 * Luthier Framework
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Http\{Request, Response};
use Luthier\Routing\{Router, RouteBuilder};
use Symfony\Component\HttpFoundation\{Request as SfRequest, Response as SfResponse};
use Symfony\Component\HttpKernel;
use Symfony\Component\Routing;

class App
{
    private static $instance;

    protected $request;

    protected $response;

    protected $router;

    public function __call($method, $args)
    {
        if(in_array(strtoupper($method), RouteBuilder::HTTP_VERBS))
        {
            return call_user_func_array([$this->router, $method], $args);
        }
        if(in_array($method, ['group']))
        {
            return call_user_func_array([$this->router, $method], $args);
        }

         throw new \Exception("Undefined method App::{$method}() ");
    }

    public function __get($property)
    {
        if(isset($this->{$property}))
        {
            return $this->{$property};
        }

        throw new \Exception("Undefined property App::$property ");
    }

    public static function getInstance()
    {
        return self::$instance;
    }


    public function __construct(SfRequest $request = null)
    {
        $this->request  = new Request($request);
        $this->response = new Response();
        $this->router   = new Router();
        self::$instance = &$this;
    }


    public function run(SfRequest $request = null)
    {
        $request = $request === null
            ? $this->request->getSfRequest()
            : $request;

        $response = $this->response->getSfResponse();
        $context  = new Routing\RequestContext();

        $context->fromRequest($request);

        $routes  = $this->router->getCompiledRoutes();
        $matcher = new Routing\Matcher\UrlMatcher($routes, $context);

        $controllerResolver = new HttpKernel\Controller\ControllerResolver();
        $argumentResolver   = new HttpKernel\Controller\ArgumentResolver();

        try
        {
            $matchedRoute  = $matcher->match($request->getPathInfo());
                             $request->attributes->add($matchedRoute);

            $routeInstance = $matchedRoute['_instance'];

            $this->router->setCurrentRoute($routeInstance);

            $controller = $controllerResolver->getController($request);
            $arguments  = $argumentResolver->getArguments($request, $controller);

            foreach($arguments as $i => $arg)
            {
                if($arg === null)
                {
                    unset($arguments[$i]);
                }
            }

            $responseResult = call_user_func_array($controller, $arguments);

        }
        catch(Routing\Exception\ResourceNotFoundException $e)
        {
            return (new SfResponse('Not Found', 404))->send();
        }
        catch(\Exception $e)
        {
            return (new SfResponse('An error occurred', 500))->send();
        }

        if(isset($responseResult) && $responseResult instanceof SfResponse)
        {
            return $responseResult->send();
        }

        $response->send();

    }
}