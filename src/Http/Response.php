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
    protected $sfResponse;

    public function __construct(SfResponse $response = null)
    {
        $this->sfResponse = $response === null
            ? new SfResponse()
            : $response;
    }

    public function getSfResponse()
    {
        return $this->sfResponse;
    }

    public function json(array $data, int $status = 200)
    {
        $this->sfResponse->headers->set('Content-Type', 'application/json');
        $this->sfResponse->setStatusCode($status);
        $this->sfResponse->setContent(json_encode($data));
    }

    public function write(string $content)
    {
        $this->sfResponse->setContent($this->sfResponse->getContent() . $content);
        return $this;
    }
}