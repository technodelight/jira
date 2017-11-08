<?php

namespace Technodelight\Jira\Helper\JiraTagConverter;

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Helper\Table as TableRenderer;

class Table
{
    private $header;
    private $rows = [];
    private $source = '';

    public function addHeader($header)
    {
        $this->header = $header;
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
        return empty($this->header) && empty($this->rows);
    }

    public function __toString()
    {
        $bufferedOutput = new BufferedOutput();
        $tableRenderer = new TableRenderer($bufferedOutput);
        if ($this->header) {
            $tableRenderer->setHeaders(array_values($this->header));
        }
        if ($this->rows) {
            $rows = [];
            foreach ($this->rows as $k => $row) {
                $rows[] = $row;
                if ($k < count($this->rows) - 1) $rows[] = new TableSeparator();
            }
            $tableRenderer->setRows($rows);
        }

        $tableRenderer->render();

        return trim($bufferedOutput->fetch());
    }
}
