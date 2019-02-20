<?php

namespace Technodelight\Jira\Console\FieldEditor\Editor;

use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKey;
use Technodelight\Jira\Console\FieldEditor\Editor;
use Technodelight\Jira\Connector\HoaConsole\IssueMetaAutocompleter;
use Technodelight\Jira\Domain\Issue\Meta\Field;

class AutocompletedEditor implements Editor
{
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;
    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    private $questionHelper;

    public function __construct(Api $api, QuestionHelper $questionHelper)
    {
        $this->api = $api;
        $this->questionHelper = $questionHelper;
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
            new IssueMetaAutocompleter($this->api, (string) $issueKey, $field->name())
        );
        $output->writeln(sprintf('<comment>Please select value to %s for</comment> <info>%s:</info>', $optionName, $field->name()));
        $value = $readline->readLine();
        if ($field->autocompleteUrl()) {
            $values = $this->api->autocompleteUrl($field->autocompleteUrl(), $value);
            $allowedValues = $values['suggestions'];
        } else {
            $allowedValues = $field->allowedValues();
        }
        if (!in_array($value, $allowedValues)) {
            $q = new ConfirmationQuestion(
                sprintf(
                    'Value "%s" does not exists for %s, do you want to use this value anyway? [y/N] ',
                    $value,
                    $field->name()
                ),
                false
            );
            if ($this->questionHelper->ask($input, $output, $q)) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * @param Field $field
     * @return bool
     */
    public function canEditField(Field $field)
    {
        return !empty($field->autocompleteUrl()) || !empty($field->allowedValues());
    }
}
