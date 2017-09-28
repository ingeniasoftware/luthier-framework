<?php

/*
 * Luthier Framework
 *
 * (c) 2017 Ingenia Software C.A - Created by Anderson Salas
 *
 */

/*
 *  Constant definitions
 */

define('LUTHIER_VERSION', 0.1) ;
define('APP_PATH', __DIR__ . '/../' . $application_folder);
define('CONFIG_PATH', APP_PATH . '/Config' );
define('VIEW_PATH', APP_PATH . '/Views' );
define('SYS_PATH', __DIR__ . '/../' . $system_folder);
define('ROOT_PATH', dirname(__DIR__));

/*
 *  Loading Symfony components
 */

require_once  __DIR__ . '/../vendor/autoload.php' ;

// TODO: Parse .ENV file
// TODO: Twig
// TODO: Autoload

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing;
use Symfony\Component\HttpKernel;
use Symfony\Component\Debug;
use Symfony\Component\Dotenv\Dotenv;

/*
 *  Setting up the application environment
 */

$dotenv = new Dotenv();

if(!file_exists( ROOT_PATH . '/.env'))
{
    echo 'Luthier startup error: No .env configuration file available for the application';
    exit(1);
}

try
{
    $dotenv->load( ROOT_PATH . '/.env' );
}
catch(Exception $e)
{
    echo 'Luthier startup error: ' . $e->getMessage();
    exit(1);
}

$environment = getenv('ENVIRONMENT');

switch($environment)
{
    case 'development':
        Debug\Debug::enable();
        Debug\ErrorHandler::register();
        Debug\ExceptionHandler::register();
        break;
    case 'testing':
    case 'production':
        Debug\ErrorHandler::register();
        Debug\ExceptionHandler::register();
        break;
    default:
        echo 'The application enviroment isn\'t configured properly';
        exit(1);
}

define('ENVIRONMENT', $environment);

$request = Request::createFromGlobals();

/*
 *  Loading Routes
 */

require_once CONFIG_PATH . '/routes.php';

$routes = Luthier\Route::getCompiledRoutes();

if(count($routes) == 0)
{
    // Show welcome message if any route is defined

    ob_start();
    include VIEW_PATH . '/WelcomeMessage.php';
    $welcome_message = ob_get_clean();

    $response = new Response($welcome_message);
    $response->send();
    exit(1);
}

/*
 *  Load framework and handle response
 */

$context = new Routing\RequestContext();
$context->fromRequest($request);
$matcher = new Routing\Matcher\UrlMatcher($routes, $context);

$controllerResolver = new HttpKernel\Controller\ControllerResolver();
$argumentResolver   = new HttpKernel\Controller\ArgumentResolver();

$app = new Luthier\Framework($matcher, $controllerResolver, $argumentResolver);

$response = $app->handle($request);

$response->send();