<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\HoaConsole;

class Aggregate
{
    private array $autocompleters;

    /** @param array<int, Autocompleter> $autocompleters */
    public function __construct(array $autocompleters = [])
    {
        $this->autocompleters = $autocompleters;
    }

    public function __invoke(string $word, int $offset): ?array
    {
        foreach ($this->autocompleters as $autocompleter) {
            if (preg_match(sprintf('~%s~', $autocompleter->getWordDefinition()), $word)) {
                return $autocompleter->complete($word);
            }
        }

        return null;
    }
}
