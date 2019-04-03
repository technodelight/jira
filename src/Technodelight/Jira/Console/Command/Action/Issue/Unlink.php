<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Domain\IssueLink\IssueLinkId;

class Unlink extends Command
{
    /**
     * @var Api
     */
    private $api;

    public function __construct(Api $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    protected function configure()
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $linkId = $input->getArgument('linkId');
        $this->api->removeIssueLink(IssueLinkId::fromString($linkId));
        $output->writeln(sprintf('Link <info>%s</info> has been successfully removed.', $linkId));
    }
}
