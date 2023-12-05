<?php

declare(strict_types=1);

namespace Technodelight\ChatGptExtension\Helpers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\ChatGptExtension\Api\Api;
use Technodelight\Jira\Domain\Issue;
use Technodelight\Jira\Helper\GitBranchnameGenerator;

class BranchnameGenerator extends GitBranchnameGenerator
{
    public function __construct(
        private readonly GitBranchnameGenerator $inner,
        private readonly Api $api
    ) {
    }

    public function fromIssue(Issue $issue): string
    {
        return $this->api->branchName($issue);
    }

    public function fromIssueWithAutocomplete(Issue $issue, InputInterface $input, OutputInterface $output): string
    {
        return $this->inner->fromIssueWithAutocomplete($issue, $input, $output);
    }
}
