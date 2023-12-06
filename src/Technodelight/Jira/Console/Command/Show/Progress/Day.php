<?php

namespace Technodelight\Jira\Console\Command\Show\Progress;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Dashboard;

class Day extends Base
{
    protected function configure(): void
    {
        $this
            ->setName('show:day')
            ->setDescription('Show your progress for a single day')
            ->setAliases(['day', 'daily-report'])
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Show your worklogs for the given date, could be "yesterday", "2015-09-28", "-1 week", etc.'
                . ' Set to "today" by default, which default can be configured',
                'today',
                ['yesterday', 'today']
            )
        ;
        $this->addProgressCommandOptions();
    }

    protected function defaultRendererType(): string
    {
        return self::RENDERER_TYPE_LIST;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $this->dateArgument($input);
        $collection = $this->dashboardConsole()->fetch($date, $this->userArgument($input), Dashboard::MODE_DAILY);
        $this->rendererForOptions($input->getOptions())->render($output, $collection);

        return self::SUCCESS;
    }
}
