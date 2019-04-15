<?php

namespace Technodelight\Jira\Renderer\Action\Show\User;

use Technodelight\Jira\Domain\User;
use Technodelight\Jira\Renderer\Action\Result;

class Success implements Result
{
    /**
     * @var User
     */
    private $user;
    private $phrase;
    private $data = [];

    /**
     * @param User $user
     * @return Success
     */
    public static function fromUser(User $user)
    {
        $instance = new self;
        $instance->user = $user;
        $instance->phrase = $user->displayName();

        return $instance;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function phrase(): string
    {
        return $this->phrase;
    }

    public function data(): array
    {
        return $this->data;
    }
}
