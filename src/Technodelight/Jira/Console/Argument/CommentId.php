<?php

namespace Technodelight\Jira\Console\Argument;

/**
 * @todo move this class under the domain namespace
 */
class CommentId
{
    private $commentId;

    public static function fromString($commentId)
    {
        $instance = new self;
        if (empty(trim($commentId))) {
            throw new \InvalidArgumentException('Comment ID cannot be empty');
        }
        $instance->commentId = $commentId;

        return $instance;
    }

    public function __toString()
    {
        return (string) $this->commentId;
    }

    private function __construct()
    {
    }
}
