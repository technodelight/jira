<?php

namespace Technodelight\Jira\Console\FieldEditor\Editor;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\HoaConsole\Aggregate;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Console\FieldEditor\Editor;
use Technodelight\Jira\Connector\HoaConsole\IssueMetaAutocompleter;
use Technodelight\Jira\Domain\Issue\Meta\Field;

class AutocompletedEditor implements Editor
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function edit(InputInterface $input, OutputInterface $output, IssueKey $issueKey, Field $field, $optionName)
    {
        $helper = new QuestionHelper();
        $question = new Question(
            sprintf('<comment>Please select value to %s for</comment> <info>%s:</info>', $optionName, $field->name())
        );
        $question->setAutocompleterCallback(
            new Aggregate([
                new IssueMetaAutocompleter($this->api, $issueKey, $field->name())
            ])
        );
        $value = $helper->ask($input, $output, $question);

        $allowedValues = $field->allowedValues();
        if ($field->autocompleteUrl()) {
            $values = $this->api->autocompleteUrl($field->autocompleteUrl(), $value);
            $allowedValues = $values['suggestions'];
        }

        if (!in_array($value, $allowedValues, true)) {
            $confirmQuestion = new ConfirmationQuestion(
                sprintf(
                    'Value "%s" does not exists for %s, do you want to use this value anyway? [y/N] ',
                    $value,
                    $field->name()
                ),
                false
            );
            if ($helper->ask($input, $output, $confirmQuestion)) {
                return $value;
            }
        }
        return $value;
    }

    public function canEditField(Field $field): bool
    {
        return !empty($field->autocompleteUrl()) || !empty($field->allowedValues());
    }
}
