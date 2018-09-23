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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Helper methods used across the framework
 * 
 * @author AndersonRafael
 */
trait UtilsTrait
{

    /**
     * Checks if the application is running in a CLI environment
     *
     * @return bool
     */
    public function isCli()
    {
        return (PHP_SAPI === 'cli' or defined('STDIN'));
    }

    /**
     * Generates a (nice) error response
     * 
     * @param Request $request
     * @param int     $status
     * @param string  $title
     * @param string $ message
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function errorResponse(Request $request, int $status = 500, string $title = 'Ups!', string $message = 'Something went wrong')
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([$title => $message], $status);
        } else {
            ob_start();
            require __DIR__ . '/Resources/Views/Error.php';
            $responseBody = ob_get_clean();
            return new Response($responseBody, $status);
        }
    }
}