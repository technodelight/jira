<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Technodelight\Jira\Domain\Field;

interface Formatter
{
    public function format(Field $field, $value);
}
