<?php

namespace Technodelight\Jira\Console\Command\Show;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Technodelight\Jira\Api\JiraRestApi\Api;
use Technodelight\Jira\Renderer\Action\Renderer;
use Technodelight\Jira\Renderer\Action\Show\User\Error;
use Technodelight\Jira\Renderer\Action\Show\User\Success;

class User extends Command
{
    public function __construct(private readonly Api $api, private readonly Renderer $renderer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('show:user')
            ->addArgument('accountId', InputArgument::OPTIONAL, 'User account ID', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $user = $this->api->user($input->getArgument('accountId'));
            $result = Success::fromUser($user);
        } catch (\Exception $e) {
            $result = Error::fromExceptionAndAccountId($e, $input->getArgument('accountId'));
        } finally {
            $this->renderer->render($output, $result);

            return self::SUCCESS;
        }
    }
}
