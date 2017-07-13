<?php

namespace Technodelight\Jira\Api;

class DefaultFieldMapper implements FieldMapper
{
    /**
     * Returns the field it receives as argument, basically does nothing :)
     *
     * @param string $field
     * @return string
     */
    public function map($field)
    {
        return $field;
    }
}
