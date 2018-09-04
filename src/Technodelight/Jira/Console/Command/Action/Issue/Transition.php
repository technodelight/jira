<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Assignee;
use Technodelight\Jira\Console\Option\Checker;
use Technodelight\Jira\Domain\Issue;
use Technodelight\GitShell\Api as GitShell;
use Technodelight\Jira\Domain\Transition as IssueTransition;
use Technodelight\Jira\Helper\CheckoutBranch;
use Technodelight\Jira\Helper\GitBranchCollector;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use \UnexpectedValueException;

class Transition extends Command
{
    const TRANSITION_DESCRIPTION_SINGLE = 'Moves issue to %s';
    const TRANSITION_DESCRIPTION_MULTIPLE = 'Moves issue to one of: %s (whichever applies first)';

    /**
     * @var string
     */
    private $name;
    /**
     * @var array
     */
    private $transitions;
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var IssueKeyResolver
     */
    private $issueKeyResolver;
    /**
     * @var ApplicationConfiguration
     */
    private $configuration;
    /**
     * @var CheckoutBranch
     */
    private $checkoutBranch;
    /**
     * @var GitBranchCollector
     */
    private $gitBranchCollector;
    /**
     * @var GitShell
     */
    private $git;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var Wordwrap
     */
    private $wordwrap;
    /**
     * @var Checker
     */
    private $optionChecker;
    /**
     * @var Assignee
     */
    private $assignee;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string[] $transitions
     * @param Api $jira
     * @param IssueKeyResolver $issueKeyResolver
     * @param ApplicationConfiguration $configuration
     * @param CheckoutBranch $checkoutBranch
     * @param GitBranchCollector $gitBranchCollector
     * @param GitShell $git
     * @param TemplateHelper $templateHelper
     * @param Wordwrap $wordwrap
     * @param Checker $optionChecker
     * @param Assignee $assignee
     */
    public function __construct(
        $name,
        $transitions,
        Api $jira,
        IssueKeyResolver $issueKeyResolver,
        ApplicationConfiguration $configuration,
        CheckoutBranch $checkoutBranch,
        GitBranchCollector $gitBranchCollector,
        GitShell $git,
        TemplateHelper $templateHelper,
        Wordwrap $wordwrap,
        Checker $optionChecker,
        Assignee $assignee
    )
    {
        if (empty($transitions)) {
            throw new UnexpectedValueException(
                sprintf('No transitions were defined for command: "%s"', $name)
            );
        }

        $this->name = $name;
        $this->transitions = $transitions;
        $this->jira = $jira;
        $this->issueKeyResolver = $issueKeyResolver;
        $this->configuration = $configuration;
        $this->checkoutBranch = $checkoutBranch;
        $this->gitBranchCollector = $gitBranchCollector;
        $this->git = $git;
        $this->templateHelper = $templateHelper;
        $this->wordwrap = $wordwrap;
        $this->optionChecker = $optionChecker;
        $this->assignee = $assignee;

        parent::__construct($this->prepareIssueTransitionCommandName($name));
    }

    protected function configure()
    {
        $this
            ->setDescription($this->getCommandDescription())
            ->setAliases([$this->name])
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)'
            )
            ->addOption(
                'assign',
                'a',
                InputOption::VALUE_OPTIONAL,
                'change assignee'
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        $transitions = $this->jira->retrievePossibleTransitionsForIssue((string) $issueKey);

        try {
            $transition = $this->findTransitionByName($transitions, $this->transitions);
            $this->checkGitChanges($input, $output, $transition);
            $this->jira->performIssueTransition((string) $issueKey, $transition->id());
            $actionString = '';
            if ($input->getOption('assign') || $this->optionChecker->hasOptionWithoutValue($input, 'assign')) {
                $assignee = $this->optionChecker->hasOptionWithoutValue($input, 'assign') ? $this->assignee->userPicker($input, $output) : $input->getOption('assign');
                $this->jira->updateIssue((string) $issueKey, ['fields' => ['assignee' => ['name' => $assignee]]]);
                $actionString = sprintf(' and has been assigned to <fg=cyan>%s</>', $assignee == $this->jira->user()->key() ? 'you' : $assignee);
            } else
            if ($input->getOption('unassign')) {
                $this->jira->updateIssue((string) $issueKey, ['fields' => ['assignee' => ['name' => '']]]);
                $actionString = ' and has been unassigned';
            }

            $issue = $this->jira->retrieveIssue((string) $issueKey);
            $output->writeln(
                sprintf(
                    'Task <info>%s</info> has been successfully moved to <comment>%s</comment>%s',
                    $issueKey,
                    $transition->name(),
                    $actionString
                )
            );
            $output->writeln($this->renderSuccessMessage($issue));

            $output->writeln(['', 'Transitions to move from this state:']);
            $command = $this->getApplication()->get('show:transitions');
            $command->run(new ArrayInput(['issueKey' => $issueKey]), $output);

            $this->checkoutToBranch($input, $output, $issue);
        } catch (UnexpectedValueException $exception) {
            $issue = $this->jira->retrieveIssue((string) $issueKey);

            $this->getApplication()->renderException($exception, $output);
            $output->writeln($this->renderUnsuccesfulMessage($issue, $transitions));
            $this->checkoutToBranch($input, $output, $issue);
            return 1;
        }

        return 0;
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
     * @param IssueTransition[] $transitions
     * @return array
     */
    private function renderUnsuccesfulMessage(Issue $issue, array $transitions)
    {
        return [
            "It seems the issue <info>{$issue->key()}</info> is already <info>{$issue->status()}</info>, and currently assigned to <info>{$issue->assignee()}</info>",
            '',
            '<comment>possible transitions:</comment>',
            $this->tab($this->listTransitions($issue, $transitions)),
            '',
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
        if ($input->getOption('branch')) {
            $this->checkoutBranch->checkoutToBranch($input, $output, $issue);
        }
    }

    /**
     * @param IssueTransition[] $transitions
     * @param array $transitionsToSearchFor
     * @return IssueTransition
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

    private function retrieveGitBranches(Issue $issue)
    {
        return implode(PHP_EOL, $this->gitBranchCollector->forIssueWithAutoGenerated($issue));
    }

    /**
     * @param Issue $issue
     * @param IssueTransition[] $transitions
     * @return array
     */
    private function listTransitions(Issue $issue, array $transitions)
    {
        $list = [];
        foreach ($transitions as $transition) {
            $commandString = '';
            try {
                if ($command = $this->configuration->transitions()->commandForTransition($transition->name())) {
                    $commandString = "<comment>[jira workflow:$command {$issue->key()}]</comment>";
                }
            } catch (\Exception $e) {
            }

            $list[] = sprintf(
                '<info>%s</info> %s' . PHP_EOL . '%s',
                $transition->name(),
                $commandString,
                $this->tab(
                    $this->wordwrap->wrap("Moves issue to <fg=cyan>{$transition->resolvesToName()}</>. {$transition->resolvesToDescription()}")
                )
            );
        }

        return $list;
    }

    private function checkGitChanges(InputInterface $input, OutputInterface $output, IssueTransition $transition)
    {
        $helper = $this->questionHelper();

        if ($diff = $this->git->diff()) {
            $output->writeln('It seems you have the following uncommited changes on your current branch:');
            foreach ($diff as $entry) {
                $output->writeln(
                    $this->tab(
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

    private function getCommandDescription()
    {
        if (count($this->transitions) == 1) {
            return sprintf(self::TRANSITION_DESCRIPTION_SINGLE, current($this->transitions));
        }
        return sprintf(self::TRANSITION_DESCRIPTION_MULTIPLE, join(', ', $this->transitions));
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }

    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getHelper('question');
    }

    private function prepareIssueTransitionCommandName($name)
    {
        return sprintf('workflow:%s', $name);
    }
}
