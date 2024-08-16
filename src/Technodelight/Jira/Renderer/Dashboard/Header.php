<?php

declare(strict_types=1);

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
            $fromDay = $collection->from();
            $toDay = $collection->to();
            $output->writeln(
                sprintf(
                    "You don't have any issues at the moment, which has work log %s",
                    $fromDay == $toDay ? $this->onDay($fromDay) : $this->inTheseDays($fromDay, $toDay)
                )
            );
        }
    }

    protected function onDay(DateTime $onDay): string
    {
        return strtr('on <info>{onDay}</info>:', ['{onDay}' => $onDay->format('Y-m-d, l')]);
    }

    protected function inTheseDays(DateTime $fromDay, DateTime $toDay): string
    {
        return strtr(
            'from <info>{fromDay}</info> to <info>{toDay}</info>',
            [
                '{fromDay}' => $fromDay->format('Y-m-d l'),
                '{toDay}' => $toDay->format('Y-m-d l')
            ]
        );
    }
}
