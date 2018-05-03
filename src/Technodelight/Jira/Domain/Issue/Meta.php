<?php

namespace Technodelight\Jira\Domain\Issue;

use Technodelight\Jira\Domain\Issue\Meta\Field;

class Meta
{
    /**
     * @var string
     */
    private $issueKey;
    /**
     * @var Field[]
     */
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

    /**
     * @param string $fieldName
     * @return Field
     * @throws \InvalidArgumentException
     */
    public function field($fieldName)
    {
        foreach ($this->fields as $field) {
            if ($field->key() == $fieldName || $field->name() == $fieldName) {
                return $field;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('No meta found for field "%s"', $fieldName)
        );
    }

    private function __construct()
    {
    }
}
