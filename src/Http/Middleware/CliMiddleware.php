<?php

/**
 * Cli middleware
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Http\Middleware;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CliMiddleware implements MiddlewareInterface
{
    public function run($request, $response, $next)
    {
        if(!$request->isCli())
        {
            throw new NotFoundHttpException(
                getenv('APP_ENV') == 'development'
                    ? 'This route is only available under CLI requests'
                    : 'Not found');
        }
    }
}