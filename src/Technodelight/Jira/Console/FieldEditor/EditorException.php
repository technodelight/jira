<?php

namespace Technodelight\Jira\Console\FieldEditor;

use Technodelight\Jira\Domain\Issue\Meta\Field;

class EditorException extends \Exception
{
    public static function fromUneditableField(Field $field)
    {
        return new self(
            sprintf('Cannot edit field: %s (no editor exists)', $field->name())
        );
    }
}
