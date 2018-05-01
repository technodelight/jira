<?php

namespace Technodelight\Jira\Console\Command\Show\Progress;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Console\Dashboard\Dashboard as ConsoleDashboard;
use Technodelight\Jira\Console\Dashboard\Dashboard;

class Today extends Base
{
    protected function configure()
    {
        $this
            ->setName('show:today')
            ->setDescription('Show your progress for today')
            ->setAliases(['today', 'daily-report'])
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Show your worklogs for the given date, could be "yesterday", "2015-09-28", today by default',
                'today'
            )
        ;
        $this->addProgressCommandOptions();
    }

    /**
     * @return string
     */
    protected function defaultRendererType()
    {
        return self::RENDERER_TYPE_LIST;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $this->dateArgument($input);
        $collection = $this->dashboardConsole()->fetch($date, $this->userArgument($input), Dashboard::MODE_DAILY);
        $this->rendererForOptions($input->getOptions())->render($output, $collection);
    }
}
