<?php

namespace Technodelight\Jira\Console\FieldEditor\Editor;

use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Console\FieldEditor\Editor;
use Technodelight\Jira\Console\HoaConsole\UserPickerAutocomplete;
use Technodelight\Jira\Domain\Issue\Meta\Field;

class UserEditor implements Editor
{
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param IssueKey $issueKey
     * @param Field $field
     * @param string $optionName
     * @return string
     */
    public function edit(InputInterface $input, OutputInterface $output, IssueKey $issueKey, Field $field, $optionName)
    {
        $readline = new Readline;
        $readline->setAutocompleter(
            new UserPickerAutocomplete($this->api)
        );
        $output->write(sprintf('<comment>Please provide a username for %s:</comment> ', $field->name()));
        return $readline->readLine();
    }

    /**
     * @param Field $field
     * @return bool
     */
    public function canEditField(Field $field)
    {
        return $field->schemaType() == 'user';
    }
}
