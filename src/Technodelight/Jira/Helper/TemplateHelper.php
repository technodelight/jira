<?php

namespace Technodelight\Jira\Helper;

class TemplateHelper
{
    /**
     * @param array|string $string
     * @param int $pad pad length
     * @return string
     */
    public function tabulate($string, $pad = 4)
    {
        if (!is_array($string)) {
            $string = explode(PHP_EOL, $string);
        }
        return str_repeat(' ', $pad) . implode(
            PHP_EOL . str_repeat(' ', $pad),
            $string
        );
    }
}
