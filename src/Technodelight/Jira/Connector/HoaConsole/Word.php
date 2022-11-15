<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\HoaConsole;

class Word implements Autocompleter
{
    private array $words;

    public function __construct(array $words = [])
    {
        $this->words = $words;
    }

    public function complete($prefix): ?array
    {
        return array_filter($this->words, static function(string $word) use ($prefix) {
            return stripos($word, $prefix) !== false;
        });
    }

    public function getWordDefinition(): string
    {
        return '.+';
    }
}
