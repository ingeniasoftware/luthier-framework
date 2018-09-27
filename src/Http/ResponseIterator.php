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

use Luthier\Routing\RouteBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Iterates over the given middleware stack and then executes the main 
 * route controller/closure callback. 
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class ResponseIterator
{
    /**
     * @var int
     */
    private static $index;

    /**
     * @var array
     */
    private static $stack;

    /**
     * @var array
     */
    private static $callback;
    
    /**
     * @var \Closure
     */
    private static $router;
    
    /**
     * @param string|callable $middleware
     * 
     * @return \Closure
     */
    private static function getMiddleware($middleware)
    {
        return self::$router->getMiddleware($middleware);   
    }
    
    /**
     * Iterates over the provided request stack
     * 
     * @param array     $stack     Request stack
     * @param callable  $callback  Route callback
     * @param array     $arguments Route arguments
     * @param Request   $request   Luthier request
     * @param Response  $response  Luthier response
     * 
     * @return \Symfony\Component\HttpFoundation\Response|mixed
     */
    public static function handle(RouteBuilder $router, array $stack, callable $callback, array $arguments, Request $request, Response $response)
    {
        self::$router   = $router;
        self::$index    = 0;
        self::$stack    = $stack;
        self::$callback = [$callback, $arguments];   
        
        if (count($stack) > 0) {      
            $middleware = self::getMiddleware(self::$stack[0]);
            Response::getRealResponse($middleware($request, $response, function($request, $response){
                return \Luthier\Http\ResponseIterator::next($request, $response);
            }), $response);
        } else {
            Response::getRealResponse($callback(...$arguments), $response);
        }
    }
        
    /**
     * Returns the current middleware in the queue, or null if no more middleware
     * left
     *  
     * @return array callable|null
     */
    public static function iterate()
    {
        return isset(self::$stack[++self::$index])
            ? self::$stack[self::$index]
            : null;
    }

    /**
     * Returns a callable of the next middleware in the queue based in the current
     * iterator index
     * 
     * @param Request $request
     * @param Response $response
     * 
     * @return callable|null
     */
    public static function next(Request $request, Response $response)
    {
        if ($response->getResponse() instanceof RedirectResponse) {
            return;
        }

        $middleware = self::iterate();

        if ($middleware === NULL) {
            [$callback, $arguments] = self::$callback;
            return $callback(...$arguments);
        } else {
            $middleware = self::getMiddleware($middleware);
            return $middleware($request, $response, function($request,$response){
                return \Luthier\Http\ResponseIterator::next($request, $response);
            });
        }
    }
}