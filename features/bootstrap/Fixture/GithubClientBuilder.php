<?php

namespace Fixture;

use Fixture\GitHub\TestHttpClient;
use Github\Client;
use Technodelight\Jira\Configuration\ApplicationConfiguration as AppConf;

class GithubClientBuilder
{
    private $testHttpClient;
    private $configuration;

    public function __construct(AppConf $configuration, TestHttpClient $testHttpClient)
    {
        $this->configuration = $configuration;
        $this->testHttpClient = $testHttpClient;
    }

    public function build()
    {
        $client = new Client($this->testHttpClient);
        $client->authenticate($this->configuration->githubToken(), null, Client::AUTH_URL_TOKEN);
        return $client;
    }
}
