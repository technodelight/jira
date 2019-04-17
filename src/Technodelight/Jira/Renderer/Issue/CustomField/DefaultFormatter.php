<?php

namespace Technodelight\Jira\Renderer\Issue\CustomField;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Field;
use Technodelight\Jira\Api\JiraTagConverter\JiraTagConverter;

class DefaultFormatter implements Formatter
{
    /**
     * @var JiraTagConverter
     */
    private $tagConverter;

    public function __construct(JiraTagConverter $tagConverter)
    {
        $this->tagConverter = $tagConverter;
    }

    public function format(Field $field, OutputInterface $output, $value)
    {
        if ($field->schemaType() == 'number' || $field->schemaType() == 'string' || $field->schemaType() == 'any' && is_string($value)) {
            return $this->tagConverter->convert($output, $value, ['tabulation' => 8]);
        }
        if ($field->schemaType() == 'array' || $field->schemaType() == 'any' && is_array($value)) {
            $value = array_map(
                function($value) {
                    return sprintf('<bg=yellow;fg=black> %s </>', $value);
                },
                $value
            );
            return join(' ', $value);
        }
    }
}
