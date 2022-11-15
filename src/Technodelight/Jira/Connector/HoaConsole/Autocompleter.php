<?php

declare(strict_types=1);

namespace Technodelight\Jira\Connector\HoaConsole;

interface Autocompleter
{
    /**
     * Complete a word.
     * Returns null for no word, a full-word or an array of full-words.
     *
     * @param string $prefix Prefix to autocomplete.
     * @return array|null
     */
    public function complete(string $prefix): ?array;

    /**
     * Get definition of a word.
     * Example: \b\w+\b. PCRE delimiters and options must not be provided.
     *
     * @return  string
     */
    public function getWordDefinition(): string;
}
