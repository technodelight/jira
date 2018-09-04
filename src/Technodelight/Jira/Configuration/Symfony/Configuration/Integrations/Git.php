<?php

namespace Technodelight\Jira\Configuration\Symfony\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Symfony\Configuration\Configuration;

class Git implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder)->root('git')
            ->info('GIT related configurations')
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('maxBranchNameLength')
                    ->info('Maximum branch name length where the tool starts complaining during automatic branch name generation (-b option for issue transition type commands). Defaults to 30')
                    ->defaultValue(30)->treatNullLike(30)
                ->end()
                ->arrayNode('branchNameGenerator')
                    ->info('Branch name generation settings. By default it conforms to https://nvie.com/posts/a-successful-git-branching-model/')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('patterns')
                            ->useAttributeAsKey('expression')
                            ->prototype('array')
                            ->info('Branch name generation patters, depending on if issue summary matches on regex')
                                ->children()
                                    ->scalarNode('expression')
                                        ->info('Expression in symfony expression language format')
                                        ->example('preg_match("~^Release ~", issue.summary())')
                                        ->isRequired()
                                    ->end()
                                    ->scalarNode('pattern')
                                        ->info('Pattern to use for generation, where {issueKey}, {summary} and any expression like {clean(issue.type()} can be used')
                                        ->example('release/{clean(substr(issue.summary(), 8))}')
                                        ->isRequired()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('separator')->info('Separator to use between words')->defaultValue('-')->end()
                        ->scalarNode('whitelist')->info('Keep this set of characters only when generating branch names')->defaultValue('A-Za-z0-9./-')->end()
                        ->variableNode('remove')
                            ->info('Clean this set of phrases from generated names. Can be an array or a comma separated string. Defaults to "BE,FE"')
                            ->defaultValue(['BE', 'FE'])
                            ->beforeNormalization()
                                ->ifString()->then(function($value) {
                                    return explode(',', $value);
                                })
                            ->end()
                        ->end()
                        ->variableNode('replace')
                            ->info('Always convert of these chars into separator char. Can be an array of values or a single string. Defaults to " :/,"')
                            ->defaultValue([' ', ':', '/', ','])
                            ->beforeNormalization()
                                ->ifString()->then(function($value) {
                                    return str_split($value);
                                })
                            ->end()
                        ->end()
                        ->variableNode('autocompleteWords')
                            ->info('Include these words into autocompleter when shortening branch name due to generated name exceeding max length. Can be an array or a list of words separated by comma.')
                            ->defaultValue(['fix', 'add', 'change', 'remove', 'implement'])
                            ->beforeNormalization()
                                ->ifString()->then(function($value) {
                                    return explode(',', $value);
                                })
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
