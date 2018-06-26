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
}