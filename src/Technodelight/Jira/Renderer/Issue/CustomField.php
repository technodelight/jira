<?php

namespace Technodelight\Jira\Renderer\Issue;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Field;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\Image as ImageRenderer;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Issue\CustomField\Exception;
use Technodelight\Jira\Renderer\Issue\CustomField\Formatter;
use Technodelight\Jira\Renderer\IssueRenderer;

class CustomField implements IssueRenderer
{
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly Api $api,
        private readonly ImageRenderer $imageRenderer,
        private readonly Formatter $formatter,
        private readonly string $customFieldName,
        private readonly bool $inline = false
    ) {
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        $field = $this->lookupField($this->customFieldName);
        if ($value = $issue->findField($field->key())) {
            $content = [
                sprintf('<comment>%s:</>', strtolower($field->name())),
                $this->renderContent($issue, $field, $output, $value)
            ];

            if ($this->inline) {
                $content = implode(' ', $content);
            }

            $output->writeln($this->tab($content));
        }
    }

    /** @throws Exception */
    private function lookupField(string $fieldName): Field
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

    private function tab(array|string $string): string
    {
        return $this->templateHelper->tabulate($string);
    }

    private function renderContent(
        Issue $issue,
        Field $field,
        OutputInterface $output,
        array|string $value
    ): string {
        // if custom field is some kind of atlassian object, we need to extract it's name and description
        if (is_array($value)) {
            return sprintf(
                '%s%s',
                $value['name'] ?? '',
                !empty($value['description']) ? sprintf(' (%)', $value['description']) : ''
            );
        }

        return $this->formatter->format($field, $output, $this->imageRenderer->render($value, $issue));
    }
}
