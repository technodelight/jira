<?php

namespace Technodelight\Jira\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Technodelight\GitShell\ApiInterface as GitShell;
use Technodelight\GitShell\Branch;
use Technodelight\Jira\Configuration\ApplicationConfiguration\IntegrationsConfiguration\GitConfiguration;
use Technodelight\Jira\Domain\Issue;

class CheckoutBranch
{
    /**
     * @var \Technodelight\Jira\Configuration\ApplicationConfiguration
     */
    private $config;
    /**
     * @var \Technodelight\GitShell\ApiInterface
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
        GitConfiguration $config,
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
        if ($input->hasOption('local') && true === $input->getOption('local')) {
            $generatedBranchOption = '';
            $branches = $this->localGitBranchesForIssue($issue);
            if (count($branches) == 1) {
                /** @var Branch $localBranchToSelect */
                $localBranchToSelect = reset($branches);
                $branchName = (string) $localBranchToSelect;
            } else {
                $branchName = '';
            }
        } else {
            $generatedBranchOption = $this->generateBranchName($issue) . ' (generated)';
            $branches = $this->gitBranchesForIssue($issue);

            $choiceOptions = $this->branchesAsTextArray($branches);
            $choiceOptions[] = $generatedBranchOption;
            $question = new ChoiceQuestion(
                'Select branch to checkout to',
                $choiceOptions,
                0
            );
            $question->setErrorMessage('Branch %s is invalid.');

            $branchName = $this->questionHelper->ask($input, $output, $question);
        }

        $selectedBranch = '';
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

    /**
     * @param Branch[] $branches
     */
    private function branchesAsTextArray(array $branches)
    {
        return array_map(
            function(Branch $branch) {
                return sprintf('%s (%s)', $branch->name(), $branch->isRemote() ? 'remote' : 'local');
            },
            $branches
        );
    }

    private function gitBranchesForIssue(Issue $issue)
    {
        return $this->git->branches((string)$issue->issueKey());
    }

    private function localGitBranchesForIssue(Issue $issue)
    {
        return array_filter(
            $this->git->branches((string)$issue->issueKey()),
            function (Branch $branch) {
                return !$branch->isRemote();
            }
        );
    }

    private function generateBranchName(Issue $issue)
    {
        return $this->branchnameGenerator->fromIssue($issue);
    }

    private function generateBranchNameWithAutocomplete(Issue $issue, InputInterface $input, OutputInterface $output)
    {
        return $this->branchnameGenerator->fromIssueWithAutocomplete($issue, $input, $output);
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
            $selectedBranch = $this->generateBranchNameWithAutocomplete($issue, $input, $output);
        }

        return $selectedBranch;
    }
}
