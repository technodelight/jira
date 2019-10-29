<?php

namespace Technodelight\Jira\Connector\GitHub;

use Buzz\Client\MultiCurl;
use Github\Client;
use Github\HttpClient\Builder;

class ApiBuilder
{
    public static function build()
    {
        return new Client(
            new Builder(new MultiCurl())
        );
    }
}
