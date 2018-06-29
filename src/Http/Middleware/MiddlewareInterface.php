<?php

/**
 * MiddlewareInterface
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http\Middleware;

interface MiddlewareInterface
{
    public function run($request, $response, $next);
}
