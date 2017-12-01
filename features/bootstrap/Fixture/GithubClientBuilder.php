<?php

namespace Fixture;

use Fixture\GitHub\TestHttpClient;
use Github\Client;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitHubConfiguration;

class GithubClientBuilder
{
    private $testHttpClient;
    private $configuration;

    public function __construct(GitHubConfiguration $configuration, TestHttpClient $testHttpClient)
    {
        $this->configuration = $configuration;
        $this->testHttpClient = $testHttpClient;
    }

    public function build()
    {
        $client = new Client($this->testHttpClient);
        $client->authenticate($this->configuration->token(), null, Client::AUTH_URL_TOKEN);
        return $client;
    }
}
