<?php

namespace Technodelight\Jira\Console\Argument;

class LogTimeArgsOptsParser
{
    private array $handledArguments = ['issueKeyOrWorklogId', 'time', 'comment', 'date'];
    private array $arguments;
    private array $opts;

    public static function fromArgsOpts(array $arguments = [], array $opts = []): self
    {
        $instance = new LogTimeArgsOptsParser();
        $instance->arguments = $arguments;
        $instance->opts = $opts;
        $instance->parse();

        return $instance;
    }

    /** @return string|int|null */
    public function issueKeyOrWorklogId(): mixed
    {
        return $this->arguments['issueKeyOrWorklogId'];
    }

    public function time(): ?string
    {
        return $this->arguments['time'];
    }

    public function comment(): ?string
    {
        return $this->arguments['comment'];
    }

    public function date(): ?string
    {
        return $this->arguments['date'];
    }

    public function isInteractive(): bool
    {
        return (bool)$this->opts['interactive'] ?? false;
    }

    /**
     * Fucking idiot MD. See line 16
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function parse(): void
    {
        unset($this->arguments['command']);

        if (0 === $this->countArguments()) {
            $this->opts['interactive'] = true;
        }

        if ($this->checkIfArgumentIsDotOrEmpty('issueKeyOrWorklogId')) {
            $this->arguments['issueKeyOrWorklogId'] = null;
        }

        if ($this->checkIfArgumentIsTime('issueKeyOrWorklogId')) {
            $this->shiftArguments();
        }

        if ($this->checkIfArgumentIsDate('comment')) {
            $this->arguments['date'] = $this->arguments['comment'];
            $this->arguments['comment'] = null;
        }
    }

    private function checkIfArgumentIsTime(string $arg): bool
    {
        return (bool) preg_match('~([0-9]+[hmsdw]{1})+~', $this->arguments[$arg] ?? '');
    }

    private function checkIfArgumentIsDate(string $arg): bool
    {
        return null !== $this->arguments[$arg]
            && false !== strtotime($this->arguments[$arg]);
    }

    private function checkIfArgumentIsDotOrEmpty(string $arg): bool
    {
        return '.' == $this->arguments[$arg]
            || '' == $this->arguments[$arg];
    }

    private function shiftArguments(): void
    {
        $this->arguments['date'] = $this->arguments['comment'];
        $this->arguments['comment'] = $this->arguments['time'];
        $this->arguments['time'] = $this->arguments['issueKeyOrWorklogId'];
        $this->arguments['issueKeyOrWorklogId'] = null;
    }

    private function countArguments(): int
    {
        $handledArgs = $this->handledArguments;
        $arguments = $this->arguments;
        return count(array_filter(
            array_keys($arguments),
            function ($arg) use ($arguments, $handledArgs) {
                return in_array($arg, $handledArgs) && !is_null($arguments[$arg]);
            }
        ));
    }
}
