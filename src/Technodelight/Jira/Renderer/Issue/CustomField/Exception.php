<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

class Exception extends \InvalidArgumentException
{
    public static function fromMissingField($fieldName, \Throwable $previousException = null)
    {
        return new self(
            sprintf('Cannot find the requested field "%s" by name', $fieldName),
            null,
            $previousException
        );
    }

    public static function fromMultipleMatchingFields($fieldName, array $matchingFields, \Throwable $previousException = null)
    {
        return new self(
            sprintf(
                'Finding field "%s" by name seems to match on multiple JIRA fields: %s',
                $fieldName,
                join(', ', $matchingFields)
            ),
            null,
            $previousException
        );
    }
}
