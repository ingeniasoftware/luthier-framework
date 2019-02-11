<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 * 
 * To my friend Mariana C., because all that I've learned it's thanks to you.
 */

namespace Luthier;

use Symfony\Component\HttpKernel;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

/**
 * Luthier Framework application main object
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Framework
{
    use UtilsTrait;

    const VERSION = '0.1.0';

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $sfRequest;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var string
     */
    protected $appPath;

    /**
     * @var \Whoops\Run
     */
    protected $whoops;

    /**
     * @var \Luthier\Routing\RouteBuilderInterface
     */
    public static $router;

    /**
     * @var \Luthier\Http\RequestInterface
     */
    public static $request;

    /**
     * @var \Luthier\Http\ResponseInterface
     */
    public static $response;

    /**
     * @var \Luthier\Templating\Driver\TemplateDriverInterface
     */
    public static $template;

    /**
     * @param mixed     $config    Application configuration
     * @param Container $container Application container
     * @param SfRequest $request   Symfony request 
     * @param string    $appPath   Application base path (by default the current working directory)
     * 
     * @throws \Exception
     */
    public function __construct($config = null, ?Container $container = null, ?SfRequest $request = null, ?string $appPath = null)
    {
        $this->container = $container ?? new Container();
        $this->sfRequest = $request ?? SfRequest::createFromGlobals();
        $this->appPath = $appPath ?? getcwd();

        if (is_array($config) || $config === null) {
            $this->config = new Configuration($config);
        } else if ($config instanceof Configuration) {
            $this->config = $config;
        } else {
            throw new \Exception("You must provide an array of configuration values or an instance of the Luthier\Configuration class");
        }

        // At this point, only a few services are required, the rest will be
        // loaded during the application startup
        foreach (['router','dispatcher','response','request'] as $name) {
            if (! $this->container->has($name)) {
                [$type,$class,$aliases] = Container::getDefaultContainer()[$name];
                $this->container->{$type}($name, $class, $aliases);
            }
        }

        self::$router = $this->container->get('router');
        self::$request = $this->container->get('request');
        self::$response = $this->container->get('response');

        require __DIR__ . '/Helpers.php';
    }

    /**
     * __call() magic method
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->container->get('router'),$method], $args);
    }

    /**
     * __get() magic method
     *
     * @return mixed
     */
    public function __get($property)
    {
        if ($this->container->has($property)) {
            return $this->container->get($property);
        } else {
            throw new \Exception("Trying to get undefined property Luthier\Framework::$property");
        }
    }

    /**
     * Sets the application Symfony request
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Symfony Request object
     *
     * @return self
     */
    public function setRequest(SfRequest $request)
    {
        $this->sfRequest = $request;
        return $this;
    }

    /**
     * Sets the application dependency container
     *
     * @param \Luthier\Container $container The dependency injection container
     *
     * @return mixed
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Sets the application configuration
     *
     * @param  Configuration  $config
     *
     * @return self
     */
    public function setConfig(Configuration $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Luthier Framework error handler
     * 
     * @param int    $type 
     * @param string $message
     * @param string $file
     * @param string $line
     * 
     * @internal
     * 
     * @return int
     */
    public function errorHandler($level, $message, $file = null, $line = null)
    {
        $error = $message;

        if (! empty($file)) {
            $error .= ' at ' . $file . ' on line ' . $line;
        }

        $this->container->get('logger')->error($error);

        if ($this->whoops === null) {
            $this->errorResponse($this->sfRequest)->send();
        } else {
            $this->whoops->handleError($level, $message, $file, $line);
        }

        exit(1);
    }

    /**
     * Internal exception handler
     * 
     * @param \Exception $exception
     * 
     * @internal
     * @return void
     */
    public function exceptionHandler($exception)
    {
        $error = 'Uncaught exception ' . get_class($exception) . ': ' . $exception->getMessage() . ' at ' . $exception->getFile() . ' on line ' . $exception->getLine() . PHP_EOL . 'Stack trace: ' . PHP_EOL . $exception->getTraceAsString();
        $this->container->get('logger')->error($error);

        if ($this->whoops === null) {
            $this->errorResponse($this->sfRequest)->send();
        } else {
            $this->whoops->handleException($exception);
        }

        exit(2);
    }

    /**
     * Shutdown handler
     * 
     * @internal
     * @return void
     */
    public function shutdownHandler()
    {
        if ($error = error_get_last() and $error !== null && $error['type'] !== E_STRICT) {
            $this->errorHandler($error['type'], $error['message']);
        }
        exit(0);
    }

    /**
     * Configures the application
     * 
     * @throws \Exception
     * 
     * @internal
     * @return void
     */
    private function configure()
    {
        // Configuration
        $config = $this->config->parse();

        foreach ($config as $name => $value) {
            $this->container->parameter($name, $value);
        }

        // Container services/factories/parameters definition
        foreach (Container::getDefaultContainer() as $name => [$type,$class,$locatorAliases]) {
            if (! $this->container->has($name)) {
                $this->container->{$type}($name, $class, $locatorAliases);
            }
        }

        // Our container private services will be available as typehinted arguments
        // within the Router/RequestHandler
        $this->container->parameter('@PRIVATE_SERVICES', $this->container->getPrivateAliases());
        
        // Adding the (core) Request event subscriber
        $this->dispatcher->addSubscriber(new Http\EventSubscriber\RequestSubscriber());

        // The translation service is a special case: we use the Symfony translator component internally,
        if (! $this->container->has('translator')) {
            $this->container->service('translator', new \Symfony\Component\Translation\Translator($config['APP_LANG']), []);
        }
        
        if (! ($this->container->get('translator') instanceof \Symfony\Component\Translation\Translator)) {
            throw new \Exception("The translation service must be an instance of " . \Symfony\Component\Translation\Translator::class . "class");
        }        

        // PHP runtime configuration
        switch ($config['APP_ENV']) {
            case 'development':
                error_reporting(- 1);
                ini_set('display_errors', 1);
                break;
            case 'production':
                error_reporting(0);
                ini_set('display_errors', 0);
                break;
            default:
                throw new \Exception('The application environment is not configured correctly');
        }

        // Error/exception reporting configuration
        if (! defined('LUTHIER_TEST_MODE') && $config['APP_ENV'] == 'development') {

            $this->whoops = new \Whoops\Run();

            $handler = ! $this->isCli() 
                ? new \Whoops\Handler\PrettyPageHandler() 
                : new \Whoops\Handler\PlainTextHandler();

            $this->whoops->pushHandler($handler);
        }

        set_error_handler([$this,'errorHandler']);
        set_exception_handler([$this,'exceptionHandler']);
        register_shutdown_function([$this,'shutdownHandler']);

        // We need to know the application current working directory. By default,
        // it's the value returned by the getcwd() function, unless it has been defined as the
        // 'APP_PATH' parameter in the container or provided in the class constructor
        if (! $this->container->has('APP_PATH') || ($this->container->has('APP_PATH') && empty($this->container->get('APP_PATH')))) {
            $this->container->parameter('APP_PATH', $this->appPath);
        }

        self::$template = $this->container->get('template');
    }

    /**
     * Runs the application
     *
     * @return void
     */
    public function run()
    {
        // Lets configure our application...
        $this->configure();

        $this->logger->debug('Luthier Framework v' . self::VERSION . ' (PHP ' . phpversion() . ') APP_PATH="' . $this->container->get('APP_PATH') . '"', ['CORE']);

        if (! $this->isCli()) {
            $this->runHttp();
        } else {
            $this->runCli();
        }
    }

    /**
     * Runs the application in HTTP mode
     * 
     * @return void
     */
    private function runHttp()
    {
        $requestHandler = $this->container->get('request_handler');

        if (! empty($this->container->get('APP_CACHE'))) {
            $cacheFolder = $this->container->get('APP_PATH') . '/' . $this->container->get('APP_CACHE');

            if (! file_exists($cacheFolder)) {
                mkdir($cacheFolder . '/http', null, true);
            }

            $this->logger->debug('HttpKernel cache folder set to "' . $cacheFolder . '/http"', ['CORE']);
            $requestHandler = new HttpKernel\HttpCache\HttpCache($requestHandler, new HttpKernel\HttpCache\Store($cacheFolder . '/http'));
        }

        $requestHandler->handle($this->sfRequest)->send();
    }

    /**
     * Runs the application in CLI mode
     * 
     * @return void
     */
    private function runCli()
    {
        $commandLoader = new FactoryCommandLoader($this->container->get('router')->getCommands());
        $application = new Application();
        $application->setCommandLoader($commandLoader);
        $application->run();
    }
}

