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
use FluentPDO;

/**
 * Application model. Uses a cool PDO query builder
 * 
 * @author <anderson@ingenia.me>
 */
class Model
{
    /**
     * @var Connection
     */
    protected $db;
    
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @param ContainerInterface $container
     * @param Connection $conection
     */
    public function __construct(ContainerInterface $container, Connection $connection)
    {
        $this->db = new FluentPDO($connection);
        
        if($container->get('APP_ENV') == 'development')
        {
            $this->db->debug = function($builder) use($container)
            {
                $query  = $builder->getQuery(false); 
                
                foreach($builder->getParameters() as $value)
                {
                    $fullQuery = preg_replace('/\?/', $value, $query, 1);
                }
               
                $container->get('logger')->debug($fullQuery, ['DATABASE']);
            };
        }
        
        $this->container = $container;
    }
}