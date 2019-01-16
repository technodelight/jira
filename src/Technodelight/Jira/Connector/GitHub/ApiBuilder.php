<?php

namespace Technodelight\Jira\Connector\GitHub;

use Buzz\Client\MultiCurl;
use Github\Client;
use Github\HttpClient\Builder;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitHubConfiguration;

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
        $client->authenticate($this->configuration->token(), null, Client::AUTH_URL_TOKEN);
        return $client;
    }
}
