<?php

/**
 * Response class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http;

use Symfony\Component\HttpFoundation\Response as SfResponse;

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
}