<?php

namespace Technodelight\Jira\Console\FieldEditor\Editor;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\EditApp\EditApp;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Console\FieldEditor\Editor;
use Technodelight\Jira\Domain\Issue\Meta\Field;

class StringEditor implements Editor
{
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;
    /**
     * @var \Technodelight\Jira\Api\EditApp\EditApp
     */
    private $editApp;

    public function __construct(Api $api, EditApp $editApp)
    {
        $this->api = $api;
        $this->editApp = $editApp;
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
        $issue = $this->api->retrieveIssue($issueKey);
        return $this->editApp->edit(
            sprintf('Edit %s for %s', $field->name(), $issue->key()),
            $this->changeLineEndingsFrom($issue->findField($field->key())) ?: ''
        );
    }

    /**
     * @param Field $field
     * @return bool
     */
    public function canEditField(Field $field)
    {
        return $field->schemaType() == 'string' && empty($field->allowedValues()) && empty($field->autocompleteUrl());
    }

    /**
     * @param string $text
     * @return string
     */
    private function changeLineEndingsFrom($text)
    {
        return join(PHP_EOL, explode("\r\n", $text));
    }
}
