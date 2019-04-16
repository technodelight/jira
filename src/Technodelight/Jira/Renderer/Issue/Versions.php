<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\ApiInterface as Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\IssueRenderer;

class Versions implements IssueRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\GitShell\ApiInterface
     */
    private $git;

    public function __construct(TemplateHelper $templateHelper, Api $git)
    {
        $this->templateHelper = $templateHelper;
        $this->git = $git;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        if ($versions = $issue->findField('versions')) {
            $this->renderVersions($output, $versions, 'versions');
        }
        if ($fixVersions = $issue->findField('fixVersions')) {
            $this->renderVersions($output, $fixVersions, 'fix versions');
        }
    }

    private function renderVersions(OutputInterface $output, array $versions, $label)
    {
        $git = $this->git;
        $versions = array_map(function(array $version) use ($git) {
            $releaseName = $version['name'];
            if ($branches = $git->branches('release/' . $releaseName)) {
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

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
