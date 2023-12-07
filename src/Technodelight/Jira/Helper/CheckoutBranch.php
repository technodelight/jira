<?php

declare(strict_types=1);

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
    public function __construct(
        private readonly GitConfiguration $config,
        private readonly GitShell $git,
        private readonly GitBranchnameGenerator $branchnameGenerator,
        private readonly QuestionHelper $questionHelper
    ) {
    }

    public function checkoutToBranch(InputInterface $input, OutputInterface $output, Issue $issue): void
    {
        if (!$this->gitBranchesForIssue($issue)) {
            $branchName = $this->getProperBranchName($input, $output, $issue);
            $output->writeln('Checking out to new branch: ' . $branchName);
            $this->git->createBranch($branchName);
        } else {
            $this->chooseBranch($input, $output, $issue);
        }
    }

    private function chooseBranch(InputInterface $input, OutputInterface $output, Issue $issue): void
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

    private function branchesAsTextArray(array $branches): array
    {
        return array_map(
            fn(Branch $branch) => sprintf('%s (%s)', $branch->name(), $branch->isRemote() ? 'remote' : 'local'),
            $branches
        );
    }

    private function gitBranchesForIssue(Issue $issue): array
    {
        return $this->git->branches((string)$issue->issueKey());
    }

    private function localGitBranchesForIssue(Issue $issue): array
    {
        return array_filter(
            $this->git->branches((string)$issue->issueKey()),
            function (Branch $branch) {
                return !$branch->isRemote();
            }
        );
    }

    private function generateBranchName(Issue $issue): string
    {
        return $this->branchnameGenerator->fromIssue($issue);
    }

    private function generateBranchNameWithAutocomplete(
        Issue $issue,
        InputInterface $input,
        OutputInterface $output
    ): string {
        return $this->branchnameGenerator->fromIssueWithAutocomplete($issue, $input, $output);
    }

    private function isShorteningBranchNameConfirmed(
        InputInterface $input,
        OutputInterface $output,
        string $branchName
    ): bool {
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

    private function getProperBranchName(InputInterface $input, OutputInterface $output, Issue $issue): string
    {
        $selectedBranch = $this->generateBranchName($issue);
        if ((strlen($selectedBranch) > $this->config->maxBranchNameLength())
            && $this->isShorteningBranchNameConfirmed($input, $output, $selectedBranch)) {
            $selectedBranch = $this->generateBranchNameWithAutocomplete($issue, $input, $output);
        }

        return $selectedBranch;
    }
}
