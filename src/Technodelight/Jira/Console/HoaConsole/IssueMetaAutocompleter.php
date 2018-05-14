<?php

namespace Technodelight\Jira\Console\HoaConsole;

use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Technodelight\Jira\Api\JiraRestApi\Api;

class IssueMetaAutocompleter implements Autocompleter
{
    /**
     * @var \Technodelight\Jira\Domain\Issue\Meta\Field
     */
    private $field;

    public function __construct(Api $api, $issueKey, $fieldName)
    {
        $meta = $api->issueEditMeta($issueKey);
        $this->field = $meta->field($fieldName);
    }

    /**
     * Complete a word.
     * Returns null for no word, a full-word or an array of full-words.
     *
     * @param   string &$prefix Prefix to autocomplete.
     * @return  mixed
     */
    public function complete(&$prefix)
    {
        $matches = [];
        foreach ($this->field->allowedValues() as $value) {
            if (strpos($value, $prefix) !== false && count($matches) < 10) {
                $matches[] = $value;
            }
        }

        return $matches ?: null;
    }

    /**
     * Get definition of a word.
     * Example: \b\w+\b. PCRE delimiters and options must not be provided.
     *
     * @return  string
     */
    public function getWordDefinition()
    {
        return '.+';
    }
}
