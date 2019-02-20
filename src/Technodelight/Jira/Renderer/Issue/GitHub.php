<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\HubHelper;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Elements\Badge;
use Technodelight\Jira\Renderer\IssueRenderer;

class GitHub implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\Jira\Helper\HubHelper
     */
    private $hub;
    /**
     * @var int
     */
    private $tabulationLevel = 1;

    public function __construct(TemplateHelper $templateHelper, HubHelper $hub)
    {
        $this->templateHelper = $templateHelper;
        $this->hub = $hub;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($hubIssues = $this->retrieveHubIssues($issue)) {
            $output->writeln($this->tabWithLevel('<comment>pull requests:</comment>'));
            $output->writeln($this->tabWithLevel($this->tab($hubIssues)));
        }
    }

    private function retrieveHubIssues(Issue $issue)
    {
        $matchingIssues = $this->getMatchingIssues($issue);
        $prIds = $this->getHubPrIdsFromIssues($matchingIssues);
        $statuses = [];
        foreach ($prIds as $id) {
            $commits = $this->hub->prCommits($id);
            $last = end($commits);
            $combined = $this->hub->statusCombined($last['sha']);
            $statuses[$id] = [];
            foreach ($combined['statuses'] as $status) {
                $statuses[$id][] = $this->formatCIStatus($status);
            }
        }

        return array_map(
            function ($hubIssue) use ($statuses) {
                return (join(PHP_EOL, array_filter([
                    sprintf(
                        '<info>#%d</> <comment>[%s]</> %s <fg=cyan>(%s)</> <fg=black>(%s) (%s)</>',
                        $hubIssue['number'],
                        $hubIssue['state'],
                        $hubIssue['title'],
                        $hubIssue['user']['login'],
                        $hubIssue['html_url'],
                        (new \DateTime($hubIssue['created_at']))->format('Y-m-d H:i:s')
                    ),
                    join(' ', array_filter([$this->formatLabels($hubIssue), $this->formatMilestone($hubIssue)])),
                    isset($statuses[$hubIssue['number']]) ? join(PHP_EOL, $statuses[$hubIssue['number']]) : ''
                ])));
            },
            $matchingIssues
        );
    }

    /**
     * @param \Technodelight\Jira\Domain\Issue $issue
     * @return array
     */
    private function getMatchingIssues(Issue $issue)
    {
        $issues = $this->hub->issues();
        return array_filter($issues, function ($hubIssue) use ($issue) {
            return strpos($hubIssue['title'], (string) $issue->issueKey()) === 0;
        });
    }

    /**
     * @param array $matchingIssues
     * @return array
     */
    private function getHubPrIdsFromIssues(array $matchingIssues)
    {
        return array_map(
            function ($hubIssue) {
                return $hubIssue['number'];
            },
            $matchingIssues
        );
    }

    private function formatLabels(array $hubIssue)
    {
        if (empty($hubIssue['labels'])) {
            return '';
        }

        $labels = $hubIssue['labels'];
        $badges = [];
        foreach ($labels as $label) {
            $badge = new Badge($label['name'], 'white', '#' . $label['color']);
            $badges[] = (string) $badge;
        }

        return $this->tab(join(' ', $badges));
    }

    private function formatMilestone($hubIssue)
    {
        if (empty($hubIssue['milestone'])) {
            return '';
        }

        $badge = new Badge(sprintf('🏷  %s', $hubIssue['milestone']['title']), 'cyan');
        return '['. $badge . ']';
    }

    private function formatCIStatus(array $status)
    {
        return $this->tab(sprintf(
            '%s  (%s) %s <fg=black>(%s)</>',
            $this->getCIStatusMark($status),
            $status['context'],
            $status['description'],
            $status['target_url']
        ));
    }

    /**
     * @param array $status
     * @return string
     */
    private function getCIStatusMark(array $status)
    {
        switch ($status['state']) {
            case 'success':
                return '✅';
            case 'pending':
                return '⌛';
            case 'failure':
                return '❌';
            default:
                return '❔';
        }
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }

    private function tabWithLevel($string)
    {
        return $this->templateHelper->tabulateWithLevel($string, $this->tabulationLevel);
    }
}
