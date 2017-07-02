<?php

/*
 * Luthier Framework
 *
 * (c) 2017 Ingenia Software C.A - Created by Anderson Salas
 *
 */

const LUTHIER_VERSION = 0.1;

require_once  __DIR__ . '/vendor/autoload.php' ;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;

$request = Request::createFromGlobals();
$routes  = new Routing\RouteCollection();

require_once __DIR__ . '/' . $application_folder . '/Config/routes.php';

$context = new Routing\RequestContext();
$context->fromRequest($request);
$matcher = new Routing\Matcher\UrlMatcher($routes, $context);

$controllerResolver = new HttpKernel\Controller\ControllerResolver();
$argumentResolver   = new HttpKernel\Controller\ArgumentResolver();

$app = new Luthier\Framework($matcher, $controllerResolver, $argumentResolver);


$response = $app->handle($request);

$response->send();