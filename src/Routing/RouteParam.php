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
    /**
     * Param name
     *
     * @var $name
     *
     * @access private
     */
    private $name;


    /**
     * Param regex
     *
     * @var $regex
     *
     * @access private
     */
    private $regex;


    /**
     * Param placeholder
     *
     * @var $placeholder
     *
     * @access private
     */
    private $placeholder;


    /**
     * Original param segment
     *
     * @var $segment
     *
     * @access private
     */
    private $segment;


    /**
     * Is the param optional?
     *
     * @var $optional
     *
     * @access private
     */
    private $optional;


    /**
     * Is the param sticky?
     *
     * @var $sticky
     *
     * @access private
     */
    private $sticky;


    /**
     * Param value
     *
     * @var $value
     *
     * @access public
     */
    public $value;


    /**
     * Placeholder regex patterns
     *
     * @var static $placeholderPatterns
     *
     * @access private
     */
    private static $placeholderPatterns = [
        '{num:[a-zA-Z0-9-_]*(\?}|})'  => '(:num)', # (:num) route
        '{any:[a-zA-Z0-9-_]*(\?}|})'  => '(:any)', # (:any) route
        '{[a-zA-Z0-9-_]*(\?}|})'      => '(:any)', # Everything else
    ];


    /**
     * Placeholders regex replacements
     *
     * @var static $placeholderReplacements
     *
     * @access private
     */
    private static $placeholderReplacements = [
        '/\(:any\)/'  => '[^/]+',
        '/\(:num\)/'  => '[0-9]+',
    ];



    /**
     * Get the placeholders patterns
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getPlaceholderReplacements()
    {
        return self::$placeholderReplacements;
    }



    /**
     * Class constructor
     *
     * @param  string $segment Route segment string
     *
     * @return mixed
     *
     * @access public
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
     * Get segment name
     *
     * @return string
     *
     * @access public
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Get original segment
     *
     * @return string
     *
     * @access public
     */
    public function getSegment()
    {
        return $this->segment;
    }


    /**
     * Get segment regex
     *
     * @return string
     *
     * @access public
     */
    public function getRegex()
    {
        return $this->regex;
    }


    /**
     * Get segment placeholder
     *
     * @return string
     *
     * @access public
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }


    /**
     * Check if segment is optional
     *
     * @return bool
     *
     * @access public
     */
    public function isOptional()
    {
        return $this->optional;
    }


    /**
     * Check if segment is sticky
     *
     * @return bool
     *
     * @access public
     */
    public function isSticky()
    {
        return $this->sticky;
    }
}