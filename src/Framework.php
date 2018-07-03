<?php

/**
 * Luthier Framework
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Http\{Request, Response, ResponseIterator};
use Luthier\Routing\Router;
use Symfony\Component\HttpFoundation\{Request as SfRequest, Response as SfResponse};
use Symfony\Component\{HttpKernel, Routing};
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use Symfony\Component\Routing\Exception\ResourceNotFoundExceptio;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Framework
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
     * App environment folder
     *
     * @var $envFolder
     *
     * @access protected
     */
    protected $envFolder;


    /**
     * App Dependency Injection container
     *
     * @var $container
     *
     * @access protected
     */
    protected $container;


    /**
     * Class constructor
     *
     * @param  ?Container   $container DI container
     * @param  ?SfRequest   Symfony Request
     * @param  ?SfResponse  Symfony Response
     * @param  string       $envFolder  Directory where is located the app .env file
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct(?Container $container = null, ?SfRequest $request = null, ?SfResponse $response = null, string $envFolder = '')
    {
        $this->container = $container === NULL
            ? new Container()
            : $container;

        $this->envFolder = $envFolder;
        $this->request   = new Request($request);
        $this->response  = new Response($response);

        // Whoops!
        (new \Whoops\Run)->pushHandler(new \Whoops\Handler\PrettyPageHandler())
            ->register();

        $requiredContainers = [
            'router' => Router::class
        ];

        foreach($requiredContainers as $name => $class)
        {
            if(!$this->container->has($name))
            {
                if($container !== NULL)
                {
                    throw new \Exception("Your custom container MUST have the '$name' service (instance of $class)");
                }
                $this->container->service('router', Router::class, true);
            }
        }

        // Set the app instance as static variable
        self::$instance = &$this;
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
     */
    public function __call($method, $args)
    {
        $dispatched = $this->router->getCurrentRoute() !== NULL;

        // Route definition methods
        if(in_array(strtoupper($method), Router::HTTP_VERBS) || in_array($method, ['match', 'group', 'middleware', 'cli']))
        {
            return call_user_func_array([$this->container->get('router'), $method], $args);
        }

        throw new \BadMethodCallException("Undefined method Luthier\Framework::{$method}() ");
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
        // Does the property exists?
        if(isset($this->{$property}))
        {
            return $this->{$property};
        }
        // Is the property registered as a service/property in the DI container?
        else if($this->container->has($property))
        {
            return $this->container->get($property);
        }
        // Is the property named "route"? Return the current route
        else if($property == 'route')
        {
            return $this->container->get('router')->getCurrentRoute();
        }
        else
        {
            throw new \Exception("Undefined property Luthier\Framework::$property");
        }
    }


    /**
     * Get app instance
     *
     * @return Request
     *
     * @access public
     * @static
     */
    public static function getInstance()
    {
        return self::$instance;
    }


    /**
     * Get app .env folder
     *
     * @return string
     *
     * @access public
     */
    public function getEnvFolder()
    {
        return $this->envFolder;
    }


    /**
     * Set app .env folder
     *
     * @param  string $envFolder
     *
     * @return string
     *
     * @access public
     */
    public function setEnvFolder(string $envFolder)
    {
        $this->envFolder = $envFolder;
    }


    /**
     * Get app DI container
     *
     * @return Container
     *
     * @access public
     */
    public function getContainer()
    {
        return $this->container;
    }


    /**
     * Set app DI container
     *
     * @param  Container    $container
     *
     * @return mixed
     *
     * @access public
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }


    /**
     * Load (optional) useful helpers
     *
     * @return void
     *
     * @access public
     */
    public function withHelperFunctions(): void
    {
        require_once __DIR__ . '/Helpers.php';
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
        $router = $this->container->get('router');

        try
        {
            (new Dotenv())->load( ($this->envFolder !== NULL ? $this->envFolder : '') . '.env' );
        }
        catch(PathException $e)
        {
            (new SfResponse('Error: Unable to find your application .env file. Does the file exists?', 500))->send();
            exit(-1);
        }
        catch(\Exception $e)
        {
            (new SfResponse('Error: Unable to parse your application .env file', 500))->send();
            exit(-1);
        }

        $environment = getenv('APP_ENV');

        if(!in_array($environment, ['development','production'], TRUE))
        {
            (new SfResponse('Error: The application configuration not valid. Check your .env file', 500))->send();
            exit(-1);
        }

        $context = new Routing\RequestContext();

        $router->setRequestContext($context);
        $router->middleware('ajax', Http\Middleware\AjaxMiddleware::class);
        $router->middleware('cli', Http\Middleware\CliMiddleware::class);

        try
        {
            // Matching the current url to a route and setting up their attributes
            $match = (
                new Routing\Matcher\UrlMatcher(
                    $router->compile(),
                    $context->fromRequest($this->request->getRequest())
                )
            )->match($this->request->getRequest()->getPathInfo());

            $this->request->getRequest()->attributes->add($match);

            $controller = (new HttpKernel\Controller\ControllerResolver())
                ->getController($this->request->getRequest());

            $arguments  = (new HttpKernel\Controller\ArgumentResolver())
                ->getArguments($this->request->getRequest(), $controller);

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
            $offset = 0;

            foreach( explode('/', trim($this->request->getRequest()->getPathInfo(), '/')) as $i => $urlSegment )
            {
                $routeSegment = explode('/', $route->getFullPath())[$i];
                if(substr($routeSegment,0,1) == '{' && substr($routeSegment,-1) == '}')
                {
                    $route->params[$offset]->value = $urlSegment;
                    $offset++;
                }
            }

            $router->setCurrentRoute($route);

            // Prepare the response
            $responseIterator = new ResponseIterator($this->request, $this->response, $route, $controller, $arguments);
            $response = $responseIterator->dispatch();
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

        if(!$response instanceof SfResponse)
        {
            $response = $this->response->getResponse();
        }

        // ...finally, send the response:
        $response->send();
    }
}