<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\ColorExtractor;
use Technodelight\Jira\Helper\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Renderer;

class Description implements Renderer
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
     * @var bool
     */
    private $renderFullDescription;

    public function __construct(TemplateHelper $templateHelper, ColorExtractor $colorExtractor, $renderFullDescription = true)
    {
        $this->templateHelper = $templateHelper;
        $this->colorExtractor = $colorExtractor;
        $this->renderFullDescription = $renderFullDescription;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($formattedDescription = $this->templateHelper->tabulate($this->formatDescription($output, $issue))) {
            $output->writeln($this->templateHelper->tabulate($formattedDescription));
        }
    }

    private function formatDescription(OutputInterface $output, Issue $issue)
    {
        $description = $this->shortenIfNotFullRendering(trim($issue->description()));
        if (!empty($description)) {
            $tagConverter = new JiraTagConverter($output, $this->colorExtractor);
            return '<comment>description:</comment>' . PHP_EOL
                . $this->templateHelper->tabulate(wordwrap($tagConverter->convert($description)));
        }
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

}
