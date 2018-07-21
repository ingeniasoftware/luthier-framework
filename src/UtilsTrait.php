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

/**
 * Various helper methods used across the framework
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
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }
}