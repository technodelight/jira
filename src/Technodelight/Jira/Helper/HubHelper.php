<?php

namespace Technodelight\Jira\Helper;

class HubHelper extends ShellCommandHelper
{
    private $issuesCache;

    public function issues()
    {
        if (!isset($this->issuesCache)) {
            $issues = array_map('trim', $this->shell('issue'));
            $result = [];
            foreach ($issues as $lineIdx => $row) {
                $signature = $this->parseIssueSignature($row);
                if (!empty($signature)) {
                    $result[$signature['pr']] = $signature;
                }
            }
            $this->issuesCache = $result;
        }

        return $this->issuesCache;
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
