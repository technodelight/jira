<?php


namespace Technodelight\Jira\Console\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\GitShell\Api as GitShell;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\Issue;

class BranchCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('branch')
            ->setDescription('Generate branch name using issue data')
            ->addArgument(
                IssueKeyResolver::ARGUMENT,
                InputArgument::OPTIONAL,
                'IssueKey to use for branch name generation'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $issueKey = $this->issueKeyArgument($input, $output);
        $this->checkoutToBranch($input, $output, $this->jiraApi()->retrieveIssue($issueKey));
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
