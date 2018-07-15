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

/**
 * @author Anderson Salas <anderson@ingenia.me>
 */
interface MiddlewareInterface
{
    public function run($request, $response, $next);
}
