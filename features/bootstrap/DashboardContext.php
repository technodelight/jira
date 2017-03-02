<?php

use Behat\Behat\Context\Context;
use Fixture\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Defines application features from the specific context.
 */
class DashboardContext implements Context
{
    private $app;
    private $exception;
    private $exitCode;
    private $output;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->app = new Application;
        $this->app->setAutoExit(false);
    }

    /**
     * @When I want to see my dashboard for :date
     */
    public function iWantToSeeMyDashboardFor($date)
    {
        try {
            $input = new ArrayInput([
                'command' => 'dashboard',
                'date' => $date,
            ]);
            $this->output = new BufferedOutput;
            $this->app->addDomainCommands();
            $this->exitCode = $this->app->run($input, $this->output);
        } catch (\Exception $e) {
            $this->exception = $e;
        } finally {
            print($this->output->fetch());
        }
    }

    /**
     * @Then it should run without error
     */
    public function itShouldrunWithoutError()
    {
        if ($this->exception instanceof \Exception) {
            throw $this->exception;
        }
        if ($this->exitCode) {
            throw new \RuntimeException(sprintf("Expected exit code 0, got %d", $this->exitCode));
        }
    }
}
