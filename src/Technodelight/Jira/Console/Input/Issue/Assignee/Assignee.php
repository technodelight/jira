<?php

declare(strict_types=1);

namespace Technodelight\Jira\Console\Input\Issue\Assignee;

use LogicException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\Argument\IssueKeyResolver;
use Technodelight\Jira\Domain\User;

class Assignee
{
    public function __construct(
        private readonly Api $api,
        private readonly IssueKeyResolver $issueKeyResolver,
        private readonly AssigneeResolver $assigneeResolver,
        private readonly QuestionHelper $questionHelper
    ) {
    }

    public function userPicker(InputInterface $input, OutputInterface $output): mixed
    {
        if (!$input->isInteractive()) {
            throw new LogicException('Input is not interactive, cannot select assignee interactively');
        }

        $assignee = $this->assigneeResolver->resolve($input);
        $issueKey = $this->issueKeyResolver->argument($input, $output);

        if (empty($assignee)) {
            $users = array_map(fn(User $user) => $user->name(), $this->api->assignablePicker($assignee, $issueKey));
            $question = new Question('Please provide a username for assignee');
            $question->setAutocompleterValues($users);
            return $this->questionHelper->ask($input, $output, $question);
        }

        return $assignee;
    }
}
