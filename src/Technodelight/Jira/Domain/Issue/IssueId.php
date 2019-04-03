<?php

namespace Technodelight\Jira\Domain\Issue;

use Technodelight\Jira\Domain\Exception\InvalidIdException;

class IssueId
{
    private $id;

    public static function fromString($id)
    {
        $id = (int) trim($id);
        if (!is_numeric($id) && !empty($id)) {
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
