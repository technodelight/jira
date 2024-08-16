<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Input\Issue\Attachment;

use ErrorException;
use InvalidArgumentException;
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
    public function __construct(private readonly Api $api) {}

    public function resolve(InputInterface $input, OutputInterface $output, IssueKey $issueKey)
    {
        $issue = $this->api->retrieveIssue($issueKey);

        if (!count($issue->attachments())) {
            throw new ErrorException(
                sprintf('No attachments for %s', $issue->issueKey())
            );
        }

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

    public function findAttachmentByFilename(Issue $issue, string $filename): Attachment
    {
        foreach ($issue->attachments() as $attachment) {
            if ($attachment->filename() === $filename) {
                return $attachment;
            }
        }

        throw new InvalidArgumentException(
            sprintf('Attachment "%s" cannot be found', $filename)
        );
    }
}
