<?php

namespace Technodelight\Jira\Renderer\Action\Issue\Edit;

use Technodelight\Jira\Console\Argument\IssueLinkArgument;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Renderer\Action\Result;

class Success implements Result
{
    /**
     * @var IssueKey
     */
    private $issueKey;
    private $phrase;
    private $data = [];

    /**
     * @param IssueKey $issueKey
     * @param IssueLinkArgument[] $linkedIssues
     * @return Success
     */
    public static function fromIssueKeys(IssueKey $issueKey, array $linkedIssues)
    {
        $instance = new self;
        $instance->issueKey = $issueKey;
        $instance->phrase = '%s has been successfully linked to %s';
        $linkedIssues = array_map(function(IssueLinkArgument $argument) {
             return $argument->issueKey();
        }, $linkedIssues);
        $instance->data = [join(', ', $linkedIssues)];

        return $instance;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }

    public function phrase(): string
    {
        return $this->phrase;
    }

    public function data(): array
    {
        return $this->data;
    }
}
