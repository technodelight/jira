<?php

namespace Technodelight\Jira\Helper\JiraTagConverter;

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Helper\Table as TableRenderer;

class Table
{
//    private $startPos;
//    private $endPos;
    private $header;
    private $rows = [];
    private $source = '';

    public function __construct()
    {
//        $this->setStartPos($startPos);
    }

//    public function setStartPos($pos) {
//        $this->startPos = $pos;
//    }
//    public function setEndPos($pos) {
//        $this->endPos = $pos;
//    }
//    public function parseSourceFromBody($body)
//    {
//        return substr($body, $this->startPos, $this->endPos - $this->startPos);
//    }
    public function addHeader($header)
    {
        $this->header = $header;
    }

    public function addRow($row)
    {
        $this->rows[] = array_map(
            function($field) {
                return wordwrap(trim($field), 25);
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
