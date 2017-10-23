<?php

namespace Technodelight\Jira\Helper;

class TemplateHelper
{
    /**
     * @param array|string $string
     * @param int $pad pad length
     * @param string $paddingChar character to use
     * @return string
     */
    public function tabulate($string, $pad = 4, $paddingChar = ' ')
    {
        if (!is_array($string)) {
            $string = explode(PHP_EOL, $string);
        } else {
            $string = explode(PHP_EOL, join(PHP_EOL, $string));
        }
        return str_repeat($paddingChar, $pad)
            . implode(PHP_EOL . str_repeat($paddingChar, $pad), $string);
    }
}
