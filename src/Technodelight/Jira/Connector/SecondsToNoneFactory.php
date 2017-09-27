<?php

namespace Technodelight\Jira\Connector;

use Technodelight\SecondsToNone;
use Technodelight\SecondsToNone\Config;

class SecondsToNoneFactory
{
    public static function build(Config $config)
    {
        return new SecondsToNone($config);
    }
}
