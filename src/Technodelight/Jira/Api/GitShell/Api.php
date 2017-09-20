<?php

namespace Technodelight\Jira\Api\GitShell;

use Technodelight\Jira\Api\Shell\Command;
use Technodelight\Jira\Api\Shell\Shell;
use Technodelight\Jira\Api\Shell\ShellCommandException;

class Api
{
    const LOG_FORMAT = '"<entry><hash><![CDATA[%H]]></hash><message><![CDATA[%B]]></message><authorName>%aN</authorName><authorDate>%at</authorDate></entry>"';
    const VERBOSE_REMOTE_REGEX = '~([a-z0-9]+)\s+([^:]+):([^/]+)/(.*).git \((fetch|push)\)~';
    private $shell;
    private $remotes;
    private $verboseRemotes;
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
        $entries = $converter->asArray(implode(PHP_EOL, $this->shell->exec($command)));
        if (empty($entries)) {
            return [];
        }

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

    public function remotes($verbose = false)
    {
        if ($verbose) {
            if (!$this->verboseRemotes) {
                $remotesDef = $this->shell->exec(Command::create()->withArgument('remote')->withOption('v'));
                $this->verboseRemotes = [];
                foreach ($remotesDef as $def) {
                    if (preg_match(self::VERBOSE_REMOTE_REGEX, trim($def), $matches)) {
                        list (,$remote, $userHost, $owner, $repo, $type) = $matches;

                        if (!isset($this->verboseRemotes[$remote])) {
                            $this->verboseRemotes[$remote] = [];
                        }
                        $this->verboseRemotes[$remote][$type] = [
                            'owner' => $owner,
                            'repo' => $repo,
                            'userHost' => $userHost,
                            'url' => sprintf('%s:%s/%s.git', $userHost, $owner, $repo)
                        ];
                    }
                }
                if (!$this->verboseRemotes) {
                    throw new \RuntimeException('No git remote found!');
                    //TODO: turn off github integration this case!
                }
            }
            return $this->verboseRemotes;
        }
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
            $command->pipe(
                Command::create('grep')->withArgument(escapeshellarg($pattern))
            );
        }
        try {
            $branches = $this->shell->exec($command);
        } catch (ShellCommandException $e) {
            // ignore exception when grep returns "no lines", return code 1
            if ($pattern && $e->getCode() == 1) {
                $branches = $e->getResult();
            } else {
                throw $e;
            }
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
            $branches
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
                ->pipe(
                    Command::create('sed')->withArgument('"s/^ *//g"')
                )
                ->pipe(
                    Command::create('grep')->withOption('v')->withArgument('"^\*"')
                )
                ->pipe(
                    Command::create('head')->withOption('1')
                )
                ->pipe(
                    Command::create('sed')->withArgument('"s/.*\[\(.*\)\].*/\1/"')
                )
                ->pipe(
                    Command::create('sed')->withArgument('"s/[\^~].*//"')
                )
        );

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

    /**
     * Get name and status diff for current branch
     *
     * @param string|null $to
     * @return \Technodelight\Jira\Api\GitShell\DiffEntry[]
     */
    public function diff($to = null)
    {
        return array_map(
            function($row) {
                return DiffEntry::fromString($row);
            },
            $this->shell->exec(
                Command::create()
                       ->withArgument('diff')->withArgument($to)
                       ->withOption('name-status')
            )
        );
    }
}
