<?php

namespace Technodelight\Jira\Connector\GitHub;

use Github\Client;
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
        $client = new Client;
        $client->authenticate($this->configuration->token(), null, Client::AUTH_URL_TOKEN);
        return $client;
    }
}
