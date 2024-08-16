<?php

namespace Technodelight\Jira\Console\Argument;

use Technodelight\Jira\Domain\Issue\IssueKey;

class IssueLinkArgument
{
    private string $relation;
    /**
     * @var IssueKey
     */
    private IssueKey $issueKey;

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function __construct($relation, $issueKey)
    {
        $this->relation = strtr($relation, ['-' => ' ']);
        $this->issueKey = IssueKey::fromString($issueKey);
    }

    /**
     * @param array $options
     * @return IssueLinkArgument[]
     */
    public static function fromOptions(array $options): array
    {
        return array_filter(
            array_map(
                function($relation, $issueKey) {
                    if (!empty($issueKey)) {
                        return new IssueLinkArgument($relation, $issueKey);
                    }
                },
                array_keys($options), array_values($options)
            )
        );
    }

    public function relation(): string
    {
        return $this->relation;
    }

    public function issueKey(): IssueKey
    {
        return $this->issueKey;
    }
}
