<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Templating\Driver;

use Luthier\Http\Response;

/**
 * @author Anderson Salas <anderson@ingenia.me>
 */
interface TemplateDriverInterface
{

    /**
     * Renders a template
     * 
     * @param string $template
     * @param array  $vars
     * @param bool   $return
     * 
     * @return Response
     */
    public function render(string $template, array $vars = [], bool $return = false);

    /**
     * Registers a global function
     * 
     * @param string   $name      Function name/alias
     * @param callable $callback  Function callback
     * @param bool     $rawHtml   Set if this function will return raw (unescaped HTML output)
     * 
     * @return void
     */
    public function addFunction(string $name, callable $callback, bool $rawHtml = false);

    /**
     * Registers a global filter (If the template engine does not support this feature, must
     * be emulated) 
     * 
     * @param string   $name
     * @param callable $callback
     * @param bool     $rawHtml
     * 
     * @return void
     */
    public function addFilter(string $name, callable $callback, bool $rawHtml = false);

    /**
     * Registers a global variable
     * 
     * @param string $name
     * @param mixed  $value
     */
    public function addGlobal(string $name, $value);

    /**
     * Adds a directory to search templates
     * 
     * @param string $dir
     */
    public function addDirectory(string $dir);
}