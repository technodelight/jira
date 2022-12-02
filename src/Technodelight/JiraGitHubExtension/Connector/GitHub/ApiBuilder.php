<?php

namespace Technodelight\JiraGitHubExtension\Connector\GitHub;

use Buzz\Client\MultiCurl;
use Github\AuthMethod;
use Github\Client;
use Github\HttpClient\Builder;
use GuzzleHttp\Psr7\HttpFactory;
use Technodelight\JiraGitHubExtension\Configuration\GitHubConfiguration;

class ApiBuilder
{
    public function __construct(private readonly GitHubConfiguration $configuration)
    {}

    public function build(): Client
    {
        $client = new Client(
            new Builder(new MultiCurl(
                new HttpFactory()
            ))
        );
        $client->authenticate($this->configuration->token(), null, AuthMethod::ACCESS_TOKEN);

        return $client;
    }
}
