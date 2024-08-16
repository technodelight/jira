<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\FieldEditor;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Issue\Meta\Field;

class FieldEditor
{
    public function __construct(private readonly array $editors = []) {}

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public function edit(InputInterface $input, OutputInterface $output, IssueKey $issueKey, Field $field, string $optionName): string
    {
        foreach ($this->editors as $editor) {
            if ($editor->canEditField($field)) {
                return $editor->edit($input, $output, $issueKey, $field, $optionName);
            }
        }

        throw EditorException::fromUneditableField($field);
    }
}
