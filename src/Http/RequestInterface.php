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

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface to be implemented by all classes that manipulates
 * Request within Luthier Framework
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
interface RequestInterface
{

    /**
     * Gets the Symfony Request
     */
    public function getRequest();

    /**
     * Sets the Symfony Request
     * 
     * @param Request $request
     */
    public function setRequest(Request $request);

    /**
     * Gets the application base url
     * 
     * @param string $url 
     * 
     * @return string
     */
    public function baseUrl(string $url = ''): string;
}