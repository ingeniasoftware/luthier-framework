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
    protected $sfRequest;

    public function __construct(SfRequest $request = null)
    {
        $this->sfRequest = $request === null
            ? SfRequest::createFromGlobals()
            : $request;
    }

    public function getSfRequest()
    {
        return $this->sfRequest;
    }

    public function __call($property, $args)
    {
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
            
            return $name !== NULL
                ? $this->sfRequest->{$httpContainers[$property]}->get($name, $default)
                : $this->sfRequest->{$httpContainers[$property]}->all();
        }

        throw new \Exception("Undefined method App:request->{$property}()");
    }
}