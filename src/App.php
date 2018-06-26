<?php

/**
 * Luthier Framework
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Http\{Request, Response};
use Luthier\Routing\Router;
use Symfony\Component\HttpFoundation\{Request as SfRequest, Response as SfResponse};
use Symfony\Component\HttpKernel;
use Symfony\Component\Routing;

class App
{
    private static $instance;

    protected $request;

    protected $response;

    protected $router;


    public static function getInstance()
    {
        return self::$instance;
    }


    public function __construct(SfRequest $request = null)
    {
        $this->request  = new Request($request);
        $this->response = new Response();
        $this->router   = new Router();
        self::$instance = $this;
    }


    public function getRequest()
    {
        return $this->request;
    }


    public function getResponse()
    {
        return $this->response;
    }


    public function getRouter()
    {
        return $this->router;
    }


    public function getApp()
    {
        return [
            $this->router,
            $this->request,
            $this->response
        ];
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
            $request->attributes->add($matcher->match($request->getPathInfo()));

            $controller = $controllerResolver->getController($request);
            $arguments  = $argumentResolver->getArguments($request, $controller);
            $response   = call_user_func_array($controller, $arguments);

        }
        catch(Routing\Exception\ResourceNotFoundException $e)
        {
            $response = new SfResponse('Not Found', 404);
        }
        catch(\Exception $e)
        {
            $response = new SfResponse('An error occurred', 500);
        }

        if($response instanceof SfResponse)
        {
            $response->send();
        }
    }

}