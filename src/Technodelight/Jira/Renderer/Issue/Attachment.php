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
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($attachments = $issue->attachments()) {
            $output->writeln($this->templateHelper->tabulate($this->renderAttachments($attachments)));
        }
    }

    /**
     * @param IssueAttachment[] $attachments
     */
    private function renderAttachments(array $attachments)
    {
        $rows = ['<comment>attachments:</comment>'];
        foreach ($attachments as $attachment) {
            $rows[] = $this->templateHelper->tabulate($this->renderAttachment($attachment));
        }
        return $rows;
    }

    private function renderAttachment(IssueAttachment $attachment)
    {
        $timeAgo = TimeAgo::withTranslation($attachment->created(), 'en');

        return sprintf(
            '<info>%s</info> %s (by <fg=cyan>%s</> %s) <fg=black>jira download %s %s</>',
            $attachment->filename(),
            BytesInHuman::fromBytes($attachment->size()),
            $attachment->author(),
            $timeAgo->inWords(),
            $attachment->issue()->issueKey(),
            $attachment->filename()
        );
    }
}
