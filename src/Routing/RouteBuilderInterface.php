<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Routing;

use Symfony\Component\Routing\RouteCollection;

/**
 * @author Anderson Salas <anderson@ingenia.me>
 */
interface RouteBuilderInterface
{

    /**
     * Returns a Symfony RouteCollection object with all compiled Luthier routes
     * 
     * @return RouteCollection
     */
    public function getRoutes(): RouteCollection;

    /**
     * Gets a route URL by its name, or throws an exception if an undefined route was requested
     * 
     * @param string $name        Route name
     * @param array  $args        Route parameters
     * @param bool   $absoluteUrl Build an absolute url
     * @throws \Exception
     * 
     * @return string
     */
    public function getRouteByName(string $name, array $args = [], bool $absoluteUrl = true): string;
}