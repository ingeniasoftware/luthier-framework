<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Http\Middleware;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Middleware that rejects routes under non-AJAX requests
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class AjaxMiddleware implements MiddlewareInterface
{

    public function run($request, $response, $next)
    {
        if (! $request->isAjax()) {
            throw new NotFoundHttpException('This route is only available under AJAX requests');
        }

        $next($request, $response);
    }
}