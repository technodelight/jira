<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\Autocompleter;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

class Factory
{
    public function __construct(private readonly string $className)
    {
    }

    public function create(InputInterface $input, OutputInterface $output): Autocompleter
    {
        if (in_array(Autocompleter::class, (array)class_implements($this->className), true)) {
            return new $this->className($input, $output);
        }

        throw new TypeError(
            sprintf(
                'Class %s must implement interface %s',
                $this->className,
                Autocompleter::class
            )
        );
    }
}
