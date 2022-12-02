<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Issue\IssueType;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Renderer\IssueRenderer;

class Header implements IssueRenderer
{
    /**
     * Formats for various issue types
     *
     * @var array
     */
    private $issueTypeFormats = [
        'Default' => '<fg=black;bg=blue> %s </>',
        'Defect' => '<error> %s </error>',
        'Bug' => '<error> %s </error>',
    ];

    public function render(OutputInterface $output, Issue $issue): void
    {
        $output->writeln(
            sprintf(
                '<info>%s</info> %s %s %s <fg=black>(%s)</>',
                $issue->key(),
                $this->formatStatus($issue->status()),
                $this->formatIssueType($issue->issueType()),
                $issue->summary(),
                $issue->url()
            )
        );
    }

    private function formatIssueType(IssueType $issueType)
    {
        $format = isset($this->issueTypeFormats[(string) $issueType])
            ? (string) $issueType : 'Default';

        return sprintf($this->issueTypeFormats[$format], $issueType);
    }

    private function formatStatus(Status $status)
    {
        $style = new PaletteOutputFormatterStyle;
        $style->setForeground('black');
        $style->setBackground($status->statusCategoryColor());
        return $style->apply(sprintf(' %s ', $status));
    }
}
