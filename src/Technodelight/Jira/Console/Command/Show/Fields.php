<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Option\Checker;
use Technodelight\Jira\Domain\Field;
use Technodelight\JiraTagConverter\Components\Table;

class Fields extends Command
{
    public function __construct(
        private readonly Api $api,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly Checker $optionChecker
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:fields')
            ->setDescription('List all available issue fields')
            ->addOption(
                'issueKey',
                '',
                InputOption::VALUE_OPTIONAL,
                'Check fields for a concrete issue',
                ''
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->optionChecker->hasOptionWithoutValue($input, 'issueKey')) {
            $issueKey = $this->issueKeyResolver->option($input, $output);
            $tableData = $this->createFieldsTable($this->api->issueEditMeta($issueKey)->fields());
        } else {
            $tableData = $this->createFieldsTable($this->api->fields());
        }
        $table = new Table();
        $table->setHeaders(array_shift($tableData));
        foreach ($tableData as $tableRow) {
            $table->addRow($tableRow);
        }
        $output->writeln((string)$table);

        return self::SUCCESS;
    }

    private function createFieldsTable(array $fields): array
    {
        $table = [['Name', 'Key', 'Is custom?', 'Schema', 'Item Type']];
        foreach ($fields as $field) {
            $table[] = [
                '<comment>' . $field->name() . '</comment>',
                $field->key(),
                $field->isCustom() ? 'Yes' : 'No',
                $field->schemaType(),
                $field->schemaItemType()
            ];
        }
        return $table;
    }
}
