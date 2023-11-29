<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\Autocompleter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Autocompleter
{
    public function __construct(InputInterface $input, OutputInterface $output);

    public function setAutocomplete(callable $autocomplete): void;

    public function read(?string $prompt = null): string;
}
