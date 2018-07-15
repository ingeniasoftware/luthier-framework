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

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpFoundation\{RedirectResponse, JsonResponse, StreamedResponse, BinaryFileResponse};

/**
 * Wrapper of the Symfony Response object
 *
 * Includes shortcuts for manipulating responses. Is registered
 * in the dependency container as the "response" service
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Response
{
    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;
    
    /**
     * @var \Luthier\Routing\RouteBuilder;
     */
    protected $router;
    
    /**
     * @var string|null
     */
    protected $appIndex;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->router   = $container->get('router');
        $this->appIndex = $container->has('APP_INDEX')
            ? $container->get('APP_INDEX')
            : null;
    }
    
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
    
    /**
     * @param SfResponse $response
     * 
     * @return \Luthier\Http\Response
     */
    public function setResponse(SfResponse $response = null)
    {
        $this->response =  $response === NULL
            ? new SfResponse()
            : $response;
        
        return $this;
    }

    public function __call($method, $args)
    {
        if(method_exists($this->response, $method) )
        {
            return call_user_func_array([$this->response, $method], $args);
        }
        else
        {
           throw new \BadMethodCallException ("Call to undefined method Response::{$method}()");
        }
    }
    
    /**
     * Updates the internal Symfony Response object of this class if the 
     * provided response is an instance of a Symfony Response too
     * 
     * @param mixed   $responseResult  Response of an intermediary request
     * @param self    $masterResponse  The master response to be compared
     * 
     * @return void
     */
    public static function getRealResponse($responseResult, self $masterResponse)
    {
        if($responseResult instanceof SfResponse)
        {
            $masterResponse->setResponse($responseResult);
        }
    }

    /**
     * Sets the response to a JSON response
     * 
     * @param string|array $data Json data
     * @param int $status HTTP status code
     * @param array $headers Additional HTTP headers
     * 
     * @return self
     */ 
    public function json($data, int $status = 200, array $headers = [])
    {
        $this->response = new JsonResponse(is_array($data) ? json_encode($data) : $data, $status, $headers, !is_string($data));
        return $this;
    }
    
    /**
     * Sets the response to a streamed response
     * 
     * @param callable $callback Callback that produces the response
     * @param int $status HTTP status code
     * @param array $headers Additional HTTP headers
     * 
     * @return self
     */
    public function stream(callable $callback, int $status = 200, array $headers = [])
    {
        $this->response = new StreamedResponse($callback, $status, $headers);
        return $this;
    }
    
    /**
     * Sets the response to a file stream response
     * 
     * @param mixed   $file                 File that will be streamed
     * @param int     $status               HTTP status code
     * @param array   $headers              Additional HTTP headers
     * @param bool    $public               Set the file as public
     * @param string  $contentDisposition   File content disposition
     * @param bool    $autoEtag             Add E-Tag automatically    
     * @param bool    $autoLastModified     Set the Last Modified property automatically
     * 
     * @return self
     */
    public function file($file, int $status = 200, array $headers = [], bool $public = true, string $contentDisposition = null, bool $autoEtag = false, bool $autoLastModified = true)
    {
        $this->response = new BinaryFileResponse($file, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);
        return $this;
    }

    /**
     * Appends text to the response body
     * 
     * This is preferred instead using the "echo" function in 
     * 
     * @param string $content Text to append to the response body
     * 
     * @return self
     */
    public function write(string $content)
    {
        $this->response->setContent($this->response->getContent() . $content);
        return $this;
    }

    /**
     * Sets the current response as a RedirectResponse with the provided parameters
     *
     * @param  string   $url      The url to be redirected
     * @param  int      $status   HTTP status code
     * @param  array    $headers  Additional HTTP headers
     *
     * @return self
     */
    public function redirect(string $url, int $status = 302, array $headers = [])
    {
        if($this->appIndex !== null && substr($url,0,7) !== 'http://' && substr($url, 0,8) !== 'https://')
        {
            $url = $this->appIndex . '/' . $url;
        }

        $this->response = new RedirectResponse($url, $status, $headers);
        return $this;
    }
 
    /**
     * Sets the current response as a RedirectResponse to a specific route URL
     * 
     * @param string  $route   Route name
     * @param array   $params  Route parameters
     * @param int     $status  HTTP status code
     * @param array   $headers Additional HTTP headers
     * 
     * @return self
     */
    public function redirectToRoute(string $route, array $params = [], int $status = 302, array $headers = [])
    {
        return $this->redirect(
            $this->router->getRouteByName($route, $params),
            $status,
            $headers
        );
    }
}