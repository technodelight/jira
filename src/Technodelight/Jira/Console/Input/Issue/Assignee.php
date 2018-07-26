<?php

namespace Technodelight\Jira\Console\Input\Issue;

use Hoa\Console\Readline\Readline;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Console\HoaConsole\UserPickerAutocomplete;

class Assignee
{
    /**
     * @var Api
     */
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function userPicker(InputInterface $input, OutputInterface $output)
    {
        if (!$input->isInteractive()) {
            throw new \RuntimeException('Input is not interactive, cannot select assigne interactively');
        }

        $readline = new Readline;
        $readline->setAutocompleter(
            new UserPickerAutocomplete($this->api)
        );
        $output->write('<comment>Please provide a username for assignee:</comment> ');
        return $readline->readLine();
    }
}
