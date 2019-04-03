<?php

namespace Technodelight\Jira\Connector\HoaConsole;

use Fuse\Fuse;
use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue\IssueKey;

class IssueMetaAutocompleter implements Autocompleter
{
    /**
     * @var \Technodelight\Jira\Domain\Issue\Meta\Field
     */
    private $field;

    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;

    public function __construct(Api $api, IssueKey $issueKey, $fieldName)
    {
        $meta = $api->issueEditMeta($issueKey);
        $this->field = $meta->field($fieldName);
        $this->api = $api;
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
        $values = $this->field->allowedValues();
        if (!empty($values)) {
            $fuse = new Fuse($values);
            $results = $fuse->search($prefix);
            $matches = [];
            foreach ($results as $key) {
                if (count($matches) < 10) {
                    $matches[] = $values[$key];
                }
            }
            return $matches ?: null;
        }
        if ($this->field->autocompleteUrl()) {
            $values = $this->api->autocompleteUrl($this->field->autocompleteUrl(), $prefix);
            if (!empty($values['suggestions'])) {
                $fuse = new Fuse(
                    $values['suggestions'],
                    [
                        'keys' => ['label'],
                    ]
                );
                $results = array_map(
                    function ($result) {
                        return $result['label'];
                    },
                    $fuse->search($prefix)
                );
                $matches = [];
                foreach ($results as $key => $value) {
                    if (count($matches) < 10) {
                        $matches[] = $value;
                    }
                }
                return $matches ?: null;
            }
        }

        return null;
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
