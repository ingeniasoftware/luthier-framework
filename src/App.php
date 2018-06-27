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
    const LUTHIER_VERSION = 1.0;

    /**
     * App instance object
     *
     * @var static $instance
     *
     * @access private
     */
    private static $instance;


    /**
     * App request object
     *
     * @var $request
     *
     * @access protected
     */
    protected $request;


    /**
     * App response object
     *
     * @var $response
     *
     * @access protected
     */
    protected $response;


    /**
     * App router object
     *
     * @var $router
     *
     * @access protected
     */
    protected $router;


    /**
     * __call() magic method
     *
     * @param  mixed $method
     * @param  mixed $args
     *
     * @return mixed
     *
     * @access public
     * @throw \Exception
     */
    public function __call($method, $args)
    {
        if(in_array(strtoupper($method), RouteBuilder::HTTP_VERBS) || $method == 'match')
        {
            return call_user_func_array([$this->router, $method], $args);
        }
        else if(in_array($method, ['group']))
        {
            return call_user_func_array([$this->router, $method], $args);
        }
        else
        {
            throw new \Exception("Undefined method App::{$method}() ");
        }
    }


    /**
     * __get() magic method
     *
     * @param  mixed  $property
     *
     * @return mixed
     *
     * @access public
     * @throw \Exception
     */
    public function __get($property)
    {
        if(isset($this->{$property}))
        {
            return $this->{$property};
        }
        else if($property == 'route')
        {
            return $this->router->getCurrentRoute();
        }
        else
        {
            throw new \Exception("Undefined property App::$property ");
        }
    }


    /**
     * Get current APP instance
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function getInstance()
    {
        return self::$instance;
    }


    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct()
    {
        $this->request  = new Request();
        $this->response = new Response();
        $this->router   = new Router();
        self::$instance = &$this;

        require_once __DIR__ . '/Functions.php';
    }


    /**
     * Run the application
     *
     * @param  SfRequest $request Custom request object (optional)
     *
     * @return mixed
     *
     * @access public
     */
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
            $matchedUrl  = $matcher->match($request->getPathInfo());
                           $request->attributes->add($matchedUrl);

            $route = $matchedUrl['_instance'];
            $this->router->setCurrentRoute($route);

            $controller = $controllerResolver->getController($request);
            $arguments  = $argumentResolver->getArguments($request, $controller);

            foreach($arguments as $i => $arg)
            {
                if($arg === null)
                {
                    unset($arguments[$i]);
                }
            }

            $paramOffset = 0;

            foreach( explode('/', trim($request->getPathInfo(), '/')) as $i => $urlSegment )
            {
                $routeSegment = explode('/', $route->getFullPath())[$i];
                if(substr($routeSegment,0,1) == '{' && substr($routeSegment,-1) == '}')
                {
                    $route->params[$paramOffset]->value = $urlSegment;
                    $paramOffset++;
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