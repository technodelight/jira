<?php

namespace Technodelight\Jira\Console\Command\Show\Progress;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Dashboard;

class Week extends Base
{
    protected function configure()
    {
        $this
            ->setName('show:week')
            ->setDescription('Show your progress for this week')
            ->setAliases(['week', 'weekly-report'])
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Show your worklogs for the given date, could be "last week", "2015-09-28", this week by default',
                'this week'
            )
        ;
        $this->addProgressCommandOptions();
    }

    /**
     * @return string
     */
    protected function defaultRendererType()
    {
        return self::RENDERER_TYPE_TABLE;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $this->dateArgument($input);
        $collection = $this->dashboardConsole()->fetch($date, $this->userArgument($input), Dashboard::MODE_WEEKLY);
        $this->rendererForOptions($input->getOptions())->render($output, $collection);
    }
}
