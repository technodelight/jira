<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Board;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Api\SymfonyRgbOutputFormatter\PaletteOutputFormatterStyle;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Domain\IssueCollection;
use Technodelight\Jira\Domain\Priority;
use Technodelight\Jira\Renderer\Issue\RendererContainer;

/**
 * @TODO: refactor rendering logic to include a type for renderers. So then the rendering engine can decide which "main" renderer (IssueRenderer / BoardRenderer) should be used
 * @TODO: configuration renderer type should be "standard" by default.
 * @TODO: mark renderer "bits" with type?
 */
class Renderer
{
    const BLOCK_WIDTH = 30;

    public function __construct(
        private readonly Api $api,
        private readonly RendererContainer $rendererProvider
    ) {
    }

    public function render(OutputInterface $output, IssueCollection $collection): void
    {
        $table = new Table($output);
        $table->setStyle('borderless');

        // collect issues by statuses
        $statuses = [];
        $issuesByStatuses = [];
        foreach ($collection as $issue) {
            $statuses[] = $issue->status()->name();
            if (!isset($issuesByStatuses[$issue->status()->name()])) {
                $issuesByStatuses[$issue->status()->name()] = [];
            }
            $issuesByStatuses[$issue->status()->name()][] = $issue;
        }
        $statuses = array_unique($statuses);

        uasort($statuses, fn($statusOne, $statusTwo) => $statusOne <=> $statusTwo);

        $table->setHeaders($statuses);

        // create columns
        $columns = [];
        foreach ($statuses as $status) {
            $issues = $issuesByStatuses[$status];
            $columns[$status] = [];
            foreach ($issues as $issue) {
                /** @var Issue $issue */
                $priority = $this->api->priority($issue->priority()->id()->id());
                $columnOutput = new BufferedOutput();
                $columnOutput->setDecorated(true);
                foreach ($this->rendererProvider->all() as $renderer) {
                    $renderer->render($columnOutput, $issue);
                }
                $columns[$status][] = $this->wrapOutputInCard($columnOutput, $priority);
            }
        }

        // create rows from columns
        $height = 0;
        foreach ($columns as $status => $renderedIssues) {
            $height = max($height, count($renderedIssues));
        }
        $rows = [];
        for ($i = 0; $i < $height; $i++) {
            $row = [];
            foreach ($statuses as $status) {
                $row[] = $columns[$status][$i] ?? '';
            }
            $rows[] = $row;
        }

        // render table
        $table->setRows($rows);
        $table->render();
    }

    private function wrapOutputInCard(BufferedOutput $columnOutput, Priority $priority): string
    {
        $rows = explode(PHP_EOL, $columnOutput->fetch());
        foreach ($rows as $idx => $row) {
            $rows[$idx] = $this->leftSide($priority)  . '</>'
                . '<fg=black;bg=white>' . $this->pad($columnOutput, $row) . ' </></>';
        }

        return PHP_EOL . join(PHP_EOL, $rows);
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    private function pad(BufferedOutput $output, $string): string
    {
        return $string
            . '<fg=black;bg=white>'
            . str_repeat(
                ' ',
                max(self::BLOCK_WIDTH - strlen(Helper::removeDecoration($output->getFormatter(), $string)), 0)
            );
    }

    private function leftSide(Priority $priority): string
    {
        $style = new PaletteOutputFormatterStyle;
        $style->setForeground($priority->statusColor());
        return $style->apply('┃') . '<fg=black;bg=white> ';
    }
}
