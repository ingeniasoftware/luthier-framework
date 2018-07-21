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

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;

/**
 * Handles the configuration of the application
 *
 * Luthier Framework applications can be configured via .env files and configuration
 * arrays, so this class checks which of them are provided and returns the actual
 * configuration. Also checks if there are missing configuration parameters and warns to
 * the user.
 *
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Configuration
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $envFolder;

    /**
     * The default application configuration
     *
     * This array is used during the application startup for check if there are missing
     * configuration items. If no configuration are provided to the application, this
     * will be used.
     *
     * @var array
     */
    protected static $defaultConfig = [
        'APP_ENV'   => 'development',
        'APP_NAME'  => 'Luthier',
        'APP_INDEX' => null,
        'APP_CACHE' => null,
    ];

    /**
     * @param  array   $config     Application configuration array
     * @param  string  $envFolder  Application .env file path
     */
    public function __construct(array $config = [], string $envFolder = null)
    {
        $this->config    = $config;
        $this->envFolder = $envFolder;
    }

    /**
     * Parses the provided application configuration
     *
     * The .env file configuration has more precedence that the application configuration
     * array, so any configuration provided by the first source will no be overwritten by
     * the second.
     *
     * Keep this in mind when you use both an .env file and a configuration array
     * in your application.
     *
     * @return array
     */
    public function parse()
    {
        $config = [];

        if($this->envFolder !== NULL)
        {
            try
            {
                (new Dotenv())->load( ($this->envFolder !== NULL ? $this->envFolder . '/' : '' ) . '.env' );
            }
            catch(PathException $e)
            {
                throw new \Exception('Unable to find your application .env file. Does the file exists?');
            }
            catch(\Exception $e)
            {
                throw new \Exception('Unable to parse your application .env file');
            }
        }

        // Failsafe base configuration
        foreach(self::$defaultConfig as $name => $default)
        {
            if($this->envFolder !== NULL && getenv($name) !== FALSE)
            {
                $config[$name] = getenv($name);
            }
            else if(isset($this->config[$name]))
            {
                $config[$name] = $this->config[$name];
            }
            else
            {
                $config[$name] = $default;
            }
        }

        // All other configuration
        foreach($this->config as $name => $value)
        {
            if(!isset($config[$name]))
            {
                $config[$name] = $value;
            }
        }

        return $config;
    }
    
    public static function getDefaultConfig()
    {
        return self::$defaultConfig;
    }
}