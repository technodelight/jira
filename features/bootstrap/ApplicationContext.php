<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Fixture\Configuration\Loader;
use Fixture\DependencyInjection\Provider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Technodelight\Jira\Console\Application;
use Technodelight\Jira\Console\Bootstrap\Bootstrap;

class ApplicationContext implements Context
{
    private int $exitCode;
    private string $output;
    private Application $app;

    /** @Given the application configuration :property is configured with: */
    public function theApplicationIsConfiguredWith($property, $jsonString)
    {
        Loader::$configs[$property] = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
    }

    /** @When I run the application with the following input: */
    public function iRunTheApplicationWithTheFollowingInput(TableNode $table)
    {
        $input = new ArrayInput($table->getRowsHash() + ['-vvv' => '']);
        $output = new BufferedOutput;
        $this->exitCode = $this->app()->run($input, $output);
        $this->output = $output->fetch();
        print($this->output);
    }

    /** @Then the exit code should be :exitCode */
    public function theExitCodeShouldBe($exitCode)
    {
        if ($this->exitCode !== (int) $exitCode) {
            throw new RuntimeException(sprintf("Expected exit code %d, got %d", $exitCode, $this->exitCode));
        }
    }

    /** @Then the output should contain :text */
    public function theOutputShouldContain($text)
    {
        if (!str_contains($this->output, $text)) {
            throw new RuntimeException(sprintf('Output does not contain expected string:' .PHP_EOL . '%s', $text));
        }
    }

    public function app(): Application
    {
        if (!isset($this->app)) {
            defined('APPLICATION_ROOT_DIR') || define('APPLICATION_ROOT_DIR', realpath(dirname(__DIR__, 2)));
            defined('SKIP_CACHE_CONTAINER') || define('SKIP_CACHE_CONTAINER', true);
            defined('ENVIRONMENT') || define('ENVIRONMENT', 'test');

            $boot = new Bootstrap(new Provider);
            $app = $boot->boot('behat');
            $app->setAutoExit(false);

            $this->app = $app;
        }

        return $this->app;
    }
}
