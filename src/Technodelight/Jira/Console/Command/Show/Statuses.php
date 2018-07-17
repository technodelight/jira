<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Domain\Status;

class Statuses extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('show:statuses')
            ->setDescription('Show available issue statuses')
            ->addArgument(
                'projectKey',
                InputArgument::OPTIONAL,
                'Project to show statuses for. Can guess project from current feature branch'
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($projectKey = $this->projectKeyResolver()->argument($input)) {
            $this->projectStatuses($output, $projectKey);
        } else {
            $this->genericStatuses($output);
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function genericStatuses(OutputInterface $output)
    {
        $output->writeln($this->displayStatusesGroupedByType($this->jiraApi()->status()));
    }

    private function projectStatuses(OutputInterface $output, $projectKey)
    {
        $byIssueType = $this->jiraApi()->projectStatuses($projectKey);
        foreach ($byIssueType as $issueTyped) {
            $output->writeln($this->formatterHelper()->formatBlock($issueTyped['name'], 'bg=white;fg=black', true));
            $output->writeln($this->tab($this->displayStatusesGroupedByType($issueTyped['statuses'])));
        }
    }

    /**
     * @param Status[] $statuses
     * @return array
     */
    protected function displayStatusesGroupedByType(array $statuses)
    {
        $groupedStatuses = $this->groupStatusesByType($statuses);
        $out = [];
        foreach ($groupedStatuses as $statuses) {
            foreach ($statuses as $idx => $status) {
                /** @var $status Status */
                if ($idx == 0) {
                    $out[] = sprintf(
                        '<fg=%s>  </> <comment>%s</>',
                        $this->colorExtractor()->extractColor($status->statusCategoryColor()) ?: 'black',
                        $status->statusCategory()
                    );
                    $out[] = '';
                }

                $out[] = sprintf(
                    '<comment>%s</> <info>%s</>',
                    $status->name(),
                    $this->getCommandForStatus($status)
                );
                if (!empty($status->description())) {
                    $out[] = $this->tab($this->tab(($status->description())));
                }
            }
        }

        return $out;
    }

    private function groupStatusesByType(array $statuses)
    {
        $groupedStatuses = [];
        foreach ($statuses as $status) {
            if (!isset($groupedStatuses[$status->statusCategory()])) {
                $groupedStatuses[$status->statusCategory()] = [];
            }

            $groupedStatuses[$status->statusCategory()][] = $status;
        }

        return $groupedStatuses;
    }

    private function getCommandForStatus(Status $status)
    {
        try {
            return sprintf('jira %s', $this->config()->transitions()->commandForTransition($status->name()));
        } catch (\Exception $e) {
            return '';
        }
    }

    private function tab($string)
    {
        return $this->templateHelper()->tabulate($string);
    }

    /**
     * @return \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private function config()
    {
        return $this->getService('technodelight.jira.config');
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }

    /**
     * @return \Technodelight\Jira\Helper\TemplateHelper
     */
    private function templateHelper()
    {
        return $this->getService('technodelight.jira.template_helper');
    }

    /**
     * @return \Symfony\Component\Console\Helper\FormatterHelper
     */
    private function formatterHelper()
    {
        return $this->getHelper('formatter');
    }

    /**
     * @return \Technodelight\Jira\Helper\ColorExtractor
     */
    private function colorExtractor()
    {
        return $this->getService('technodelight.jira.color_extractor');
    }

    /**
     * @return \Technodelight\Jira\Console\Argument\ProjectKeyResolver
     */
    private function projectKeyResolver()
    {
        return $this->getService('technodelight.jira.console.argument.project_key_resolver');
    }
}
