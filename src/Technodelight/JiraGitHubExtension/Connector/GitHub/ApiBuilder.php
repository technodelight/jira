<?php

namespace Technodelight\JiraGitHubExtension\Connector\GitHub;

use Buzz\Client\MultiCurl;
use Github\Client;
use Github\HttpClient\Builder;
use Technodelight\JiraGitHubExtension\Configuration\GitHubConfiguration;

class ApiBuilder
{
    private $configuration;

    public function __construct(GitHubConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function build()
    {
        $client = new Client(
            new Builder(new MultiCurl())
        );
        $client->authenticate($this->configuration->token(), null, Client::AUTH_HTTP_TOKEN);

        return $client;
    }
}
