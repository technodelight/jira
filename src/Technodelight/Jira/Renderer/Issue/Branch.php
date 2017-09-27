<?php

namespace Technodelight\Jira\Renderer\Issue;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\GitShell\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\GitBranchnameGenerator;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Renderer;

class Branch implements Renderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;
    /**
     * @var \Technodelight\Jira\Api\GitShell\Api
     */
    private $git;
    /**
     * @var \Technodelight\Jira\Helper\GitBranchnameGenerator
     */
    private $gitBranchnameGenerator;

    public function __construct(TemplateHelper $templateHelper, Api $git, GitBranchnameGenerator $gitBranchnameGenerator)
    {
        $this->templateHelper = $templateHelper;
        $this->git = $git;
        $this->gitBranchnameGenerator = $gitBranchnameGenerator;
    }

    public function render(OutputInterface $output, Issue $issue)
    {
        $output->writeln($this->tab('<comment>branches:</comment>'));
        $output->writeln(
            $this->tab($this->tab($this->getBranches($issue)))
        );
    }

    private function getBranches(Issue $issue)
    {
        $branches = $this->git->branches($issue->ticketNumber());
        if (empty($branches)) {
            return [$this->gitBranchnameGenerator->fromIssue($issue) . ' (generated)'];
        } else {
            return array_unique(
                array_map(
                    function (array $branchData) {
                        return sprintf('%s (%s)', $branchData['name'], $branchData['remote'] ? 'remote' : 'local');
                    },
                    $branches
                )
            );
        }
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
