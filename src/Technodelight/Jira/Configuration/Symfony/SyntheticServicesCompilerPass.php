<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Technodelight\Jira\Console\Application;

class SyntheticServicesCompilerPass implements CompilerPassInterface
{
    /**
     * @var \Technodelight\Jira\Console\Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->syntheticContainerServices($this->application) as $serviceId => $object) {
            $container->set($serviceId, $object);
        }
    }

    private function syntheticContainerServices(Application $app)
    {
        return [
            'technodelight.jira.app' => $app,
            'console.formatter_helper' => $app->getDefaultHelperSet()->get('formatter'),
            'console.dialog_helper' => $app->getDefaultHelperSet()->get('dialog'),
            'console.question_helper' => $app->getDefaultHelperSet()->get('question'),
        ];
    }
}
