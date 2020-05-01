<?php

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
use Technodelight\Jira\Console\Dashboard\Dashboard;
use Technodelight\Jira\Console\Input\Worklog\Comment as CommentInput;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Worklog;
use Technodelight\Jira\Renderer\DashboardRenderer;
use Technodelight\Jira\Renderer\Issue\Header as HeaderRenderer;
use Technodelight\Jira\Renderer\Issue\Worklog as WorklogRenderer;

class LogTime extends Command
{
    /**
     * @var Api
     */
    private $jira;
    /**
     * @var IssueKeyOrWorklogIdResolver
     */
    private $issueKeyOrWorklogIdResolver;
    /**
     * @var InteractiveIssueSelector
     */
    private $issueSelector;
    /**
     * @var CommentInput
     */
    private $commentInput;
    /**
     * @var DateResolver
     */
    private $dateResolver;
    /**
     * @var QuestionHelper
     */
    private $questionHelper;
    /**
     * @var WorklogHandler
     */
    private $worklogHandler;
    /**
     * @var DateHelper
     */
    private $dateHelper;
    /**
     * @var WorklogRenderer
     */
    private $worklogRenderer;
    /**
     * @var HeaderRenderer
     */
    private $headerRenderer;
    /**
     * @var DashboardRenderer
     */
    private $dashboardRenderer;
    /**
     * @var Dashboard
     */
    private $dashboardDataProvider;

    public function __construct(
        Api $jira,
        IssueKeyOrWorklogIdResolver $issueKeyOrWorklogIdResolver,
        InteractiveIssueSelector $issueSelector,
        CommentInput $commentInput,
        DateResolver $dateResolver,
        QuestionHelper $questionHelper,
        WorklogHandler $worklogHandler,
        DateHelper $dateHelper,
        WorklogRenderer $worklogRenderer,
        HeaderRenderer $headerRenderer,
        DashboardRenderer $dashboardRenderer,
        Dashboard $dashboardDataProvider
    )
    {
        parent::__construct();
        $this->jira = $jira;
        $this->issueKeyOrWorklogIdResolver = $issueKeyOrWorklogIdResolver;
        $this->issueSelector = $issueSelector;
        $this->commentInput = $commentInput;
        $this->dateResolver = $dateResolver;
        $this->questionHelper = $questionHelper;
        $this->worklogHandler = $worklogHandler;
        $this->dateHelper = $dateHelper;
        $this->worklogRenderer = $worklogRenderer;
        $this->headerRenderer = $headerRenderer;
        $this->dashboardRenderer = $dashboardRenderer;
        $this->dashboardDataProvider = $dashboardDataProvider;
    }

    protected function configure()
    {
        $this
            ->setName('issue:log')
            ->setDescription('Log work against issue')
            ->setAliases(['log', 'worklog'])
            ->addArgument(
                IssueKeyOrWorklogIdResolver::NAME,
                InputArgument::OPTIONAL,
                'Issue key, like PROJ-123 OR a specific worklog\'s ID'
            )
            ->addArgument(
                'time',
                InputArgument::OPTIONAL,
                'Time you spent with the issue, like \'1h\''
            )
            ->addArgument(
                'comment',
                InputArgument::OPTIONAL,
                'Add comment to worklog'
            )
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Date to put your log to, like \'yesterday 12:00\' or \'' . date('Y-m-d') . '\', anything http://php.net/strtotime can parse'
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
                'Move worklog to another date',
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
                'Keep default comment (Worked on ISSUE-123 or the previous comment when updating existing worklog'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $argParser = LogTimeArgsOptsParser::fromArgsOpts($input->getArguments(), $input->getOptions());

        $input->setArgument(IssueKeyOrWorklogIdResolver::NAME, $argParser->issueKeyOrWorklogId());
        $input->setArgument('time', $argParser->time());
        $input->setArgument('comment', $argParser->comment());
        $input->setArgument('date', $argParser->date());

        if ($input->getOption('interactive')) {
            return;
        }

        /** @var \Technodelight\Jira\Console\Argument\IssueKeyOrWorklogId $issueKeyOrWorklogId */
        $issueKeyOrWorklogId = $this->issueKeyOrWorklogIdResolver->argument($input);

        // fix when no arguments but you want to log your time to current issue specified by branch
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
            $input->setArgument('time', $this->askForTimeToLog($input, $output, $issueKeyOrWorklogId->issueKey(), $issueKeyOrWorklogId->worklog()));
        }

        if (!$input->getArgument('comment')) {
            if ($issueKeyOrWorklogId->isWorklogId()) {
                $worklog = $this->worklogHandler->retrieve($issueKeyOrWorklogId->worklogId());
                $issue = $this->jira->retrieveIssue($worklog->issueKey());
            } else {
                $issue = $this->jira->retrieveIssue($issueKeyOrWorklogId->issueKey());
                $worklog = null;
            }

            $input->setArgument('comment', $this->commentInput->read($output, $issue, $worklog, $input->getOption('keep-default-comment')));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('interactive')) {
            return $this->interactiveTimelog($input, $output);
        }

        return $this->doWorklog($input, $output);
    }

    private function doWorklog(InputInterface $input, OutputInterface $output)
    {
        $issueKeyOrWorklogId = $this->issueKeyOrWorklogIdResolver->argument($input);
        $timeSpent = $input->getArgument('time') ?: null;
        $comment = $input->getArgument('comment') ?: null;
        $worklogDate = $input->getOption('move') ? $this->dateResolver->option($input, 'move') : null;

        if ($issueKeyOrWorklogId->isWorklogId()) {
            return $this->processExistingWorklog($input, $output, $issueKeyOrWorklogId, $timeSpent, $comment, $worklogDate);
        }

        return $this->processNewWorklog($input, $output, $issueKeyOrWorklogId, $timeSpent, $comment);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param IssueKeyOrWorklogId $issueKeyOrWorklogId
     * @param string|null $timeSpent
     * @param string|null $comment
     * @param string|null $worklogDate
     * @return int
     */
    private function processExistingWorklog(
        InputInterface $input,
        OutputInterface $output,
        IssueKeyOrWorklogId $issueKeyOrWorklogId,
        $timeSpent = null,
        $comment = null,
        $worklogDate = null
    )
    {
        try {
            if ($input->getOption('delete')) {
                $this->deleteWorklog($issueKeyOrWorklogId->worklog());
                $output->writeln(
                    sprintf('<comment>Worklog <info>%d</info> has been deleted successfully</comment>', $issueKeyOrWorklogId->worklog()
                        ->id())
                );
            } else {
                $this->updateWorklog($issueKeyOrWorklogId->worklog(), $timeSpent, $comment, $worklogDate);
                $output->writeln(
                    sprintf(
                        '<comment>Worklog <info>%d</info> has been updated</comment>',
                        (string) $issueKeyOrWorklogId->worklog()->id()
                    )
                );
            }

            return 0;
        } catch (\UnexpectedValueException $exception) {
            $output->writeln($exception->getMessage());

            return 1;
        } catch (\Exception $exception) {
            $output->writeln(
                sprintf('<error>Something bad happened while processing %s</error>', $issueKeyOrWorklogId->worklogId())
            );
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return 1;
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param IssueKeyOrWorklogId $issueKeyOrWorklogId
     * @param string|null $timeSpent
     * @param string|null $comment
     * @return int
     */
    private function processNewWorklog(
        InputInterface $input,
        OutputInterface $output,
        IssueKeyOrWorklogId $issueKeyOrWorklogId,
        $timeSpent = null,
        $comment = null
    )
    {
        if (!$timeSpent) {
            $output->writeln('<error>You need to specify the issue and time arguments at least</error>');

            return 1;
        }

        $worklog = $this->logNewWork(
            $issueKeyOrWorklogId->issueKey(),
            $timeSpent,
            $comment ?: 'Worked on issue ' . $issueKeyOrWorklogId->issueKey(),
            $this->dateResolver->argument($input)
        );
        $this->showSuccessMessages($output, $worklog);

        return 0;
    }

    private function interactiveTimelog(InputInterface $input, OutputInterface $output)
    {
        $worklogs = $this->worklogHandler->find(new DateTime, new DateTime)->filterByUser($this->jira->user());
        $timeLeft = $this->dateHelper->humanToSeconds('1d') - $worklogs->totalTimeSpentSeconds();
        if ($timeLeft <= 0) {
            $output->writeln(
                sprintf(
                    '<info>You already filled in your timesheets for %s</info>',
                    (string) $this->dateResolver->argument($input) == 'now' ? 'today' : $this->dateResolver->argument($input)
                )
            );
            return 1;
        }

        while ($timeLeft > 0) {
            $output->writeln(sprintf('<comment>%s</comment> time left to log.', $this->dateHelper->secondsToHuman($timeLeft)));
            $issue = $this->askIssueToChooseFrom($input, $output);
            $time = $this->askForTimeToLog($input, $output, $issue->key());
            $comment = $this->commentInput->read($output, $issue);
            $worklog = $this->logNewWork($issue->key(), $time, $comment ?: 'Worked on issue ' . $issue->key(), $this->dateResolver->argument($input));
            $this->showSuccessMessages($output, $worklog);
            $timeLeft = $timeLeft - $worklog->timeSpentSeconds();
        }

        $output->writeln('<info>You have filled in your timesheets completely</info>');

        $this->renderDashboard($input, $output);

        return 0;
    }

    private function deleteWorklog(Worklog $worklog)
    {
        $this->worklogHandler->delete($worklog);
        return true;
    }

    private function updateWorklog(Worklog $worklog, $timeSpent, $comment, $startDay)
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

        throw new \UnexpectedValueException(sprintf('Cannot update worklog <info>%d</info> as it looks the same as it was.', $worklog->id()));
    }

    private function logNewWork($issueKey, $timeSpent, $comment, $startDay)
    {
        $user = $this->jira->user();

        $worklog = $this->worklogHandler->create(
            Worklog::fromArray([
                'id' => null,
                'author' => $user,
                'comment' => $comment,
                'started' => date(DateHelper::FORMAT_FROM_JIRA, strtotime($startDay)),
                'timeSpentSeconds' => $this->dateHelper->humanToSeconds($timeSpent)
            ], $issueKey)
        );
        // load issue
        $issue = $this->jira->retrieveIssue($issueKey);
        $worklog->assignIssue($issue);
        $worklogs = $this->worklogHandler->findByIssue($issue, 20);
        $issue->assignWorklogs($worklogs);
        return $worklog;
    }

    /**
     * @param string|int $issueKey
     * @param Worklog $worklog
     * @return string
     */
    protected function loggedTimeDialogText($issueKey, $worklog = null)
    {
        if ($worklog) {
            $confirm = sprintf(
                "You logged '%s' previously. Leave the time empty to keep this value.",
                $this->dateHelper->secondsToHuman($worklog->timeSpentSeconds())
            );
        } else {
            $confirm = '';
        }

        return $confirm . PHP_EOL
            . sprintf('<comment>Please enter the time you want to log against <info>%s</info>:</> ', $issueKey . ($worklog ? ' ('.$worklog->id().')' : ''));
    }

    private function showSuccessMessages(OutputInterface $output, Worklog $worklog)
    {
        $output->writeln(
            "You have successfully logged <comment>{$this->dateHelper->secondsToHuman($worklog->timeSpentSeconds())}</comment>"
            ." to issue <info>{$worklog->issueKey()} on {$worklog->date()->format('Y-m-d H:i:s')}</info> ({$worklog->id()})"
        );
        $output->writeln('');
        $output->writeln(
            "Time spent: <comment>{$this->dateHelper->secondsToHuman($worklog->issue()->timeSpent())}</comment>, "
            . "Remaining estimate: <comment>{$this->dateHelper->secondsToHuman($worklog->issue()->remainingEstimate())}</comment>"
        );
        $output->writeln('');
        $output->writeln('Logged work so far:');
        $this->worklogRenderer->renderWorklogs($output, $worklog->issue()->worklogs());
        $output->writeln('');
        $this->dashboardRenderer->render($output, $this->dashboardDataProvider->fetch($worklog->date()->format('Y-m-d')));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Issue
     */
    private function askIssueToChooseFrom(InputInterface $input, OutputInterface $output)
    {
        return $this->issueSelector->chooseIssue($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $issueKey
     * @param Worklog $worklog
     * @return string
     */
    protected function askForTimeToLog(InputInterface $input, OutputInterface $output, $issueKey, Worklog $worklog = null)
    {
        $question = new Question(
            $this->loggedTimeDialogText($issueKey, $worklog), $worklog ? $this->dateHelper->secondsToHuman($worklog->timeSpentSeconds()) : '1d');
        $question->setValidator(function ($answer) {
            return preg_replace('~[^0-9hmds. ]+~', '', $answer);
        });
        return $this->questionHelper->ask(
            $input,
            $output,
            $question
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function renderDashboard(InputInterface $input, OutputInterface $output)
    {
        $this->dashboardRenderer->render(
            $output,
            $this->dashboardDataProvider->fetch(
                date('Y-m-d', strtotime($this->dateResolver->argument($input)))
            )
        );
    }
}
