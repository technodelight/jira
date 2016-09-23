<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Configuration\Configuration;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Template\Template;
use Technodelight\Simplate;
use \UnexpectedValueException;

class IssueTransitionCommand extends AbstractCommand
{
    const TRANSITION_DESCRIPTION = 'Move issue to %s';

    private $transitionName;

    /**
     * Constructor.
     *
     * @throws LogicException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name, $transitionName)
    {
        if (empty($transitionName)) {
            throw new UnexpectedValueException(
                sprintf('Undefined transition: "%s"', $name)
            );
        }
        $this->transitionName = $transitionName;
        parent::__construct($container, $name);
    }

    protected function configure()
    {
        $this
            ->setDescription(sprintf(self::TRANSITION_DESCRIPTION, $this->transitionName))
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)'
            )
            ->addOption(
                'assign',
                'a',
                InputOption::VALUE_NONE,
                'change assignee to you'
            )
            ->addOption(
                'unassign',
                'u',
                InputOption::VALUE_NONE,
                'unassign issue'
            )
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_NONE,
                'generate/choose branch'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input);
        $jira = $this->getService('technodelight.jira.api');
        $issue = $jira->retrieveIssue($issueKey);
        $transitions = $jira->retrievePossibleTransitionsForIssue($issueKey);
        $git = $this->getService('technodelight.jira.git_helper');

        try {
            $transition = $this->filterTransitionByName($transitions, $this->transitionName);
            $jira->performIssueTransition($issueKey, $transition['id']);
            $actionString = '';
            if ($input->getOption('assign')) {
                $jira->updateIssue($issueKey, ['fields' => ['assignee' => ['name' => $jira->user()['name']]]]);
                $issue = $jira->retrieveIssue($issueKey);
                $actionString = ' and has been assigned to you';
            } else
            if ($input->getOption('unassign')) {
                $jira->updateIssue($issueKey, ['fields' => ['assignee' => ['name' => '']]]);
                $issue = $jira->retrieveIssue($issueKey);
                $actionString = ' and has been unassigned';
            }

            $output->writeln(
                sprintf(
                    'Task <info>%s</info> has been successfully moved to <comment>%s</comment>%s',
                    $issueKey,
                    $this->transitionName,
                    $actionString
                )
            );
            $success = true;
        } catch (UnexpectedValueException $exception) {
            $output->writeln(
                sprintf('<error>%s</error>' . PHP_EOL, $exception->getMessage())
            );
            $success = false;
        }

        if ($input->getOption('branch')) {
            $branches = $this->gitBranchesForIssue($issue);
            if (empty($branches)) {
                $branchName = $this->generateBranchName($issue);
                $output->writeln('Checking out to new branch: ' . $branchName);
                $git->createBranch($branchName);
            } else {
                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    'Select branch to checkout to',
                    $branches,
                    0
                );
                $question->setErrorMessage('Branch %s is invalid.');

                $branchName = $helper->ask($input, $output, $question);
                $output->writeln('Checking out to: ' . $branchName);
                $git->switchBranch($branchName);
            }
        }

        $output->writeln(
            Simplate::fromFile($this->getApplication()->directory('views') . '/Commands/transition.template')->render(
                [
                    'success' => $success,
                    'issueKey' => $issue->ticketNumber(),
                    'transitionName' => $this->transitionName,
                    'transitionsNames' => implode(', ', $this->transitionsNames($transitions)),
                    'status' => $issue->status(),
                    'asignee' => $issue->assignee(),
                    'url' => $issue->url(),
                    'branches' => $this->getService('technodelight.jira.template_helper')->tabulate($this->retrieveGitBranches($issue)),
                ]
            )
        );
    }

    private function transitionsNames(array $transitions)
    {
        $names = [];
        foreach ($transitions as $transition) {
            $names[] = $transition['name'];
        }

        return $names;
    }

    private function filterTransitionByName($transitions, $name)
    {
        foreach ($transitions as $transition) {
            if ($transition['name'] == $name) {
                return $transition;
            }
        }

        throw new UnexpectedValueException(
            sprintf('No "%s" transition available for this issue', $name)
        );
    }

    private function gitBranchesForIssue(Issue $issue)
    {
        return array_map(
            function (array $branchData) {
                return $branchData['name'];
            },
            $this->getService('technodelight.jira.git_helper')->branches($issue->ticketNumber())
        );
    }

    private function generateBranchName(Issue $issue)
    {
        return $this->getService('technodelight.jira.git_branchname_generator')->fromIssue($issue);
    }

    private function retrieveGitBranches(Issue $issue)
    {
        $branches = $this->gitBranchesForIssue($issue);
        if (empty($branches)) {
            $branches = [
                $this->generateBranchName($issue) . ' (generated)'
            ];
        }

        return implode(PHP_EOL, $branches);
    }
}
