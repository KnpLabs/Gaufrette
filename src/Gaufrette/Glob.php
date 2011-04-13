<?php

namespace Gaufrette;

class Glob
{
    protected $pattern;
    protected $strictLeadingDot;
    protected $strictWildcartSlash;
    protected $regex = null;

    /**
     * Constructor
     *
     * @param  string $pattern The glob pattern
     */
    public function __construct($pattern, $strictLeadingDot = true, $strictWildcartSlash = true)
    {
        $this->pattern = $pattern;
    }

    /**
     * Returns the pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Returns the regex associated to the glob
     *
     * @return  string
     */
    public function getRegex()
    {
        if (null === $this->regex) {
            $this->compile();
        }

        return $this->regex;
    }

    /**
     * Indicates whether the specified filename matches the glob
     *
     * @param  string $filename The filename that is being tested
     */
    public function matches($filename)
    {
        return (boolean) preg_match($this->getRegex(), $filename);
    }

    /**
     * Filters the given input
     *
     * @param  mixed $list
     *
     * @return array
     */
    public function filter($list)
    {
        return array_filter((array) $list, array($this, 'matches'));
    }

    /**
     * Build the regex for the pattern
     */
    protected function compile()
    {
        $firstByte   = true;
        $escaping    = false;
        $inCurlies   = 0;
        $patternSize = strlen($this->pattern);
        $regex       = '';

        for ($i = 0; $i < $patternSize; $i++) {
            $car = $this->pattern[$i];

            if ($firstByte) {
                if ($this->strictLeadingDot && '.' !== $car) {
                    $regex.= '(?=[^\.])';
                }

                $firstByte = false;
            }

            switch ($car) {
                case '/':
                    $firstByte = true;
                case '.':
                case '(':
                case ')':
                case '|':
                case '+':
                case '^':
                case '$':
                    $regex.= '\\' . $car;
                    break;
                case '[':
                case ']':
                    $regex.= $escaping ? '\\' . $car : $car;
                    break;
                case '*':
                    $regex.= $escaping ? '\\*' : $this->strictWildcartSlash ? '[^/]*' : '.*';
                    break;
                case '?':
                    $regex.= $escaping ? '\\?' : $this->strictWildcartSlash ? '[^/]' : '.';
                    break;
                case '{':
                    $regex.= !$escaping && ++$inCurlies ? '(' : '\\{';
                    break;
                case '}':
                    $regex.= !$escaping && $inCurlies && $inCurlies-- ? ')' : '}';
                    break;
                case ',':
                    $regex.= !$escaping && $inCurlies ? '|' : ',';
                    break;
                case '\\':
                    $regex.= $escaping ? '\\\\' : '';
                    $escaping = !$escaping;
                    continue;
                default:
                    $regex.= $car;
            }

            $escaping = false;
        }

        $this->regex = '#^' . $regex . '$#';
    }
}
