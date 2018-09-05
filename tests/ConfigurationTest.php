<?php 

namespace Luthier;

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testDefaultAndCustomConfiguration()
    {
        $defaultInstance = new Configuration();
        $configArrayInstance = new Configuration(['APP_ENV' => 'development']);
        $envDirInstance = new Configuration(null, __DIR__ . '/assets');
                
        $this->assertSame(
            array_keys(Configuration::getDefaultConfig()),
            array_keys($defaultInstance->parse())
        );
        
        $this->assertSame(
            array_keys(Configuration::getDefaultConfig()),
            array_keys($configArrayInstance->parse())
        );
        
        $this->assertSame(
            array_keys(Configuration::getDefaultConfig()),
            array_keys($envDirInstance->parse())
        );
    }
    
    public function testEnvDirNullValues()
    {
        $instance = new Configuration(null, __DIR__ . '/assets');
        foreach(Configuration::getDefaultConfig() as $name => $default)
        {
            $this->assertSame($default, $instance->parse()[$name]);
        }
    }
    
    public function testConfigurationPrecedence()
    {
        $instance = new Configuration(['APP_ENV' => 'production'], __DIR__ . '/assets');
        $this->assertSame($instance->parse()['APP_ENV'], 'development');
    }
    
    public function testInexistentEnvFileException()
    {
        $this->expectException(\Exception::class);
        $instance = new Configuration(null, __DIR__ . '/inexistent');
        $instance->parse();
    }
    
}