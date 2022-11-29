<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Terminal;

class TerminalDimensionProvider
{
    public function __construct(private Terminal $terminal)
    {
    }

    public function width(): int
    {
        return $this->terminal->getWidth();
    }

    public function height(): int
    {
        return $this->terminal->getHeight();
    }
}
