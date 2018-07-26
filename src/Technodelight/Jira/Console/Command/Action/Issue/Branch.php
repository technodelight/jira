<?php


namespace Technodelight\Jira\Console\Command\Action\Issue;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\Api as GitShell;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Console\Command\AbstractCommand;
use Technodelight\Jira\Domain\Issue;

class Branch extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('issue:branch')
            ->setDescription('Generate branch name using issue data')
            ->setAliases(['branch'])
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'IssueKey to use for branch name generation'
            )
            ->addOption(
                'local',
                'l',
                InputOption::VALUE_NONE,
                'Choose an existing branch automagically'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        $this->checkoutToBranch($input, $output, $this->jiraApi()->retrieveIssue((string) $issueKey));
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     */
    private function checkoutToBranch(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        $this->checkoutBranch()->checkoutToBranch($input, $output, $issue);
    }

    /**
     * @return \Technodelight\Jira\Helper\CheckoutBranch
     */
    private function checkoutBranch()
    {
        /** @var GitShell $git */
        return $this->getService('technodelight.jira.checkout_branch');
    }

    /**
     * @return \Technodelight\Jira\Api\JiraRestApi\Api
     */
    private function jiraApi()
    {
        return $this->getService('technodelight.jira.api');
    }
}
