<?php

namespace Technodelight\Jira\Connector\HoaConsole;

use Fuse\Fuse;
use Hoa\Console\Readline\Autocompleter\Autocompleter;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Attachment;
use Technodelight\Jira\Domain\Issue\IssueKey;

class IssueAttachmentAutocomplete implements Autocompleter
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var IssueKey
     */
    private $issueKey;

    public function __construct(Api $jira, IssueKey $issueKey)
    {
        $this->jira = $jira;
        $this->issueKey = $issueKey;
    }

    /**
     * Complete a word.
     * Returns null for no word, a full-word or an array of full-words.
     *
     * @param   string  &$prefix Prefix to autocomplete.
     * @return  mixed
     */
    public function complete(&$prefix)
    {
        $values = $this->retrieveAttachmentFilenames();

        if (!empty($values)) {
            $fuse = new Fuse($values);
            $results = $fuse->search(trim($prefix, '~[^]'));
            $matches = [];
            foreach ($results as $key) {
                $value = $values[$key];
                if (count($matches) < 10) {
                    if ($this->isImage($value)) {
                        $matches[] = '!' . $value . '|thumbnail!';
                    } else {
                        $matches[] = '[^' . $value . ']';
                    }
                }
            }

            return $matches ?: null;
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
        return '(![^|!]+(\|thumbnail)?!?)|(\[\^[^]]+\]?)';
    }

    /**
     * @return string[]
     */
    private function retrieveAttachmentFilenames()
    {
        return array_map(function(Attachment $attachment) {
            return $attachment->filename();
        }, $this->jira->retrieveIssue($this->issueKey)->attachments());
    }

    private function isImage($value)
    {
        $ext = pathinfo($value, PATHINFO_EXTENSION);

        return in_array($ext, ['png', 'svg', 'jpeg', 'jpg', 'bmp', 'gif']);
    }
}
