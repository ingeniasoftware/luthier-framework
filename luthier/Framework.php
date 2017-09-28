<?php

/*
 * Luthier Framework
 *
 * (c) 2017 Ingenia Software C.A - Created by Anderson Salas
 *
 */

namespace Luthier;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class Framework
{
    protected $matcher;
    protected $controllerResolver;
    protected $argumentResolver;

    public function __construct(UrlMatcher $matcher, ControllerResolver $controllerResolver, ArgumentResolver $argumentResolver)
    {
        $this->matcher = $matcher;
        $this->controllerResolver = $controllerResolver;
        $this->argumentResolver = $argumentResolver;
    }

    public function handle(Request $request)
    {
        $this->matcher->getContext()->fromRequest($request);

        // TODO: Add JSON response errors
        // TODO: Add CLI response erros

        try
        {
            $request->attributes->add($this->matcher->match($request->getPathInfo()));

            $controller = $this->controllerResolver->getController($request);
            $arguments = $this->argumentResolver->getArguments($request, $controller);

            return call_user_func_array($controller, $arguments);
        }
        catch (ResourceNotFoundException $e)
        {
            if(ENVIRONMENT != 'production')
                throw $e;

            ob_start();
            include VIEW_PATH . '/Internals/404.php';
            $_404 = ob_get_clean();

            return new Response($_404, 404);
        }
        catch (\Exception $e)
        {
            if(ENVIRONMENT != 'production')
                throw $e;

            return new Response('An error occurred', 500);
        }
    }
}