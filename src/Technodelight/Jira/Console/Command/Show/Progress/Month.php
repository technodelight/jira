<?php

namespace Technodelight\Jira\Console\Command\Show\Progress;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Dashboard;

class Month extends Base
{
    protected function configure()
    {
        $this
            ->setName('show:month')
            ->setDescription('Show your progress for this month')
            ->setAliases(['month', 'monthly-report'])
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Show your worklogs for the given date, could be "last month", "2015-09-28", this month by default',
                'this month'
            )
        ;
        $this->addProgressCommandOptions();
    }

    /**
     * @return string
     */
    protected function defaultRendererType()
    {
        return self::RENDERER_TYPE_SUMMARY;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $this->dateArgument($input);
        $collection = $this->dashboardConsole()->fetch($date, $this->userArgument($input), Dashboard::MODE_MONTHLY);
        $this->rendererForOptions($input->getOptions())->render($output, $collection);
    }
}
