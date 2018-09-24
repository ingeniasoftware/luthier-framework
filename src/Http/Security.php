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

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * HTTP security layer
 * 
 * Inspired by the CodeIgniter Security class
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Security
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @var Response
     */
    protected $response;
    
    /**
     * @var Request
     */
    protected $request;
        
    /**
     * @var string
     */
    protected $csrfTokenName;
    
    /**
     * @var string
     */
    protected $csrfTokenHash;
    
    /**
     * @var int
     */
    protected $csrfTokenCookieLifetime;
    
    /**
     * @var string
     */
    protected $csrfTokenCookiePath;
    
    /**
     * @var string
     */
    protected $csrfTokenCookieDomain;
    
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        
        $csrfTokenName = $container->get('CSRF_TOKEN_NAME');
        
        $this->response = $container->get('response');
        $this->request = $container->get('request');
                   
        if ($csrfTokenName !== null) {
            $this->csrfTokenName = $csrfTokenName;
            $this->initializeCsrfProtection();
        }
    }
    
    /**
     * @internal
     */
    private function initializeCsrfProtection()
    {        
        $this->csrfTokenCookieLifetime = (int) $this->container->get('CSRF_TOKEN_LIFETIME');
        $this->csrfTokenCookieDomain = $this->container->get('CSRF_TOKEN_COOKIE_DOMAIN');     
        $this->csrfTokenCookiePath = $this->container->get('CSRF_TOKEN_COOKIE_PATH');
        
        $this->setCsrfHash();
    }
    
    /**
     * Gets the current CSRF token hash
     * 
     * @return string
     */
    public function getCsrfTokenHash()
    {
        return $this->csrfTokenHash;
    }
    
    /**
     * Gets the current CSRF token name
     * 
     * @return string
     */
    public function getCsrfTokenName()
    {
        return $this->csrfTokenName;
    }
    
    /**
     * Checks if the request contains a valid CSRF token
     * 
     * @throws Exception\CsrfTokenFailedException
     * 
     * @return Response|null
     */
    public function verifyCsrfToken()
    {
        if ( strtoupper($this->request->getMethod()) === "GET" ) {
            $this->response->headers->setCookie( 
                new Cookie(
                    $this->csrfTokenName,
                    $this->csrfTokenHash,
                    time() + $this->csrfTokenCookieLifetime,
                    $this->csrfTokenCookiePath,
                    $this->csrfTokenCookieDomain,
                    $this->request->isSecure()
                ) 
            );
            
            return;
        }
        
        $requestToken = $this->request->getRequest()->request->get($this->csrfTokenName); 
        
        if (! ($requestToken !== null && hash_equals($requestToken, $this->csrfTokenHash))) {
            throw new Exception\CsrfTokenFailedException("Invalid or missing CSRF token");  
        }
    }
    
    /**
     * Generates cryptographically secure pseudo-random bytes
     * 
     * @param int $length
     * @throws \Exception
     * @return string
     */
    public function getRandomBytes(int $length)
    {        
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }

        throw new \Exception("No random bytes generator functions available!");
    }
    
    private function setCsrfHash()
    {
        if ($this->csrfTokenHash !== null) {
            return;
        }
        
        $tokenCookie = $this->request->cookie($this->csrfTokenName, null);
        
        if (!empty($tokenCookie) && preg_match('#^[0-9a-f]{32}$#iS', $tokenCookie)) {
            $this->csrfTokenHash = $tokenCookie;
        } else {
            $length =  $this->container->get('CSRF_TOKEN_LENGTH');
            $this->csrfTokenHash = $this->getRandomBytes($length);
        }
    }
}