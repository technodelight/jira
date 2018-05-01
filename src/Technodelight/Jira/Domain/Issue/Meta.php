<?php

namespace Technodelight\Jira\Domain\Issue;

use Technodelight\Jira\Domain\Issue\Meta\Field;

class Meta
{
    private $issueKey;
    private $fields = [];

    public static function fromArrayAndIssueKey(array $metaFields, $issueKey)
    {
        $instance = new self;
        $instance->fields = array_map(
            function (array $meta) {
                return Field::fromArray($meta);
            },
            $metaFields
        );
        $instance->issueKey = $issueKey;
        return $instance;
    }

    public function issueKey()
    {
        return $this->issueKey;
    }

    /**
     * @return Field[]
     */
    public function fields()
    {
        return $this->fields;
    }

    private function __construct()
    {
    }
}
