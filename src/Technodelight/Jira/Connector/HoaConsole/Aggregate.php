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

    public function __invoke(string $buffer, int $offset = 0): array
    {
        $words = explode(' ', $buffer);
        $word = end($words);

        foreach ($this->autocompleters as $autocompleter) {
            if ($complete = $autocompleter->complete($word)) {
                return is_array($complete) ? $complete : [$complete];
            }
        }

        return [];
    }
}
