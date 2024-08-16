<?php

declare(strict_types=1);

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

    public function __construct(
        private readonly Git $git,
        private readonly Guesser $guesser,
        private readonly InteractiveIssueSelector $issueSelector
    ) {}

    public function argument(InputInterface $input, OutputInterface $output): IssueKey
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
                    $prevArgValue = $input->getArgument(self::ARGUMENT);
                    $input->setArgument($argument, (string) $issueKey);
                } else if ($shift && isset($prevArgValue)) {
                    $input->setArgument($argument, $prevArgValue);
                    $prevArgValue = $value;
                    $shift = false;
                }
            }
        }

        if (null === $issueKey) {
            throw new UnexpectedValueException(
                ':\'( Cannot figure out issueKey argument, please specify explicitly'
            );
        }

        return $issueKey;
    }

    public function option(InputInterface $input, OutputInterface $output): ?IssueKey
    {
        if (!$input->hasOption(self::OPTION)) {
            return null;
        }
        $value = $this->resolve($input->getOption(self::OPTION), $input, $output);
        $input->setOption(self::OPTION, (string) $value);
        return $value;
    }

    private function resolve($argumentOrOption, InputInterface $input, OutputInterface $output): ?IssueKey
    {
        $key = $this->guesser->guessIssueKey($argumentOrOption, $this->git->currentBranch());
        if (!empty($key)) {
            return $key;
        }

        return $this->guesser->guessIssueKey($this->issueSelector->chooseIssue($input, $output)->key());
    }

    private function isArgValueAnIssueKey($value, $issueKey): bool
    {
        return $value === (string) $issueKey
            || (null !== $this->guesser->guessIssueKey((string)$issueKey));
    }
}
