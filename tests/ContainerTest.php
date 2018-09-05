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

class ContainerTest extends TestCase
{
    public function testDefaultAndCustomContainer()
    {
        $defaultInstance = new Container();
        $customInstance = (new Container())->service('router', \Luthier\Routing\RouteBuilder::class);
        
        foreach([$defaultInstance, $customInstance] as $container)
        {
            foreach(Container::getDefaultContainer() as $name => $service)
            {
                if(!$container->has($name))
                {
                    $container->service($name, $service);
                }
            }
        }
        
        $this->assertSame(
            array_keys(Container::getDefaultContainer()),
            $defaultInstance->getServices()
        );
        
        $this->assertSame(
            array_keys(Container::getDefaultContainer()),
            $customInstance->getServices()
        );
    }
}