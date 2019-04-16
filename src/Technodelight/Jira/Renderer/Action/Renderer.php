<?php

namespace Technodelight\Jira\Renderer\Action;

use Symfony\Component\Console\Output\OutputInterface;

interface Renderer
{
    public function canProcess(Result $result): bool;

    public function render(OutputInterface $output, Result $result): int;
}
