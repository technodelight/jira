<?php

namespace Technodelight\Jira\Console\Argument;

class LogTimeArgsOptsParser
{
    /** @var array */
    private $handledArguments = ['issueKeyOrWorklogId', 'time', 'comment', 'date'];
    /** @var array */
    private $arguments;
    /** @var array */
    private $opts;

    private function __construct()
    {
    }

    public static function fromArgsOpts(array $arguments = [], array $opts = [])
    {
        $logTimeArgsOptsParser = new LogTimeArgsOptsParser();
        $logTimeArgsOptsParser->arguments = $arguments;
        $logTimeArgsOptsParser->opts = $opts;
        $logTimeArgsOptsParser->parse();

        return $logTimeArgsOptsParser;
    }

    /**
     * @return string|int
     */
    public function issueKeyOrWorklogId()
    {
        return $this->arguments['issueKeyOrWorklogId'];
    }

    /**
     * @return string
     */
    public function time()
    {
        return $this->arguments['time'];
    }

    /**
     * @return string
     */
    public function comment()
    {
        return $this->arguments['comment'];
    }

    /**
     * @return string
     */
    public function date()
    {
        return $this->arguments['date'];
    }

    /**
     * @return bool
     */
    public function isInteractive()
    {
        return $this->opts['interactive'];
    }

    private function parse()
    {
        unset($this->arguments['command']);

        if (0 == $this->countArguments()) {
            $this->opts['interactive'] = true;
        }

        if ($this->checkIfArgumentIsDot('issueKeyOrWorklogId')) {
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

    private function checkIfArgumentIsTime($arg)
    {
        return (bool) preg_match('~([0-9]+[hmsdw]{1})+~', $this->arguments[$arg]);
    }

    private function checkIfArgumentIsDate($arg)
    {
        return false !== strtotime($this->arguments[$arg]);
    }

    private function checkIfArgumentIsDot($arg)
    {
        return '.' == $this->arguments[$arg];
    }

    private function shiftArguments()
    {
        $this->arguments['date'] = $this->arguments['comment'];
        $this->arguments['comment'] = $this->arguments['time'];
        $this->arguments['time'] = $this->arguments['issueKeyOrWorklogId'];
        $this->arguments['issueKeyOrWorklogId'] = null;
    }

    private function countArguments()
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
