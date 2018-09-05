<?php 

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier;

use Psr\Container\ContainerInterface;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Simple Monolog logger for Luthier Framework
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Logger extends MonologLogger
{    
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {                
        if(!empty($container->get('APP_LOG')))
        {
            $log = $container->get('APP_PATH') . '/' . $container->get('APP_LOG');
            
            if(!file_exists(dirname($log)))
            {
                mkdir(dirname($log),null,true);
                file_put_contents($log, '');
            }
            
            $this->name = $container->get('APP_NAME');
            
            $handler = new StreamHandler($log, $container->get('APP_ENV') == 'development' ? MonologLogger::DEBUG : MonologLogger::ERROR);
            $handler->setFormatter(new LineFormatter(null,null,true,true));
            
            $this->handlers = [ $handler ];
            $this->processors = [];
        } 
    }
}