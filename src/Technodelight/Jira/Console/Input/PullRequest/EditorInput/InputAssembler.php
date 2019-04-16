<?php

namespace Technodelight\Jira\Console\Input\PullRequest\EditorInput;

use Technodelight\GitShell\LogEntry;

class InputAssembler
{
    private $title;
    /**
     * @var array
     */
    private $logEntries;
    /**
     * @var array
     */
    private $labels;
    /**
     * @var array
     */
    private $milestones;
    /**
     * @var array
     */
    private $assignees;

    /**
     * InputAssembler constructor.
     *
     * @param string $title
     * @param LogEntry[] $logEntries
     * @param array $labels
     * @param array $milestones
     */
    public function __construct($title, array $logEntries, array $labels, array $milestones, array $assignees)
    {
        $this->title = $title;
        $this->logEntries = $logEntries;
        $this->labels = $labels;
        $this->milestones = $milestones;
        $this->assignees = $assignees;
    }

    /**
     * @return string
     */
    public function title()
    {
        if (preg_match('~([A-Z]+-\d+)-(.*)~', $this->title, $matches)) {
            $issueKey = $matches[1];

            return sprintf('Create pull request for %s', $issueKey);
        }

        return sprintf('Create pull request for %s', ucfirst(strtr($this->title, ['-' => ' ', '/' => ' '])));
    }

    public function content()
    {
        return join(PHP_EOL, array_merge(
            [$this->prTitle() . PHP_EOL],
            $this->formatLogEntries($this->logEntries),
            ['# please tick the boxes with an "x" below if you want to assign labels or milestone'],
            $this->format($this->labels, 'labels', 'name'),
            $this->format($this->milestones, 'milestones', 'title'),
            $this->format($this->assignees, 'assignees', 'login')
        ));
    }

    /**
     * @return string
     */
    private function prTitle()
    {
        if (preg_match('~([A-Z]+-\d+)-(.*)~', $this->title, $matches)) {
            $issueKey = $matches[1];
            $change = strtr($matches[2], ['-' => ' ']);

            return sprintf('%s %s', $issueKey, $change);
        }

        return ucfirst(strtr($this->title, ['-' => ' ', '/' => ' ']));
    }

    private function formatLogEntries(array $logEntries)
    {
        $rows = array_map(function (LogEntry $log) {
            /** @var LogEntry $log */
            if ($log->message()->hasBody()) {
                return $log->message()->getBody();
            }

            return $log->message()->getHeader();
        }, $logEntries);

        return array_map(function($row) {
            return '- ' . ltrim($row, '- ');
        }, array_filter(array_map('trim', $rows)));
    }

    private function format(array $labels, $title, $key)
    {
        $content = [
            '#',
            '# ' . $title . ':'
        ];
        foreach ($labels as $label) {
            $content[] = '# [ ] ' . $label[$key];
        }

        return $content;
    }
}
