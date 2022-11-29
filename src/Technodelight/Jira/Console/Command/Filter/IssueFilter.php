<?php

namespace Technodelight\Jira\Console\Command\Filter;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Command\IssueRendererAware;
use Technodelight\Jira\Template\IssueRenderer;

class IssueFilter extends Command implements IssueRendererAware
{
    private Api $api;
    private IssueRenderer $issueRenderer;

    /**
     * Constructor.
     *
     * @param string $name The name of the command; passing null means it must be set in configure()
     * @param string $jql JQL Filter to use
     *
     * @throws \LogicException When the command name is empty
     */
    public function __construct(private string $name, private readonly string $jql)
    {
        if (empty($jql)) {
            throw new InvalidArgumentException('JQL is empty');
        }

        $this->name = $this->prepareCommandName($name);

        parent::__construct($this->prepareCommandName($this->name));
    }

    public function setJiraApi(Api $api): void
    {
        $this->api = $api;
    }

    public function setIssueRenderer(IssueRenderer $issueRenderer): void
    {
        $this->issueRenderer = $issueRenderer;
    }

    private function prepareCommandName($name): string
    {
        return sprintf('search:%s', str_replace('search:', '', $name));
    }

    protected function configure(): void
    {
        $this
            ->setDescription($this->descriptionFromJql())
            ->setAliases([$this->name])
            ->addOption(
                'page',
                'p',
                InputOption::VALUE_REQUIRED,
                'Page',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issues = $this->api->search($this->jql, $this->page($input), [Api::FIELDS_ALL, 'comment']);
        if (!$issues->count()) {
            $output->writeln('<info>There seem to be no results matching for your criteria.</info>');
            $output->writeln(sprintf('<fg=black>%s</>', $this->jql));
            return self::FAILURE;
        }

        $this->issueRenderer->renderIssues($output, $issues, $input->getOptions());

        return self::SUCCESS;
    }

    private function descriptionFromJql(): string
    {
        return sprintf(
            'Runs filter: \'%s\'', $this->jql
        );
    }

    private function page(InputInterface $input): ?int
    {
        if (is_numeric($input->getOption('page'))) {
            return ($input->getOption('page') - 1) * 50;
        }

        return null;
    }
}
