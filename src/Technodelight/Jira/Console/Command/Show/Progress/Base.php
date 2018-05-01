<?php

namespace Technodelight\Jira\Console\Command\Show\Progress;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Technodelight\Jira\Console\Argument\DateResolver;
use Technodelight\Jira\Console\Dashboard\Dashboard;
use Technodelight\Jira\Renderer\DashboardRenderer;

abstract class Base extends Command
{
    const RENDERER_TYPE_LIST = 'list';
    const RENDERER_TYPE_SUMMARY = 'summary';
    const RENDERER_TYPE_TABLE = 'table';

    /**
     * @var Dashboard
     */
    private $dashboardConsole;
    /**
     * @var DateResolver
     */
    private $dateResolver;
    /**
     * @var DashboardRenderer[]
     */
    private $renderers = [];

    public function setDashboardConsole(Dashboard $dashboardConsole)
    {
        $this->dashboardConsole = $dashboardConsole;
    }

    public function setDateArgumentResolver(DateResolver $dateResolver)
    {
        $this->dateResolver = $dateResolver;
    }

    public function addRenderer($type, DashboardRenderer $renderer)
    {
        $this->renderers[$type] = $renderer;
    }

    protected function addProgressCommandOptions()
    {
        $this->addOption(
            'list',
            'l',
            InputOption::VALUE_NONE,
            'Render dashboard as a list'
        )
        ->addOption(
            'summary',
            's',
            InputOption::VALUE_NONE,
            'Render summary only'
        )
        ->addOption(
            'table',
            't',
            InputOption::VALUE_NONE,
            'Render dashboard as table'
        )
        ->addOption(
            'user',
            'u',
            InputOption::VALUE_REQUIRED,
            'Fetch worklogs for specified user. This is you by default'
        )
        ;
    }

    /**
     * @return Dashboard
     */
    protected function dashboardConsole()
    {
        return $this->dashboardConsole;
    }

    protected function dateArgument(InputInterface $input)
    {
        return $this->dateResolver->argument($input);
    }

    protected function userArgument(InputInterface $input)
    {
        if ($input->getOption('user')) {
            return $input->getOption('user');
        }
        return null;
    }

    /**
     * @return string
     */
    abstract protected function defaultRendererType();

    /**
     * @param InputOption[] $options
     * @return DashboardRenderer
     */
    protected function rendererForOptions(array $options)
    {
        $renderers = ['list', 'summary', 'table'];
        foreach ($renderers as $rendererType) {
            if (!empty($options[$rendererType])) {
                return $this->rendererFor($rendererType);
            }
        }
        return $this->rendererFor($this->defaultRendererType());
    }

    /**
     * @param string $type
     * @return DashboardRenderer
     */
    private function rendererFor($type)
    {
        if (isset($this->renderers[$type])) {
            return $this->renderers[$type];
        }

        throw new RuntimeException(
            sprintf('No renderer for %s', $type)
        );
    }
}
