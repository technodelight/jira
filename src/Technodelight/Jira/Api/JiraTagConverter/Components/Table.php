<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\BufferedOutput;

class Table
{
    private $headers;
    private $rows = [];
    private $source = '';

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function addRow($row, $withWrap = true)
    {
        $this->rows[] = array_map(
            function($field) use ($withWrap) {
                if ($withWrap) {
                    return wordwrap(trim($field), 25);
                }
                return trim($field);
            },
            $row
        );
    }

    public function appendSource($source)
    {
        $this->source.= $source;
    }

    public function source()
    {
        return trim($this->source, PHP_EOL);
    }

    public function isEmpty()
    {
        return empty($this->headers) && empty($this->rows);
    }

    public function __toString()
    {
        $bufferedOutput = new BufferedOutput();
        $tableRenderer = new PrettyTable($bufferedOutput);
        if (!empty($this->headers)) {
            $tableRenderer->setHeaders(array_values($this->headers));
        }
        if (!empty($this->rows)) {
            $rows = [];
            foreach ($this->rows as $k => $row) {
                $rows[] = $row;
                if ($k < count($this->rows) - 1) $rows[] = new TableSeparator();
            }
            $tableRenderer->setRows($rows);
        }

        // render table using factory table renderer
        $tableRenderer->render();
        return trim($bufferedOutput->fetch());
    }
}
