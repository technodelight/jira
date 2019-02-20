<?php

namespace Technodelight\Jira\Console\Input\PullRequest;

class OutputParser
{
    private $output;
    private $title = '';
    private $content = '';
    private $labels = [];
    private $milestones = [];
    private $assignees = [];

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
        $track = array_fill_keys(['labels', 'milestones', 'assignees'], false);

        foreach ($rows as $idx => $row) {
            if (empty($this->title) && strpos($row, '#') === false) {
                $this->title = $row;
                continue;
            }
            if (strpos($row, '#') !== 0 && empty($this->labels)) {
                $this->content.= $row . PHP_EOL;
                continue;
            }

            // track if we're reached milestones section
            foreach ($track as $type => $index) {
                if ($index === false && strpos($row, '# ' . $type) === 0) {
                    $track[$type] = $idx;
                    continue;
                }
            }

            // reached a tickbox
            if (preg_match('~^# \[([xXy+ ]{1})\] (.*)~', $row, $matches)) {
                foreach (array_reverse($track) as $type => $index) {
                    if ($index === false) {
                        continue; // not set yet
                    }
                    if (!empty(trim($matches[1]))) {
                        array_push($this->$type, trim($matches[2]));
                        break;
                    }
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
        return end($this->milestones) ?: null;
    }

    public function assignees()
    {
        return $this->assignees;
    }
}
