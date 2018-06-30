<?php

/**
 * Response class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http;

use Symfony\Component\HttpFoundation\Response as SfResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;


class Response
{
    /**
     * Symfony response object
     *
     * @var $sfResponse
     *
     * @access protected
     */
    protected $sfResponse;


    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct()
    {
        $this->sfResponse = new SfResponse();
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
        if(method_exists($this->sfResponse, $method) )
        {
            return call_user_func_array([$this->sfResponse, $method], $args);
        }
        else
        {
            throw new \Exception("Undefined method App:response->{$method}()");
        }
    }


    /**
     * Get Symfony response object
     *
     * @return SfResponse
     *
     * @access public
     */
    public function getSfResponse()
    {
        return $this->sfResponse;
    }


    /**
     * Sets the response to a JSON response
     *
     * @param  array $data
     * @param  int   $status The HTTP response code (200 by default)
     *
     * @return mixed
     *
     * @access public
     */
    public function json(array $data, int $status = 200)
    {
        $this->sfResponse->headers->set('Content-Type', 'application/json');
        $this->sfResponse->setStatusCode($status);
        $this->sfResponse->setContent(json_encode($data));
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
        $this->sfResponse->setContent($this->sfResponse->getContent() . $content);
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
        $this->sfResponse = new RedirectResponse($url, $status, $headers);
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
    public function routeRedirect(string $route, array $params = [], int $status = 302, array $headers = [])
    {
        return $this->redirect( route($route, $params), $status, $headers );
    }
}