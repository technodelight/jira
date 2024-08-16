<?php

declare(strict_types=1);

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\ApiInterface as Git;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class Versions implements IssueRenderer
{
    public function __construct(
        private readonly TemplateHelper $templateHelper,
        private readonly Git $git
    ) {
    }

    public function render(OutputInterface $output, Issue $issue): void
    {
        $versions = $issue->findField('versions');
        $fixVersions = $issue->findField('fixVersions');
        if (!empty($versions)) {
            $this->renderVersions($output, $versions, 'versions');
        }
        if (!empty($fixVersions)) {
            $this->renderVersions($output, $fixVersions, 'fix versions');
        }
    }

    private function renderVersions(OutputInterface $output, array $versions, string $label): void
    {
        $versions = array_map(function(array $version) {
            $releaseName = $version['name'];
            $branches = $this->git->branches('release/' . $releaseName);
            if (!empty($branches)) {
                $branch = reset($branches);
                return sprintf('%s (%s)', $releaseName, $branch->name());
            }
            return $releaseName;
        }, $versions);

        $output->writeln(
            $this->tab(
                sprintf('<comment>%s:</comment> %s', $label, join(',', $versions))
            )
        );
    }

    private function tab($string): string
    {
        return $this->templateHelper->tabulate($string);
    }
}
