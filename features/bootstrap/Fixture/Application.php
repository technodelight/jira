<?php

namespace Fixture;

use Technodelight\Jira\Console\Application as BaseApp;

class Application extends BaseApp
{
    public function __construct($name = 'JIRA CLI', $version = 'Behat', $isTesting = true)
    {
        parent::__construct($name, $version, $isTesting);
    }
}
