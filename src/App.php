<?php

/**
 * Luthier Framework
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Http\{Request, Response, ResponseIterator};
use Luthier\Routing\{Router, RouteBuilder};
use Symfony\Component\HttpFoundation\{Request as SfRequest, Response as SfResponse};
use Symfony\Component\HttpKernel;
use Symfony\Component\Routing;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use Symfony\Component\Routing\Exception\ResourceNotFoundExceptio;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * Class constructor
     *
     * @param  string  $envDir Directory where look for the .env file used by the app
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct(string $envDir = null)
    {
        $dotenv = new Dotenv();

        try
        {
            $dotenv->load( ($envDir !== NULL ? $envDir : '') . '.env' );
        }
        catch(PathException $e)
        {
            (new SfResponse('Error: Unable to find your application environment file', 500))->send();
            exit(-1);
        }
        catch(\Exception $e)
        {
            (new SfResponse('Error: Unable to parse your application environment file', 500))->send();
            exit(-1);
        }

        $environment = getenv('APP_ENV');

        if(!in_array($environment, ['development','production'], TRUE))
        {
            (new SfResponse('Error: Your application environment configuration is missing or invalid', 500))->send();
            exit(-1);
        }

        // Loading essential functions
        require_once __DIR__ . '/Functions.php';

        // Adding essential objects to App singleton
        $this->request    = new Request();
        $this->response   = new Response();
        $this->router     = new Router();

        // Defining some basic middleware
        RouteBuilder::middleware('ajax', Http\Middleware\AjaxMiddleware::class);

        // Set the app instance as static variable
        self::$instance = &$this;

        // Whoops!
        (new \Whoops\Run)->pushHandler(new \Whoops\Handler\PrettyPageHandler())
            ->register();
    }


    /**
     * __call() magic method
     *
     * @param  string $method
     * @param  mixed $args
     *
     * @return mixed
     *
     * @access public
     * @throw \Exception
     */
    public function __call($method, $args)
    {
        // Is a HTTP Verb?
        if(in_array(strtoupper($method), RouteBuilder::HTTP_VERBS) || $method == 'match')
        {
            return call_user_func_array([$this->router, $method], $args);
        }
        // Is a RouteBuilder method?
        else if(in_array($method, ['group', 'middleware']))
        {
            // Calling a middleware inside a dispatched route? Dispatch it
            if($method == 'middleware' && $this->router->getCurrentRoute() !== NULL && count($args) == 0)
            {
                if(!is_string($args[0]))
                {
                    throw new \BadMethodCallException("Invalid middleware name");
                }

                return ResponseIterator::evaluateIntermediaryResponse(
                    call_user_func_array([$this->router,'runMiddleware'],[$args[0], $this->request, $this->response, function($request, $response){}])
                );
            }
            // If not, just call the RouteBuilder method
            return call_user_func_array([$this->router, $method], $args);
        }
        else
        {
            throw new \BadMethodCallException("Undefined method App::{$method}() ");
        }
    }


    /**
     * __get() magic method
     *
     * @param  string  $property
     *
     * @return mixed
     *
     * @access public
     * @throw \Exception
     */
    public function __get($property)
    {
        // Getting a protected/private property of this class?
        if(isset($this->{$property}))
        {
            return $this->{$property};
        }
        // Getting the "route" property? Return the current route
        else if($property == 'route')
        {
            return $this->router->getCurrentRoute();
        }
        else
        {
            throw new \InvalidArgumentException("Undefined property App::$property ");
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
     * Run the application and dispatches the response
     *
     * @return mixed
     *
     * @access public
     */
    public function run()
    {
        $request   = $this->request;
        $sfRequest = $request->getSfRequest();
        $response  = $this->response;
        $requestContext = new Routing\RequestContext();

        try
        {
            // Matching the current url to a route and setting up their attributes
            $match = (
                new Routing\Matcher\UrlMatcher(
                    ($this->router)($requestContext),
                    $requestContext->fromRequest($sfRequest)
                )
            )->match($sfRequest->getPathInfo());

            $sfRequest->attributes->add($match);

            $controller = (new HttpKernel\Controller\ControllerResolver())
                ->getController($sfRequest);

            $arguments  = (new HttpKernel\Controller\ArgumentResolver())
                ->getArguments($sfRequest, $controller);

            // Removing NULL arguments used in the callback to allow default arguments
            // values in the route definitions
            foreach($arguments as $i => $arg)
            {
                if($arg === null)
                {
                    unset($arguments[$i]);
                }
            }

            $route = $match['_instance'];

            // Now we assign the matched route parameters values from the url
            $paramOffset = 0;
            foreach( explode('/', trim($sfRequest->getPathInfo(), '/')) as $i => $urlSegment )
            {
                $routeSegment = explode('/', $route->getFullPath())[$i];
                if(substr($routeSegment,0,1) == '{' && substr($routeSegment,-1) == '}')
                {
                    $route->params[$paramOffset]->value = $urlSegment;
                    $paramOffset++;
                }
            }

            $this->router->setCurrentRoute($route);

            // Prepare and dispatch the response
            $responseIterator = new ResponseIterator($request, $response, $route, $controller, $arguments);
            $responseResult   = $responseIterator->dispatch();
        }
        catch(ResourceNotFoundException|NotFoundHttpException $e)
        {
            if(getenv('APP_ENV') == 'development')
            {
                throw $e;
            }

            return (new SfResponse('Not Found', 404))->send();
        }
        catch(\Exception $e)
        {
            if(getenv('APP_ENV') == 'development')
            {
                throw $e;
            }

            return (new SfResponse('An error occurred', 500))->send();
        }

        // Is the response result a Symfony Response object? send it directly
        if($responseResult instanceof SfResponse)
        {
            return $responseResult->send();
        }

        // If not, just send the response content stored at this point
        $response->getSfResponse()->send();
    }
}