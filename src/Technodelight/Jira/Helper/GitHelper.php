<?php

namespace Technodelight\Jira\Helper;

class GitHelper extends ShellCommandHelper
{
    public function log($from, $to = 'head')
    {
        $logs = $this->shell(
            'log'
            . ' --format="<entry><hash><![CDATA[%H]]></hash><message><![CDATA[%B]]></message><authorName>%aN</authorName><authorDate>%at</authorDate></entry>"'
            . ' --no-merges'
            . ' --date-order'
            . ' --reverse'
            . sprintf(' %s..%s', $from, $to)
        );
        $xml = simplexml_load_string(
            sprintf('<root>%s</root>', implode('', $logs)),
            null,
            LIBXML_NOCDATA
        );
        $entries = $this->xml2array($xml);
        $entries['entry'] = (array) $entries['entry'];
        return $entries;
    }

    public function createBranch($branchName)
    {
        $this->shell(sprintf('checkout -b %s', $branchName));
    }

    public function switchBranch($branchName)
    {
        $this->shell(sprintf('checkout %s', $branchName));
    }

    public function branches($pattern = '')
    {
        return array_map(
            function($row) {
                return str_replace('remotes/', '', $row);
            },
            $this->shell('branch -a ' . ($pattern ? sprintf('| grep "%s"', $pattern) : ''))
        );
    }

    public function currentBranch()
    {
        $list = $this->branches('* ');
        return ltrim(end($list), '* ');
    }

    public function parentBranch()
    {
        $parent = $this->shell(
            'show-branch -a 2> /dev/null | sed "s/^ *//g" | grep -v "^\*" | head -1 | sed "s/.*\[\(.*\)\].*/\1/" | sed "s/[\^~].*//"'
        );
        return trim(end($parent));
    }

    public function issueKeyFromCurrentBranch()
    {
        if (preg_match('~^feature/([A-Z]+-[0-9]+)-(.*)~', $this->currentBranch(), $matches)) {
            return $matches[1];
        }

        return '';
    }

    public function topLevelDirectory()
    {
        $tld = $this->shell('rev-parse --show-toplevel');
        return trim(end($tld));
    }

    public function commitMessages()
    {
        if ($parent = $this->parentBranch()) {
            return array_map(
                function($entry) {
                    return $entry['message'];
                },
                $this->log($parent)['entry']
            );
        }

        return [];
    }

    public function getName()
    {
        return 'git';
    }

    protected function getExecutable()
    {
        return '/usr/bin/env git';
    }

    private function xml2array($xmlObject, $out = [])
    {
        foreach ((array) $xmlObject as $index => $node) {
            $out[$index] = (is_object($node) || is_array($node)) ? $this->xml2array($node) : (string) $node;
        }

        return $out;
    }
}
