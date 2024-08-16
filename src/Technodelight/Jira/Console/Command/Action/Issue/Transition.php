<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Exception;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\GitShell\DiffEntry;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\AssigneeAutocomplete;
use Technodelight\Jira\Console\Argument\IssueKeyAutocomplete;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Input\Issue\Assignee\Assignee as AssigneeInput;
use Technodelight\Jira\Console\Input\Issue\Assignee\AssigneeResolver;
use Technodelight\Jira\Console\Option\Checker;
use Technodelight\Jira\Domain\Issue;
use Technodelight\GitShell\ApiInterface as GitShell;
use Technodelight\Jira\Domain\Issue\IssueKey;
use Technodelight\Jira\Domain\Transition as IssueTransition;
use Technodelight\Jira\Helper\CheckoutBranch;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Action\Issue\Transition\Error;
use Technodelight\Jira\Renderer\Action\Issue\Transition\Success;
use Technodelight\Jira\Renderer\Action\Renderer;
use \UnexpectedValueException;

class Transition extends Command
{
    private const TRANSITION_DESCRIPTION_SINGLE = 'Moves issue to %s';
    private const TRANSITION_DESCRIPTION_MULTIPLE = 'Moves issue to one of: %s (whichever applies first)';

    public function __construct(
        private readonly string $name,
        private readonly array $transitions,
        private readonly Api $jira,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly CheckoutBranch $checkoutBranch,
        private readonly GitShell $git,
        private readonly TemplateHelper $templateHelper,
        private readonly Checker $optionChecker,
        private readonly AssigneeInput $assigneeInput,
        private readonly QuestionHelper $questionHelper,
        private readonly Renderer $renderer,
        private readonly AssigneeAutocomplete $assigneeAutocomplete,
        private readonly IssueKeyAutocomplete $issueKeyAutocomplete
    )
    {
        if (empty($transitions)) {
            throw new UnexpectedValueException(
                sprintf('No transitions were defined for command: "%s"', $name)
            );
        }

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName($this->prepareIssueTransitionCommandName($this->name))
            ->setDescription($this->getCommandDescription())
            ->setAliases([$this->name])
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)',
                null,
                fn(CompletionInput $completionInput)
                    => $this->issueKeyAutocomplete->autocomplete($completionInput->getCompletionValue())
            )
            ->addOption(
                'assign',
                'a',
                InputOption::VALUE_OPTIONAL,
                'change assignee',
                AssigneeResolver::DEFAULT_ASSIGNEE,
                fn(CompletionInput $completionInput)
                    => $this->assigneeAutocomplete->autocomplete($completionInput->getCompletionValue())
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

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $issueKey = $this->issueKeyResolver->argument($input, $output);
            $issue = $this->jira->retrieveIssue($issueKey);
            $assignee = null;
            $transitions = $this->jira->retrievePossibleTransitionsForIssue($issueKey);
            $transition = $this->findTransitionByName($transitions, $this->transitions);

            $this->checkGitChanges($input, $output, $transition);

            $this->jira->performIssueTransition($issueKey, $transition);

            $assignee = $this->getAssignee($input, $output, $assignee, $issueKey);

            $issue = $this->jira->retrieveIssue($issueKey);

            $returnCode = $this->renderer->render(
                $output,
                Success::fromIssueKeyAndAssignee($issueKey, $transition, $assignee)
            );
            $this->checkoutToBranch($input, $output, $issue);
        } catch (Exception $exception) {
            $returnCode = $this->renderer->render(
                $output,
                Error::fromExceptionIssueKeyTransitions(
                    $exception, $issueKey ?? '- no issue key -', $this->transitions
                )
            );
            if (isset($issue)) {
                $this->checkoutToBranch($input, $output, $issue);
            }
        }

        return $returnCode;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param mixed $assignee
     * @param IssueKey $issueKey
     * @return false|mixed
     */
    private function getAssignee(
        InputInterface $input,
        OutputInterface $output,
        mixed $assignee,
        IssueKey $issueKey
    ): mixed {
        if ($input->getOption('assign') || $this->optionChecker->hasOptionWithoutValue($input, 'assign')) {
            $assignee = $this->optionChecker->hasOptionWithoutValue($input, 'assign')
                ? $this->assigneeInput->userPicker($input, $output)
                : $input->getOption('assign');
            $this->jira->assignIssue($issueKey, $assignee);
        } elseif ($input->getOption('unassign')) {
            $assignee = false;
            $this->jira->assignIssue($issueKey, AssigneeResolver::UNASSIGN);
        }

        return $assignee;
    }

    private function checkoutToBranch(InputInterface $input, OutputInterface $output, Issue $issue): void
    {
        if ($input->getOption('branch')) {
            $this->checkoutBranch->checkoutToBranch($input, $output, $issue);
        }
    }

    private function findTransitionByName(array $transitions, array $transitinsToSearch): IssueTransition
    {
        foreach ($transitinsToSearch as $name) {
            foreach ($transitions as $transition) {
                if ($transition->name() == $name) {
                    return $transition;
                }
            }
        }

        throw new UnexpectedValueException(
            sprintf('Cannot apply any transition from %s for this issue', join(', ', $transitinsToSearch))
        );
    }

    private function checkGitChanges(InputInterface $input, OutputInterface $output, IssueTransition $transition): void
    {
        $diff = $this->git->diff();
        if (!empty($diff) && $input->isInteractive()) {
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
                throw new LogicException('Please commit your changes first.');
            }
        }
    }

    private function getCommandDescription(): string
    {
        if (count($this->transitions) === 1) {
            return sprintf(self::TRANSITION_DESCRIPTION_SINGLE, current($this->transitions));
        }
        return sprintf(self::TRANSITION_DESCRIPTION_MULTIPLE, join(', ', $this->transitions));
    }

    private function prepareIssueTransitionCommandName($name): string
    {
        return sprintf('workflow:%s', $name);
    }
}
