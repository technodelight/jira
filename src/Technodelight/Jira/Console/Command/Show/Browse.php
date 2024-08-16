<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Show;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\CliOpen\CliOpen as OpenApp;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Renderer\Issue\Header;

class Browse extends Command
{
    public function __construct(
        private readonly OpenApp $openApp,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly Api $jira,
        private readonly Header $headerRenderer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:browse')
            ->setDescription('Open issue in browser')
            ->setAliases(['browse'])
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issueKey = $this->issueKeyResolver->argument($input, $output);
        try {
            $issue = $this->jira->retrieveIssue($issueKey);
            $this->openIssueLink($output, $issue);

            return self::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln(
                sprintf(
                    'Cannot open <info>%s</info> in browser, reason: %s',
                    $issueKey,
                    sprintf("(%s) %s", get_class($exception), $exception->getMessage())
                )
            );

            return self::FAILURE;
        }
    }

    private function openIssueLink(OutputInterface $output, Issue $issue): void
    {
        $this->renderHeader($output, $issue, false);
        $output->writeln(
            sprintf('Opening jira for <info>%s</info> in browser...', $issue->issueKey())
        );
        $this->openApp->open($issue->url());
    }


    private function renderHeader(OutputInterface $output, Issue $issue): void
    {
        $this->headerRenderer->render($output, $issue);
        $output->writeln('');
    }
}
