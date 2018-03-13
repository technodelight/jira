<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Renderer\DashboardRenderer;

class Stats implements DashboardRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\DateHelper
     */
    private $dateHelper;

    public function __construct(DateHelper $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    public function render(OutputInterface $output, Collection $collection)
    {
        if (!$collection->count()) {
            return;
        }

        $totalTimeInRange = $this->dateHelper->humanToSeconds(sprintf('%dd', $collection->days()));
        $summary = $collection->totalTimeSpentSeconds();
        $output->writeln(
            sprintf(
                'Total time logged: %s of %s (%0.2f%%, %s)',
                $this->dateHelper->secondsToHuman($summary),
                sprintf('%dd', $collection->days()),
                ($summary / $totalTimeInRange) * 100, // percentage
                $this->missingTimeText($totalTimeInRange - $summary)
            )
        );
        if ($collection->days() > 1) {
            $output->writeln(
                sprintf(
                    '%0.2f issues per day, %0.2f worklogs per day, average time spent %s',
                    $collection->issuesCount() / $collection->days(),
                    $collection->count() / $collection->days(),
                    $this->dateHelper->secondsToHuman(ceil($collection->totalTimeSpentSeconds() / $collection->count()))
                )
            );
        }
        $output->writeln('');
    }

    private function missingTimeText($missingTime)
    {
        if ($missingTime >= 0) {
            return sprintf(
                '%s missing',
                $this->dateHelper->secondsToHuman($missingTime)
            );
        }
        return sprintf(
            '<bg=red>%s overtime</>',
            $this->dateHelper->secondsToHuman(abs($missingTime))
        );
    }
}
