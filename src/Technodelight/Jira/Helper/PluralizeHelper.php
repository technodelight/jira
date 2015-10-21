<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Helper\Helper;

class PluralizeHelper extends Helper
{
    public function getName()
    {
        return 'pluralize';
    }

    public function pluralize($word, $count = 1)
    {
        if ($count <= 1) {
            return $word;
        }

        return $word . 's';
    }
}
