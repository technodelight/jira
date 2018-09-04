<?php

namespace Technodelight\Jira\Console\Command\Action;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;

class Filter extends Command
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;
    /**
     * @var ExpressionLanguage
     */
    private $exp;

    public function __construct(Api $jira, IssueKeyResolver $issueKeyResolver, ExpressionLanguage $exp)
    {
        $this->jira = $jira;
        $this->issueKeyResolver = $issueKeyResolver;
        $this->exp = $exp;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setName('filter')
            ->setDescription('Run a filter check on specific fields on the issue key and print out issue key if filter matches')
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $condition = $input->getArgument('filter');
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $issue = $this->jira->retrieveIssue((string) $issueKey);

        try {
            if ($this->exp->evaluate($condition, ['issue' => $issue])) {
                $output->writeln((string) $issueKey);
            }
        } catch (\Exception $e) {
            $output->writeln($e->getMessage(), OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
    }
}
