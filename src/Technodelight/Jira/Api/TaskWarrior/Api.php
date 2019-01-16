<?php

namespace Technodelight\Jira\Api\TaskWarrior;

use Technodelight\ShellExec\Command;
use Technodelight\ShellExec\Shell;
use Technodelight\ShellExec\ShellCommandException;

class Api
{
    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var bool
     */
    private $isSupported;

    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    public function isSupported()
    {
        if (!isset($this->isSupported)) {
            try {
                $this->shell->exec(
                    Command::create('which')
                        ->withArgument('task')
                        ->withStdErrToStdOut()
                        ->withStdOutTo('/dev/null')
                );
                $this->isSupported = true;
            } catch (ShellCommandException $e) {
                $this->isSupported = false;
            }
        }

        return $this->isSupported;
    }

    public function list($pattern = null)
    {
        $command = Command::create('/usr/bin/env task');
        if ($pattern !== null) {
            $command->withArgument($pattern);
        }
        $command->withArgument('list')
            ->withStdErrTo('/dev/null');

        try {
            return $this->shell->exec($command);
        } catch (ShellCommandException $e) {
            if ($e->getCode() == 1) {
                return [];
            }

            throw $e;
        }
    }
}
