<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Http;

use Symfony\Component\HttpFoundation\Response;

/**
 * Interface to be implemented by all classes that manipulates
 * Responses within Luthier Framework
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
interface ResponseInterface
{

    /**
     * Gets the Symfony Response
     */
    public function getResponse();

    /**
     * Sets the Symfony Response
     * 
     * @param Response $response
     */
    public function setResponse(Response $request);

    /**
     * Writes a text to the response
     * 
     * @param string $string
     */
    public function write(string $string);

    /**
     * Sets the current response as a RedirectResponse with the provided parameters
     *
     * @param  string  $url     The url to be redirected
     * @param  int     $status  HTTP status code
     * @param  array   $headers Additional HTTP headers
     */
    public function redirect(string $url, int $status = 302, array $headers = []);

    /**
     * Sets the current response as a RedirectResponse to a specific route URL
     *
     * @param string $route   Route name
     * @param array  $params  Route parameters
     * @param int    $status  HTTP status code
     * @param array  $headers Additional HTTP headers
     */
    public function routeRedirect(string $route, array $params = [], int $status = 302, array $headers = []);

    /**
     * Sets the response to a JSON response
     *
     * @param string|array $data    Json data
     * @param int          $status  HTTP status code
     * @param array        $headers Additional HTTP headers
     */
    public function json($data, int $status = 200, array $headers = []);
}