<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\HoaConsole;

class Aggregate
{
    /** @param array<int, Autocompleter> $autocompleters */
    public function __construct(private readonly array $autocompleters = []) {}

    public function __invoke(string $buffer): array
    {
        $words = explode(' ', $buffer);
        $word = end($words);

        foreach ($this->autocompleters as $autocompleter) {
            $complete = $autocompleter->complete($word);
            if (!empty($complete)) {
                return is_array($complete) ? $complete : [$complete];
            }
        }

        return [];
    }
}
