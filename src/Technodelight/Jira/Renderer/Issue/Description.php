<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\ColorExtractor;
use Technodelight\Jira\Helper\Image;
use Technodelight\Jira\Helper\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;

class Description implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\Jira\Helper\ColorExtractor
     */
    private $colorExtractor;
    /**
     * @var \Technodelight\Jira\Helper\Image
     */
    private $imageRenderer;
    /**
     * @var bool
     */
    private $renderFullDescription;
    /**
     * @var \Technodelight\Jira\Helper\Wordwrap
     */
    private $wordwrap;

    public function __construct(TemplateHelper $templateHelper, ColorExtractor $colorExtractor, Image $imageRenderer, Wordwrap $wordwrap, $renderFullDescription = true)
    {
        $this->templateHelper = $templateHelper;
        $this->colorExtractor = $colorExtractor;
        $this->renderFullDescription = $renderFullDescription;
        $this->imageRenderer = $imageRenderer;
        $this->wordwrap = $wordwrap;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($formattedDescription = $this->formatDescription($output, $issue)) {
            $output->writeln($this->templateHelper->tabulate($formattedDescription));
        }
    }

    private function formatDescription(OutputInterface $output, Issue $issue)
    {
        $description = $this->shortenIfNotFullRendering(trim($issue->description()));
        if (!empty($description)) {
            return '<comment>description:</comment>' . PHP_EOL
                . $this->templateHelper->tabulate($this->renderContents($output, $issue, $description));
        }

        return '';
    }

    private function shortenIfNotFullRendering($text, $maxLines = 2)
    {
        if (!$this->renderFullDescription) {
            $lines = explode(PHP_EOL, $text);
            return implode(
                    PHP_EOL,
                    array_filter(
                        array_map('trim', array_slice($lines, 0, $maxLines))
                    )
                ) . (count($lines) > $maxLines ? '...' : '');
        }

        return $text;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @param string $description
     * @return string
     */
    private function renderContents(OutputInterface $output, Issue $issue, $description)
    {
        $tagConverter = new JiraTagConverter($output, $this->colorExtractor);
        $body = $this->wordwrap->wrap($tagConverter->convert($description));
        if ($this->renderFullDescription) {
            $body = $this->imageRenderer->render($body, $issue);
        }

        return $body;
    }

}
