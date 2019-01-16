<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraTagConverter\Components\PrettyTable;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionsConfiguration;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\Transition;

class Transitions extends Command
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
     * @var TransitionsConfiguration
     */
    private $transitionsConfiguration;

    public function setJiraApi(Api $jira)
    {
        $this->jira = $jira;
    }

    public function setIssueKeyResolver(IssueKeyResolver $issueKeyResolver)
    {
        $this->issueKeyResolver = $issueKeyResolver;
    }

    public function setTransitionsConfig(TransitionsConfiguration $transitionsConfiguration)
    {
        $this->transitionsConfiguration = $transitionsConfiguration;
    }

    protected function configure()
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);

        if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET) {
            $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
            $this->renderQuietOutput($issueKey, $output);
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        } else {
            $this->renderTableOutput($issueKey, $output);
        }
    }

    private function renderQuietOutput($issueKey, OutputInterface $output)
    {
        $transitions = $this->jira->retrievePossibleTransitionsForIssue($issueKey);
        foreach ($transitions as $transition) {
            if ($command = $this->checkHasCommand($transition)) {
                $output->writeln($command);
            }
        }
    }

    private function renderTableOutput($issueKey, OutputInterface $output)
    {
        $renderer = new PrettyTable($output);
        $renderer->setHeaders(['Transition', 'Command']);
        $renderer->setRows($this->collectTableRows($issueKey));
        $renderer->render();
    }

    /**
     * @param Transition $transition
     * @return string
     */
    private function checkHasCommand(Transition $transition)
    {
        try {
            return 'workflow:' . $this->transitionsConfiguration->commandForTransition($transition->name());
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param string $issueKey
     * @return array
     */
    private function collectTableRows($issueKey)
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
