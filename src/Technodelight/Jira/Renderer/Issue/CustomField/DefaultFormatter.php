<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Field;
use Technodelight\Jira\Helper\JiraTagConverter;

class DefaultFormatter implements Formatter
{
    public function format(Field $field, OutputInterface $output, $value)
    {
        if ($field->schemaType() == 'string' || $field->schemaType() == 'any' && is_string($value)) {
            $tagConverter = new JiraTagConverter();
            return $tagConverter->convert($output, $value);
        }
        if ($field->schemaType() == 'array' || $field->schemaType() == 'any' && is_array($value)) {
            $value = array_map(
                function($value) {
                    return sprintf('<bg=yellow;fg=black> %s </>', $value);
                },
                $value
            );
            return join(' ', $value);
        }
    }
}
