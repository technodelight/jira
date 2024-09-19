<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Project implements Configuration
{
    public function configurations(): ArrayNodeDefinition|NodeDefinition
    {
        return (new TreeBuilder('project'))->getRootNode()
            ->info('Project specific settings')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('yesterdayAsWeekday')
                    ->info('Using \'yesterday\' means last workday on monday')
                    ->defaultTrue()
                ->end()
                ?->scalarNode('defaultWorklogTimestamp')
                    ->info('Default worklog timestamp to use if date is omitted')
                    ->defaultValue('now')
                ->end()
                ?->scalarNode('oneDay')
                    ->info('Your work hours for a single day (valid values ie. "7 hours 30 minutes", 7.5 (treated as hours), 27000 (in seconds)')
                    ->defaultValue(7.5 * 3600)
                ->end()
                ?->integerNode('cacheTtl')
                    ->info('keep API data in caches')
                    ->defaultValue(15 * 60)
                ->end()
            ?->end();
    }
}
