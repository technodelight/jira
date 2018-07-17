<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Technodelight\Jira\Domain\Field;
use Symfony\Component\Console\Output\OutputInterface;

interface Formatter
{
    public function format(Field $field, OutputInterface $output, $value);
}
