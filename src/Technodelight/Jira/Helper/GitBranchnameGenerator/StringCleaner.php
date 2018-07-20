<?php

namespace Technodelight\Jira\Helper\GitBranchnameGenerator;

class StringCleaner
{
    private $charWhitelist = 'A-Za-z0-9./-';
    private $remove = ['BE', 'FE'];
    private $replace = [' ', ':', '/', ','];
    private $separator = '-';

    public function __construct($charWhitelist = null, array $remove = null, array $replace = null, $separator = null)
    {
        $this->charWhitelist = $charWhitelist ?: $this->charWhitelist;
        $this->remove = $remove ?: $this->remove;
        $this->replace = $replace ?: $this->replace;
        $this->separator = $separator ?: $this->separator;
    }

    /**
     * @param array|string $string
     * @return array|null|string|string[]
     */
    public function clean($string)
    {
        if (is_array($string)) {
            return array_map([$this, 'clean'], $string);
        }

        return $this->cleanup(strtolower($this->replace($this->remove($string))));
    }

    private function remove($string)
    {
        return str_replace($this->remove, '', $string);
    }

    private function replace($string)
    {
        return str_replace($this->replace, $this->separator, $string);
    }

    private function cleanup($string)
    {
        $string = preg_replace('~[^' . $this->charWhitelist . ']~', '', $string);
        return preg_replace(
            '~[' . preg_quote($this->separator) . ']+~',
            $this->separator,
            trim($string, $this->separator)
        );
    }
}
