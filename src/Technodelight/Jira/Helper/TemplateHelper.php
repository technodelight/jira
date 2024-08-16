<?php

namespace Technodelight\Jira\Helper;

class TemplateHelper
{
    public function tabulate(
        array|string $string,
        int $pad = 4,
        string $paddingChar = ' '
    ): string {
        if (is_array($string)) {
            $string = join(PHP_EOL, $string);
        }
        $string = explode(PHP_EOL, $string);

        return str_repeat($paddingChar, $pad)
            . implode(PHP_EOL . str_repeat($paddingChar, $pad), $string);
    }

    public function tabulateWithLevel(
        array|string$string,
        int $level = 1,
        int $pad = 4,
        string $paddingChar = ' '
    ): string {
        return $this->tabulate($string, $level * $pad, $paddingChar);
    }
}
