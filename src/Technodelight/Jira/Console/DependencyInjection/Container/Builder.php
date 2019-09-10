<?php

namespace Technodelight\Jira\Console\DependencyInjection\Container;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Technodelight\Jira\Console\DependencyInjection\CompilerPass\ApplicationConfiguration;
use Technodelight\Jira\Console\DependencyInjection\CompilerPass\CommandInitialisation;
use Technodelight\Jira\Console\DependencyInjection\CompilerPass\CommandRegistration;
use Technodelight\Jira\Console\DependencyInjection\CompilerPass\Extensions;
use Technodelight\Jira\Console\DependencyInjection\CompilerPass\IssueRendererOptions;
use Technodelight\Jira\Console\DependencyInjection\CompilerPass\RendererProvider;

class Builder
{
    /**
     * @return ContainerBuilder
     * @throws Exception
     */
    public function build(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(sprintf('%s/src/Technodelight/Jira/Resources/configs', APPLICATION_ROOT_DIR))
        );
        $loader->load('services.xml');
        // add compiler passes
        $container->addCompilerPass(new ApplicationConfiguration);
        $container->addCompilerPass(new RendererProvider);
        $container->addCompilerPass(new CommandInitialisation);
        $container->addCompilerPass(new IssueRendererOptions);
        $container->addCompilerPass(new CommandRegistration);
        $container->addCompilerPass(new Extensions);

        return $container;
    }
}
