<?php

namespace Technodelight\Jira\Console\Command\Show\Progress;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\WorklogFetcher;

class Week extends Base
{
    protected function configure(): void
    {
        $this
            ->setName('show:week')
            ->setDescription('Show your progress for this week')
            ->setAliases(['week', 'weekly-report'])
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Show your worklogs for the given date, could be "last week", "2015-09-28", this week by default',
                'this week',
                ['this week', 'last week']
            )
        ;
        $this->addProgressCommandOptions();
    }

    protected function defaultRendererType(): string
    {
        return self::RENDERER_TYPE_TABLE;
    }

    protected function rendererMode(): int
    {
        return WorklogFetcher::MODE_WEEKLY;
    }
}
