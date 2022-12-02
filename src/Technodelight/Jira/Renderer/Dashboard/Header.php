<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use DateTime;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\DashboardCollection;
use Technodelight\Jira\Renderer\DashboardRenderer;

class Header implements DashboardRenderer
{
    public function render(OutputInterface $output, DashboardCollection $collection): void
    {
        if (!$collection->count()) {
            $from = $collection->from();
            $to = $collection->to();
            $output->writeln(
                sprintf(
                    "You don't have any issues at the moment, which has worklog %s",
                    $from == $to ? $this->onDay($from) : $this->inTheseDays($from, $to)
                )
            );
        }
    }

    /**
     * @param DateTime $from
     * @return string
     */
    protected function onDay(DateTime $from)
    {
        return sprintf('on <info>%s</info>:', $from->format('Y-m-d, l'));
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return string
     */
    protected function inTheseDays(DateTime $from, DateTime $to)
    {
        return sprintf(
            'from <info>%s</info> to <info>%s</info>',
            $from->format('Y-m-d l'),
            $to->format('Y-m-d l')
        );
    }
}
