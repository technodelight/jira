<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Field;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Issue\CustomField\Exception;
use Technodelight\Jira\Renderer\Issue\CustomField\Formatter;
use Technodelight\Jira\Renderer\IssueRenderer;

class CustomField implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private $api;
    /**
     * @var \Technodelight\Jira\Renderer\Issue\CustomField\Formatter
     */
    private $formatter;
    /**
     * @var string
     */
    private $customFieldName;
    /**
     * @var bool
     */
    private $inline;

    public function __construct(TemplateHelper $templateHelper, Api $api, Formatter $formatter, $customFieldName, $inline = false)
    {
        $this->templateHelper = $templateHelper;
        $this->api = $api;
        $this->formatter = $formatter;
        $this->customFieldName = $customFieldName;
        $this->inline = $inline;
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        $field = $this->lookupField($this->customFieldName);
        if ($value = $issue->findField($field->key())) {
            if ($this->inline) {
                $output->writeln($this->tab(
                    sprintf(
                        '<comment>%s:</> %s',
                        strtolower($field->name()),
                        $this->formatter->format($field, $output, $value)

                    )
                ));
            } else {
                $output->writeln(
                    $this->tab(sprintf('<comment>%s:</>', strtolower($field->name())))
                );
                $output->writeln(
                    $this->tab($this->tab($this->formatter->format($field, $output, $value)))
                );
            }
        }
    }

    /**
     * @param string $fieldName
     * @return Field
     * @throws \InvalidArgumentException
     */
    private function lookupField($fieldName)
    {
        $fields = array_filter(
            $this->api->fields(),
            function(Field $field) use ($fieldName) {
                return $field->name() == $fieldName;
            }
        );
        if (empty($fields)) {
            throw Exception::fromMissingField($fieldName);
        }
        if (count($fields) > 1) {
            throw Exception::fromMultipleMatchingFields($fieldName, $fields);
        }

        return reset($fields);
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
