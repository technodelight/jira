<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\GitShell\Api;
use Technodelight\GitShell\LogEntry;
use Technodelight\Jira\Api\EditApp\EditApp;
use Technodelight\Jira\Api\JiraRestApi\Api as Jira;
use Technodelight\Jira\Helper\HubHelper;
use Technodelight\Jira\Helper\TemplateHelper;

class PullRequest extends Command
{
    /**
     * @var HubHelper
     */
    private $hub;
    /**
     * @var Api
     */
    private $git;
    /**
     * @var Jira
     */
    private $jira;
    /**
     * @var EditApp
     */
    private $editor;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    public function __construct(HubHelper $hub, Api $git, Jira $jira, EditApp $editor, TemplateHelper $templateHelper)
    {
        parent::__construct();

        $this->hub = $hub;
        $this->git = $git;
        $this->jira = $jira;
        $this->editor = $editor;
        $this->templateHelper = $templateHelper;
    }

    protected function configure()
    {
        $this
            ->setName('issue:pr')
            ->setDescription('Create pull request from current branch')
            ->addOption(
                'base',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify base branch to create the PR against',
                'develop'
            )
            ->addOption(
                'head',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify head branch, defaults to current branch',
                false
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Github\Exception\MissingArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $base = $input->getOption('base');
        $head = $input->getOption('head') === false ? $this->git->currentBranch()->name() : $input->getOption('head');
        $this->checkIfBranchIsBehind($input, $output, $head);
        if (false === $this->checkIfHasPr($input, $output, $head)) {
            $output->writeln(sprintf('<error>You already have an open PR for this branch:</error>', $head));
        }

        list($title, $body) = $this->readTitleAndContent($base, $head);
        list($body, $labels) = $this->parseContent($body);

        $this->hub->createPr($title, $body, $base, $head);
    }

    /**
     * @param string $base
     * @param string $head
     * @return array [title,content]
     */
    protected function readTitleAndContent($base, $head)
    {
        $result = $this->editor->edit(
            $this->titleFromBranchName($head),
            $this->contentFromBranch($base, $head) . PHP_EOL . $this->controlThings()
        );

        return explode(PHP_EOL.PHP_EOL, $result, 2);
    }

    private function checkIfBranchIsBehind(InputInterface $input, OutputInterface $output, $head)
    {
        if ($head === $this->git->currentBranch()) {
            $logs = $this->git->log('origin/' . $head, $head);
            if (!empty($logs) || $diff = $this->git->diff()) {
                $helper = $this->questionHelper();

                $output->writeln('It seems you have the following uncommited changes on your current branch:');
                foreach ($diff as $entry) {
                    $output->writeln(
                        $this->tab(
                            sprintf('<comment>%s</comment> %s', $entry->state(), $entry->file())
                        )
                    );
                }
                $question = new ConfirmationQuestion(
                    'Are you sure you want to create a PR? [Y/n] ',
                true
                );

                if (!$helper->ask($input, $output, $question)) {
                    throw new \RuntimeException('Please commit your changes first.');
                }
            }
        }
    }

    private function checkIfHasPr(InputInterface $input, OutputInterface $output, $head)
    {
        $prs = $this->hub->prForHead($head, 'open');
        if (!empty($prs)) {
            $output->writeln('You already have an open PR for this branch!');
            return false;
        }

        return true;
    }
    /**
     * @param string $head
     * @return string
     */
    private function titleFromBranchName($head)
    {
        if (preg_match('~([A-Z]+-\d+)-(.*)~', $head, $matches)) {
            $issueKey = $matches[1];
            $change = strtr($matches[2], ['-' => ' ']);

            return sprintf('%s %s', $issueKey, $change);
        }
    }

    /**
     * @param string $base
     * @param string $head
     * @return string
     */
    private function contentFromBranch($base, $head)
    {
        $content = [];
        foreach ($this->git->log($base, $head) as $log) {
            /** @var LogEntry $log */
            if ($log->message()->hasBody()) {
                $content[] = $log->message()->getBody();
            } else {
                $content[] = $log->message()->getHeader();
            }
        }
        return implode(PHP_EOL, $content);
    }

    private function controlThings()
    {
        $labels = $this->hub->labels();
        $content = [
            '# attach labels:'
        ];
        foreach ($labels as $label) {
            $content[] = '# [ ] ' . $label['name'];
        }
        $content[] = '#';
        //@TODO: add assignees somehow
        return implode(PHP_EOL, $content);
    }


    /**
     * @return \Symfony\Component\Console\Helper\QuestionHelper
     */
    private function questionHelper()
    {
        return $this->getHelper('question');
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }

    private function parseContent($body)
    {
        $rows = explode(PHP_EOL, $body);
        $content = [];
        $labels = [];
        foreach ($rows as $row) {
            if (strpos('# [', trim($row)) === 0) {
                if (preg_match('~# \[(.)\] (.*)~', trim($row), $matches)) {
                    if (!empty($matches[1])) {
                        // ticked
                        $labels[] = $matches[2];
                    }
                }
            } elseif (strpos('#', trim($row)) === 0) {
                // skip as this is a comment
                continue;
            } else {
                $content[] = $row;
            }
        }
        return [implode(PHP_EOL, $content), $labels];
    }
}
