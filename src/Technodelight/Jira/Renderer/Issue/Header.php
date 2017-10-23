<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\ColorExtractor;
use Technodelight\Jira\Renderer\IssueRenderer;

class Header implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\ColorExtractor
     */
    private $colorExtractor;

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

    public function __construct(ColorExtractor $colorExtractor)
    {
        $this->colorExtractor = $colorExtractor;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        $output->writeln(
            sprintf(
                '<info>%s</info> %s %s %s <fg=black>(%s)</>',
                $issue->key(),
                $this->formatStatus($issue->status(), $issue->statusCategory()),
                $this->formatIssueType($issue->issueType()),
                $issue->summary(),
                $issue->url()
            )
        );
    }

    private function formatIssueType($issueType)
    {
        $format = isset($this->issueTypeFormats[$issueType])
            ? $issueType : 'Default';

        return sprintf($this->issueTypeFormats[$format], $issueType);
    }

    private function formatStatus($status, $statusCategory)
    {
        $bgColor = $this->colorExtractor->extractColor($statusCategory['colorName']);
        $fgColor = 'black';

        return sprintf('<fg=%s;bg=%s> %s </>', $fgColor, $bgColor, $status);
    }
}
