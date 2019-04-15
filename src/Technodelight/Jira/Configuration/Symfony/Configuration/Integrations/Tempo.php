<?php

namespace Technodelight\Jira\Configuration\Symfony\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Symfony\Configuration\Configuration;

class Tempo implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder)->root('tempo')
            ->info('Tempo timesheets (https://tempo.io/doc/timesheets/api/rest/latest)')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')
                    ->defaultFalse()
                    ->treatNullLike(false)
                    ->validate()
                        ->ifString()->then(function($value) { return (bool) $value; })
                    ->end()
                ->end()
                ->scalarNode('version')->defaultValue('2')->end()
                ->scalarNode('apiToken')->attribute('hidden', true)->defaultNull()->end()
                ->variableNode('instances')
                    ->defaultNull()->example('secondary')
                    ->validate()
                        ->ifString()->then(function ($instance) {
                            $names = explode(',', $instance);
                            $instances = [];
                            foreach ($names as $name) {
                                $instances[] = ['name' => trim($name), 'apiToken' => null];
                            }

                            return $instances;
                        })
                        ->ifNull()->then(function () {
                            return [['name' => null, 'apiToken' => null]];
                        })
                        ->ifArray()->then(function (array $instances) {
                            foreach ($instances as $inst) {
                                if (empty($inst['name']) || empty($inst['apiToken'])) {
                                    throw new \InvalidArgumentException(
                                        'Tempo version 2: you must provide both "name" and "apiToken" for each instance. This seems to be invalid: ' . var_export($instances, true)
                                    );
                                }
                            }
                            return $instances;
                        })
                    ->end()
                ->end()
            ->end();
    }
}
