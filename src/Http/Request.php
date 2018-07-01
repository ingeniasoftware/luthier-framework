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
     * Symfony response object
     *
     * @var $sfRequest
     *
     * @access protected
     */
    protected $sfRequest;


    /**
     * Class constructor
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct()
    {
        $this->sfRequest = SfRequest::createFromGlobals();
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
        ];

        if(in_array($property, array_keys($httpContainers)))
        {
            $name    = $args[0] ?? NULL;
            $default = $args[1] ?? NULL;

            // ... return their values directly
            return $name !== NULL
                ? $this->sfRequest->{$httpContainers[$property]}->get($name, $default)
                : $this->sfRequest->{$httpContainers[$property]}->all();
        }
        // Is a Symfony Request method? call it
        else if( method_exists($this->sfRequest, $property) )
        {
            return call_user_func_array([$this->sfRequest, $property], $args);
        }
        else
        {
            throw new \Exception("Undefined method App:request->{$property}()");
        }

    }


    /**
     * Get Symfony request object
     *
     * @return SfResponse
     *
     * @access public
     */
    public function getSfRequest()
    {
        return $this->sfRequest;
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
        return is_cli();
    }
}