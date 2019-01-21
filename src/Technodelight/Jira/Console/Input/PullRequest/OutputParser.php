<?php

namespace Technodelight\Jira\Console\Input\PullRequest;

class OutputParser
{
    private $output;
    private $title = '';
    private $content = '';
    private $labels = [];
    private $milestone = '';

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function parse()
    {
        $rows = explode(PHP_EOL, $this->output);
        $this->title = '';
        $this->content = '';
        $this->labels = [];
        $foundLabels = false;
        $foundMilestones = false;
        foreach ($rows as $row) {
            if (empty($this->title) && strpos($row, '#') === false) {
                $this->title = $row;
                continue;
            }
            if (strpos($row, '#') !== 0 && empty($this->labels)) {
                $this->content.= $row . PHP_EOL;
                continue;
            }
            if (!empty($row) && strpos($row, '# labels') === 0) {
                $foundLabels = true;
            }
            if (!empty($row) && strpos($row, '# milestones') === 0) {
                $foundMilestones = true;
            }
            if ($foundMilestones && preg_match('~\[([xXy+ ]{1})\] (.*)~', $row, $matches)) {
                $this->milestone = trim($matches[2]);
                continue;
            }
            if ($foundLabels && preg_match('~\[([xXy+ ]{1})\] (.*)~', $row, $matches)) {
                if (!empty(trim($matches[1]))) {
                    $this->labels[] = trim($matches[2]);
                }
            }
        }
    }

    public function title()
    {
        return $this->title;
    }

    public function content()
    {
        return trim($this->content);
    }

    public function labels()
    {
        return $this->labels;
    }

    public function milestone()
    {
        return $this->milestone;
    }
}
