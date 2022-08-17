<?php

namespace Technodelight\Jira\Console\Option;

use Symfony\Component\Console\Input\InputInterface;

class Checker
{
    public function hasOptionWithoutValue(InputInterface $input, $option): bool
    {
        return $input->getOption($option) === '' && in_array('--' . $option, $_SERVER['argv']);
    }
}
