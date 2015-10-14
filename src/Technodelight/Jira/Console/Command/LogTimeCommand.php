<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Technodelight\Jira\Template\Template;
use Technodelight\Jira\Template\WorklogRenderer;
use Technodelight\Jira\Helper\DateHelper;

use UnexpectedValueException;

class LogTimeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('log')
            ->setDescription('Log work against issue')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key, like PROJ-123'
            )
            ->addArgument(
                'time',
                InputArgument::OPTIONAL,
                'Time you spent with the issue, like \'1h\''
            )
            ->addArgument(
                'remaining',
                InputArgument::OPTIONAL,
                "Set remaining estimate to the value you specify, like '1h'. Issue will have this new remaining estimate when you use this argument."
            )
            ->addOption(
                'leave-remaining',
                'l',
                InputOption::VALUE_NONE,
                'Skip updating remaining time. If you specified the remaining time this option is ineffective.'
            )
            ->addOption(
                'comment',
                'c',
                InputOption::VALUE_REQUIRED,
                'Add comment to worklog'
            )
            ->addOption(
                'interactive',
                'i',
                InputOption::VALUE_NONE,
                'Interactively set the time spent, remaining time and comment'
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getHelper('dialog');
        $templateHelper = $this->getApplication()->templateHelper();
        $project = $this->getApplication()->config()->project();
        $jira = $this->getApplication()->jira();

        if (!$issueKey = $input->getArgument('issueKey')) {
            $input->setOption('interactive', true);
            $issues = $this->retrieveInProgressIssues();
            $index = $dialog->select(
                $output,
                PHP_EOL . 'Choose an issue to log time to',
                $issues,
                0
            );
            $issueKey = $issues[$index];
            $input->setArgument('issueKey', $issueKey);
        }

        if ($input->getOption('interactive')) {
            if (!$input->getArgument('time')) {
                $timeSpent = $dialog->askAndValidate(
                    $output,
                    PHP_EOL . 'Please enter the time you want to log: ',
                    function ($answer) {
                        if (!preg_match('~[0-9hmd ]~', $answer)) {
                            throw new \RuntimeException(
                                "It's not possible to log '$answer' as time, as it's not matching the allowed format."
                            );
                        }

                        return $answer;
                    },
                    false,
                    '1d'
                );

                $input->setArgument('time', $timeSpent);
            }

            if (!$input->getArgument('remaining')) {
                $remaining = $dialog->askAndValidate(
                    $output,
                    PHP_EOL . "Do you want to update remaining time?" . PHP_EOL
                    . "If yes, just enter the required time left, or leave it blank to skip." . PHP_EOL,
                    function ($answer) {
                        if (!empty($answer) && !preg_match('~[0-9hmd ]~', $answer)) {
                            throw new \RuntimeException(
                                "It's not possible to log '$answer' as time, as it's not matching the allowed format."
                            );
                        }

                        return $answer;
                    },
                    false,
                    false
                );
                $input->setArgument('remaining', $remaining);
            }

            if (!$input->getOption('comment')) {
                $commitMessages = $this->retrieveGitCommitMessages();
                if (!empty($commitMessages)) {
                    $commitMessagesSummary = PHP_EOL . 'What you have done so far: (based on your git commit messages):' . PHP_EOL
                        . $templateHelper->tabulate(wordwrap($this->retrieveGitCommitMessages())) . PHP_EOL;
                } else {
                    $commitMessagesSummary = PHP_EOL;
                }
                $comment = $dialog->ask(
                    $output,
                    PHP_EOL . "Do you want to add a comment on your work log?" . PHP_EOL
                    . "If you leave it empty, the comment will be 'Worked on issue $issueKey'" . PHP_EOL
                    . $commitMessagesSummary,
                    false
                );

                $input->setOption('comment', $comment);
            }
        }

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $input->getArgument('issueKey');
        $timeSpent = $input->getArgument('time');
        $remaining = $input->getArgument('remaining');
        $app = $this->getApplication();

        if (!$issueKey || !$timeSpent) {
            return $output->writeln('<error>You need to specify the issue and time arguments</error>');
        }

        $this->log($input);

        $issue = $app->jira()->retrieveIssue($issueKey);
        $template = Template::fromFile('Technodelight/Jira/Resources/views/Commands/logtime.template');
        $worklogs = $app->jira()->retrieveIssueWorklogs($issueKey);

        $currentWorklogDetails = [
            'issueKey' => $issueKey,
            'logged' => $timeSpent,
            'estimate' => $app->dateHelper()->secondsToHuman($issue->estimate()),
            'spent' => $app->dateHelper()->secondsToHuman($issue->timeSpent()),
            'worklogs' => $this->renderWorklogs($worklogs),
        ];

        $output->writeln(
            $this->deDoubleNewlineize($template->render($currentWorklogDetails))
        );
    }

    private function log(InputInterface $input)
    {
        $issueKey = $input->getArgument('issueKey');
        $timeSpent = $input->getArgument('time');
        $remaining = $input->getArgument('remaining');
        $comment = $input->getOption('comment') ?: sprintf('Worked on issue %s', $issueKey);

        if ($remaining) {
            $res = $this->getApplication()->jira()->worklog(
                $issueKey,
                $timeSpent,
                $comment,
                'new',
                $remaining
            );
        } else {
            $res = $this->getApplication()->jira()->worklog(
                $issueKey,
                $timeSpent,
                $comment,
                $input->getOption('leave-remaining') ? 'leave' : 'auto'
            );
        }
    }

    private function renderWorklogs($worklogs)
    {
        $renderer = new WorklogRenderer;
        return $renderer->renderWorklogs(array_slice($worklogs, -10));
    }

    private function retrieveInProgressIssues()
    {
        $project = $this->getApplication()->config()->project();
        $issues = $this->getApplication()->jira()->inprogressIssues($project, true);
        $issueKeys = [];
        foreach ($issues as $issue) {
            $issueKeys[] = $issue->issueKey();
        }

        return $issueKeys;
    }

    private function retrieveGitCommitMessages()
    {
        $git = $this->getApplication()->git();
        return implode(PHP_EOL, $git->commitMessages());
    }

    private function deDoubleNewlineize($string)
    {
        return str_replace(PHP_EOL . PHP_EOL, PHP_EOL, $string);
    }
}
