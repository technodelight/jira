<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Fixture\GitHub\TestHttpClient;

class GithubContext implements Context
{
    const FIXTURE_PATH = '/fixtures/github/';
    const ERROR_NO_SUCH_FIXTURE = 'No such fixture: "%s"';
    const ERROR_CANNOT_WRITE_FIXTURE = 'Fixture assertion failed: "%s"';
    const ERROR_CANNOT_READ_FIXTURE = 'Fixture read failure: "%s"';

    /**
     * @Given GitHub returns :fixture fixture for :method path :path
     */
    public function githubReturnsFixtureForPath($fixture, $method, $path)
    {
        TestHttpClient::$fixtures[$method][$path] = $this->read($fixture);
    }

    /**
     * @Given Git command :command returns:
     */
    public function gitCommandReturns($command, PyStringNode $node)
    {
        \Technodelight\ShellExec\TestShell::fixture($command, $node->getStrings());
    }

    private function read($fixture)
    {
        $filename = __DIR__ . '/' . self::FIXTURE_PATH . $fixture . '.json';
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf(self::ERROR_NO_SUCH_FIXTURE, $fixture));
        }
        $data = file_get_contents($filename);
        if (false === $data) {
            throw new \InvalidArgumentException(sprintf(self::ERROR_CANNOT_READ_FIXTURE, $fixture));
        }
        return $data;
    }
}
