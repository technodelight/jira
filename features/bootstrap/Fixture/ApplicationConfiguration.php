<?php

namespace Fixture;

use Technodelight\Jira\Configuration\ApplicationConfiguration as BaseAppConf;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Configuration\TransitionResolver;

class ApplicationConfiguration extends BaseAppConf
{
    public static $transitions = [];

    public static $useTempo = false;

    public function tempo()
    {
        return [
            'enabled' => self::$useTempo
        ];
    }

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

    public function oneDayAmount()
    {
        return 27000;
    }

    public function transitions()
    {
        return new TransitionResolver(self::$transitions);
    }

    public function aliases()
    {
        return [];
    }

    public function filters()
    {
        return [];
    }

    public function renderers()
    {
        return RenderersConfiguration::fromArray([]);
    }
}
