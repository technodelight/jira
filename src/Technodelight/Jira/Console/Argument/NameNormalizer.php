<?php

namespace Technodelight\Jira\Console\Argument;

class NameNormalizer
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function normalize()
    {
        return trim(
            strtolower(
                preg_replace('~[-]+~', '-', preg_replace('/(?<!^)[A-Z]/', '-$0', preg_replace('~[^a-z0-9]+~i', '-', $this->name)))
            ),
            '-'
        );
    }
}
