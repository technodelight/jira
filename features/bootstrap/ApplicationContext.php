<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Fixture\ApplicationConfiguration;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Technodelight\Jira\Console\Bootstrap\Bootstrap;

class ApplicationContext implements Context
{
    private $app;
    private $exitCode;
    /**
     * @var string
     */
    private $output;

    /**
     * @Given the application configuration :property is configured with:
     */
    public function theApplicationIsConfiguredWith($property, $jsonString)
    {
        ApplicationConfiguration::$$property = json_decode($jsonString, true);
    }

    /**
     * @When I run the application with the following input:
     */
    public function iRunTheApplicationWithTheFollowingInput(TableNode $table)
    {
        $input = new ArrayInput($table->getRowsHash() + ['-vvv']);
        $output = new BufferedOutput;
        $this->exitCode = $this->app()->run($input, $output);
        $this->output = $output->fetch();
        print($this->output);
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

    /**
     * @Then the output should contain :text
     */
    public function theOutputShouldContain($text)
    {
        if (strpos($this->output, $text) === false) {
            throw new \RuntimeException(sprintf('Output does not contain expected string:' .PHP_EOL . '%s', $text));
        }
    }

    public function app()
    {
        if (!isset($this->app)) {
            if (!defined('APPLICATION_ROOT_DIR')) {
                define('APPLICATION_ROOT_DIR', realpath(__DIR__ . '/../..'));
                define('SKIP_CACHE_CONTAINER', true);
                define('ENVIRONMENT', 'test');
            }
            $boot = new Bootstrap();
            $this->app = $boot->boot('behat');
            $this->app->setAutoExit(false);
        }

        return $this->app;
    }
}
