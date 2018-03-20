<?php

namespace Technodelight\Jira\Console\Command\Action\Issue;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Console\Command\AbstractCommand;

class Unlink extends AbstractCommand
{
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
        $this->jiraApi()->removeIssueLink($linkId);
        $output->writeln(sprintf('Link <info>%s</info> has been successfully removed.', $linkId));
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }
}
