<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Domain\Attachment as IssueAttachment;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\Downloader;

class Attachment extends AbstractCommand
{
    const SUCCESS_MESSAGE = 'File "%s" has been successfully downloaded to "%s" .';
    const CANCEL_MESSAGE = 'Skipped downloading file "%s"';
    const ERROR_MESSAGE = 'Something went wrong while downloading "%s."';

    protected function configure()
    {
        $this
            ->setName('issue:attachment')
            ->setDescription('Download attachment from a ticket')
            ->setAliases(['download'])
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123), defaults to current issue, taken from branch name'
            )
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Filename to download'
            )
            ->addArgument(
                'targetPath',
                InputArgument::OPTIONAL,
                'Path to download the file to (defaults to current working directory)'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \ErrorException
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);

        if (empty($input->getArgument('filename'))) {
            /** @var \Technodelight\Jira\Domain\Issue $issue */
            $issue = $this->jiraApi()->retrieveIssue($issueKey);

            if (!count($issue->attachments())) {
                throw new \ErrorException(
                    sprintf('No attachments for %s', $issue->issueKey())
                );
            }

            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');

            $question = new ChoiceQuestion(
                '<comment>Select file to download</comment>',
                array_map(function (IssueAttachment $attachment) {
                    return $attachment->filename();
                }, $issue->attachments()),
                0
            );
            $question->setErrorMessage('Filename %s is invalid.');

            $filename = $helper->ask($input, $output, $question);
            $input->setArgument('filename', $filename);
        }
        if (!$input->getArgument('targetPath')) {
            $input->setArgument('targetPath', getcwd());
        }
        $input->setArgument(
            'targetPath',
            rtrim($input->getArgument('targetPath'), '/\\' . DIRECTORY_SEPARATOR)
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        $filename = $input->getArgument('filename');

        /** @var \Technodelight\Jira\Domain\Issue $issue */
        $issue = $this->jiraApi()->retrieveIssue($issueKey);
        /** @var \Symfony\Component\Console\Helper\FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        try {
            if (!count($issue->attachments())) {
                throw new \ErrorException(
                    sprintf('No attachments for %s', $issue->issueKey())
                );
            }

            // download file
            $attachment = $this->findAttachment($issue, $filename);
            $targetFilePath = $input->getArgument('targetPath') . DIRECTORY_SEPARATOR . $filename;
            if ($this->confirmDownload($input, $output, $targetFilePath)) {

                $downloader = new Downloader;
                $f = fopen($targetFilePath, 'w');
                /** @var ProgressBar $progress */
                list($progress, $callback) = $downloader->progressBarWithProgressFunction($output);
                $progress->start($attachment->size());
                $this->jiraApi()->download($attachment->url(), $f, $callback);
                $progress->finish();

                $output->writeln('');

                $output->writeln(
                    $formatter->formatBlock(
                        sprintf(self::SUCCESS_MESSAGE, $filename, $targetFilePath),
                        'info'
                    )
                );
            } else {
                $output->writeln(
                    $formatter->formatBlock(sprintf(self::CANCEL_MESSAGE, $filename), 'comment')
                );
            }
        } catch (\Exception $e) {
            $errors = [
                sprintf(self::ERROR_MESSAGE, $filename),
                $e->getMessage(),
            ];
            $output->writeln(
                $formatter->formatBlock($errors, 'error', true)
            );
        }
    }

    /**
     * @param Issue $issue
     * @param $filename
     * @return IssueAttachment
     */
    private function findAttachment(Issue $issue, $filename)
    {
        foreach ($issue->attachments() as $attachment) {
            if ($attachment->filename() == $filename) {
                return $attachment;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Attachment "%s" cannot be found', $filename)
        );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param $targetFilePath
     * @return bool
     */
    protected function confirmDownload(InputInterface $input, OutputInterface $output, $targetFilePath)
    {

        if (is_file($targetFilePath)) {
            $question = new ConfirmationQuestion(
                sprintf('<comment>File "%s" already exists, do you want to overwrite it?</comment> [y/N] ', $targetFilePath),
                false
            );
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');
            return $helper->ask($input, $output, $question);
        }

        return true;
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }
}
