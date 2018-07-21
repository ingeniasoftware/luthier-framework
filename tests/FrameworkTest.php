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
    /**
     * @var Framework
     */
    private $instance;

    /**
     * {@inheritDoc}
     * 
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->instance = new Framework();
    }

    /**
     * {@inheritDoc}
     * 
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown()
    {
        $this->instance = null;
        parent::tearDown();
    }
    
    public function testCallMethod()
    {
        $instance = $this->instance;
        
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
    
    public function testDefaultAndCustomContainer()
    {
        $defaultInstance = $this->instance;
        $customContainer = new Container();
        $customContainer->service('router', \Luthier\Routing\RouteBuilder::class);
        $customInstance  = new Framework(null, $customContainer);
        
        $customEmptyContainerInstance = new Framework(null, new Container());
         
        $this->assertSame(
            array_keys(Container::getDefaultContainer()), 
            $defaultInstance->getContainer()->getServices()
        ); 
        
        $this->assertSame(
            array_keys(Container::getDefaultContainer()),
            $customInstance->getContainer()->getServices()
        ); 
        
        $this->assertSame(
            array_keys(Container::getDefaultContainer()),
            $customEmptyContainerInstance->getContainer()->getServices()
        ); 
    }
    
    public function testDefaultAndCustomConfiguration()
    {
        $defaultInstance = $this->instance;
        $instanceWithConfigArray = new Framework(['APP_ENV' => 'development']);
        $instanceWithEnvConfig = (new Framework())->setEnvFolder(__DIR__ . '/assets');
        
        $this->assertSame(
            array_keys(\Luthier\Configuration::getDefaultConfig()), 
            array_keys($defaultInstance->getConfig())
        );
        
        $this->assertSame(
            array_keys(\Luthier\Configuration::getDefaultConfig()),
            array_keys($instanceWithConfigArray->getConfig())
        );
        
        $this->assertSame(
            array_keys(\Luthier\Configuration::getDefaultConfig()),
            array_keys($instanceWithEnvConfig->getConfig())
        );
        
        foreach(\Luthier\Configuration::getDefaultConfig() as $name => $defaultValue)
        {
            $this->assertSame($defaultValue, $instanceWithEnvConfig->getConfig()[$name]); 
        }
    }
    
    public function testConfigurationPrecedence()
    {
        $instance = new Framework(['APP_ENV' => 'production']);
        $instance->setEnvFolder(__DIR__ . '/assets');
        $this->assertSame($instance->getConfig()['APP_ENV'], 'development');
    }
    
    public function testInexistentEnvFileException()
    {
        $this->expectException(\Exception::class);
        $instance = (new Framework())->setEnvFolder(__DIR__ . '/inexistent')->run();
    }
}

