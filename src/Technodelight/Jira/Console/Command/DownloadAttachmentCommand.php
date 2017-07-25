<?php

namespace Technodelight\Jira\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Technodelight\Jira\Api\Attachment;
use Technodelight\Jira\Api\Issue;

class DownloadAttachmentCommand extends AbstractCommand
{
    const SUCCESS_MESSAGE = '<info>File %s has been successfully downloaded.</info>';

    const ERROR_MESSAGE = '<error> Something went wrong while downloading %s. </error>';

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
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('filename')) {
            $issueKey = $this->issueKeyArgument($input);
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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input);
        /** @var \Technodelight\Jira\Api\Api $jira */
        $jira = $this->getService('technodelight.jira.api');
        /** @var \Technodelight\Jira\Api\Issue $issue */
        $issue = $jira->retrieveIssue($issueKey);
        $filename = $input->getArgument('filename');

        try {
            // download file
            $attachmentUrl = $this->findAttachment($issue, $filename);
            $jira->download($attachmentUrl, $filename);
            $output->writeln(sprintf(self::SUCCESS_MESSAGE, $filename));
        } catch (\Exception $e) {
            $output->writeln(sprintf(self::ERROR_MESSAGE, $filename));
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
            sprintf('Attachment %s cannot be found', $filename)
        );
    }
}
