<?php

namespace Technodelight\Jira\Api\OpenApp\Driver;

use Technodelight\Jira\Api\OpenApp\Driver;
use Technodelight\Jira\Api\OpenApp\Exception;
use Technodelight\Jira\Api\Shell\Command;
use Technodelight\Jira\Api\Shell\Shell;
use Technodelight\Jira\Api\Shell\ShellCommandException;

class XdgOpen implements Driver
{
    /**
     * @var \Technodelight\Jira\Api\Shell\Shell
     */
    private $shell;

    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    /**
     * @param string $uri
     * @throws Exception
     * @return void
     */
    public function open($uri)
    {
        try {
            $this->shell->exec(Command::create('xdg-open')->withArgument($uri));
        } catch (ShellCommandException $exception) {
            Exception::fromUri($uri);
        }
    }
}
