<?php

/**
 * RouteParam class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Routing;

final class RouteParam
{
    private $name;

    private $regex;

    private $placeholder;

    private $optional;

    private $segment;

    public $value;

    // FIXME: (:num) placeholder not detected properly!
    
    private static $placeholderPatterns = [
        '{num:[a-zA-Z0-9-_]*(\?}|})'      => '(:num)', # (:num) route
        '{any:[a-zA-Z0-9-_]*(\?}|})'      => '(:any)', # (:any) route
        '{[a-zA-Z0-9-_]*(\?}|})'          => '(:any)', # Everything else
    ];

    private static $placeholderReplacements = [
        '/\(:any\)/'  => '[^/]+',
        '/\(:num\)/'  => '[0-9]+',
    ];


    public static function getPlaceholderReplacements()
    {
        return self::$placeholderReplacements;
    }


    public function __construct($segment, $default = null)
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
        $this->name = substr($name,1, !$this->optional ? -1 : -2);
    }

    public function getName()
    {
        return $this->name;
    }


    public function getSegment()
    {
        return $this->segment;
    }


    public function getRegex()
    {
        return $this->regex;
    }


    public function getPlaceholder()
    {
        return $this->placeholder;
    }


    public function isOptional()
    {
        return $this->optional;
    }
}