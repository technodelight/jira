<?php

namespace Technodelight\Jira\Console\Application\Daemon;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\Application\Daemon;

class Task implements \Core_ITask
{
    /**
     * @var Daemon
     */
    private $daemon;
    /**
     * @var Application
     */
    private $app;
    /**
     * @var string
     */
    private $arguments;
    /**
     * @var BufferedOutput
     */
    private $output;
    /**
     * @var resource
     */
    private $socket;

    public function __construct(Application $app, $arguments, $socket)
    {
        $this->app = $app;
        $this->arguments = $arguments;
        $this->socket = $socket;
    }

    /**
     * Called on Construct or Init
     *
     * @return void
     */
    public function setup()
    {
        $this->daemon = Daemon::getInstance();
    }

    /**
     * Called on Destruct
     *
     * @return void
     */
    public function teardown()
    {
        is_resource($this->socket) && socket_write($this->socket, $this->output->fetch());
    }

    /**
     * This is called after setup() returns
     *
     * @return void
     */
    public function start()
    {
        $this->daemon->log("- " . $this->arguments);
        try {
            $input = new StringInput($this->arguments);
            $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
            $this->app->run($input, $output);
        } catch (\Exception $exception) {
            $output = new BufferedOutput();
            $output->setVerbosity(BufferedOutput::VERBOSITY_VERBOSE);
            $this->app->renderException($exception, $output);
            $this->daemon->log($exception);
        } finally {
            $this->output = isset($output) ? $output : new BufferedOutput();
        }
    }
}
