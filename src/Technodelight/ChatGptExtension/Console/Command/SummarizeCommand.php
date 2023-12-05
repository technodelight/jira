<?php

declare(strict_types=1);

namespace Technodelight\ChatGptExtension\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\ChatGptExtension\Api\Api;
use Technodelight\Jira\Api\JiraRestApi\Api as Jira;
use Technodelight\Jira\Console\Argument\IssueKeyAutocomplete;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Helper\TemplateHelper;
use Technodelight\Jira\Renderer\Issue\Header;
use Technodelight\Jira\Renderer\Issue\HeaderWithSummary;

class SummarizeCommand extends Command
{
    public function __construct(
        private readonly Api $api,
        private readonly Jira $jira,
        private readonly IssueKeyAutocomplete $autocomplete,
        private readonly Header $renderer,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly TemplateHelper $templateHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('chatgpt:summarize')
            ->setAliases(['summarize'])
            ->setDescription('Summarize an issue using chatGPT')
            ->addArgument(
                'issueKey',
                InputArgument::OPTIONAL,
                'Issue key (ie. PROJ-123), defaults to current issue, taken from branch name',
                null,
                fn(CompletionInput $completionInput)
                => $this->autocomplete->autocomplete($completionInput->getCompletionValue())
            )
            ->addOption(
                'with-comments',
                null,
                InputOption::VALUE_NONE,
                'Summarize comments as well. Might burn your token allowance, be aware.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $issue = $this->jira->retrieveIssue($this->issueKeyResolver->argument($input, $output));
        $summary = $this->api->summarize($issue);

        $this->renderer->render($output, $issue);
        $output->writeln('<info>that\'s this thing all about:</info>');
        $output->writeln($this->templateHelper->tabulate($summary));
        if ($input->getOption('with-comments') && !empty($issue->comments())) {
            $output->writeln('<info>here\'s the discussion in the comments:</info>');
            $comments = $this->api->summarizeComments($issue);
            $output->writeln($this->templateHelper->tabulate($comments));
        }
        return self::SUCCESS;
    }
}
