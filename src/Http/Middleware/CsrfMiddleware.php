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

use Psr\Container\ContainerInterface;
use Luthier\Http\Exception\InvalidCsrfTokenException; 
use Luthier\UtilsTrait;

/**
 * CSRF protection middleware
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class CsrfMiddleware implements MiddlewareInterface
{
    use UtilsTrait;
    
    /** 
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * {@inheritdoc}
     *
     * @see \Luthier\Http\Middleware\MiddlewareInterface::run()
     */
    public function run($request, $response, $next)
    {
        $csrf = $this->container->get('security');
        
        if ($csrf->getCsrfTokenName() !== null) {
            try
            {
                $csrf->verifyCsrfToken();  
                return $next($request, $response);
            }
            catch(InvalidCsrfTokenException $e)
            {
                return $this->errorResponse($request->getRequest() ,400, null, 'Your request does not contain a valid CSRF token');
            }
        } else {
            $next($request, $response);
        }   
    }
}