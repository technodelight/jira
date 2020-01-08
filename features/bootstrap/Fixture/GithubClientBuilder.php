<?php

namespace Fixture;

use Fixture\GitHub\TestHttpClient;
use Github\Client;

class GithubClientBuilder
{
    private $testHttpClient;

    public function __construct(TestHttpClient $testHttpClient)
    {
        $this->testHttpClient = $testHttpClient;
    }

    public function build()
    {
        $client = Client::createWithHttpClient($this->testHttpClient);
        $client->authenticate('thisTokenIsAFake', null, Client::AUTH_URL_TOKEN);

        return $client;
    }
}
