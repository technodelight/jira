<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Action\Issue;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\JiraRestApi\DateHelper;
use Technodelight\Jira\Connector\WorklogHandler;
use Technodelight\Jira\Console\Argument\DateResolver;
use Technodelight\Jira\Console\Argument\InteractiveIssueSelector;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogId;
use Technodelight\Jira\Console\Argument\IssueKeyOrWorklogIdResolver;
use Technodelight\Jira\Console\Argument\LogTimeArgsOptsParser;
use Technodelight\Jira\Console\Dashboard\WorklogFetcher;
use Technodelight\Jira\Console\Input\Worklog\Comment as CommentInput;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Renderer\DashboardRenderer;
use Technodelight\Jira\Renderer\Issue\Header as HeaderRenderer;
use Technodelight\Jira\Renderer\Issue\Worklog as WorklogRenderer;
use UnexpectedValueException;

class LogTime extends Command
{
    public function __construct(
        private readonly Api $jira,
        private readonly IssueKeyOrWorklogIdResolver $ikResolver,
        private readonly InteractiveIssueSelector $issueSelector,
        private readonly CommentInput $commentInput,
        private readonly DateResolver $dateResolver,
        private readonly QuestionHelper $questionHelper,
        private readonly WorklogHandler $worklogHandler,
        private readonly DateHelper $dateHelper,
        private readonly WorklogRenderer $worklogRenderer,
        private readonly HeaderRenderer $headerRenderer,
        private readonly DashboardRenderer $dashboardRenderer,
        private readonly WorklogFetcher $dashDataProvider
    ) {
        parent::__construct();
    }

    public function fetchRelevantData(IssueKeyOrWorklogId $identifier): array
    {
        if ($identifier->isWorklogId()) {
            $workLog = $this->worklogHandler->retrieve($identifier->worklogId()->id());
            $issue = $this->jira->retrieveIssue($workLog->issueId());
            return [$workLog, $issue];
        }

        return [null, $this->jira->retrieveIssue($identifier->issueKey())];
    }

    protected function configure(): void
    {
        $this
            ->setName('issue:log')
            ->setDescription('Log work against issue')
            ->setAliases(['log', 'worklog'])
            ->addArgument(
                IssueKeyOrWorklogIdResolver::NAME,
                InputArgument::OPTIONAL,
                'Issue key, like PROJ-123 OR a specific work log\'s ID'
            )
            ->addArgument(
                'time',
                InputArgument::OPTIONAL,
                'Time you spent with the issue, like \'1h\''
            )
            ->addArgument(
                'comment',
                InputArgument::OPTIONAL,
                'Add comment to work log'
            )
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Date to put your log to, like \'yesterday 12:00\' or \'' . date('Y-m-d')
                . '\', anything http://php.net/strtotime can parse'
            )
            ->addOption(
                'delete',
                'd',
                InputOption::VALUE_NONE,
                'Delete worklog'
            )
            ->addOption(
                'move',
                'm',
                InputOption::VALUE_REQUIRED,
                'Move work log to another date',
                false
            )
            ->addOption(
                'interactive',
                'I',
                InputOption::VALUE_NONE,
                'Log time interactively'
            )
            ->addOption(
                'keep-default-comment',
                'k',
                InputOption::VALUE_NONE,
                'Keep default comment (Worked on ISSUE-123 or the previous comment when updating existing work log'
            )
        ;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $argParser = LogTimeArgsOptsParser::fromArgsOpts($input->getArguments(), $input->getOptions());

        $input->setArgument(IssueKeyOrWorklogIdResolver::NAME, $argParser->issueKeyOrWorklogId());
        $input->setArgument('time', $argParser->time());
        $input->setArgument('comment', $argParser->comment());
        $input->setArgument('date', $argParser->date());

        if ($input->getOption('interactive')) {
            return;
        }

        $issueKeyOrWorklogId = $this->ikResolver->argument($input);

        // fix when no arguments, but you want to log your time to current issue specified by branch
        if (!$input->getArgument('issueKeyOrWorklogId') && $issueKeyOrWorklogId->isEmpty()) {
            $input->setOption('interactive', $argParser->isInteractive());
            return;
        }

        if ($input->getOption('delete') || $input->getOption('move')) {
            return;
        }

        // show issue header
        if ($issueKeyOrWorklogId->isIssueKey()) {
            $this->headerRenderer->render(
                $output,
                $this->jira->retrieveIssue($issueKeyOrWorklogId->issueKey())
            );
        }

        if (!$input->getArgument('time')) {
            $input->setArgument(
                'time',
                $this->askForTimeToLog(
                    $input,
                    $output,
                    $issueKeyOrWorklogId->issueKey() ? (string)$issueKeyOrWorklogId->issueKey() : '',
                    $issueKeyOrWorklogId->worklog()
                )
            );
        }

        if (!$input->getArgument('comment')) {
            list($worklog, $issue) = $this->fetchRelevantData($issueKeyOrWorklogId);

            $input->setArgument(
                'comment',
                $this->commentInput->read($input, $output, $issue, $worklog, $input->getOption('keep-default-comment'))
            );
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('interactive')) {
            return $this->interactiveTimelog($input, $output);
        }

        return $this->doWorklog($input, $output);
    }

    private function doWorklog(InputInterface $input, OutputInterface $output): int
    {
        $issueKeyOrWorklogId = $this->ikResolver->argument($input);
        $timeSpent = $input->getArgument('time') ?: null;
        $comment = $input->getArgument('comment') ?: null;
        $worklogDate = $input->getOption('move') ? $this->dateResolver->option($input, 'move') : null;

        if ($issueKeyOrWorklogId->isWorklogId()) {
            return $this->processExistingWorklog(
                $input,
                $output,
                $issueKeyOrWorklogId,
                $timeSpent,
                $comment,
                $worklogDate
            );
        }

        return $this->processNewWorklog($input, $output, $issueKeyOrWorklogId, $timeSpent, $comment);
    }

    private function processExistingWorklog(
        InputInterface $input,
        OutputInterface $output,
        IssueKeyOrWorklogId $issueKeyOrWorklogId,
        $timeSpent = null,
        $comment = null,
        $worklogDate = null
    ): int {
        try {
            if ($input->getOption('delete')) {
                $this->deleteWorklog($issueKeyOrWorklogId->worklog());
                $output->writeln(
                    sprintf(
                        '<comment>Worklog <info>%s</info> has been deleted successfully</comment>',
                        $issueKeyOrWorklogId->worklog()->id()
                    )
                );
                return Command::SUCCESS;
            }

            $this->updateWorklog($issueKeyOrWorklogId->worklog(), $timeSpent, $comment, $worklogDate);
            $output->writeln(
                sprintf(
                    '<comment>Worklog <info>%s</info> has been updated</comment>',
                    $issueKeyOrWorklogId->worklog()->id()
                )
            );

            return Command::SUCCESS;
        } catch (UnexpectedValueException $exception) {
            $output->writeln($exception->getMessage());

            return 1;
        } catch (\Exception $exception) {
            $output->writeln(
                sprintf(
                    '<error>Something bad happened while processing %s</error>',
                    $issueKeyOrWorklogId->worklogId()
                )
            );
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }
    }

    private function processNewWorklog(
        InputInterface $input,
        OutputInterface $output,
        IssueKeyOrWorklogId $issueKeyOrWorklogId,
        $timeSpent = null,
        $comment = null
    ): int {
        if (!$timeSpent) {
            $output->writeln('<error>You need to specify the issue and time arguments at least</error>');

            return self::FAILURE;
        }

        $worklog = $this->logNewWork(
            $issueKeyOrWorklogId->issueKey(),
            $timeSpent,
            $comment ?: 'Worked on issue ' . $issueKeyOrWorklogId->issueKey(),
            $this->dateResolver->argument($input)
        );
        $this->showSuccessMessages($output, $worklog);

        return self::SUCCESS;
    }

    private function interactiveTimelog(InputInterface $input, OutputInterface $output): int
    {
        $worklogs = $this->worklogHandler->find(new DateTime, new DateTime)->filterByUser($this->jira->user());
        $timeLeft = $this->dateHelper->humanToSeconds('1d') - $worklogs->totalTimeSpentSeconds();
        if ($timeLeft <= 0) {
            $output->writeln(
                sprintf(
                    '<info>You already filled in your timesheets for %s</info>',
                    (string) $this->dateResolver->argument($input) == 'now'
                        ? 'today'
                        : $this->dateResolver->argument($input)
                )
            );
            return self::FAILURE;
        }

        while ($timeLeft > 0) {
            $output->writeln(
                sprintf('<comment>%s</comment> time left to log.', $this->dateHelper->secondsToHuman($timeLeft))
            );
            $issue = $this->askIssueToChooseFrom($input, $output);
            $time = $this->askForTimeToLog($input, $output, $issue->key());
            $comment = $this->commentInput->read($input, $output, $issue);
            $worklog = $this->logNewWork(
                $issue->key(),
                $time,
                $comment ?: 'Worked on issue ' . $issue->key(),
                $this->dateResolver->argument($input)
            );
            $this->showSuccessMessages($output, $worklog);
            $timeLeft = $timeLeft - $worklog->timeSpentSeconds();
        }

        $output->writeln('<info>You have filled in your timesheets completely</info>');

        $this->renderDashboard($input, $output);

        return self::SUCCESS;
    }

    private function deleteWorklog(Worklog $worklog): void
    {
        $this->worklogHandler->delete($worklog);
    }

    private function updateWorklog(Worklog $worklog, $timeSpent, $comment, $startDay): bool
    {
        $updatedWorklog = clone $worklog;

        if ($timeSpent) {
            $updatedWorklog->timeSpentSeconds($this->dateHelper->humanToSeconds($timeSpent));
        }
        if ($comment) {
            $updatedWorklog->comment($comment);
        }
        if ($startDay) {
            $updatedWorklog->date($this->dateHelper->stringToFormattedDate($startDay, DateHelper::FORMAT_FROM_JIRA));
        }

        if (!$worklog->isSame($updatedWorklog)) {
            $this->worklogHandler->update($updatedWorklog);
            return true;
        }

        throw new UnexpectedValueException(
            sprintf('Cannot update worklog <info>%s</info> as it does not introduce a change.', $worklog->id())
        );
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function logNewWork($issueKey, $timeSpent, $comment, $startDay): Worklog
    {
        $workLog = $this->worklogHandler->create(
            Worklog::fromArray([
                'id' => null,
                'author' => $this->jira->user(),
                'comment' => $comment,
                'started' => date(DateHelper::FORMAT_FROM_JIRA, strtotime($startDay)),
                'timeSpentSeconds' => $this->dateHelper->humanToSeconds($timeSpent)
            ], $issueKey)
        );
        // load issue
        $issue = $this->jira->retrieveIssue($issueKey);
        $workLog->assignIssue($issue);
        $worklogs = $this->worklogHandler->findByIssue($issue, 20);
        $issue->assignWorklogs($worklogs);
        return $workLog;
    }

    private function loggedTimeDialogText(string $issueKey, Worklog $worklog = null): string
    {
        $confirm = '';
        if ($worklog) {
            $confirm = sprintf(
                "You logged '%s' previously. Leave the time empty to keep this value.",
                $this->dateHelper->secondsToHuman($worklog->timeSpentSeconds())
            );
        }

        return $confirm . PHP_EOL
            . sprintf(
                '<comment>Please enter the time you want to log against <info>%s</info>:</> ',
                $issueKey . ($worklog ? ' ('.$worklog->id().')' : '')
            );
    }

    private function showSuccessMessages(OutputInterface $output, Worklog $worklog): void
    {
        $output->writeln(
            sprintf(
                'You have successfully logged <comment>%s</comment> to issue <info>%s on %s</info> (%s)',
                $this->dateHelper->secondsToHuman($worklog->timeSpentSeconds()),
                $worklog->issueKey(),
                $worklog->date()->format('Y-m-d H:i:s'),
                $worklog->id()
            )
        );
        $output->writeln('');
        $output->writeln(
            sprintf(
                "Time spent: <comment>%s</comment>, Remaining estimate: <comment>%s</comment>",
                $this->dateHelper->secondsToHuman($worklog->issue()->timeSpent()),
                $this->dateHelper->secondsToHuman($worklog->issue()->remainingEstimate())
            )
        );
        $output->writeln('');
        $output->writeln('Logged work so far:');
        $this->worklogRenderer->renderWorklogs($output, $worklog->issue()->worklogs());
        $output->writeln('');
        $this->dashboardRenderer->render(
            $output,
            $this->dashDataProvider->fetch($worklog->date()->format('Y-m-d'))
        );
    }

    private function askIssueToChooseFrom(InputInterface $input, OutputInterface $output): Issue
    {
        return $this->issueSelector->chooseIssue($input, $output);
    }

    private function askForTimeToLog(
        InputInterface $input,
        OutputInterface $output,
        string $issueKey,
        Worklog $worklog = null
    ): string {
        $question = new Question(
            $this->loggedTimeDialogText($issueKey, $worklog),
            $worklog ? $this->dateHelper->secondsToHuman($worklog->timeSpentSeconds()) : '1d'
        );
        $question->setValidator(function ($answer) {
            return preg_replace('~[^0-9hmds. ]+~', '', $answer);
        });

        return $this->questionHelper->ask(
            $input,
            $output,
            $question
        );
    }

    private function renderDashboard(InputInterface $input, OutputInterface $output): void
    {
        $this->dashboardRenderer->render(
            $output,
            $this->dashDataProvider->fetch(
                date('Y-m-d', strtotime($this->dateResolver->argument($input)))
            )
        );
    }
}
