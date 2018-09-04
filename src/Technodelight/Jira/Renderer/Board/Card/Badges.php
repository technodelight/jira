<?php

namespace Technodelight\Jira\Renderer\Board\Card;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;

class Badges extends Base
{
    public function render(OutputInterface $output, Issue $issue)
    {
        $versions = array_map(
            function($version) {
                return $version['name'];
            },
            $issue->findField('fixVersions')
        );
        $labels = $issue->findField('labels');

        $badges = array_filter($this->renderBadges($versions, $labels));
        if (!empty($badges)) {
            $output->writeln($badges);
        }
    }

    private function renderBadges(array $versions, array $labels)
    {
        $rows = [];
        foreach ($versions as $version) {
            $rows[] = RenderableBadge::fromStringAndType($version, RenderableBadge::VERSION);
        }
        foreach ($labels as $label) {
            $rows[] = RenderableBadge::fromStringAndType($label, RenderableBadge::LABEL);
        }
        $rows = [$rows];

        for($idx = 0; $idx < count($rows); $idx++) {
            /** @var RenderableBadge[] $row */
            $row = $rows[$idx];

            // collect everything which should be pushed to next row
            $nextRow = [];
            if ($this->totalLengthOfStringsInArray($row) > self::BLOCK_WIDTH) {
                while($this->totalLengthOfStringsInArray($row) > self::BLOCK_WIDTH && count($row) > 1) {
                    $nextRow[] = array_pop($row);
                }
            }
            if (!empty($nextRow)) {
                $rows[$idx + 1] = array_merge(isset($rows[$idx + 1]) ? $rows[$idx + 1] : [], $nextRow);
            }

            // check how the row should be rendered, ie. if it overflows the max width
            if($this->totalLengthOfStringsInArray($row) > self::BLOCK_WIDTH && count($row) == 1) {
                /** @var RenderableBadge $badge */
                $badge = array_shift($row);
                $rows[$idx] = $this->formatBadge($this->wordwrap->shorten(array_shift($row)), $badge->style());
            } else {
                $rows[$idx] = array_map(function(RenderableBadge $badge) use ($versions) {
                    return $this->formatBadge($badge, $badge->style());
                }, $row);
            }

            $rows[$idx] = join('<bg=white> </>', $rows[$idx]);
        }

        return $rows;
    }

    private function totalLengthOfStringsInArray($texts)
    {
        return array_sum(array_map('strlen', $texts)) + count($texts) - 1;
    }

    private function formatBadge($text, $colorDef)
    {
        return sprintf('<%s>%s</>', $colorDef, $text);
    }
}
