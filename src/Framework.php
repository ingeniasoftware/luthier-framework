<?php

/**
 * Luthier Framework
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

use Luthier\Http\{Request, Response};
use Luthier\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\{Request as SfRequest, Response as SfResponse};
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;

class Framework
{
    const VERSION = '0.1.0a';

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
     * App config array
     *
     * @var $config
     *
     * @access protected
     */
    protected $config = [];



    /**
     * Class constructor
     *
     * @param  ?string      $config  App configuration
     * @param  ?Container   $container DI container
     * @param  ?SfRequest   Symfony Request
     * @param  ?SfResponse  Symfony Response
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct($config = null, ?Container $container = null, ?SfRequest $request = null, ?SfResponse $response = null)
    {
        // Registering Whoops!
        (new \Whoops\Run)->pushHandler(new \Whoops\Handler\PrettyPageHandler())
            ->register();

        $this->container = $container === NULL
            ? new Container()
            : $container;

        if(is_string($config))
        {
            $this->envFolder = $envFolder;
        }
        else if(is_array($config) || $config === NULL)
        {
            $this->config = $config !== NULL
                ? $config
                : [];
        }
        else
        {
            throw new \Exception("Invalid application configuration format: must be an array or a string");
        }

        $this->request  = new Request($request);
        $this->response = new Response($response);

        $reqContainers = [
            'router'     => Router::class,
            'dispatcher' => EventDispatcher::class
        ];

        foreach($reqContainers as $name => $class)
        {
            if(!$this->container->has($name))
            {
                if($container !== NULL)
                {
                    throw new \Exception("Your custom container MUST have the '$name' service (instance of $class)");
                }
                $this->container->service($name, $class, true);
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
        if(in_array(strtoupper($method), Router::HTTP_VERBS) || in_array($method, ['match', 'group', 'middleware', 'command']))
        {
            return call_user_func_array([$this->container->get('router'), $method], $args);
        }

        throw new \BadMethodCallException("Call to undefined method Luthier\Framework::{$method}() ");
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
     * Get app config array
     *
     * @return mixed
     *
     * @access public
     */
    public function getConfig()
    {
        return $this->config;
    }


    /**
     * Set app .env folder
     *
     * @param  string $envFolder
     *
     * @return self
     *
     * @access public
     */
    public function setEnvFolder(string $envFolder)
    {
        $this->envFolder = $envFolder;
        return $this;
    }


    /**
     * Set app DI container
     *
     * @param  Container $container
     *
     * @return self
     *
     * @access public
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }


    /**
     * Set the Symfony request
     *
     * @param  SfRequest    $request
     *
     * @return self
     *
     * @access public
     */
    public function setRequest(SfRequest $request)
    {
        $this->request = $request;
        return $this;
    }


    /**
     * Set the Symfony response
     *
     * @param  SfResponse   $response
     *
     * @return mixed
     *
     * @access public
     */
    public function setResponse(SfResponse $response)
    {
        $this->response = $response;
        return $this;
    }



    /**
     * Set app config array
     *
     * @param  array        $config
     *
     * @return mixed
     *
     * @access public
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }


    /**
     * Load (optional) useful helper functions
     *
     * @return void
     *
     * @access public
     */
    public function withHelpers(): void
    {
        require_once __DIR__ . '/Helpers.php';
    }


    /**
     * Configures the app and run it
     *
     * @return mixed
     *
     * @access public
     */
    public function run()
    {
        $reqConfig = [
            'APP_ENV',
        ];

        $defConfig = [
            'APP_ENV' => 'development',
        ];

        $config = [];

        if($this->envFolder !== NULL)
        {
            try
            {
                (new Dotenv())->load( ($this->envFolder !== NULL ? $this->envFolder : '') . '.env' );
            }
            catch(PathException $e)
            {
                throw new \Exception('Unable to find your application .env file. Does the file exists?');
            }
            catch(\Exception $e)
            {
                throw new \Exception('Unable to parse your application .env file');
            }

            foreach($reqConfig as $_name)
            {
                if(getenv($_name) === FALSE)
                {
                    throw new \Exception('Error: Missing ' . $_name . ' parameter in your .env file');
                }
                $config[$_name] = getenv($_name);
            }
        }
        else if(!empty($this->config))
        {
            foreach($reqConfig as $_name)
            {
                if(!isset($this->config[$_name]))
                {
                    throw new \Exception('Error: Missing ' . $_name . ' parameter in your application configuration array');
                }
                $config[$_name] = $this->config[$_name];
            }
        }
        else
        {
            $config = $defConfig;
        }

        foreach($config as $name => $value)
        {
            $this->container->parameter($name, $value, true);
        }

        if(!in_array($config['APP_ENV'], ['development','production'], TRUE))
        {
            throw new \Exception('The application environment is not valid');
        }

        error_reporting( $config['APP_ENV'] == 'development' ? E_ALL : 0 );
        ini_set('display_errors', $config['APP_ENV'] == 'development' ? 'on' : 'off' );

        $router = $this->container->get('router');

        if(!$router instanceof Router)
        {
            throw new \Exception('Error: Invalid router. MUST be an instance of the ' . Router::class . 'class');
        }

        $router->handle($this->request, $this->response);
    }
}
