<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Configuration\ApplicationConfiguration\TransitionsConfiguration;
use Technodelight\Jira\Console\Argument\ProjectKeyResolver;
use Technodelight\Jira\Domain\Project\ProjectKey;
use Technodelight\Jira\Domain\Status;
use Technodelight\Jira\Helper\ColorExtractor;
use Technodelight\Jira\Helper\TemplateHelper;

class Statuses extends Command
{
    /**
     * @var ProjectKeyResolver
     */
    private $projectKeyResolver;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var TransitionsConfiguration
     */
    private $transitionsConfiguration;
    /**
     * @var FormatterHelper
     */
    private $formatterHelper;
    /**
     * @var ColorExtractor
     */
    private $colorExtractor;
    /**
     * @var TemplateHelper
     */
    private $templateHelper;

    public function __construct(
        ProjectKeyResolver $projectKeyResolver,
        Api $api,
        TransitionsConfiguration $transitionsConfiguration,
        FormatterHelper $formatterHelper,
        ColorExtractor $colorExtractor,
        TemplateHelper $templateHelper
    )
    {
        $this->projectKeyResolver = $projectKeyResolver;
        $this->api = $api;
        $this->transitionsConfiguration = $transitionsConfiguration;
        $this->formatterHelper = $formatterHelper;
        $this->colorExtractor = $colorExtractor;
        $this->templateHelper = $templateHelper;

        parent::__construct();
    }

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
        if ($projectKey = $this->projectKeyResolver->argument($input)) {
            $this->projectStatuses($output, $projectKey);
        } else {
            $this->genericStatuses($output);
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function genericStatuses(OutputInterface $output)
    {
        $output->writeln($this->displayStatusesGroupedByType($this->api->status()));
    }

    private function projectStatuses(OutputInterface $output, ProjectKey $projectKey)
    {
        $byIssueType = $this->api->projectStatuses($projectKey);
        foreach ($byIssueType as $issueTyped) {
            $output->writeln($this->formatterHelper->formatBlock($issueTyped['name'], 'bg=white;fg=black', true));
            $output->writeln($this->tab($this->displayStatusesGroupedByType($issueTyped['statuses'])));
        }
    }

    /**
     * @param Status[] $statuses
     * @return array
     */
    private function displayStatusesGroupedByType(array $statuses)
    {
        $groupedStatuses = $this->groupStatusesByType($statuses);
        $out = [];
        foreach ($groupedStatuses as $statuses) {
            foreach ($statuses as $idx => $status) {
                /** @var $status Status */
                if ($idx == 0) {
                    $out[] = sprintf(
                        '<fg=%s>  </> <comment>%s</>',
                        $this->colorExtractor->extractColor($status->statusCategoryColor()) ?: 'black',
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
            return sprintf('jira %s', $this->transitionsConfiguration->commandForTransition($status->name()));
        } catch (\Exception $e) {
            return '';
        }
    }

    private function tab($string)
    {
        return $this->templateHelper->tabulate($string);
    }
}
