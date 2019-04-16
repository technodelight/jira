<?php

namespace Technodelight\Jira\Console\FieldEditor;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Issue\Meta\Field;

class FieldEditor
{
    /**
     * @var Editor[]
     */
    private $editors;

    public function __construct(array $editors)
    {
        $this->editors = $editors;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param IssueKey $issueKey
     * @param Field $field
     * @param string $optionName
     * @return string
     * @throws EditorException
     */
    public function edit(InputInterface $input, OutputInterface $output, IssueKey $issueKey, Field $field, $optionName)
    {
        foreach ($this->editors as $editor) {
            if ($editor->canEditField($field)) {
                return $editor->edit($input, $output, $issueKey, $field, $optionName);
            }
        }

        throw EditorException::fromUneditableField($field);
    }
}
