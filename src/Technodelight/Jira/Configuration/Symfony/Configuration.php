<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Technodelight\Jira\Console\OutputFormatter\PaletteOutputFormatterStyle;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('');

        $rootNode
            ->children()
                ->append($this->credentialsSection())
                ->append($this->instancesSection())
                ->append($this->integrationsSection())
                ->append($this->projectSection())
                ->append($this->transitionsSection())
                ->append($this->aliasesSection())
                ->append($this->filtersSection())
                ->append($this->rendererSection())
            ->end()
        ->end();

        return $treeBuilder;
    }

    private function credentialsSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('credentials');

        $rootNode
            ->info('JIRA connection credentials')
            ->attribute('deprecated', true)
            ->children()
                ->scalarNode('domain')
                    ->info('JIRA\'s domain without protocol, like something.atlassian.net')
                    ->example('something.atlassian.net')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('username')
                    ->info('Your JIRA username')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('password')
                    ->info('Your JIRA password')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ->end();

        return $rootNode;
    }

    private function instancesSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('instances');

        $rootNode
            ->info('Different JIRA instances to use')
                ->prototype('array')
                    ->normalizeKeys(false)
                    ->children()
                        ->scalarNode('name')
                            ->info('Unique internal ID to use in command line arguments as reference (ie. --instance secondary)')
                            ->example('secondary')
                        ->end()
                        ->scalarNode('domain')
                            ->info('JIRA\'s domain without protocol, like something.atlassian.net')
                            ->example('something.atlassian.net')
                            ->cannotBeEmpty()
                            ->isRequired()
                        ->end()
                        ->scalarNode('username')
                            ->info('Instance JIRA username')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('password')
                            ->info('Instance JIRA password')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }

    private function integrationsSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('integrations');

        $rootNode
            ->info('Third party integration configs')
            ->children()
                ->arrayNode('github')
                    ->info('GitHub credentials - used to retrieve pull request data, including webhook statuses. Visit this page to generate a token: https://github.com/settings/tokens/new?scopes=repo&description=jira+cli+tool')
                    ->children()
                        ->scalarNode('apiToken')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('git')
                    ->info('GIT related configurations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('maxBranchNameLength')
                            ->info('Maximum branch name length where the tool starts complaining during automatic branch name generation (-b option for issue transition type commands). Defaults to 30')
                            ->defaultValue(30)->treatNullLike(30)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('tempo')
                    ->info('Tempo timesheets (https://tempo.io/doc/timesheets/api/rest/latest)')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->treatNullLike(false)->end()
                        ->scalarNode('instances')->defaultNull()->example('secondary')->end()
                    ->end()
                ->end()
                ->arrayNode('iterm')
                    ->info('iTerm2 integration (OS X Only)')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('renderImages')->defaultTrue()->treatNullLike(true)->end()
                        ->scalarNode('thumbnailWidth')->defaultValue(300)->treatNullLike(300)->end()
                        ->scalarNode('imageCacheTtl')->defaultValue(5)->treatNullLike(5)->end()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }

    private function projectSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('project');

        $rootNode
            ->info('Project specific settings')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('yesterdayAsWeekday')
                    ->info('Using \'yesterday\' means last workday on monday')
                    ->defaultTrue()
                ->end()
                ->scalarNode('defaultWorklogTimestamp')
                    ->info('Default worklog timestamp to use if date is omitted')
                    ->defaultValue('now')
                ->end()
                ->scalarNode('oneDay')
                    ->info('Your work hours for a single day (valid values ie. "7 hours 30 minutes", 7.5 (treated as hours), 27000 (in seconds)')
                    ->defaultValue(7.5 * 3600)
                ->end()
                ->integerNode('cacheTtl')
                    ->info('keep API data in caches')
                    ->defaultValue(15 * 60)
                ->end()
            ->end();

        return $rootNode;
    }

    private function transitionsSection()
    {
        $treeBuilder = new TreeBuilder;
        $rootNode = $treeBuilder->root('transitions');

        $rootNode
            ->info('Issue transitions registered as commands')
            ->prototype('array')
                ->children()
                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
                    ->variableNode('transition')->beforeNormalization()->ifString()->then(function ($value) {
                        return [$value];
                    })
                ->end()
            ->end();

        return $rootNode;
    }

    private function aliasesSection()
    {
        $treeBuilder = new TreeBuilder;
        $rootNode = $treeBuilder->root('aliases');

        $rootNode
            ->info('Use named issues instead of numbers. Can be used anywhere where issueKey is a command\'s input')
            ->prototype('array')
                ->children()
                    ->scalarNode('alias')->cannotBeEmpty()->isRequired()->end()
                    ->scalarNode('issueKey')->cannotBeEmpty()->isRequired()->end()
                ->end()
            ->end();

        return $rootNode;
    }

    private function filtersSection()
    {
        $treeBuilder = new TreeBuilder;
        $rootNode = $treeBuilder->root('filters');

        $rootNode
            ->info('Custom quick filters registered as commands. See advanced search help at https://confluence.atlassian.com/jiracorecloud/advanced-searching-765593707.html')
            ->prototype('array')
                ->children()
                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
                    ->scalarNode('jql')->cannotBeEmpty()->isRequired()->end()
                ->end()
            ->end();

        return $rootNode;
    }

    private function rendererSection()
    {
        $treeBuilder = new TreeBuilder;
        $rootNode = $treeBuilder->root('renderers');

        $rootNode
            ->info('Rendering setup')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('modes')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')
                                ->cannotBeEmpty()
                                ->isRequired()
                                ->validate()
                                    ->ifString()->then(function ($value) {
                                        return strtolower(strtr($value, [' ' => '-']));
                                    })
                                ->end()
                            ->end()
                            ->booleanNode('inherit')->defaultTrue()->end()
                            ->arrayNode('fields')
                                ->info('see available fields in show:renderers command')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->cannotBeEmpty()->isRequired()->end()
                                        ->scalarNode('formatter')->defaultValue('default')->treatNullLike('default')->end()
                                        ->booleanNode('inline')->defaultFalse()->treatNullLike(false)->end()
                                        ->scalarNode('after')->defaultValue(null)->end()
                                        ->scalarNode('before')->defaultValue(null)->end()
                                        ->booleanNode('remove')->defaultNull()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('formatters')
                    ->info('Custom formatters')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->info('Alias, as it will be used in renderer configs')->cannotBeEmpty()->isRequired()->end()
                            ->scalarNode('class')->info('Full class path with namespace')->cannotBeEmpty()->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }
}
