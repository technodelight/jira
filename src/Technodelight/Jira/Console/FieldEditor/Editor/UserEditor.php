<?php

namespace Technodelight\Jira\Console\FieldEditor\Editor;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\HoaConsole\Aggregate;
use Technodelight\Jira\Connector\HoaConsole\UserPickerAutocomplete;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Console\FieldEditor\Editor;
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
        $q = new QuestionHelper();
        $question = new Question(sprintf('<comment>Please provide a username for %s:</comment> ', $field->name()));
        $question->setAutocompleterCallback(
            new Aggregate([
                new UserPickerAutocomplete($this->api)
            ])
        );

        return $q->ask($input, $output, $question);
    }

    /**
     * @param Field $field
     * @return bool
     */
    public function canEditField(Field $field): bool
    {
        return $field->schemaType() === 'user';
    }
}
