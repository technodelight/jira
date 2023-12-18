<?php

namespace Technodelight\Jira\Console\Command\Show\Progress;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\WorklogFetcher;

class Month extends Base
{
    protected function configure(): void
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

    protected function defaultRendererType(): string
    {
        return self::RENDERER_TYPE_SUMMARY;
    }

    protected function rendererMode(): int
    {
        return WorklogFetcher::MODE_MONTHLY;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $this->dateArgument($input);
        $collection = $this->worklogFetcher()->fetch(
            (string)$date,
            $this->userArgument($input),
            WorklogFetcher::MODE_MONTHLY
        );
        $this->rendererForOptions($input->getOptions())->render($output, $collection);

        return parent::execute($input, $output);
    }
}
