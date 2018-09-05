<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

/**
 * Generates the URL of the given route name
 * 
 * @see \Luthier\Routing\RouteBuilderInterface::getRouteByName()
 * 
 * @return string
 */
function route(?string $name = null, array $args = [], bool $absoluteUrl = true)
{
    if($name === null)
    {
        return \Luthier\Framework::$request->getRequest()->attributes->get('_orig_route');
    }
    
    return \Luthier\Framework::$router->getRouteByName($name, $args, $absoluteUrl);
}

/**
 * Generates an application URL
 * 
 * @param string $url
 * 
 * @see \Luthier\Http\RequestInterface::baseUrl()
 * 
 * @return string
 */
function url(string $url = '')
{
    return \Luthier\Framework::$request->baseUrl($url);
}

/**
 * Sets the current response as a RedirectResponse to the given URL and
 * parameters
 * 
 * @param string $url
 * @param int    $status
 * @param array  $headers
 * 
 * @return void
 */
function redirect(string $url = '', int $status = 302, array $headers = [])
{
    \Luthier\Framework::$response->redirect($url, $status, $headers);
}

/**
 * Sets the current response as a RedirectResponse to the URL of the given
 * route name
 * 
 * @param string  $name
 * @param array   $params
 * @param number  $status
 * @param array   $headers
 * 
 * @return void
 */
function route_redirect($name, $params = [], $status = 302, $headers = [])
{
    \Luthier\Framework::$response->routeRedirect($name, $params, $status, $headers);
}

/**
 * Sets the current response as a JsonResponse with the given array of data
 * 
 * @param array   $data
 * @param number  $status
 * @param array   $headers
 * 
 * @return void
 */
function json_response(array $data, $status = 200, $headers = [])
{
    \Luthier\Framework::$response->json($data, $status, $headers);
}

/**
 * Renders a template
 * 
 * @param string $template
 * @param array  $vars
 * @param bool   $return
 * 
 * @return string|null
 */
function view(string $template, array $vars = [], $return = false)
{
    return \Luthier\Framework::$template->render($template, $vars, $return);  
}
