<?php

/*
 * Luthier Framework
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of the Luthier Framework. See the LICENSE file for copyright
 * information and license details
 */

namespace Luthier\Routing;

/**
 * Route parameter representation
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
final class RouteParam
{
    /**
     * @var string
     */
    private $name;

    /**
     * Actual parameter regex
     * 
     * @var string
     */
    private $regex;
    
    /**
     * @var string
     */
    private $placeholder;

    /**
     * Original segment of the parameter
     *
     * @var string
     */
    private $segment;

    /**
     * @var bool
     */
    private $optional;

    /**
     * @var bool
     */
    private $sticky;

    /**
     * @var mixed
     */
    public $value;

    /**
     * Parameter segment -> placeholder conversion array
     *
     * @var string[]
     */
    private static $placeholderPatterns = [
        '{num:[a-zA-Z0-9-_]*(\?}|})'  => '(:num)', # (:num) route
        '{any:[a-zA-Z0-9-_]*(\?}|})'  => '(:any)', # (:any) route
        '{[a-zA-Z0-9-_]*(\?}|})'      => '(:any)', # Everything else
    ];

    /**
     * Parameter placeholder -> regex conversion array
     *
     * @var string[]
     */
    private static $placeholderReplacements = [
        '/\(:any\)/'  => '[^/]+',
        '/\(:num\)/'  => '[0-9]+',
    ];

    /**
     * Gets the placeholder -> regex conversion array
     *
     * @return string[]
     */
    public static function getPlaceholderReplacements()
    {
        return self::$placeholderReplacements;
    }
    
    /**
     * @param string $segment Route segment
     */
    public function __construct(string $segment)
    {
        $this->segment = $segment;
        $customRegex = false;

        $matches = [];

        if(preg_match('/{\((.*)\):[a-zA-Z0-9-_]*(\?}|})/', $segment, $matches))
        {
            $this->placeholder = $matches[1];
            $this->regex = $matches[1];
            $name = preg_replace('/\((.*)\):/', '', $segment, 1);
        }
        else
        {
            foreach(self::$placeholderPatterns as $regex => $replacement)
            {
                $parsedSegment = preg_replace('/'.$regex.'/' , $replacement, $segment);

                if($segment != $parsedSegment )
                {
                    $this->placeholder = $replacement;
                    $this->regex = preg_replace(array_keys(self::$placeholderReplacements), array_values(self::$placeholderReplacements), $replacement,1);
                    $name = preg_replace(['/num:/', '/any:/'], '', $segment, 1);
                    break;
                }
            }
        }

        $this->optional = substr($segment,-2,1) == '?';
        $this->name     = substr($name,1, !$this->optional ? -1 : -2);
        $this->sticky   = substr($this->name,0,1) == '_';
    }

    /**
     * Gets the segment name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the original segment string
     *
     * @return string
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * Gets the segment regex
     *
     * @return string
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * Gets the segment placeholder
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * Checks if the segment is optional
     *
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * Checks if the segment is sticky
     *
     * @return bool
     */
    public function isSticky()
    {
        return $this->sticky;
    }
}