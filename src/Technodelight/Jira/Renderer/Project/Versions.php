<?php

namespace Technodelight\Jira\Renderer\Project;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Domain\Project\Version;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\ProjectRenderer;

class Versions implements ProjectRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Project $project): void
    {
        if ($versions = $project->versions()) {
            $this->renderVersions($output, $versions);
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Version[] $versions
     */
    private function renderVersions(OutputInterface $output, array $versions)
    {
        $output->writeln($this->tab('<comment>versions:</comment>'));
        foreach ($versions as $version) {
            $output->writeln($this->tab($this->tab($this->header($version))));
            if ($version->description()) {
                $output->writeln($this->tab($this->tab($this->tab($this->body($version)))));
            }
        }
    }

    private function header(Version $version)
    {
        return sprintf(
            '<info>%s</info> (%s)',
            $version->name(),
            $version->isReleased()
                ? $version->releaseDate()->format('Y-m-d')
                : 'planned'
        );
    }

    private function body(Version $version)
    {
        return wordwrap($version->description());
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
