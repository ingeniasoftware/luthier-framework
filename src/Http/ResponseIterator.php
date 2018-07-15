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
 * Iterates over the current route request middleware and finally
 * and finally returns a response
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
     * Stack of request middleware
     * 
     * @var array
     */
    private static $stack;

    /**
     * Array with the callback/arguments of the matched route (the final response)
     * 
     * @var array
     */
    private static $callback;

    /**
     * Handles the provided request stacks and returns a response
     * 
     * @param array      $stack      Request stack
     * @param callable   $callback   Route callback
     * @param array      $arguments  Route arguments
     * @param Request    $request    Luthier request object
     * @param Response   $response   Luthier response object
     * 
     * @return \Symfony\Component\HttpFoundation\Response|mixed
     */
    public static function handle(array $stack, callable $callback, array $arguments, Request $request, Response $response)
    {
        self::$index = 1;
        self::$stack = $stack;
        self::$callback = [$callback, $arguments];
                
        if(count(self::$stack) > 0)
        {
            $middleware = RouteBuilder::getMiddleware(self::$stack[0]);
            $middlewareResponse = $middleware($request, $response, function($request, $response){
                return \Luthier\Http\ResponseIterator::next($request, $response);
            });
            Response::getRealResponse($middlewareResponse, $response);
        }
        else
        {
            [$callback, $arguments] = self::$callback;
            Response::getRealResponse(call_user_func_array($callback, $arguments), $response);
        }
    }
        
    /**
     * Returns the current middleware in the queue, or the final response
     * if there is not no more middleware left 
     *  
     * @return array ([?callable $currentMiddleware, ?array $finalResponse])
     */
    public static function iterate()
    {
        return isset(self::$stack[++self::$index])
            ? [ self::$stack[self::$index] , NULL ]
            : [ NULL, self::$callback ];
    }

    /**
     * Returns a callback of the next middleware in the queue based in the current
     * response iterator index
     * 
     * @param Request $request
     * @param Response $response
     * 
     * @return callable|null
     */
    public static function next(Request $request, Response $response)
    {
        if($response->getResponse() instanceof RedirectResponse)
        {
            return;
        }

        [$currentMiddleware, $finalResponse] = self::iterate();

        if($finalResponse !== NULL)
        {
            [$callback, $arguments] = $finalResponse;
            return call_user_func_array($callback, $arguments);
        }
        else
        {
            $middleware = RouteBuilder::getMiddleware($currentMiddleware);
            return $currentMiddleware($request, $response, function($request,$response){
                return \Luthier\Http\ResponseIterator::next($request, $response);
            });
        }
    }
}