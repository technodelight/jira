<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\Jira\Domain\Attachment;
use Technodelight\Jira\Domain\Issue;

class DownloadAttachmentCommand extends AbstractCommand
{
    const SUCCESS_MESSAGE = 'File "%s" has been successfully downloaded to "%s" .';
    const CANCEL_MESSAGE = 'Skipped downloading file "%s"';
    const ERROR_MESSAGE = 'Something went wrong while downloading "%s."';

    protected function configure()
    {
        $this
            ->setName('download')
            ->setDescription('Download attachment from a ticket')
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

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('filename')) {
            $issueKey = $this->issueKeyArgument($input, $output);
            $jira = $this->getService('technodelight.jira.api');
            /** @var \Technodelight\Jira\Api\Issue $issue */
            $issue = $jira->retrieveIssue($issueKey);

            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');

            $question = new ChoiceQuestion(
                'Select file to download',
                array_map(function (Attachment $attachment) {
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
            // download file
            $attachmentUrl = $this->findAttachment($issue, $filename);
            $targetFilePath = $input->getArgument('targetPath') . DIRECTORY_SEPARATOR . $filename;
            if ($this->confirmDownload($input, $output, $targetFilePath)) {
                $this->jiraApi()->download($attachmentUrl, $targetFilePath);
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

    private function findAttachment(Issue $issue, $filename)
    {
        foreach ($issue->attachments() as $attachment) {
            if ($attachment->filename() == $filename) {
                return $attachment->url();
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
                sprintf('File "%s" already exists, do you want to overwrite it?', $targetFilePath),
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
