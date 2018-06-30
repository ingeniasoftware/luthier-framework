<?php

/**
 * Get the current app instance
 *
 * @return mixed
 */
function luthier()
{
    return Luthier\App::getInstance();
}


/**
 * Return a route url by its name
 *
 * @param  string   $name
 * @param  array     $args
 *
 * @return mixed
 */
function route(string $name, array $args = [])
{
    return luthier()->router->getRouteByName($name, $args);
}