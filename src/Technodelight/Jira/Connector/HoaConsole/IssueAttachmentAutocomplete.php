<?php

namespace Technodelight\Jira\Connector\HoaConsole;

use Fuse\Fuse;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Attachment;
use Technodelight\Jira\Domain\Issue\IssueKey;

class IssueAttachmentAutocomplete implements Autocompleter
{
    private Api $api;
    private IssueKey $issueKey;

    public function __construct(Api $api, IssueKey $issueKey)
    {
        $this->api = $api;
        $this->issueKey = $issueKey;
    }

    public function complete($prefix): ?array
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

    public function getWordDefinition(): string
    {
        return '(![^|!]+(\|thumbnail)?!?)|(\[\^[^]]+\]?)';
    }

    /** @return string[] */
    private function retrieveAttachmentFilenames(): array
    {
        return array_map(static function (Attachment $attachment) {
            return $attachment->filename();
        }, $this->api->retrieveIssue($this->issueKey)->attachments());
    }

    private function isImage($value): bool
    {
        $ext = pathinfo($value, PATHINFO_EXTENSION);

        return in_array($ext, ['png', 'svg', 'jpeg', 'jpg', 'bmp', 'gif'], true);
    }
}
