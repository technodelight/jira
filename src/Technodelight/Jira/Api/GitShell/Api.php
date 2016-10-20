<?php

namespace Technodelight\Jira\Api\GitShell;

use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Api\GitShell\XmlToArray;
use Technodelight\Jira\Api\Shell\Command;
use Technodelight\Jira\Api\Shell\Shell;

class Api
{
    const LOG_FORMAT = '"<entry><hash><![CDATA[%H]]></hash><message><![CDATA[%B]]></message><authorName>%aN</authorName><authorDate>%at</authorDate></entry>"';
    private $shell;
    private $remotes;
    private $tld;

    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    public function log($from, $to = 'head')
    {
        $command = Command::create()
            ->withArgument('log')
            ->withOption('format', self::LOG_FORMAT)
            ->withOption('no-merges')
            ->withOption('date-order')
            ->withOption('reverse')
            ->withArgument(sprintf('%s..%s', $from, $to));

        $converter = new XmlToArray('entry');
        $entries = $converter->asArray(implode('', $this->shell->exec($command)));
        foreach ($entries['entry'] as $entry) {
            yield LogEntry::fromArray($entry);
        }
    }

    public function createBranch($branch)
    {
        $this->shell->exec(Command::create()->withArgument('checkout')->withOption('b')->withArgument($branch));
    }

    public function switchBranch($branch)
    {
        $this->shell->exec(Command::create()->withArgument('checkout')->withArgument($branch));
    }

    public function remotes()
    {
        if (!$this->remotes) {
            $this->remotes = $this->shell->exec(Command::create()->withArgument('remote'));
        }
        return $this->remotes;
    }

    public function branches($pattern = '')
    {
        $remotes = $this->remotes();
        $command = Command::create()->withArgument('branch')->withOption('a');
        if ($pattern) {
            $command->pipe()->withArgument('grep')->withArgument(escapeshellarg($pattern));
        }
        return array_map(
            function($branchDef) use ($remotes) {
                $current = false;
                $remote = '';
                if (preg_match('~(' . join('|', $remotes) . ')/([^/]+)/~', $branchDef, $matches)) {
                    $remote = $matches[1];
                }
                if (strpos($branchDef, '* ') !== false) {
                    $current = true;
                }

                return Branch::fromArray([
                    'name' => str_replace(['remotes/'.$remote.'/', '* '], '', $branchDef),
                    'current' => $current,
                    'remote' => $remote,
                ]);
            },
            $this->shell->exec($command)
        );
    }

    public function currentBranch()
    {
        $list = $this->branches('* ');
        foreach ($list as $branch) {
            if ($branch->current()) {
                return $branch;
            }
        }
    }

    public function parentBranch()
    {
        $parent = $this->shell->exec(
            Command::create()
                ->withArgument('show-branch')->withOption('a')->withStdErrTo('/dev/null')
                ->pipe()
                    ->withArgument('sed')->withArgument('"s/^ *//g"')
                ->pipe()
                    ->withArgument('grep')->withOption('v')->withArgument('"^\*"')
                ->pipe()
                    ->withArgument('head')->withOption('1')
                ->pipe()
                    ->withArgument('sed')->withArgument('"s/.*\[\(.*\)\].*/\1/"')
                ->pipe()
                    ->withArgument('sed')->withArgument('"s/[\^~].*//"')
        )
        // $parent = $this->shell->exec(
        //     'show-branch -a 2> /dev/null | sed "s/^ *//g" | grep -v "^\*" | head -1 | sed "s/.*\[\(.*\)\].*/\1/" | sed "s/[\^~].*//"'
        // );
        return end($parent);
    }

    public function topLevelDirectory()
    {
        if (!$this->tld) {
            $tld = $this->shell->exec(Command::create()->withArgument('rev-parse')->withOption('show-toplevel'));
            $this->tld = end($tld);
        }
        return $this->tld;
    }
}
