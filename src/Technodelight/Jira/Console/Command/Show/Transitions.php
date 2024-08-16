<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Show;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionsConfiguration;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Transition;
use Technodelight\JiraTagConverter\Components\PrettyTable;

class Transitions extends Command
{
    public function __construct(
        private readonly Api $jira,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly TransitionsConfiguration $transitionsConf
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:transitions')
            ->setDescription('Show possible transitions for an issue')
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'IssueKey to show transitions for'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);

        if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            $this->renderQuietOutput($issueKey, $output);
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
            return self::SUCCESS;
        }

        $this->renderTableOutput($issueKey, $output);
        return self::SUCCESS;
    }

    private function renderQuietOutput($issueKey, OutputInterface $output): void
    {
        $transitions = $this->jira->retrievePossibleTransitionsForIssue($issueKey);
        foreach ($transitions as $transition) {
            $command = $this->checkHasCommand($transition);
            if (!empty($command)) {
                $output->writeln($command);
            }
        }
    }

    private function renderTableOutput(IssueKey $issueKey, OutputInterface $output): void
    {
        $renderer = new PrettyTable($output);
        $renderer->setHeaders(['Transition', 'Command']);
        $renderer->setRows($this->collectTableRows($issueKey));
        $renderer->render();
    }

    private function checkHasCommand(Transition $transition): string
    {
        try {
            return 'workflow:' . $this->transitionsConf->commandForTransition($transition->name());
        } catch (Exception $exception) {
            return '';
        }
    }

    private function collectTableRows(IssueKey $issueKey): array
    {
        $transitions = $this->jira->retrievePossibleTransitionsForIssue($issueKey);
        $rows = [];
        foreach ($transitions as $transition) {
            $command = $this->checkHasCommand($transition);
            $rows[] = [$transition->name(), $command ? sprintf('jira %s %s', $command, $issueKey) : ''];
        }
        return $rows;
    }
}
