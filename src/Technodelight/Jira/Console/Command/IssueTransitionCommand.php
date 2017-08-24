<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Api\Issue;
use Technodelight\Jira\Helper\GitHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Simplate;
use \UnexpectedValueException;

class IssueTransitionCommand extends AbstractCommand
{
    const TRANSITION_DESCRIPTION = 'Move issue to %s';

    private $transitionName;

    /**
     * Constructor.
     *
     * @throws \UnexpectedValueException When the command name is empty
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
        /** @var \Technodelight\Jira\Api\JiraRestApi\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        $issue = $jira->retrieveIssue($issueKey);
        $transitions = $jira->retrievePossibleTransitionsForIssue($issueKey);
        /** @var GitHelper $git */
        $git = $this->getService('technodelight.jira.git_helper');

        try {
            $this->checkGitChanges($output);
            $transition = $this->filterTransitionByName($transitions, $this->transitionName);
            $jira->performIssueTransition($issueKey, $transition['id']);
            $actionString = '';
            if ($input->getOption('assign')) {
                $jira->updateIssue($issueKey, ['fields' => ['assignee' => ['name' => $jira->user()->name()]]]);
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
            if (!$this->gitBranchesForIssue($issue)) {
                $branchName = $this->generateBranchName($issue);
                $output->writeln('Checking out to new branch: ' . $branchName);
                $git->createBranch($branchName);
            } else {
                $this->chooseBranch($input, $output, $issue);
            }
        }

        /** @var TemplateHelper $templateHelper */
        $templateHelper = $this->getService('technodelight.jira.template_helper');
        $output->writeln(
            Simplate::fromFile($this->getApplication()->directory('views') . '/Commands/transition.template')->render(
                [
                    'success' => $success,
                    'issueKey' => $issue->ticketNumber(),
                    'transitionName' => $this->transitionName,
                    'transitionsNames' => $templateHelper->tabulate($this->listTransitions($transitions, $issue)),
                    'status' => $issue->status(),
                    'asignee' => $issue->assignee(),
                    'url' => $issue->url(),
                    'branches' => $templateHelper->tabulate($this->retrieveGitBranches($issue)),
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
                return sprintf('%s (%s)', $branchData['name'], $branchData['remote'] ? 'remote' : 'local');
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

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     * @return void
     * @throws \LogicException if can't select branch
     */
    private function chooseBranch(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $generatedBranchOption = $this->generateBranchName($issue) . ' (generated)';
        $question = new ChoiceQuestion(
            'Select branch to checkout to',
            array_merge($this->gitBranchesForIssue($issue), [$this->generateBranchName($issue) . ' (generated)']),
            0
        );
        $question->setErrorMessage('Branch %s is invalid.');

        $branchName = $helper->ask($input, $output, $question);

        $selectedBranch = '';
        /** @var \Technodelight\Jira\Api\GitShell\Api $git */
        $git = $this->getService('technodelight.gitshell.api');
        $branches = $git->branches($issue->issueKey());
        $new = false;
        foreach ($branches as $branch) {
            /** @var \Technodelight\Jira\Api\GitShell\Branch $branch */
            if ($branchName == (string) $branch) {
                $selectedBranch = $branch->name();
                break;
            }
        }
        if (!$selectedBranch && ($branchName == $generatedBranchOption)) {
            $selectedBranch = $this->generateBranchName($issue);
            $new = true;
        }
        if (!$selectedBranch) {
            throw new \LogicException(sprintf('Cannot select branch %s', $branchName));
        }

        if ($new) {
            $output->writeln('Checking out to new branch: ' . $selectedBranch);
            $git->createBranch($selectedBranch);
        } else {
            $output->writeln('Checking out to: ' . $selectedBranch);
            $git->switchBranch($selectedBranch);
        }
    }

    /**
     * @param array $transitions
     * @param \Technodelight\Jira\Api\Issue $issue
     * @return string
     */
    private function listTransitions(array $transitions, Issue $issue)
    {
        $transitionNames = $this->transitionsNames($transitions);
        /** @var \Technodelight\Jira\Configuration\ApplicationConfiguration $config */
        $config = $this->getService('technodelight.jira.config');
        $transitionMap = $config->transitions();
        $list = [];
        foreach ($transitionNames as $transitionName) {
            if ($command = array_search($transitionName, $transitionMap)) {
                $list[] = sprintf(
                    '- <info>%s</info> (jira %s %s)',
                    $transitionName,
                    $command,
                    $issue->issueKey()
                );
            } else {
                $list[] = sprintf(
                    '- <info>%s</info>',
                    $transitionName
                );
            }
        }
        return implode(PHP_EOL, $list);
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \RuntimeException
     */
    private function checkGitChanges(OutputInterface $output)
    {
        /** @var \Technodelight\Jira\Api\GitShell\Api $git */
        $git = $this->getService('technodelight.gitshell.api');
        /** @var TemplateHelper $templateHelper */
        $templateHelper = $this->getService('technodelight.jira.template_helper');
        if ($diff = $git->diff()) {
            /** @var \Symfony\Component\Console\Helper\DialogHelper $dialog */
            $dialog = $this->getService('console.dialog_helper');
            $output->writeln('It seems you have the following uncommited changes on your current branch:');
            foreach ($diff as $entry) {
                $output->writeln(
                    $templateHelper->tabulate(
                        sprintf('<comment>%s</comment> %s', $entry->state(), $entry->file())
                    )
                );
            }

            if (!$dialog->askConfirmation($output, 'Do you want to continue? [Y/n] ')) {
                throw new \RuntimeException('Please commit your changes first.');
            }
        }
    }
}
