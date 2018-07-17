<?php

namespace Technodelight\Jira\Renderer\Dashboard;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Dashboard\Collection;
use Technodelight\Jira\Helper\DateHelper;
use Technodelight\Jira\Helper\PluralizeHelper;
use Technodelight\Jira\Renderer\DashboardRenderer;

class Progress implements DashboardRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\DateHelper
     */
    private $dateHelper;
    /**
     * @var \Technodelight\Jira\Helper\PluralizeHelper
     */
    private $pluralizeHelper;

    public function __construct(DateHelper $dateHelper, PluralizeHelper $pluralizeHelper)
    {
        $this->dateHelper = $dateHelper;
        $this->pluralizeHelper = $pluralizeHelper;
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
                'You have been working on %d %s %s' . PHP_EOL,
                $collection->issuesCount(),
                $this->pluralizeHelper->pluralize('issue', $collection->issuesCount()),
                $this->dateRange($collection)
            )
        );
        $progress = $this->createProgressbar($output, $totalTimeInRange);
        $progress->setProgress($summary);
        $progress->display();
        $output->writeln('');
    }

    private function createProgressbar(OutputInterface $output, $steps)
    {
        // render progress bar
        $progress = new ProgressBar($output, $steps);
        $progress->setFormat('%bar% %percent%%');
        $progress->setBarCharacter('<bg=green> </>');
        $progress->setEmptyBarCharacter('<bg=white> </>');
        $progress->setProgressCharacter('<bg=green> </>');
        $progress->setBarWidth(50);
        return $progress;
    }

    private function dateRange(Collection $collection)
    {
        if ($collection->from() == $collection->to()) {
            return sprintf(
                'on %s',
                $collection->from()->format('Y-m-d l')
            );
        } else {
            return sprintf(
                'from %s to %s',
                $collection->from()->format('Y-m-d l'),
                $collection->to()->format('Y-m-d l')
            );
        }
    }
}
