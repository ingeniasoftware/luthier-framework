<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

if (!function_exists('route')) {
    
    /**
     * Generates the URL of the given route name
     *
     * @see \Luthier\Routing\RouteBuilderInterface::getRouteByName()
     *
     * @return string
     */
    function route(?string $name = null, array $args = [], bool $absoluteUrl = true)
    {
        if ($name === null) {
            return \Luthier\Framework::getContainer()->get('request')->getRequest()->attributes->get('_orig_route');
        }
        
        return \Luthier\Framework::getContainer()->get('router')->getRouteByName($name, $args, $absoluteUrl);
    }
    
}

if (!function_exists('url')) {
    
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
        return \Luthier\Framework::getContainer()->get('')->baseUrl($url);
    }   
    
}


if (!function_exists('redirect')) {
   
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
        \Luthier\Framework::getContainer()->get('response')->redirect($url, $status, $headers);
    }    
    
}

if (!function_exists('route_redirect')) {
    
    /**
     * Sets the current response as a RedirectResponse to the URL of the given
     * route name
     * 
     * @param string $name
     * @param array  $params
     * @param number $status
     * @param array  $headers
     * 
     * @return void
     */
    function route_redirect($name, $params = [], $status = 302, $headers = [])
    {
        \Luthier\Framework::getContainer()->get('response')->routeRedirect($name, $params, $status, $headers);
    }    
    
}

if (!function_exists('json')) {
    
    /**
     * Sets the current response as a JsonResponse with the given array of data
     * 
     * @param array  $data
     * @param number $status
     * @param array  $headers
     * 
     * @return void
     */
    function json_response(array $data, $status = 200, $headers = [])
    {
        \Luthier\Framework::getContainer()->get('response')->json($data, $status, $headers);
    }    
    
}

if (!function_exists('xml_response')) {
    
    /**
     * Sets the current response as a XML response with the given array of data
     *
     * @param array  $data
     * @param number $status
     * @param array  $headers
     *
     * @return void
     */
    function xml_response(array $data, ?string $rootName = null, ?bool $translateSpaces = true, $status = 200, $headers = [])
    {
        \Luthier\Framework::getContainer()->get('response')->xml($data, $rootName, $translateSpaces, $status, $headers);
    }
    
}

if (!function_exists('view')) {
    
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
        \Luthier\Framework::getContainer()->get('template')->render($template, $vars, $return);
    }
    
}

if (!function_exists('csrf_field')) {
    
    /**
     * Renders a hidden HTML input tag with the CSRF field
     *
     * @return string
     */
    function csrf_field()
    {

    }
    
}
