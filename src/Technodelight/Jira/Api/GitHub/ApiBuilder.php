<?php

namespace Technodelight\Jira\Api\GitHub;

use Github\Client;
use Technodelight\Jira\Configuration\ApplicationConfiguration;

class ApiBuilder
{
    private $configuration;

    public function __construct(ApplicationConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function build()
    {
        $client = new Client;
        $client->authenticate($this->configuration->githubToken(), null, Client::AUTH_URL_TOKEN);
        return $client;
    }
}
