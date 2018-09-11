<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Database;

use Psr\Container\ContainerInterface;

/**
 * Simple PDO wrapper
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Connection extends \PDO
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $driver = $container->get('DB_TYPE');
        $host = $container->get('DB_HOST');
        $username = $container->get('DB_USER');
        $password = $container->get('DB_PASS');
        $database = $container->get('DB_NAME');

        $this->container = $container;

        parent::__construct("$driver:host=$host;dbname=$database", $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);
    }

    /**
     * Loads a (namespaced) model
     * 
     * @param string $model
     * 
     * @return Model
     */
    public function model(string $model)
    {
        $namespace = $this->container->get('DB_MDNS');

        if (! empty($namespace)) {
            $model = $namespace . '\\' . $model;
        }

        return new $model($this->container, $this);
    }
}