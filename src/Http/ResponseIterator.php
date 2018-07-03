<?php

/**
 * ResponseIterator class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http;

use Luthier\Framework;
use Luthier\Container;
use Luthier\Application;
use Luthier\Routing\{Route,Router};
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
     * Middleware queue index
     *
     * @var static $index
     *
     * @access private
     */
    private static $index = 0;


    /**
     * The current middleware queue
     *
     * @var static $middlewareQueue
     *
     * @access private
     */
    private static $middlewareQueue = [];


    /**
     * The route (final) response callback to be dispatched to the framework
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

        self::$middlewareQueue = array_merge(Router::getContext('middleware')['global'], $route->getMiddleware());
        self::$routeResponse = [$controller, $arguments];
    }


    /**
     * Dispatch the initial response
     *
     * (If no middleware present, only the route callback will be dispatched)
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

            $middleware = Router::getMiddleware(self::$middlewareQueue[0]);

            return self::intermediaryResponse(call_user_func_array($middleware,[$request, $response, function($request,$response){
                return \Luthier\Http\ResponseIterator::next($request, $response);
            }]));
        }
        else
        {
            [$controller, $method] = self::$routeResponse;
            self::bindApp($controller);

            return call_user_func_array($controller, $method);
        }
    }


    /**
     * Evaluates a middleware response
     *
     * @param  mixed  $responseResult
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function intermediaryResponse($responseResult)
    {
        $response = Framework::getInstance()->response->getResponse();

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
     * Bind the current Application singleton to the closure
     *
     * @param  mixed  $controller (Passed by reference)
     *
     * @return mixed
     *
     * @access private
     * @static
     */
    private static function bindApp(&$controller)
    {
        if($controller instanceof \Closure)
        {
            $luthier = Framework::getInstance();

            $app = new Application(
                $luthier->getContainer()->getPsrContainer(),
                $luthier->route,
                $luthier->request,
                $luthier->response
            );

            $controller = \Closure::bind($controller, $app, Application::class);
        }
    }


    /**
     * Returns the current middleware in the queue
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function iterate()
    {
        return isset(self::$middlewareQueue[++self::$index])
            ? [ self::$middlewareQueue[self::$index] , NULL ]
            : [ NULL, self::$routeResponse ];
    }


    /**
     * Returns a callback of the next middleware in the queue based in the current
     * iterator index
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
        [$currentMiddleware, $finalResponse] = self::iterate();

        if($finalResponse !== NULL)
        {
            [$controller, $arguments] = $finalResponse;
            self::bindApp($controller);

            return call_user_func_array($controller, $arguments);
        }

        $middleware = Router::getMiddleware($currentMiddleware);

        return call_user_func_array($middleware, [$request, $response, function($request,$response){
            return \Luthier\Http\ResponseIterator::next($request, $response);
        }]);
    }
}