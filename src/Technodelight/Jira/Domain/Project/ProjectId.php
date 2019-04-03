<?php

namespace Technodelight\Jira\Domain\Project;

use Technodelight\Jira\Domain\Exception\InvalidIdException;

class ProjectId
{
    private $id;

    public static function fromString($id)
    {
        if (!is_numeric($id)) {
            throw new InvalidIdException(sprintf('"%s" is non numeric and cannot be used as ID!', $id));
        }
        $instance = new self;
        $instance->id = $id;

        return $instance;
    }

    public function id()
    {
        return $this->id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
