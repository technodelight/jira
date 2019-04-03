<?php

namespace Technodelight\Jira\Domain\Project;

use Technodelight\Jira\Domain\Exception\MissingProjectKeyException;

class ProjectKey
{
    private $projectKey;

    public static function fromString($string)
    {
        if (empty(trim($string))) {
            throw new MissingProjectKeyException();
        }
        $instance = new self;
        $instance->projectKey = strtoupper(trim($string));
        return $instance;
    }

    public function __toString()
    {
        return $this->projectKey;
    }
}
