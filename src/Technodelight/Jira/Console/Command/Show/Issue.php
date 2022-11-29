<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Domain\Issue as DomainIssue;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Template\IssueRenderer;

class Issue extends Command implements IssueRendererAware
{
    public function __construct(
        private readonly Api $api,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly IssueRenderer $issueRenderer,
        private readonly WorklogHandler $worklogHandler,
        private readonly Wordwrap $wordwrap
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show')
            ->setDescription('Show an issue')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123), defaults to current issue, taken from branch name'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $issue = $this->api->retrieveIssue($issueKey);

        $this->tryFetchAndAssignWorklogs($output, $issue);

        $this->issueRenderer->render($output, $issue, $input->getOptions());

        return self::SUCCESS;
    }

    private function tryFetchAndAssignWorklogs(OutputInterface $output, DomainIssue $issue): void
    {
        $formatterHelper = new FormatterHelper;
        try {
            $worklogs = $this->worklogHandler->findByIssue($issue);
            $issue->assignWorklogs($worklogs);
        } catch (\Exception $e) {
            $output->writeln(
                $formatterHelper->formatBlock([
                    'Sorry, cannot display worklogs right now...',
                    $this->wordwrap->wrap(join(' ', explode(PHP_EOL, $e->getMessage())))]
                , 'error', true)
            );
        }
    }
}
