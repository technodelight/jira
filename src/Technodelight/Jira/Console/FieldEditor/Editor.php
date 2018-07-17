<?php

namespace Technodelight\Jira\Console\FieldEditor;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Domain\Issue\Meta\Field;

interface Editor
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param IssueKey $issueKey
     * @param Field $field
     * @param string $optionName
     * @return string
     */
    public function edit(InputInterface $input, OutputInterface $output, IssueKey $issueKey, Field $field, $optionName);

    /**
     * @param Field $field
     * @return bool
     */
    public function canEditField(Field $field);
}
