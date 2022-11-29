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
    public function __construct(
        private readonly ProjectKeyResolver $projectKeyResolver,
        private readonly Api $api,
        private readonly TransitionsConfiguration $transitionsConfiguration,
        private readonly FormatterHelper $formatterHelper,
        private readonly ColorExtractor $colorExtractor,
        private readonly TemplateHelper $templateHelper
    ) {
        parent::__construct();
    }

    protected function configure(): void
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


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($projectKey = $this->projectKeyResolver->argument($input)) {
            $this->projectStatuses($output, $projectKey);
        } else {
            $this->genericStatuses($output);
        }

        return self::SUCCESS;
    }

    private function genericStatuses(OutputInterface $output): void
    {
        $output->writeln($this->displayStatusesGroupedByType($this->api->status()));
    }

    private function projectStatuses(OutputInterface $output, ProjectKey $projectKey): void
    {
        $byIssueType = $this->api->projectStatuses($projectKey);
        foreach ($byIssueType as $issueTyped) {
            $output->writeln($this->formatterHelper->formatBlock($issueTyped['name'], 'bg=white;fg=black', true));
            $output->writeln($this->tab($this->displayStatusesGroupedByType($issueTyped['statuses'])));
        }
    }

    private function displayStatusesGroupedByType(array $statuses): array
    {
        $groupedStatuses = $this->groupStatusesByType($statuses);
        $out = [];
        foreach ($groupedStatuses as $groupedStatus) {
            foreach ($groupedStatus as $idx => $status) {
                /** @var $status Status */
                if ($idx === 0) {
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

    private function groupStatusesByType(array $statuses): array
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

    private function getCommandForStatus(Status $status): string
    {
        try {
            return sprintf('jira %s', $this->transitionsConfiguration->commandForTransition($status->name()));
        } catch (\Exception $e) {
            return '';
        }
    }

    private function tab($string): string
    {
        return $this->templateHelper->tabulate($string);
    }
}
