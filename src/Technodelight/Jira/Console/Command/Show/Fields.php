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
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Fields extends Command
{
    private $api;
    private $issueKeyResolver;
    private $optionChecker;

    public function __construct(Api $api, IssueKeyResolver $issueKeyResolver, Checker $optionChecker)
    {
        $this->api = $api;
        $this->issueKeyResolver = $issueKeyResolver;
        $this->optionChecker = $optionChecker;

        parent::__construct();
    }

    protected function configure()
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->optionChecker->hasOptionWithoutValue($input, 'issueKey')) {
            $issueKey = $this->issueKeyResolver->option($input, $output);
            $table = $this->createFieldsTable($this->api->issueEditMeta($issueKey)->fields());
        } else {
            $table = $this->createFieldsTable($this->api->fields());
        }
        $renderer = new PrettyTable($output);
        $renderer
            ->setHeaders(array_shift($table))
            ->setRows(array_values($table));
        $renderer->render();
    }

    /**
     * @param Field[]|\Technodelight\Jira\Domain\Issue\Meta\Field[] $fields
     * @return array
     */
    protected function createFieldsTable($fields)
    {
        $table = [['Name', 'Key', 'Is custom?', 'Schema', 'Item Type']];
        foreach ($fields as $field) {
            $table[] = ['<comment>'.$field->name() . '</comment>', $field->key(), $field->isCustom() ? 'Yes' : 'No', $field->schemaType(), $field->schemaItemType()];
        }
        return $table;
    }
}
