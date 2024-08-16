<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use DateTime;
use SebastianBergmann\Diff\Differ;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\Issue\Changelog as DomainChangelog;
use Technodelight\Jira\Domain\Issue\Changelog\Item;
use Technodelight\JiraTagConverter\JiraTagConverter;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;
use Technodelight\TimeAgo;

class Changelog implements IssueRenderer
{
    /** @SuppressWarnings(PHPMD.BooleanArgumentFlag) */
    public function __construct(
        private readonly Api $api,
        private readonly TemplateHelper $helper,
        private readonly JiraTagConverter $tagConverter,
        private readonly string|bool $timeLimit = false,
        private readonly int|bool $limit = false
    ) {}

    /**
     * @throws \Exception
     */
    public function render(OutputInterface $output, Issue $issue): void
    {
        $changelogs = $this->filterChangelogs($this->api->issueChangelogs($issue->key()));
        if (empty($changelogs)) {
            return;
        }

        $output->writeln($this->tab('<comment>changelogs:</comment>'));
        foreach ($changelogs as $changelog) {
            $output->writeln(
                $this->tab($this->tab(
                    sprintf(
                        '<info>%s</info> <comment>[~%s]</> %s: <fg=black>(%s)</>',
                        $changelog->author()->displayName(),
                        $changelog->author()->name(),
                        $this->ago($changelog->created()),
                        $changelog->created()->format('Y-m-d H:i:s')
                    )
                ))
            );
            foreach ($changelog->items() as $item) {
                $output->writeln($this->tab($this->tab($this->tab(
                    $this->renderChange($output, $item)
                ))));
            }
        }
    }

    private function renderChange(OutputInterface $output, Item $item): string
    {
        if ($item->isMultiLine()) {
            return $this->multilineChange($output, $item);
        }
        return $this->onelineChange($item);
    }

    /**
     * @param OutputInterface $output
     * @param Item $item
     * @return string
     */
    private function multilineChange(OutputInterface $output, Item $item)
    {
        if (empty($item->fromString())) {
            return sprintf(
                '<comment>%s</comment> was set to <fg=green>%s</>',
                $item->field(),
                PHP_EOL . $this->tagConverter->convert($output, $item->toString())
            );
        }

        if (empty($item->toString())) {
            return sprintf(
                '<comment>%s</comment> was removed (was <fg=red>%s</>)',
                $item->field(),
                PHP_EOL . $this->tagConverter->convert($output, $item->fromString())
            );
        }

        $differ = new Differ;

        return sprintf(
            '<comment>%s</comment> was changed: ' . PHP_EOL . '%s',
            $item->field(),
            $this->formatDiff(
                $differ->diff(
                    $this->tagConverter->convert($output, $item->fromString()),
                    $this->tagConverter->convert($output, $item->toString())
                )
            )
        );
    }

    /**
     * @param Item $item
     * @return string
     */
    private function onelineChange(Item $item)
    {
        if (empty($item->fromString())) {
            return sprintf(
                '<comment>%s</comment> was set to <fg=green>%s</>',
                $item->field(),
                $item->toString()
            );

        }
        if (empty($item->toString())) {
            return sprintf(
                '<comment>%s</comment> was removed (was <fg=red>%s</>)',
                $item->field(),
                $item->fromString()
            );
        }

        return sprintf(
            '<comment>%s</comment> was changed from <fg=red>%s</> to <fg=green>%s</>',
            $item->field(),
            $item->fromString(),
            $item->toString()
        );
    }

    private function ago(DateTime $date)
    {
        return TimeAgo::fromDateTime($date)->inWords();
    }

    private function tab($string)
    {
        return $this->helper->tabulate($string);
    }

    private function formatDiff($diff)
    {
        $lines = explode(PHP_EOL, $diff);
        foreach ($lines as $idx => $line) {
            if (substr($line, 0, 1) == '-') {
                $lines[$idx] = '<fg=red>' . $line . '</>';
            } else if (substr($line, 0, 1) == '+') {
                $lines[$idx] = '<fg=green>' . $line . '</>';
            }
        }
        return implode(PHP_EOL, $lines);
    }

    /**
     * @param DomainChangelog[] $changelogs
     * @throws \Exception
     */
    private function filterChangelogs(array $changelogs): array
    {
        if ($this->timeLimit === false) {
            return $changelogs;
        }

        $timeLimit = $this->timeLimit;
        $max = $this->limit;
        $counter = 0;
        return array_filter($changelogs, static function(DomainChangelog $changelog) use ($timeLimit, $counter, $max) {
            $counter++;

            return $max === false
                ? $changelog->created() >= new DateTime($timeLimit)
                : $changelog->created() >= new DateTime($timeLimit) && $counter <= $max;
        });
    }
}
