<?php

/**
 * Get the current app instance
 *
 * @return mixed
 */
function luthier()
{
    return Luthier\Framework::getInstance();
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
    return luthier()->getContainer()->get('router')->getRouteByName($name, $args);
}


/**
 * Checks if is a CLI request
 * (Taken from CodeIgniter 'is_cli()' code)
 *
 * @return mixed
 */
function is_cli()
{
    return luthier()->request->isCli();
}