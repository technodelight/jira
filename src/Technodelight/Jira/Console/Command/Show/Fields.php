<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyAutocomplete;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Option\Checker;
use Technodelight\Jira\Domain\Field as DomainField;
use Technodelight\Jira\Domain\Issue\Meta\Field as MetaField;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Fields extends Command
{
    public function __construct(
        private readonly Api $api,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly Checker $optionChecker,
        private readonly IssueKeyAutocomplete $autocomplete
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
                'Check available fields for a specific issue',
                null,
                fn(CompletionInput $i) => $this->autocomplete->autocomplete($i->getCompletionValue())
            )
            ->addOption(
                'like',
                '',
                InputOption::VALUE_REQUIRED,
                'Find fields named like the option'
            )
        ;
    }

    /** @SuppressWarnings(PHPMD) */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->optionChecker->hasOptionWithoutValue($input, 'issueKey')) {
            $issueKey = $this->issueKeyResolver->option($input, $output);
            $tableData = $this->createFieldsTable($input, $this->api->issueEditMeta($issueKey)->fields());
        } else {
            $tableData = $this->createFieldsTable($input, $this->api->fields());
        }
        $table = new PrettyTable($output);
        $table->setHeaders([['Name', 'Key', 'Is custom?', 'Schema', 'Item Type']]);
        foreach ($tableData as $tableRow) {
            $table->addRow($tableRow);
        }
        $table->render();

        return self::SUCCESS;
    }

    private function createFieldsTable(InputInterface $input, array $fields): array
    {
        $table = [];
        foreach ($fields as $field) {
            $like = $input->getOption('like');
            if (!empty($like)
                && !$this->isFieldLike($field, $like)) {
                continue;
            }
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

    private function isFieldLike(MetaField|DomainField $field, mixed $like): bool
    {
        $field = strtolower($field->name());
        $like = strtolower($like);

        return match (true) {
            str_starts_with($like, '%') && str_ends_with($like, '%') => str_contains($field, $like),
            str_starts_with($like, '%') => str_starts_with($field, $like),
            str_ends_with($like, '%') => str_ends_with($field, $like),
            default => str_contains($field, $like)
        };
    }
}
