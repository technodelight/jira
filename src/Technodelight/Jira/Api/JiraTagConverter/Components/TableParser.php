<?php

namespace Technodelight\Jira\Api\JiraTagConverter\Components;

class TableParser
{
    private $body;
    private $table;

    const HEADER_LIMITER = '||';

    const COL_LIMITER = '|';

    public function __construct($body)
    {
        $this->body = $body;
    }

    public function parseAndReplace()
    {
        return $this->replaceTables($this->parse());
    }

    private function replaceTables(array $tables)
    {
        foreach ($tables as $table) {
            $originalTable = $table->source();
            $startPos = strpos($this->body, $originalTable);
            $this->body = substr($this->body, 0, $startPos)
                . (string) $table
                . substr($this->body, $startPos + strlen($originalTable));
        }

        return $this->body;
    }

    /**
     * @return Table[]
     */
    private function parse()
    {
        $lines = explode(PHP_EOL, $this->body);
        $noOfLines = count($lines);
        $tables = [];

        foreach ($lines as $row => $line) {
            if ($this->isTableRow($line)) {
                if ($headers = $this->extract($line, self::HEADER_LIMITER)) {
                    $this->table()->addHeader($headers);
                } elseif ($fields = $this->extract($line, self::COL_LIMITER)) {
                    $this->table()->addRow($fields);
                }
                $this->table()->appendSource($line . ($noOfLines == $row ? '' : PHP_EOL));
            } else if (!$this->table()->isEmpty()) {
                $tables[] = $this->table();
                unset($this->table);
            }
        }
        if (!$this->table()->isEmpty()) {
            $tables[] = $this->table();
            unset ($this->table);
        }
        return $tables;
    }

    private function isTableRow($line)
    {
        $line = trim($line);
        return substr($line, 0, 1) == self::COL_LIMITER
            && substr($line, -1, 1) == self::COL_LIMITER;
    }

    /**
     * @return Table
     */
    private function table()
    {
        if (!isset($this->table)) {
            $this->table = new Table;
        }
        return $this->table;
    }

    private function extract($line, $delimiter)
    {
        $fields = explode($delimiter, trim($line));
        array_shift($fields); // chars before the first column delimiter
        array_pop($fields); // chars after the last column delimiter
        return $fields;
    }
}
