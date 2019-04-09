<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\GitShell\DiffEntry;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Assignee\Assignee as AssigneeInput;
use Technodelight\Jira\Console\Option\Checker;
use Technodelight\Jira\Domain\Issue;
use Technodelight\GitShell\Api as GitShell;
use Technodelight\Jira\Domain\Transition as IssueTransition;
use Technodelight\Jira\Helper\CheckoutBranch;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Action\Issue\Transition\Error;
use Technodelight\Jira\Renderer\Action\Issue\Transition\Success;
use Technodelight\Jira\Renderer\Action\Renderer;
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
     * @var CheckoutBranch
     */
    private $checkoutBranch;
    /**
     * @var GitShell
     */
    private $git;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var Checker
     */
    private $optionChecker;
    /**
     * @var AssigneeInput
     */
    private $assigneeInput;
    /**
     * @var QuestionHelper
     */
    private $questionHelper;
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @param $name
     * @param $transitions
     * @param Api $jira
     * @param IssueKeyResolver $issueKeyResolver
     * @param CheckoutBranch $checkoutBranch
     * @param GitShell $git
     * @param TemplateHelper $templateHelper
     * @param Checker $optionChecker
     * @param AssigneeInput $assignee
     * @param QuestionHelper $questionHelper
     * @param Renderer $renderer
     */
    public function __construct(
        $name,
        $transitions,
        Api $jira,
        IssueKeyResolver $issueKeyResolver,
        CheckoutBranch $checkoutBranch,
        GitShell $git,
        TemplateHelper $templateHelper,
        Checker $optionChecker,
        AssigneeInput $assignee,
        QuestionHelper $questionHelper,
        Renderer $renderer
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
        $this->checkoutBranch = $checkoutBranch;
        $this->git = $git;
        $this->templateHelper = $templateHelper;
        $this->optionChecker = $optionChecker;
        $this->assigneeInput = $assignee;
        $this->questionHelper = $questionHelper;
        $this->renderer = $renderer;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName($this->prepareIssueTransitionCommandName($this->name))
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
        try {
            $issueKey = $this->issueKeyResolver->argument($input, $output);
            $issue = $this->jira->retrieveIssue($issueKey);

            $assignee = null;
            $transitions = $this->jira->retrievePossibleTransitionsForIssue($issueKey);
            $transition = $this->findTransitionByName($transitions, $this->transitions);

            $this->checkGitChanges($input, $output, $transition);

            $this->jira->performIssueTransition($issueKey, $transition);
            if ($input->getOption('assign') || $this->optionChecker->hasOptionWithoutValue($input, 'assign')) {
                $assignee = $this->optionChecker->hasOptionWithoutValue($input, 'assign') ? $this->assigneeInput->userPicker($input, $output) : $input->getOption('assign');
                $this->jira->updateIssue($issueKey, ['fields' => ['assignee' => ['name' => $assignee]]]);
            } else
            if ($input->getOption('unassign')) {
                $assignee = false;
                $this->jira->updateIssue($issueKey, ['fields' => ['assignee' => ['name' => '']]]);
            }

            $issue = $this->jira->retrieveIssue($issueKey);

            $returnCode = $this->renderer->render($output, Success::fromIssueKeyAndAssignee($issueKey, $transition, $assignee));
            $this->checkoutToBranch($input, $output, $issue);
        } catch (Exception $exception) {
            $returnCode = $this->renderer->render($output, Error::fromExceptionIssueKeyTransitions($exception, $issueKey, $this->transitions));
            if (isset($issue)) {
                $this->checkoutToBranch($input, $output, $issue);
            }
        } finally {
            return $returnCode;
        }
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

    private function checkGitChanges(InputInterface $input, OutputInterface $output, IssueTransition $transition)
    {
        if (($diff = $this->git->diff()) && $input->isInteractive()) {
            $output->writeln('It seems you have the following uncommited changes on your current branch:');
            foreach ($diff as $entry) {
                /** @var DiffEntry $entry */
                $output->writeln(
                    $this->templateHelper->tabulate(
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

            if (!$this->questionHelper->ask($input, $output, $question)) {
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

    private function prepareIssueTransitionCommandName($name)
    {
        return sprintf('workflow:%s', $name);
    }
}
