<?php

namespace Technodelight\Jira\Console\Input\PullRequest;

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
     * InputAssembler constructor.
     *
     * @param string $title
     * @param LogEntry[] $logEntries
     * @param array $labels
     * @param array $milestones
     */
    public function __construct($title, array $logEntries, array $labels, array $milestones)
    {
        $this->title = $title;
        $this->logEntries = $logEntries;
        $this->labels = $labels;
        $this->milestones = $milestones;
    }

    /**
     * @return string
     */
    public function title()
    {
        if (preg_match('~([A-Z]+-\d+)-(.*)~', $this->title, $matches)) {
            $issueKey = $matches[1];
            $change = strtr($matches[2], ['-' => ' ']);

            return sprintf('%s %s', $issueKey, $change);
        }

        return ucfirst(strtr($this->title, ['-' => ' ', '/' => ' ']));
    }

    public function content()
    {
        return join(PHP_EOL, array_merge(
            $this->formatLogEntries($this->logEntries),
            [PHP_EOL],
            $this->format($this->labels, 'labels'),
            [PHP_EOL],
            $this->format($this->milestones, 'milestones')
        ));
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
        }, $rows);
    }

    private function format(array $labels, $title)
    {
        $content = [
            '#',
            '# ' . $title . ':'
        ];
        foreach ($labels as $label) {
            $content[] = '# [ ] ' . (!empty($label['name']) ? $label['name'] : $label['title']);
        }
        $content[] = '#';

        return $content;
    }
}
