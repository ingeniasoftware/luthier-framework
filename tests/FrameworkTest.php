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

use PHPUnit\Framework\TestCase;

class FrameworkTest extends TestCase
{    
    public function testCallMagicMethodSyntax()
    {
        $instance = new Framework();
        
        // Single HTTP verb route definition
        foreach(Routing\RouteBuilder::HTTP_VERBS as $verb)
        {
            $this->assertInstanceOf(Routing\Route::class, $instance->__call($verb, ['foo', function(){}])); 
        }
        
        // Multiple HTTP verb route definition
        $this->assertInstanceOf(Routing\Route::class, $instance->__call('match', [[], 'foo', function(){}])); 

        // Route group
        foreach(
            [ 
                /* Short group syntax */
                ['prefix', function(){}], 
                /* Extended group syntax */
                ['prefix', ['middleware' => [], 'namespace' => ''], function(){} ], 
                ['prefix', ['middleware' => []], function(){} ], 
                ['prefix', ['namespace' => ''], function(){} ],
            ] 
            as $syntax)
        {
            $this->assertNull($instance->__call('group', $syntax));
        }
        
        // Route middleware
        foreach(
            [
                'foo',                    /* Running a middleware alias */
                function(){},             /* Running a middleware closure */
                ['foo','bar','baz'],      /* Running an array of middleware aliases */
                [
                    function(){},
                    function(){}, 
                    function(){}
                ],                        /* Running an array of middleware closures */
                ['foo', function(){}],    /* Running an array of middleware closures/aliases */
                ['foo' => 'bar' ],        /* Defining a middleware alias of a callable */
                ['foo' => function(){} ], /* Defining a middleware alias of a closure */
            ] 
            as $syntax
        )
        {
            $this->assertNull($instance->__call('middleware', [$syntax]));
        }
    }
}

