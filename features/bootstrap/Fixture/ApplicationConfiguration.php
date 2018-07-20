<?php

namespace Fixture;

use Technodelight\Jira\Configuration\ApplicationConfiguration as BaseAppConf;
use Technodelight\Jira\Configuration\ApplicationConfiguration\AliasesConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\FiltersConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\ProjectConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\RenderersConfiguration;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionsConfiguration;

class ApplicationConfiguration extends BaseAppConf
{
    public static $transitions = [];

    public static $useTempo = false;

    public function integrations()
    {
        return IntegrationsConfiguration::fromArray([
            'github' => [
                'apiToken' => 'githu670k3n',
            ],
            'git' => [
                'maxBranchNameLength' => 30,
                'branchNameGenerator' => [
                    'whitelist' => 'A-Za-z0-9./-',
                    'remove' => ['BE', 'FE'],
                    'replace' => [' ', ':', '/', ','],
                    'separator' => '-',
                    'autocompleteWords' => ['fix', 'add', 'change', 'remove', 'implement'],
                    'patterns' => [
                        'preg_match("~^Release ~", issue.summary())' => ['pattern' => 'release/{clean(substr(issue.summary(), 8))}'],
                        'preg_match("~.*~", issue.summary())' => ['pattern' => 'feature/{issueKey}-{summary}'],
                    ]                ],
            ],
            'iterm' => [
                'renderImages' => false,
                'thumbnailWidth' => 300,
                'imageCacheTtl' => 0
            ],
            'tempo' => [
                'enabled' => self::$useTempo,
                'instances' => [],
                'apiToken' => '123321',
                'version' => null,
            ],
            'editor' => [
                'executable' => 'vim'
            ]
        ]);
    }

    public function instances()
    {
        return BaseAppConf\InstancesConfiguration::fromArray([
            [
                'name' => 'test',
                'domain' => 'fixture.jira.phar',
                'username' => 'test',
                'password' => 'test',
                'tempo' => self::$useTempo
            ]
        ]);
    }


    public function project()
    {
        return ProjectConfiguration::fromArray([
            'yesterdayAsWeekday' => true,
            'defaultWorklogTimestamp' => 'now',
            'oneDay' => 27000,
            'cacheTtl' => 0,
        ]);
    }


    public function transitions()
    {
        $transitions = [];
        foreach (self::$transitions as $command => $transition) {
            $transitions[] = ['command' => $command, 'transition' => (array) $transition];
        }
        return TransitionsConfiguration::fromArray($transitions);
    }

    public function aliases()
    {
        return AliasesConfiguration::fromArray([]);
    }

    public function filters()
    {
        return FiltersConfiguration::fromArray([]);
    }

    public function renderers()
    {
        return RenderersConfiguration::fromArray([]);
    }
}
