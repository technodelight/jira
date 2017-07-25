<?php

namespace Technodelight\Jira\Template;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\Attachment;

class AttachmentRenderer
{
    /**
     * @param \Technodelight\Jira\Api\Attachment[] $attachments
     * @return string
     */
    public function renderAttachment(array $attachments)
    {
        $out = [];
        foreach ($attachments as $attachment) {
            $out[] = $this->renderRow($attachment);
        }
        return implode(PHP_EOL, $out);
    }

    private function renderRow(Attachment $attachment)
    {
        return sprintf(
            '<info>%s</info> (<fg=cyan>%s</>) <fg=black>jira download %s %s</>',
            $attachment->filename(),
            $attachment->author(),
            $attachment->issue()->issueKey(),
            $attachment->filename()
        );
    }
}
