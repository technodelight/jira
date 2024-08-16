<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Show\Progress;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Configuration\ApplicationConfiguration\ProjectConfiguration;
use Technodelight\Jira\Console\Argument\Date;
use Technodelight\Jira\Console\Argument\DateResolver;
use Technodelight\Jira\Console\Dashboard\WorklogFetcher;
use Technodelight\Jira\Renderer\DashboardRenderer;

abstract class Base extends Command
{
    protected const RENDERER_TYPE_LIST = 'list';
    protected const RENDERER_TYPE_SUMMARY = 'summary';
    protected const RENDERER_TYPE_TABLE = 'table';

    /** @var DashboardRenderer[] */
    private array $renderers = [];

    public function __construct(
        private readonly WorklogFetcher $worklogFetcher,
        private readonly DateResolver $dateResolver,
        private readonly ProjectConfiguration $projectConfiguration
    ) {
        parent::__construct();
    }

    public function addRenderer($type, DashboardRenderer $renderer): void
    {
        $this->renderers[$type] = $renderer;
    }

    protected function addProgressCommandOptions(): void
    {
        $this
            ->addOption(
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
                'short-list',
                'L',
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

    protected function worklogFetcher(): WorklogFetcher
    {
        return $this->worklogFetcher;
    }

    protected function dateArgument(InputInterface $input): ?Date
    {
        return $this->dateResolver->argument($input);
    }

    protected function userArgument(InputInterface $input): ?string
    {
        $user = $input->getOption('user');
        if (is_string($user)) {
            return $user;
        }
        return null;
    }

    abstract protected function defaultRendererType(): string;

    abstract protected function rendererMode(): int;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $this->dateArgument($input);
        $collection = $this->worklogFetcher()->fetch(
            (string)$date,
            $this->userArgument($input),
            $this->rendererMode()
        );
        $this->rendererForOptions($input->getOptions())->render($output, $collection);

        return $collection->totalTimeSpentSeconds()
            >= ($collection->days() * $this->projectConfiguration->oneDayAmount())
            ? self::SUCCESS
            : self::FAILURE;
    }

    protected function rendererForOptions(array $options): DashboardRenderer
    {
        $renderers = array_keys($this->renderers);
        foreach ($renderers as $rendererType) {
            if (!empty($options[$rendererType])) {
                return $this->rendererFor($rendererType);
            }
        }
        return $this->rendererFor($this->defaultRendererType());
    }

    private function rendererFor($type): DashboardRenderer
    {
        if (isset($this->renderers[$type])) {
            return $this->renderers[$type];
        }

        throw new InvalidArgumentException(
            sprintf('No renderer for %s', $type)
        );
    }
}
