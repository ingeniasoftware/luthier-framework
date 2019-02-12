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
 * Handles the configuration of the Luthier Framework applications
 *
 * They can be configured via .env files or directly with arrays, so this class 
 * parses and returns the actual configuration.
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
     * @var bool
     */
    protected $envLoaded = false;

    /**
     * @var string
     */
    protected $envFolder;

    /**
     * The default application configuration
     *
     * @var array
     */
    protected static $defaultConfig = [
        // General configuration
        'APP_ENV' => 'development',
        'APP_NAME' => 'Luthier',
        'APP_URL' => null,
        'APP_LOG' => null,
        'APP_CACHE' => null,
        'APP_PATH' => null,
        'APP_LANG' => 'en',
        // Session & Cookiesconfiguration
        'SESSION_NAME' => 'luthier_session',
        // Database configuration
        'DB_TYPE' => 'mysql',
        'DB_HOST' => 'localhost',
        'DB_USER' => 'root',
        'DB_PASS' => null,
        'DB_NAME' => null,
        'DB_MDNS' => null,
        // Template configuration
        'TEMPLATE_DRIVER' => 'default',
        'TEMPLATE_DIR' => null,
        // CSRF protection configuration
        'CSRF_TOKEN_NAME' => null,
        'CSRF_TOKEN_LIFETIME' => 3600,
        'CSRF_TOKEN_COOKIE_DOMAIN' => null,
        'CSRF_TOKEN_COOKIE_PATH' => null,
        'CSRF_TOKEN_LENGTH' => 16
    ];

    /**
     * @param  array   $config     Application configuration array
     * @param  string  $envFolder  Application .env file path
     */
    public function __construct(?array $config = [], ?string $envFolder = null)
    {
        $this->config = $config ?? [];
        $this->envFolder = $envFolder;
        
        if($envFolder !== null)
            $this->fetchEnvFile();
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

        // Failsafe base configuration
        foreach (self::$defaultConfig as $name => $default) {
            $config[$name] = $this->getConfigValue($name, $default);
        }

        // All other configuration
        foreach ($this->config as $name => $value) {
            if (! isset($config[$name])) {
                $config[$name] = $value;
            }
        }

        $this->parsed = $config;
        
        return $config;
    }

    /**
     * @return array
     */
    public static function getDefaultConfig()
    {
        return self::$defaultConfig;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getEnvFolder()
    {
        return $this->envFolder;
    }

    /**
     * @param array $config
     *
     * @return self
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param string $envFolder
     *
     * @return self
     */
    public function setEnvFolder(string $envFolder)
    {
        $this->envFolder = $envFolder;
        $this->fetchEnvFile();
        return $this;
    }
    
    private function fetchEnvFile()
    {
        if($this->envLoaded)
            return;
        
        if ($this->envFolder !== NULL) {
            try {
                (new Dotenv())->load(($this->envFolder !== NULL ? $this->envFolder . '/' : '') . '.env');
            } catch (PathException $e) {
                throw new \Exception('Unable to find your application .env file. Does the file exists?');
            } catch (\Exception $e) {
                throw new \Exception('Unable to parse your application .env file');
            }
        }
        
        $this->envLoaded = true;
    }
    
    /**
     * @param string $name
     * @param mixed $default
     * 
     * @return mixed
     */
    public function getConfigValue(string $name, $default = null)
    {
        $this->fetchEnvFile();
        
        if ($this->envFolder !== NULL && getenv($name) !== FALSE) {
            // Empty strings are considered NULL
            return !empty(getenv($name)) ? getEnv($name) : null;
        } else if (isset($this->config[$name])) {
            return $this->config[$name];
        } else {
            return $default;
        }
    }
}