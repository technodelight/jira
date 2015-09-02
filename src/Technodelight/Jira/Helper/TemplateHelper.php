<?php

namespace Technodelight\Jira\Helper;

class TemplateHelper
{
    public function tabulate($string, $pad = 4)
    {
        return implode(
            PHP_EOL . str_repeat(' ', $pad),
            explode(PHP_EOL, $string)
        );
    }
}
