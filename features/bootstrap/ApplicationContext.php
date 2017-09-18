<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Fixture\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ApplicationContext implements Context
{
    private $app;
    private $exitCode;
    private $output;

    /**
     * @When I run the application with the following input:
     */
    public function iRunTheApplicationWithTheFollowingInput(TableNode $table)
    {
        $input = new ArrayInput($table->getRowsHash());
        $this->output = new BufferedOutput;
        $this->app()->addDomainCommands();
        $this->exitCode = $this->app()->run($input, $this->output);
        print($this->output->fetch());
    }

    /**
     * @Then the exit code should be :exitCode
     */
    public function theExitCodeShouldBe($exitCode)
    {
        if ($this->exitCode !== (int) $exitCode) {
            throw new \RuntimeException(sprintf("Expected exit code %d, got %d", $exitCode, $this->exitCode));
        }
    }

    public function app()
    {
        if (!isset($this->app)) {
            $this->app = new Application;
            $this->app->setAutoExit(false);
        }

        return $this->app;
    }
}
