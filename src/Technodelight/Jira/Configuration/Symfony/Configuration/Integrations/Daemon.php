<?php

namespace Technodelight\Jira\Configuration\Symfony\Configuration\Integrations;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Technodelight\Jira\Configuration\Symfony\Configuration\Configuration;

class Daemon implements Configuration
{
    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    public function configurations()
    {
        return (new TreeBuilder)->root('daemon')
            ->info('Use application in server/client mode. Could speed things up a bit')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->scalarNode('address')
                    ->info('IP to listen on. Defaults to 0.0.0.0')
                    ->validate()
                        ->ifString()->then(function ($ip) {
                            if (ip2long($ip) === false) {
                                throw new \InvalidArgumentException(
                                    sprintf('Provided IP for daemon %s is not valid', $ip)
                                );
                            }
                        })
                    ->end()
                    ->defaultValue('0.0.0.0')
                ->end()
                ->scalarNode('port')
                    ->info('Port to listen on. Defaults to 50200.')
                    ->validate()
                        ->ifString()->then(function ($port) {
                            if ($port > 65535 || $port < 444) {
                                throw new \InvalidArgumentException(
                                    sprintf('Provided port for daemon %s is not valid (should be between 444 - 65535)', $port)
                                );
                            }
                        })
                    ->end()
                    ->defaultValue('50200')
                ->end()
            ->end()
        ;
    }
}
