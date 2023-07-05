<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\Image;
use Technodelight\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;

class Description implements IssueRenderer
{
    private const MAX_ROWS = 2;
    private TemplateHelper $templateHelper;
    private Image $imageRenderer;
    private Wordwrap $wordwrap;
    private JiraTagConverter $tagConverter;
    private bool $renderFullDescription;

    public function __construct(
        TemplateHelper $templateHelper,
        Image $imageRenderer,
        Wordwrap $wordwrap,
        JiraTagConverter $tagConverter,
        bool $renderFullDescription = true
    ) {
        $this->templateHelper = $templateHelper;
        $this->imageRenderer = $imageRenderer;
        $this->wordwrap = $wordwrap;
        $this->tagConverter = $tagConverter;
        $this->renderFullDescription = $renderFullDescription;
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        if ($formattedDescription = $this->formatDescription($output, $issue)) {
            $output->writeln($this->templateHelper->tabulate($formattedDescription));
        }
    }

    private function formatDescription(OutputInterface $output, Issue $issue): string
    {
        $description = $this->shortenIfNotFullRendering(trim($issue->description() ?: ''));
        if (!empty($description)) {
            return '<comment>description:</comment>' . PHP_EOL
                . $this->templateHelper->tabulate($this->renderContents($output, $issue, $description));
        }

        return '';
    }

    private function shortenIfNotFullRendering(string $text): string
    {
        if (!$this->renderFullDescription) {
            $lines = explode(PHP_EOL, $text);
            return implode(
                PHP_EOL,
                array_filter(
                    array_map('trim', array_slice($lines, 0, self::MAX_ROWS))
                )
            ) . (count($lines) > self::MAX_ROWS ? '...' : '');
        }

        return $text;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param string $description
     * @return string
     */
    private function renderContents(OutputInterface $output, Issue $issue, string $description): string
    {
        $body = $this->wordwrap->wrap($this->tagConverter->convert($output, $description, ['tabulation' => 8]));
        if ($this->renderFullDescription) {
            $body = $this->imageRenderer->render($body, $issue);
        }

        return $body;
    }

}
