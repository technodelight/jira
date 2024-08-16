<?php

declare(strict_types=1);

namespace Technodelight\Jira\Api\TaskWarrior;

use Technodelight\ShellExec\Command;
use Technodelight\ShellExec\Shell;
use Technodelight\ShellExec\ShellCommandException;

/** @SuppressWarnings(PHPMD.StaticAccess) */
class Api
{
    private bool $isSupported;

    public function __construct(private readonly Shell $shell) {}

    public function isSupported(): bool
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

    public function list($pattern = null): array
    {
        $command = Command::create('/usr/bin/env task');
        if ($pattern !== null) {
            $command->withArgument($pattern);
        }
        $command->withArgument('list')
            ->withStdErrTo('/dev/null');

        try {
            return $this->shell->exec($command);
        } catch (ShellCommandException $exception) {
            if ($exception->getCode() === 1) {
                return [];
            }

            throw $exception;
        }
    }
}
