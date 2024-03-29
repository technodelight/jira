<?php

namespace Technodelight\Jira\Console\Argument;

use BadMethodCallException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\GitShell\ApiInterface as Git;
use Technodelight\Jira\Console\Argument\IssueKeyResolver\Guesser;
use Technodelight\Jira\Domain\Issue\IssueKey;
use UnexpectedValueException;

class IssueKeyResolver
{
    public const ARGUMENT = 'issueKey';
    public const OPTION = 'issueKey';

    private Git $git;
    private Guesser $guesser;
    private InteractiveIssueSelector  $issueSelector;

    public function __construct(Git $git, Guesser $guesser, InteractiveIssueSelector $issueSelector)
    {
        $this->git = $git;
        $this->guesser = $guesser;
        $this->issueSelector = $issueSelector;
    }

    public function argument(InputInterface $input, OutputInterface $output, $strict = true): IssueKey
    {
        if (!$input->hasArgument(self::ARGUMENT)) {
            throw new BadMethodCallException('There\'s no issueKey argument specified in the input definition');
        }
        $issueKey = $this->resolve($input->getArgument(self::ARGUMENT), $input, $output);

        if (!empty($issueKey)) {
            $shift = false;
            foreach ($input->getArguments() as $argument => $value) {
                //@TODO: something stopped working here
                if ($argument === self::ARGUMENT && !$this->isArgValueAnIssueKey($value, $issueKey)) {
                    $shift = true;
                    $previousArgumentValue = $input->getArgument(self::ARGUMENT);
                    $input->setArgument($argument, (string) $issueKey);
                } else if ($shift && isset($previousArgumentValue)) {
                    $input->setArgument($argument, $previousArgumentValue);
                    $previousArgumentValue = $value;
                    $shift = false;
                }
            }
        }

        if (null === $issueKey && $strict === true) {
            throw new UnexpectedValueException(
                ':\'( Cannot figure out issueKey argument, please specify explicitly'
            );
        }

        return $issueKey;
    }

    public function option(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasOption(self::OPTION)) {
            return null;
        }
        $value = $this->resolve($input->getOption(self::OPTION), $input, $output);
        $input->setOption(self::OPTION, (string) $value);
        return $value;
    }

    private function resolve($argumentOrOption, InputInterface $input, OutputInterface $output)
    {
        if ($key = $this->guesser->guessIssueKey($argumentOrOption, $this->git->currentBranch())) {
            return $key;
        }

        return $this->guesser->guessIssueKey($this->issueSelector->chooseIssue($input, $output)->key());
    }

    private function isArgValueAnIssueKey($value, $issueKey)
    {
        return $value === (string) $issueKey
            || (null !== $this->guesser->guessIssueKey((string)$issueKey));
    }
}
