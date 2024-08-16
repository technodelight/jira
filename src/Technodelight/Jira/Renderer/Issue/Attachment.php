<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\BytesInHuman\BytesInHuman;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Attachment as IssueAttachment;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\TimeAgo;

class Attachment implements IssueRenderer
{
    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly bool $shortMode = false
    ) {}

    public function render(OutputInterface $output, Issue $issue): void
    {
        $attachments = $issue->attachments();
        if (!empty($attachments)) {
            $content = $this->shortMode ? $this->renderSummary($attachments) : $this->renderAttachments($attachments);
            $output->writeln($this->templateHelper->tabulate($content));
        }
    }

    private function renderAttachments(array $attachments): array
    {
        $rows = ['<comment>attachments:</comment>'];
        foreach ($attachments as $attachment) {
            $rows[] = $this->templateHelper->tabulate($this->renderAttachment($attachment));
        }
        return $rows;
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function renderAttachment(IssueAttachment $attachment): string
    {
        return strtr(
            '<info>{filename}</info> {size} (by <fg=cyan>{author}</> {when}) <fg=black>jira download {issueKey} {filenameSlashed}</>',
            [
                '{filename}' => $attachment->filename(),
                '{size}' => BytesInHuman::fromBytes($attachment->size()),
                '{author}' => $attachment->author(),
                '{when}' => TimeAgo::withTranslation($attachment->created(), 'en')->inWords(),
                '{issueKey}' => $attachment->issue()->issueKey(),
                '{filenameSlashed}' => addcslashes($attachment->filename(),' ()')
            ]
        );
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function renderSummary(array $attachments): string
    {
        $totalSize = 0;
        foreach ($attachments as $attachment) {
            $totalSize+= $attachment->size();
        }

        return strtr(
            '<comment>attachments:</comment> {count} {phrase} ({size})',
            [
                '{count}' => count($attachments),
                '{phrase}' => count($attachments) == 1 ? 'file' : 'files',
                '{size}' => BytesInHuman::fromBytes($totalSize)
            ]
        );
    }
}
