<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Http\Exception;

/**
 * This exception is thrown when a request submits an invalid
 * (or missing) CSRF token and the CSRF protection is enabled
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class CsrfTokenFailedException extends \Exception
{ 
}