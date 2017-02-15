<?php

namespace Technodelight\Jira\Console\Argument;

use Symfony\Component\Console\Input\InputInterface;

interface Resolver
{
    public function argument(InputInterface $input);
    public function option(InputInterface $input);
}
