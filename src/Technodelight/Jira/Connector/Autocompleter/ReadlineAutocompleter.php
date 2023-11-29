<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\Autocompleter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReadlineAutocompleter implements Autocompleter
{
    /** @var callable */
    private $autocomplete;

    public function __construct(
        private readonly InputInterface $input,
        private readonly OutputInterface $output
    ) {
    }


    public function setAutocomplete(callable $autocomplete): void
    {
        $this->autocomplete = $autocomplete;
    }

    public function read(?string $prompt = null): string
    {
        if ($this->autocomplete) {
            readline_completion_function($this->autocomplete);
        }

        return (string)readline($prompt);
    }
}
