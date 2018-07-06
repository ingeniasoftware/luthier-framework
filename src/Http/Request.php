<?php

/**
 * Request class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http;

use Symfony\Component\HttpFoundation\Request as SfRequest;

class Request
{
    /**
     * Symfony request object
     *
     * @var $request
     *
     * @access protected
     */
    protected $request;



    /**
     * Class constructor
     *
     * @param  SfRequest $request Symfony Request object (optional)
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct(SfRequest $request = NULL)
    {
        $this->request = $request === NULL
            ? SfRequest::createFromGlobals()
            : $request;
    }


    /**
     * __call() magic method
     *
     * @param  mixed  $property
     * @param  mixed  $args
     *
     * @return mixed
     *
     * @access public
     */
    public function __call($property, $args)
    {
        // Is any of the following shortcuts?
        $httpContainers = [
            'attributes' => 'attributes',
            'post'       => 'request',
            'get'        => 'query',
            'server'     => 'server',
            'file'       => 'files',
            'session'    => 'session',
            'header'     => 'header',
            'cookie'     => 'cookies',
        ];

        if(in_array($property, array_keys($httpContainers)))
        {
            $name    = $args[0] ?? NULL;
            $default = $args[1] ?? NULL;

            // ... return their values directly
            return $name !== NULL
                ? $this->request->{$httpContainers[$property]}->get($name, $default)
                : $this->request->{$httpContainers[$property]}->all();
        }
        // Is a Symfony Request method? call it
        else if( method_exists($this->request, $property) )
        {
            return call_user_func_array([$this->request, $property], $args);
        }
        else
        {
            throw new \BadMethodCallException ("Call to undefined method Request::{$property}()");
        }
    }


    /**
     * Get Symfony request object
     *
     * @return SfResponse
     *
     * @access public
     */
    public function getRequest()
    {
        return $this->request;
    }


    /**
     * Check if the current request is an AJAX request
     *
     * @return bool
     *
     * @access public
     */
    public function isAjax()
    {
        return strtolower($this->server('HTTP_X_REQUESTED_WITH','')) === 'xmlhttprequest';
    }


    /**
     * Check if the current request is a CLI request (alias of using the is_cli() function)
     *
     * @return mixed
     *
     * @access public
     */
    public function isCli()
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));;
    }
}