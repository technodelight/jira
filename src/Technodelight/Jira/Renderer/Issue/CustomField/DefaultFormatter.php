<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Technodelight\Jira\Domain\Field;

class DefaultFormatter implements Formatter
{
    public function format(Field $field, $value)
    {
        if ($field->schemaType() == 'string') {
            return $value;
        }
        if ($field->schemaType() == 'array') {
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
