<?php

namespace Technodelight\Jira\Api;

interface FieldMapper
{
    /**
     * Maps a field to another field
     *
     * @param string $field
     * @return string
     */
    public function map($field);
}
