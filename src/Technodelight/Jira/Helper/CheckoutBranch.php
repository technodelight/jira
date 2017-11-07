<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\Jira\Api\GitShell\Api as GitShell;
use Technodelight\Jira\Api\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration;
use Technodelight\Jira\Domain\Issue;

class CheckoutBranch
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private $config;
    /**
     * @var \Technodelight\Jira\Api\GitShell\Api
     */
    private $git;
    /**
     * @var \Technodelight\Jira\Helper\GitBranchnameGenerator
     */
    private $branchnameGenerator;
    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    private $questionHelper;

    public function __construct(
        ApplicationConfiguration $config,
        GitShell $git,
        GitBranchnameGenerator $branchnameGenerator,
        QuestionHelper $questionHelper
    )
    {
        $this->config = $config;
        $this->git = $git;
        $this->branchnameGenerator = $branchnameGenerator;
        $this->questionHelper = $questionHelper;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     */
    public function checkoutToBranch(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        if (!$this->gitBranchesForIssue($issue)) {
            $branchName = $this->getProperBranchName($input, $output, $issue);
            $output->writeln('Checking out to new branch: ' . $branchName);
            $this->git->createBranch($branchName);
        } else {
            $this->chooseBranch($input, $output, $issue);
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     * @return void
     * @throws \LogicException if can't select branch
     */
    private function chooseBranch(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        $generatedBranchOption = $this->generateBranchName($issue) . ' (generated)';
        $question = new ChoiceQuestion(
            'Select branch to checkout to',
            array_merge($this->gitBranchesForIssue($issue), [$this->generateBranchName($issue) . ' (generated)']),
            0
        );
        $question->setErrorMessage('Branch %s is invalid.');

        $branchName = $this->questionHelper->ask($input, $output, $question);

        $selectedBranch = '';
        $branches = $this->git->branches($issue->issueKey());
        $new = false;
        foreach ($branches as $branch) {
            /** @var Branch $branch */
            if ($branchName == (string) $branch) {
                $selectedBranch = $branch->name();
                break;
            }
        }
        if (!$selectedBranch && ($branchName == $generatedBranchOption)) {
            $selectedBranch = $this->getProperBranchName($input, $output, $issue);
            $new = true;
        }
        if (!$selectedBranch) {
            throw new \LogicException(sprintf('Cannot select branch %s', $branchName));
        }

        if ($new) {
            $output->writeln('Checking out to new branch: ' . $selectedBranch);
            $this->git->createBranch($selectedBranch);
        } else {
            $output->writeln('Checking out to: ' . $selectedBranch);
            $this->git->switchBranch($selectedBranch);
        }
    }

    private function gitBranchesForIssue(Issue $issue)
    {
        return array_map(
            function(Branch $branch) {
                return sprintf('%s (%s)', $branch->name(), $branch->isRemote() ? 'remote' : 'local');
            },
            $this->git->branches($issue->ticketNumber())
        );
    }

    private function generateBranchName(Issue $issue)
    {
        return $this->branchnameGenerator->fromIssue($issue);
    }

    private function generateBranchNameWithAutocomplete(Issue $issue)
    {
        return $this->branchnameGenerator->fromIssueWithAutocomplete($issue);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $branchName
     * return bool
     */
    private function isShorteningBranchNameConfirmed(InputInterface $input, OutputInterface $output, $branchName)
    {
        return $this->questionHelper->ask(
            $input,
            $output,
            new ConfirmationQuestion(
                'The generated branch name seems to be too long. Do you want to shorten it?' . PHP_EOL
                . '(' . $branchName . ')' . PHP_EOL
                . '[Y/n] ? ',
                true
            )
        );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param Issue $issue
     * @return string
     */
    private function getProperBranchName(InputInterface $input, OutputInterface $output, Issue $issue)
    {
        $selectedBranch = $this->generateBranchName($issue);
        if ((strlen($selectedBranch) > $this->config->maxBranchNameLength())
            && $this->isShorteningBranchNameConfirmed($input, $output, $selectedBranch)) {
            $selectedBranch = $this->generateBranchNameWithAutocomplete($issue);
        }

        return $selectedBranch;
    }
}
