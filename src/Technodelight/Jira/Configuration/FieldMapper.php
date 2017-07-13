<?php

namespace Technodelight\Jira\Configuration;

use Technodelight\Jira\Api\FieldMapper as FieldMapperInterface;

class FieldMapper implements FieldMapperInterface
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private $configuration;

    public function __construct(ApplicationConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function map($field)
    {
        $map = $this->configuration->fieldMap();
        if (isset($map[$field])) {
            return $map[$field];
        }
        return $field;
    }
}
