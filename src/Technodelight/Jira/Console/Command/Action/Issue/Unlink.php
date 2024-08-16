<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\IssueLink\IssueLinkId;

class Unlink extends Command
{
    public function __construct(private readonly Api $api)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('issue:unlink')
            ->setAliases(['unlink'])
            ->setDescription('Remove issue links')
            ->addArgument(
                'linkId',
                InputArgument::REQUIRED,
                'Issue Link ID'
            );
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $linkId = $input->getArgument('linkId');
        $this->api->removeIssueLink(IssueLinkId::fromNumeric($linkId));
        $output->writeln(sprintf('Link <info>%s</info> has been successfully removed.', $linkId));

        return self::SUCCESS;
    }
}
