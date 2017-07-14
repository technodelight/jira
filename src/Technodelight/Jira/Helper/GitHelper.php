<?php

namespace Technodelight\Jira\Helper;

/**
 * Class GitHelper
 *
 * @package Technodelight\Jira\Helper
 * @deprecated
 */
class GitHelper extends ShellCommandHelper
{
    private $remotes;

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
        if (isset($entries['entry']['message'])) {
            return array($entries['entry']);
        } elseif (isset($entries['entry'])) {
            return $entries['entry'];
        }

        return [];
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
        $self = $this;
        $remotes = array_map(function($remote) { return $remote . '/'; }, $this->remotes());
        return array_map(
            function($branchDef) use ($remotes, $self) {
                $type = 'checkout';
                $current = false;
                $remote = false;
                if (strpos($branchDef, 'remotes/') !== false) {
                    $type = 'switch';
                    $remote = true;
                }
                if (strpos($branchDef, '* ') !== false) {
                    $current = true;
                }
                $name = trim(str_replace($remotes, '', str_replace('remotes/', '', $branchDef)), '* ');
                return [
                    'name' => $name,
                    'type' => $type,
                    'current' => $current,
                    'remote' => $remote,
                    'issueKey' => $self->issueKeyFromBranch($name),
                ];
            },
            $this->shell('branch -a ' . ($pattern ? sprintf('| grep "%s"', $pattern) : ''))
        );
    }

    public function currentBranch()
    {
        $list = $this->branches('* ');
        foreach ($list as $branch) {
            if ($branch['current']) {
                return $branch['name'];
            }
        }
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
        return $this->issueKeyFromBranch($this->currentBranch());
    }

    private function issueKeyFromBranch($branchName)
    {
        if (preg_match('~^feature/([A-Z]+-[0-9]+)-(.*)~', $branchName, $matches)) {
            return $matches[1];
        }

        return '';
    }

    public function topLevelDirectory()
    {
        $tld = $this->shell('rev-parse --show-toplevel');
        return trim(end($tld));
    }

    public function commitEntries()
    {
        if ($parent = $this->parentBranch()) {
            return $this->log($parent);
        }

        return [];
    }

    public function remotes()
    {
        if (!$this->remotes) {
            $this->remotes = array_map(
                'trim',
                $this->shell('remote')
            );
        }
        return $this->remotes;
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
