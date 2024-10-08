<?php

declare(strict_types=1);

namespace Technodelight\Jira\Configuration\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Filters implements Configuration
{
    public function configurations(): ArrayNodeDefinition|NodeDefinition
    {
        $root = (new TreeBuilder('filters'))->getRootNode();

        $root
            ->info('Custom quick filters registered as commands. See advanced search help at https://confluence.atlassian.com/jiracorecloud/advanced-searching-765593707.html')
            ->normalizeKeys(false)
            ->prototype('array')
                ->children()
                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
                    ->scalarNode('jql')->defaultValue('')->end()
                    ->scalarNode('filterId')->defaultNull()->end()
                    ->scalarNode('instance')->defaultNull()->end()
                ->end()
                ->beforeNormalization()->ifArray()->then(function (array $value) {
                    if (!empty($value['filterId']) && empty($value['instance'])) {
                        throw new InvalidConfigurationException('value for filter.instance must be provided when using filterId');
                    }
                    return $value;
                })
            ->end();

        return $root;
    }
}
