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
 * Interface to be implemented by all application's middleware
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
interface MiddlewareInterface
{
    /**
     * Runs the middleware 
     * 
     * @param \Luthier\Http\RequestInterface  $request
     * @param \Luthier\Http\ResponseInterface $response
     * @param \Closure                        $next
     */
    public function run($request, $response, $next);
}
