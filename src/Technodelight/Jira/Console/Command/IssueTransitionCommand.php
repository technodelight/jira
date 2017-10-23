<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Api\GitShell\Api as GitShell;
use Technodelight\Jira\Domain\Transition;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Simplate;
use \UnexpectedValueException;

class IssueTransitionCommand extends AbstractCommand
{
    const TRANSITION_DESCRIPTION_SINGLE = 'Moves issue to %s';
    const TRANSITION_DESCRIPTION_MULTIPLE = 'Moves issue to one of: %s (whichever applies first)';

    private $transitions;

    /**
     * Constructor.
     *
     * @throws \UnexpectedValueException When the command name is empty
     */
    public function __construct(ContainerBuilder $container, $name, $transitions)
    {
        if (empty($transitions)) {
            throw new UnexpectedValueException(
                sprintf('No transitions were defined for command: "%s"', $name)
            );
        }
        $this->transitions = $transitions;
        parent::__construct($container, $name);
    }

    protected function configure()
    {
        $this
            ->setDescription($this->getCommandDescription())
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
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
        $issueKey = $this->issueKeyArgument($input, $output);
        /** @var \Technodelight\Jira\Api\JiraRestApi\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        $issue = $jira->retrieveIssue($issueKey);
        $transitions = $jira->retrievePossibleTransitionsForIssue($issueKey);

        try {
            $transition = $this->findTransitionByName($transitions, $this->transitions);
            $this->checkGitChanges($input, $output, $transition);
            $jira->performIssueTransition($issueKey, $transition->id());
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
                    $transition->name(),
                    $actionString
                )
            );
            $output->writeln($this->renderSuccessMessage($issue));
            $this->checkoutToBranch($input, $output, $issue);
        } catch (UnexpectedValueException $exception) {
            $output->writeln(
                sprintf('<error>%s</error>' . PHP_EOL, $exception->getMessage())
            );
            $output->writeln($this->renderUnsuccesfulMessage($issue, $transitions));
            $this->checkoutToBranch($input, $output, $issue);
            return 1;
        }
    }

    private function renderSuccessMessage(Issue $issue)
    {
        return [
            "<comment>link:</comment> {$issue->url()}",
            '<comment>branches:</comment>',
            $this->tab($this->retrieveGitBranches($issue))
        ];
    }

    /**
     * @param Issue $issue
     * @param Transition[] $transitions
     * @return array
     */
    private function renderUnsuccesfulMessage(Issue $issue, array $transitions)
    {
        return [
            "It seems the issue <info>{$issue->key()}</info> is already <info>{$issue->status()}</info>, and currently assigned to <info>{$issue->assignee()}</info>",
            '',
            'Possible transitions:',
            $this->tab($this->listTransitions($issue, $transitions)),
            "<comment>link:</comment> {$issue->url()}"
        ];
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     */
    private function checkoutToBranch(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        $git = $this->gitShell();
        if ($input->getOption('branch')) {
            if (!$this->gitBranchesForIssue($issue)) {
                $branchName = $this->getProperBranchName($input, $output, $issue);
                $output->writeln('Checking out to new branch: ' . $branchName);
                $git->createBranch($branchName);
            } else {
                $this->chooseBranch($input, $output, $issue);
            }
        }
    }

    /**
     * @param Transition[] $transitions
     * @param array $transitionsToSearchFor
     * @return Transition
     * @throws \UnexpectedValueException
     */
    private function findTransitionByName(array $transitions, $transitionsToSearchFor)
    {
        foreach ($transitionsToSearchFor as $name) {
            foreach ($transitions as $transition) {
                if ($transition->name() == $name) {
                    return $transition;
                }
            }
        }

        throw new UnexpectedValueException(
            sprintf('Cannot apply any transition from %s for this issue', join(', ', $transitionsToSearchFor))
        );
    }

    private function gitBranchesForIssue(Issue $issue)
    {
        return array_map(
            function(Branch $branch) {
                return sprintf('%s (%s)', $branch->name(), $branch->isRemote() ? 'remote' : 'local');
            },
            $this->gitShell()->branches($issue->ticketNumber())
        );
    }

    private function generateBranchName(Issue $issue)
    {
        return $this->branchnameGenerator()->fromIssue($issue);
    }

    private function generateBranchNameWithAutocomplete(Issue $issue)
    {
        return $this->branchnameGenerator()->fromIssueWithAutocomplete($issue);
    }

    private function retrieveGitBranches(Issue $issue)
    {
        $branches = $this->gitBranchesForIssue($issue);
        if (empty($branches)) {
            $branches = [
                $this->generateBranchName($issue) . ' (generated)',
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
        $helper = $this->questionHelper();
        $generatedBranchOption = $this->generateBranchName($issue) . ' (generated)';
        $question = new ChoiceQuestion(
            'Select branch to checkout to',
            array_merge($this->gitBranchesForIssue($issue), [$this->generateBranchName($issue) . ' (generated)']),
            0
        );
        $question->setErrorMessage('Branch %s is invalid.');

        $branchName = $helper->ask($input, $output, $question);

        $selectedBranch = '';
        /** @var GitShell $git */
        $git = $this->gitShell();
        $branches = $git->branches($issue->issueKey());
        $new = false;
        foreach ($branches as $branch) {
            /** @var Branch $branch */
            if ($branchName == (string) $branch) {
                $selectedBranch = $branch->name();
                break;
            }
        }
        if (!$selectedBranch && ($branchName == $generatedBranchOption)) {
            $selectedBranch = $this->getProperBranchName($input, $output, $issue);
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
     * @param Issue $issue
     * @param Transition[] $transitions
     * @return array
     */
    private function listTransitions(Issue $issue, array $transitions)
    {
        $list = [];
        foreach ($transitions as $transition) {
            $commandString = '';
            if ($command = $this->config()->transitions()->commandForTransition($transition->name())) {
                $commandString = "(jira $command {$issue->key()})";
            }
            $list[] = sprintf(
                '- <info>%s</info> %s' . PHP_EOL . '%s',
                $transition->name(),
                $commandString,
                $this->tab($this->tab(
                    wordwrap("Moves issue to <comment>{$transition->resolvesToName()}</comment>. {$transition->resolvesToDescription()}")
                ))
            );
        }

        return $list;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Transition $transition
     * @throws \RuntimeException
     */
    private function checkGitChanges(InputInterface $input, OutputInterface $output, Transition $transition)
    {
        $git = $this->gitShell();
        $helper = $this->questionHelper();
        $templateHelper = $this->templateHelper();

        if ($diff = $git->diff()) {
            $output->writeln('It seems you have the following uncommited changes on your current branch:');
            foreach ($diff as $entry) {
                $output->writeln(
                    $templateHelper->tabulate(
                        sprintf('<comment>%s</comment> %s', $entry->state(), $entry->file())
                    )
                );
            }
            $question = new ConfirmationQuestion(
                sprintf(
                    'Are you sure you want to perform the <comment>%s</comment> transition?  [Y/n] ',
                    $transition->name()
                ),
                true
            );

            if (!$helper->ask($input, $output, $question)) {
                throw new \RuntimeException('Please commit your changes first.');
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $branchName
     * return bool
     */
    protected function isShorteningBranchNameConfirmed(InputInterface $input, OutputInterface $output, $branchName)
    {
        return $this->questionHelper()->ask(
            $input,
            $output,
            new ConfirmationQuestion(
                'The generated branch name seems to be too long. Do you want to shorten it?' . PHP_EOL
                . '(' . $branchName . ')' . PHP_EOL
                . '[Y/n] ? ',
                true
            )
        );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     * @return string
     */
    private function getProperBranchName(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        $selectedBranch = $this->generateBranchName($issue);
        if ((strlen($selectedBranch) > $this->config()->maxBranchNameLength())
            && $this->isShorteningBranchNameConfirmed($input, $output, $selectedBranch)) {
            $selectedBranch = $this->generateBranchNameWithAutocomplete($issue);
        }

        return $selectedBranch;
    }

    /**
     * @return string
     */
    protected function getCommandDescription()
    {
        if (count($this->transitions) == 1) {
            return sprintf(self::TRANSITION_DESCRIPTION_SINGLE, current($this->transitions));
        }
        return sprintf(self::TRANSITION_DESCRIPTION_MULTIPLE, join(', ', $this->transitions));
    }

    private function tab($string)
    {
        return $this->templateHelper()->tabulate($string);
    }

    /**
     * @return \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private function config()
    {
        return $this->getService('technodelight.jira.config');
    }

    /**
     * @return \Technodelight\Jira\Helper\GitBranchnameGenerator
     */
    private function branchnameGenerator()
    {
        return $this->getService('technodelight.jira.git_branchname_generator');
    }

    /**
     * @return \Technodelight\Jira\Api\GitShell\Api
     */
    private function gitShell()
    {
        /** @var GitShell $git */
        return $this->getService('technodelight.gitshell.api');
    }

    /**
     * @return TemplateHelper
     */
    private function templateHelper()
    {
        return $this->getService('technodelight.jira.template_helper');
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getHelper('question');
    }
}
