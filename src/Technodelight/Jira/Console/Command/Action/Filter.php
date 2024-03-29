<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Action;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\Wordwrap;

class Filter extends Command
{
    public function __construct(
        private readonly Api $jira,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly ExpressionLanguage $exp,
        private readonly Wordwrap $wordwrap
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('filter')
            ->setDescription('Run a filter check on specific fields on the issue key and print out issue key'
                .' if filter matches')
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::REQUIRED,
                'Issue Key where the assignee has to be changed. Can guess from current feature branch'
            )
            ->addArgument(
                'filter',
                InputArgument::REQUIRED,
                'Filter condition, for example "issue.labels in [\'1.60.0\']"'
            )
            ->setHelp($this->assembleHelp());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $condition = $input->getArgument('filter');
            $issueKey = $this->issueKeyResolver->argument($input, $output);
            $issue = $this->jira->retrieveIssue($issueKey);
            if ($this->exp->evaluate($condition, ['issue' => $issue])) {
                $output->writeln((string)$issueKey);
            }
            return self::SUCCESS;
        } catch (Exception $e) {
            $output->writeln($e->getMessage(), OutputInterface::VERBOSITY_VERY_VERBOSE);
            return self::FAILURE;
        }
    }

    private function assembleHelp(): string
    {
        $fields = array_filter(get_class_methods(Issue::class), static function (string $field) {
            return false === in_array($field, ['__call', 'fromArray'], true);
        });

        return $this->wordwrap->wrap("Available fields to use: " . PHP_EOL . '  ' . implode(', ', $fields));
    }
}
