<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\Image;
use Technodelight\Jira\Api\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Helper\Wordwrap;
use Technodelight\Jira\Renderer\IssueRenderer;

class Description implements IssueRenderer
{
    /**
     * @var TemplateHelper
     */
    private $templateHelper;
    /**
     * @var Image
     */
    private $imageRenderer;
    /**
     * @var Wordwrap
     */
    private $wordwrap;
    /**
     * @var JiraTagConverter
     */
    private $tagConverter;
    /**
     * @var bool
     */
    private $renderFullDescription;

    public function __construct(TemplateHelper $templateHelper, Image $imageRenderer, Wordwrap $wordwrap, JiraTagConverter $tagConverter, $renderFullDescription = true)
    {
        $this->templateHelper = $templateHelper;
        $this->imageRenderer = $imageRenderer;
        $this->wordwrap = $wordwrap;
        $this->tagConverter = $tagConverter;
        $this->renderFullDescription = $renderFullDescription;
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
        $body = $this->wordwrap->wrap($this->tagConverter->convert($output, $description, ['tabulation' => 8]));
        if ($this->renderFullDescription) {
            $body = $this->imageRenderer->render($body, $issue);
        }

        return $body;
    }

}
