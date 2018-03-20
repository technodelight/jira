<?php

namespace Technodelight\Jira\Console\Argument;

class IssueLinkArgument
{
    private $relation;
    /**
     * @var IssueKey
     */
    private $issueKey;

    public function __construct($relation, $issueKey)
    {
        $this->relation = strtr($relation, ['-' => ' ']);
        $this->issueKey = IssueKey::fromString($issueKey);
    }

    /**
     * @param array $options
     * @return IssueLinkArgument[]
     */
    public static function fromOptions(array $options)
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

    public function relation()
    {
        return $this->relation;
    }

    public function issueKey()
    {
        return $this->issueKey;
    }
}
