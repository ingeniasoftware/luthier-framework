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
use Luthier\Validator\Exception\ValidationConstraintException;
use Luthier\UtilsTrait;

/**
 * Middleware that handles the validation errors automatically
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class ValidationMiddleware implements MiddlewareInterface
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
        try
        {
            $validationErrors = $request->sessionFlash->get('@VALIDATION_ERRORS');
            
            if (!$request->isAjax() && is_array($validationErrors) && !empty($validationErrors)) {
                $this->container->get('validator')->setValidationErrors($validationErrors);
            }
 
            $next($request, $response);
        }
        catch(ValidationConstraintException $e)
        {
            $validationErrors = $this->container->get('validator')->getValidationErrors();
            $validationMessage = 'Your request fails the validation constraints';
            
            if (! $request->isAjax()) {
                
                $referer = $request->header('referer');                             
                if (!empty($referer) && $referer != $request->getRequest()->getUri()){
                    $request->sessionFlash->set(
                        '@VALIDATION_ERRORS',
                        $validationErrors
                    );
                    return $response->redirect($referer);
                }
                
                return $this->errorResponse($request->getRequest(), 400, null, $validationMessage);
            }
            
            return $response->json([
                'error' => $validationMessage,
                'fields' => $validationErrors,
            ],400);
 
        }
    }
}