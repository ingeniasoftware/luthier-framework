<?php

/**
 * ResponseIterator class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http;

use Luthier\App;
use Luthier\Routing\{Route,RouteBuilder};
use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ResponseIterator
{
    /**
     * Symfony Request object
     *
     * @var $request
     *
     * @access private
     */
    private $request;


    /**
     * Symfony Response object
     *
     * @var $response
     *
     * @access private
     */
    private $response;


    /**
     * Sub-request count
     *
     * @var static $count
     *
     * @access private
     */
    private static $count = 0;


    /**
     * The current middleware queue
     *
     * @var static $middlewareQueue
     *
     * @access private
     */
    private static $middlewareQueue = [];


    /**
     * The route (final) response callback to be invoked
     *
     * @var static $routeResponse
     *
     * @access private
     */
    private static $routeResponse;


    /**
     * Class constructor
     *
     * @param  Request      $request
     * @param  Response     $response
     * @param  Route        $route
     * @param  callable     $controller
     * @param  array        $arguments
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct(Request $request, Response $response, Route $route, callable $controller, array $arguments)
    {
        $this->request  = $request;
        $this->response = $response;

        self::$middlewareQueue = array_merge(RouteBuilder::getContext('middleware')['global'], $route->getMiddleware());
        self::$routeResponse   = [$controller, $arguments];
    }


    /**
     * Dispatch the initial response
     *
     * If no middleware present, only the route callback will be invoked and returned
     *
     * @return mixed
     *
     * @access public
     */
    public function dispatch()
    {
        if(count(self::$middlewareQueue) > 0)
        {
            [$request, $response] = [$this->request, $this->response];

            $middleware = RouteBuilder::getMiddleware(self::$middlewareQueue[0]);

            return self::evaluateIntermediaryResponse(call_user_func_array($middleware,[$request, $response, function($request,$response){
                return \Luthier\Http\ResponseIterator::next($request, $response);
            }]));
        }
        else
        {
            [$controller, $method] = self::$routeResponse;
            return call_user_func_array($controller, $method);
        }
    }


    /**
     * Evaluates an intermediary response (for example, a middleware) and stops the
     * queue if necessary
     *
     * @param  mixed        $responseResult
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function evaluateIntermediaryResponse($responseResult)
    {
        $response = App::getInstance()->response->getSfResponse();

        if($responseResult instanceof SfResponse)
        {
            $responseResult->send();
            exit(1);
        }
        else if($response instanceof RedirectResponse)
        {
            $response->send();
            exit(1);
        }
        else
        {
            return $responseResult;
        }
    }


    /**
     * Callback to the next middleware in the queue (when is called inside a middleware)
     *
     * @param  mixed  $request
     * @param  mixed  $response
     *
     * @return callable
     *
     * @access public
     * @static
     */
    public static function next($request, $response)
    {
        [$callback, $action] = self::iterate();

        if($action !== NULL)
        {
            [$controller, $arguments] = $action;
            return call_user_func_array($controller, $arguments);
        }

        $middleware = RouteBuilder::getMiddleware($callback);

        return call_user_func_array($middleware, [$request, $response, function($request,$response){
            return \Luthier\Http\ResponseIterator::next($request, $response);
        }]);
    }


    /**
     * Return the next middleware in the queue
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function iterate()
    {
        return isset(self::$middlewareQueue[++self::$count])
            ? [ self::$middlewareQueue[self::$count] , NULL ]
            : [ NULL, self::$routeResponse ];
    }
}