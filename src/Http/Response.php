<?php

/**
 * Response class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http;

use Luthier\Framework;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpFoundation\{RedirectResponse, JsonResponse, StreamedResponse, BinaryFileResponse};


class Response
{
    /**
     * Symfony response object
     *
     * @var $response
     *
     * @access protected
     */
    protected $response;


    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct(SfResponse $response = NULL)
    {
        $this->response =  $response === NULL
            ? new SfResponse()
            : $response;
    }


    /**
     * __call() magic method
     *
     * @param  mixed  $method
     * @param  mixed  $args
     *
     * @return mixed
     *
     * @access public
     */
    public function __call($method, $args)
    {
        if(method_exists($this->response, $method) )
        {
            return call_user_func_array([$this->response, $method], $args);
        }
        else
        {
           throw new \BadMethodCallException ("Undefined method Response::{$property}()");
        }
    }


    /**
     * Get Symfony response object
     *
     * @return SfResponse
     *
     * @access public
     */
    public function getResponse()
    {
        return $this->response;
    }


    /**
     * Sets the response to a JSON response
     *
     * @param  string|array $data
     * @param  int   $status The HTTP response code (200 by default)
     *
     * @return SfResponse
     *
     * @access public
     */
    public function json($data, int $status = 200, array $headers = [])
    {
        $this->response = new JsonResponse(is_array($data) ? json_encode($data) : $data, $status, $headers, !is_string($data));
        return $this;
    }


    /**
     * Set the response to a streamed response
     *
     * @param  callable     $callback
     * @param  int          $status
     * @param  array        $headers
     *
     * @return SfResponse
     *
     * @access public
     */
    public function stream(callable $callback, int $status = 200, array $headers = [])
    {
        $this->response = new StreamedResponse($callback, $status, $headers);
        return $this;
    }


    /**
     * Set the response to a file stream response
     *
     * @param  mixed        $file
     * @param  int          $status
     * @param  array        $headers
     * @param  bool         $public
     * @param  string       $contentDisposition
     * @param  bool         $autoEtag
     * @param  bool         $autoLastModified
     *
     * @return SfResponse
     *
     * @access public
     */
    public function file($file, int $status = 200, array $headers = [], bool $public = true, string $contentDisposition = null, bool $autoEtag = false, bool $autoLastModified = true)
    {
        $this->response = new BinaryFileResponse($file, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);
        return $this;
    }


    /**
     * Writes to response body a string
     *
     * (You should use this instead the 'echo' function)
     *
     * @param  string $content
     *
     * @return self
     *
     * @access public
     */
    public function write(string $content)
    {
        $this->response->setContent($this->response->getContent() . $content);
        return $this;
    }


    /**
     * Set the current response as a RedirectResponse with the provided parameters
     *
     * @param  string   $url
     * @param  int      $status
     * @param  array    $headers (Optional)
     *
     * @return mixed
     *
     * @access public
     */
    public function redirect(string $url, int $status = 302, array $headers = [])
    {
        $this->response = new RedirectResponse($url, $status, $headers);
        return $this;
    }


    /**
     * Set the current response as a RedirectResponse to a specific route
     *
     * @param  string   $route
     * @param  array    $params
     * @param  int      $status (Optional)
     * @param  array    $headers (Optional)
     *
     * @return mixed
     *
     * @access public
     */
    public function redirectToRoute(string $route, array $params = [], int $status = 302, array $headers = [])
    {
        $router =  Framework::getInstance()->router;

        return $this->redirect(
            $router->getRouteByName($route, $params),
            $status,
            $headers
        );
    }
}