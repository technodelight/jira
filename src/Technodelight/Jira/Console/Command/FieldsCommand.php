<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FieldsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('fields')
            ->setDescription('List all available issue fields')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = $this->createFieldsTable();
        $renderer = new Table($output);
        $renderer
            ->setHeaders(array_shift($table))
            ->setRows(array_values($table));
        $renderer->render();
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function api()
    {
        return $this->getService('technodelight.jira.api');
    }

    protected function createFieldsTable()
    {
        $fields = $this->api()->fields();
        $table = [['Name', 'Is custom?', 'Schema']];
        foreach ($fields as $field) {
            $table[] = [$field->name(), $field->isCustom() ? 'Yes' : 'No', $field->schemaType()];
        }
        return $table;
    }
}
