<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\AccountIdUsernameReplacer;
use Technodelight\Jira\Helper\Image as ImageRenderer;
use Technodelight\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;

class Description implements IssueRenderer
{
    private const MAX_ROWS = 2;

    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly ImageRenderer $imageRenderer,
        private readonly Wordwrap $wordwrap,
        private readonly JiraTagConverter $tagConverter,
        private readonly AccountIdUsernameReplacer $replacer,
        private readonly bool $renderFull = true
    ) {}

    public function render(OutputInterface $output, Issue $issue): void
    {
        $formattedDescription = $this->formatDescription($output, $issue);
        if (!empty($formattedDescription)) {
            $output->writeln($this->templateHelper->tabulate($formattedDescription));
        }
    }

    private function formatDescription(OutputInterface $output, Issue $issue): string
    {
        $content = $this->replacer->replace(trim($issue->description() ?: ''));
        $description = $this->shortenIfNotFullRendering($content);
        if (!empty($description)) {
            return '<comment>description:</comment>' . PHP_EOL
                . $this->templateHelper->tabulate($this->renderContents($output, $issue, $description));
        }

        return '';
    }

    private function shortenIfNotFullRendering(string $text): string
    {
        if (!$this->renderFull) {
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

    private function renderContents(OutputInterface $output, Issue $issue, string $description): string
    {
        $body = $this->wordwrap->wrap($this->tagConverter->convert($output, $description, ['tabulation' => 8]));
        if ($this->renderFull) {
            $body = $this->imageRenderer->render($body, $issue);
        }

        return $body;
    }

}
