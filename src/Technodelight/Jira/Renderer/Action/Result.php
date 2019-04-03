<?php

namespace Technodelight\Jira\Renderer\Action;

interface Result
{
    /**
     * @return string
     */
    public function phrase(): string;

    /**
     * @return array
     */
    public function data(): array;
}
