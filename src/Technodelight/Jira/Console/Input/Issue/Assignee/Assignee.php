<?php

namespace Technodelight\Jira\Console\Input\Issue\Assignee;

use Hoa\Console\Readline\Autocompleter\Aggregate;
use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Connector\HoaConsole\DefaultUsersAutocomplete;
use Technodelight\Jira\Connector\HoaConsole\UserPickerAutocomplete;

class Assignee
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var AssigneeResolver
     */
    private $assigneeResolver;

    public function __construct(Api $api, AssigneeResolver $assigneeResolver)
    {
        $this->api = $api;
        $this->assigneeResolver = $assigneeResolver;
    }

    public function userPicker(InputInterface $input, OutputInterface $output)
    {
        if (!$input->isInteractive()) {
            throw new \RuntimeException('Input is not interactive, cannot select assigne interactively');
        }

        $readline = new Readline;
        $readline->setAutocompleter(
            new Aggregate([
                new DefaultUsersAutocomplete($this->assigneeResolver),
                new UserPickerAutocomplete($this->api),
            ])
        );
        $output->write('<comment>Please provide a username for assignee:</comment> ');
        return $this->assigneeResolver->fetchValueForDefaultUser($readline->readLine());
    }
}
