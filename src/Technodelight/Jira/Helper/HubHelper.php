<?php

namespace Technodelight\Jira\Helper;

class HubHelper extends ShellCommandHelper
{
    public function issues()
    {
        $issues = array_map('trim', $this->shell('issue'));
        $result = [];
        foreach ($issues as $lineIdx => $row) {
            $signature = $this->parseIssueSignature($row);
            if (!empty($signature)) {
                $result[$row['pr']] = $row;
            }
        }

        return $result;
    }

    public function getName()
    {
        return 'hub';
    }

    private function parseIssueSignature($row)
    {
        if (preg_match('~([0-9]+)\]\s(.*) \( (https://github.com/.*) \)$~', $row, $matches)) {
            return ['pr' => $matches[1], 'text' => $matches[2], 'link' => $matches[3]];
        }

        return [];
    }

    protected function getExecutable()
    {
        return '/usr/bin/env hub';
    }
}
