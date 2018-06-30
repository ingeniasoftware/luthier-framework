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
        if(!$request->isAjax())
        {
            throw new NotFoundHttpException(
                getenv('APP_ENV') == 'development'
                    ? 'This route is only available under AJAX requests'
                    : 'Not found');
        }
    }
}