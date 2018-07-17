<?php

namespace Technodelight\Jira\Console\Option;

use Symfony\Component\Console\Input\InputInterface;

class Checker
{
    public function hasOptionWithoutValue(InputInterface $input, $option)
    {
        return $input->getOption($option) === '' && in_array('--' . $option, $_SERVER['argv']);
    }
}
