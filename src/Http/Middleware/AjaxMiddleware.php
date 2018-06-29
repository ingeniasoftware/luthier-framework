<?php

/**
 * AjaxMiddleware
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http\Middleware;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AjaxMiddleware implements MiddlewareInterface
{
    public function run($request, $response, $next)
    {
        if($request->server('HTTP_X_REQUESTED_WITH') === NULL || strtolower($request->server('HTTP_X_REQUESTED_WITH')) !== 'xmlhttprequest')
        {
            throw new NotFoundHttpException('Not found');
        }
    }
}