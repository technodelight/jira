<?php

namespace Technodelight\Jira\Renderer\Project;

use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Domain\Project;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\ProjectRenderer;

class Description implements ProjectRenderer
{
    /**
     * @var \Technodelight\Jira\Helper\TemplateHelper
     */
    private $templateHelper;

    public function __construct(TemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function render(OutputInterface $output, Project $project)
    {
        if ($description = $project->description()) {
            $output->writeln($this->tab(wordwrap(strip_tags($description))));
        }
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
