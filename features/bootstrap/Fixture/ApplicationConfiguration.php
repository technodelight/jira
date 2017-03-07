<?php

namespace Fixture;

use Technodelight\Jira\Configuration\ApplicationConfiguration as BaseAppConf;

class ApplicationConfiguration extends BaseAppConf
{
    private static $transitions = [];

    public function username()
    {
        return 'zgal';
    }

    public function password()
    {
        return 'YouDontNeedPasswordsInFixtures';
    }

    public function githubToken()
    {
        return 'githu670k3n';
    }

    public function domain()
    {
        return 'fixture.jira.phar';
    }

    public function yesterdayAsWeekday()
    {
        return true;
    }

    public function defaultWorklogTimestamp()
    {
        return 'now';
    }

    public function cacheTtl()
    {
        return 0;
    }

    public function transitions()
    {
        return self::$transitions;
    }

    public function aliases()
    {
        return [];
    }

    public function filters()
    {
        return [];
    }
}
