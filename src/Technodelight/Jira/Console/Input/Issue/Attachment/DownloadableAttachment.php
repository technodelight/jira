<?php

namespace Technodelight\Jira\Console\Input\Issue\Attachment;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Attachment;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Issue\IssueKey;

class DownloadableAttachment
{
    /**
     * @var Api
     */
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool|IssueKey $issueKey
     * @throws \ErrorException
     */
    public function resolve(InputInterface $input, OutputInterface $output, IssueKey $issueKey)
    {
        /** @var \Technodelight\Jira\Domain\Issue $issue */
        $issue = $this->api->retrieveIssue($issueKey);

        if (!count($issue->attachments())) {
            throw new \ErrorException(
                sprintf('No attachments for %s', $issue->issueKey())
            );
        }

        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = new QuestionHelper;

        $question = new ChoiceQuestion(
            '<comment>Select file to download</comment>',
            array_map(function (Attachment $attachment) {
                return $attachment->filename();
            }, $issue->attachments()),
            0
        );
        $question->setErrorMessage('Filename %s is invalid.');

        return $helper->ask($input, $output, $question);
    }

    /**
     * @param Issue $issue
     * @param string $filename
     * @return Attachment
     */
    public function findAttachmentByFilename(Issue $issue, $filename)
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
}
